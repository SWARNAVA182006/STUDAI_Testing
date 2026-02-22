<?php

namespace App\Services\AI;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser as PdfParser;

class ResumeAnalyzerService extends AIService
{
    /**
     * Analyze a resume file and extract structured data
     */
    public function analyzeResume(string $filePath): array
    {
        // Extract text from resume
        $resumeText = $this->extractTextFromFile($filePath);
        
        if (empty($resumeText)) {
            throw new \Exception('Could not extract text from resume');
        }

        $systemPrompt = <<<SYSTEM
You are an expert resume analyzer. Extract structured information from resumes and provide detailed analysis.
Always respond with valid JSON format.
SYSTEM;

        $prompt = <<<PROMPT
Analyze this resume and extract the following information in JSON format:

{
  "personal_info": {
    "name": "Full name",
    "email": "Email address",
    "phone": "Phone number",
    "location": "Current location",
    "linkedin": "LinkedIn URL",
    "portfolio": "Portfolio/website URL"
  },
  "summary": "Professional summary or objective",
  "experience": [
    {
      "title": "Job title",
      "company": "Company name",
      "location": "Job location",
      "start_date": "YYYY-MM",
      "end_date": "YYYY-MM or 'Present'",
      "description": "Job description",
      "achievements": ["Achievement 1", "Achievement 2"]
    }
  ],
  "education": [
    {
      "degree": "Degree name",
      "institution": "School/University",
      "field": "Field of study",
      "graduation_year": "YYYY",
      "gpa": "GPA if mentioned"
    }
  ],
  "skills": {
    "technical": ["Skill 1", "Skill 2"],
    "soft": ["Skill 1", "Skill 2"],
    "languages": [{"language": "English", "proficiency": "Native"}]
  },
  "certifications": [
    {
      "name": "Certification name",
      "issuer": "Issuing organization",
      "date": "YYYY-MM"
    }
  ],
  "projects": [
    {
      "name": "Project name",
      "description": "Brief description",
      "technologies": ["Tech 1", "Tech 2"],
      "url": "Project URL if available"
    }
  ]
}

Resume text:
$resumeText
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }

    /**
     * Provide feedback and improvement suggestions for a resume
     */
    public function getResumeFeedback(string $resumeText, ?string $targetRole = null): array
    {
        $systemPrompt = <<<SYSTEM
You are an expert career coach and resume reviewer. Provide constructive, actionable feedback to improve resumes.
Always respond with valid JSON format.
SYSTEM;

        $targetContext = $targetRole ? "The candidate is targeting: $targetRole" : "General resume review";

        $prompt = <<<PROMPT
Review this resume and provide detailed feedback in JSON format:

{
  "overall_score": 85,
  "strengths": [
    "Specific strength 1",
    "Specific strength 2"
  ],
  "weaknesses": [
    "Specific weakness 1",
    "Specific weakness 2"
  ],
  "suggestions": [
    {
      "section": "Experience/Education/Skills/etc",
      "issue": "What's wrong",
      "recommendation": "How to fix it",
      "priority": "high/medium/low"
    }
  ],
  "missing_elements": [
    "Element 1 that should be added",
    "Element 2 that should be added"
  ],
  "keywords_to_add": [
    "Keyword 1 for ATS",
    "Keyword 2 for ATS"
  ],
  "formatting_issues": [
    "Issue 1",
    "Issue 2"
  ],
  "ats_compatibility_score": 75,
  "estimated_experience_level": "Entry/Mid/Senior/Executive"
}

$targetContext

Resume text:
$resumeText
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt, ['cache_hours' => 1]);
    }

    /**
     * Optimize resume for a specific job posting
     */
    public function optimizeForJob(string $resumeText, string $jobDescription): array
    {
        $systemPrompt = <<<SYSTEM
You are an ATS (Applicant Tracking System) optimization expert. Help candidates tailor their resumes for specific jobs.
Always respond with valid JSON format.
SYSTEM;

        $prompt = <<<PROMPT
Analyze how well this resume matches the job description and provide optimization suggestions:

{
  "match_score": 75,
  "matching_skills": ["Skill 1", "Skill 2"],
  "missing_skills": ["Skill 1", "Skill 2"],
  "keyword_alignment": {
    "present": ["Keyword 1", "Keyword 2"],
    "missing": ["Keyword 3", "Keyword 4"]
  },
  "experience_alignment": "How well experience matches (1-2 paragraphs)",
  "suggested_changes": [
    {
      "section": "Section name",
      "current": "Current text",
      "suggested": "Optimized text",
      "reason": "Why this change helps"
    }
  ],
  "bullet_point_improvements": [
    {
      "current": "Current bullet point",
      "improved": "Improved version with metrics",
      "why": "Explanation"
    }
  ],
  "recommended_additions": [
    "What to add to improve match"
  ]
}

Job Description:
$jobDescription

Current Resume:
$resumeText
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }

    /**
     * Calculate ATS (Applicant Tracking System) compatibility score
     */
    public function calculateATSScore(string $resumeText): array
    {
        $systemPrompt = "You are an ATS system analyzer. Evaluate resume compatibility with automated tracking systems.";

        $prompt = <<<PROMPT
Evaluate this resume for ATS compatibility and return JSON:

{
  "overall_score": 85,
  "factors": {
    "formatting": {"score": 90, "issues": ["Issue 1"]},
    "keywords": {"score": 80, "density": 15, "suggestions": ["Add keyword X"]},
    "structure": {"score": 95, "missing_sections": []},
    "readability": {"score": 85, "complex_sentences": 3}
  },
  "critical_issues": ["Issue that will cause rejection"],
  "warnings": ["Non-critical issues"],
  "tips": ["Quick improvement tips"]
}

Resume:
$resumeText
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }

    /**
     * Extract skills from resume with categorization
     */
    public function extractSkills(string $resumeText): array
    {
        $systemPrompt = "You are a skills extraction and categorization expert.";

        $prompt = <<<PROMPT
Extract and categorize all skills from this resume in JSON format:

{
  "technical_skills": [
    {"name": "Python", "proficiency": "Expert", "years": 5}
  ],
  "soft_skills": ["Leadership", "Communication"],
  "tools": ["Git", "Docker"],
  "frameworks": ["Laravel", "React"],
  "languages": [
    {"language": "English", "proficiency": "Native"},
    {"language": "Spanish", "proficiency": "Intermediate"}
  ],
  "certifications": ["AWS Certified"],
  "trending_skills": ["Skills that are currently in demand"],
  "skill_gaps": ["Skills that would complement the existing set"]
}

Resume:
$resumeText
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }

    /**
     * Generate professional summary based on resume
     */
    public function generateSummary(array $resumeData, ?string $targetRole = null): string
    {
        $systemPrompt = "You are a professional resume writer specializing in compelling summary statements.";

        $experience = json_encode($resumeData['experience'] ?? []);
        $skills = json_encode($resumeData['skills'] ?? []);
        $targetContext = $targetRole ? "Tailored for: $targetRole" : "";

        $prompt = <<<PROMPT
Write a compelling professional summary (3-4 sentences) for a resume based on this information:

Experience: $experience
Skills: $skills
$targetContext

Requirements:
- Start with years of experience and key role
- Highlight 2-3 major achievements or skills
- Include industry-specific keywords
- End with career goals or value proposition
- Keep it under 100 words
PROMPT;

        return $this->callAI($prompt, $systemPrompt);
    }

    /**
     * Extract text from various file formats
     */
    protected function extractTextFromFile(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        try {
            switch ($extension) {
                case 'pdf':
                    return $this->extractFromPDF($filePath);
                
                case 'doc':
                case 'docx':
                    return $this->extractFromDOCX($filePath);
                
                case 'txt':
                    return file_get_contents($filePath);
                
                default:
                    throw new \Exception("Unsupported file format: $extension");
            }
        } catch (\Exception $e) {
            \Log::error('Resume text extraction failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }

    /**
     * Extract text from PDF
     */
    protected function extractFromPDF(string $filePath): string
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($filePath);
        return $pdf->getText();
    }

    /**
     * Extract text from DOCX
     */
    protected function extractFromDOCX(string $filePath): string
    {
        // Simplified DOCX parsing - in production use a proper library
        $zip = new \ZipArchive();
        if ($zip->open($filePath) === true) {
            $content = $zip->getFromName('word/document.xml');
            $zip->close();
            
            // Remove XML tags
            $text = strip_tags($content);
            return $text;
        }
        
        return '';
    }

    /**
     * Compare resume with profile and suggest updates
     */
    public function compareWithProfile(string $resumeText, Profile $profile): array
    {
        $systemPrompt = "You are a profile consistency analyzer.";

        $profileData = [
            'headline' => $profile->headline,
            'summary' => $profile->summary,
            'experience' => $profile->experience,
            'education' => $profile->education,
            'skills' => $profile->skills,
        ];

        $profileJson = json_encode($profileData, JSON_PRETTY_PRINT);
        
        $prompt = <<<PROMPT
Compare this resume with the user's profile and identify discrepancies:

Profile: {$profileJson}

Resume: $resumeText

Return JSON:
{
  "discrepancies": [
    {"field": "experience", "profile_value": "X", "resume_value": "Y", "severity": "high/medium/low"}
  ],
  "missing_in_profile": ["Items in resume but not in profile"],
  "missing_in_resume": ["Items in profile but not in resume"],
  "recommendations": ["How to sync both"]
}
PROMPT;

        return $this->callAIForJSON($prompt, $systemPrompt);
    }
}
