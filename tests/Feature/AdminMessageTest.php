<?php

namespace Tests\Feature;

use App\Jobs\SendAudienceSms;
use App\Models\AudienceMessage;
use App\Models\Interest;
use App\Models\User;
use App\Services\SmsGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminMessageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(bool $isAdmin = true): User
    {
        $user = new User(['name' => 'Administrator', 'email' => 'messages@example.test', 'password' => 'test-password-123']);
        $user->is_admin = $isAdmin;
        $user->save();
        $this->actingAs($user);
        Http::preventStrayRequests();
        return $user;
    }

    private function contact(array $values = []): Interest
    {
        return Interest::create(array_merge([
            'submission_id' => (string) Str::uuid(), 'name' => 'Amina', 'phone' => '0712345678',
            'interest' => 'Volunteer', 'consented_at' => now(),
        ], $values));
    }

    private function payload(array $values = []): array
    {
        return array_merge(['title' => 'Meeting reminder', 'body' => 'Hello everyone. Our meeting starts at 10am.', 'intent' => 'review'], $values);
    }

    private function connect(): void
    {
        config(['services.twilio.sid' => 'ACtest', 'services.twilio.token' => 'test-token', 'services.twilio.from' => '+15005550006']);
    }

    public function test_messaging_routes_require_an_administrator(): void
    {
        $owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'test-password-123']);
        $message = AudienceMessage::create(['user_id' => $owner->id, 'title' => 'Private', 'body' => 'Private text', 'filters' => []]);
        foreach (['/admin/messages', '/admin/messages/create', '/admin/messages/audience', '/admin/messages/'.$message->id, '/admin/messages/'.$message->id.'/edit'] as $url) $this->get($url)->assertRedirect('/admin/login');
        $this->post('/admin/messages', $this->payload())->assertRedirect('/admin/login');
        $this->post('/admin/messages/'.$message->id.'/send')->assertRedirect('/admin/login');
        $this->admin(false);
        $this->get('/admin/messages')->assertForbidden();
        $this->get('/admin/messages/audience')->assertForbidden();
        $this->post('/admin/messages', $this->payload())->assertForbidden();
        $this->put('/admin/messages/'.$message->id, $this->payload())->assertForbidden();
        $this->post('/admin/messages/'.$message->id.'/send')->assertForbidden();
    }

    public function test_dashboard_cta_carries_filters_and_composer_shows_reachable_audience(): void
    {
        $this->admin();
        $this->contact();
        $this->contact(['name' => 'Idea person', 'phone' => '0722000000', 'interest' => 'Share an idea']);
        $this->get('/admin?interest=Volunteer&status=new&q=Amina')->assertOk()
            ->assertSee(route('admin.messages.create', ['interest' => 'Volunteer', 'status' => 'new', 'q' => 'Amina']))->assertSee('Send message');
        $this->get('/admin/messages/create?interest=Volunteer')->assertOk()->assertSee('Who is this for?')->assertSee('Review message')->assertSee('1 reachable contact');
        $this->getJson('/admin/messages/audience?interest=Volunteer')->assertOk()->assertJson(['count' => 1, 'excluded' => 0]);
        $this->getJson('/admin/messages/audience?interest=invalid')->assertUnprocessable();
    }

    public function test_review_snapshots_filters_and_normalizes_and_deduplicates_phone_numbers(): void
    {
        $this->admin();
        $this->contact();
        $this->contact(['phone' => '+254 712 345 678']);
        $this->contact(['phone' => '7123456']);
        $this->contact(['phone' => '0722000000', 'interest' => 'Campaign updates']);
        $this->post('/admin/messages', $this->payload(['interest' => 'Volunteer', 'status' => 'new', 'q' => 'Amina']))->assertRedirect();
        $message = AudienceMessage::firstOrFail();
        $this->assertSame('review', $message->status);
        $this->assertSame(2, $message->excluded_count);
        $this->assertSame(['interest' => 'Volunteer', 'status' => 'new', 'q' => 'Amina'], $message->filters);
        $this->assertSame(['+254712345678'], $message->recipients()->pluck('phone')->all());
        $this->contact(['phone' => '0733000000']);
        $this->get('/admin/messages/'.$message->id)->assertOk()
            ->assertViewHas('recipients', fn ($recipients) => $recipients->total() === 1)
            ->assertSee('unique phone numbers')->assertDontSee('+254733000000');
    }

    public function test_empty_audience_can_be_saved_as_draft_but_cannot_be_reviewed(): void
    {
        $this->admin();
        $this->post('/admin/messages', $this->payload())->assertSessionHasErrors('interest');
        $this->assertDatabaseCount('audience_messages', 0);
        $this->post('/admin/messages', $this->payload(['intent' => 'draft']))->assertRedirect();
        $message = AudienceMessage::firstOrFail();
        $this->get('/admin/messages/'.$message->id)->assertOk()->assertSee('Continue editing');
        $this->get('/admin/messages')->assertOk()->assertSee('Meeting reminder')->assertSee('Draft');
        $this->assertDatabaseCount('message_recipients', 0);
    }

    public function test_missing_sms_connection_preserves_review_and_cannot_queue_messages(): void
    {
        $this->admin();
        config(['services.twilio.sid' => null]);
        $this->contact();
        $this->post('/admin/messages', $this->payload());
        $message = AudienceMessage::firstOrFail();
        $this->get('/admin/messages/'.$message->id)->assertOk()->assertSee('Connect SMS to start sending')->assertSee('disabled', false);
        $this->post('/admin/messages/'.$message->id.'/send', ['review_token' => $message->review_token, 'confirm' => '1'])->assertSessionHasErrors('sms');
        $this->assertSame('review', $message->fresh()->status);
        $this->assertDatabaseCount('jobs', 0);
        Http::assertNothingSent();
    }

    public function test_queue_and_worker_submit_exact_review_once_and_keep_submission_status_separate(): void
    {
        $this->admin();
        $this->connect();
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SMtest', 'status' => 'queued'], 201)]);
        $person = $this->contact();
        $this->post('/admin/messages', $this->payload());
        $message = AudienceMessage::firstOrFail();
        $this->contact(['name' => 'Late arrival', 'phone' => '0733000000']);
        $send = ['review_token' => $message->review_token, 'confirm' => '1'];
        $this->post('/admin/messages/'.$message->id.'/send', $send)->assertRedirect('/admin/messages/'.$message->id);
        $this->assertDatabaseCount('jobs', 1);
        $this->post('/admin/messages/'.$message->id.'/send', $send)->assertConflict();
        $this->assertDatabaseCount('jobs', 1);
        $queuedJob = Queue::connection('messages')->pop('messages');
        $queuedJob->fire();
        $queuedJob->delete();
        $recipient = $message->recipients()->firstOrFail();
        $this->assertSame('submitted', $recipient->status);
        $this->assertSame('SMtest', $recipient->provider_id);
        (new SendAudienceSms($recipient->id))->handle(app(SmsGateway::class));
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['To'] === '+254712345678' && $request['Body'] === $message->body && $request['From'] === '+15005550006');
        $this->assertSame('new', $person->fresh()->status);
        $this->get('/admin/messages/'.$message->id)->assertOk()->assertSee('Submitted to SMS service')->assertDontSee('Late arrival');
        $this->get('/admin/messages/'.$message->id.'/edit')->assertConflict();
        $this->put('/admin/messages/'.$message->id, $this->payload())->assertConflict();
    }

    public function test_send_requires_confirmation_latest_review_and_valid_state(): void
    {
        $this->admin();
        $this->connect();
        $this->contact();
        $this->post('/admin/messages', $this->payload());
        $message = AudienceMessage::firstOrFail();
        $oldToken = $message->review_token;
        $this->post('/admin/messages/'.$message->id.'/send', ['review_token' => $oldToken])->assertSessionHasErrors('confirm');
        $this->put('/admin/messages/'.$message->id, $this->payload(['body' => 'An updated message.']))->assertRedirect();
        $this->post('/admin/messages/'.$message->id.'/send', ['review_token' => $oldToken, 'confirm' => '1'])->assertConflict();
        $this->put('/admin/messages/'.$message->id, $this->payload(['intent' => 'draft']));
        $this->post('/admin/messages/'.$message->id.'/send', ['review_token' => $message->fresh()->review_token, 'confirm' => '1'])->assertConflict();
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_queue_failure_rolls_back_message_and_all_recipient_jobs(): void
    {
        $this->admin();
        $this->connect();
        $this->contact();
        $this->post('/admin/messages', $this->payload());
        $message = AudienceMessage::firstOrFail();
        DB::statement('DROP TABLE jobs');
        $this->post('/admin/messages/'.$message->id.'/send', ['review_token' => $message->review_token, 'confirm' => '1'])->assertServerError();
        $this->assertSame('review', $message->fresh()->status);
        $this->assertNull($message->fresh()->queued_at);
    }

    public function test_worker_records_provider_failures_and_does_not_retry_or_send_changed_contacts(): void
    {
        $this->admin();
        $this->connect();
        Http::fake(['api.twilio.com/*' => Http::response(['message' => 'Rejected'], 400)]);
        $first = $this->contact();
        $changed = $this->contact(['phone' => '0722000000']);
        $this->post('/admin/messages', $this->payload());
        $message = AudienceMessage::firstOrFail();
        $message->update(['status' => 'queued']);
        $changed->update(['phone' => '0733000000']);
        foreach ($message->recipients as $recipient) (new SendAudienceSms($recipient->id))->handle(app(SmsGateway::class));
        $this->assertSame('failed', $message->recipients()->where('interest_id', $first->id)->first()->status);
        $this->assertSame('skipped', $message->recipients()->where('interest_id', $changed->id)->first()->status);
        foreach ($message->recipients as $recipient) (new SendAudienceSms($recipient->id))->handle(app(SmsGateway::class));
        Http::assertSentCount(1);
        $this->get('/admin/messages/'.$message->id)->assertOk()->assertSee('Needs attention')->assertSee('Skipped');
    }

    public function test_message_validation_and_html_escaping(): void
    {
        $this->admin();
        $this->contact(['name' => '<script>contact()</script>']);
        $this->post('/admin/messages', $this->payload(['body' => str_repeat('x', 1001)]))->assertSessionHasErrors('body');
        $this->post('/admin/messages', $this->payload(['title' => ' ', 'body' => ' ']))->assertSessionHasErrors(['title', 'body']);
        $this->post('/admin/messages', $this->payload(['body' => '<script>alert(1)</script>']));
        $message = AudienceMessage::firstOrFail();
        $this->get('/admin/messages/'.$message->id)->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('<script>contact()</script>', false);
    }
}
