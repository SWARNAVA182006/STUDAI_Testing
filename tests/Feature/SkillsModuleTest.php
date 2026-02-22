<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_skills_dashboard_requires_authentication(): void
    {
        $response = $this->get('/skills/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_skills_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/skills/dashboard');

        $response->assertStatus(200);
    }

    public function test_skills_assessments_requires_authentication(): void
    {
        $response = $this->get('/skills/assessments');

        $response->assertRedirect('/login');
    }

    public function test_learning_paths_requires_authentication(): void
    {
        $response = $this->get('/skills/learning-paths');

        $response->assertRedirect('/login');
    }

    public function test_daily_learning_requires_authentication(): void
    {
        $response = $this->get('/skills/daily-learning');

        $response->assertRedirect('/login');
    }

    public function test_skill_validation_requires_authentication(): void
    {
        $response = $this->get('/skills/validation');

        $response->assertRedirect('/login');
    }
}
