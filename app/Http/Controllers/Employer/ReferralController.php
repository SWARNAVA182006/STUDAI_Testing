<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\EmployeeReferral;
use App\Models\Job;
use App\Models\ReferralSetting;
use App\Models\User;
use App\Notifications\ReferralStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferralController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:employer');
    }
    
    /**
     * Referral dashboard
     */
    public function index(Request $request)
    {
        $company = auth('employer')->user()->company;
        
        $query = EmployeeReferral::where('company_id', $company->id)
            ->with(['referrer', 'candidate', 'job', 'application']);
        
        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('bonus_status')) {
            $query->where('bonus_status', $request->bonus_status);
        }
        
        if ($request->filled('referrer_id')) {
            $query->where('referrer_id', $request->referrer_id);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('candidate', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $referrals = $query->orderByDesc('created_at')->paginate(20);
        
        // Statistics
        $stats = [
            'total_referrals' => EmployeeReferral::where('company_id', $company->id)->count(),
            'pending_review' => EmployeeReferral::where('company_id', $company->id)
                ->where('status', 'pending')->count(),
            'hired_referrals' => EmployeeReferral::where('company_id', $company->id)
                ->where('status', 'hired')->count(),
            'pending_bonuses' => EmployeeReferral::where('company_id', $company->id)
                ->where('bonus_status', 'pending')->sum('bonus_amount'),
            'paid_bonuses' => EmployeeReferral::where('company_id', $company->id)
                ->where('bonus_status', 'paid')->sum('bonus_amount'),
        ];
        
        // Top referrers
        $topReferrers = DB::table('employee_referrals')
            ->select('referrer_id', DB::raw('COUNT(*) as total_referrals'), 
                    DB::raw('SUM(CASE WHEN status = "hired" THEN 1 ELSE 0 END) as successful_hires'),
                    DB::raw('SUM(CASE WHEN bonus_status = "paid" THEN bonus_amount ELSE 0 END) as total_bonuses'))
            ->where('company_id', $company->id)
            ->groupBy('referrer_id')
            ->orderByDesc('successful_hires')
            ->limit(10)
            ->get();
        
        $topReferrersData = [];
        foreach ($topReferrers as $referrer) {
            $user = User::find($referrer->referrer_id);
            if ($user) {
                $topReferrersData[] = [
                    'referrer' => $user,
                    'total_referrals' => $referrer->total_referrals,
                    'successful_hires' => $referrer->successful_hires,
                    'total_bonuses' => $referrer->total_bonuses,
                ];
            }
        }
        
        return view('employer.referrals.index', compact('referrals', 'stats', 'topReferrersData'));
    }
    
    /**
     * Create new referral
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'candidate_name' => 'required|string|max:255',
            'candidate_email' => 'required|email',
            'candidate_phone' => 'nullable|string',
            'candidate_linkedin' => 'nullable|url',
            'resume_file' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'job_id' => 'required|exists:jobs,id',
            'referrer_notes' => 'nullable|string',
        ]);
        
        $employer = auth('employer')->user();
        $company = $employer->company;
        
        // Get referral settings
        $settings = ReferralSetting::firstOrCreate(
            ['company_id' => $company->id],
            [
                'enabled' => true,
                'auto_approve' => false,
                'default_bonus_amount' => 25000,
                'bonus_by_level' => [
                    'entry' => 15000,
                    'mid' => 25000,
                    'senior' => 50000,
                    'lead' => 75000,
                    'executive' => 100000,
                ],
                'probation_days' => 90,
            ]
        );
        
        if (!$settings->enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Referral program is currently disabled.',
            ], 403);
        }
        
        // Check max referrals per employee
        if ($settings->max_referrals_per_employee) {
            $referralCount = EmployeeReferral::where('company_id', $company->id)
                ->where('referrer_id', $employer->id)
                ->whereDate('created_at', '>=', now()->startOfMonth())
                ->count();
            
            if ($referralCount >= $settings->max_referrals_per_employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have reached the maximum number of referrals for this month.',
                ], 403);
            }
        }
        
        // Find or create candidate user
        $candidate = User::where('email', $validated['candidate_email'])->first();
        
        if (!$candidate) {
            // Create minimal user record for referred candidate
            $candidate = User::create([
                'name' => $validated['candidate_name'],
                'email' => $validated['candidate_email'],
                'phone' => $validated['candidate_phone'] ?? null,
                'account_type' => 'job_seeker',
                'password' => bcrypt(Str::random(16)), // Will be reset on first login
            ]);
        }
        
        // Upload resume if provided
        $resumePath = null;
        if ($request->hasFile('resume_file')) {
            $resumePath = $request->file('resume_file')->store('referral-resumes', 'private');
        }
        
        // Determine bonus amount based on job level
        $job = Job::findOrFail($validated['job_id']);
        $bonusAmount = $settings->default_bonus_amount;
        
        if ($job->experience_level && isset($settings->bonus_by_level[$job->experience_level])) {
            $bonusAmount = $settings->bonus_by_level[$job->experience_level];
        }
        
        // Create referral
        $referral = EmployeeReferral::create([
            'company_id' => $company->id,
            'referrer_id' => $employer->id,
            'candidate_id' => $candidate->id,
            'job_id' => $validated['job_id'],
            'status' => $settings->auto_approve ? 'contacted' : 'pending',
            'bonus_amount' => $bonusAmount,
            'bonus_status' => 'pending',
            'referrer_notes' => $validated['referrer_notes'] ?? null,
            'resume_path' => $resumePath,
        ]);
        
        // Create application if auto-approved
        if ($settings->auto_approve) {
            $application = Application::create([
                'job_id' => $validated['job_id'],
                'user_id' => $candidate->id,
                'status' => 'received',
                'source' => 'referral',
                'referral_id' => $referral->id,
                'resume_path' => $resumePath,
            ]);
            
            $referral->update(['application_id' => $application->id]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Referral submitted successfully!',
            'referral_id' => $referral->id,
        ]);
    }
    
    /**
     * Leaderboard
     */
    public function leaderboard(Request $request)
    {
        $company = auth('employer')->user()->company;
        $period = $request->input('period', 'all_time'); // all_time, this_year, this_quarter, this_month
        
        $query = DB::table('employee_referrals')
            ->select(
                'referrer_id',
                DB::raw('COUNT(*) as total_referrals'),
                DB::raw('SUM(CASE WHEN status = "hired" THEN 1 ELSE 0 END) as successful_hires'),
                DB::raw('SUM(CASE WHEN bonus_status = "paid" THEN bonus_amount ELSE 0 END) as total_earned'),
                DB::raw('SUM(CASE WHEN bonus_status = "approved" THEN bonus_amount ELSE 0 END) as pending_payout'),
                DB::raw('MAX(created_at) as last_referral_date')
            )
            ->where('company_id', $company->id);
        
        // Apply period filter
        switch ($period) {
            case 'this_month':
                $query->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month);
                break;
            case 'this_quarter':
                $query->whereBetween('created_at', [
                    now()->startOfQuarter(),
                    now()->endOfQuarter()
                ]);
                break;
            case 'this_year':
                $query->whereYear('created_at', now()->year);
                break;
        }
        
        $leaderboard = $query->groupBy('referrer_id')
            ->orderByDesc('successful_hires')
            ->orderByDesc('total_referrals')
            ->get();
        
        // Attach user data and calculate rankings
        $rankedLeaderboard = [];
        $rank = 1;
        foreach ($leaderboard as $entry) {
            $user = User::find($entry->referrer_id);
            if ($user) {
                $rankedLeaderboard[] = [
                    'rank' => $rank++,
                    'referrer' => $user,
                    'total_referrals' => $entry->total_referrals,
                    'successful_hires' => $entry->successful_hires,
                    'success_rate' => $entry->total_referrals > 0 
                        ? round(($entry->successful_hires / $entry->total_referrals) * 100, 1)
                        : 0,
                    'total_earned' => $entry->total_earned,
                    'pending_payout' => $entry->pending_payout,
                    'last_referral_date' => $entry->last_referral_date,
                ];
            }
        }
        
        return view('employer.referrals.leaderboard', compact('rankedLeaderboard', 'period'));
    }
    
    /**
     * Approve/reject referral
     */
    public function approve(Request $request, EmployeeReferral $referral)
    {
        $company = auth('employer')->user()->company;
        
        if ($referral->company_id !== $company->id) {
            abort(403);
        }
        
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'rejection_reason' => 'nullable|required_if:action,reject|string',
        ]);
        
        if ($validated['action'] === 'approve') {
            // Create application for the candidate
            $application = Application::create([
                'job_id' => $referral->job_id,
                'user_id' => $referral->candidate_id,
                'status' => 'received',
                'source' => 'referral',
                'referral_id' => $referral->id,
                'resume_path' => $referral->resume_path,
            ]);
            
            $referral->update([
                'status' => 'contacted',
                'application_id' => $application->id,
                'reviewed_at' => now(),
            ]);
            
            event(new \App\Events\ReferralReviewed($referral, 'approved'));
            
            return response()->json([
                'success' => true,
                'message' => 'Referral approved and application created.',
            ]);
            
        } else {
            $referral->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
                'reviewed_at' => now(),
                'bonus_status' => 'not_eligible',
            ]);
            
            event(new \App\Events\ReferralReviewed($referral, 'rejected'));
            
            return response()->json([
                'success' => true,
                'message' => 'Referral rejected.',
            ]);
        }
    }
    
    /**
     * Referral settings
     */
    public function settings()
    {
        $company = auth('employer')->user()->company;
        
        $settings = ReferralSetting::firstOrCreate(
            ['company_id' => $company->id],
            [
                'enabled' => true,
                'auto_approve' => false,
                'default_bonus_amount' => 25000,
                'bonus_by_level' => [
                    'entry' => 15000,
                    'mid' => 25000,
                    'senior' => 50000,
                    'lead' => 75000,
                    'executive' => 100000,
                ],
                'probation_days' => 90,
                'terms_and_conditions' => null,
            ]
        );
        
        return view('employer.referrals.settings', compact('settings'));
    }
    
    /**
     * Update referral settings
     */
    public function updateSettings(Request $request)
    {
        $company = auth('employer')->user()->company;
        
        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
            'auto_approve' => 'nullable|boolean',
            'default_bonus_amount' => 'required|numeric|min:0',
            'bonus_entry' => 'required|numeric|min:0',
            'bonus_mid' => 'required|numeric|min:0',
            'bonus_senior' => 'required|numeric|min:0',
            'bonus_lead' => 'required|numeric|min:0',
            'bonus_executive' => 'required|numeric|min:0',
            'probation_days' => 'required|integer|min:0|max:365',
            'max_referrals_per_employee' => 'nullable|integer|min:1',
            'terms_and_conditions' => 'nullable|string',
        ]);
        
        $settings = ReferralSetting::updateOrCreate(
            ['company_id' => $company->id],
            [
                'enabled' => $request->has('enabled'),
                'auto_approve' => $request->has('auto_approve'),
                'default_bonus_amount' => $validated['default_bonus_amount'],
                'bonus_by_level' => [
                    'entry' => $validated['bonus_entry'],
                    'mid' => $validated['bonus_mid'],
                    'senior' => $validated['bonus_senior'],
                    'lead' => $validated['bonus_lead'],
                    'executive' => $validated['bonus_executive'],
                ],
                'probation_days' => $validated['probation_days'],
                'max_referrals_per_employee' => $validated['max_referrals_per_employee'] ?? null,
                'terms_and_conditions' => $validated['terms_and_conditions'] ?? null,
            ]
        );
        
        return redirect()->route('employer.referrals.settings')
            ->with('success', 'Referral program settings updated successfully!');
    }
}
