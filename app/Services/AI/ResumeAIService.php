<?php

namespace App\Services\AI;

use App\Models\Resume;
use App\Models\Job;
use App\Models\AIResumeGeneration;
use App\Models\ResumeAISuggestion;
use App\Traits\InteractsWithAI;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ResumeAIService
{
    use InteractsWithAI;
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Generate professional summary based on user profile
     */
    public function generateProfessionalSummary(Resume $resume, ?Job $targetJob = null): string
    {
        $cacheKey = "resume_summary_{$resume->id}_" . ($targetJob ? $targetJob->id : 'general');
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($resume, $targetJob) {
            $experience = $resume->experience ?? [];
            $skills = $resume->skills ?? [];
            $education = $resume->education ?? [];

            $prompt = $this->buildSummaryPrompt($resume, $experience, $skills, $education, $targetJob);

            try {
                $startTime = microtime(true);
                
                $summary = trim($this->ai(
                    $prompt,
                    'You are an expert resume writer and career coach. Create professional, compelling summaries that highlight achievements and value proposition.',
                    ['temperature' => 0.7]
                ));

                $tokensUsed = 0; // Tracked centrally by AIService
                $generationTime = microtime(true) - $startTime;

                // Log generation
                $this->logGeneration($resume->user_id, $resume->id, 'summary', $prompt, $summary, $tokensUsed, $generationTime);

                return $summary;
            } catch (\Exception $e) {
                Log::error('Resume summary generation failed', [
                    'resume_id' => $resume->id,
                    'error' => $e->getMessage(),
                ]);
                
                return $this->getFallbackSummary($resume);
            }
        });
    }

    /**
     * Optimize experience bullets with action verbs and quantification
     */
    public function optimizeExperienceBullets(array $bullets, ?string $jobTitle = null): array
    {
        if (empty($bullets)) {
            return [];
        }

        $bulletsList = implode("\n", array_map(fn($b, $k) => ($k + 1) . ". " . $b, $bullets, array_keys($bullets)));
        
        $prompt = "Rewrite the following resume bullet points to be more impactful using strong action verbs and quantifying achievements where possible.\n\nJob Title: {$jobTitle}\n\nBullet Points:\n{$bulletsList}\n\nReturn ONLY a JSON array of objects, where each object has 'original' (string), 'optimized' (string), and 'confidence' (integer 0-100) keys. Do not include markdown formatting.";

        try {
            $data = $this->aiJSON(
                $prompt,
                'You are an expert resume writer. Rewrite bullet points to be achievement-focused. Return strictly JSON.',
                ['temperature' => 0.7]
            );
            
            // Handle potential wrapper keys like "bullets" or direct array
            $optimizedBullets = $data['bullets'] ?? $data ?? [];

            // Fallback if structure is wrong
            if (!is_array($optimizedBullets)) {
                throw new \Exception("Invalid JSON structure");
            }

            return $optimizedBullets;

        } catch (\Exception $e) {
            Log::error('Bullet optimization failed', ['error' => $e->getMessage()]);
            
            // Fallback: return originals
            return array_map(fn($bullet) => [
                'original' => $bullet,
                'optimized' => $bullet,
                'confidence' => 0,
            ], $bullets);
        }
    }

    /**
     * Extract and categorize skills from experience and education
     */
    public function extractSkills(Resume $resume): array
    {
        $content = json_encode($resume->experience) . ' ' . json_encode($resume->education) . ' ' . json_encode($resume->projects);

        $prompt = "Extract technical skills, soft skills, and tools/technologies from this professional background. Categorize them appropriately:\n\n{$content}\n\nReturn ONLY a JSON array with three keys: 'technical', 'soft', and 'tools'. Each should contain an array of skill strings.";

        try {
            return $this->aiJSON(
                $prompt,
                'You are an AI that extracts and categorizes skills from professional backgrounds. Always respond with valid JSON only.',
                ['temperature' => 0.3]
            );
        } catch (\Exception $e) {
            Log::error('Skill extraction failed', [
                'resume_id' => $resume->id,
                'error' => $e->getMessage(),
            ]);
            
            return $this->getFallbackSkills();
        }
    }

    /**
     * Customize resume for specific job
     */
    public function customizeForJob(Resume $resume, Job $job): array
    {
        $suggestions = [];

        // Analyze job description
        $jobKeywords = $this->extractJobKeywords($job);
        
        // Compare with resume content
        $resumeKeywords = $this->extractResumeKeywords($resume);
        
        // Find missing important keywords
        $missingKeywords = array_diff($jobKeywords['important'], $resumeKeywords);

        if (!empty($missingKeywords)) {
            $suggestions[] = [
                'type' => 'keyword',
                'section' => 'skills',
                'priority' => 'high',
                'suggestion' => 'Add these relevant keywords from the job description: ' . implode(', ', array_slice($missingKeywords, 0, 10)),
                'keywords' => array_slice($missingKeywords, 0, 10),
            ];
        }

        // Suggest summary customization
        $customSummary = $this->generateProfessionalSummary($resume, $job);
        
        if ($customSummary !== $resume->professional_summary) {
            $suggestions[] = [
                'type' => 'summary',
                'section' => 'summary',
                'priority' => 'high',
                'original' => $resume->professional_summary,
                'suggested' => $customSummary,
                'reasoning' => 'Tailored to highlight skills and experience relevant to this specific role',
            ];
        }

        // Analyze experience relevance
        $experienceSuggestions = $this->suggestExperienceReordering($resume, $job);
        $suggestions = array_merge($suggestions, $experienceSuggestions);

        // Save suggestions to database
        foreach ($suggestions as $suggestion) {
            ResumeAISuggestion::create([
                'resume_id' => $resume->id,
                'section' => $suggestion['section'],
                'suggestion_type' => $suggestion['type'],
                'original_content' => $suggestion['original'] ?? '',
                'suggested_content' => $suggestion['suggested'] ?? $suggestion['suggestion'],
                'reasoning' => $suggestion['reasoning'] ?? '',
                'confidence_score' => $suggestion['confidence'] ?? 80,
                'status' => 'pending',
                'metadata' => $suggestion,
            ]);
        }

        return $suggestions;
    }

    /**
     * Analyze resume for ATS optimization
     */
    public function analyzeATSCompatibility(Resume $resume): array
    {
        $analysis = [
            'score' => 0,
            'issues' => [],
            'recommendations' => [],
            'keyword_density' => 0,
            'readability_score' => 0,
        ];

        // Check formatting
        if ($resume->template && !$resume->template->is_ats_friendly) {
            $analysis['issues'][] = 'Template may not be ATS-friendly';
            $analysis['recommendations'][] = 'Switch to an ATS-optimized template';
        } else {
            $analysis['score'] += 20;
        }

        // Check for contact information
        $contactScore = 0;
        if ($resume->email) $contactScore += 5;
        if ($resume->phone) $contactScore += 5;
        if ($resume->location) $contactScore += 5;
        if ($resume->linkedin_url) $contactScore += 5;
        
        $analysis['score'] += $contactScore;

        if ($contactScore < 15) {
            $analysis['recommendations'][] = 'Add complete contact information (email, phone, location, LinkedIn)';
        }

        // Check for key sections
        if (empty($resume->experience)) {
            $analysis['issues'][] = 'Missing work experience section';
        } else {
            $analysis['score'] += 20;
        }

        if (empty($resume->education)) {
            $analysis['issues'][] = 'Missing education section';
        } else {
            $analysis['score'] += 15;
        }

        if (empty($resume->skills)) {
            $analysis['issues'][] = 'Missing skills section';
        } else {
            $analysis['score'] += 15;
        }

        // Check for quantified achievements
        $hasMetrics = $this->hasQuantifiedAchievements($resume);
        if ($hasMetrics) {
            $analysis['score'] += 15;
        } else {
            $analysis['recommendations'][] = 'Quantify your achievements with numbers, percentages, or metrics';
        }

        // Check keyword density
        if ($resume->target_job_id) {
            $keywordAnalysis = $this->analyzeKeywordDensity($resume);
            $analysis['keyword_density'] = $keywordAnalysis['density'];
            $analysis['score'] += min(15, $keywordAnalysis['density']);
            
            if ($keywordAnalysis['density'] < 10) {
                $analysis['recommendations'][] = 'Increase relevant keywords from job description';
            }
        }

        // Readability check
        $wordCount = $resume->getWordCount();
        if ($wordCount > 1000) {
            $analysis['issues'][] = 'Resume is too long (keep it under 1000 words for 1-2 pages)';
        } else {
            $analysis['score'] += 10;
        }

        return $analysis;
    }

    /**
     * Generate achievement from raw description
     */
    public function quantifyAchievement(string $description, string $context = ''): string
    {
        $prompt = "Transform this work responsibility into a quantified achievement using the STAR method (Situation, Task, Action, Result). Add specific metrics, percentages, or numbers:\n\nResponsibility: {$description}\nContext: {$context}\n\nProvide only the achievement bullet point.";

        try {
            return trim($this->ai(
                $prompt,
                'You are an expert at transforming job responsibilities into measurable achievements with specific metrics.',
                ['temperature' => 0.8]
            ));
        } catch (\Exception $e) {
            return $description;
        }
    }

    /**
     * Private helper methods
     */
    private function buildSummaryPrompt(Resume $resume, array $experience, array $skills, array $education, ?Job $targetJob): string
    {
        $yearsOfExperience = $this->calculateYearsOfExperience($experience);
        $topSkills = collect($skills)->flatten()->take(8)->implode(', ');
        
        $prompt = "Create a compelling 3-4 sentence professional summary for a resume:\n\n";
        $prompt .= "Years of Experience: {$yearsOfExperience}\n";
        $prompt .= "Top Skills: {$topSkills}\n";
        
        if (!empty($experience)) {
            $prompt .= "Recent Role: " . ($experience[0]['title'] ?? 'Professional') . "\n";
        }
        
        if (!empty($education)) {
            $prompt .= "Education: " . ($education[0]['degree'] ?? '') . " in " . ($education[0]['field'] ?? '') . "\n";
        }

        if ($targetJob) {
            $prompt .= "\nTarget Role: {$targetJob->title}\n";
            $prompt .= "Company: {$targetJob->company->name}\n";
            $prompt .= "Key Requirements: " . substr($targetJob->requirements, 0, 300) . "\n";
            $prompt .= "\nTailor the summary to highlight relevance for this specific role.\n";
        }

        $prompt .= "\nWrite a professional summary that highlights value proposition, key achievements, and career goals. Make it compelling and achievement-focused.";

        return $prompt;
    }

    private function calculateYearsOfExperience(array $experience): int
    {
        $totalMonths = 0;

        foreach ($experience as $exp) {
            if (isset($exp['start_date']) && isset($exp['end_date'])) {
                $start = \Carbon\Carbon::parse($exp['start_date']);
                $end = $exp['end_date'] === 'Present' ? now() : \Carbon\Carbon::parse($exp['end_date']);
                $totalMonths += $start->diffInMonths($end);
            }
        }

        return max(0, intval($totalMonths / 12));
    }

    private function extractJobKeywords(Job $job): array
    {
        $text = strtolower($job->description . ' ' . $job->requirements . ' ' . $job->title);
        
        // Extract important keywords (this is simplified; real implementation would use NLP)
        $keywords = $this->extractKeywordsFromText($text);
        
        return [
            'important' => array_slice($keywords, 0, 20),
            'all' => $keywords,
        ];
    }

    private function extractResumeKeywords(Resume $resume): array
    {
        $text = strtolower(
            $resume->professional_summary . ' ' .
            json_encode($resume->experience) . ' ' .
            json_encode($resume->skills) . ' ' .
            json_encode($resume->projects)
        );

        return $this->extractKeywordsFromText($text);
    }

    private function extractKeywordsFromText(string $text): array
    {
        // Remove special characters
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $words = explode(' ', $text);
        
        // Stop words
        $stopWords = ['the', 'is', 'at', 'which', 'on', 'and', 'a', 'an', 'as', 'are', 'was', 'were', 'been', 'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should'];
        
        // Filter and count
        $keywords = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 3 && !in_array($word, $stopWords);
        });

        // Get frequency
        $frequency = array_count_values($keywords);
        arsort($frequency);

        return array_keys($frequency);
    }

    private function suggestExperienceReordering(Resume $resume, Job $job): array
    {
        // This would use AI to analyze which experiences are most relevant
        // For now, return empty array (can be enhanced)
        return [];
    }

    private function hasQuantifiedAchievements(Resume $resume): bool
    {
        $content = json_encode($resume->experience) . json_encode($resume->achievements);
        return preg_match('/\d+%|\d+\+|\$\d+|increased|decreased|improved|reduced|grew|saved|generated/', strtolower($content)) > 0;
    }

    private function analyzeKeywordDensity(Resume $resume): array
    {
        if (!$resume->target_job_id) {
            return ['density' => 0, 'matched' => 0, 'total' => 0];
        }

        $jobKeywords = $this->extractJobKeywords($resume->targetJob);
        $resumeKeywords = $this->extractResumeKeywords($resume);

        $matched = array_intersect($jobKeywords['important'], $resumeKeywords);
        $density = count($jobKeywords['important']) > 0 
            ? (count($matched) / count($jobKeywords['important'])) * 100 
            : 0;

        return [
            'density' => round($density, 2),
            'matched' => count($matched),
            'total' => count($jobKeywords['important']),
        ];
    }

    private function logGeneration(int $userId, ?int $resumeId, string $type, string $prompt, string $response, int $tokens, float $time): void
    {
        AIResumeGeneration::create([
            'user_id' => $userId,
            'resume_id' => $resumeId,
            'generation_type' => $type,
            'input_data' => substr($prompt, 0, 5000),
            'prompt_used' => substr($prompt, 0, 5000),
            'ai_response' => substr($response, 0, 5000),
            'tokens_used' => $tokens,
            'cost' => $tokens * 0.00003, // GPT-4 pricing estimation
            'generation_time' => $time,
            'model_used' => config('ai.azure.models.chat'),
        ]);
    }

    private function getFallbackSummary(Resume $resume): string
    {
        return "Experienced professional with expertise in " . 
               implode(', ', array_slice(($resume->skills['technical'] ?? []), 0, 3)) . 
               ". Proven track record of delivering results and driving business value.";
    }

    private function getFallbackSkills(): array
    {
        return [
            'technical' => [],
            'soft' => [],
            'tools' => [],
        ];
    }
}
