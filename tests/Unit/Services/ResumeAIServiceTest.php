<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\Job;
use App\Models\Resume;
use App\Models\User;
use App\Services\AI\ResumeAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\MocksAIService;

class ResumeAIServiceTest extends TestCase
{
    use RefreshDatabase, MocksAIService;

    protected ResumeAIService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ResumeAIService::class);
    }

    // ---------------------------------------------------------------
    // generateProfessionalSummary
    // ---------------------------------------------------------------

    public function test_generate_professional_summary_returns_string(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Seasoned software engineer with 5+ years building scalable web applications.']]
                ],
                'usage' => ['totalTokens' => 90]
            ], 200)
        ]);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $resume = $this->createResume();

        $result = $this->service->generateProfessionalSummary($resume);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_generate_professional_summary_with_target_job_includes_role_context(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Results-driven developer with deep expertise in Laravel, seeking Senior role at Tech Corp.']]
                ],
                'usage' => ['totalTokens' => 110]
            ], 200)
        ]);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $resume = $this->createResume();
        $job = $this->createJob();

        $result = $this->service->generateProfessionalSummary($resume, $job);

        $this->assertIsString($result);

        Http::assertSent(function ($request) {
            $messages = $request->data()['messages'] ?? [];
            $userMessage = $messages[1]['content'] ?? '';
            return str_contains($userMessage, 'Target Role');
        });
    }

    public function test_generate_professional_summary_returns_fallback_on_api_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Service unavailable'], 500)
        ]);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $resume = $this->createResume([
            'skills' => ['technical' => ['PHP', 'Laravel', 'MySQL']]
        ]);

        $result = $this->service->generateProfessionalSummary($resume);

        $this->assertIsString($result);
        $this->assertStringContainsString('professional', strtolower($result));
    }

    // ---------------------------------------------------------------
    // optimizeExperienceBullets
    // ---------------------------------------------------------------

    public function test_optimize_experience_bullets_returns_optimized_array(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'bullets' => [
                            ['original' => 'Managed a team', 'optimized' => 'Led a team of 8 engineers delivering 3 projects on time', 'confidence' => 92],
                            ['original' => 'Fixed bugs', 'optimized' => 'Reduced production defects by 45% through systematic QA', 'confidence' => 87],
                        ]
                    ])]]
                ],
                'usage' => ['totalTokens' => 140]
            ], 200)
        ]);

        $bullets = ['Managed a team', 'Fixed bugs'];
        $result = $this->service->optimizeExperienceBullets($bullets, 'Software Engineer');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('optimized', $result[0]);
        $this->assertArrayHasKey('confidence', $result[0]);
    }

    public function test_optimize_experience_bullets_handles_empty_input(): void
    {
        $result = $this->service->optimizeExperienceBullets([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_optimize_experience_bullets_returns_originals_on_api_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Server error'], 500)
        ]);

        $bullets = ['Built systems', 'Wrote documentation'];
        $result = $this->service->optimizeExperienceBullets($bullets);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Built systems', $result[0]['original']);
        $this->assertEquals(0, $result[0]['confidence']);
    }

    // ---------------------------------------------------------------
    // analyzeATSCompatibility
    // ---------------------------------------------------------------

    public function test_analyze_ats_compatibility_returns_structured_result(): void
    {
        $resume = $this->createResume([
            'email' => 'dev@example.com',
            'phone' => '555-9999',
            'location' => 'New York, NY',
            'linkedin_url' => 'https://linkedin.com/in/dev',
            'experience' => [
                ['title' => 'Engineer', 'description' => 'Increased revenue by 25%']
            ],
            'education' => [
                ['degree' => 'BS', 'field' => 'Computer Science']
            ],
            'skills' => ['PHP', 'JavaScript'],
        ]);

        $result = $this->service->analyzeATSCompatibility($resume);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('issues', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    public function test_analyze_ats_compatibility_flags_missing_experience(): void
    {
        $resume = $this->createResume([
            'experience' => [],
            'education' => [],
            'skills' => [],
        ]);

        $result = $this->service->analyzeATSCompatibility($resume);

        $hasExperienceIssue = collect($result['issues'])
            ->contains(fn($issue) => str_contains(strtolower($issue), 'experience'));

        $this->assertTrue($hasExperienceIssue);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    protected function createResume(array $attributes = []): Resume
    {
        $user = User::factory()->create();

        $defaults = [
            'user_id' => $user->id,
            'title' => 'My Resume',
            'professional_summary' => 'Experienced software developer',
            'email' => 'test@example.com',
            'phone' => '555-1234',
            'location' => 'San Francisco, CA',
            'linkedin_url' => 'https://linkedin.com/in/test',
            'experience' => [
                [
                    'title' => 'Software Engineer',
                    'company' => 'Tech Corp',
                    'start_date' => '2020-01-01',
                    'end_date' => 'Present',
                    'description' => 'Built scalable applications',
                ]
            ],
            'education' => [
                ['degree' => 'Bachelor', 'field' => 'Computer Science', 'institution' => 'State University']
            ],
            'skills' => [
                'technical' => ['PHP', 'Laravel', 'MySQL'],
                'soft' => ['Communication', 'Leadership'],
            ],
            'projects' => [],
        ];

        return Resume::factory()->create(array_merge($defaults, $attributes));
    }

    protected function createJob(array $attributes = []): Job
    {
        $company = Company::factory()->create(['name' => 'Test Company']);

        $defaults = [
            'company_id' => $company->id,
            'title' => 'Senior Software Engineer',
            'description' => 'Build awesome software',
            'requirements' => 'PHP, Laravel, MySQL, 5+ years experience',
            'experience_level' => 'senior',
            'status' => 'published',
        ];

        return Job::factory()->create(array_merge($defaults, $attributes));
    }
}
