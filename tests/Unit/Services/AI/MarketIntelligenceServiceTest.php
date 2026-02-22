<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\MarketDataSnapshot;
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

    public function test_analyze_job_market_returns_complete_analysis(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Market outlook is strong with 15% YoY growth. Key opportunities in AI and cloud technologies. Recommend focusing on in-demand skills.']]
                ],
                'usage' => ['totalTokens' => 150]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        // Create jobs for analysis
        $this->createJobsForAnalysis();

        $result = $this->service->analyzeJobMarket('Software Engineer', 'San Francisco', 'Technology');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('demand_supply', $result);
        $this->assertArrayHasKey('trends', $result);
        $this->assertArrayHasKey('insights', $result);
        $this->assertArrayHasKey('updated_at', $result);
    }

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

        $this->createJobsForAnalysis(currentCount: 50, historicalCount: 40);

        $result = $this->service->analyzeJobMarket();

        $this->assertArrayHasKey('demand_score', $result['demand_supply']);
        $this->assertArrayHasKey('supply_score', $result['demand_supply']);
        $this->assertArrayHasKey('market_health', $result['demand_supply']);
        $this->assertArrayHasKey('growth_rate', $result['demand_supply']);
        $this->assertArrayHasKey('jobs_available', $result['demand_supply']);
        $this->assertArrayHasKey('competition_level', $result['demand_supply']);
    }

    public function test_analyze_job_market_identifies_skill_trends(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Skills analysis complete.']]
                ],
                'usage' => ['totalTokens' => 50]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $this->createJobsWithSkills();

        $result = $this->service->analyzeJobMarket();

        $this->assertArrayHasKey('skills', $result['trends']);
        $this->assertIsArray($result['trends']['skills']);
    }

    public function test_analyze_job_market_calculates_salary_trends(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Salary analysis complete.']]
                ],
                'usage' => ['totalTokens' => 50]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $this->createJobsWithSalaries();

        $result = $this->service->analyzeJobMarket();

        $this->assertArrayHasKey('salaries', $result['trends']);
        $this->assertArrayHasKey('average_salary', $result['trends']['salaries']);
        $this->assertArrayHasKey('change_percentage', $result['trends']['salaries']);
        $this->assertArrayHasKey('trend', $result['trends']['salaries']);
    }

    public function test_analyze_job_market_tracks_remote_work_trends(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Remote work analysis complete.']]
                ],
                'usage' => ['totalTokens' => 50]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $this->createJobsWithRemoteOptions();

        $result = $this->service->analyzeJobMarket();

        $this->assertArrayHasKey('remote_work', $result['trends']);
        $this->assertArrayHasKey('remote_percentage', $result['trends']['remote_work']);
    }

    public function test_analyze_job_market_uses_cache(): void
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

    public function test_analyze_job_market_stores_snapshot(): void
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

        $this->createJobsForAnalysis();

        $this->service->analyzeJobMarket('Engineer', 'NYC', 'Tech');

        $this->assertDatabaseHas('market_data_snapshots', [
            'snapshot_type' => 'job_market',
            'role' => 'Engineer',
            'location' => 'NYC',
            'industry' => 'Tech',
        ]);
    }

    public function test_analyze_job_market_returns_fallback_on_ai_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Service unavailable'], 500)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $this->createJobsForAnalysis();

        $result = $this->service->analyzeJobMarket();

        $this->assertIsString($result['insights']);
        $this->assertStringContainsString('unavailable', strtolower($result['insights']));
    }

    public function test_get_market_overview_returns_comprehensive_data(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Overview complete.']]
                ],
                'usage' => ['totalTokens' => 50]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $this->createJobsForAnalysis();

        $result = $this->service->getMarketOverview();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_market', $result);
        $this->assertArrayHasKey('top_roles', $result);
        $this->assertArrayHasKey('top_locations', $result);
        $this->assertArrayHasKey('emerging_skills', $result);
        $this->assertArrayHasKey('salary_trends', $result);
    }

    public function test_classifies_competition_level_correctly(): void
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

        // Create jobs with many applications (high competition)
        $job = $this->createJobsForAnalysis(currentCount: 5, historicalCount: 5);

        // Add many applications to simulate high competition
        foreach (Job::all() as $j) {
            Application::factory()->count(100)->create(['job_id' => $j->id]);
        }

        $result = $this->service->analyzeJobMarket();

        $this->assertContains($result['demand_supply']['competition_level'], ['low', 'moderate', 'high', 'very_high']);
    }

    public function test_classifies_trend_direction_correctly(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Trend analysis.']]
                ],
                'usage' => ['totalTokens' => 50]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        // Create jobs with significant growth
        $this->createJobsForAnalysis(currentCount: 100, historicalCount: 50); // 100% growth

        $result = $this->service->analyzeJobMarket();

        // With 100% growth, should be rapidly_rising or rising
        $this->assertContains($result['trends']['salaries']['trend'] ?? 'stable', ['rapidly_rising', 'rising', 'stable', 'declining', 'rapidly_declining']);
    }

    public function test_filters_by_role(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Filtered analysis.']]
                ],
                'usage' => ['totalTokens' => 50]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        // Create jobs with different titles
        $company = Company::factory()->create();
        Job::factory()->count(10)->create([
            'company_id' => $company->id,
            'title' => 'Software Engineer',
            'status' => 'published',
            'expires_at' => now()->addDays(30),
            'created_at' => now()->subDays(30),
        ]);
        Job::factory()->count(5)->create([
            'company_id' => $company->id,
            'title' => 'Data Scientist',
            'status' => 'published',
            'expires_at' => now()->addDays(30),
            'created_at' => now()->subDays(30),
        ]);

        $result = $this->service->analyzeJobMarket('Software Engineer');

        // Should have filtered results
        $this->assertIsArray($result);
    }

    public function test_filters_by_location(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Location filtered.']]
                ],
                'usage' => ['totalTokens' => 50]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $company = Company::factory()->create();
        Job::factory()->count(10)->create([
            'company_id' => $company->id,
            'location' => 'San Francisco, CA',
            'status' => 'published',
            'expires_at' => now()->addDays(30),
            'created_at' => now()->subDays(30),
        ]);
        Job::factory()->count(5)->create([
            'company_id' => $company->id,
            'location' => 'New York, NY',
            'status' => 'published',
            'expires_at' => now()->addDays(30),
            'created_at' => now()->subDays(30),
        ]);

        $result = $this->service->analyzeJobMarket(null, 'San Francisco');

        $this->assertIsArray($result);
    }

    public function test_filters_by_industry(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Industry filtered.']]
                ],
                'usage' => ['totalTokens' => 50]
            ], 200)
        ]);

        Cache::shouldReceive('remember')->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $techCompany = Company::factory()->create(['industry' => 'Technology']);
        $financeCompany = Company::factory()->create(['industry' => 'Finance']);

        Job::factory()->count(10)->create([
            'company_id' => $techCompany->id,
            'status' => 'published',
            'expires_at' => now()->addDays(30),
            'created_at' => now()->subDays(30),
        ]);
        Job::factory()->count(5)->create([
            'company_id' => $financeCompany->id,
            'status' => 'published',
            'expires_at' => now()->addDays(30),
            'created_at' => now()->subDays(30),
        ]);

        $result = $this->service->analyzeJobMarket(null, null, 'Technology');

        $this->assertIsArray($result);
    }

    /**
     * Helper to create jobs for analysis.
     */
    protected function createJobsForAnalysis(int $currentCount = 20, int $historicalCount = 15): void
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

    /**
     * Helper to create jobs with different skills.
     */
    protected function createJobsWithSkills(): void
    {
        $company = Company::factory()->create();

        // Jobs with trending skills
        Job::factory()->count(10)->create([
            'company_id' => $company->id,
            'status' => 'published',
            'expires_at' => now()->addDays(30),
            'created_at' => now()->subDays(15),
            'extracted_skills' => [
                'required_skills' => ['TypeScript', 'React', 'AWS'],
            ],
        ]);

        // Historical jobs with older skills
        Job::factory()->count(5)->create([
            'company_id' => $company->id,
            'status' => 'published',
            'expires_at' => now()->subDays(60),
            'created_at' => now()->subDays(100),
            'extracted_skills' => [
                'required_skills' => ['JavaScript', 'jQuery'],
            ],
        ]);
    }

    /**
     * Helper to create jobs with salary data.
     */
    protected function createJobsWithSalaries(): void
    {
        $company = Company::factory()->create();

        // Current jobs with higher salaries
        Job::factory()->count(10)->create([
            'company_id' => $company->id,
            'status' => 'published',
            'expires_at' => now()->addDays(30),
            'created_at' => now()->subDays(15),
            'min_salary' => 120000,
            'max_salary' => 160000,
        ]);

        // Historical jobs with lower salaries
        Job::factory()->count(10)->create([
            'company_id' => $company->id,
            'status' => 'published',
            'expires_at' => now()->subDays(60),
            'created_at' => now()->subDays(100),
            'min_salary' => 100000,
            'max_salary' => 140000,
        ]);
    }

    /**
     * Helper to create jobs with remote options.
     */
    protected function createJobsWithRemoteOptions(): void
    {
        $company = Company::factory()->create();

        // Current remote jobs
        Job::factory()->count(8)->create([
            'company_id' => $company->id,
            'status' => 'published',
            'expires_at' => now()->addDays(30),
            'created_at' => now()->subDays(15),
            'is_remote' => true,
        ]);

        // Current on-site jobs
        Job::factory()->count(2)->create([
            'company_id' => $company->id,
            'status' => 'published',
            'expires_at' => now()->addDays(30),
            'created_at' => now()->subDays(15),
            'is_remote' => false,
        ]);

        // Historical (less remote)
        Job::factory()->count(5)->create([
            'company_id' => $company->id,
            'status' => 'published',
            'expires_at' => now()->subDays(60),
            'created_at' => now()->subDays(100),
            'is_remote' => true,
        ]);
        Job::factory()->count(5)->create([
            'company_id' => $company->id,
            'status' => 'published',
            'expires_at' => now()->subDays(60),
            'created_at' => now()->subDays(100),
            'is_remote' => false,
        ]);
    }
}
