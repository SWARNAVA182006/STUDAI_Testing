<?php

declare(strict_types=1);

namespace Tests\Feature\API;

use App\Models\InterviewSession;
use App\Models\InterviewQuestion;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\Traits\MocksAIService;
use Tests\TestCase;

class InterviewAPITest extends TestCase
{
    use RefreshDatabase, MocksAIService;

    protected User $user;
    protected JobListing $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->job = JobListing::factory()->create();
    }

    public function test_can_start_interview_session(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockInterviewQuestions(5);

        $response = $this->postJson('/api/interview/sessions', [
            'job_id' => $this->job->id,
            'question_count' => 5,
            'question_types' => ['behavioral', 'technical'],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'status',
                'total_questions',
                'current_question',
            ]);

        $this->assertDatabaseHas('interview_sessions', [
            'user_id' => $this->user->id,
            'job_listing_id' => $this->job->id,
        ]);
    }

    public function test_can_start_generic_interview_session(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockInterviewQuestions(5);

        $response = $this->postJson('/api/interview/sessions', [
            'role' => 'Software Engineer',
            'question_count' => 5,
        ]);

        $response->assertStatus(201);
    }

    public function test_can_get_session_details(): void
    {
        Sanctum::actingAs($this->user);

        $session = InterviewSession::factory()->create([
            'user_id' => $this->user->id,
            'job_listing_id' => $this->job->id,
        ]);

        $response = $this->getJson("/api/interview/sessions/{$session->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'status',
                'job',
                'questions',
                'progress',
            ]);
    }

    public function test_cannot_access_other_users_session(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $session = InterviewSession::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->getJson("/api/interview/sessions/{$session->id}");

        $response->assertStatus(404);
    }

    public function test_can_abandon_session(): void
    {
        Sanctum::actingAs($this->user);

        $session = InterviewSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'in_progress',
        ]);

        $response = $this->postJson("/api/interview/sessions/{$session->id}/abandon");

        $response->assertStatus(200);

        $session->refresh();
        $this->assertEquals('abandoned', $session->status);
    }

    public function test_can_get_user_history(): void
    {
        Sanctum::actingAs($this->user);

        InterviewSession::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/interview/sessions/user/history');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_get_next_question(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockAI();

        $session = InterviewSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'in_progress',
        ]);

        InterviewQuestion::factory()->count(3)->create([
            'interview_session_id' => $session->id,
        ]);

        $response = $this->getJson("/api/interview/sessions/{$session->id}/next-question");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'question' => [
                    'id',
                    'text',
                    'type',
                    'difficulty',
                ],
                'question_number',
                'total_questions',
            ]);
    }

    public function test_can_submit_answer(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockAI();

        $session = InterviewSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'in_progress',
        ]);

        $question = InterviewQuestion::factory()->create([
            'interview_session_id' => $session->id,
        ]);

        $response = $this->postJson("/api/interview/sessions/{$session->id}/answer", [
            'question_id' => $question->id,
            'answer' => 'This is my detailed answer using the STAR method...',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'received',
                'question_id',
            ]);

        $this->assertDatabaseHas('interview_responses', [
            'interview_session_id' => $session->id,
            'interview_question_id' => $question->id,
        ]);
    }

    public function test_can_get_question_feedback(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockAIWithJSON([
            'score' => 85,
            'strengths' => ['Clear structure', 'Good examples'],
            'improvements' => ['Add more specifics'],
            'recommendations' => ['Practice STAR method'],
        ]);

        $session = InterviewSession::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $question = InterviewQuestion::factory()->create([
            'interview_session_id' => $session->id,
        ]);

        // Create response first
        $session->responses()->create([
            'interview_question_id' => $question->id,
            'user_answer' => 'My answer...',
        ]);

        $response = $this->getJson("/api/interview/sessions/{$session->id}/questions/{$question->id}/feedback");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'score',
                'strengths',
                'improvements',
                'recommendations',
            ]);
    }

    public function test_can_get_session_report(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockAI();

        $session = InterviewSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);

        // Create questions and responses
        $questions = InterviewQuestion::factory()->count(3)->create([
            'interview_session_id' => $session->id,
        ]);

        foreach ($questions as $question) {
            $session->responses()->create([
                'interview_question_id' => $question->id,
                'user_answer' => 'Test answer',
                'score' => rand(70, 95),
            ]);
        }

        $response = $this->getJson("/api/interview/sessions/{$session->id}/report");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'session_id',
                'overall_score',
                'questions_answered',
                'performance_by_type',
                'strengths',
                'areas_for_improvement',
                'recommendations',
            ]);
    }

    public function test_report_requires_completed_session(): void
    {
        Sanctum::actingAs($this->user);

        $session = InterviewSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'in_progress',
        ]);

        $response = $this->getJson("/api/interview/sessions/{$session->id}/report");

        $response->assertStatus(400);
    }

    public function test_session_auto_completes_after_all_answers(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockAI();

        $session = InterviewSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'in_progress',
            'total_questions' => 1,
        ]);

        $question = InterviewQuestion::factory()->create([
            'interview_session_id' => $session->id,
        ]);

        $response = $this->postJson("/api/interview/sessions/{$session->id}/answer", [
            'question_id' => $question->id,
            'answer' => 'Final answer',
        ]);

        $response->assertStatus(200);

        $session->refresh();
        $this->assertEquals('completed', $session->status);
    }

    public function test_answer_with_time_tracking(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockAI();

        $session = InterviewSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'in_progress',
        ]);

        $question = InterviewQuestion::factory()->create([
            'interview_session_id' => $session->id,
        ]);

        $response = $this->postJson("/api/interview/sessions/{$session->id}/answer", [
            'question_id' => $question->id,
            'answer' => 'My answer',
            'time_taken_seconds' => 120,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('interview_responses', [
            'interview_question_id' => $question->id,
            'time_taken_seconds' => 120,
        ]);
    }

    public function test_session_with_video_recording(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockAI();

        $response = $this->postJson('/api/interview/sessions', [
            'role' => 'Software Engineer',
            'question_count' => 3,
            'enable_video' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('video_enabled', true);
    }

    public function test_cannot_answer_same_question_twice(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockAI();

        $session = InterviewSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'in_progress',
        ]);

        $question = InterviewQuestion::factory()->create([
            'interview_session_id' => $session->id,
        ]);

        // First answer
        $this->postJson("/api/interview/sessions/{$session->id}/answer", [
            'question_id' => $question->id,
            'answer' => 'First answer',
        ]);

        // Second answer to same question
        $response = $this->postJson("/api/interview/sessions/{$session->id}/answer", [
            'question_id' => $question->id,
            'answer' => 'Second answer',
        ]);

        $response->assertStatus(409);
    }

    public function test_interview_requires_authentication(): void
    {
        $response = $this->postJson('/api/interview/sessions', [
            'role' => 'Developer',
        ]);

        $response->assertStatus(401);
    }
}
