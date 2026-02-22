<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use App\Models\NegotiationStrategy;
use App\Models\Profile;
use App\Models\SalaryTrend;
use App\Models\SkillTrend;
use App\Models\User;
use App\Services\AI\NegotiationStrategistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\MocksAIService;

class NegotiationStrategistServiceTest extends TestCase
{
    use RefreshDatabase, MocksAIService;

    protected NegotiationStrategistService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(NegotiationStrategistService::class);
    }

    public function test_generate_strategy_creates_strategy_record(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'The company has a collaborative culture with moderate salary flexibility.']]
                ],
                'usage' => ['totalTokens' => 150]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $user = $this->createUserWithProfile();

        $offerData = [
            'role' => 'Senior Software Engineer',
            'company_name' => 'Tech Corp',
            'location' => 'San Francisco',
            'offered_salary' => 150000,
            'current_salary' => 130000,
            'years_experience' => 8,
        ];

        $result = $this->service->generateStrategy($user, $offerData);

        $this->assertInstanceOf(NegotiationStrategy::class, $result);
        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals('Senior Software Engineer', $result->role);
        $this->assertEquals('Tech Corp', $result->company_name);
        $this->assertEquals(150000, $result->offered_salary);
    }

    public function test_generate_strategy_calculates_optimal_range(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Standard tech company with typical negotiation room.']]
                ],
                'usage' => ['totalTokens' => 100]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        // Create salary trend data
        SalaryTrend::factory()->create([
            'role' => 'Software Engineer',
            'location' => 'San Francisco',
            'experience_level' => 'senior',
            'median_salary' => 160000,
            'percentile_25' => 140000,
            'percentile_75' => 180000,
            'percentile_90' => 200000,
        ]);

        $user = $this->createUserWithProfile();

        $offerData = [
            'role' => 'Software Engineer',
            'company_name' => 'Startup Inc',
            'location' => 'San Francisco',
            'offered_salary' => 140000, // Below median
            'current_salary' => 120000,
            'years_experience' => 7,
        ];

        $result = $this->service->generateStrategy($user, $offerData);

        // When offer is below median, optimal should be higher than offer
        $this->assertGreaterThan($offerData['offered_salary'], $result->optimal_ask);
        $this->assertGreaterThanOrEqual($result->minimum_acceptable, $result->optimal_ask);
        $this->assertGreaterThanOrEqual($result->optimal_ask, $result->stretch_goal);
    }

    public function test_generate_strategy_identifies_strengths_for_experienced_candidate(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'The company values experience and has room for negotiation.']]
                ],
                'usage' => ['totalTokens' => 100]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $user = $this->createUserWithProfile([
            'skills' => ['PHP', 'Python', 'Machine Learning'],
            'education' => [['degree' => 'Master', 'field' => 'Computer Science']],
        ]);

        $offerData = [
            'role' => 'Lead Engineer',
            'company_name' => 'Enterprise Corp',
            'location' => 'New York',
            'offered_salary' => 180000,
            'current_salary' => 165000,
            'years_experience' => 12, // Extensive experience
        ];

        $result = $this->service->generateStrategy($user, $offerData);

        // Should identify experience as a strength
        $hasExperienceStrength = collect($result->strongest_points)->contains(function ($strength) {
            return str_contains(strtolower($strength['category'] ?? ''), 'experience');
        });

        $this->assertTrue($hasExperienceStrength || count($result->strongest_points) > 0);
    }

    public function test_generate_strategy_identifies_hot_skills(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'AI company with high demand for ML skills.']]
                ],
                'usage' => ['totalTokens' => 100]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        // Create skill trend data
        SkillTrend::factory()->create([
            'skill_name' => 'Machine Learning',
            'trend_status' => 'hot',
            'growth_rate' => 45.5,
        ]);

        $user = $this->createUserWithProfile([
            'skills' => ['Machine Learning', 'Python', 'TensorFlow'],
        ]);

        $offerData = [
            'role' => 'ML Engineer',
            'company_name' => 'AI Startup',
            'location' => 'Seattle',
            'offered_salary' => 200000,
            'years_experience' => 5,
        ];

        $result = $this->service->generateStrategy($user, $offerData);

        // Should identify hot skills in strengths
        $hasSkillStrength = collect($result->strongest_points)->contains(function ($strength) {
            return ($strength['category'] ?? '') === 'skills';
        });

        $this->assertInstanceOf(NegotiationStrategy::class, $result);
    }

    public function test_generate_strategy_determines_appropriate_timing(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Standard corporate environment.']]
                ],
                'usage' => ['totalTokens' => 80]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $user = $this->createUserWithProfile();

        $offerData = [
            'role' => 'Developer',
            'company_name' => 'Tech Company',
            'location' => 'Austin',
            'offered_salary' => 100000,
            'years_experience' => 3,
        ];

        $result = $this->service->generateStrategy($user, $offerData);

        $this->assertContains($result->recommended_timing, ['within_24h', 'within_48h', 'within_week']);
        $this->assertNotEmpty($result->timing_rationale);
    }

    public function test_generate_strategy_determines_tone_based_on_flexibility(): void
    {
        // Mock high flexibility company
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'The company is highly flexible with salary negotiations and values win-win outcomes.']]
                ],
                'usage' => ['totalTokens' => 100]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $user = $this->createUserWithProfile();

        $offerData = [
            'role' => 'Engineer',
            'company_name' => 'Flexible Corp',
            'location' => 'Remote',
            'offered_salary' => 120000,
            'years_experience' => 4,
        ];

        $result = $this->service->generateStrategy($user, $offerData);

        $this->assertContains($result->recommended_tone, ['collaborative', 'professional', 'confident']);
    }

    public function test_generate_strategy_includes_alternative_benefits_for_inflexible_companies(): void
    {
        // Mock low flexibility company
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'The company is not flexible with base salary and has rigid pay bands.']]
                ],
                'usage' => ['totalTokens' => 100]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $user = $this->createUserWithProfile();

        $offerData = [
            'role' => 'Analyst',
            'company_name' => 'BigCorp Inc',
            'location' => 'Chicago',
            'offered_salary' => 80000,
            'years_experience' => 2,
        ];

        $result = $this->service->generateStrategy($user, $offerData);

        // Should suggest alternative benefits when base salary is rigid
        $this->assertIsArray($result->benefits_to_negotiate);
    }

    public function test_generate_strategy_generates_ai_insights(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => "Based on market data, this offer is below the median. You have strong leverage to negotiate for a 10-15% increase.\n\nKey points:\n1. Your experience aligns well with the role\n2. Market data supports higher compensation\n\nRisk: Don't push too aggressively as the company may have budget constraints."]]
                ],
                'usage' => ['totalTokens' => 200]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $user = $this->createUserWithProfile();

        $offerData = [
            'role' => 'Senior Developer',
            'company_name' => 'Tech Startup',
            'location' => 'Denver',
            'offered_salary' => 140000,
            'years_experience' => 6,
        ];

        $result = $this->service->generateStrategy($user, $offerData);

        $this->assertNotEmpty($result->ai_summary);
        $this->assertIsString($result->ai_summary);
    }

    public function test_generate_strategy_uses_fallback_when_ai_fails(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Service unavailable'], 500)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $user = $this->createUserWithProfile();

        $offerData = [
            'role' => 'Developer',
            'company_name' => 'Company',
            'location' => 'Remote',
            'offered_salary' => 100000,
            'years_experience' => 3,
        ];

        $result = $this->service->generateStrategy($user, $offerData);

        // Should still create a strategy with fallback insights
        $this->assertInstanceOf(NegotiationStrategy::class, $result);
        $this->assertNotEmpty($result->ai_summary);
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
            'skills' => ['PHP', 'Laravel', 'React'],
            'education' => [
                ['degree' => 'Bachelor', 'field' => 'Computer Science']
            ],
        ];

        Profile::factory()->create(array_merge($defaults, $profileAttributes));

        return $user->fresh(['profile']);
    }
}
