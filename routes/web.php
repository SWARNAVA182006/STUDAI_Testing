<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CareerProfileController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentHistoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Employer\EmployerDashboardController;
use App\Http\Controllers\Employer\CompanyProfileController;
use App\Http\Controllers\Employer\JobPostingController;
use App\Http\Controllers\Employer\ApplicantTrackingController;
use App\Http\Controllers\Admin\ApplicationMonitorController;
use App\Http\Controllers\SkillAnalyzerWebController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\EnhancedAnalyticsController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\OfferLetterController;
use App\Http\Controllers\CareerCoachController;
use App\Http\Controllers\ResumeController;
use App\Livewire\ProfileWizard;
use Illuminate\Support\Facades\Route;

// Marketing Pages
Route::get('/', [MarketingController::class, 'home'])->name('home');
Route::get('/features', [MarketingController::class, 'features'])->name('features');
Route::get('/pricing', [MarketingController::class, 'pricing'])->name('pricing');
Route::get('/about', [MarketingController::class, 'about'])->name('about');
Route::get('/how-it-works', [MarketingController::class, 'howItWorks'])->name('how-it-works');
Route::get('/blog', [MarketingController::class, 'blog'])->name('blog');
Route::get('/contact', [MarketingController::class, 'contact'])->name('contact');
Route::get('/for-employers', [MarketingController::class, 'forEmployers'])->name('employers');

// Legal Pages
Route::get('/privacy-policy', [MarketingController::class, 'privacy'])->name('privacy');
Route::get('/terms-and-conditions', [MarketingController::class, 'terms'])->name('terms');
Route::get('/refund-policy', [MarketingController::class, 'refundPolicy'])->name('refund-policy');

// Newsletter
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

// Contact Form
Route::post('/contact/submit', [ContactController::class, 'submit'])->name('contact.submit');

// Payment Gateway Callbacks (PayU)
Route::post('/payment/success', [PaymentController::class, 'payuSuccess'])->name('payment.payu.success');
Route::post('/payment/failure', [PaymentController::class, 'payuFailure'])->name('payment.payu.failure');

// Payment Gateway Callbacks (Stripe)
Route::get('/payment/stripe/success', [\App\Http\Controllers\StripeWebhookController::class, 'checkoutSuccess'])->name('payment.stripe.success');

// Dashboard (Authenticated)
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// Subscriptions & Billing
Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
    // Public pricing page
    Route::get('/pricing', [SubscriptionController::class, 'pricing'])->name('pricing');
    
    // Authenticated subscription management
    Route::middleware(['auth'])->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::get('/select-plan', [SubscriptionController::class, 'selectPlan'])->name('select-plan');
        Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscribe');
        Route::post('/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/resume', [SubscriptionController::class, 'resume'])->name('resume');
    });
});

// Profile Management (Authenticated)
Route::middleware('auth')->group(function () {
    // Skill Gap Analyzer Routes
    Route::prefix('skills')->name('skills.')->group(function () {
        Route::get('/dashboard', [SkillAnalyzerWebController::class, 'dashboard'])->name('dashboard');
        Route::get('/learning-paths', [SkillAnalyzerWebController::class, 'learningPaths'])->name('learning-paths');
        Route::get('/learning-path/{id}', [SkillAnalyzerWebController::class, 'showLearningPath'])->name('learning-path.show');
        Route::get('/validation', [SkillAnalyzerWebController::class, 'validation'])->name('validation');
        Route::get('/assessments', [SkillAnalyzerWebController::class, 'assessments'])->name('assessments');
        Route::get('/assessment/{id}', [SkillAnalyzerWebController::class, 'takeAssessment'])->name('assessment.take');
        Route::get('/daily-learning', [SkillAnalyzerWebController::class, 'dailyLearning'])->name('daily-learning');
    });
    
    // Applications Tracking
    Route::get('/applications', [DashboardController::class, 'applications'])->name('dashboard.applications');
    
    // Payment History
    Route::get('/payments', [PaymentHistoryController::class, 'index'])->name('payments.index');
    Route::get('/payments/{id}', [PaymentHistoryController::class, 'show'])->name('payments.show');
    
    // Job Search & Actions
    Route::get('/jobs/search', [JobController::class, 'search'])->name('jobs.search');
    Route::get('/jobs/saved', [JobController::class, 'saved'])->name('jobs.saved');
    Route::get('/jobs/{id}', [JobController::class, 'show'])->name('jobs.show');
    
    // Job Actions API
    Route::post('/api/jobs/{id}/toggle-save', [JobController::class, 'toggleSave'])->name('api.jobs.toggle-save');
    Route::post('/api/jobs/{id}/apply', [JobController::class, 'apply'])->name('api.jobs.apply');
    Route::post('/api/ai/generate-cover-letter', [JobController::class, 'generateCoverLetter'])->name('api.ai.generate-cover-letter');
    
    // Account settings (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Career Profile Management
    Route::prefix('profile/career')->name('profile.career.')->group(function () {
        Route::get('/', [CareerProfileController::class, 'index'])->name('index');
        Route::get('/builder', ProfileWizard::class)->name('builder');
        
        // API endpoints for profile management
        Route::post('/upload-resume', [CareerProfileController::class, 'uploadResume'])->name('upload-resume');
        Route::post('/auto-fill', [CareerProfileController::class, 'autoFillFromResume'])->name('auto-fill');
        Route::patch('/section/{section}', [CareerProfileController::class, 'updateSection'])->name('update-section');
        Route::get('/suggestions', [CareerProfileController::class, 'getAISuggestions'])->name('suggestions');
    });

    // Interview Preparation & Mock Practice
    Route::prefix('interview')->name('interview.')->group(function () {
        Route::get('/', [InterviewController::class, 'index'])->name('index');
        Route::get('/create', [InterviewController::class, 'create'])->name('create');
        Route::post('/start', [InterviewController::class, 'start'])->name('start');

        Route::get('/session/{session}', [InterviewController::class, 'session'])->name('session');
        Route::post('/session/{session}/answer', [InterviewController::class, 'submitAnswer'])->name('submit-answer');
        Route::post('/session/{session}/follow-up', [InterviewController::class, 'getFollowUp'])->name('follow-up');
        Route::get('/session/{session}/complete', [InterviewController::class, 'complete'])->name('complete');

        Route::get('/common-questions', [InterviewController::class, 'commonQuestions'])->name('common-questions');
        Route::get('/star-guide', [InterviewController::class, 'starGuide'])->name('star-guide');
        Route::post('/format-star', [InterviewController::class, 'formatStar'])->name('format-star');

        Route::get('/salary-negotiation', [InterviewController::class, 'salaryNegotiation'])->name('salary-negotiation');
        Route::post('/salary-negotiation/guide', [InterviewController::class, 'getNegotiationGuide'])->name('get-negotiation-guide');

        Route::get('/tips', [InterviewController::class, 'tips'])->name('tips');
        Route::get('/coaches', [InterviewController::class, 'findCoaches'])->name('coaches');
        Route::post('/recordings', [InterviewController::class, 'saveRecording'])->name('recordings.save');
    });

    // Enhanced Analytics Dashboard Routes
    Route::prefix('analytics')->name('analytics.')->group(function () {
        // Main dashboard
        Route::get('/', [EnhancedAnalyticsController::class, 'dashboard'])->name('dashboard');
        
        // Job Market Heatmap
        Route::get('/heatmap', [EnhancedAnalyticsController::class, 'heatmapView'])->name('heatmap');
        Route::get('/api/heatmap', [EnhancedAnalyticsController::class, 'heatmap'])->name('api.heatmap');
        
        // Salary Benchmarking
        Route::get('/salary-benchmark', [EnhancedAnalyticsController::class, 'salaryBenchmarkView'])->name('salary-benchmark');
        Route::get('/api/salary-benchmark', [EnhancedAnalyticsController::class, 'salaryBenchmark'])->name('api.salary-benchmark');
        
        // Skills Demand Forecasting
        Route::get('/skills-forecast', [EnhancedAnalyticsController::class, 'skillsForecastView'])->name('skills-forecast');
        Route::get('/api/skills-forecast', [EnhancedAnalyticsController::class, 'skillsForecast'])->name('api.skills-forecast');
        
        // Career Path Visualization
        Route::get('/career-path', [EnhancedAnalyticsController::class, 'careerPathView'])->name('career-path');
        Route::get('/api/career-path', [EnhancedAnalyticsController::class, 'careerPath'])->name('api.career-path');
        
        // Application Funnel Analytics
        Route::get('/application-funnel', [EnhancedAnalyticsController::class, 'applicationFunnelView'])->name('application-funnel');
        Route::get('/api/application-funnel', [EnhancedAnalyticsController::class, 'applicationFunnel'])->name('api.application-funnel');
        
        // Time-to-Hire Metrics
        Route::get('/time-to-hire', [EnhancedAnalyticsController::class, 'timeToHireView'])->name('time-to-hire');
        Route::get('/api/time-to-hire', [EnhancedAnalyticsController::class, 'timeToHire'])->name('api.time-to-hire');
        
        // Source Attribution
        Route::get('/source-attribution', [EnhancedAnalyticsController::class, 'sourceAttributionView'])->name('source-attribution');
        Route::get('/api/source-attribution', [EnhancedAnalyticsController::class, 'sourceAttribution'])->name('api.source-attribution');
        
        // Competitor Salary Comparison
        Route::get('/competitor-salary', [EnhancedAnalyticsController::class, 'competitorSalaryView'])->name('competitor-salary');
        Route::get('/api/competitor-salary', [EnhancedAnalyticsController::class, 'competitorSalary'])->name('api.competitor-salary');
        
        // Salary Trends
        Route::get('/api/salary-trends', [EnhancedAnalyticsController::class, 'salaryTrends'])->name('api.salary-trends');
    });

    // Email Template Library Routes
    Route::prefix('email-templates')->name('email-templates.')->group(function () {
        Route::get('/', [EmailTemplateController::class, 'index'])->name('index');
        Route::get('/create', [EmailTemplateController::class, 'create'])->name('create');
        Route::post('/', [EmailTemplateController::class, 'store'])->name('store');
        Route::get('/variables', [EmailTemplateController::class, 'variables'])->name('variables');
        Route::get('/analytics', [EmailTemplateController::class, 'userAnalytics'])->name('user-analytics');
        Route::get('/category/{categoryId}', [EmailTemplateController::class, 'byCategory'])->name('by-category');
        Route::get('/{id}', [EmailTemplateController::class, 'show'])->name('show');
        Route::get('/{id}/data', [EmailTemplateController::class, 'getData'])->name('data');
        Route::get('/{id}/edit', [EmailTemplateController::class, 'edit'])->name('edit');
        Route::put('/{id}', [EmailTemplateController::class, 'update'])->name('update');
        Route::delete('/{id}', [EmailTemplateController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/duplicate', [EmailTemplateController::class, 'duplicate'])->name('duplicate');
        Route::post('/{id}/preview', [EmailTemplateController::class, 'preview'])->name('preview');
        Route::post('/{id}/ai-customize', [EmailTemplateController::class, 'aiCustomize'])->name('ai-customize');
        Route::post('/{id}/accept-customization', [EmailTemplateController::class, 'acceptCustomization'])->name('accept-customization');
        Route::post('/{id}/send', [EmailTemplateController::class, 'send'])->name('send');
        Route::get('/{id}/analytics', [EmailTemplateController::class, 'analytics'])->name('analytics');
    });

    // AI Career Coach Routes
    Route::prefix('career-coach')->name('career-coach.')->group(function () {
        Route::get('/', [CareerCoachController::class, 'index'])->name('index');
        Route::post('/session', [CareerCoachController::class, 'newSession'])->name('session.create');
        Route::get('/session/{session}', [CareerCoachController::class, 'session'])->name('session.show');
        Route::get('/goals', [CareerCoachController::class, 'goals'])->name('goals');
        Route::post('/goals', [CareerCoachController::class, 'createGoal'])->name('goals.create');
        Route::patch('/goals/{goal}', [CareerCoachController::class, 'updateGoal'])->name('goals.update');
        Route::delete('/goals/{goal}', [CareerCoachController::class, 'deleteGoal'])->name('goals.delete');
        Route::get('/preferences', [CareerCoachController::class, 'preferences'])->name('preferences');
        Route::post('/preferences', [CareerCoachController::class, 'updatePreferences'])->name('preferences.update');
        Route::get('/history', [CareerCoachController::class, 'history'])->name('history');
        Route::get('/checkin', [CareerCoachController::class, 'checkin'])->name('checkin');
        Route::post('/checkin', [CareerCoachController::class, 'processCheckin'])->name('checkin.process');
        Route::get('/suggestions', [CareerCoachController::class, 'suggestions'])->name('suggestions');
    });

    // Resume Builder Routes
    Route::prefix('resume')->name('resume.')->group(function () {
        Route::get('/', [ResumeController::class, 'index'])->name('index');
        Route::get('/create', [ResumeController::class, 'create'])->name('create');
        Route::post('/', [ResumeController::class, 'store'])->name('store');
        Route::get('/{resume}/edit', [ResumeController::class, 'edit'])->name('edit');
        Route::put('/{resume}', [ResumeController::class, 'update'])->name('update');
        Route::delete('/{resume}', [ResumeController::class, 'destroy'])->name('destroy');
        Route::get('/{resume}/preview', [ResumeController::class, 'preview'])->name('preview');
        Route::get('/{resume}/export/pdf', [ResumeController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/{resume}/export/docx', [ResumeController::class, 'exportDocx'])->name('export.docx');
        Route::post('/{resume}/duplicate', [ResumeController::class, 'duplicate'])->name('duplicate');
        Route::post('/{resume}/set-default', [ResumeController::class, 'setDefault'])->name('set-default');
        Route::post('/{resume}/toggle-public', [ResumeController::class, 'togglePublic'])->name('toggle-public');
        
        // AI-Powered Features
        Route::post('/{resume}/ai/generate-summary', [ResumeController::class, 'generateSummary'])->name('ai.generate-summary');
        Route::post('/{resume}/ai/extract-skills', [ResumeController::class, 'extractSkills'])->name('ai.extract-skills');
        Route::post('/{resume}/ai/optimize-for-job', [ResumeController::class, 'optimizeForJob'])->name('ai.optimize-for-job');
        Route::post('/{resume}/ai/analyze-ats', [ResumeController::class, 'analyzeATS'])->name('ai.analyze-ats');
        
        // Suggestions
        Route::post('/{resume}/suggestions/{suggestionId}/accept', [ResumeController::class, 'acceptSuggestion'])->name('suggestions.accept');
        Route::post('/{resume}/suggestions/{suggestionId}/reject', [ResumeController::class, 'rejectSuggestion'])->name('suggestions.reject');
    });
});

// Public Skill Certificate View (No Auth Required)
Route::get('/skills/certificate/{hash}', [SkillAnalyzerWebController::class, 'showCertificate'])->name('skills.certificate.public');

// Public Resume View (No Auth Required)
Route::get('/r/{shareToken}', [ResumeController::class, 'publicView'])->name('resume.public');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // Autonomous Agent Routes (Job Seekers)
    Route::prefix('agent')->name('agent.')->middleware(['verified', 'subscription'])->group(function () {
        Route::get('/dashboard', function() {
            $user = Auth::user();
            $config = $user->agentConfiguration;
            $configured = $config !== null && $config->target_roles !== null && count($config->target_roles) > 0;
            
            // Get stats if configured
            $stats = null;
            $recentApplications = collect();
            $upcomingTasks = collect();
            
            if ($configured && $config) {
                $stats = [
                    'applications_today' => $user->applications()->whereDate('created_at', today())->count(),
                    'applications_this_week' => $user->applications()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    'applications_this_month' => $config->applications_this_month ?? 0,
                    'interviews_scheduled' => $user->applications()->where('status', 'interview')->count(),
                    'response_rate' => $user->applications()->count() > 0 
                        ? round(($user->applications()->whereNotIn('status', ['pending', 'applied'])->count() / $user->applications()->count()) * 100) 
                        : 0,
                ];
                
                $recentApplications = $user->applications()
                    ->with('job.company')
                    ->latest()
                    ->take(5)
                    ->get();
            }
            
            return view('agent.dashboard', compact('configured', 'config', 'stats', 'recentApplications', 'upcomingTasks'));
        })->name('dashboard');
        
        Route::get('/configure', function() {
            $user = Auth::user();
            $config = $user->agentConfiguration;
            return view('agent.configure', compact('config'));
        })->name('configure');
        
        Route::post('/configure', function(\Illuminate\Http\Request $request) {
            $user = Auth::user();
            
            // Validate the form data
            $validated = $request->validate([
                'job_search_criteria' => 'required|array',
                'job_search_criteria.keywords' => 'required|array|min:1',
                'job_search_criteria.locations' => 'nullable|array',
                'job_search_criteria.job_types' => 'nullable|array',
                'job_search_criteria.experience_levels' => 'nullable|array',
                'job_search_criteria.min_salary' => 'nullable|integer|min:0',
                'job_search_criteria.max_salary' => 'nullable|integer|min:0',
                'job_search_criteria.remote_preference' => 'nullable|string',
                'preferences' => 'nullable|array',
                'preferences.match_threshold' => 'nullable|integer|min:50|max:95',
                'preferences.apply_to_external_jobs' => 'nullable',
                'preferences.auto_customize_resume' => 'nullable',
                'preferences.generate_cover_letter' => 'nullable',
                'daily_application_limit' => 'nullable|integer|min:1|max:50',
                'require_approval' => 'nullable',
                'auto_follow_up' => 'nullable',
                'follow_up_days' => 'nullable|integer|min:1|max:30',
                'active_hours' => 'nullable|array',
                'active_hours.start' => 'nullable|integer|min:0|max:23',
                'active_hours.end' => 'nullable|integer|min:0|max:23',
                'active_hours.days' => 'nullable|array',
                'enable_learning' => 'nullable',
                'send_digest' => 'nullable',
            ]);
            
            $jobSearchCriteria = $validated['job_search_criteria'] ?? [];
            $preferences = $validated['preferences'] ?? [];
            $activeHours = $validated['active_hours'] ?? [];
            
            // Create or update configuration
            $config = \App\Models\AgentConfiguration::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'target_roles' => $jobSearchCriteria['keywords'] ?? [],
                    'preferred_locations' => $jobSearchCriteria['locations'] ?? [],
                    'employment_types' => $jobSearchCriteria['job_types'] ?? ['full_time'],
                    'min_experience_years' => null,
                    'max_experience_years' => null,
                    'min_salary' => $jobSearchCriteria['min_salary'] ?? null,
                    'max_salary' => $jobSearchCriteria['max_salary'] ?? null,
                    'work_arrangements' => [$jobSearchCriteria['remote_preference'] ?? 'no_preference'],
                    'match_threshold_percentage' => $preferences['match_threshold'] ?? 70,
                    'daily_application_limit' => $validated['daily_application_limit'] ?? 10,
                    'auto_follow_up' => isset($validated['auto_follow_up']),
                    'follow_up_days' => $validated['follow_up_days'] ?? 7,
                    'enable_learning' => isset($validated['enable_learning']),
                    'active_hours' => $activeHours,
                    'active_days' => $activeHours['days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                    'is_active' => false,
                ]
            );
            
            return redirect()->route('agent.dashboard')
                ->with('success', 'Agent configuration saved successfully! You can now activate your agent.');
        })->name('configure.store');
        
        Route::get('/applications', function() {
            return view('agent.applications');
        })->name('applications');
        
        Route::get('/metrics', function() {
            return view('agent.metrics');
        })->name('metrics');
        
        // Agent action routes (proxy to API controller)
        Route::post('/activate', [\App\Http\Controllers\API\AgentController::class, 'activate'])->name('activate');
        Route::post('/pause', [\App\Http\Controllers\API\AgentController::class, 'pause'])->name('pause');
        Route::post('/resume', [\App\Http\Controllers\API\AgentController::class, 'resume'])->name('resume');
        Route::post('/deactivate', [\App\Http\Controllers\API\AgentController::class, 'deactivate'])->name('deactivate');
        Route::get('/learning', [\App\Http\Controllers\API\AgentController::class, 'learning'])->name('learning');
    });
});

// Employer Dashboard & Features (Authenticated Employers)
Route::middleware(['auth', 'employer'])->prefix('employer')->name('employer.')->group(function () {
    // Dashboard & Analytics
    Route::get('/', [EmployerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/analytics', [EmployerDashboardController::class, 'analytics'])->name('analytics');
    
    // Job Posting Management
    Route::resource('jobs', JobPostingController::class)->except(['index', 'show']);
    Route::get('/jobs', [JobPostingController::class, 'index'])->name('jobs.index');
    Route::get('/jobs/{id}', [JobPostingController::class, 'show'])->name('jobs.show');
    Route::patch('/jobs/{id}/close', [JobPostingController::class, 'close'])->name('jobs.close');
    Route::patch('/jobs/{id}/reopen', [JobPostingController::class, 'reopen'])->name('jobs.reopen');
    Route::post('/jobs/{id}/duplicate', [JobPostingController::class, 'duplicate'])->name('jobs.duplicate');
    
    // Applicant Tracking System
    Route::get('/applicants', [ApplicantTrackingController::class, 'index'])->name('applicants.index');
    Route::get('/applicants/kanban', [ApplicantTrackingController::class, 'kanban'])->name('applicants.kanban');
    Route::get('/applicants/{id}', [ApplicantTrackingController::class, 'show'])->name('applicants.show');
    Route::patch('/applicants/{id}/status', [ApplicantTrackingController::class, 'updateStatus'])->name('applicants.updateStatus');
    Route::patch('/applicants/bulk-status', [ApplicantTrackingController::class, 'bulkUpdateStatus'])->name('applicants.bulkStatus');
    Route::post('/applicants/{id}/note', [ApplicantTrackingController::class, 'addNote'])->name('applicants.addNote');
    Route::post('/applicants/export', [ApplicantTrackingController::class, 'export'])->name('applicants.export');
    
    // Company Profile Management
    Route::get('/profile', [CompanyProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [CompanyProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [CompanyProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/logo', [CompanyProfileController::class, 'updateLogo'])->name('profile.updateLogo');
    Route::delete('/profile/logo', [CompanyProfileController::class, 'deleteLogo'])->name('profile.deleteLogo');
    
    // S.C.O.U.T. AI - Employer Intelligence Suite
    Route::prefix('scout')->name('scout.')->group(function () {
        Route::get('/', fn() => view('scout.dna-dashboard'))->name('dashboard');
        Route::get('/dna', fn() => view('scout.dna-dashboard'))->name('dna');
        Route::get('/analyze-culture', fn() => view('scout.analyze-culture'))->name('analyze-culture');
        Route::get('/hiring-insights', fn() => view('scout.hiring-insights'))->name('hiring-insights');
        Route::get('/candidate-matching', fn() => view('scout.candidate-matching'))->name('candidate-matching');
        Route::get('/team-compatibility', fn() => view('scout.team-compatibility'))->name('team-compatibility');
        Route::get('/resume-analysis', fn() => view('scout.resume-analysis'))->name('resume-analysis');
        Route::get('/shortlisting', fn() => view('scout.automated-shortlisting'))->name('shortlisting');
        Route::get('/assessment', fn() => view('scout.adaptive-assessment'))->name('assessment');
        Route::get('/behavioral', fn() => view('scout.behavioral-intelligence'))->name('behavioral');
        Route::get('/bias-elimination', fn() => view('scout.bias-elimination'))->name('bias-elimination');
        Route::get('/predictive', fn() => view('scout.predictive-analytics'))->name('predictive');
        Route::get('/learning', fn() => view('scout.continuous-learning'))->name('learning');
    });
});

// Market Intelligence Routes (Authenticated Job Seekers)
Route::middleware(['auth', 'verified'])->prefix('market')->name('market.')->group(function () {
    Route::get('/overview', fn() => view('market.overview'))->name('overview');
    Route::get('/positioning', fn() => view('market.positioning'))->name('positioning');
    Route::get('/salary-intelligence', fn() => view('market.salary-intelligence'))->name('salary-intelligence');
    Route::get('/skill-trends', fn() => view('market.skill-trends'))->name('skill-trends');
});

// AI Negotiation Strategist Routes (Authenticated Job Seekers)
Route::middleware(['auth', 'verified'])->prefix('negotiation')->name('negotiation.')->group(function () {
    // Dashboard - Main negotiation hub with strategy overview
    Route::get('/dashboard', function() {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $activeStrategies = $user->strategies()->count();
        $totalSessions = $user->negotiationSessions()->count();
        
        // Calculate average salary gain from completed strategies
        $completedStrategies = $user->strategies()
            ->whereNotNull('actual_outcome')
            ->get();
        
        $avgGainPercent = $completedStrategies->count() > 0 
            ? round($completedStrategies->avg('potential_gain_percentage'), 1)
            : 0;
        
        // Calculate success rate (strategies where actual >= optimal ask)
        $successfulCount = $completedStrategies->filter(function($strategy) {
            return $strategy->actual_outcome >= $strategy->optimal_ask;
        })->count();
        
        $successRate = $completedStrategies->count() > 0
            ? round(($successfulCount / $completedStrategies->count()) * 100)
            : 0;
        
        // Get active coaching sessions
        $activeSessions = $user->negotiationSessions()
            ->whereNull('outcome')
            ->count();
        
        // Get recent strategies with relationships
        $strategies = $user->strategies()
            ->with(['scenarios', 'scripts', 'sessions'])
            ->latest()
            ->paginate(10);
        
        return view('negotiation.dashboard', compact(
            'activeStrategies',
            'totalSessions',
            'avgGainPercent',
            'successRate',
            'activeSessions',
            'strategies'
        ));
    })->name('dashboard');
    
    // Strategy Detail - Comprehensive strategy analysis with visualizations
    Route::get('/strategy/{id}', function($id) {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $strategy = $user->strategies()->findOrFail($id);
        
        // Calculate readiness score based on completed components
        $readinessScore = 0;
        $readinessFactors = [
            [
                'name' => 'Market Research',
                'points' => $strategy->market_data ? 20 : 0,
                'max_points' => 20,
                'status' => $strategy->market_data ? 'complete' : 'pending'
            ],
            [
                'name' => 'Negotiation Leverage',
                'points' => $strategy->leverage_points ? 20 : 0,
                'max_points' => 20,
                'status' => $strategy->leverage_points ? 'complete' : 'pending'
            ],
            [
                'name' => 'Scenarios Prepared',
                'points' => $strategy->scenarios->count() >= 3 ? 20 : ($strategy->scenarios->count() * 7),
                'max_points' => 20,
                'status' => $strategy->scenarios->count() >= 3 ? 'complete' : ($strategy->scenarios->count() > 0 ? 'partial' : 'pending')
            ],
            [
                'name' => 'Scripts Ready',
                'points' => $strategy->scripts->count() >= 3 ? 20 : ($strategy->scripts->count() * 7),
                'max_points' => 20,
                'status' => $strategy->scripts->count() >= 3 ? 'complete' : ($strategy->scripts->count() > 0 ? 'partial' : 'pending')
            ],
            [
                'name' => 'Company Intelligence',
                'points' => $strategy->company_culture_analysis ? 20 : 0,
                'max_points' => 20,
                'status' => $strategy->company_culture_analysis ? 'complete' : 'pending'
            ]
        ];
        
        $readinessScore = collect($readinessFactors)->sum('points');
        
        // Build leverage analysis for radar chart
        $leverageAnalysis = [
            'market_position' => min(100, ($strategy->market_position_percentile ?? 50) * 1.5),
            'experience' => min(100, ($strategy->experience_years ?? 0) * 5),
            'skills' => min(100, (count($strategy->skills ?? []) * 10)),
            'alternatives' => $strategy->has_other_offers ? 80 : ($strategy->is_currently_employed ? 50 : 20)
        ];
        
        return view('negotiation.strategy', compact('strategy', 'readinessScore', 'readinessFactors', 'leverageAnalysis'));
    })->name('strategy');
    
    // Scenarios - Scenario comparison and risk/reward analysis
    Route::get('/scenarios/{id}', function($id) {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $strategy = $user->strategies()->findOrFail($id);
        $scenarios = $strategy->scenarios()->orderBy('risk_level', 'asc')->get();
        
        return view('negotiation.scenarios', compact('strategy', 'scenarios'));
    })->name('scenarios');
    
    // Scripts - Script library with personalization tools
    Route::get('/scripts/{id}', function($id) {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $strategy = $user->strategies()->findOrFail($id);
        $scripts = $strategy->scripts()->orderBy('stage', 'asc')->get();
        
        return view('negotiation.scripts', compact('strategy', 'scripts'));
    })->name('scripts');
    
    // Coaching - Real-time negotiation coaching session
    Route::get('/coaching/{sessionId}', function($sessionId) {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $session = $user->negotiationSessions()->findOrFail($sessionId);
        $strategy = $session->strategy;
        $messages = $session->messages()->orderBy('created_at', 'asc')->get();
        
        return view('negotiation.coaching', compact('session', 'strategy', 'messages'));
    })->name('coaching');

    // Active Coaching Sessions - View all active negotiation sessions
    Route::get('/coaching-sessions', function() {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $activeSessions = $user->negotiationSessions()
            ->where('status', 'active')
            ->with('strategy')
            ->latest()
            ->paginate(10);
        
        return view('negotiation.sessions', compact('activeSessions'));
    })->name('coaching.active');
    
    // Tactics Library - Browse negotiation tactics and frameworks
    Route::get('/tactics', function() {
        $tactics = \App\Models\NegotiationTactic::orderBy('category')->orderBy('name')->get()->groupBy('category');
        
        return view('negotiation.tactics', compact('tactics'));
    })->name('tactics');
});

// Company Reviews & Ratings Routes (Public & Authenticated)
Route::prefix('companies')->name('companies.')->group(function () {
    // Public company pages
    Route::get('/', [\App\Http\Controllers\CompanyReviewController::class, 'index'])->name('index');
    Route::get('/{company:slug}', [\App\Http\Controllers\CompanyReviewController::class, 'show'])->name('show');
    Route::get('/{company:slug}/reviews', [\App\Http\Controllers\CompanyReviewController::class, 'reviews'])->name('reviews');
    Route::get('/{company:slug}/salaries', [\App\Http\Controllers\CompanyReviewController::class, 'salaries'])->name('salaries');
    Route::get('/{company:slug}/interviews', [\App\Http\Controllers\CompanyReviewController::class, 'interviews'])->name('interviews');
    Route::get('/{company:slug}/jobs', [\App\Http\Controllers\CompanyReviewController::class, 'jobs'])->name('jobs');
    
    // Authenticated routes for submitting reviews
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/{company:slug}/reviews/create', [\App\Http\Controllers\CompanyReviewController::class, 'createReview'])->name('reviews.create');
        Route::get('/{company:slug}/salaries/create', [\App\Http\Controllers\CompanyReviewController::class, 'createSalary'])->name('salaries.create');
        Route::get('/{company:slug}/interviews/create', [\App\Http\Controllers\CompanyReviewController::class, 'createInterview'])->name('interviews.create');
        
        // Toggle follow/unfollow company
        Route::post('/{company:slug}/follow', [\App\Http\Controllers\CompanyReviewController::class, 'toggleFollow'])->name('follow');
    });
});

// Video Interview Routes (Authenticated Job Seekers)
Route::middleware(['auth', 'verified'])->prefix('video-interview')->name('video-interview.')->group(function () {
    // Session Management
    Route::get('/', \App\Livewire\VideoInterview\SessionList::class)->name('sessions');
    Route::get('/create', \App\Livewire\VideoInterview\CreateMockInterview::class)->name('create');
    Route::get('/record/{session}', \App\Livewire\VideoInterview\VideoRecorder::class)->name('record');
    Route::get('/results/{session}', \App\Livewire\VideoInterview\SessionResults::class)->name('results');
    
    // API Endpoints
    Route::get('/api/session/{session}/upload-url', [\App\Http\Controllers\VideoInterviewController::class, 'getUploadUrl'])->name('api.upload-url');
    Route::post('/api/session/{session}/upload', [\App\Http\Controllers\VideoInterviewController::class, 'uploadVideo'])->name('api.upload');
    Route::get('/api/session/{session}/analysis', [\App\Http\Controllers\VideoInterviewController::class, 'getAnalysis'])->name('api.analysis');
    Route::get('/api/recording/{recording}/playback-url', [\App\Http\Controllers\VideoInterviewController::class, 'getPlaybackUrl'])->name('api.playback-url');
    Route::post('/api/recording/{recording}/client-analysis', [\App\Http\Controllers\VideoInterviewController::class, 'submitClientAnalysis'])->name('api.client-analysis');
    
    // Invitation handling
    Route::get('/invitation/{invitation:token}', function(\App\Models\VideoInterviewInvitation $invitation) {
        return view('video-interview.invitation', compact('invitation'));
    })->name('invitation');
    Route::post('/invitation/{invitation}/accept', [\App\Http\Controllers\VideoInterviewController::class, 'acceptInvitation'])->name('invitation.accept');
    Route::post('/invitation/{invitation}/decline', [\App\Http\Controllers\VideoInterviewController::class, 'declineInvitation'])->name('invitation.decline');
});

// Mobile PWA Routes
Route::middleware(['auth'])->prefix('mobile')->name('mobile.')->group(function () {
    // Swipe Job Browser (Tinder-style)
    Route::get('/swipe', \App\Livewire\Mobile\SwipeJobBrowser::class)->name('swipe');
    
    // Saved Jobs (Offline-capable)
    Route::get('/saved', function () {
        return view('mobile.saved-jobs');
    })->name('saved');
    
    // Quick Apply Modal (included as component)
    
    // Push Notification Management
    Route::post('/push/subscribe', function (\Illuminate\Http\Request $request) {
        $service = app(\App\Services\PushNotificationService::class);
        $subscription = $service->subscribe(auth()->user(), $request->all());
        return response()->json(['success' => true, 'id' => $subscription->id]);
    })->name('push.subscribe');
    
    Route::post('/push/unsubscribe', function (\Illuminate\Http\Request $request) {
        $service = app(\App\Services\PushNotificationService::class);
        $service->unsubscribe(auth()->user(), $request->input('endpoint'));
        return response()->json(['success' => true]);
    })->name('push.unsubscribe');
    
    // Offline Data Sync
    Route::post('/sync/jobs', function (\Illuminate\Http\Request $request) {
        $user = auth()->user();
        $jobs = $request->input('jobs', []);
        
        foreach ($jobs as $job) {
            if ($job['action'] === 'save') {
                $user->savedJobs()->syncWithoutDetaching([$job['id']]);
            }
        }
        
        return response()->json(['success' => true, 'synced' => count($jobs)]);
    })->name('sync.jobs');
    
    Route::post('/sync/applications', function (\Illuminate\Http\Request $request) {
        // Handle offline application syncing
        return response()->json(['success' => true]);
    })->name('sync.applications');
});

// Mobile API for offline-first features
Route::middleware(['auth'])->prefix('api/mobile')->name('api.mobile.')->group(function () {
    // Get user's saved jobs for offline caching
    Route::get('/saved-jobs', function () {
        $jobs = auth()->user()->savedJobs()
            ->with('company:id,name,logo')
            ->select(['id', 'title', 'company_id', 'location', 'salary_min', 'salary_max', 'job_type', 'is_remote', 'created_at'])
            ->get();
        return response()->json($jobs);
    })->name('saved-jobs');
    
    // Get user's primary resume for offline quick apply
    Route::get('/primary-resume', function () {
        $resume = auth()->user()->resumes()->where('is_primary', true)->first();
        return response()->json($resume);
    })->name('primary-resume');
    
    // Get user profile data for offline access
    Route::get('/profile-data', function () {
        $user = auth()->user();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar_url,
            'profile_completion' => $user->profile_completion ?? 0,
        ]);
    })->name('profile-data');
    
    // Get VAPID public key for push subscription
    Route::get('/vapid-key', function () {
        return response()->json([
            'publicKey' => config('webpush.vapid.public_key'),
        ]);
    })->name('vapid-key');
});

// Professional Networking Routes (Candidate/Job Seeker)
Route::middleware(['auth', 'verified'])->prefix('network')->name('network.')->group(function () {
    // Activity Feed
    Route::get('/', function () {
        return view('network.feed');
    })->name('feed');
    
    // Connections
    Route::get('/connections', function () {
        return view('network.connections');
    })->name('connections');
    
    // Messaging
    Route::get('/messages', function () {
        return view('network.messages');
    })->name('messages');
    
    Route::get('/messages/{conversation}', function ($conversation) {
        $conversationModel = \App\Models\NetworkConversation::findOrFail($conversation);
        return view('network.messages', ['selectedConversation' => $conversationModel]);
    })->name('messages.show');
    
    // Groups
    Route::get('/groups', function () {
        return view('network.groups');
    })->name('groups');
    
    Route::get('/groups/{group}', function ($group) {
        $groupModel = \App\Models\Group::findOrFail($group);
        return view('network.group-detail', ['group' => $groupModel]);
    })->name('groups.show');
    
    // Events
    Route::get('/events', function () {
        return view('network.events');
    })->name('events');
    
    Route::get('/events/{event:slug}', function ($event) {
        $eventModel = \App\Models\NetworkEvent::where('slug', $event)->firstOrFail();
        return view('network.event-detail', ['event' => $eventModel]);
    })->name('events.show');
    
    // Mentorship
    Route::get('/mentorship', function () {
        return view('network.mentorship');
    })->name('mentorship');
    
    // Profile View (public-ish, for other users to view)
    Route::get('/profile/{user}', function ($user) {
        $userModel = \App\Models\User::findOrFail($user);
        return view('network.profile', ['user' => $userModel]);
    })->name('profile.show');
});

// Calendar Integration Routes (Job Seekers & Employers)
Route::middleware(['auth', 'verified'])->prefix('calendar')->name('calendar.')->group(function () {
    // Calendar Dashboard
    Route::get('/', [\App\Http\Controllers\CalendarController::class, 'dashboard'])->name('dashboard');
    
    // Calendar Connections
    Route::get('/connect/{provider}', [\App\Http\Controllers\CalendarController::class, 'connect'])->name('connect')
        ->whereIn('provider', ['google', 'microsoft', 'apple']);
    Route::get('/callback/{provider}', [\App\Http\Controllers\CalendarController::class, 'callback'])->name('callback')
        ->whereIn('provider', ['google', 'microsoft', 'apple']);
    Route::delete('/disconnect/{connection}', [\App\Http\Controllers\CalendarController::class, 'disconnect'])->name('disconnect');
    
    // Events Management
    Route::get('/events', [\App\Http\Controllers\CalendarController::class, 'events'])->name('events');
    Route::post('/events', [\App\Http\Controllers\CalendarController::class, 'createEvent'])->name('events.create');
    Route::put('/events/{event}', [\App\Http\Controllers\CalendarController::class, 'updateEvent'])->name('events.update');
    Route::delete('/events/{event}', [\App\Http\Controllers\CalendarController::class, 'deleteEvent'])->name('events.delete');
    
    // Availability Management
    Route::get('/availability', [\App\Http\Controllers\CalendarController::class, 'availability'])->name('availability');
    Route::post('/availability', [\App\Http\Controllers\CalendarController::class, 'updateAvailability'])->name('availability.update');
    
    // Scheduling Links (Calendly-style)
    Route::get('/scheduling-links', [\App\Http\Controllers\CalendarController::class, 'schedulingLinks'])->name('scheduling-links');
    Route::post('/scheduling-links', [\App\Http\Controllers\CalendarController::class, 'createSchedulingLink'])->name('scheduling-links.create');
    Route::put('/scheduling-links/{link}', [\App\Http\Controllers\CalendarController::class, 'updateSchedulingLink'])->name('scheduling-links.update');
    Route::delete('/scheduling-links/{link}', [\App\Http\Controllers\CalendarController::class, 'deleteSchedulingLink'])->name('scheduling-links.delete');
});

// Public Scheduling Routes (No Auth Required - for booking meetings)
Route::prefix('schedule')->name('schedule.')->group(function () {
    // View scheduling link and available times
    Route::get('/{link:slug}', [\App\Http\Controllers\SchedulingController::class, 'show'])->name('show');
    Route::get('/{link:slug}/times', [\App\Http\Controllers\SchedulingController::class, 'getAvailableTimes'])->name('times');
    Route::post('/{link:slug}/book', [\App\Http\Controllers\SchedulingController::class, 'book'])->name('book');
    Route::get('/confirmation/{event}', [\App\Http\Controllers\SchedulingController::class, 'confirmation'])->name('confirmation');
});

// ATS Integration Routes (Employers)
Route::middleware(['auth', 'employer'])->prefix('employer/ats')->name('ats.')->group(function () {
    // Dashboard & Connection Management
    Route::get('/', [\App\Http\Controllers\AtsController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\AtsController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\AtsController::class, 'store'])->name('store');
    Route::get('/{connection}', [\App\Http\Controllers\AtsController::class, 'show'])->name('show');
    Route::put('/{connection}', [\App\Http\Controllers\AtsController::class, 'update'])->name('update');
    Route::delete('/{connection}', [\App\Http\Controllers\AtsController::class, 'destroy'])->name('destroy');
    
    // Sync & Testing
    Route::post('/{connection}/sync', [\App\Http\Controllers\AtsController::class, 'sync'])->name('sync');
    Route::get('/{connection}/test', [\App\Http\Controllers\AtsController::class, 'testConnection'])->name('test');
    
    // Data Views
    Route::get('/{connection}/logs', [\App\Http\Controllers\AtsController::class, 'syncLogs'])->name('logs');
    Route::get('/{connection}/candidates', [\App\Http\Controllers\AtsController::class, 'candidates'])->name('candidates');
    Route::get('/{connection}/jobs', [\App\Http\Controllers\AtsController::class, 'jobs'])->name('jobs');
});

// ATS OAuth Callback (No Auth Middleware - callback from ATS)
Route::get('/ats/callback/{provider}', [\App\Http\Controllers\AtsController::class, 'callback'])->name('ats.callback');

// ATS Webhook Endpoints (No Auth - authenticated via webhook signature)
Route::post('/webhooks/ats/{provider}/{connectionId}', [\App\Http\Controllers\AtsController::class, 'webhook'])->name('ats.webhook');

// Offer Letter Routes (Employers)
Route::middleware(['auth', 'employer'])->prefix('employer/offers')->name('offer-letters.')->group(function () {
    // Offer Management
    Route::get('/', [OfferLetterController::class, 'index'])->name('index');
    Route::get('/create', [OfferLetterController::class, 'create'])->name('create');
    Route::post('/', [OfferLetterController::class, 'store'])->name('store');
    Route::get('/statistics', [OfferLetterController::class, 'statistics'])->name('statistics');
    Route::get('/{offerLetter}', [OfferLetterController::class, 'show'])->name('show');
    Route::get('/{offerLetter}/edit', [OfferLetterController::class, 'edit'])->name('edit');
    Route::put('/{offerLetter}', [OfferLetterController::class, 'update'])->name('update');
    
    // Offer Actions
    Route::post('/{offerLetter}/send', [OfferLetterController::class, 'send'])->name('send');
    Route::get('/{offerLetter}/download', [OfferLetterController::class, 'download'])->name('download');
    Route::post('/{offerLetter}/withdraw', [OfferLetterController::class, 'withdraw'])->name('withdraw');
    Route::get('/{offerLetter}/activities', [OfferLetterController::class, 'activities'])->name('activities');
    
    // Digital Signature
    Route::post('/{offerLetter}/signature', [OfferLetterController::class, 'requestSignature'])->name('signature.request');
    Route::get('/{offerLetter}/signature/status', [OfferLetterController::class, 'signatureStatus'])->name('signature.status');
    
    // Counter Offers (Employer Response)
    Route::post('/counter-offers/{counterOffer}/respond', [OfferLetterController::class, 'respondToCounterOffer'])->name('counter-offers.respond');
    
    // Templates Management
    Route::get('/templates/list', [OfferLetterController::class, 'templates'])->name('templates.index');
    Route::get('/templates/{template}', [OfferLetterController::class, 'showTemplate'])->name('templates.show');
    Route::post('/templates', [OfferLetterController::class, 'storeTemplate'])->name('templates.store');
    
    // Benefits Packages
    Route::get('/benefits/list', [OfferLetterController::class, 'benefitsPackages'])->name('benefits.index');
    Route::post('/benefits', [OfferLetterController::class, 'storeBenefitsPackage'])->name('benefits.store');
    Route::get('/benefits/template', [OfferLetterController::class, 'benefitsTemplate'])->name('benefits.template');
});

// Candidate Offer Letter Routes
Route::middleware('auth')->prefix('my-offers')->name('candidate.offers.')->group(function () {
    Route::get('/', [OfferLetterController::class, 'index'])->name('index');
    Route::get('/{offerLetter}', [OfferLetterController::class, 'show'])->name('show');
    Route::get('/{offerLetter}/download', [OfferLetterController::class, 'download'])->name('download');
    
    // Offer Response
    Route::post('/{offerLetter}/accept', [OfferLetterController::class, 'accept'])->name('accept');
    Route::post('/{offerLetter}/decline', [OfferLetterController::class, 'decline'])->name('decline');
    Route::post('/{offerLetter}/counter-offer', [OfferLetterController::class, 'counterOffer'])->name('counter-offer');
    
    // AI Analysis
    Route::get('/{offerLetter}/analyze', [OfferLetterController::class, 'analyze'])->name('analyze');
    Route::get('/{offerLetter}/suggest-counter', [OfferLetterController::class, 'suggestCounterOffer'])->name('suggest-counter');
    
    // Offer Comparison
    Route::get('/compare/tool', [OfferLetterController::class, 'comparison'])->name('comparison');
    Route::post('/compare', [OfferLetterController::class, 'createComparison'])->name('comparison.create');
    Route::get('/compare/{comparison}', [OfferLetterController::class, 'comparisonReport'])->name('comparison.report');
});

// Background Check Routes (Employers)
Route::middleware(['auth', 'employer'])->prefix('employer/background-checks')->name('background-checks.')->group(function () {
    // Main Management
    Route::get('/', [\App\Http\Controllers\BackgroundCheckController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\BackgroundCheckController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\BackgroundCheckController::class, 'store'])->name('store');
    Route::get('/statistics', [\App\Http\Controllers\BackgroundCheckController::class, 'statistics'])->name('statistics');
    Route::get('/{backgroundCheck}', [\App\Http\Controllers\BackgroundCheckController::class, 'show'])->name('show');
    
    // Consent Management
    Route::post('/{backgroundCheck}/send-consent', [\App\Http\Controllers\BackgroundCheckController::class, 'sendConsent'])->name('send-consent');
    Route::post('/{backgroundCheck}/resend-consent', [\App\Http\Controllers\BackgroundCheckController::class, 'resendConsent'])->name('resend-consent');
    
    // Actions
    Route::post('/{backgroundCheck}/cancel', [\App\Http\Controllers\BackgroundCheckController::class, 'cancel'])->name('cancel');
    Route::get('/{backgroundCheck}/download', [\App\Http\Controllers\BackgroundCheckController::class, 'download'])->name('download');
    Route::post('/{backgroundCheck}/recheck', [\App\Http\Controllers\BackgroundCheckController::class, 'recheck'])->name('recheck');
    Route::patch('/{backgroundCheck}/notes', [\App\Http\Controllers\BackgroundCheckController::class, 'updateNotes'])->name('notes.update');
    
    // Adverse Action (FCRA Compliance)
    Route::post('/{backgroundCheck}/adverse-action', [\App\Http\Controllers\BackgroundCheckController::class, 'initiateAdverseAction'])->name('adverse-action');
    Route::post('/{backgroundCheck}/adverse-action/final', [\App\Http\Controllers\BackgroundCheckController::class, 'sendFinalAdverseAction'])->name('adverse-action.final');
    Route::post('/{backgroundCheck}/adverse-action/withdraw', [\App\Http\Controllers\BackgroundCheckController::class, 'withdrawAdverseAction'])->name('adverse-action.withdraw');
    
    // Packages
    Route::get('/packages/list', [\App\Http\Controllers\BackgroundCheckController::class, 'packages'])->name('packages');
    Route::post('/packages', [\App\Http\Controllers\BackgroundCheckController::class, 'storePackage'])->name('packages.store');
});

// Candidate Consent Routes (Signed URL - No Auth Required)
Route::prefix('background-check-consent')->name('background-check-consent.')->group(function () {
    Route::get('/{backgroundCheck}', [\App\Http\Controllers\BackgroundCheckController::class, 'consent'])
        ->name('show')
        ->middleware('signed');
    Route::post('/{backgroundCheck}', [\App\Http\Controllers\BackgroundCheckController::class, 'submitConsent'])
        ->name('submit')
        ->middleware('signed');
});

// Background Check Webhook (No Auth - authenticated via signature)
Route::post('/webhooks/background-check/{provider}', [\App\Http\Controllers\BackgroundCheckController::class, 'webhook'])
    ->name('background-check.webhook');

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Application Monitoring
    Route::get('/applications/monitor', [ApplicationMonitorController::class, 'index'])->name('applications.monitor');
    Route::get('/applications/{application}', [ApplicationMonitorController::class, 'show'])->name('applications.show');
    Route::get('/applications/export', [ApplicationMonitorController::class, 'export'])->name('applications.export');
    Route::post('/applications/bulk-update', [ApplicationMonitorController::class, 'bulkUpdate'])->name('applications.bulkUpdate');

    // Agent Administration - Emergency Controls
    Route::prefix('agent')->name('agent.')->group(function () {
        // Global kill switch
        Route::post('/kill-all', [\App\Http\Controllers\Admin\AgentAdminController::class, 'killAll'])
            ->middleware('throttle:5,60') // Max 5 kill-all actions per hour
            ->name('kill-all');
        Route::post('/resume-all', [\App\Http\Controllers\Admin\AgentAdminController::class, 'resumeAll'])
            ->middleware('throttle:5,60')
            ->name('resume-all');
        Route::get('/status', [\App\Http\Controllers\Admin\AgentAdminController::class, 'status'])
            ->name('status');
        Route::get('/list', [\App\Http\Controllers\Admin\AgentAdminController::class, 'list'])
            ->name('list');

        // Individual agent controls
        Route::post('/{userId}/stop', [\App\Http\Controllers\Admin\AgentAdminController::class, 'stopAgent'])
            ->middleware('throttle:30,1')
            ->name('stop');
        Route::post('/{userId}/resume', [\App\Http\Controllers\Admin\AgentAdminController::class, 'resumeAgent'])
            ->middleware('throttle:30,1')
            ->name('resume');
    });
});

// ============================================================
// TALENT MARKETPLACE ROUTES
// ============================================================

use App\Http\Controllers\Marketplace\MarketplaceController;
use App\Http\Controllers\Marketplace\FreelancerController;
use App\Http\Controllers\Marketplace\EmployerController as MarketplaceEmployerController;
use App\Http\Controllers\Marketplace\ContractController;

Route::prefix('marketplace')->name('marketplace.')->group(function () {
    // Public Routes
    Route::get('/', [MarketplaceController::class, 'index'])->name('index');
    Route::get('/projects', [MarketplaceController::class, 'projects'])->name('projects');
    Route::get('/projects/{project}', [MarketplaceController::class, 'project'])->name('project.show');
    Route::get('/freelancers', [MarketplaceController::class, 'freelancers'])->name('freelancers');
    Route::get('/freelancers/{profile}', [MarketplaceController::class, 'freelancer'])->name('freelancer.show');
    Route::get('/categories', [MarketplaceController::class, 'categories'])->name('categories');

    // Authenticated Routes
    Route::middleware('auth')->group(function () {
        // Freelancer Dashboard & Profile
        Route::prefix('freelancer')->name('freelancer.')->group(function () {
            Route::get('/dashboard', [FreelancerController::class, 'dashboard'])->name('dashboard');
            Route::get('/profile', [FreelancerController::class, 'profile'])->name('profile');
            Route::post('/profile', [FreelancerController::class, 'updateProfile'])->name('profile.update');
            Route::get('/proposals', [FreelancerController::class, 'proposals'])->name('proposals');
            Route::get('/contracts', [FreelancerController::class, 'activeContracts'])->name('contracts');
            Route::get('/earnings', [FreelancerController::class, 'earnings'])->name('earnings');
            Route::get('/badges', [FreelancerController::class, 'badges'])->name('badges');
            Route::post('/projects/{project}/proposal', [FreelancerController::class, 'submitProposal'])->name('submit-proposal');
            Route::delete('/proposals/{proposal}', [FreelancerController::class, 'withdrawProposal'])->name('withdraw-proposal');
            Route::get('/saved-projects', [FreelancerController::class, 'savedProjects'])->name('saved-projects');
            Route::post('/projects/{project}/save', [FreelancerController::class, 'toggleSaveProject'])->name('toggle-save-project');
        });

        // Employer Dashboard & Project Management
        Route::prefix('employer')->name('employer.')->group(function () {
            Route::get('/dashboard', [MarketplaceEmployerController::class, 'dashboard'])->name('dashboard');
            Route::get('/projects', [MarketplaceEmployerController::class, 'projects'])->name('projects');
            Route::get('/projects/create', [MarketplaceEmployerController::class, 'createProject'])->name('create-project');
            Route::post('/projects', [MarketplaceEmployerController::class, 'storeProject'])->name('store-project');
            Route::get('/projects/{project}/manage', [MarketplaceEmployerController::class, 'manageProject'])->name('manage-project');
            Route::put('/projects/{project}', [MarketplaceEmployerController::class, 'updateProject'])->name('update-project');
            Route::delete('/projects/{project}', [MarketplaceEmployerController::class, 'deleteProject'])->name('delete-project');
            Route::post('/projects/{project}/publish', [MarketplaceEmployerController::class, 'publishProject'])->name('publish-project');
            Route::post('/projects/{project}/close', [MarketplaceEmployerController::class, 'closeProject'])->name('close-project');
            Route::get('/projects/{project}/proposals', [MarketplaceEmployerController::class, 'reviewProposals'])->name('review-proposals');
            Route::post('/proposals/{proposal}/hire', [MarketplaceEmployerController::class, 'hireFreelancer'])->name('hire');
            Route::post('/proposals/{proposal}/reject', [MarketplaceEmployerController::class, 'rejectProposal'])->name('reject-proposal');
            Route::get('/freelancers/{profile}/invite', [MarketplaceEmployerController::class, 'showInviteForm'])->name('invite');
            Route::post('/freelancers/{profile}/invite', [MarketplaceEmployerController::class, 'sendInvitation'])->name('send-invitation');
            Route::get('/contracts', [MarketplaceEmployerController::class, 'contracts'])->name('contracts');
            Route::get('/saved', [MarketplaceEmployerController::class, 'savedFreelancers'])->name('saved');
            Route::post('/freelancers/{profile}/save', [MarketplaceEmployerController::class, 'toggleSaveFreelancer'])->name('toggle-save-freelancer');
        });

        // Contract Management (Both parties)
        Route::prefix('contracts')->name('contracts.')->group(function () {
            Route::get('/{contract}', [ContractController::class, 'show'])->name('show');
            Route::get('/{contract}/milestones', [ContractController::class, 'milestones'])->name('milestones');
            Route::post('/{contract}/milestones', [ContractController::class, 'createMilestone'])->name('milestones.create');
            Route::post('/milestones/{milestone}/submit', [ContractController::class, 'submitMilestone'])->name('milestones.submit');
            Route::post('/milestones/{milestone}/approve', [ContractController::class, 'approveMilestone'])->name('milestones.approve');
            Route::post('/milestones/{milestone}/revision', [ContractController::class, 'requestRevision'])->name('milestones.revision');
            Route::post('/milestones/{milestone}/fund', [ContractController::class, 'fundMilestone'])->name('milestones.fund');
            Route::get('/{contract}/messages', [ContractController::class, 'messages'])->name('messages');
            Route::post('/{contract}/messages', [ContractController::class, 'sendMessage'])->name('messages.send');
            Route::post('/{contract}/complete', [ContractController::class, 'complete'])->name('complete');
            Route::post('/{contract}/cancel', [ContractController::class, 'cancel'])->name('cancel');
            Route::post('/{contract}/dispute', [ContractController::class, 'dispute'])->name('dispute');
            Route::post('/{contract}/review', [ContractController::class, 'submitReview'])->name('review');
        });

        // Escrow Payment Callbacks
        Route::prefix('escrow')->name('escrow.')->group(function () {
            Route::post('/razorpay/callback', [ContractController::class, 'razorpayCallback'])->name('razorpay.callback');
            Route::post('/payu/success', [ContractController::class, 'payuSuccess'])->name('payu.success');
            Route::post('/payu/failure', [ContractController::class, 'payuFailure'])->name('payu.failure');
        });

        // Messaging (between freelancer and employer)
        Route::get('/message/{profile}', [MarketplaceController::class, 'messageFreelancer'])->name('message');
    });
});

// ============================================================
// GAMIFICATION LAYER ROUTES
// ============================================================

use App\Http\Controllers\GamificationController;

Route::middleware(['auth', 'verified'])->prefix('gamification')->name('gamification.')->group(function () {
    // Main Dashboard
    Route::get('/', [GamificationController::class, 'dashboard'])->name('dashboard');
    
    // Achievements
    Route::get('/achievements', [GamificationController::class, 'achievements'])->name('achievements');
    
    // Badges
    Route::get('/badges', [GamificationController::class, 'badges'])->name('badges');
    
    // Daily Challenges
    Route::get('/challenges', [GamificationController::class, 'challenges'])->name('challenges');
    Route::post('/challenges/{userChallenge}/claim', [GamificationController::class, 'claimChallenge'])->name('challenges.claim');
    
    // Leaderboards
    Route::get('/leaderboards', [GamificationController::class, 'leaderboards'])->name('leaderboards');
    Route::post('/leaderboards/toggle', [GamificationController::class, 'toggleLeaderboardOptIn'])->name('leaderboards.toggle');
    
    // Rewards Store
    Route::get('/rewards', [GamificationController::class, 'rewards'])->name('rewards');
    Route::post('/rewards/{reward}/redeem', [GamificationController::class, 'redeemReward'])->name('rewards.redeem');
    
    // Daily Reward
    Route::post('/claim-daily', [GamificationController::class, 'claimDailyReward'])->name('claim-daily');
    
    // History & Stats
    Route::get('/history', [GamificationController::class, 'history'])->name('history');
    Route::get('/stats', [GamificationController::class, 'stats'])->name('stats');
    
    // API Endpoints for AJAX
    Route::get('/api/profile', [GamificationController::class, 'getProfile'])->name('api.profile');
    Route::get('/api/notifications', [GamificationController::class, 'getNotifications'])->name('api.notifications');
});

/*
|--------------------------------------------------------------------------
| Health Check Routes
|--------------------------------------------------------------------------
|
| These routes are used for monitoring and load balancer health checks.
| They do not require authentication.
|
*/

Route::get('/health', [\App\Http\Controllers\HealthCheckController::class, 'health'])
    ->name('health.check');

Route::get('/ready', [\App\Http\Controllers\HealthCheckController::class, 'ready'])
    ->name('health.ready');

// Include employer routes from separate file
require __DIR__.'/employer.php';

require __DIR__.'/auth.php';
