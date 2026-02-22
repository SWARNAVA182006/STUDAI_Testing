<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterviewPrepTest extends TestCase
{
    use RefreshDatabase;

    public function test_interview_index_requires_authentication(): void
    {
        $response = $this->get('/interview');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_interview_prep(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/interview');

        $response->assertStatus(200);
    }

    public function test_interview_create_requires_authentication(): void
    {
        $response = $this->get('/interview/create');

        $response->assertRedirect('/login');
    }

    public function test_interview_tips_requires_authentication(): void
    {
        $response = $this->get('/interview/tips');

        $response->assertRedirect('/login');
    }

    public function test_salary_negotiation_requires_authentication(): void
    {
        $response = $this->get('/interview/salary-negotiation');

        $response->assertRedirect('/login');
    }

    public function test_star_guide_requires_authentication(): void
    {
        $response = $this->get('/interview/star-guide');

        $response->assertRedirect('/login');
    }
}
