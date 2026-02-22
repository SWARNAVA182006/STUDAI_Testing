<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Profile;
use App\Models\User;
use App\Models\UserSkill;
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

    // ---------------------------------------------------------------
    // analyzeUserSkillGaps
    // ---------------------------------------------------------------

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
                        ]
                    ])]]
                ],
                'usage' => [
                    'totalTokens' => 400,
                    'promptTokens' => 180,
                    'completionTokens' => 220,
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

    public function test_analyze_user_skill_gaps_returns_cached_data_when_available(): void
    {
        $cachedGaps = collect([
            ['skill_name' => 'Cached Skill', 'gap_type' => 'missing']
        ]);

        Cache::shouldReceive('has')
            ->with(\Mockery::pattern('/skill_gaps_analysis_/'))
            ->once()
            ->andReturn(true);
        Cache::shouldReceive('get')
            ->with(\Mockery::pattern('/skill_gaps_analysis_/'))
            ->once()
            ->andReturn($cachedGaps);

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

    // ---------------------------------------------------------------
    // getIndustryTrends
    // ---------------------------------------------------------------

    public function test_get_industry_trends_returns_structured_categories(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'emerging' => [
                            ['skill' => 'Rust', 'trend_score' => 88, 'predicted_demand_2025' => 92]
                        ],
                        'growing' => [
                            ['skill' => 'Python', 'growth_rate' => 28, 'current_demand' => 87]
                        ],
                        'declining' => [
                            ['skill' => 'jQuery', 'decline_rate' => -18, 'current_demand' => 25]
                        ],
                        'future_critical' => [
                            ['skill' => 'AI/ML', 'importance_2027' => 95, 'learning_time_months' => 12]
                        ],
                    ])]]
                ],
                'usage' => ['totalTokens' => 350, 'promptTokens' => 120, 'completionTokens' => 230]
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

    public function test_get_industry_trends_returns_empty_categories_on_api_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Service unavailable'], 500)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $result = $this->service->getIndustryTrends('Technology');

        $this->assertIsArray($result);
        $this->assertEmpty($result['emerging']);
        $this->assertEmpty($result['growing']);
        $this->assertEmpty($result['declining']);
        $this->assertEmpty($result['future_critical']);
    }

    // ---------------------------------------------------------------
    // Edge case: user without skills
    // ---------------------------------------------------------------

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

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

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
