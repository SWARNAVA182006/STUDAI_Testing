<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\ApplicationTemplate;
use App\Models\DiscoveredJob;
use App\Models\Profile;
use App\Models\User;
use App\Services\AI\CoverLetterGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\MocksAIService;

class CoverLetterGeneratorServiceTest extends TestCase
{
    use RefreshDatabase, MocksAIService;

    protected CoverLetterGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CoverLetterGeneratorService::class);
    }

    // ---------------------------------------------------------------
    // generate - happy path
    // ---------------------------------------------------------------

    public function test_generate_returns_structured_cover_letter(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'subject_line' => 'Software Engineer Application - Tech Company',
                        'greeting' => 'Dear Hiring Manager,',
                        'intro_paragraph' => 'I am excited to apply for the Software Engineer position.',
                        'body_paragraphs' => [
                            'With over 5 years of experience in PHP and Laravel...',
                            'In my previous role, I led a team that increased efficiency by 30%.',
                        ],
                        'closing_paragraph' => 'I look forward to discussing how I can contribute.',
                        'signature' => "Sincerely,\nTest User",
                        'postscript' => null,
                        'keywords_highlighted' => ['PHP', 'Laravel', 'scalability'],
                        'confidence_score' => 0.85,
                        'talking_points' => ['Leadership', 'Technical expertise'],
                        'tone_notes' => 'Confident but approachable.',
                    ])]]
                ],
                'usage' => ['totalTokens' => 400]
            ], 200)
        ]);

        Cache::shouldReceive('has')->andReturn(false);
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        $user = $this->createUserWithProfile();
        $job = $this->createDiscoveredJob();

        $result = $this->service->generate($user, $job);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('structured_letter', $result);
        $this->assertArrayHasKey('cover_letter', $result);
        $this->assertArrayHasKey('keywords_used', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('subject_line', $result);
    }

    public function test_generate_returns_rendered_letter_text(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'subject_line' => 'Application for Developer',
                        'greeting' => 'Dear Team,',
                        'intro_paragraph' => 'I am writing to express interest.',
                        'body_paragraphs' => ['I have relevant experience.'],
                        'closing_paragraph' => 'Thank you for considering my application.',
                        'signature' => "Best regards,\nTest User",
                        'postscript' => 'I am available for an interview anytime.',
                        'keywords_highlighted' => ['Python'],
                        'confidence_score' => 0.80,
                        'talking_points' => [],
                        'tone_notes' => 'Professional.',
                    ])]]
                ],
                'usage' => ['totalTokens' => 250]
            ], 200)
        ]);

        Cache::shouldReceive('has')->andReturn(false);
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        $user = $this->createUserWithProfile();
        $job = $this->createDiscoveredJob();

        $result = $this->service->generate($user, $job);

        $this->assertIsString($result['cover_letter']);
        $this->assertStringContainsString('Dear Team,', $result['cover_letter']);
        $this->assertStringContainsString('Thank you', $result['cover_letter']);
        $this->assertStringContainsString('P.S.', $result['cover_letter']);
    }

    // ---------------------------------------------------------------
    // generate - caching
    // ---------------------------------------------------------------

    public function test_generate_returns_cached_result_when_available(): void
    {
        $cachedResult = [
            'structured_letter' => ['greeting' => 'Cached greeting'],
            'cover_letter' => 'Cached letter content',
            'keywords_used' => ['cached'],
            'confidence' => 0.90,
            'subject_line' => 'Cached subject',
            'metadata' => [],
        ];

        Cache::shouldReceive('has')->once()->andReturn(true);
        Cache::shouldReceive('get')->once()->andReturn($cachedResult);

        $user = $this->createUserWithProfile();
        $job = $this->createDiscoveredJob();

        $result = $this->service->generate($user, $job);

        $this->assertEquals($cachedResult, $result);
        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------
    // generate - fallback on AI error
    // ---------------------------------------------------------------

    public function test_generate_returns_fallback_on_ai_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Service unavailable'], 500)
        ]);

        Cache::shouldReceive('has')->andReturn(false);
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        $user = $this->createUserWithProfile();
        $job = $this->createDiscoveredJob();

        $result = $this->service->generate($user, $job);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('cover_letter', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertLessThanOrEqual(0.7, $result['confidence']);
    }

    // ---------------------------------------------------------------
    // generate - metadata
    // ---------------------------------------------------------------

    public function test_generate_includes_metadata_with_options(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'subject_line' => 'App',
                        'greeting' => 'Hi,',
                        'intro_paragraph' => 'Intro.',
                        'body_paragraphs' => [],
                        'closing_paragraph' => 'Close.',
                        'signature' => 'Sig',
                        'postscript' => null,
                        'keywords_highlighted' => [],
                        'confidence_score' => 0.8,
                        'talking_points' => [],
                        'tone_notes' => 'Note.',
                    ])]]
                ],
                'usage' => ['totalTokens' => 100]
            ], 200)
        ]);

        Cache::shouldReceive('has')->andReturn(false);
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        $user = $this->createUserWithProfile();
        $job = $this->createDiscoveredJob();

        $result = $this->service->generate($user, $job, [
            'tone' => 'professional',
            'length' => 'short',
        ]);

        $this->assertIsArray($result['metadata']);
        $this->assertEquals($job->id, $result['metadata']['job_id']);
        $this->assertEquals('professional', $result['metadata']['tone']);
        $this->assertEquals('short', $result['metadata']['length']);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    protected function createUserWithProfile(array $profileAttributes = []): User
    {
        $user = User::factory()->create(['name' => 'Test User']);

        $defaults = [
            'user_id' => $user->id,
            'headline' => 'Software Engineer',
            'current_role' => 'Senior Developer',
            'years_of_experience' => 5,
            'skills' => [
                'technical' => ['PHP', 'Laravel', 'Python', 'Django'],
                'soft' => ['Leadership', 'Communication'],
            ],
            'industries' => ['Technology', 'SaaS'],
            'achievements' => ['Led team of 10', 'Increased revenue by 30%'],
            'values' => ['Innovation', 'Collaboration'],
            'education' => [
                ['degree' => 'BS', 'field' => 'Computer Science', 'institution' => 'State University'],
            ],
        ];

        Profile::factory()->create(array_merge($defaults, $profileAttributes));

        return $user->fresh(['profile']);
    }

    protected function createDiscoveredJob(array $attributes = []): DiscoveredJob
    {
        $defaults = [
            'title' => 'Software Engineer',
            'company_name' => 'Tech Company',
            'location' => 'San Francisco, CA',
            'description' => 'Build scalable web applications using modern technologies. We need experts in PHP, Python, and cloud services.',
            'extracted_skills' => ['PHP', 'Python', 'AWS'],
            'url' => 'https://example.com/job/123',
            'external_id' => 'job_' . uniqid(),
        ];

        return DiscoveredJob::factory()->create(array_merge($defaults, $attributes));
    }
}
