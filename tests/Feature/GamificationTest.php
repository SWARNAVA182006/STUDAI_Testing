<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_gamification_dashboard_requires_authentication(): void
    {
        $response = $this->get('/gamification');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_gamification(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/gamification');

        $response->assertStatus(200);
    }

    public function test_achievements_requires_authentication(): void
    {
        $response = $this->get('/gamification/achievements');

        $response->assertRedirect('/login');
    }

    public function test_badges_requires_authentication(): void
    {
        $response = $this->get('/gamification/badges');

        $response->assertRedirect('/login');
    }

    public function test_leaderboards_requires_authentication(): void
    {
        $response = $this->get('/gamification/leaderboards');

        $response->assertRedirect('/login');
    }

    public function test_rewards_requires_authentication(): void
    {
        $response = $this->get('/gamification/rewards');

        $response->assertRedirect('/login');
    }

    public function test_challenges_requires_authentication(): void
    {
        $response = $this->get('/gamification/challenges');

        $response->assertRedirect('/login');
    }
}
