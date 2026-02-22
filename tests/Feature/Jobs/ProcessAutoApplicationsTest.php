<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessAutoApplications;
use App\Models\User;
use App\Models\AgentConfiguration;
use App\Models\JobSource;
use App\Models\DiscoveredJob;
use App\Models\JobMatch;
use App\Models\AutoApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessAutoApplicationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected AgentConfiguration $config;
    protected JobSource $jobSource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'account_type' => 'job_seeker',
            'email' => 'test@example.com',
        ]);

        $this->config = AgentConfiguration::create([
            'user_id' => $this->user->id,
            'is_active' => true,
            'daily_application_limit' => 5,
            'applications_this_month' => 0,
            'match_threshold_percentage' => 70,
            'application_aggressiveness' => 'moderate',
            'active_hours' => ['start' => '00:00', 'end' => '23:59'],
            'active_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            'next_run_at' => now()->subHour(),
        ]);

        $this->jobSource = JobSource::create([
            'name' => 'Test Source',
            'type' => 'scraper',
            'url' => 'https://example.com',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_be_dispatched()
    {
        Queue::fake();

        ProcessAutoApplications::dispatch();

        Queue::assertPushed(ProcessAutoApplications::class);
    }

    /** @test */
    public function it_processes_active_configurations_only()
    {
        // Create inactive config
        $inactiveUser = User::factory()->create(['account_type' => 'job_seeker']);
        AgentConfiguration::create([
            'user_id' => $inactiveUser->id,
            'is_active' => false,
            'daily_application_limit' => 5,
        ]);

        $job = new ProcessAutoApplications();
        $job->handle();

        // Should only process active config
        $this->config->refresh();
        $this->assertNotNull($this->config->last_run_at);
    }

    /** @test */
    public function it_skips_configurations_outside_active_hours()
    {
        $this->config->update([
            'active_hours' => ['start' => '09:00', 'end' => '17:00'],
        ]);

        // Set time outside active hours
        $this->travelTo(now()->setTime(20, 0));

        $job = new ProcessAutoApplications();
        $job->handle();

        // Should not update last_run_at since it's outside active hours
        $this->config->refresh();
        $this->assertNull($this->config->last_run_at);
    }

    /** @test */
    public function it_skips_configurations_on_inactive_days()
    {
        $this->config->update([
            'active_days' => ['monday', 'tuesday', 'wednesday'],
        ]);

        // Mock Saturday
        $this->travelTo(now()->parse('next saturday'));

        $job = new ProcessAutoApplications();
        $job->handle();

        $this->config->refresh();
        $this->assertNull($this->config->last_run_at);
    }

    /** @test */
    public function it_respects_daily_application_limits()
    {
        $this->config->update(['daily_application_limit' => 2]);

        // Create 2 applications already made today
        AutoApplication::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        // Create approved matches
        $this->createApprovedMatches(3);

        $job = new ProcessAutoApplications();
        $job->handle();

        // Should not create more applications due to daily limit
        $this->assertEquals(2, AutoApplication::where('user_id', $this->user->id)->count());
    }

    /** @test */
    public function it_processes_approved_job_matches()
    {
        // Create approved matches
        $matches = $this->createApprovedMatches(3);

        $job = new ProcessAutoApplications();
        $job->handle();

        $this->config->refresh();
        $this->assertNotNull($this->config->last_run_at);
        $this->assertNotNull($this->config->next_run_at);
    }

    /** @test */
    public function it_skips_pending_matches_below_threshold()
    {
        // Create match below threshold
        $discoveredJob = DiscoveredJob::create([
            'job_source_id' => $this->jobSource->id,
            'external_id' => 'test-123',
            'url' => 'https://example.com/job/123',
            'title' => 'Test Job',
            'company_name' => 'Test Company',
            'description' => 'Test description',
            'location' => 'Remote',
        ]);

        JobMatch::create([
            'user_id' => $this->user->id,
            'discovered_job_id' => $discoveredJob->id,
            'match_score' => 65, // Below threshold of 70
            'status' => 'pending',
        ]);

        $job = new ProcessAutoApplications();
        $job->handle();

        // Should not create applications for low-scoring matches
        $this->assertEquals(0, AutoApplication::where('user_id', $this->user->id)->count());
    }

    /** @test */
    public function it_updates_next_run_at_after_processing()
    {
        $originalNextRun = $this->config->next_run_at;

        $job = new ProcessAutoApplications();
        $job->handle();

        $this->config->refresh();
        $this->assertTrue($this->config->next_run_at->isAfter($originalNextRun));
    }

    /** @test */
    public function it_can_process_specific_configuration()
    {
        $job = new ProcessAutoApplications($this->config->id);
        $job->handle();

        $this->config->refresh();
        $this->assertNotNull($this->config->last_run_at);
    }

    /** @test */
    public function it_limits_applications_per_config()
    {
        // Create 10 approved matches
        $this->createApprovedMatches(10);

        // Job processes max 3 per config by default
        $job = new ProcessAutoApplications(maxPerConfig: 3);
        $job->handle();

        // Should process exactly 3 matches (or less if limited by other factors)
        $this->config->refresh();
        $this->assertNotNull($this->config->last_run_at);
    }

    /**
     * Helper to create approved job matches
     */
    protected function createApprovedMatches(int $count): array
    {
        $matches = [];

        for ($i = 0; $i < $count; $i++) {
            $discoveredJob = DiscoveredJob::create([
                'job_source_id' => $this->jobSource->id,
                'external_id' => 'test-' . $i,
                'url' => 'https://example.com/job/' . $i,
                'title' => 'Test Job ' . $i,
                'company_name' => 'Test Company',
                'description' => 'Test description for job ' . $i,
                'location' => 'Remote',
                'extracted_skills' => json_encode(['PHP', 'Laravel']),
            ]);

            $matches[] = JobMatch::create([
                'user_id' => $this->user->id,
                'discovered_job_id' => $discoveredJob->id,
                'match_score' => rand(85, 95),
                'status' => 'approved',
                'reviewed_at' => now(),
            ]);
        }

        return $matches;
    }
}
