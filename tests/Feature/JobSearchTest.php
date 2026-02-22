<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_search_page_is_accessible(): void
    {
        $response = $this->get('/jobs/search');

        $response->assertStatus(200);
    }

    public function test_job_search_displays_jobs(): void
    {
        $company = Company::factory()->create(['name' => 'Test Company']);
        $job = Job::factory()->create([
            'company_id' => $company->id,
            'title' => 'Software Engineer',
            'status' => 'published',
        ]);

        $response = $this->get('/jobs/search');

        $response->assertStatus(200);
    }

    public function test_job_detail_page_is_accessible(): void
    {
        $company = Company::factory()->create();
        $job = Job::factory()->create([
            'company_id' => $company->id,
            'status' => 'published',
        ]);

        $response = $this->get("/jobs/{$job->id}");

        $response->assertStatus(200);
    }

    public function test_saved_jobs_requires_authentication(): void
    {
        $response = $this->get('/jobs/saved');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_saved_jobs(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/jobs/saved');

        $response->assertStatus(200);
    }
}
