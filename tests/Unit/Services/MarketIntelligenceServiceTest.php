<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Services\AI\MarketIntelligenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\MocksAIService;

class MarketIntelligenceServiceTest extends TestCase
{
    use RefreshDatabase, MocksAIService;

    protected MarketIntelligenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MarketIntelligenceService::class);
    }

    // ---------------------------------------------------------------
    // analyzeJobMarket - complete analysis
    // ---------------------------------------------------------------

    public function test_analyze_job_market_returns_complete_analysis(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Market outlook is strong with 15% YoY growth. AI and cloud technologies present the biggest opportunities.']]
                ],
                'usage' => ['totalTokens' => 150]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $this->seedJobData();

        $result = $this->service->analyzeJobMarket('Software Engineer', 'San Francisco', 'Technology');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('demand_supply', $result);
        $this->assertArrayHasKey('trends', $result);
        $this->assertArrayHasKey('insights', $result);
        $this->assertArrayHasKey('updated_at', $result);
    }

    // ---------------------------------------------------------------
    // analyzeJobMarket - demand/supply metrics
    // ---------------------------------------------------------------

    public function test_analyze_job_market_calculates_demand_supply_metrics(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Market analysis complete.']]
                ],
                'usage' => ['totalTokens' => 50]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $this->seedJobData(currentCount: 50, historicalCount: 40);

        $result = $this->service->analyzeJobMarket();

        $this->assertArrayHasKey('demand_score', $result['demand_supply']);
        $this->assertArrayHasKey('supply_score', $result['demand_supply']);
        $this->assertArrayHasKey('market_health', $result['demand_supply']);
        $this->assertArrayHasKey('growth_rate', $result['demand_supply']);
        $this->assertArrayHasKey('jobs_available', $result['demand_supply']);
        $this->assertArrayHasKey('competition_level', $result['demand_supply']);
    }

    // ---------------------------------------------------------------
    // analyzeJobMarket - caching
    // ---------------------------------------------------------------

    public function test_analyze_job_market_returns_cached_result(): void
    {
        $cachedResult = [
            'demand_supply' => ['growth_rate' => 10],
            'trends' => [],
            'insights' => 'Cached insights',
            'updated_at' => now()->toIso8601String(),
        ];

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($cachedResult);

        $result = $this->service->analyzeJobMarket('Developer');

        $this->assertEquals($cachedResult, $result);
        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------
    // analyzeJobMarket - AI error fallback
    // ---------------------------------------------------------------

    public function test_analyze_job_market_returns_fallback_insight_on_ai_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Service unavailable'], 500)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $this->seedJobData();

        $result = $this->service->analyzeJobMarket();

        $this->assertIsString($result['insights']);
        $this->assertStringContainsString('unavailable', strtolower($result['insights']));
    }

    // ---------------------------------------------------------------
    // analyzeJobMarket - stores snapshot
    // ---------------------------------------------------------------

    public function test_analyze_job_market_stores_snapshot_in_database(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Analysis stored.']]
                ],
                'usage' => ['totalTokens' => 50]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $this->seedJobData();

        $this->service->analyzeJobMarket('Engineer', 'NYC', 'Tech');

        $this->assertDatabaseHas('market_data_snapshots', [
            'snapshot_type' => 'job_market',
            'role' => 'Engineer',
            'location' => 'NYC',
            'industry' => 'Tech',
        ]);
    }

    // ---------------------------------------------------------------
    // getMarketOverview
    // ---------------------------------------------------------------

    public function test_get_market_overview_returns_comprehensive_data(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Overview analysis.']]
                ],
                'usage' => ['totalTokens' => 50]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $this->seedJobData();

        $result = $this->service->getMarketOverview();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_market', $result);
        $this->assertArrayHasKey('top_roles', $result);
        $this->assertArrayHasKey('top_locations', $result);
        $this->assertArrayHasKey('emerging_skills', $result);
        $this->assertArrayHasKey('salary_trends', $result);
    }

    // ---------------------------------------------------------------
    // Edge case: competition classification
    // ---------------------------------------------------------------

    public function test_classifies_competition_level_based_on_applications(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Analysis.']]
                ],
                'usage' => ['totalTokens' => 50]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $this->seedJobData(currentCount: 5, historicalCount: 5);

        // Add many applications to simulate high competition
        foreach (Job::all() as $job) {
            Application::factory()->count(50)->create(['job_id' => $job->id]);
        }

        $result = $this->service->analyzeJobMarket();

        $this->assertContains(
            $result['demand_supply']['competition_level'],
            ['low', 'moderate', 'high', 'very_high']
        );
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    protected function seedJobData(int $currentCount = 20, int $historicalCount = 15): void
    {
        $company = Company::factory()->create(['industry' => 'Technology']);

        // Current jobs (last 90 days)
        Job::factory()->count($currentCount)->create([
            'company_id' => $company->id,
            'title' => 'Software Engineer',
            'location' => 'San Francisco, CA',
            'status' => 'published',
            'expires_at' => now()->addDays(30),
            'created_at' => now()->subDays(30),
            'min_salary' => 100000,
            'max_salary' => 150000,
            'extracted_skills' => [
                'required_skills' => ['PHP', 'Laravel', 'JavaScript'],
            ],
        ]);

        // Historical jobs (90-180 days ago)
        Job::factory()->count($historicalCount)->create([
            'company_id' => $company->id,
            'title' => 'Software Engineer',
            'location' => 'San Francisco, CA',
            'status' => 'published',
            'expires_at' => now()->subDays(60),
            'created_at' => now()->subDays(120),
            'min_salary' => 95000,
            'max_salary' => 140000,
            'extracted_skills' => [
                'required_skills' => ['PHP', 'JavaScript'],
            ],
        ]);
    }
}
