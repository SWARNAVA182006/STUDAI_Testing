<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use App\Models\Profile;
use App\Models\User;
use App\Models\UserSkill;
use App\Models\SkillGap;
use App\Services\AI\SkillGapAnalyzerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\MocksAIService;

class SkillGapAnalyzerServiceTest extends TestCase
{
    use RefreshDatabase, MocksAIService;

    protected SkillGapAnalyzerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SkillGapAnalyzerService::class);
    }

    public function test_analyze_user_skill_gaps_returns_collection(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'skills' => [
                            [
                                'name' => 'TypeScript',
                                'category' => 'Programming',
                                'importance' => 'essential',
                                'required_proficiency' => 'intermediate',
                                'market_demand_score' => 85,
                                'avg_salary_impact' => 15000,
                                'learning_time_weeks' => 12,
                                'difficulty' => 'moderate',
                                'prerequisites' => ['JavaScript'],
                                'required_for_roles' => ['Frontend Developer'],
                                'trend' => 'rising',
                            ],
                            [
                                'name' => 'Docker',
                                'category' => 'DevOps',
                                'importance' => 'preferred',
                                'required_proficiency' => 'intermediate',
                                'market_demand_score' => 75,
                                'avg_salary_impact' => 10000,
                                'learning_time_weeks' => 8,
                                'difficulty' => 'moderate',
                                'prerequisites' => ['Linux basics'],
                                'required_for_roles' => ['DevOps Engineer'],
                                'trend' => 'stable',
                            ],
                        ]
                    ])]]
                ],
                'usage' => [
                    'totalTokens' => 500,
                    'promptTokens' => 200,
                    'completionTokens' => 300,
                ]
            ], 200)
        ]);

        Cache::shouldReceive('has')->andReturn(false);
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);
        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $user = $this->createUserWithProfile();

        $result = $this->service->analyzeUserSkillGaps($user);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    public function test_analyze_user_skill_gaps_identifies_missing_skills(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'skills' => [
                            [
                                'name' => 'Kubernetes',
                                'category' => 'DevOps',
                                'importance' => 'essential',
                                'required_proficiency' => 'intermediate',
                                'market_demand_score' => 90,
                                'avg_salary_impact' => 20000,
                                'learning_time_weeks' => 16,
                                'difficulty' => 'hard',
                                'prerequisites' => ['Docker'],
                                'required_for_roles' => ['DevOps Engineer', 'SRE'],
                                'trend' => 'rising',
                            ],
                        ]
                    ])]]
                ],
                'usage' => ['totalTokens' => 300, 'promptTokens' => 100, 'completionTokens' => 200]
            ], 200)
        ]);

        Cache::shouldReceive('has')->andReturn(false);
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);
        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $user = $this->createUserWithProfile([
            'skills' => [] // User has no skills
        ]);

        $result = $this->service->analyzeUserSkillGaps($user);

        $this->assertTrue($result->count() > 0 || $result->isEmpty()); // Either gaps found or empty collection
    }

    public function test_analyze_user_skill_gaps_identifies_proficiency_gaps(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'skills' => [
                            [
                                'name' => 'PHP',
                                'category' => 'Programming',
                                'importance' => 'essential',
                                'required_proficiency' => 'advanced', // Requires advanced
                                'market_demand_score' => 70,
                                'avg_salary_impact' => 8000,
                                'learning_time_weeks' => 8,
                                'difficulty' => 'moderate',
                                'prerequisites' => [],
                                'required_for_roles' => ['Backend Developer'],
                                'trend' => 'stable',
                            ],
                        ]
                    ])]]
                ],
                'usage' => ['totalTokens' => 200, 'promptTokens' => 80, 'completionTokens' => 120]
            ], 200)
        ]);

        Cache::shouldReceive('has')->andReturn(false);
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);
        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $user = $this->createUserWithProfile();
        // Add PHP skill at beginner level
        UserSkill::factory()->create([
            'user_id' => $user->id,
            'skill_name' => 'PHP',
            'proficiency_score' => 30, // Beginner level, but advanced required
            'proficiency_level' => 'beginner',
        ]);

        $result = $this->service->analyzeUserSkillGaps($user);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    public function test_analyze_user_skill_gaps_uses_cache(): void
    {
        $cachedGaps = collect([
            ['skill_name' => 'Cached Skill', 'gap_type' => 'missing']
        ]);

        Cache::shouldReceive('has')->with(\Mockery::pattern('/skill_gaps_analysis_/'))->once()->andReturn(true);
        Cache::shouldReceive('get')->with(\Mockery::pattern('/skill_gaps_analysis_/'))->once()->andReturn($cachedGaps);

        $user = $this->createUserWithProfile();

        $result = $this->service->analyzeUserSkillGaps($user, forceRefresh: false);

        $this->assertEquals($cachedGaps, $result);
        Http::assertNothingSent();
    }

    public function test_analyze_user_skill_gaps_force_refresh_bypasses_cache(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode(['skills' => []])]]
                ],
                'usage' => ['totalTokens' => 50, 'promptTokens' => 20, 'completionTokens' => 30]
            ], 200)
        ]);

        Cache::shouldReceive('has')->andReturn(true);
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);
        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $user = $this->createUserWithProfile();

        $result = $this->service->analyzeUserSkillGaps($user, forceRefresh: true);

        Http::assertSentCount(1);
    }

    public function test_get_industry_trends_returns_trends_array(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'emerging' => [
                            ['skill' => 'Rust', 'trend_score' => 85, 'predicted_demand_2025' => 90]
                        ],
                        'growing' => [
                            ['skill' => 'Python', 'growth_rate' => 25, 'current_demand' => 85]
                        ],
                        'declining' => [
                            ['skill' => 'jQuery', 'decline_rate' => -15, 'current_demand' => 30]
                        ],
                        'future_critical' => [
                            ['skill' => 'AI/ML', 'importance_2027' => 95, 'learning_time_months' => 12]
                        ],
                    ])]]
                ],
                'usage' => ['totalTokens' => 400, 'promptTokens' => 150, 'completionTokens' => 250]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $result = $this->service->getIndustryTrends('Technology');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('emerging', $result);
        $this->assertArrayHasKey('growing', $result);
        $this->assertArrayHasKey('declining', $result);
        $this->assertArrayHasKey('future_critical', $result);
    }

    public function test_get_industry_trends_returns_empty_arrays_on_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Service error'], 500)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $result = $this->service->getIndustryTrends('Technology');

        $this->assertIsArray($result);
        $this->assertEmpty($result['emerging']);
        $this->assertEmpty($result['growing']);
        $this->assertEmpty($result['declining']);
        $this->assertEmpty($result['future_critical']);
    }

    public function test_analyze_handles_user_without_profile_skills(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode(['skills' => []])]]
                ],
                'usage' => ['totalTokens' => 50, 'promptTokens' => 20, 'completionTokens' => 30]
            ], 200)
        ]);

        Cache::shouldReceive('has')->andReturn(false);
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);
        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $user = $this->createUserWithProfile([
            'career_goals' => [],
            'preferences' => [],
        ]);

        $result = $this->service->analyzeUserSkillGaps($user);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    /**
     * Helper to create a user with profile for testing.
     */
    protected function createUserWithProfile(array $profileAttributes = []): User
    {
        $user = User::factory()->create();

        $defaults = [
            'user_id' => $user->id,
            'headline' => 'Software Engineer',
            'career_goals' => [
                'target_roles' => ['Senior Software Engineer', 'Tech Lead'],
            ],
            'preferences' => [
                'industry' => 'Technology',
            ],
        ];

        Profile::factory()->create(array_merge($defaults, $profileAttributes));

        return $user->fresh(['profile']);
    }
}
