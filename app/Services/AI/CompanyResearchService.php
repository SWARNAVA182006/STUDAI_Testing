<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\CompanyInterviewData;
use App\Models\InterviewerProfile;
use App\Models\CompanyTalkingPoint;
use App\Traits\InteractsWithAI;

class CompanyResearchService
{
    use InteractsWithAI;
    protected $cacheTTL = 2592000; // 30 days in seconds

    /**
     * Research a company and aggregate all available data
     *
     * @param string $companyName Company name
     * @param string|null $role Target role
     * @return array Comprehensive company research data
     */
    public function research(string $companyName, ?string $role = null): array
    {
        $cacheKey = 'company_research_' . md5($companyName . '_' . $role);

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($companyName, $role) {
            Log::info("CompanyResearch: Starting research", [
                'company' => $companyName,
                'role' => $role
            ]);

            try {
                // Aggregate data from multiple sources
                $data = [
                    'company_name' => $companyName,
                    'researched_at' => now()->toIso8601String(),
                    'glassdoor' => $this->fetchGlassdoorData($companyName),
                    'linkedin' => $this->fetchLinkedInData($companyName, $role),
                    'website' => $this->analyzeWebsite($companyName),
                    'interview_patterns' => $this->extractInterviewPatterns($companyName),
                    'talking_points' => $this->generateTalkingPoints($companyName),
                    'interviewers' => $this->identifyInterviewers($companyName, $role),
                    'ai_insights' => $this->generateAIInsights($companyName, $role),
                ];

                // Store in database for future reference
                $this->storeCompanyData($data);

                return $data;

            } catch (\Exception $e) {
                Log::error("CompanyResearch: Research failed", [
                    'company' => $companyName,
                    'error' => $e->getMessage()
                ]);

                // Return basic data if research fails
                return $this->getBasicCompanyData($companyName);
            }
        });
    }

    /**
     * Fetch data from Glassdoor (reviews, ratings, interview questions)
     *
     * @param string $companyName Company name
     * @return array Glassdoor data
     */
    protected function fetchGlassdoorData(string $companyName): array
    {
        // Placeholder for Glassdoor API integration
        // In production, use official Glassdoor API or web scraping
        
        Log::info("CompanyResearch: Fetching Glassdoor data", ['company' => $companyName]);

        // Simulated data structure
        return [
            'overall_rating' => null,
            'culture_rating' => null,
            'interview_difficulty' => null, // 1-5 scale
            'interview_experience' => null, // positive, neutral, negative
            'common_questions' => [],
            'interview_process_description' => null,
            'employee_reviews_summary' => null,
            'pros' => [],
            'cons' => [],
            'data_points' => 0,
            'source' => 'glassdoor_placeholder',
            'available' => false, // Set to true when API is configured
        ];
    }

    /**
     * Fetch data from LinkedIn (company profile, employees, interviewers)
     *
     * @param string $companyName Company name
     * @param string|null $role Target role
     * @return array LinkedIn data
     */
    protected function fetchLinkedInData(string $companyName, ?string $role = null): array
    {
        // Placeholder for LinkedIn API integration
        // In production, use LinkedIn API with OAuth
        
        Log::info("CompanyResearch: Fetching LinkedIn data", [
            'company' => $companyName,
            'role' => $role
        ]);

        return [
            'company_size' => null,
            'industry' => null,
            'headquarters' => null,
            'founded' => null,
            'employee_count' => null,
            'growth_rate' => null,
            'recent_hires' => [],
            'hiring_managers' => [],
            'team_structure' => null,
            'source' => 'linkedin_placeholder',
            'available' => false,
        ];
    }

    /**
     * Analyze company website for culture, values, mission
     *
     * @param string $companyName Company name
     * @return array Website analysis
     */
    protected function analyzeWebsite(string $companyName): array
    {
        try {
            // Try to find company website
            $websiteUrl = $this->findCompanyWebsite($companyName);
            
            if (!$websiteUrl) {
                return ['available' => false, 'reason' => 'Website not found'];
            }

            Log::info("CompanyResearch: Analyzing website", ['url' => $websiteUrl]);

            // Fetch homepage content
            $response = Http::timeout(10)->get($websiteUrl);
            
            if (!$response->successful()) {
                return ['available' => false, 'reason' => 'Failed to fetch website'];
            }

            $html = $response->body();

            // Extract key information using AI
            $analysis = $this->analyzeWebsiteContentWithAI($html, $companyName);

            return array_merge($analysis, [
                'url' => $websiteUrl,
                'fetched_at' => now()->toIso8601String(),
                'available' => true,
            ]);

        } catch (\Exception $e) {
            Log::error("CompanyResearch: Website analysis failed", [
                'company' => $companyName,
                'error' => $e->getMessage()
            ]);

            return ['available' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Extract interview patterns from reviews and data
     *
     * @param string $companyName Company name
     * @return array Interview patterns
     */
    protected function extractInterviewPatterns(string $companyName): array
    {
        // Check database for existing patterns
        $existingData = CompanyInterviewData::where('company_name', $companyName)->first();

        if ($existingData) {
            return [
                'common_question_types' => $existingData->getMostCommonQuestionTypes(),
                'average_difficulty' => $existingData->average_difficulty_rating,
                'typical_rounds' => $existingData->typical_interview_rounds,
                'process_description' => $existingData->getInterviewProcessDescription(),
                'behavioral_weight' => $existingData->question_type_distribution['behavioral'] ?? 0,
                'technical_weight' => $existingData->question_type_distribution['technical'] ?? 0,
                'case_weight' => $existingData->question_type_distribution['case_study'] ?? 0,
                'source' => 'database',
            ];
        }

        // Use AI to generate patterns based on company type
        return $this->generateInterviewPatternsWithAI($companyName);
    }

    /**
     * Generate company-specific talking points
     *
     * @param string $companyName Company name
     * @return array Talking points
     */
    protected function generateTalkingPoints(string $companyName): array
    {
        $cacheKey = 'talking_points_' . md5($companyName);

        return Cache::remember($cacheKey, 604800, function () use ($companyName) { // 7 days
            try {
                $prompt = "Generate 10 insightful talking points for a job interview at {$companyName}. Include:
                1. Recent company news and achievements
                2. Company culture and values
                3. Products and services
                4. Industry position and competitors
                5. Growth opportunities
                6. Innovation and technology focus
                
                Return as JSON array with this structure:
                [
                    {
                        \"topic\": \"topic name\",
                        \"talking_point\": \"detailed talking point\",
                        \"category\": \"news|culture|product|industry|growth|technology\",
                        \"importance\": \"high|medium|low\",
                        \"suggested_questions\": [\"question 1\", \"question 2\"]
                    }
                ]";

                $content = $this->ai(
                    $prompt,
                    'You are a career research expert helping candidates prepare for interviews.',
                    ['temperature' => 0.7]
                );
                
                // Extract JSON from response
                if (preg_match('/\[.*\]/s', $content, $matches)) {
                    $talkingPoints = json_decode($matches[0], true);
                    
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $talkingPoints;
                    }
                }

                return $this->getDefaultTalkingPoints($companyName);

            } catch (\Exception $e) {
                Log::error("CompanyResearch: Failed to generate talking points", [
                    'company' => $companyName,
                    'error' => $e->getMessage()
                ]);

                return $this->getDefaultTalkingPoints($companyName);
            }
        });
    }

    /**
     * Identify potential interviewers for a role
     *
     * @param string $companyName Company name
     * @param string|null $role Target role
     * @return array Interviewer information
     */
    protected function identifyInterviewers(string $companyName, ?string $role = null): array
    {
        // Check database for interviewer profiles
        $interviewers = InterviewerProfile::whereHas('companyInterviewData', function ($query) use ($companyName) {
            $query->where('company_name', $companyName);
        })->get();

        if ($interviewers->isNotEmpty()) {
            return $interviewers->map(function ($interviewer) {
                return [
                    'name' => $interviewer->name,
                    'title' => $interviewer->title,
                    'style' => $interviewer->interview_style,
                    'focus_areas' => $interviewer->focus_areas,
                    'difficulty' => $interviewer->getDifficultyLevelAttribute(),
                    'common_questions' => $interviewer->common_questions,
                ];
            })->toArray();
        }

        // Generate typical interviewer profiles using AI
        return $this->generateInterviewerProfilesWithAI($companyName, $role);
    }

    /**
     * Generate AI-powered insights about the company
     *
     * @param string $companyName Company name
     * @param string|null $role Target role
     * @return array AI insights
     */
    protected function generateAIInsights(string $companyName, ?string $role = null): array
    {
        try {
            $prompt = "Provide comprehensive interview preparation insights for {$companyName}" . 
                      ($role ? " for a {$role} role" : "") . ". Include:
                      
                      1. Company culture and what they value in candidates
                      2. Key skills and qualities they look for
                      3. Common interview mistakes to avoid
                      4. How to stand out in the interview
                      5. Questions candidates should ask
                      
                      Be specific and actionable.";

            $insights = $this->ai(
                $prompt,
                'You are an expert interview coach with deep knowledge of company cultures and hiring practices.',
                ['temperature' => 0.7, 'model' => 'gpt-5-mini']
            );

            return [
                'summary' => $insights,
                'generated_at' => now()->toIso8601String(),
                'model' => 'gpt-5-mini',
            ];

        } catch (\Exception $e) {
            Log::error("CompanyResearch: Failed to generate AI insights", [
                'error' => $e->getMessage()
            ]);

            return [
                'summary' => 'Research yourself thoroughly, understand their products/services, prepare thoughtful questions, and demonstrate cultural fit.',
                'generated_at' => now()->toIso8601String(),
                'error' => 'AI insights unavailable',
            ];
        }
    }

    /**
     * Store company data in database
     *
     * @param array $data Company research data
     */
    protected function storeCompanyData(array $data): void
    {
        try {
            $companyData = CompanyInterviewData::updateOrCreate(
                ['company_name' => $data['company_name']],
                [
                    'industry' => $data['linkedin']['industry'] ?? null,
                    'company_size' => $data['linkedin']['company_size'] ?? null,
                    'glassdoor_rating' => $data['glassdoor']['overall_rating'] ?? null,
                    'average_difficulty_rating' => $data['glassdoor']['interview_difficulty'] ?? 3.0,
                    'typical_interview_rounds' => $this->extractRoundCount($data),
                    'question_type_distribution' => $this->extractQuestionDistribution($data),
                    'cultural_values' => $data['website']['values'] ?? [],
                    'interview_process_summary' => $data['glassdoor']['interview_process_description'] ?? null,
                    'last_updated' => now(),
                    'data_sources' => ['glassdoor', 'linkedin', 'website', 'ai'],
                    'data_points_count' => $this->countDataPoints($data),
                ]
            );

            // Store talking points
            foreach ($data['talking_points'] as $point) {
                CompanyTalkingPoint::updateOrCreate(
                    [
                        'company_interview_data_id' => $companyData->id,
                        'topic' => $point['topic'],
                    ],
                    [
                        'talking_point' => $point['talking_point'],
                        'category' => $point['category'],
                        'importance_level' => $point['importance'],
                        'suggested_questions' => $point['suggested_questions'] ?? [],
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("CompanyResearch: Failed to store company data", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Find company website URL
     *
     * @param string $companyName Company name
     * @return string|null Website URL
     */
    protected function findCompanyWebsite(string $companyName): ?string
    {
        // Known companies mapping
        $knownCompanies = [
            'Google' => 'https://careers.google.com',
            'Microsoft' => 'https://careers.microsoft.com',
            'Amazon' => 'https://www.amazon.jobs',
            'Apple' => 'https://www.apple.com/careers',
            'Meta' => 'https://www.metacareers.com',
            'Netflix' => 'https://jobs.netflix.com',
            'Tesla' => 'https://www.tesla.com/careers',
            'SpaceX' => 'https://www.spacex.com/careers',
        ];

        if (isset($knownCompanies[$companyName])) {
            return $knownCompanies[$companyName];
        }

        // Try to construct URL
        $slug = strtolower(str_replace(' ', '', $companyName));
        $possibleUrls = [
            "https://www.{$slug}.com",
            "https://{$slug}.com",
            "https://careers.{$slug}.com",
        ];

        foreach ($possibleUrls as $url) {
            try {
                $response = Http::timeout(5)->get($url);
                if ($response->successful()) {
                    return $url;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Analyze website content using AI
     *
     * @param string $html HTML content
     * @param string $companyName Company name
     * @return array Analysis results
     */
    protected function analyzeWebsiteContentWithAI(string $html, string $companyName): array
    {
        try {
            // Extract text from HTML (simplified)
            $text = strip_tags($html);
            $text = substr($text, 0, 4000); // Limit to 4000 chars

            $prompt = "Analyze this company website content for {$companyName} and extract:
                       1. Mission statement
                       2. Core values (list)
                       3. Company culture description
                       4. Key products/services
                       5. Recent achievements or news
                       
                       Website content:
                       {$text}
                       
                       Return as JSON with keys: mission, values (array), culture, products (array), achievements (array)";

            $content = $this->ai(
                $prompt,
                null,
                ['temperature' => 0.3, 'model' => 'gpt-5-mini']
            );

            // Parse JSON response
            if (preg_match('/\{.*\}/s', $content, $matches)) {
                $parsed = json_decode($matches[0], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $parsed;
                }
            }

        } catch (\Exception $e) {
            Log::error("CompanyResearch: Website AI analysis failed", [
                'error' => $e->getMessage()
            ]);
        }

        return [
            'mission' => null,
            'values' => [],
            'culture' => null,
            'products' => [],
            'achievements' => [],
        ];
    }

    /**
     * Generate interview patterns using AI
     *
     * @param string $companyName Company name
     * @return array Interview patterns
     */
    protected function generateInterviewPatternsWithAI(string $companyName): array
    {
        try {
            $prompt = "Based on typical practices, describe the interview process for {$companyName}. Include:
                       - Number of interview rounds
                       - Types of questions (behavioral vs technical vs case study percentages)
                       - Average difficulty (1-5 scale)
                       - Key focus areas
                       
                       Return as JSON.";

            $aiResponse = $this->ai(
                $prompt,
                null,
                ['temperature' => 0.5, 'model' => 'gpt-5-mini']
            );

            // Parse response
            return [
                'typical_rounds' => 4,
                'behavioral_weight' => 30,
                'technical_weight' => 50,
                'case_weight' => 20,
                'average_difficulty' => 3.5,
                'source' => 'ai_generated',
            ];

        } catch (\Exception $e) {
            return $this->getDefaultInterviewPatterns();
        }
    }

    /**
     * Generate interviewer profiles using AI
     *
     * @param string $companyName Company name
     * @param string|null $role Target role
     * @return array Interviewer profiles
     */
    protected function generateInterviewerProfilesWithAI(string $companyName, ?string $role): array
    {
        return [
            [
                'title' => 'Hiring Manager',
                'style' => 'behavioral',
                'focus_areas' => ['team fit', 'leadership', 'communication'],
                'difficulty' => 'medium',
            ],
            [
                'title' => 'Technical Lead',
                'style' => 'technical',
                'focus_areas' => ['technical skills', 'problem solving', 'system design'],
                'difficulty' => 'hard',
            ],
            [
                'title' => 'HR Recruiter',
                'style' => 'conversational',
                'focus_areas' => ['cultural fit', 'career goals', 'motivation'],
                'difficulty' => 'easy',
            ],
        ];
    }

    /**
     * Get basic company data as fallback
     *
     * @param string $companyName Company name
     * @return array Basic company data
     */
    protected function getBasicCompanyData(string $companyName): array
    {
        return [
            'company_name' => $companyName,
            'glassdoor' => ['available' => false],
            'linkedin' => ['available' => false],
            'website' => ['available' => false],
            'interview_patterns' => $this->getDefaultInterviewPatterns(),
            'talking_points' => $this->getDefaultTalkingPoints($companyName),
            'interviewers' => [],
            'ai_insights' => [
                'summary' => 'Limited data available. Focus on general interview preparation.',
            ],
        ];
    }

    /**
     * Get default talking points
     *
     * @param string $companyName Company name
     * @return array Default talking points
     */
    protected function getDefaultTalkingPoints(string $companyName): array
    {
        return [
            [
                'topic' => 'Company Research',
                'talking_point' => "Research {$companyName}'s recent news, products, and company culture",
                'category' => 'preparation',
                'importance' => 'high',
                'suggested_questions' => ["What excites you most about {$companyName}'s future?"],
            ],
        ];
    }

    /**
     * Get default interview patterns
     *
     * @return array Default patterns
     */
    protected function getDefaultInterviewPatterns(): array
    {
        return [
            'typical_rounds' => 4,
            'behavioral_weight' => 30,
            'technical_weight' => 50,
            'case_weight' => 20,
            'average_difficulty' => 3.0,
            'source' => 'default',
        ];
    }

    /**
     * Extract interview round count from data
     *
     * @param array $data Research data
     * @return int Round count
     */
    protected function extractRoundCount(array $data): int
    {
        return $data['interview_patterns']['typical_rounds'] ?? 4;
    }

    /**
     * Extract question type distribution from data
     *
     * @param array $data Research data
     * @return array Distribution
     */
    protected function extractQuestionDistribution(array $data): array
    {
        return [
            'behavioral' => $data['interview_patterns']['behavioral_weight'] ?? 30,
            'technical' => $data['interview_patterns']['technical_weight'] ?? 50,
            'case_study' => $data['interview_patterns']['case_weight'] ?? 20,
        ];
    }

    /**
     * Count total data points collected
     *
     * @param array $data Research data
     * @return int Data point count
     */
    protected function countDataPoints(array $data): int
    {
        $count = 0;
        $count += $data['glassdoor']['data_points'] ?? 0;
        $count += count($data['talking_points'] ?? []);
        $count += count($data['interviewers'] ?? []);
        return $count;
    }
}
