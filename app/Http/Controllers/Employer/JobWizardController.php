<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobTemplate;
use App\Services\AI\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class JobWizardController extends Controller
{
    protected AIService $aiService;
    
    public function __construct(AIService $aiService)
    {
        $this->middleware('auth:employer');
        $this->aiService = $aiService;
    }
    
    /**
     * Start job posting wizard
     */
    public function start()
    {
        $company = auth('employer')->user()->company;
        
        // Get available templates
        $publicTemplates = JobTemplate::where('is_public', true)->get();
        $companyTemplates = JobTemplate::where('company_id', $company->id)->get();
        
        $categories = [
            'engineering' => 'Engineering & Technology',
            'marketing' => 'Marketing & Communications',
            'sales' => 'Sales & Business Development',
            'design' => 'Design & Creative',
            'product' => 'Product Management',
            'data' => 'Data & Analytics',
            'hr' => 'Human Resources',
            'finance' => 'Finance & Accounting',
            'operations' => 'Operations & Management',
            'customer_success' => 'Customer Success & Support',
        ];
        
        return view('employer.jobs.wizard.start', compact('publicTemplates', 'companyTemplates', 'categories'));
    }
    
    /**
     * Generate AI job description
     */
    public function generateDescription(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'experience_level' => 'required|in:entry,mid,senior,lead,executive',
            'employment_type' => 'required|in:full_time,part_time,contract,internship',
            'location' => 'nullable|string',
            'work_mode' => 'required|in:onsite,remote,hybrid',
            'key_responsibilities' => 'nullable|array',
            'required_skills' => 'nullable|array',
            'company_description' => 'nullable|string',
        ]);
        
        $company = auth('employer')->user()->company;
        
        try {
            $prompt = $this->buildJobDescriptionPrompt($validated, $company);
            $aiResponse = $this->aiService->generateText($prompt, null, [
                'model' => 'gpt-4',
                'temperature' => 0.7,
            ]);
            
            // Parse AI response (expecting JSON)
            $generated = json_decode($aiResponse, true);
            
            if (!$generated) {
                throw new \Exception('Invalid AI response format');
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'description' => $generated['description'] ?? '',
                    'responsibilities' => $generated['responsibilities'] ?? [],
                    'requirements' => $generated['requirements'] ?? [],
                    'required_skills' => $generated['required_skills'] ?? [],
                    'preferred_skills' => $generated['preferred_skills'] ?? [],
                    'benefits' => $generated['benefits'] ?? [],
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate job description. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get job templates
     */
    public function templates(Request $request)
    {
        $company = auth('employer')->user()->company;
        $category = $request->input('category');
        
        $query = JobTemplate::where(function ($q) use ($company) {
            $q->where('company_id', $company->id)
                ->orWhere('is_public', true);
        });
        
        if ($category) {
            $query->where('category', $category);
        }
        
        $templates = $query->orderByDesc('usage_count')->get();
        
        return response()->json([
            'success' => true,
            'templates' => $templates,
        ]);
    }
    
    /**
     * Preview job posting
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'location' => 'nullable|string',
            'work_mode' => 'required|string',
            'employment_type' => 'required|string',
            'experience_level' => 'nullable|string',
            'salary_min' => 'nullable|numeric',
            'salary_max' => 'nullable|numeric',
            'required_skills' => 'nullable|array',
            'responsibilities' => 'nullable|array',
            'requirements' => 'nullable|array',
            'benefits' => 'nullable|array',
        ]);
        
        $company = auth('employer')->user()->company;
        
        // Create preview data
        $preview = [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'company' => $company,
            'location' => $validated['location'] ?? 'Not specified',
            'work_mode' => ucfirst($validated['work_mode']),
            'employment_type' => str_replace('_', ' ', ucfirst($validated['employment_type'])),
            'experience_level' => $validated['experience_level'] ?? 'Not specified',
            'salary_range' => $this->formatSalaryRange($validated['salary_min'] ?? null, $validated['salary_max'] ?? null),
            'required_skills' => $validated['required_skills'] ?? [],
            'responsibilities' => $validated['responsibilities'] ?? [],
            'requirements' => $validated['requirements'] ?? [],
            'benefits' => $validated['benefits'] ?? [],
        ];
        
        return view('employer.jobs.wizard.preview', compact('preview'));
    }
    
    /**
     * Publish job posting
     */
    public function publish(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'location' => 'nullable|string',
            'work_mode' => 'required|in:onsite,remote,hybrid',
            'employment_type' => 'required|in:full_time,part_time,contract,internship',
            'experience_level' => 'nullable|in:entry,mid,senior,lead,executive',
            'min_experience' => 'nullable|integer|min:0',
            'preferred_experience' => 'nullable|integer|min:0',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
            'salary_currency' => 'nullable|string|max:3',
            'salary_period' => 'nullable|in:hourly,monthly,yearly',
            'required_skills' => 'nullable|array',
            'preferred_skills' => 'nullable|array',
            'responsibilities' => 'nullable|array',
            'requirements' => 'nullable|array',
            'benefits' => 'nullable|array',
            'application_method' => 'required|in:internal,email,external',
            'application_email' => 'nullable|email',
            'external_url' => 'nullable|url',
            'expires_in_days' => 'required|integer|min:7|max:90',
            'is_featured' => 'boolean',
            'is_urgent' => 'boolean',
            'publish_immediately' => 'boolean',
            'save_as_template' => 'boolean',
            'template_name' => 'nullable|required_if:save_as_template,true|string',
        ]);
        
        $employer = auth('employer')->user();
        $company = $employer->company;
        
        // Check subscription limits
        $activeJobs = Job::where('company_id', $company->id)
            ->where('status', 'published')
            ->count();
        
        if ($employer->subscription && !$employer->hasFeature('unlimited_job_postings')) {
            $limit = $employer->subscription->subscriptionPlan->job_postings_limit ?? 5;
            if ($activeJobs >= $limit) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have reached your job posting limit. Please upgrade your plan.',
                ], 403);
            }
        }
        
        // Create job
        $job = Job::create([
            'company_id' => $company->id,
            'posted_by' => $employer->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'location' => $validated['location'] ?? null,
            'work_mode' => $validated['work_mode'],
            'employment_type' => $validated['employment_type'],
            'experience_level' => $validated['experience_level'] ?? null,
            'min_experience' => $validated['min_experience'] ?? null,
            'preferred_experience' => $validated['preferred_experience'] ?? null,
            'salary_min' => $validated['salary_min'] ?? null,
            'salary_max' => $validated['salary_max'] ?? null,
            'salary_currency' => $validated['salary_currency'] ?? 'INR',
            'salary_period' => $validated['salary_period'] ?? 'yearly',
            'required_skills' => $validated['required_skills'] ?? [],
            'preferred_skills' => $validated['preferred_skills'] ?? [],
            'responsibilities' => $validated['responsibilities'] ?? [],
            'requirements' => $validated['requirements'] ?? [],
            'benefits' => $validated['benefits'] ?? [],
            'application_method' => $validated['application_method'],
            'application_email' => $validated['application_email'] ?? null,
            'external_url' => $validated['external_url'] ?? null,
            'is_featured' => $validated['is_featured'] ?? false,
            'is_urgent' => $validated['is_urgent'] ?? false,
            'status' => ($validated['publish_immediately'] ?? true) ? 'published' : 'draft',
            'published_at' => ($validated['publish_immediately'] ?? true) ? now() : null,
            'expires_at' => now()->addDays($validated['expires_in_days']),
        ]);
        
        // Save as template if requested
        if ($validated['save_as_template'] ?? false) {
            JobTemplate::create([
                'company_id' => $company->id,
                'name' => $validated['template_name'],
                'category' => $validated['category'],
                'title_template' => $validated['title'],
                'description_template' => $validated['description'],
                'requirements_template' => implode("\n", $validated['requirements'] ?? []),
                'responsibilities_template' => implode("\n", $validated['responsibilities'] ?? []),
                'default_skills' => $validated['required_skills'] ?? [],
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Job posted successfully!',
            'job_id' => $job->id,
            'redirect_url' => route('employer.jobs.show', $job),
        ]);
    }
    
    /**
     * Build AI prompt for job description
     */
    protected function buildJobDescriptionPrompt(array $data, $company): string
    {
        $prompt = "Generate a comprehensive job posting in JSON format for:\n\n";
        $prompt .= "Job Title: {$data['title']}\n";
        $prompt .= "Category: {$data['category']}\n";
        $prompt .= "Experience Level: {$data['experience_level']}\n";
        $prompt .= "Employment Type: {$data['employment_type']}\n";
        $prompt .= "Work Mode: {$data['work_mode']}\n";
        
        if (!empty($data['location'])) {
            $prompt .= "Location: {$data['location']}\n";
        }
        
        if (!empty($data['key_responsibilities'])) {
            $prompt .= "Key Focus Areas: " . implode(', ', $data['key_responsibilities']) . "\n";
        }
        
        if (!empty($data['required_skills'])) {
            $prompt .= "Required Skills: " . implode(', ', $data['required_skills']) . "\n";
        }
        
        $prompt .= "\nCompany: {$company->name}\n";
        if (!empty($data['company_description'])) {
            $prompt .= "About Company: {$data['company_description']}\n";
        }
        
        $prompt .= "\nGenerate a professional job description with:\n";
        $prompt .= "1. Compelling overview (2-3 paragraphs)\n";
        $prompt .= "2. 5-8 key responsibilities\n";
        $prompt .= "3. 5-8 requirements (qualifications/experience)\n";
        $prompt .= "4. 5-10 required technical skills\n";
        $prompt .= "5. 3-5 preferred/bonus skills\n";
        $prompt .= "6. 4-6 benefits/perks\n\n";
        $prompt .= "Return ONLY valid JSON in this exact format:\n";
        $prompt .= "{\n";
        $prompt .= '  "description": "string",'."\n";
        $prompt .= '  "responsibilities": ["string"],'."\n";
        $prompt .= '  "requirements": ["string"],'."\n";
        $prompt .= '  "required_skills": ["string"],'."\n";
        $prompt .= '  "preferred_skills": ["string"],'."\n";
        $prompt .= '  "benefits": ["string"]'."\n";
        $prompt .= "}";
        
        return $prompt;
    }
    
    /**
     * Format salary range
     */
    protected function formatSalaryRange(?float $min, ?float $max): string
    {
        if (!$min && !$max) {
            return 'Not disclosed';
        }
        
        if ($min && $max) {
            return '₹' . number_format($min) . ' - ₹' . number_format($max);
        }
        
        if ($min) {
            return '₹' . number_format($min) . '+';
        }
        
        return 'Up to ₹' . number_format($max);
    }
}
