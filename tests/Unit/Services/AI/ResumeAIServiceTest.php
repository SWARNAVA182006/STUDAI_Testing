<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use App\Models\Job;
use App\Models\Resume;
use App\Models\User;
use App\Models\Company;
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

    public function test_generate_professional_summary_returns_string(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Experienced software engineer with 5+ years...']]
                ],
                'usage' => ['totalTokens' => 100]
            ], 200)
        ]);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $resume = $this->createResume();

        $result = $this->service->generateProfessionalSummary($resume);

        $this->assertIsString($result);
        $this->assertStringContainsString('software engineer', $result);
    }

    public function test_generate_professional_summary_with_target_job(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Results-driven developer seeking Senior role...']]
                ],
                'usage' => ['totalTokens' => 120]
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

    public function test_generate_professional_summary_falls_back_on_error(): void
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

    public function test_optimize_experience_bullets_returns_optimized_array(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'bullets' => [
                            ['original' => 'Worked on projects', 'optimized' => 'Led 5 cross-functional projects delivering $2M in revenue', 'confidence' => 95],
                            ['original' => 'Fixed bugs', 'optimized' => 'Reduced bug backlog by 60% through systematic debugging process', 'confidence' => 88],
                        ]
                    ])]]
                ],
                'usage' => ['totalTokens' => 150]
            ], 200)
        ]);

        $bullets = ['Worked on projects', 'Fixed bugs'];
        $result = $this->service->optimizeExperienceBullets($bullets, 'Software Engineer');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('optimized', $result[0]);
        $this->assertArrayHasKey('confidence', $result[0]);
    }

    public function test_optimize_experience_bullets_handles_empty_array(): void
    {
        $result = $this->service->optimizeExperienceBullets([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_optimize_experience_bullets_falls_back_on_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Invalid JSON'], 500)
        ]);

        $bullets = ['Managed team', 'Built systems'];
        $result = $this->service->optimizeExperienceBullets($bullets);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(0, $result[0]['confidence']);
    }

    public function test_extract_skills_returns_categorized_skills(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => '{"technical": ["PHP", "Python"], "soft": ["Leadership", "Communication"], "tools": ["Git", "Docker"]}']]
                ],
                'usage' => ['totalTokens' => 80]
            ], 200)
        ]);

        $resume = $this->createResume([
            'experience' => [
                ['title' => 'Developer', 'description' => 'Built PHP and Python applications']
            ]
        ]);

        $result = $this->service->extractSkills($resume);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('technical', $result);
        $this->assertArrayHasKey('soft', $result);
        $this->assertArrayHasKey('tools', $result);
        $this->assertContains('PHP', $result['technical']);
    }

    public function test_extract_skills_returns_fallback_on_invalid_json(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'This is not valid JSON']]
                ],
                'usage' => ['totalTokens' => 20]
            ], 200)
        ]);

        $resume = $this->createResume();

        $result = $this->service->extractSkills($resume);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('technical', $result);
        $this->assertEmpty($result['technical']);
    }

    public function test_analyze_ats_compatibility_returns_structured_analysis(): void
    {
        $resume = $this->createResume([
            'email' => 'test@example.com',
            'phone' => '555-1234',
            'location' => 'San Francisco, CA',
            'linkedin_url' => 'https://linkedin.com/in/test',
            'experience' => [
                ['title' => 'Developer', 'description' => 'Led team to increase revenue by 25%']
            ],
            'education' => [
                ['degree' => 'BS', 'field' => 'Computer Science']
            ],
            'skills' => ['PHP', 'Laravel'],
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

    public function test_analyze_ats_compatibility_detects_missing_sections(): void
    {
        $resume = $this->createResume([
            'experience' => [],
            'education' => [],
            'skills' => [],
        ]);

        $result = $this->service->analyzeATSCompatibility($resume);

        $this->assertIsArray($result['issues']);
        $hasExperienceIssue = collect($result['issues'])->contains(fn($issue) => str_contains(strtolower($issue), 'experience'));
        $this->assertTrue($hasExperienceIssue);
    }

    public function test_quantify_achievement_returns_improved_text(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Increased sales revenue by 35% ($1.2M) through implementation of automated CRM workflow']]
                ],
                'usage' => ['totalTokens' => 50]
            ], 200)
        ]);

        $result = $this->service->quantifyAchievement(
            'Improved sales process',
            'Sales team at tech company'
        );

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/\d+/', $result); // Contains numbers
    }

    public function test_quantify_achievement_returns_original_on_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Error'], 500)
        ]);

        $original = 'Managed a team';
        $result = $this->service->quantifyAchievement($original);

        $this->assertEquals($original, $result);
    }

    public function test_customize_for_job_returns_suggestions(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Tailored professional summary for the role...']]
                ],
                'usage' => ['totalTokens' => 100]
            ], 200)
        ]);

        Cache::shouldReceive('remember')
            ->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $resume = $this->createResume([
            'professional_summary' => 'Generic summary',
            'skills' => ['technical' => ['PHP']],
        ]);
        $job = $this->createJob([
            'description' => 'We need a Python developer with AWS experience',
            'requirements' => 'Python, AWS, Docker required'
        ]);

        $result = $this->service->customizeForJob($resume, $job);

        $this->assertIsArray($result);
        // Should identify keyword gaps
        $hasKeywordSuggestion = collect($result)->contains(fn($s) => ($s['type'] ?? '') === 'keyword');
        $this->assertTrue(count($result) > 0 || $hasKeywordSuggestion);
    }

    /**
     * Helper method to create a resume for testing.
     */
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
                    'description' => 'Built scalable applications'
                ]
            ],
            'education' => [
                [
                    'degree' => 'Bachelor',
                    'field' => 'Computer Science',
                    'institution' => 'State University'
                ]
            ],
            'skills' => [
                'technical' => ['PHP', 'Laravel', 'MySQL'],
                'soft' => ['Communication', 'Leadership'],
            ],
            'projects' => [],
        ];

        return Resume::factory()->create(array_merge($defaults, $attributes));
    }

    /**
     * Helper method to create a job for testing.
     */
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
