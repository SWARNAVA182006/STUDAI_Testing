<?php

namespace App\Services;

use App\Models\Job;
use App\Models\User;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ResumeCustomizerService
{
    protected $aiService;
    
    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }
    
    /**
     * Customize resume for a specific job posting
     */
    public function customizeForJob($resume, Job $job)
    {
        // Extract job keywords using AI
        $keywords = $this->extractKeywords($job);
        
        // Optimize resume content
        $optimized = $this->optimizeContent($resume, $keywords, $job);
        
        // Calculate ATS score
        $atsScore = $this->calculateATSScore($optimized, $job);
        
        // Generate improvement suggestions
        $suggestions = $this->generateSuggestions($resume, $job, $atsScore);
        
        // Identify missing keywords
        $missingKeywords = $this->identifyMissingKeywords($resume, $keywords);
        
        return [
            'optimized_resume' => $optimized,
            'ats_score' => $atsScore,
            'keywords_matched' => $keywords['matched'],
            'keywords_missing' => $missingKeywords,
            'suggestions' => $suggestions,
            'strength_score' => $this->calculateStrengthScore($atsScore),
        ];
    }
    
    /**
     * Extract important keywords from job description using AI
     */
    protected function extractKeywords(Job $job)
    {
        $cacheKey = 'job_keywords_' . $job->id;
        
        return Cache::remember($cacheKey, 3600, function () use ($job) {
            $prompt = "Analyze this job posting and extract:\n\n";
            $prompt .= "Job Title: {$job->title}\n";
            $prompt .= "Description: {$job->description}\n";
            $prompt .= "Requirements: " . json_encode($job->requirements) . "\n\n";
            $prompt .= "Extract:\n";
            $prompt .= "1. Technical skills (programming languages, tools, frameworks)\n";
            $prompt .= "2. Soft skills (communication, leadership, etc.)\n";
            $prompt .= "3. Industry keywords and buzzwords\n";
            $prompt .= "4. Required qualifications and certifications\n";
            $prompt .= "5. Years of experience indicators\n\n";
            $prompt .= "Return as JSON with categories: technical_skills, soft_skills, industry_terms, qualifications, experience_keywords";
            
            $response = $this->aiService->generateText($prompt, 'You are an expert ATS keyword extractor. Return only valid JSON.');
            
            try {
                $keywords = json_decode($response, true);
                return [
                    'all' => array_merge(
                        $keywords['technical_skills'] ?? [],
                        $keywords['soft_skills'] ?? [],
                        $keywords['industry_terms'] ?? [],
                        $keywords['qualifications'] ?? []
                    ),
                    'technical' => $keywords['technical_skills'] ?? [],
                    'soft' => $keywords['soft_skills'] ?? [],
                    'industry' => $keywords['industry_terms'] ?? [],
                    'qualifications' => $keywords['qualifications'] ?? [],
                    'matched' => [], // Will be populated later
                ];
            } catch (\Exception $e) {
                // Fallback to simple extraction
                return $this->fallbackKeywordExtraction($job);
            }
        });
    }
    
    /**
     * Optimize resume content for better ATS matching
     */
    protected function optimizeContent($resume, $keywords, Job $job)
    {
        $prompt = "Optimize this resume for the following job posting:\n\n";
        $prompt .= "RESUME:\n{$resume}\n\n";
        $prompt .= "JOB TITLE: {$job->title}\n";
        $prompt .= "KEY REQUIREMENTS:\n" . json_encode($job->requirements) . "\n\n";
        $prompt .= "IMPORTANT KEYWORDS: " . implode(', ', $keywords['all']) . "\n\n";
        $prompt .= "Instructions:\n";
        $prompt .= "1. Rewrite bullet points to include relevant keywords naturally\n";
        $prompt .= "2. Quantify achievements where possible\n";
        $prompt .= "3. Use action verbs (achieved, led, developed, etc.)\n";
        $prompt .= "4. Match industry terminology from the job posting\n";
        $prompt .= "5. Highlight relevant experience and skills\n";
        $prompt .= "6. Keep the same overall structure and honesty\n";
        $prompt .= "7. Ensure ATS-friendly formatting (no tables, simple bullets)\n\n";
        $prompt .= "Return the optimized resume maintaining professional tone.";
        
        $systemPrompt = "You are an expert resume writer and career coach. Optimize resumes for ATS systems while maintaining truthfulness and professional quality.";
        
        return $this->aiService->generateText($prompt, $systemPrompt);
    }
    
    /**
     * Calculate ATS compatibility score
     */
    protected function calculateATSScore($resume, Job $job)
    {
        $score = [
            'overall' => 0,
            'keyword_match' => 0,
            'formatting' => 0,
            'experience_match' => 0,
            'skills_match' => 0,
        ];
        
        // Keyword matching (40%)
        $jobKeywords = $this->extractKeywords($job)['all'];
        $matchedKeywords = 0;
        $resumeLower = strtolower($resume);
        
        foreach ($jobKeywords as $keyword) {
            if (stripos($resumeLower, strtolower($keyword)) !== false) {
                $matchedKeywords++;
            }
        }
        
        $score['keyword_match'] = count($jobKeywords) > 0 
            ? ($matchedKeywords / count($jobKeywords)) * 100 
            : 0;
        
        // Formatting check (20%)
        $score['formatting'] = $this->checkFormatting($resume);
        
        // Experience level match (20%)
        $score['experience_match'] = $this->matchExperienceLevel($resume, $job);
        
        // Skills match (20%)
        $score['skills_match'] = $this->matchSkills($resume, $job);
        
        // Calculate overall score
        $score['overall'] = (
            ($score['keyword_match'] * 0.4) +
            ($score['formatting'] * 0.2) +
            ($score['experience_match'] * 0.2) +
            ($score['skills_match'] * 0.2)
        );
        
        return $score;
    }
    
    /**
     * Check resume formatting for ATS compatibility
     */
    protected function checkFormatting($resume)
    {
        $score = 100;
        
        // Penalize for ATS-unfriendly elements
        if (preg_match('/\|{2,}/', $resume)) $score -= 10; // Tables
        if (preg_match('/[►•○●■□]/', $resume)) $score -= 5; // Special bullets
        if (preg_match('/[\x{2500}-\x{257F}]/u', $resume)) $score -= 10; // Box drawing chars
        if (strlen($resume) < 500) $score -= 20; // Too short
        if (strlen($resume) > 10000) $score -= 10; // Too long
        
        // Bonus for good elements
        if (preg_match('/\b(email|phone|linkedin)\b/i', $resume)) $score += 5;
        if (preg_match('/\b\d{4}\s*-\s*\d{4}\b/', $resume)) $score += 5; // Date ranges
        
        return max(0, min(100, $score));
    }
    
    /**
     * Match experience level between resume and job
     */
    protected function matchExperienceLevel($resume, Job $job)
    {
        // Extract years of experience from resume
        preg_match_all('/(\d+)\+?\s*years?/i', $resume, $matches);
        $resumeYears = !empty($matches[1]) ? max(array_map('intval', $matches[1])) : 0;
        
        // Map job experience level to years
        $experienceLevelMap = [
            'entry_level' => 2,
            'mid_level' => 5,
            'senior_level' => 8,
            'executive' => 12,
        ];
        
        $requiredYears = $experienceLevelMap[$job->experience_level] ?? 3;
        
        // Calculate match score
        if ($resumeYears >= $requiredYears) {
            return 100;
        } elseif ($resumeYears >= $requiredYears - 1) {
            return 80;
        } elseif ($resumeYears >= $requiredYears - 2) {
            return 60;
        }
        
        return 40;
    }
    
    /**
     * Match skills between resume and job requirements
     */
    protected function matchSkills($resume, Job $job)
    {
        $requiredSkills = $job->required_skills ?? [];
        if (empty($requiredSkills)) return 100;
        
        $matchedSkills = 0;
        $resumeLower = strtolower($resume);
        
        foreach ($requiredSkills as $skill) {
            if (stripos($resumeLower, strtolower($skill)) !== false) {
                $matchedSkills++;
            }
        }
        
        return ($matchedSkills / count($requiredSkills)) * 100;
    }
    
    /**
     * Generate improvement suggestions
     */
    protected function generateSuggestions($resume, Job $job, $atsScore)
    {
        $suggestions = [];
        
        // Keyword suggestions
        if ($atsScore['keyword_match'] < 70) {
            $missingKeywords = $this->identifyMissingKeywords($resume, $this->extractKeywords($job));
            $suggestions[] = [
                'type' => 'keywords',
                'priority' => 'high',
                'title' => 'Add Missing Keywords',
                'description' => 'Your resume is missing important keywords from the job posting.',
                'action' => 'Consider adding: ' . implode(', ', array_slice($missingKeywords, 0, 5)),
            ];
        }
        
        // Formatting suggestions
        if ($atsScore['formatting'] < 80) {
            $suggestions[] = [
                'type' => 'formatting',
                'priority' => 'high',
                'title' => 'Improve ATS Compatibility',
                'description' => 'Your resume may have formatting issues that ATS systems struggle with.',
                'action' => 'Use simple bullet points, avoid tables, and use standard fonts.',
            ];
        }
        
        // Experience suggestions
        if ($atsScore['experience_match'] < 70) {
            $suggestions[] = [
                'type' => 'experience',
                'priority' => 'medium',
                'title' => 'Highlight Relevant Experience',
                'description' => 'Better highlight experience that matches the job requirements.',
                'action' => 'Add specific examples and quantifiable achievements.',
            ];
        }
        
        // Skills suggestions
        if ($atsScore['skills_match'] < 70) {
            $suggestions[] = [
                'type' => 'skills',
                'priority' => 'high',
                'title' => 'Add Required Skills',
                'description' => 'Your resume is missing some required skills from the job posting.',
                'action' => 'Include: ' . implode(', ', array_slice($job->required_skills ?? [], 0, 3)),
            ];
        }
        
        // Quantification suggestion
        if (!preg_match('/\d+%|\$\d+|\d+\+/', $resume)) {
            $suggestions[] = [
                'type' => 'achievements',
                'priority' => 'medium',
                'title' => 'Quantify Achievements',
                'description' => 'Add numbers and percentages to make your achievements more impactful.',
                'action' => 'Use metrics like "increased sales by 30%" or "managed team of 10".',
            ];
        }
        
        // Action verbs suggestion
        $actionVerbs = ['achieved', 'led', 'developed', 'implemented', 'managed', 'created'];
        $hasActionVerbs = false;
        foreach ($actionVerbs as $verb) {
            if (stripos($resume, $verb) !== false) {
                $hasActionVerbs = true;
                break;
            }
        }
        
        if (!$hasActionVerbs) {
            $suggestions[] = [
                'type' => 'language',
                'priority' => 'medium',
                'title' => 'Use Strong Action Verbs',
                'description' => 'Start bullet points with powerful action verbs.',
                'action' => 'Use words like: achieved, led, developed, implemented, managed.',
            ];
        }
        
        return $suggestions;
    }
    
    /**
     * Identify keywords missing from resume
     */
    protected function identifyMissingKeywords($resume, $keywords)
    {
        $missing = [];
        $resumeLower = strtolower($resume);
        
        foreach ($keywords['all'] as $keyword) {
            if (stripos($resumeLower, strtolower($keyword)) === false) {
                $missing[] = $keyword;
            }
        }
        
        return $missing;
    }
    
    /**
     * Calculate overall strength score
     */
    protected function calculateStrengthScore($atsScore)
    {
        $overall = $atsScore['overall'];
        
        if ($overall >= 80) {
            return ['score' => $overall, 'level' => 'excellent', 'color' => 'green'];
        } elseif ($overall >= 60) {
            return ['score' => $overall, 'level' => 'good', 'color' => 'blue'];
        } elseif ($overall >= 40) {
            return ['score' => $overall, 'level' => 'fair', 'color' => 'yellow'];
        } else {
            return ['score' => $overall, 'level' => 'needs improvement', 'color' => 'red'];
        }
    }
    
    /**
     * Fallback keyword extraction without AI
     */
    protected function fallbackKeywordExtraction(Job $job)
    {
        $text = $job->title . ' ' . $job->description . ' ' . json_encode($job->requirements);
        $words = str_word_count(strtolower($text), 1);
        
        // Remove common words
        $commonWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $words = array_diff($words, $commonWords);
        
        // Get word frequency
        $frequency = array_count_values($words);
        arsort($frequency);
        
        // Get top keywords
        $keywords = array_slice(array_keys($frequency), 0, 20);
        
        return [
            'all' => $keywords,
            'technical' => $job->required_skills ?? [],
            'soft' => [],
            'industry' => [],
            'qualifications' => [],
            'matched' => [],
        ];
    }
    
    /**
     * Export optimized resume to file
     */
    public function exportOptimizedResume($optimizedContent, User $user, Job $job)
    {
        $filename = 'resume_optimized_' . $job->slug . '_' . time() . '.txt';
        $path = 'resumes/optimized/' . $user->id . '/' . $filename;
        
        Storage::put($path, $optimizedContent);
        
        return [
            'path' => $path,
            'url' => Storage::url($path),
            'filename' => $filename,
        ];
    }
}
