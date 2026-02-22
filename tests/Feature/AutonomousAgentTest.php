<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutonomousAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_dashboard_requires_authentication(): void
    {
        $response = $this->get('/agent');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_agent_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/agent');

        $response->assertStatus(200);
    }

    public function test_configure_requires_authentication(): void
    {
        $response = $this->get('/agent/configure');

        $response->assertRedirect('/login');
    }

    public function test_search_preferences_requires_authentication(): void
    {
        $response = $this->get('/agent/search-preferences');

        $response->assertRedirect('/login');
    }

    public function test_resume_requires_authentication(): void
    {
        $response = $this->get('/agent/resume');

        $response->assertRedirect('/login');
    }

    public function test_activity_requires_authentication(): void
    {
        $response = $this->get('/agent/activity');

        $response->assertRedirect('/login');
    }

    public function test_applications_requires_authentication(): void
    {
        $response = $this->get('/agent/applications');

        $response->assertRedirect('/login');
    }
}
