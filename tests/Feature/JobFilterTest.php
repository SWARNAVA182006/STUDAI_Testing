<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_filter_jobs_by_employment_type()
    {
        $company = Company::factory()->create();
        
        Job::factory()->create([
            'company_id' => $company->id,
            'title' => 'Full Time Job',
            'employment_type' => 'full-time',
            'status' => 'published',
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        Job::factory()->create([
            'company_id' => $company->id,
            'title' => 'Part Time Job',
            'employment_type' => 'part-time',
            'status' => 'published',
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->get('/jobs/search?job_type[]=full-time');

        $response->assertStatus(200);
        $response->assertSee('Full Time Job');
        $response->assertDontSee('Part Time Job');
    }

    public function test_can_filter_jobs_by_location_type()
    {
        $company = Company::factory()->create();
        
        Job::factory()->create([
            'company_id' => $company->id,
            'title' => 'Remote Job',
            'location_type' => 'remote',
            'status' => 'published',
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        Job::factory()->create([
            'company_id' => $company->id,
            'title' => 'Onsite Job',
            'location_type' => 'onsite',
            'status' => 'published',
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        // Test Remote
        $response = $this->get('/jobs/search?remote=1');
        $response->assertStatus(200);
        $response->assertSee('Remote Job');
        $response->assertDontSee('Onsite Job');

        // Test Onsite
        $response = $this->get('/jobs/search?onsite=1');
        $response->assertStatus(200);
        $response->assertSee('Onsite Job');
        $response->assertDontSee('Remote Job');
    }

    public function test_can_filter_jobs_by_salary()
    {
        $company = Company::factory()->create();
        
        Job::factory()->create([
            'company_id' => $company->id,
            'title' => 'High Paying Job',
            'salary_min' => 2000000,
            'salary_max' => 3000000,
            'status' => 'published',
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        Job::factory()->create([
            'company_id' => $company->id,
            'title' => 'Low Paying Job',
            'salary_min' => 500000,
            'salary_max' => 800000,
            'status' => 'published',
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        // Filter for jobs with max salary >= 15 LPA (1500000)
        // Controller logic: $query->where('salary_max', '>=', $request->salary_min * 100000);
        $response = $this->get('/jobs/search?salary_min=15');

        $response->assertStatus(200);
        $response->assertSee('High Paying Job');
        $response->assertDontSee('Low Paying Job');
    }
}
