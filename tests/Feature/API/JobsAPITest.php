<?php

declare(strict_types=1);

namespace Tests\Feature\API;

use App\Models\Company;
use App\Models\JobListing;
use App\Models\SavedJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\Traits\MocksAIService;
use Tests\TestCase;

class JobsAPITest extends TestCase
{
    use RefreshDatabase, MocksAIService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_search_jobs(): void
    {
        JobListing::factory()->count(5)->create(['is_active' => true]);

        $response = $this->getJson('/api/jobs/search');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'company', 'location'],
                ],
                'meta' => ['total', 'per_page', 'current_page'],
            ]);
    }

    public function test_can_search_jobs_with_keyword(): void
    {
        JobListing::factory()->create([
            'title' => 'Senior PHP Developer',
            'is_active' => true,
        ]);
        JobListing::factory()->create([
            'title' => 'Java Developer',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/jobs/search?q=PHP');

        $response->assertStatus(200);
        $this->assertTrue(
            collect($response->json('data'))->contains(fn($job) => str_contains($job['title'], 'PHP'))
        );
    }

    public function test_can_search_jobs_by_location(): void
    {
        JobListing::factory()->create([
            'location' => 'Bangalore',
            'is_active' => true,
        ]);
        JobListing::factory()->create([
            'location' => 'Mumbai',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/jobs/search?location=Bangalore');

        $response->assertStatus(200);
        foreach ($response->json('data') as $job) {
            $this->assertStringContainsString('Bangalore', $job['location']);
        }
    }

    public function test_can_filter_jobs_by_salary_range(): void
    {
        JobListing::factory()->create([
            'salary_min' => 500000,
            'salary_max' => 700000,
            'is_active' => true,
        ]);
        JobListing::factory()->create([
            'salary_min' => 1000000,
            'salary_max' => 1500000,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/jobs/search?salary_min=800000');

        $response->assertStatus(200);
        foreach ($response->json('data') as $job) {
            $this->assertGreaterThanOrEqual(800000, $job['salary_min'] ?? 0);
        }
    }

    public function test_can_filter_jobs_by_experience(): void
    {
        JobListing::factory()->create([
            'experience_min' => 2,
            'experience_max' => 5,
            'is_active' => true,
        ]);
        JobListing::factory()->create([
            'experience_min' => 5,
            'experience_max' => 10,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/jobs/search?experience=3');

        $response->assertStatus(200);
    }

    public function test_can_filter_jobs_by_remote(): void
    {
        JobListing::factory()->create([
            'is_remote' => true,
            'is_active' => true,
        ]);
        JobListing::factory()->create([
            'is_remote' => false,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/jobs/search?remote=true');

        $response->assertStatus(200);
        foreach ($response->json('data') as $job) {
            $this->assertTrue($job['is_remote']);
        }
    }

    public function test_can_get_job_details(): void
    {
        $job = JobListing::factory()->create(['is_active' => true]);

        $response = $this->getJson("/api/jobs/{$job->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'title',
                'description',
                'requirements',
                'company',
                'location',
                'salary_min',
                'salary_max',
            ]);
    }

    public function test_cannot_get_inactive_job(): void
    {
        $job = JobListing::factory()->create(['is_active' => false]);

        $response = $this->getJson("/api/jobs/{$job->id}");

        $response->assertStatus(404);
    }

    public function test_authenticated_user_gets_recommended_jobs(): void
    {
        Sanctum::actingAs($this->user);
        JobListing::factory()->count(5)->create(['is_active' => true]);

        $this->mockAI();

        $response = $this->getJson('/api/jobs/recommended');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'match_score'],
                ],
            ]);
    }

    public function test_recommendations_require_authentication(): void
    {
        $response = $this->getJson('/api/jobs/recommended');

        $response->assertStatus(401);
    }

    public function test_can_get_match_analysis(): void
    {
        Sanctum::actingAs($this->user);
        $job = JobListing::factory()->create(['is_active' => true]);

        $this->mockJobMatching(85.5);

        $response = $this->getJson("/api/jobs/{$job->id}/match-analysis");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'overall_score',
                'skill_match',
                'experience_match',
                'strengths',
                'gaps',
            ]);
    }

    public function test_can_save_job(): void
    {
        Sanctum::actingAs($this->user);
        $job = JobListing::factory()->create(['is_active' => true]);

        $response = $this->postJson('/api/jobs/saved', [
            'job_id' => $job->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('saved_jobs', [
            'user_id' => $this->user->id,
            'job_listing_id' => $job->id,
        ]);
    }

    public function test_cannot_save_job_twice(): void
    {
        Sanctum::actingAs($this->user);
        $job = JobListing::factory()->create(['is_active' => true]);

        SavedJob::create([
            'user_id' => $this->user->id,
            'job_listing_id' => $job->id,
        ]);

        $response = $this->postJson('/api/jobs/saved', [
            'job_id' => $job->id,
        ]);

        $response->assertStatus(409);
    }

    public function test_can_list_saved_jobs(): void
    {
        Sanctum::actingAs($this->user);
        $jobs = JobListing::factory()->count(3)->create(['is_active' => true]);

        foreach ($jobs as $job) {
            SavedJob::create([
                'user_id' => $this->user->id,
                'job_listing_id' => $job->id,
            ]);
        }

        $response = $this->getJson('/api/jobs/saved');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_remove_saved_job(): void
    {
        Sanctum::actingAs($this->user);
        $job = JobListing::factory()->create(['is_active' => true]);
        $savedJob = SavedJob::create([
            'user_id' => $this->user->id,
            'job_listing_id' => $job->id,
        ]);

        $response = $this->deleteJson("/api/jobs/saved/{$savedJob->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('saved_jobs', [
            'id' => $savedJob->id,
        ]);
    }

    public function test_can_apply_to_job(): void
    {
        Sanctum::actingAs($this->user);
        $job = JobListing::factory()->create(['is_active' => true]);

        $response = $this->postJson("/api/jobs/{$job->id}/apply", [
            'cover_letter' => 'I am interested in this position...',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'application_id',
                'status',
            ]);

        $this->assertDatabaseHas('applications', [
            'user_id' => $this->user->id,
            'job_listing_id' => $job->id,
        ]);
    }

    public function test_cannot_apply_to_same_job_twice(): void
    {
        Sanctum::actingAs($this->user);
        $job = JobListing::factory()->create(['is_active' => true]);

        // First application
        $this->postJson("/api/jobs/{$job->id}/apply");

        // Second application
        $response = $this->postJson("/api/jobs/{$job->id}/apply");

        $response->assertStatus(409);
    }

    public function test_job_search_pagination(): void
    {
        JobListing::factory()->count(25)->create(['is_active' => true]);

        $response = $this->getJson('/api/jobs/search?per_page=10&page=2');

        $response->assertStatus(200)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_job_search_sorting(): void
    {
        JobListing::factory()->create([
            'created_at' => now()->subDays(5),
            'is_active' => true,
        ]);
        JobListing::factory()->create([
            'created_at' => now(),
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/jobs/search?sort=newest');

        $response->assertStatus(200);
        $jobs = $response->json('data');
        $this->assertGreaterThanOrEqual(
            $jobs[1]['created_at'] ?? '',
            $jobs[0]['created_at']
        );
    }

    public function test_can_get_similar_jobs(): void
    {
        $job = JobListing::factory()->create(['is_active' => true]);
        JobListing::factory()->count(5)->create(['is_active' => true]);

        $this->mockAI();

        $response = $this->getJson("/api/jobs/{$job->id}/similar");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title'],
                ],
            ]);
    }
}
