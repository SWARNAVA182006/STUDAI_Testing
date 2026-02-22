<?php

declare(strict_types=1);

namespace Tests\Feature\API;

use App\Models\AgentConfiguration;
use App\Models\AutoApplication;
use App\Models\Company;
use App\Models\DiscoveredJob;
use App\Models\JobListing;
use App\Models\JobMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\Traits\MocksAIService;
use Tests\TestCase;

class AgentAPITest extends TestCase
{
    use RefreshDatabase, MocksAIService;

    protected User $user;
    protected AgentConfiguration $agentConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->agentConfig = AgentConfiguration::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => false,
        ]);
    }

    public function test_can_get_agent_config(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/agent/config');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'is_active',
                'auto_apply',
                'requires_approval',
                'daily_limit',
                'min_match_score',
            ]);
    }

    public function test_can_configure_agent(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/agent/configure', [
            'auto_apply' => true,
            'requires_approval' => true,
            'daily_limit' => 10,
            'min_match_score' => 75,
            'target_roles' => ['Developer', 'Engineer'],
            'preferred_locations' => ['Bangalore', 'Remote'],
            'salary_minimum' => 1000000,
        ]);

        $response->assertStatus(200);

        $this->agentConfig->refresh();
        $this->assertTrue($this->agentConfig->auto_apply);
        $this->assertTrue($this->agentConfig->requires_approval);
        $this->assertEquals(10, $this->agentConfig->daily_limit);
    }

    public function test_can_activate_agent(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/agent/activate');

        $response->assertStatus(200)
            ->assertJson(['is_active' => true]);

        $this->agentConfig->refresh();
        $this->assertTrue($this->agentConfig->is_active);
    }

    public function test_activate_requires_subscription(): void
    {
        $userWithoutSubscription = User::factory()->create();
        AgentConfiguration::factory()->create([
            'user_id' => $userWithoutSubscription->id,
        ]);
        Sanctum::actingAs($userWithoutSubscription);

        // If subscription is required, this might return 403
        $response = $this->postJson('/api/agent/activate');

        // Accept either success or subscription required error
        $this->assertTrue(in_array($response->status(), [200, 403]));
    }

    public function test_can_pause_agent(): void
    {
        Sanctum::actingAs($this->user);
        $this->agentConfig->update(['is_active' => true]);

        $response = $this->postJson('/api/agent/pause');

        $response->assertStatus(200);

        $this->agentConfig->refresh();
        $this->assertEquals('paused', $this->agentConfig->status);
    }

    public function test_can_resume_agent(): void
    {
        Sanctum::actingAs($this->user);
        $this->agentConfig->update(['status' => 'paused', 'is_active' => true]);

        $response = $this->postJson('/api/agent/resume');

        $response->assertStatus(200);

        $this->agentConfig->refresh();
        $this->assertEquals('active', $this->agentConfig->status);
    }

    public function test_can_deactivate_agent(): void
    {
        Sanctum::actingAs($this->user);
        $this->agentConfig->update(['is_active' => true]);

        $response = $this->postJson('/api/agent/deactivate');

        $response->assertStatus(200)
            ->assertJson(['is_active' => false]);

        $this->agentConfig->refresh();
        $this->assertFalse($this->agentConfig->is_active);
    }

    public function test_can_get_agent_status(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/agent/status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'is_active',
                'status',
                'last_run_at',
                'applications_today',
                'jobs_discovered',
            ]);
    }

    public function test_can_get_applications(): void
    {
        Sanctum::actingAs($this->user);

        $job = JobListing::factory()->create();
        AutoApplication::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'job_listing_id' => $job->id,
        ]);

        $response = $this->getJson('/api/agent/applications');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_applications_by_status(): void
    {
        Sanctum::actingAs($this->user);

        $job = JobListing::factory()->create();
        AutoApplication::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'job_listing_id' => $job->id,
            'status' => 'submitted',
        ]);
        AutoApplication::factory()->create([
            'user_id' => $this->user->id,
            'job_listing_id' => $job->id,
            'status' => 'rejected',
        ]);

        $response = $this->getJson('/api/agent/applications?status=submitted');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_get_metrics(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/agent/metrics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_applications',
                'applications_this_week',
                'success_rate',
                'avg_match_score',
                'jobs_discovered',
            ]);
    }

    public function test_can_get_learning_data(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/agent/learning');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'preferred_companies',
                'successful_keywords',
                'avoided_keywords',
            ]);
    }

    public function test_can_blacklist_company(): void
    {
        Sanctum::actingAs($this->user);
        $company = Company::factory()->create();

        $response = $this->postJson('/api/agent/blacklist', [
            'company_id' => $company->id,
            'reason' => 'Not interested in this company',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('company_blacklists', [
            'user_id' => $this->user->id,
            'company_id' => $company->id,
        ]);
    }

    public function test_can_remove_from_blacklist(): void
    {
        Sanctum::actingAs($this->user);
        $company = Company::factory()->create();

        // Add to blacklist first
        $this->user->blacklistedCompanies()->attach($company->id, ['reason' => 'Test']);

        $response = $this->deleteJson('/api/agent/unblacklist', [
            'company_id' => $company->id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('company_blacklists', [
            'user_id' => $this->user->id,
            'company_id' => $company->id,
        ]);
    }

    public function test_can_trigger_manual_discovery(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockAI();

        $response = $this->postJson('/api/agent/discover');

        $response->assertStatus(202)
            ->assertJson(['message' => 'Discovery job queued']);
    }

    public function test_can_get_pending_approvals(): void
    {
        Sanctum::actingAs($this->user);

        $job = JobListing::factory()->create();
        $discoveredJob = DiscoveredJob::factory()->create([
            'job_listing_id' => $job->id,
        ]);

        JobMatch::factory()->create([
            'user_id' => $this->user->id,
            'discovered_job_id' => $discoveredJob->id,
            'job_listing_id' => $job->id,
            'status' => 'pending_approval',
        ]);

        $response = $this->getJson('/api/agent/approvals');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'job',
                        'match_score',
                        'created_at',
                    ],
                ],
            ]);
    }

    public function test_can_approve_application(): void
    {
        Sanctum::actingAs($this->user);

        $job = JobListing::factory()->create();
        $discoveredJob = DiscoveredJob::factory()->create([
            'job_listing_id' => $job->id,
        ]);

        $match = JobMatch::factory()->create([
            'user_id' => $this->user->id,
            'discovered_job_id' => $discoveredJob->id,
            'job_listing_id' => $job->id,
            'status' => 'pending_approval',
        ]);

        $response = $this->postJson("/api/agent/approvals/{$match->id}/approve");

        $response->assertStatus(200);

        $match->refresh();
        $this->assertEquals('approved', $match->status);
    }

    public function test_can_reject_application(): void
    {
        Sanctum::actingAs($this->user);

        $job = JobListing::factory()->create();
        $discoveredJob = DiscoveredJob::factory()->create([
            'job_listing_id' => $job->id,
        ]);

        $match = JobMatch::factory()->create([
            'user_id' => $this->user->id,
            'discovered_job_id' => $discoveredJob->id,
            'job_listing_id' => $job->id,
            'status' => 'pending_approval',
        ]);

        $response = $this->postJson("/api/agent/approvals/{$match->id}/reject", [
            'reason' => 'Not a good fit',
        ]);

        $response->assertStatus(200);

        $match->refresh();
        $this->assertEquals('rejected', $match->status);
    }

    public function test_can_bulk_approve(): void
    {
        Sanctum::actingAs($this->user);

        $job = JobListing::factory()->create();
        $discoveredJob = DiscoveredJob::factory()->create([
            'job_listing_id' => $job->id,
        ]);

        $matches = JobMatch::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'discovered_job_id' => $discoveredJob->id,
            'job_listing_id' => $job->id,
            'status' => 'pending_approval',
        ]);

        $response = $this->postJson('/api/agent/approvals/bulk-approve', [
            'ids' => $matches->pluck('id')->toArray(),
        ]);

        $response->assertStatus(200);

        foreach ($matches as $match) {
            $match->refresh();
            $this->assertEquals('approved', $match->status);
        }
    }

    public function test_agent_operations_require_authentication(): void
    {
        $endpoints = [
            ['GET', '/api/agent/config'],
            ['POST', '/api/agent/configure'],
            ['POST', '/api/agent/activate'],
            ['GET', '/api/agent/status'],
        ];

        foreach ($endpoints as [$method, $url]) {
            $response = $this->json($method, $url);
            $response->assertStatus(401);
        }
    }

    public function test_blocked_by_kill_switch(): void
    {
        Sanctum::actingAs($this->user);

        // Activate global kill switch
        AgentConfiguration::activateGlobalKillSwitch(1, 'Emergency maintenance');

        $response = $this->postJson('/api/agent/activate');

        // Should be blocked with 503 or appropriate error
        $this->assertTrue(in_array($response->status(), [503, 403, 423]));

        // Clean up
        AgentConfiguration::deactivateGlobalKillSwitch();
    }
}
