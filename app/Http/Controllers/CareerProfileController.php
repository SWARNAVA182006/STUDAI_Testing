<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Services\AI\ResumeAnalyzerService;
use App\Services\AI\SkillsExtractorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CareerProfileController extends Controller
{
    protected ResumeAnalyzerService $resumeAnalyzer;

    public function __construct(ResumeAnalyzerService $resumeAnalyzer)
    {
        $this->resumeAnalyzer = $resumeAnalyzer;
    }

    /**
     * Show the career profile dashboard
     */
    public function index()
    {
        $user = auth()->user();
        $profile = $user->profile ?? new Profile(['user_id' => $user->id]);
        
        return view('profile.career.index', [
            'profile' => $profile,
            'completion' => $profile->exists ? $profile->getCompletionPercentage() : 0,
        ]);
    }

    /**
     * Show the profile builder wizard
     */
    public function builder()
    {
        $user = auth()->user();
        $profile = $user->profile ?? new Profile(['user_id' => $user->id]);
        
        return view('profile.career.builder', [
            'profile' => $profile,
            'step' => request()->get('step', 'basics'),
        ]);
    }

    /**
     * Upload and analyze resume
     */
    public function uploadResume(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resume' => 'required|file|mimes:pdf,doc,docx,txt|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = auth()->user();
            
            // Store the resume file
            $file = $request->file('resume');
            $path = $file->store('resumes/' . $user->id, 'private');
            $fullPath = Storage::disk('private')->path($path);
            
            // Analyze resume with AI
            $analysisData = $this->resumeAnalyzer
                ->forUser($user)
                ->analyzeResume($fullPath);
            
            // Get AI feedback
            $resumeText = $this->resumeAnalyzer->extractTextFromFile($fullPath);
            $feedback = $this->resumeAnalyzer->getResumeFeedback(
                $resumeText,
                $request->input('target_role', 'General')
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Resume analyzed successfully',
                'data' => $analysisData,
                'feedback' => $feedback,
                'resume_path' => $path,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Resume upload failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze resume: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Auto-fill profile from resume analysis
     */
    public function autoFillFromResume(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'analysis_data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = auth()->user();
            $data = $request->input('analysis_data');
            
            // Create or update profile
            $profile = $user->profile ?? new Profile();
            $profile->user_id = $user->id;
            
            // Map resume data to profile fields
            if (isset($data['personal_info'])) {
                $profile->headline = $data['personal_info']['professional_title'] ?? null;
            }
            
            $profile->summary = $data['summary'] ?? null;
            $profile->skills = $data['skills'] ?? [];
            $profile->experience = $data['experience'] ?? [];
            $profile->education = $data['education'] ?? [];
            $profile->certifications = $data['certifications'] ?? [];
            $profile->projects = $data['projects'] ?? [];
            
            if (isset($data['personal_info']['location'])) {
                $profile->current_location = $data['personal_info']['location'];
            }
            
            if (isset($data['personal_info']['linkedin'])) {
                $profile->linkedin_url = $data['personal_info']['linkedin'];
            }
            
            if (isset($data['personal_info']['portfolio'])) {
                $profile->portfolio_url = $data['personal_info']['portfolio'];
            }
            
            $profile->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Profile auto-filled successfully',
                'profile' => $profile,
                'completion' => $profile->getCompletionPercentage(),
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Auto-fill profile failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to auto-fill profile: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update profile section
     */
    public function updateSection(Request $request, string $section)
    {
        $user = auth()->user();
        $profile = $user->profile ?? new Profile(['user_id' => $user->id]);
        
        // Validate based on section
        $rules = $this->getValidationRules($section);
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            // Update the specific section
            switch ($section) {
                case 'basics':
                    $profile->headline = $request->input('headline');
                    $profile->summary = $request->input('summary');
                    $profile->current_location = $request->input('current_location');
                    break;
                    
                case 'experience':
                    $profile->experience = $request->input('experience', []);
                    break;
                    
                case 'education':
                    $profile->education = $request->input('education', []);
                    break;
                    
                case 'skills':
                    $profile->skills = $request->input('skills', []);
                    break;
                    
                case 'certifications':
                    $profile->certifications = $request->input('certifications', []);
                    break;
                    
                case 'projects':
                    $profile->projects = $request->input('projects', []);
                    break;
                    
                case 'preferences':
                    $profile->expected_salary_min = $request->input('expected_salary_min');
                    $profile->expected_salary_max = $request->input('expected_salary_max');
                    $profile->career_goals = $request->input('career_goals');
                    break;
                    
                case 'links':
                    $profile->linkedin_url = $request->input('linkedin_url');
                    $profile->portfolio_url = $request->input('portfolio_url');
                    $profile->github_url = $request->input('github_url');
                    break;
            }
            
            $profile->save();
            
            return response()->json([
                'success' => true,
                'message' => ucfirst($section) . ' updated successfully',
                'profile' => $profile,
                'completion' => $profile->getCompletionPercentage(),
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Profile section update failed', [
                'section' => $section,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get validation rules for each section
     */
    protected function getValidationRules(string $section): array
    {
        $rules = [
            'basics' => [
                'headline' => 'required|string|max:255',
                'summary' => 'required|string|max:1000',
                'current_location' => 'nullable|string|max:255',
            ],
            'experience' => [
                'experience' => 'required|array',
                'experience.*.title' => 'required|string',
                'experience.*.company' => 'required|string',
                'experience.*.start_date' => 'required|date',
                'experience.*.end_date' => 'nullable|date',
                'experience.*.description' => 'nullable|string',
            ],
            'education' => [
                'education' => 'required|array',
                'education.*.degree' => 'required|string',
                'education.*.institution' => 'required|string',
                'education.*.field' => 'required|string',
                'education.*.graduation_year' => 'required|integer|min:1950|max:' . (date('Y') + 10),
            ],
            'skills' => [
                'skills' => 'required|array|min:1',
                'skills.*.name' => 'required|string',
                'skills.*.proficiency' => 'nullable|in:beginner,intermediate,advanced,expert',
                'skills.*.years' => 'nullable|integer|min:0|max:50',
            ],
            'certifications' => [
                'certifications' => 'nullable|array',
                'certifications.*.name' => 'required|string',
                'certifications.*.issuer' => 'required|string',
                'certifications.*.date' => 'nullable|date',
            ],
            'projects' => [
                'projects' => 'nullable|array',
                'projects.*.name' => 'required|string',
                'projects.*.description' => 'required|string',
                'projects.*.technologies' => 'nullable|array',
                'projects.*.url' => 'nullable|url',
            ],
            'preferences' => [
                'expected_salary_min' => 'nullable|numeric|min:0',
                'expected_salary_max' => 'nullable|numeric|min:0',
                'career_goals' => 'nullable|string|max:1000',
            ],
            'links' => [
                'linkedin_url' => 'nullable|url',
                'portfolio_url' => 'nullable|url',
                'github_url' => 'nullable|url',
            ],
        ];
        
        return $rules[$section] ?? [];
    }

    /**
     * Get AI suggestions for profile improvement
     */
    public function getAISuggestions(Request $request)
    {
        try {
            $user = auth()->user();
            $profile = $user->profile;
            
            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please create your profile first',
                ], 404);
            }
            
            // Convert profile to resume text format
            $resumeText = $this->profileToResumeText($profile);
            
            // Get AI feedback
            $targetRole = $request->input('target_role', $profile->headline ?? 'General');
            $feedback = $this->resumeAnalyzer
                ->forUser($user)
                ->getResumeFeedback($resumeText, $targetRole);
            
            return response()->json([
                'success' => true,
                'suggestions' => $feedback,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('AI suggestions failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get AI suggestions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Convert profile to resume text
     */
    protected function profileToResumeText(Profile $profile): string
    {
        $text = [];
        
        if ($profile->headline) {
            $text[] = $profile->headline;
        }
        
        if ($profile->summary) {
            $text[] = "\nSummary:\n" . $profile->summary;
        }
        
        if (!empty($profile->experience)) {
            $text[] = "\nExperience:";
            foreach ($profile->experience as $exp) {
                $text[] = sprintf(
                    "%s at %s (%s - %s)\n%s",
                    $exp['title'] ?? '',
                    $exp['company'] ?? '',
                    $exp['start_date'] ?? '',
                    $exp['end_date'] ?? 'Present',
                    $exp['description'] ?? ''
                );
            }
        }
        
        if (!empty($profile->education)) {
            $text[] = "\nEducation:";
            foreach ($profile->education as $edu) {
                $text[] = sprintf(
                    "%s in %s from %s (%s)",
                    $edu['degree'] ?? '',
                    $edu['field'] ?? '',
                    $edu['institution'] ?? '',
                    $edu['graduation_year'] ?? ''
                );
            }
        }
        
        if (!empty($profile->skills)) {
            $skills = collect($profile->skills)->pluck('name')->implode(', ');
            $text[] = "\nSkills: " . $skills;
        }
        
        return implode("\n\n", $text);
    }

    /**
     * Delete profile
     */
    public function destroy()
    {
        try {
            $user = auth()->user();
            $profile = $user->profile;
            
            if ($profile) {
                $profile->delete();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Profile deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Profile deletion failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete profile: ' . $e->getMessage(),
            ], 500);
        }
    }
}
