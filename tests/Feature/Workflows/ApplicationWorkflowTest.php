<?php

declare(strict_types=1);

namespace Tests\Feature\Workflows;

use App\Models\Application;
use App\Models\Company;
use App\Models\JobListing;
use App\Models\Resume;
use App\Models\User;
use App\Notifications\ApplicationStatusChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\Support\Traits\MocksAIService;
use Tests\TestCase;

class ApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase, MocksAIService;

    protected User $jobSeeker;
    protected User $employer;
    protected Company $company;
    protected JobListing $job;
    protected Resume $resume;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->jobSeeker = User::factory()->create();
        $this->employer = User::factory()->create();
        $this->company = Company::factory()->create([
            'user_id' => $this->employer->id,
        ]);
        $this->job = JobListing::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
        $this->resume = Resume::factory()->create([
            'user_id' => $this->jobSeeker->id,
            'is_primary' => true,
        ]);
    }

    public function test_complete_application_workflow(): void
    {
        $this->mockAI();

        // Step 1: Job seeker searches for jobs
        Sanctum::actingAs($this->jobSeeker);

        $searchResponse = $this->getJson('/api/jobs/search?q=Developer');
        $searchResponse->assertStatus(200);

        // Step 2: Job seeker views job details
        $jobResponse = $this->getJson("/api/jobs/{$this->job->id}");
        $jobResponse->assertStatus(200);

        // Step 3: Job seeker gets match analysis
        $this->mockJobMatching(85.5);
        $matchResponse = $this->getJson("/api/jobs/{$this->job->id}/match-analysis");
        $matchResponse->assertStatus(200);
        $this->assertGreaterThanOrEqual(0, $matchResponse->json('overall_score'));

        // Step 4: Job seeker saves the job
        $saveResponse = $this->postJson('/api/jobs/saved', [
            'job_id' => $this->job->id,
        ]);
        $saveResponse->assertStatus(201);

        // Step 5: Job seeker applies to the job
        $applyResponse = $this->postJson("/api/jobs/{$this->job->id}/apply", [
            'cover_letter' => 'I am excited to apply for this position...',
            'resume_id' => $this->resume->id,
        ]);
        $applyResponse->assertStatus(201);

        $applicationId = $applyResponse->json('application_id');

        // Verify application was created
        $this->assertDatabaseHas('applications', [
            'id' => $applicationId,
            'user_id' => $this->jobSeeker->id,
            'job_listing_id' => $this->job->id,
            'status' => 'pending',
        ]);

        // Step 6: Employer views applications
        Sanctum::actingAs($this->employer);

        $applicationsResponse = $this->getJson("/api/employer/jobs/{$this->job->id}/applications");
        $applicationsResponse->assertStatus(200);
        $this->assertCount(1, $applicationsResponse->json('data'));

        // Step 7: Employer shortlists the candidate
        $shortlistResponse = $this->putJson("/api/employer/applications/{$applicationId}/status", [
            'status' => 'shortlisted',
            'notes' => 'Strong candidate, good skill match',
        ]);
        $shortlistResponse->assertStatus(200);

        // Verify status change was recorded
        $this->assertDatabaseHas('applications', [
            'id' => $applicationId,
            'status' => 'shortlisted',
        ]);

        // Notification sent to job seeker
        Notification::assertSentTo(
            $this->jobSeeker,
            ApplicationStatusChangedNotification::class
        );

        // Step 8: Employer schedules interview
        $interviewResponse = $this->postJson("/api/employer/applications/{$applicationId}/interview", [
            'scheduled_at' => now()->addDays(3)->toDateTimeString(),
            'type' => 'video',
            'notes' => 'Technical interview round',
        ]);
        $interviewResponse->assertStatus(201);

        // Step 9: Employer marks interview completed
        $completeInterviewResponse = $this->putJson("/api/employer/applications/{$applicationId}/status", [
            'status' => 'interviewed',
            'notes' => 'Interview went well, strong technical skills',
        ]);
        $completeInterviewResponse->assertStatus(200);

        // Step 10: Employer makes offer
        $offerResponse = $this->putJson("/api/employer/applications/{$applicationId}/status", [
            'status' => 'offered',
            'notes' => 'Offering 15 LPA with standard benefits',
        ]);
        $offerResponse->assertStatus(200);

        // Verify final state
        $application = Application::find($applicationId);
        $this->assertEquals('offered', $application->status);

        // Verify status history
        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $applicationId,
            'from_status' => 'pending',
            'to_status' => 'shortlisted',
        ]);
    }

    public function test_application_rejection_workflow(): void
    {
        $this->mockAI();
        Sanctum::actingAs($this->jobSeeker);

        // Apply to job
        $applyResponse = $this->postJson("/api/jobs/{$this->job->id}/apply");
        $applicationId = $applyResponse->json('application_id');

        // Employer rejects application
        Sanctum::actingAs($this->employer);

        $rejectResponse = $this->putJson("/api/employer/applications/{$applicationId}/status", [
            'status' => 'rejected',
            'notes' => 'Insufficient experience for the role',
        ]);
        $rejectResponse->assertStatus(200);

        // Verify rejection
        $this->assertDatabaseHas('applications', [
            'id' => $applicationId,
            'status' => 'rejected',
        ]);

        // Notification sent
        Notification::assertSentTo(
            $this->jobSeeker,
            ApplicationStatusChangedNotification::class
        );
    }

    public function test_application_withdrawal_workflow(): void
    {
        $this->mockAI();
        Sanctum::actingAs($this->jobSeeker);

        // Apply to job
        $applyResponse = $this->postJson("/api/jobs/{$this->job->id}/apply");
        $applicationId = $applyResponse->json('application_id');

        // Job seeker withdraws application
        $withdrawResponse = $this->postJson("/api/applications/{$applicationId}/withdraw", [
            'reason' => 'Accepted another offer',
        ]);
        $withdrawResponse->assertStatus(200);

        // Verify withdrawal
        $this->assertDatabaseHas('applications', [
            'id' => $applicationId,
            'status' => 'withdrawn',
        ]);
    }

    public function test_bulk_application_status_update(): void
    {
        Sanctum::actingAs($this->employer);

        // Create multiple applications
        $applications = Application::factory()->count(5)->create([
            'job_listing_id' => $this->job->id,
            'status' => 'pending',
        ]);

        $applicationIds = $applications->pluck('id')->toArray();

        // Bulk update to shortlisted
        $response = $this->postJson('/api/employer/applications/bulk-status', [
            'ids' => array_slice($applicationIds, 0, 3),
            'status' => 'shortlisted',
        ]);
        $response->assertStatus(200);

        // Verify updates
        $this->assertEquals(3, Application::where('status', 'shortlisted')->count());
        $this->assertEquals(2, Application::where('status', 'pending')->count());
    }

    public function test_application_with_ai_enhanced_cover_letter(): void
    {
        $this->mockAIWithResponse('AI-generated cover letter tailored for the role...');
        Sanctum::actingAs($this->jobSeeker);

        // Request AI cover letter generation
        $generateResponse = $this->postJson('/api/ai/generate-cover-letter', [
            'job_id' => $this->job->id,
            'resume_id' => $this->resume->id,
        ]);
        $generateResponse->assertStatus(200);

        $coverLetter = $generateResponse->json('cover_letter');

        // Apply with generated cover letter
        $applyResponse = $this->postJson("/api/jobs/{$this->job->id}/apply", [
            'cover_letter' => $coverLetter,
            'ai_generated' => true,
        ]);
        $applyResponse->assertStatus(201);
    }

    public function test_application_activity_tracking(): void
    {
        $this->mockAI();
        Sanctum::actingAs($this->jobSeeker);

        // Apply to job
        $applyResponse = $this->postJson("/api/jobs/{$this->job->id}/apply");
        $applicationId = $applyResponse->json('application_id');

        // Check activity log
        $activityResponse = $this->getJson("/api/applications/{$applicationId}/activity");
        $activityResponse->assertStatus(200);

        $activities = $activityResponse->json('data');
        $this->assertNotEmpty($activities);
        $this->assertEquals('application_submitted', $activities[0]['action']);
    }

    public function test_employer_views_candidate_profile(): void
    {
        Sanctum::actingAs($this->jobSeeker);

        // Create application
        $application = Application::factory()->create([
            'user_id' => $this->jobSeeker->id,
            'job_listing_id' => $this->job->id,
        ]);

        // Employer views candidate
        Sanctum::actingAs($this->employer);

        $response = $this->getJson("/api/employer/applications/{$application->id}/candidate");
        $response->assertStatus(200)
            ->assertJsonStructure([
                'name',
                'skills',
                'experience',
                'resume',
            ]);

        // Activity logged
        $this->assertDatabaseHas('application_activity_logs', [
            'application_id' => $application->id,
            'action' => 'viewed_by_employer',
        ]);
    }
}
