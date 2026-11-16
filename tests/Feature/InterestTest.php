<?php

namespace Tests\Feature;

use App\Models\Interest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InterestTest extends TestCase
{
    use RefreshDatabase;

    private function details(array $overrides = []): array
    {
        return array_merge([
            'submission_id' => (string) Str::uuid(),
            'name' => 'Community Member', 'phone' => '0712 345 678',
            'interest' => 'Volunteer', 'consent' => true,
        ], $overrides);
    }

    public function test_submission_is_saved_with_consent_and_cannot_assign_its_own_status(): void
    {
        $this->postJson('/api/interests', $this->details(['status' => 'contacted', 'is_admin' => true]))
            ->assertCreated()->assertJson(['message' => 'Thank you. Your details have been received.']);
        $this->assertDatabaseHas('interests', ['name' => 'Community Member', 'phone' => '0712 345 678', 'interest' => 'Volunteer', 'status' => 'new']);
        $this->assertNotNull(Interest::first()->consented_at);
        $this->getJson('/api/interests')->assertMethodNotAllowed();
    }

    public function test_all_four_involvement_choices_are_supported(): void
    {
        foreach (Interest::OPTIONS as $option) {
            $this->postJson('/api/interests', $this->details(['interest' => $option]))->assertCreated();
            $this->assertDatabaseHas('interests', ['interest' => $option]);
        }
    }

    public function test_validation_rejects_missing_consent_invalid_choices_and_invalid_numbers(): void
    {
        foreach ([['name' => '  '], ['phone' => 'abc'], ['phone' => '123'], ['phone' => ['0712345678']], ['interest' => 'Unknown'], ['consent' => false], ['submission_id' => 'invalid']] as $index => $invalid) {
            // Separate IPs keep this validation check independent of the submission limiter.
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.'.($index + 1)])
                ->postJson('/api/interests', $this->details($invalid))->assertUnprocessable()->assertJsonValidationErrors(array_keys($invalid));
        }
        $this->assertDatabaseCount('interests', 0);
    }

    public function test_retry_does_not_duplicate_a_successful_submission(): void
    {
        $details = $this->details();
        $this->postJson('/api/interests', $details)->assertCreated();
        $this->postJson('/api/interests', $details)->assertCreated();
        $this->assertDatabaseCount('interests', 1);
    }

    public function test_submission_rate_is_limited(): void
    {
        for ($i = 0; $i < 5; $i++) $this->postJson('/api/interests', $this->details())->assertCreated();
        $this->postJson('/api/interests', $this->details())->assertTooManyRequests();
        $this->assertDatabaseCount('interests', 5);
    }
}
