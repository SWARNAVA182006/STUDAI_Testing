<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NegotiationTest extends TestCase
{
    use RefreshDatabase;

    public function test_negotiation_dashboard_requires_authentication(): void
    {
        $response = $this->get('/negotiation');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_negotiation_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/negotiation');

        $response->assertStatus(200);
    }

    public function test_scenarios_requires_authentication(): void
    {
        $response = $this->get('/negotiation/scenarios');

        $response->assertRedirect('/login');
    }

    public function test_templates_requires_authentication(): void
    {
        $response = $this->get('/negotiation/templates');

        $response->assertRedirect('/login');
    }

    public function test_scripts_requires_authentication(): void
    {
        $response = $this->get('/negotiation/scripts');

        $response->assertRedirect('/login');
    }

    public function test_progress_requires_authentication(): void
    {
        $response = $this->get('/negotiation/progress');

        $response->assertRedirect('/login');
    }
}
