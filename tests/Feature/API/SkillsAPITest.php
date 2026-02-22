<?php

declare(strict_types=1);

namespace Tests\Feature\API;

use App\Models\LearningPath;
use App\Models\SkillAssessment;
use App\Models\SkillGap;
use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\Traits\MocksAIService;
use Tests\TestCase;

class SkillsAPITest extends TestCase
{
    use RefreshDatabase, MocksAIService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_analyze_skill_gaps(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockSkillGapAnalysis(['React', 'TypeScript']);

        $response = $this->postJson('/api/skills/analyze', [
            'target_role' => 'Senior Full Stack Developer',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'critical_gaps',
                'nice_to_have',
                'emerging_skills',
                'recommendations',
            ]);
    }

    public function test_analyze_requires_target_role(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/skills/analyze', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_role']);
    }

    public function test_can_get_skill_gaps(): void
    {
        Sanctum::actingAs($this->user);

        SkillGap::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/skills/gaps');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_generate_learning_path(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockAIWithJSON([
            'learning_path' => [
                'title' => 'React Mastery',
                'duration' => '2-3 months',
                'milestones' => [
                    ['title' => 'Fundamentals', 'duration' => '2 weeks'],
                    ['title' => 'Hooks & State', 'duration' => '2 weeks'],
                ],
                'resources' => [
                    ['title' => 'React Documentation', 'type' => 'documentation'],
                ],
            ],
        ]);

        $skillGap = SkillGap::factory()->create([
            'user_id' => $this->user->id,
            'skill_name' => 'React',
        ]);

        $response = $this->postJson("/api/skills/learning-path/{$skillGap->id}");

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'title',
                'milestones',
                'resources',
            ]);
    }

    public function test_can_get_learning_path(): void
    {
        Sanctum::actingAs($this->user);

        $learningPath = LearningPath::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/skills/learning-path/{$learningPath->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'title',
                'milestones',
                'progress',
            ]);
    }

    public function test_can_update_learning_progress(): void
    {
        Sanctum::actingAs($this->user);

        $learningPath = LearningPath::factory()->create([
            'user_id' => $this->user->id,
            'progress' => 0,
        ]);

        $response = $this->patchJson('/api/skills/progress', [
            'learning_path_id' => $learningPath->id,
            'milestone_id' => 1,
            'completed' => true,
        ]);

        $response->assertStatus(200);

        $learningPath->refresh();
        $this->assertGreaterThan(0, $learningPath->progress);
    }

    public function test_can_get_daily_recommendations(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockAIWithJSON([
            'recommendations' => [
                ['title' => 'Practice React hooks', 'duration' => '30 minutes'],
                ['title' => 'Read about TypeScript generics', 'duration' => '20 minutes'],
            ],
        ]);

        $response = $this->getJson('/api/skills/daily-recommendations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'recommendations' => [
                    '*' => ['title', 'duration'],
                ],
            ]);
    }

    public function test_can_validate_skill(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/skills/validate', [
            'skill' => 'PHP',
            'proof_type' => 'certification',
            'proof_url' => 'https://example.com/cert/123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'validation_id',
                'status',
            ]);
    }

    public function test_can_generate_assessment(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockAIWithJSON([
            'questions' => [
                ['text' => 'What is dependency injection?', 'type' => 'multiple_choice'],
                ['text' => 'Explain the MVC pattern', 'type' => 'short_answer'],
            ],
        ]);

        $userSkill = UserSkill::factory()->create([
            'user_id' => $this->user->id,
            'skill_name' => 'PHP',
        ]);

        $response = $this->postJson("/api/skills/assessment/{$userSkill->id}");

        $response->assertStatus(201)
            ->assertJsonStructure([
                'assessment_id',
                'skill',
                'question_count',
            ]);
    }

    public function test_can_submit_assessment(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockAIWithJSON([
            'score' => 85,
            'passed' => true,
            'feedback' => 'Great job!',
        ]);

        $assessment = SkillAssessment::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'in_progress',
        ]);

        $response = $this->postJson("/api/skills/assessment/{$assessment->id}/submit", [
            'answers' => [
                ['question_id' => 1, 'answer' => 'A'],
                ['question_id' => 2, 'answer' => 'MVC separates concerns...'],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'score',
                'passed',
                'feedback',
            ]);
    }

    public function test_can_get_assessment_results(): void
    {
        Sanctum::actingAs($this->user);

        $assessment = SkillAssessment::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
            'score' => 85,
        ]);

        $response = $this->getJson("/api/skills/assessment/{$assessment->id}/results");

        $response->assertStatus(200)
            ->assertJson([
                'score' => 85,
            ]);
    }

    public function test_can_get_skill_trends(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockAIWithJSON([
            'trending_up' => ['AI/ML', 'Rust', 'Go'],
            'trending_down' => ['jQuery', 'PHP 5'],
            'emerging' => ['Web3', 'Quantum Computing'],
        ]);

        $response = $this->getJson('/api/skills/trends');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'trending_up',
                'trending_down',
                'emerging',
            ]);
    }

    public function test_can_get_certificate(): void
    {
        // Public endpoint - no auth required
        $assessment = SkillAssessment::factory()->create([
            'status' => 'completed',
            'passed' => true,
            'certificate_hash' => 'abc123',
        ]);

        $response = $this->getJson('/api/skills/certificate/abc123');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'valid',
                'skill',
                'user_name',
                'issued_at',
                'score',
            ]);
    }

    public function test_invalid_certificate_returns_404(): void
    {
        $response = $this->getJson('/api/skills/certificate/invalid_hash');

        $response->assertStatus(404);
    }

    public function test_can_get_user_skills(): void
    {
        Sanctum::actingAs($this->user);

        UserSkill::factory()->count(5)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/skills');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_can_add_skill(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/skills', [
            'skill_name' => 'Kubernetes',
            'proficiency_level' => 'intermediate',
            'years_experience' => 2,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('user_skills', [
            'user_id' => $this->user->id,
            'skill_name' => 'Kubernetes',
        ]);
    }

    public function test_can_update_skill(): void
    {
        Sanctum::actingAs($this->user);

        $skill = UserSkill::factory()->create([
            'user_id' => $this->user->id,
            'proficiency_level' => 'beginner',
        ]);

        $response = $this->putJson("/api/skills/{$skill->id}", [
            'proficiency_level' => 'advanced',
        ]);

        $response->assertStatus(200);

        $skill->refresh();
        $this->assertEquals('advanced', $skill->proficiency_level);
    }

    public function test_can_delete_skill(): void
    {
        Sanctum::actingAs($this->user);

        $skill = UserSkill::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/skills/{$skill->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('user_skills', ['id' => $skill->id]);
    }

    public function test_skills_require_authentication(): void
    {
        $response = $this->getJson('/api/skills');

        $response->assertStatus(401);
    }
}
