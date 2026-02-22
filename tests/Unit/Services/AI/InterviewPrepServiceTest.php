<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use App\Models\Company;
use App\Models\Job;
use App\Models\Profile;
use App\Models\User;
use App\Services\AI\InterviewPrepService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\MocksAIService;

class InterviewPrepServiceTest extends TestCase
{
    use RefreshDatabase, MocksAIService;

    protected InterviewPrepService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InterviewPrepService::class);
    }

    public function test_generate_questions_returns_categorized_questions(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'behavioral_questions' => [
                            [
                                'question' => 'Tell me about a time you resolved a conflict in your team.',
                                'category' => 'conflict',
                                'difficulty' => 'medium',
                                'why_asked' => 'Assesses conflict resolution skills',
                                'star_framework_tips' => 'Focus on specific situation and measurable outcome',
                            ],
                        ],
                        'technical_questions' => [
                            [
                                'question' => 'Explain the difference between REST and GraphQL.',
                                'topic' => 'API Design',
                                'difficulty' => 'medium',
                                'key_concepts' => ['REST', 'GraphQL', 'Query flexibility'],
                            ],
                        ],
                        'situational_questions' => [
                            [
                                'question' => 'How would you handle a missed deadline?',
                                'scenario_type' => 'time_management',
                                'what_they_assess' => 'Problem-solving under pressure',
                            ],
                        ],
                        'company_culture_questions' => [
                            [
                                'question' => 'Why do you want to work at our company?',
                                'purpose' => 'Assess cultural fit and genuine interest',
                            ],
                        ],
                    ])]]
                ],
                'usage' => ['totalTokens' => 500]
            ], 200)
        ]);

        $job = $this->createJob();

        $result = $this->service->generateQuestions($job);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('behavioral_questions', $result);
        $this->assertArrayHasKey('technical_questions', $result);
        $this->assertArrayHasKey('situational_questions', $result);
        $this->assertArrayHasKey('company_culture_questions', $result);
    }

    public function test_generate_questions_respects_difficulty_level(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'behavioral_questions' => [['question' => 'Hard question', 'difficulty' => 'hard']],
                        'technical_questions' => [['question' => 'Hard tech', 'difficulty' => 'hard']],
                        'situational_questions' => [],
                        'company_culture_questions' => [],
                    ])]]
                ],
                'usage' => ['totalTokens' => 200]
            ], 200)
        ]);

        $job = $this->createJob();

        $result = $this->service->generateQuestions($job, difficulty: 'hard');

        Http::assertSent(function ($request) {
            $messages = $request->data()['messages'] ?? [];
            $userMessage = $messages[1]['content'] ?? '';
            return str_contains($userMessage, 'hard');
        });
    }

    public function test_evaluate_answer_returns_structured_feedback(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'score' => 75,
                        'strengths' => ['Clear communication', 'Good example used'],
                        'weaknesses' => ['Missing quantified result'],
                        'specific_feedback' => 'Your answer demonstrated good situation description.',
                        'star_compliance' => [
                            'has_situation' => true,
                            'has_task' => true,
                            'has_action' => true,
                            'has_result' => false,
                            'missing_elements' => ['Quantified outcome'],
                        ],
                        'improved_answer' => 'Consider adding specific metrics to your result.',
                        'key_takeaways' => ['Always include measurable outcomes'],
                        'follow_up_likely' => 'What was the impact on team productivity?',
                    ])]]
                ],
                'usage' => ['totalTokens' => 300]
            ], 200)
        ]);

        $result = $this->service->evaluateAnswer(
            'Tell me about a time you led a team.',
            'I led a team of 5 developers on a critical project. I assigned tasks and held daily standups.',
            'behavioral'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('strengths', $result);
        $this->assertArrayHasKey('weaknesses', $result);
        $this->assertArrayHasKey('star_compliance', $result);
        $this->assertIsInt($result['score']);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    public function test_evaluate_answer_assesses_star_compliance(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'score' => 60,
                        'strengths' => [],
                        'weaknesses' => ['Missing STAR structure'],
                        'specific_feedback' => 'Answer lacks clear structure.',
                        'star_compliance' => [
                            'has_situation' => false,
                            'has_task' => false,
                            'has_action' => true,
                            'has_result' => false,
                            'missing_elements' => ['Situation', 'Task', 'Result'],
                        ],
                        'improved_answer' => 'Start with context...',
                        'key_takeaways' => ['Use STAR method'],
                        'follow_up_likely' => 'Could you provide more context?',
                    ])]]
                ],
                'usage' => ['totalTokens' => 250]
            ], 200)
        ]);

        $result = $this->service->evaluateAnswer(
            'Tell me about a challenge you faced.',
            'I just fixed it by working harder.',
            'behavioral'
        );

        $this->assertFalse($result['star_compliance']['has_situation']);
        $this->assertNotEmpty($result['star_compliance']['missing_elements']);
    }

    public function test_generate_mock_interview_returns_interview_structure(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'interview_format' => 'video',
                        'estimated_duration' => '45 minutes',
                        'sections' => [
                            [
                                'section_name' => 'Introduction',
                                'duration' => '5 minutes',
                                'questions' => [
                                    [
                                        'question' => 'Tell me about yourself',
                                        'type' => 'opening',
                                        'suggested_answer_approach' => 'Use present-past-future format',
                                        'time_limit' => '2 minutes',
                                    ],
                                ],
                            ],
                        ],
                        'preparation_tips' => ['Research the company', 'Practice STAR responses'],
                        'common_pitfalls' => ['Talking too long', 'Not asking questions'],
                        'success_indicators' => ['Clear communication', 'Enthusiasm shown'],
                    ])]]
                ],
                'usage' => ['totalTokens' => 400]
            ], 200)
        ]);

        $user = $this->createUserWithProfile();
        $job = $this->createJob();

        $result = $this->service->generateMockInterview($user, $job);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('interview_format', $result);
        $this->assertArrayHasKey('sections', $result);
        $this->assertArrayHasKey('preparation_tips', $result);
    }

    public function test_get_answering_tips_returns_framework_guidance(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'framework' => [
                            'name' => 'STAR',
                            'steps' => [
                                [
                                    'step' => 'Situation',
                                    'description' => 'Set the context',
                                    'tips' => ['Be specific', 'Keep it brief'],
                                    'example' => 'In my previous role at...',
                                ],
                            ],
                        ],
                        'dos' => ['Use specific examples', 'Quantify results'],
                        'donts' => ['Ramble', 'Be negative about past employers'],
                        'time_management' => [
                            'ideal_length' => '2-3 minutes',
                            'how_to_stay_concise' => 'Practice with timer',
                            'how_to_elaborate_if_asked' => 'Have additional details ready',
                        ],
                        'body_language_tips' => ['Maintain eye contact', 'Sit up straight'],
                        'example_strong_answers' => [],
                    ])]]
                ],
                'usage' => ['totalTokens' => 350]
            ], 200)
        ]);

        $result = $this->service->getAnsweringTips('behavioral');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('framework', $result);
        $this->assertArrayHasKey('dos', $result);
        $this->assertArrayHasKey('donts', $result);
        $this->assertArrayHasKey('time_management', $result);
    }

    public function test_generate_candidate_questions_returns_questions_by_category(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'about_the_role' => [
                            ['question' => 'What does success look like?', 'why_ask' => 'Understand expectations'],
                        ],
                        'about_the_team' => [
                            ['question' => 'How is the team structured?', 'why_ask' => 'Understand dynamics'],
                        ],
                        'about_growth' => [
                            ['question' => 'What development opportunities exist?', 'why_ask' => 'Career path clarity'],
                        ],
                        'about_culture' => [
                            ['question' => 'How would you describe the culture?', 'why_ask' => 'Assess fit'],
                        ],
                        'about_challenges' => [
                            ['question' => 'What are the biggest challenges?', 'why_ask' => 'Set realistic expectations'],
                        ],
                        'questions_to_avoid' => [
                            ['bad_question' => 'What is the salary?', 'why_avoid' => 'Too early', 'better_alternative' => 'Ask after offer'],
                        ],
                    ])]]
                ],
                'usage' => ['totalTokens' => 300]
            ], 200)
        ]);

        $job = $this->createJob();

        $result = $this->service->generateCandidateQuestions($job, 'first_round');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('about_the_role', $result);
        $this->assertArrayHasKey('about_the_team', $result);
        $this->assertArrayHasKey('questions_to_avoid', $result);
    }

    public function test_prepare_for_format_returns_format_specific_guidance(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'format_overview' => 'Technical interviews assess coding ability in real-time.',
                        'what_to_expect' => ['Live coding', 'System design questions'],
                        'technical_setup' => [
                            'requirements' => ['Stable internet', 'Working webcam'],
                            'setup_tips' => ['Test audio beforehand'],
                            'common_tech_issues' => ['Screen sharing problems'],
                        ],
                        'specific_tips' => ['Think out loud', 'Ask clarifying questions'],
                        'practice_exercises' => [
                            ['exercise' => 'Solve LeetCode problems', 'how_to_practice' => 'Daily', 'success_criteria' => 'Medium difficulty'],
                        ],
                        'common_mistakes' => ['Starting to code without understanding the problem'],
                        'how_to_stand_out' => ['Discuss tradeoffs'],
                        'example_scenarios' => [],
                    ])]]
                ],
                'usage' => ['totalTokens' => 400]
            ], 200)
        ]);

        $job = $this->createJob();

        $result = $this->service->prepareForFormat('technical', $job);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('format_overview', $result);
        $this->assertArrayHasKey('technical_setup', $result);
        $this->assertArrayHasKey('specific_tips', $result);
    }

    public function test_generate_follow_up_returns_email_content(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'subject_line' => 'Thank You for the Interview - Software Engineer Position',
                        'email_body' => 'Dear John,\n\nThank you for taking the time to speak with me today...',
                        'key_elements_included' => ['Thank you', 'Reference to conversation', 'Reiterate interest'],
                        'tone' => 'professional',
                        'send_timing_recommendation' => 'within 24 hours',
                        'alternative_version' => 'Shorter version available upon request.',
                    ])]]
                ],
                'usage' => ['totalTokens' => 250]
            ], 200)
        ]);

        $user = User::factory()->create(['name' => 'Test Candidate']);
        $job = $this->createJob();

        $result = $this->service->generateFollowUp($user, $job, [
            'interviewer_name' => 'John Smith',
            'date' => 'today',
            'key_points' => ['Discussed team structure', 'Talked about growth opportunities'],
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('subject_line', $result);
        $this->assertArrayHasKey('email_body', $result);
        $this->assertArrayHasKey('send_timing_recommendation', $result);
    }

    public function test_prepare_salary_discussion_returns_negotiation_guidance(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'range_analysis' => [
                            'overlap' => true,
                            'candidate_position' => 'within',
                            'negotiation_leverage' => 'medium',
                            'recommendation' => 'Room for negotiation exists',
                        ],
                        'how_to_answer_salary_question' => [
                            'if_asked_early' => 'Deflect politely, focus on fit first',
                            'if_asked_after_offer' => 'Provide range based on research',
                            'deflection_strategies' => ['Ask about total compensation'],
                        ],
                        'negotiation_strategies' => [
                            ['strategy' => 'Anchoring', 'when_to_use' => 'When you have leverage', 'script' => 'Based on my research...'],
                        ],
                        'total_compensation_considerations' => [
                            ['item' => 'Equity', 'how_to_evaluate' => 'Check vesting schedule', 'negotiation_potential' => 'high'],
                        ],
                        'common_mistakes' => ['Accepting too quickly'],
                        'power_phrases' => ['I am excited about the opportunity...'],
                        'walk_away_scenarios' => ['Offer significantly below market'],
                    ])]]
                ],
                'usage' => ['totalTokens' => 400]
            ], 200)
        ]);

        $user = $this->createUserWithProfile([
            'expected_salary_min' => 120000,
            'expected_salary_max' => 150000,
        ]);
        $job = $this->createJob([
            'salary_min' => 100000,
            'salary_max' => 140000,
        ]);

        $result = $this->service->prepareSalaryDiscussion($user, $job);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('range_analysis', $result);
        $this->assertArrayHasKey('negotiation_strategies', $result);
        $this->assertArrayHasKey('total_compensation_considerations', $result);
    }

    /**
     * Helper to create a job for testing.
     */
    protected function createJob(array $attributes = []): Job
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'size' => 'medium',
            'industry' => 'Technology',
            'description' => 'A tech company building great products.',
        ]);

        $defaults = [
            'company_id' => $company->id,
            'title' => 'Software Engineer',
            'description' => 'Build scalable web applications',
            'experience_level' => 'mid',
            'status' => 'published',
            'salary_min' => 100000,
            'salary_max' => 150000,
            'location' => 'San Francisco, CA',
        ];

        return Job::factory()->create(array_merge($defaults, $attributes));
    }

    /**
     * Helper to create a user with profile.
     */
    protected function createUserWithProfile(array $profileAttributes = []): User
    {
        $user = User::factory()->create();

        $defaults = [
            'user_id' => $user->id,
            'headline' => 'Software Engineer',
            'skills' => ['PHP', 'Laravel', 'JavaScript'],
            'experience' => [
                ['title' => 'Developer', 'company' => 'Tech Corp', 'description' => 'Built applications'],
            ],
            'expected_salary_min' => 100000,
            'expected_salary_max' => 150000,
        ];

        Profile::factory()->create(array_merge($defaults, $profileAttributes));

        return $user->fresh(['profile']);
    }
}
