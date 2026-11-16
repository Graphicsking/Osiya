<?php

namespace Tests\Feature;

use App\Models\Interest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function user(bool $admin = true): User
    {
        $user = new User(['name' => 'Campaign Admin', 'email' => 'admin@example.test', 'password' => 'a-test-password-123']);
        $user->is_admin = $admin;
        $user->save();

        return $user;
    }

    private function submission(array $overrides = []): Interest
    {
        return Interest::create(array_merge(['submission_id' => (string) Str::uuid(), 'name' => 'Amina Example', 'phone' => '0712345678', 'interest' => 'Volunteer', 'consented_at' => now()], $overrides));
    }

    public function test_guests_and_non_admins_cannot_read_or_update_submissions(): void
    {
        $entry = $this->submission();
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->patch('/admin/interests/'.$entry->id, ['status' => 'contacted'])->assertRedirect('/admin/login');
        $this->actingAs($this->user(false))->get('/admin')->assertForbidden();
        $this->patch('/admin/interests/'.$entry->id, ['status' => 'contacted'])->assertForbidden();
        $this->assertDatabaseHas('interests', ['id' => $entry->id, 'status' => 'new']);
    }

    public function test_admin_can_sign_in_and_sign_out(): void
    {
        $user = $this->user();
        $this->postJson('/admin/login', ['email' => ['invalid'], 'password' => 'incorrect'])->assertUnprocessable();
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'incorrect'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'a-test-password-123'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
        $this->get('/admin')->assertOk()->assertSee('Community submissions')->assertSee('Your next connection starts here.');
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_non_admin_credentials_cannot_sign_in_to_dashboard(): void
    {
        $this->user(false);
        $this->post('/admin/login', ['email' => 'admin@example.test', 'password' => 'a-test-password-123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_dashboard_search_filters_and_status_updates(): void
    {
        $entry = $this->submission();
        $other = $this->submission(['name' => 'Brian Example', 'phone' => '+254722000000', 'interest' => 'Share an idea']);
        $this->actingAs($this->user())->get('/admin')->assertOk()->assertSee('Amina Example')->assertSee('Brian Example')->assertSee('0712345678');
        $this->get('/admin?q=0722')->assertOk()->assertDontSee('Amina Example')->assertDontSee('Brian Example');
        $this->get('/admin?q=254722')->assertOk()->assertSee('Brian Example')->assertDontSee('Amina Example');
        $this->get('/admin?interest=Volunteer')->assertOk()->assertSee('Amina Example')->assertDontSee('Brian Example');
        $this->patch('/admin/interests/'.$entry->id, ['status' => 'contacted'])->assertRedirect();
        $this->assertDatabaseHas('interests', ['id' => $entry->id, 'status' => 'contacted']);
        $this->get('/admin?status=new')->assertOk()->assertSee($other->name)->assertDontSee('Amina Example');
        $this->patch('/admin/interests/'.$entry->id, ['status' => 'other'])->assertSessionHasErrors('status');
    }

    public function test_dashboard_escapes_submitted_names_and_paginates(): void
    {
        $this->submission(['name' => '<script>alert(1)</script>']);
        $this->actingAs($this->user())->get('/admin')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        for ($i = 0; $i < 20; $i++) $this->submission(['name' => 'Member '.$i]);
        $this->get('/admin')->assertOk()->assertSee('Page 1 of 2')->assertDontSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        $this->get('/admin?page=2')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_default_people_cards_support_combined_filters_and_status_updates(): void
    {
        $newVolunteer = $this->submission();
        $contacted = $this->submission(['name' => 'Contacted Volunteer']);
        $contacted->status = 'contacted';
        $contacted->save();
        $this->submission(['name' => 'Idea Member', 'interest' => 'Share an idea']);
        $conversation = $this->submission(['name' => 'Conversation Member', 'interest' => 'Community conversations']);
        $conversation->status = 'contacted';
        $conversation->save();

        $this->actingAs($this->user())->get('/admin?interest=Volunteer&q=Amina&status=new')
            ->assertOk()->assertViewHas('displayView', 'cards')
            ->assertDontSee('Involvement groups')->assertDontSee('class="group-card', false)
            ->assertSee('class="person-card"', false)->assertDontSee('<table>', false)
            ->assertSee('Amina Example')->assertDontSee('Contacted Volunteer')->assertDontSee('Idea Member');

        $this->from('/admin?interest=Volunteer&view=cards')->patch('/admin/interests/'.$newVolunteer->id, ['status' => 'contacted'])
            ->assertRedirect('/admin?interest=Volunteer&view=cards');
        $this->get('/admin?interest=Volunteer&status=new')->assertOk()
            ->assertDontSee('class="person-card"', false)->assertSee('No matching submissions');
    }

    public function test_each_involvement_filter_shows_its_people_and_the_table_remains_available(): void
    {
        foreach (Interest::OPTIONS as $option) $this->submission(['name' => 'Person for '.$option, 'interest' => $option]);
        $this->actingAs($this->user());
        foreach (Interest::OPTIONS as $option) {
            $response = $this->get('/admin?'.http_build_query(['interest' => $option, 'view' => 'cards']));
            $response->assertOk()->assertSee('Person for '.$option)->assertSee('class="person-card"', false);
            foreach (array_diff(Interest::OPTIONS, [$option]) as $other) $response->assertDontSee('Person for '.$other);
        }
        $response = $this->get('/admin?view=table&interest=Volunteer&q=Person&status=new&page=1');
        $response->assertOk()->assertViewHas('displayView', 'table')->assertSee('<table>', false)->assertDontSee('class="person-card"', false)
            ->assertSee('Person for Volunteer')->assertDontSee('Person for Share an idea')
            ->assertSee('name="view" value="table"', false);
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $cardsLink = $xpath->query('//nav[@aria-label="Submission display"]/a[1]')->item(0)->getAttribute('href');
        parse_str(parse_url($cardsLink, PHP_URL_QUERY), $params);
        $this->assertEquals(['view' => 'cards', 'interest' => 'Volunteer', 'q' => 'Person', 'status' => 'new'], $params);
        $this->get('/admin?view=unknown')->assertSessionHasErrors('view');
    }

    public function test_group_pagination_preserves_filters_and_card_view(): void
    {
        for ($i = 0; $i < 21; $i++) $this->submission(['name' => 'Volunteer '.$i]);
        $this->submission(['name' => 'Idea Person', 'interest' => 'Share an idea']);
        $response = $this->actingAs($this->user())->get('/admin?view=cards&interest=Volunteer&status=new&q=Volunteer');
        $response->assertOk()->assertSee('Page 1 of 2')->assertDontSee('Idea Person');
        $paginator = $response->viewData('interests');
        $this->assertCount(20, $paginator);
        $this->assertEquals(21, $paginator->total());
        $this->get($paginator->nextPageUrl())->assertOk()->assertSee('Page 2 of 2')->assertSee('Volunteer 0')->assertDontSee('Volunteer 20')
            ->assertViewHas('displayView', 'cards')->assertViewHas('interests', fn ($entries) => $entries->count() === 1);
    }

    public function test_login_attempts_are_limited(): void
    {
        for ($i = 0; $i < 5; $i++) $this->post('/admin/login', ['email' => 'nobody@example.test', 'password' => 'incorrect'])->assertSessionHasErrors('email');
        $this->post('/admin/login', ['email' => 'nobody@example.test', 'password' => 'incorrect'])->assertTooManyRequests();
    }

    public function test_admin_creation_command_hashes_password_and_creates_admin(): void
    {
        $this->artisan('admin:create')->expectsQuestion('Administrator name', 'Campaign Admin')
            ->expectsQuestion('Administrator email', 'admin@example.test')
            ->expectsQuestion('Password (at least 12 characters)', 'a-test-password-123')
            ->expectsQuestion('Confirm password', 'a-test-password-123')->assertSuccessful();
        $user = User::first();
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('a-test-password-123', $user->password));
    }
}
