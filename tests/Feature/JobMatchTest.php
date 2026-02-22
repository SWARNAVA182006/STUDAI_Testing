<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\JobSource;
use App\Models\DiscoveredJob;
use App\Models\JobMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobMatchTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected JobSource $jobSource;
    protected DiscoveredJob $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'account_type' => 'job_seeker',
        ]);

        $this->jobSource = JobSource::create([
            'name' => 'Test Source',
            'type' => 'scraper',
            'url' => 'https://example.com',
            'is_active' => true,
        ]);

        $this->job = DiscoveredJob::create([
            'job_source_id' => $this->jobSource->id,
            'external_id' => 'test-123',
            'url' => 'https://example.com/job/123',
            'title' => 'Senior Laravel Developer',
            'company_name' => 'Tech Corp',
            'description' => 'Looking for experienced Laravel developer',
            'location' => 'Bangalore',
            'salary_min' => 1000000,
            'salary_max' => 1500000,
            'salary_currency' => 'INR',
            'extracted_skills' => json_encode(['Laravel', 'PHP', 'MySQL']),
        ]);
    }

    /** @test */
    public function it_can_create_a_job_match()
    {
        $match = JobMatch::create([
            'user_id' => $this->user->id,
            'discovered_job_id' => $this->job->id,
            'match_score' => 85,
            'skill_match_score' => 90,
            'location_match_score' => 80,
            'salary_match_score' => 85,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('job_matches', [
            'user_id' => $this->user->id,
            'discovered_job_id' => $this->job->id,
            'match_score' => 85,
        ]);
    }

    /** @test */
    public function it_can_determine_if_approved()
    {
        $pendingMatch = JobMatch::create([
            'user_id' => $this->user->id,
            'discovered_job_id' => $this->job->id,
            'match_score' => 75,
            'status' => 'pending',
        ]);

        $approvedMatch = JobMatch::create([
            'user_id' => $this->user->id,
            'discovered_job_id' => DiscoveredJob::factory()->create()->id,
            'match_score' => 90,
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        $this->assertFalse($pendingMatch->isApproved());
        $this->assertTrue($approvedMatch->isApproved());
    }

    /** @test */
    public function approved_scope_returns_only_approved_matches()
    {
        JobMatch::create([
            'user_id' => $this->user->id,
            'discovered_job_id' => $this->job->id,
            'match_score' => 75,
            'status' => 'pending',
        ]);

        JobMatch::create([
            'user_id' => $this->user->id,
            'discovered_job_id' => DiscoveredJob::factory()->create()->id,
            'match_score' => 90,
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        $approvedMatches = JobMatch::approved()->get();
        $this->assertCount(1, $approvedMatches);
        $this->assertEquals('approved', $approvedMatches->first()->status);
    }

    /** @test */
    public function pending_scope_returns_only_pending_matches()
    {
        JobMatch::create([
            'user_id' => $this->user->id,
            'discovered_job_id' => $this->job->id,
            'match_score' => 75,
            'status' => 'pending',
        ]);

        JobMatch::create([
            'user_id' => $this->user->id,
            'discovered_job_id' => DiscoveredJob::factory()->create()->id,
            'match_score' => 90,
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);

        $pendingMatches = JobMatch::pending()->get();
        $this->assertCount(1, $pendingMatches);
        $this->assertEquals('pending', $pendingMatches->first()->status);
    }

    /** @test */
    public function it_calculates_match_grade_correctly()
    {
        $excellentMatch = JobMatch::create([
            'user_id' => $this->user->id,
            'discovered_job_id' => $this->job->id,
            'match_score' => 95,
            'status' => 'pending',
        ]);

        $goodMatch = JobMatch::create([
            'user_id' => $this->user->id,
            'discovered_job_id' => DiscoveredJob::factory()->create()->id,
            'match_score' => 82,
            'status' => 'pending',
        ]);

        $fairMatch = JobMatch::create([
            'user_id' => $this->user->id,
            'discovered_job_id' => DiscoveredJob::factory()->create()->id,
            'match_score' => 72,
            'status' => 'pending',
        ]);

        $poorMatch = JobMatch::create([
            'user_id' => $this->user->id,
            'discovered_job_id' => DiscoveredJob::factory()->create()->id,
            'match_score' => 55,
            'status' => 'pending',
        ]);

        $this->assertEquals('excellent', $excellentMatch->getMatchGrade());
        $this->assertEquals('good', $goodMatch->getMatchGrade());
        $this->assertEquals('fair', $fairMatch->getMatchGrade());
        $this->assertEquals('poor', $poorMatch->getMatchGrade());
    }

    /** @test */
    public function it_calculates_detailed_score_breakdown()
    {
        $match = JobMatch::create([
            'user_id' => $this->user->id,
            'discovered_job_id' => $this->job->id,
            'match_score' => 85,
            'skill_match_score' => 90,
            'location_match_score' => 80,
            'salary_match_score' => 85,
            'experience_match_score' => 88,
            'culture_match_score' => 75,
            'status' => 'pending',
        ]);

        $breakdown = $match->calculateDetailedScoreBreakdown();

        $this->assertArrayHasKey('overall', $breakdown);
        $this->assertArrayHasKey('skills', $breakdown);
        $this->assertArrayHasKey('location', $breakdown);
        $this->assertArrayHasKey('salary', $breakdown);
        $this->assertEquals(85, $breakdown['overall']);
        $this->assertEquals(90, $breakdown['skills']);
    }

    /** @test */
    public function it_calculates_salary_match_score()
    {
        // Perfect match
        $match = new JobMatch();
        $match->user_id = $this->user->id;
        
        $score = $match->calculateSalaryMatch(
            job: $this->job,
            userMinSalary: 1000000,
            userMaxSalary: 1500000
        );

        $this->assertEquals(100, $score);

        // Job salary below user minimum
        $lowSalaryJob = DiscoveredJob::factory()->create([
            'salary_min' => 500000,
            'salary_max' => 800000,
        ]);

        $lowScore = $match->calculateSalaryMatch(
            job: $lowSalaryJob,
            userMinSalary: 1000000,
            userMaxSalary: 1500000
        );

        $this->assertLessThan(50, $lowScore);
    }

    /** @test */
    public function it_calculates_location_match_score()
    {
        $match = new JobMatch();
        $match->user_id = $this->user->id;

        // Exact location match
        $exactMatch = $match->calculateLocationMatch(
            job: $this->job,
            preferredLocations: ['Bangalore', 'Mumbai']
        );
        $this->assertEquals(100, $exactMatch);

        // Remote job
        $remoteJob = DiscoveredJob::factory()->create([
            'location' => 'Remote',
            'work_arrangement' => 'remote',
        ]);

        $remoteScore = $match->calculateLocationMatch(
            job: $remoteJob,
            preferredLocations: ['Bangalore']
        );
        $this->assertGreaterThanOrEqual(90, $remoteScore);

        // No match
        $noMatchJob = DiscoveredJob::factory()->create([
            'location' => 'Delhi',
        ]);

        $noMatchScore = $match->calculateLocationMatch(
            job: $noMatchJob,
            preferredLocations: ['Bangalore', 'Mumbai']
        );
        $this->assertLessThan(50, $noMatchScore);
    }

    /** @test */
    public function it_belongs_to_user_and_discovered_job()
    {
        $match = JobMatch::create([
            'user_id' => $this->user->id,
            'discovered_job_id' => $this->job->id,
            'match_score' => 85,
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(User::class, $match->user);
        $this->assertInstanceOf(DiscoveredJob::class, $match->discoveredJob);
        $this->assertEquals($this->user->id, $match->user->id);
        $this->assertEquals($this->job->id, $match->discoveredJob->id);
    }
}
