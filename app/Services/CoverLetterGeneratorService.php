<?php

namespace App\Services;

use App\Models\Job;
use App\Models\User;
use App\Models\Company;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CoverLetterGeneratorService
{
    protected $aiService;
    
    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }
    
    /**
     * Generate cover letter for a specific job
     */
    public function generateForJob(User $user, Job $job, array $options = [])
    {
        // Get user profile
        $profile = $user->profile;
        
        // Research company if available
        $companyInsights = $this->researchCompany($job->company);
        
        // Determine tone
        $tone = $options['tone'] ?? 'professional';
        
        // Select template
        $template = $options['template'] ?? 'standard';
        
        // Generate cover letter
        $coverLetter = $this->generateContent($user, $profile, $job, $companyInsights, $tone, $template);
        
        // Get alternative versions
        $alternatives = $this->generateAlternatives($user, $profile, $job, $tone);
        
        return [
            'content' => $coverLetter,
            'alternatives' => $alternatives,
            'tone' => $tone,
            'template' => $template,
            'word_count' => str_word_count($coverLetter),
            'personalization_score' => $this->calculatePersonalizationScore($coverLetter, $job),
        ];
    }
    
    /**
     * Generate cover letter content using AI
     */
    protected function generateContent(User $user, $profile, Job $job, $companyInsights, $tone, $template)
    {
        $prompt = $this->buildPrompt($user, $profile, $job, $companyInsights, $tone, $template);
        
        $systemPrompt = "You are an expert career coach and professional writer specializing in compelling cover letters. ";
        $systemPrompt .= "Write personalized, authentic cover letters that showcase the candidate's unique value while maintaining the requested tone. ";
        $systemPrompt .= "Avoid clichés and generic statements. Focus on specific achievements and genuine enthusiasm.";
        
        return $this->aiService->generateText($prompt, $systemPrompt);
    }
    
    /**
     * Build comprehensive prompt for AI
     */
    protected function buildPrompt(User $user, $profile, Job $job, $companyInsights, $tone, $template)
    {
        $prompt = "Write a compelling cover letter with the following details:\n\n";
        
        // Candidate information
        $prompt .= "CANDIDATE:\n";
        $prompt .= "Name: {$user->name}\n";
        $prompt .= "Headline: " . ($profile->headline ?? 'Professional') . "\n";
        $prompt .= "Summary: " . ($profile->summary ?? '') . "\n";
        
        if (!empty($profile->experience)) {
            $prompt .= "\nRELEVANT EXPERIENCE:\n";
            foreach (array_slice($profile->experience, 0, 3) as $exp) {
                $prompt .= "- {$exp['title']} at {$exp['company']} ({$exp['duration']})\n";
                $prompt .= "  {$exp['description']}\n";
            }
        }
        
        if (!empty($profile->skills)) {
            $prompt .= "\nKEY SKILLS: " . implode(', ', array_slice($profile->skills, 0, 10)) . "\n";
        }
        
        // Job information
        $prompt .= "\nPOSITION:\n";
        $prompt .= "Job Title: {$job->title}\n";
        $prompt .= "Company: {$job->company_name}\n";
        $prompt .= "Location: {$job->location}\n";
        $prompt .= "Type: {$job->job_type}\n";
        
        $prompt .= "\nJOB REQUIREMENTS:\n";
        $prompt .= json_encode($job->requirements) . "\n";
        
        $prompt .= "\nJOB DESCRIPTION:\n";
        $prompt .= Str::limit($job->description, 500) . "\n";
        
        // Company insights
        if ($companyInsights) {
            $prompt .= "\nCOMPANY INSIGHTS:\n";
            $prompt .= "Industry: " . ($companyInsights['industry'] ?? 'N/A') . "\n";
            $prompt .= "About: " . Str::limit($companyInsights['description'] ?? '', 200) . "\n";
            if (!empty($companyInsights['culture'])) {
                $prompt .= "Culture: {$companyInsights['culture']}\n";
            }
        }
        
        // Tone and template instructions
        $prompt .= "\nWRITING INSTRUCTIONS:\n";
        $prompt .= "Tone: {$tone}\n";
        $prompt .= $this->getToneInstructions($tone) . "\n";
        $prompt .= "Template: {$template}\n";
        $prompt .= $this->getTemplateStructure($template) . "\n";
        
        $prompt .= "\nREQUIREMENTS:\n";
        $prompt .= "- Length: 300-400 words\n";
        $prompt .= "- Include specific examples from experience\n";
        $prompt .= "- Show enthusiasm for the role and company\n";
        $prompt .= "- Highlight 2-3 key achievements relevant to the job\n";
        $prompt .= "- Address why you're interested in this specific company\n";
        $prompt .= "- Strong opening and closing paragraphs\n";
        $prompt .= "- No generic platitudes or clichés\n";
        $prompt .= "- Maintain authenticity and genuine voice\n";
        
        return $prompt;
    }
    
    /**
     * Get tone-specific instructions
     */
    protected function getToneInstructions($tone)
    {
        $instructions = [
            'professional' => 'Use formal, business-appropriate language. Be respectful and polished.',
            'enthusiastic' => 'Show genuine excitement and energy. Use positive, dynamic language.',
            'confident' => 'Assert your qualifications firmly. Use strong, decisive language.',
            'conversational' => 'Write naturally and warmly. Be personable while remaining professional.',
            'creative' => 'Use creative language and storytelling. Show personality and unique perspective.',
        ];
        
        return $instructions[$tone] ?? $instructions['professional'];
    }
    
    /**
     * Get template structure
     */
    protected function getTemplateStructure($template)
    {
        $structures = [
            'standard' => "Follow standard cover letter structure:\n1. Opening: Introduction and position interest\n2. Body: Qualifications and relevant experience\n3. Closing: Call to action and contact information",
            
            'problem_solution' => "Structure:\n1. Identify a problem the company faces\n2. Explain how your skills solve that problem\n3. Provide specific examples of past solutions\n4. Express desire to contribute",
            
            'storytelling' => "Structure:\n1. Start with a compelling story or anecdote\n2. Connect the story to your professional journey\n3. Tie your experience to the role\n4. End with future vision",
            
            'achievements_focused' => "Structure:\n1. Open with strongest achievement\n2. Detail 2-3 key accomplishments with metrics\n3. Connect achievements to job requirements\n4. Express readiness to contribute",
            
            't_format' => "T-Format Structure:\n1. Brief introduction\n2. Two-column comparison:\n   - Your qualifications | Job requirements\n3. Closing paragraph with enthusiasm",
        ];
        
        return $structures[$template] ?? $structures['standard'];
    }
    
    /**
     * Research company information
     */
    protected function researchCompany($company)
    {
        if (!$company) return null;
        
        $cacheKey = 'company_insights_' . $company->id;
        
        return Cache::remember($cacheKey, 86400, function () use ($company) {
            return [
                'name' => $company->name,
                'industry' => $company->industry,
                'description' => $company->description,
                'culture' => $company->culture ?? null,
                'values' => $company->values ?? null,
                'size' => $company->company_size,
                'website' => $company->website,
            ];
        });
    }
    
    /**
     * Generate alternative versions
     */
    protected function generateAlternatives(User $user, $profile, Job $job, $baseTone)
    {
        $alternatives = [];
        $tones = ['professional', 'enthusiastic', 'confident'];
        
        // Remove base tone from alternatives
        $tones = array_diff($tones, [$baseTone]);
        
        foreach (array_slice($tones, 0, 2) as $tone) {
            $alternatives[] = [
                'tone' => $tone,
                'preview' => $this->generatePreview($user, $profile, $job, $tone),
            ];
        }
        
        return $alternatives;
    }
    
    /**
     * Generate preview (first paragraph) of alternative tone
     */
    protected function generatePreview(User $user, $profile, Job $job, $tone)
    {
        $prompt = "Write ONLY the opening paragraph of a cover letter for:\n\n";
        $prompt .= "Candidate: {$user->name}\n";
        $prompt .= "Position: {$job->title} at {$job->company_name}\n";
        $prompt .= "Tone: {$tone}\n\n";
        $prompt .= "Make it compelling and {$tone}. Maximum 3 sentences.";
        
        $systemPrompt = "You are a cover letter expert. Write only the requested opening paragraph in the specified tone.";
        
        return $this->aiService->generateText($prompt, $systemPrompt);
    }
    
    /**
     * Calculate personalization score
     */
    protected function calculatePersonalizationScore($coverLetter, Job $job)
    {
        $score = 0;
        
        // Check for company name (20 points)
        if (stripos($coverLetter, $job->company_name) !== false) {
            $score += 20;
        }
        
        // Check for job title (15 points)
        if (stripos($coverLetter, $job->title) !== false) {
            $score += 15;
        }
        
        // Check for specific skills from job requirements (25 points)
        $requiredSkills = $job->required_skills ?? [];
        $matchedSkills = 0;
        foreach ($requiredSkills as $skill) {
            if (stripos($coverLetter, $skill) !== false) {
                $matchedSkills++;
            }
        }
        if (count($requiredSkills) > 0) {
            $score += ($matchedSkills / count($requiredSkills)) * 25;
        }
        
        // Check for specific achievements with numbers (20 points)
        if (preg_match('/\d+%|\$\d+|\d+\+/', $coverLetter)) {
            $score += 20;
        }
        
        // Check for company-specific information (10 points)
        if ($job->company && stripos($coverLetter, $job->company->industry ?? '') !== false) {
            $score += 10;
        }
        
        // Check for location mention (10 points)
        if (stripos($coverLetter, $job->location) !== false) {
            $score += 10;
        }
        
        return min(100, $score);
    }
    
    /**
     * Get available tones
     */
    public function getAvailableTones()
    {
        return [
            'professional' => [
                'name' => 'Professional',
                'description' => 'Formal, business-appropriate language',
                'best_for' => 'Corporate positions, traditional industries',
            ],
            'enthusiastic' => [
                'name' => 'Enthusiastic',
                'description' => 'Energetic and passionate tone',
                'best_for' => 'Startups, creative roles, mission-driven companies',
            ],
            'confident' => [
                'name' => 'Confident',
                'description' => 'Assertive and self-assured',
                'best_for' => 'Leadership roles, competitive industries',
            ],
            'conversational' => [
                'name' => 'Conversational',
                'description' => 'Natural and personable',
                'best_for' => 'Modern companies, collaborative environments',
            ],
            'creative' => [
                'name' => 'Creative',
                'description' => 'Unique and storytelling approach',
                'best_for' => 'Creative industries, innovative companies',
            ],
        ];
    }
    
    /**
     * Get available templates
     */
    public function getAvailableTemplates()
    {
        return [
            'standard' => [
                'name' => 'Standard',
                'description' => 'Traditional three-paragraph structure',
                'best_for' => 'Most positions and industries',
            ],
            'problem_solution' => [
                'name' => 'Problem-Solution',
                'description' => 'Address company challenges you can solve',
                'best_for' => 'Consulting, product roles, strategic positions',
            ],
            'storytelling' => [
                'name' => 'Storytelling',
                'description' => 'Narrative approach with personal anecdote',
                'best_for' => 'Creative roles, communications, marketing',
            ],
            'achievements_focused' => [
                'name' => 'Achievements-Focused',
                'description' => 'Lead with quantifiable accomplishments',
                'best_for' => 'Sales, leadership, results-driven roles',
            ],
            't_format' => [
                'name' => 'T-Format',
                'description' => 'Two-column comparison format',
                'best_for' => 'When closely matching job requirements',
            ],
        ];
    }
    
    /**
     * Save cover letter draft
     */
    public function saveDraft(User $user, Job $job, $content, $metadata = [])
    {
        return $user->coverLetterDrafts()->create([
            'job_id' => $job->id,
            'content' => $content,
            'tone' => $metadata['tone'] ?? 'professional',
            'template' => $metadata['template'] ?? 'standard',
            'personalization_score' => $this->calculatePersonalizationScore($content, $job),
            'word_count' => str_word_count($content),
        ]);
    }
    
    /**
     * Get AI-powered improvement suggestions
     */
    public function getSuggestions($coverLetter, Job $job)
    {
        $suggestions = [];
        
        // Check length
        $wordCount = str_word_count($coverLetter);
        if ($wordCount < 250) {
            $suggestions[] = [
                'type' => 'length',
                'severity' => 'warning',
                'message' => 'Your cover letter is quite short. Consider expanding to 300-400 words.',
            ];
        } elseif ($wordCount > 500) {
            $suggestions[] = [
                'type' => 'length',
                'severity' => 'warning',
                'message' => 'Your cover letter is quite long. Consider condensing to 300-400 words.',
            ];
        }
        
        // Check for company name
        if (stripos($coverLetter, $job->company_name) === false) {
            $suggestions[] = [
                'type' => 'personalization',
                'severity' => 'error',
                'message' => "Mention the company name ({$job->company_name}) to show genuine interest.",
            ];
        }
        
        // Check for specific skills
        $requiredSkills = $job->required_skills ?? [];
        $mentionedSkills = 0;
        foreach ($requiredSkills as $skill) {
            if (stripos($coverLetter, $skill) !== false) {
                $mentionedSkills++;
            }
        }
        if ($mentionedSkills < min(3, count($requiredSkills))) {
            $suggestions[] = [
                'type' => 'skills',
                'severity' => 'warning',
                'message' => 'Mention more required skills from the job posting to improve relevance.',
            ];
        }
        
        // Check for quantifiable achievements
        if (!preg_match('/\d+%|\$\d+|\d+\s*(years|months|people|users|customers)/i', $coverLetter)) {
            $suggestions[] = [
                'type' => 'achievements',
                'severity' => 'info',
                'message' => 'Add quantifiable achievements (e.g., "increased sales by 30%") to make your letter more impactful.',
            ];
        }
        
        // Check for generic phrases
        $genericPhrases = [
            'I am writing to apply',
            'Dear Hiring Manager',
            'I believe I would be a great fit',
            'I am passionate about',
            'team player',
        ];
        
        foreach ($genericPhrases as $phrase) {
            if (stripos($coverLetter, $phrase) !== false) {
                $suggestions[] = [
                    'type' => 'language',
                    'severity' => 'info',
                    'message' => "Consider replacing generic phrase '{$phrase}' with more specific language.",
                ];
                break; // Only suggest once
            }
        }
        
        return $suggestions;
    }
}
