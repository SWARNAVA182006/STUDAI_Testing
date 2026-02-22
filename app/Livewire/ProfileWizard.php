<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Profile;
use App\Services\AI\ResumeAnalyzerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProfileWizard extends Component
{
    use WithFileUploads;

    // Wizard state
    public $currentStep = 1;
    public $totalSteps = 6;
    
    // Step 1: Resume Upload
    public $resumeFile;
    public $uploadProgress = 0;
    public $analyzing = false;
    public $analysisComplete = false;
    public $analysisData = null;
    
    // Step 2: Basic Info
    public $headline = '';
    public $summary = '';
    public $current_location = '';
    
    // Step 3: Experience
    public $experience = [];
    
    // Step 4: Education
    public $education = [];
    
    // Step 5: Skills
    public $skills = [];
    public $skillSearch = '';
    
    // Step 6: Links & Preferences
    public $linkedin_url = '';
    public $portfolio_url = '';
    public $github_url = '';
    public $expected_salary_min = null;
    public $expected_salary_max = null;
    public $career_goals = '';
    
    // Additional
    public $certifications = [];
    public $projects = [];

    protected $rules = [
        // Step 2
        'headline' => 'required|string|max:255',
        'summary' => 'required|string|max:1000',
        'current_location' => 'nullable|string|max:255',
        
        // Step 3
        'experience.*.title' => 'required|string',
        'experience.*.company' => 'required|string',
        'experience.*.start_date' => 'required|date',
        'experience.*.end_date' => 'nullable|date',
        'experience.*.description' => 'nullable|string',
        
        // Step 4
        'education.*.degree' => 'required|string',
        'education.*.institution' => 'required|string',
        'education.*.field' => 'required|string',
        'education.*.graduation_year' => 'required|integer|min:1950',
        
        // Step 5
        'skills.*.name' => 'required|string',
        'skills.*.proficiency' => 'nullable|in:beginner,intermediate,advanced,expert',
        'skills.*.years' => 'nullable|integer|min:0|max:50',
        
        // Step 6
        'linkedin_url' => 'nullable|url',
        'portfolio_url' => 'nullable|url',
        'github_url' => 'nullable|url',
        'expected_salary_min' => 'nullable|numeric|min:0',
        'expected_salary_max' => 'nullable|numeric|min:0',
        'career_goals' => 'nullable|string|max:1000',
    ];

    public function mount()
    {
        $user = auth()->user();
        $profile = $user->profile;
        
        if ($profile) {
            // Load existing profile data
            $this->headline = $profile->headline ?? '';
            $this->summary = $profile->summary ?? '';
            $this->current_location = $profile->current_location ?? '';
            $this->experience = $profile->experience ?? [];
            $this->education = $profile->education ?? [];
            $this->skills = $profile->skills ?? [];
            $this->certifications = $profile->certifications ?? [];
            $this->projects = $profile->projects ?? [];
            $this->linkedin_url = $profile->linkedin_url ?? '';
            $this->portfolio_url = $profile->portfolio_url ?? '';
            $this->github_url = $profile->github_url ?? '';
            $this->expected_salary_min = $profile->expected_salary_min;
            $this->expected_salary_max = $profile->expected_salary_max;
            $this->career_goals = $profile->career_goals ?? '';
        }
    }

    /**
     * Upload and analyze resume
     */
    public function uploadResume()
    {
        $this->validate([
            'resumeFile' => 'required|file|mimes:pdf,doc,docx,txt|max:5120', // 5MB
        ]);

        try {
            $this->analyzing = true;
            $this->uploadProgress = 0;
            
            $user = auth()->user();
            
            // Store file
            $path = $this->resumeFile->store('resumes/' . $user->id, 'private');
            $fullPath = Storage::disk('private')->path($path);
            
            $this->uploadProgress = 50;
            
            // Analyze with AI
            $resumeAnalyzer = app(ResumeAnalyzerService::class);
            $this->analysisData = $resumeAnalyzer->analyzeResume($fullPath);
            
            $this->uploadProgress = 100;
            $this->analyzing = false;
            $this->analysisComplete = true;
            
            // Auto-fill from analysis
            $this->autoFillFromAnalysis();
            
            session()->flash('message', 'Resume analyzed successfully! Review and edit the information below.');
            
            // Move to next step
            $this->currentStep = 2;
            
        } catch (\Exception $e) {
            $this->analyzing = false;
            Log::error('Resume analysis failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
            
            session()->flash('error', 'Failed to analyze resume. Please try again or fill in manually.');
        }
    }

    /**
     * Auto-fill profile from resume analysis
     */
    protected function autoFillFromAnalysis()
    {
        if (!$this->analysisData) {
            return;
        }
        
        $data = $this->analysisData;
        
        // Basic info
        if (isset($data['personal_info']['professional_title'])) {
            $this->headline = $data['personal_info']['professional_title'];
        }
        
        if (isset($data['summary'])) {
            $this->summary = $data['summary'];
        }
        
        if (isset($data['personal_info']['location'])) {
            $this->current_location = $data['personal_info']['location'];
        }
        
        // Experience
        if (isset($data['experience'])) {
            $this->experience = $data['experience'];
        }
        
        // Education
        if (isset($data['education'])) {
            $this->education = $data['education'];
        }
        
        // Skills
        if (isset($data['skills'])) {
            $this->skills = $data['skills'];
        }
        
        // Certifications
        if (isset($data['certifications'])) {
            $this->certifications = $data['certifications'];
        }
        
        // Projects
        if (isset($data['projects'])) {
            $this->projects = $data['projects'];
        }
        
        // Links
        if (isset($data['personal_info']['linkedin'])) {
            $this->linkedin_url = $data['personal_info']['linkedin'];
        }
        
        if (isset($data['personal_info']['portfolio'])) {
            $this->portfolio_url = $data['personal_info']['portfolio'];
        }
        
        if (isset($data['personal_info']['github'])) {
            $this->github_url = $data['personal_info']['github'];
        }
    }

    /**
     * Add new experience entry
     */
    public function addExperience()
    {
        $this->experience[] = [
            'title' => '',
            'company' => '',
            'start_date' => '',
            'end_date' => '',
            'current' => false,
            'description' => '',
            'achievements' => [],
        ];
    }

    /**
     * Remove experience entry
     */
    public function removeExperience($index)
    {
        unset($this->experience[$index]);
        $this->experience = array_values($this->experience);
    }

    /**
     * Add new education entry
     */
    public function addEducation()
    {
        $this->education[] = [
            'degree' => '',
            'institution' => '',
            'field' => '',
            'graduation_year' => date('Y'),
            'gpa' => null,
        ];
    }

    /**
     * Remove education entry
     */
    public function removeEducation($index)
    {
        unset($this->education[$index]);
        $this->education = array_values($this->education);
    }

    /**
     * Add new skill
     */
    public function addSkill()
    {
        if (!empty($this->skillSearch)) {
            $this->skills[] = [
                'name' => $this->skillSearch,
                'proficiency' => 'intermediate',
                'years' => 1,
            ];
            $this->skillSearch = '';
        }
    }

    /**
     * Remove skill
     */
    public function removeSkill($index)
    {
        unset($this->skills[$index]);
        $this->skills = array_values($this->skills);
    }

    /**
     * Add certification
     */
    public function addCertification()
    {
        $this->certifications[] = [
            'name' => '',
            'issuer' => '',
            'date' => date('Y-m-d'),
            'expiry_date' => null,
        ];
    }

    /**
     * Remove certification
     */
    public function removeCertification($index)
    {
        unset($this->certifications[$index]);
        $this->certifications = array_values($this->certifications);
    }

    /**
     * Add project
     */
    public function addProject()
    {
        $this->projects[] = [
            'name' => '',
            'description' => '',
            'technologies' => [],
            'url' => '',
            'start_date' => '',
            'end_date' => '',
        ];
    }

    /**
     * Remove project
     */
    public function removeProject($index)
    {
        unset($this->projects[$index]);
        $this->projects = array_values($this->projects);
    }

    /**
     * Navigate to next step
     */
    public function nextStep()
    {
        $this->validateCurrentStep();
        
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    /**
     * Navigate to previous step
     */
    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    /**
     * Go to specific step
     */
    public function goToStep($step): void
    {
        if ($step >= 1 && $step <= $this->totalSteps) {
            $this->currentStep = $step;
        }
    }

    /**
     * Handle step change from step wizard component
     */
    #[On('stepChanged')]
    public function handleStepChanged(int $step): void
    {
        $this->goToStep($step);
    }

    /**
     * Validate current step
     */
    protected function validateCurrentStep()
    {
        switch ($this->currentStep) {
            case 2:
                $this->validate([
                    'headline' => 'required|string|max:255',
                    'summary' => 'required|string|max:1000',
                ]);
                break;
            case 3:
                if (!empty($this->experience)) {
                    $this->validate([
                        'experience.*.title' => 'required|string',
                        'experience.*.company' => 'required|string',
                        'experience.*.start_date' => 'required|date',
                    ]);
                }
                break;
            case 4:
                if (!empty($this->education)) {
                    $this->validate([
                        'education.*.degree' => 'required|string',
                        'education.*.institution' => 'required|string',
                        'education.*.field' => 'required|string',
                        'education.*.graduation_year' => 'required|integer|min:1950',
                    ]);
                }
                break;
            case 5:
                $this->validate([
                    'skills' => 'required|array|min:1',
                ]);
                break;
        }
    }

    /**
     * Save profile
     */
    public function saveProfile()
    {
        $this->validate();
        
        try {
            $user = auth()->user();
            $profile = $user->profile ?? new Profile();
            
            $profile->user_id = $user->id;
            $profile->headline = $this->headline;
            $profile->summary = $this->summary;
            $profile->current_location = $this->current_location;
            $profile->experience = $this->experience;
            $profile->education = $this->education;
            $profile->skills = $this->skills;
            $profile->certifications = $this->certifications;
            $profile->projects = $this->projects;
            $profile->linkedin_url = $this->linkedin_url;
            $profile->portfolio_url = $this->portfolio_url;
            $profile->github_url = $this->github_url;
            $profile->expected_salary_min = $this->expected_salary_min;
            $profile->expected_salary_max = $this->expected_salary_max;
            $profile->career_goals = $this->career_goals;
            
            $profile->save();
            
            session()->flash('message', 'Profile saved successfully!');
            
            return redirect()->route('profile.career.index');
            
        } catch (\Exception $e) {
            Log::error('Profile save failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
            
            session()->flash('error', 'Failed to save profile. Please try again.');
        }
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentageProperty()
    {
        $requiredFields = [
            'headline',
            'summary',
            'experience',
            'education',
            'skills',
        ];
        
        $completed = 0;
        foreach ($requiredFields as $field) {
            $value = $this->$field;
            if (!empty($value)) {
                $completed++;
            }
        }
        
        return round(($completed / count($requiredFields)) * 100);
    }

    public function render()
    {
        return view('livewire.profile-wizard');
    }
}
