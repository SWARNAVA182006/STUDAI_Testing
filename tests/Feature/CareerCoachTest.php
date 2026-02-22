<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CareerCoachTest extends TestCase
{
    use RefreshDatabase;

    public function test_career_coach_index_requires_authentication(): void
    {
        $response = $this->get('/career-coach');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_career_coach(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/career-coach');

        $response->assertStatus(200);
    }

    public function test_goals_requires_authentication(): void
    {
        $response = $this->get('/career-coach/goals');

        $response->assertRedirect('/login');
    }

    public function test_checkin_requires_authentication(): void
    {
        $response = $this->get('/career-coach/checkin');

        $response->assertRedirect('/login');
    }

    public function test_history_requires_authentication(): void
    {
        $response = $this->get('/career-coach/history');

        $response->assertRedirect('/login');
    }

    public function test_suggestions_requires_authentication(): void
    {
        $response = $this->get('/career-coach/suggestions');

        $response->assertRedirect('/login');
    }

    public function test_preferences_requires_authentication(): void
    {
        $response = $this->get('/career-coach/preferences');

        $response->assertRedirect('/login');
    }
}
