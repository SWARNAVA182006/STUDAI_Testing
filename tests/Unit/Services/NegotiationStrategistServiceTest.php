<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

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

    // ---------------------------------------------------------------
    // generateStrategy - happy path
    // ---------------------------------------------------------------

    public function test_generate_strategy_creates_strategy_record(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'The company values collaboration and has moderate flexibility in salary negotiations.']]
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

    // ---------------------------------------------------------------
    // generateStrategy - optimal range calculation
    // ---------------------------------------------------------------

    public function test_generate_strategy_calculates_optimal_range_above_offer(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Standard tech company with typical salary ranges.']]
                ],
                'usage' => ['totalTokens' => 100]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

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
            'offered_salary' => 140000,
            'current_salary' => 120000,
            'years_experience' => 7,
        ];

        $result = $this->service->generateStrategy($user, $offerData);

        $this->assertGreaterThan($offerData['offered_salary'], $result->optimal_ask);
        $this->assertGreaterThanOrEqual($result->minimum_acceptable, $result->optimal_ask);
        $this->assertGreaterThanOrEqual($result->optimal_ask, $result->stretch_goal);
    }

    // ---------------------------------------------------------------
    // generateStrategy - timing & tone
    // ---------------------------------------------------------------

    public function test_generate_strategy_determines_timing_and_tone(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Standard corporate environment with moderate flexibility.']]
                ],
                'usage' => ['totalTokens' => 80]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $user = $this->createUserWithProfile();

        $offerData = [
            'role' => 'Developer',
            'company_name' => 'Corp Inc',
            'location' => 'Austin',
            'offered_salary' => 100000,
            'years_experience' => 3,
        ];

        $result = $this->service->generateStrategy($user, $offerData);

        $this->assertContains($result->recommended_timing, ['within_24h', 'within_48h', 'within_week']);
        $this->assertNotEmpty($result->timing_rationale);
        $this->assertContains($result->recommended_tone, ['collaborative', 'professional', 'confident']);
    }

    // ---------------------------------------------------------------
    // generateStrategy - AI fallback
    // ---------------------------------------------------------------

    public function test_generate_strategy_produces_result_when_ai_fails(): void
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

        $this->assertInstanceOf(NegotiationStrategy::class, $result);
        $this->assertNotEmpty($result->ai_summary);
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
            'skills' => ['PHP', 'Laravel', 'React'],
            'education' => [
                ['degree' => 'Bachelor', 'field' => 'Computer Science']
            ],
        ];

        Profile::factory()->create(array_merge($defaults, $profileAttributes));

        return $user->fresh(['profile']);
    }
}
