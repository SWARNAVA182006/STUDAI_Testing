<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

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
                        'signature' => 'Sincerely,\nTest User',
                        'postscript' => null,
                        'keywords_highlighted' => ['PHP', 'Laravel', 'scalability'],
                        'confidence_score' => 0.85,
                        'talking_points' => ['Leadership', 'Technical expertise', 'Problem solving'],
                        'tone_notes' => 'Confident but approachable tone.',
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
                        'signature' => 'Best regards,\nTest User',
                        'postscript' => 'P.S. I am available for an interview anytime.',
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

    public function test_generate_uses_cache(): void
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

    public function test_generate_bypasses_cache_with_force_refresh(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'subject_line' => 'Fresh letter',
                        'greeting' => 'Hello,',
                        'intro_paragraph' => 'Fresh intro.',
                        'body_paragraphs' => [],
                        'closing_paragraph' => 'Fresh closing.',
                        'signature' => 'Fresh signature',
                        'postscript' => null,
                        'keywords_highlighted' => [],
                        'confidence_score' => 0.75,
                        'talking_points' => [],
                        'tone_notes' => 'Fresh.',
                    ])]]
                ],
                'usage' => ['totalTokens' => 150]
            ], 200)
        ]);

        Cache::shouldReceive('has')->andReturn(true);
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        $user = $this->createUserWithProfile();
        $job = $this->createDiscoveredJob();

        $result = $this->service->generate($user, $job, ['force_refresh' => true]);

        Http::assertSentCount(1);
    }

    public function test_generate_respects_tone_option(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'subject_line' => 'Application',
                        'greeting' => 'Hello,',
                        'intro_paragraph' => 'intro',
                        'body_paragraphs' => [],
                        'closing_paragraph' => 'closing',
                        'signature' => 'sig',
                        'postscript' => null,
                        'keywords_highlighted' => [],
                        'confidence_score' => 0.8,
                        'talking_points' => [],
                        'tone_notes' => 'Enthusiastic tone.',
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

        $result = $this->service->generate($user, $job, ['tone' => 'enthusiastic']);

        Http::assertSent(function ($request) {
            $messages = $request->data()['messages'] ?? [];
            $userMessage = $messages[1]['content'] ?? '';
            return str_contains($userMessage, 'Enthusiastic');
        });

        $this->assertEquals('enthusiastic', $result['metadata']['tone']);
    }

    public function test_generate_respects_length_option(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'subject_line' => 'Application',
                        'greeting' => 'Hi,',
                        'intro_paragraph' => 'Short intro.',
                        'body_paragraphs' => [],
                        'closing_paragraph' => 'Thanks.',
                        'signature' => 'Name',
                        'postscript' => null,
                        'keywords_highlighted' => [],
                        'confidence_score' => 0.7,
                        'talking_points' => [],
                        'tone_notes' => 'Short letter.',
                    ])]]
                ],
                'usage' => ['totalTokens' => 80]
            ], 200)
        ]);

        Cache::shouldReceive('has')->andReturn(false);
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        $user = $this->createUserWithProfile();
        $job = $this->createDiscoveredJob();

        $result = $this->service->generate($user, $job, ['length' => 'short']);

        Http::assertSent(function ($request) {
            $messages = $request->data()['messages'] ?? [];
            $userMessage = $messages[1]['content'] ?? '';
            return str_contains($userMessage, '2 paragraphs');
        });
    }

    public function test_generate_uses_template_when_available(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'subject_line' => 'Templated letter',
                        'greeting' => 'Dear Hiring Team,',
                        'intro_paragraph' => 'Using template style.',
                        'body_paragraphs' => [],
                        'closing_paragraph' => 'Closing.',
                        'signature' => 'Signature',
                        'postscript' => null,
                        'keywords_highlighted' => [],
                        'confidence_score' => 0.85,
                        'talking_points' => [],
                        'tone_notes' => 'Template-based.',
                    ])]]
                ],
                'usage' => ['totalTokens' => 200]
            ], 200)
        ]);

        Cache::shouldReceive('has')->andReturn(false);
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        $user = $this->createUserWithProfile();
        $job = $this->createDiscoveredJob();

        // Create a template
        $template = ApplicationTemplate::factory()->create([
            'user_id' => $user->id,
            'type' => 'cover_letter',
            'content' => 'Dear {company}, I am excited about {position}...',
            'success_rate' => 0.75,
            'average_match_score' => 80,
        ]);

        $result = $this->service->generate($user, $job, ['use_template' => true]);

        $this->assertEquals($template->id, $result['metadata']['template_id']);
    }

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
        // Fallback confidence is lower
        $this->assertLessThanOrEqual(0.7, $result['confidence']);
    }

    public function test_generate_extracts_keywords_from_job(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'subject_line' => 'Application',
                        'greeting' => 'Dear Hiring Manager,',
                        'intro_paragraph' => 'Intro with Python and Django.',
                        'body_paragraphs' => [],
                        'closing_paragraph' => 'Closing.',
                        'signature' => 'Name',
                        'postscript' => null,
                        'keywords_highlighted' => ['Python', 'Django', 'PostgreSQL'],
                        'confidence_score' => 0.85,
                        'talking_points' => [],
                        'tone_notes' => 'Technical.',
                    ])]]
                ],
                'usage' => ['totalTokens' => 150]
            ], 200)
        ]);

        Cache::shouldReceive('has')->andReturn(false);
        Cache::shouldReceive('get')->andReturn(null);
        Cache::shouldReceive('put')->andReturn(true);

        $user = $this->createUserWithProfile();
        $job = $this->createDiscoveredJob([
            'description' => 'We need a Python developer with Django and PostgreSQL experience.',
            'extracted_skills' => ['Python', 'Django', 'PostgreSQL'],
        ]);

        $result = $this->service->generate($user, $job);

        $this->assertContains('Python', $result['keywords_used']);
    }

    public function test_generate_includes_metadata(): void
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
            'length' => 'medium',
        ]);

        $this->assertIsArray($result['metadata']);
        $this->assertEquals($job->id, $result['metadata']['job_id']);
        $this->assertEquals('professional', $result['metadata']['tone']);
        $this->assertEquals('medium', $result['metadata']['length']);
    }

    /**
     * Helper to create a user with profile.
     */
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

    /**
     * Helper to create a discovered job.
     */
    protected function createDiscoveredJob(array $attributes = []): DiscoveredJob
    {
        $defaults = [
            'title' => 'Software Engineer',
            'company_name' => 'Tech Company',
            'location' => 'San Francisco, CA',
            'description' => 'Build scalable web applications using modern technologies. We need experts in PHP, Python, and cloud services.',
            'extracted_skills' => ['PHP', 'Python', 'AWS'],
            'url' => 'https://example.com/job/123',
            'external_id' => 'job_123',
        ];

        return DiscoveredJob::factory()->create(array_merge($defaults, $attributes));
    }
}
