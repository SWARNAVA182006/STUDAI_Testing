<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\TalentPoolCandidate;
use App\Models\User;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TalentPoolController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:employer');
    }
    
    /**
     * Show talent pool dashboard
     */
    public function index(Request $request)
    {
        $user = auth('employer')->user();
        $company = $user->company;
        
        $tags = $request->input('tags', []);
        $search = $request->input('search');
        $rating = $request->input('rating');
        $source = $request->input('source');
        
        $query = TalentPoolCandidate::where('company_id', $company->id)
            ->where('is_active', true)
            ->with(['candidate.profile', 'addedBy:id,name']);
        
        // Filter by tags
        if (!empty($tags)) {
            $query->where(function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }
        
        // Search by name, email, skills
        if ($search) {
            $query->whereHas('candidate', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('profile', function ($pq) use ($search) {
                        $pq->whereJsonContains('skills', $search);
                    });
            });
        }
        
        // Filter by rating
        if ($rating) {
            $query->where('rating', '>=', $rating);
        }
        
        // Filter by source
        if ($source) {
            $query->where('source', $source);
        }
        
        $candidates = $query->orderByDesc('last_contacted_at')
            ->orderByDesc('created_at')
            ->paginate(24);
        
        // Get all unique tags for filter
        $allTags = TalentPoolCandidate::where('company_id', $company->id)
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->sort()
            ->values();
        
        // Statistics
        $stats = [
            'total_candidates' => TalentPoolCandidate::where('company_id', $company->id)->where('is_active', true)->count(),
            'contacted_this_month' => TalentPoolCandidate::where('company_id', $company->id)
                ->whereMonth('last_contacted_at', now()->month)
                ->count(),
            'avg_rating' => TalentPoolCandidate::where('company_id', $company->id)->avg('rating'),
            'by_source' => TalentPoolCandidate::where('company_id', $company->id)
                ->select('source', DB::raw('count(*) as count'))
                ->groupBy('source')
                ->pluck('count', 'source'),
        ];
        
        return view('employer.talent-pool.index', compact('candidates', 'allTags', 'stats', 'tags', 'search', 'rating', 'source'));
    }
    
    /**
     * Add candidate to talent pool
     */
    public function addCandidate(Request $request, User $user)
    {
        $employer = auth('employer')->user();
        $company = $employer->company;
        
        $validated = $request->validate([
            'source' => 'nullable|string',
            'tags' => 'nullable|array',
            'notes' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5',
        ]);
        
        // Check if already in pool
        $existing = TalentPoolCandidate::where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->first();
        
        if ($existing) {
            if (!$existing->is_active) {
                $existing->update(['is_active' => true]);
                return response()->json([
                    'success' => true,
                    'message' => 'Candidate reactivated in talent pool',
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Candidate already in talent pool',
            ], 422);
        }
        
        TalentPoolCandidate::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'added_by' => $employer->id,
            'source' => $validated['source'] ?? 'manual',
            'tags' => $validated['tags'] ?? [],
            'notes' => $validated['notes'] ?? null,
            'rating' => $validated['rating'] ?? null,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Candidate added to talent pool',
        ]);
    }
    
    /**
     * Remove candidate from talent pool
     */
    public function removeCandidate(User $user)
    {
        $company = auth('employer')->user()->company;
        
        $candidate = TalentPoolCandidate::where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        $candidate->update(['is_active' => false]);
        
        return response()->json([
            'success' => true,
            'message' => 'Candidate removed from talent pool',
        ]);
    }
    
    /**
     * Tag candidates
     */
    public function tagCandidates(Request $request)
    {
        $validated = $request->validate([
            'candidate_ids' => 'required|array',
            'candidate_ids.*' => 'exists:talent_pool,id',
            'tags' => 'required|array',
            'action' => 'required|in:add,remove,replace',
        ]);
        
        $company = auth('employer')->user()->company;
        
        TalentPoolCandidate::whereIn('id', $validated['candidate_ids'])
            ->where('company_id', $company->id)
            ->get()
            ->each(function ($candidate) use ($validated) {
                $currentTags = $candidate->tags ?? [];
                
                switch ($validated['action']) {
                    case 'add':
                        $newTags = array_unique(array_merge($currentTags, $validated['tags']));
                        break;
                    case 'remove':
                        $newTags = array_diff($currentTags, $validated['tags']);
                        break;
                    case 'replace':
                        $newTags = $validated['tags'];
                        break;
                }
                
                $candidate->update(['tags' => array_values($newTags)]);
            });
        
        return response()->json([
            'success' => true,
            'message' => 'Tags updated successfully',
        ]);
    }
    
    /**
     * Search candidates (advanced)
     */
    public function search(Request $request)
    {
        $company = auth('employer')->user()->company;
        
        $validated = $request->validate([
            'skills' => 'nullable|array',
            'min_experience' => 'nullable|integer',
            'max_experience' => 'nullable|integer',
            'location' => 'nullable|string',
            'education_level' => 'nullable|string',
            'availability' => 'nullable|in:immediate,2_weeks,1_month,3_months',
        ]);
        
        $query = TalentPoolCandidate::where('company_id', $company->id)
            ->where('is_active', true)
            ->with(['candidate.profile']);
        
        // Filter by skills
        if (!empty($validated['skills'])) {
            $query->whereHas('candidate.profile', function ($q) use ($validated) {
                foreach ($validated['skills'] as $skill) {
                    $q->whereJsonContains('skills', $skill);
                }
            });
        }
        
        // Filter by experience
        if (isset($validated['min_experience'])) {
            $query->whereHas('candidate.profile', function ($q) use ($validated) {
                $q->where('total_experience', '>=', $validated['min_experience']);
            });
        }
        
        if (isset($validated['max_experience'])) {
            $query->whereHas('candidate.profile', function ($q) use ($validated) {
                $q->where('total_experience', '<=', $validated['max_experience']);
            });
        }
        
        // Filter by location
        if (!empty($validated['location'])) {
            $query->whereHas('candidate.profile', function ($q) use ($validated) {
                $q->where('location', 'like', "%{$validated['location']}%");
            });
        }
        
        $results = $query->get();
        
        return response()->json([
            'success' => true,
            'count' => $results->count(),
            'candidates' => $results,
        ]);
    }
    
    /**
     * Bulk outreach to candidates
     */
    public function bulkOutreach(Request $request)
    {
        $validated = $request->validate([
            'candidate_ids' => 'required|array',
            'candidate_ids.*' => 'exists:talent_pool,id',
            'job_id' => 'nullable|exists:jobs,id',
            'template_id' => 'nullable|exists:message_templates,id',
            'subject' => 'required_without:template_id|string',
            'message' => 'required_without:template_id|string',
        ]);
        
        $company = auth('employer')->user()->company;
        $employer = auth('employer')->user();
        
        $candidates = TalentPoolCandidate::whereIn('id', $validated['candidate_ids'])
            ->where('company_id', $company->id)
            ->with('candidate')
            ->get();
        
        $successCount = 0;
        
        foreach ($candidates as $talentPoolCandidate) {
            $candidate = $talentPoolCandidate->candidate;
            
            // Get or create conversation
            $conversation = \App\Models\Conversation::firstOrCreate([
                'company_id' => $company->id,
                'candidate_id' => $candidate->id,
                'job_id' => $validated['job_id'] ?? null,
            ], [
                'subject' => $validated['subject'],
                'last_message_at' => now(),
            ]);
            
            // Personalize message
            $personalizedMessage = $this->personalizeMessage(
                $validated['message'],
                $candidate,
                $validated['job_id'] ?? null
            );
            
            // Create message
            \App\Models\Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $employer->id,
                'body' => $personalizedMessage,
            ]);
            
            // Update last contacted
            $talentPoolCandidate->update([
                'last_contacted_at' => now(),
            ]);
            
            // Dispatch event — notification sent via NotifyOnMessagingAndReferral subscriber
            event(new \App\Events\MessageSent($candidate, $conversation, $personalizedMessage));
            
            $successCount++;
        }
        
        return response()->json([
            'success' => true,
            'message' => "Messages sent to {$successCount} candidates",
        ]);
    }
    
    /**
     * Update candidate notes and rating
     */
    public function updateCandidate(Request $request, TalentPoolCandidate $candidate)
    {
        $company = auth('employer')->user()->company;
        
        if ($candidate->company_id !== $company->id) {
            abort(403);
        }
        
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5',
            'tags' => 'nullable|array',
        ]);
        
        $candidate->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Candidate updated successfully',
        ]);
    }
    
    /**
     * Get candidate match for specific job
     */
    public function matchForJob(Request $request)
    {
        $validated = $request->validate([
            'job_id' => 'required|exists:jobs,id',
            'min_score' => 'nullable|integer|min:0|max:100',
        ]);
        
        $company = auth('employer')->user()->company;
        $job = \App\Models\Job::where('company_id', $company->id)
            ->findOrFail($validated['job_id']);
        
        $minScore = $validated['min_score'] ?? 70;
        
        $candidates = TalentPoolCandidate::where('company_id', $company->id)
            ->where('is_active', true)
            ->with(['candidate.profile'])
            ->get();
        
        $matches = [];
        
        foreach ($candidates as $talentPoolCandidate) {
            $candidate = $talentPoolCandidate->candidate;
            
            // Calculate match score (reuse logic from ApplicationService)
            $matchScore = $this->calculateMatchScore($candidate, $job);
            
            if ($matchScore >= $minScore) {
                $matches[] = [
                    'talent_pool_id' => $talentPoolCandidate->id,
                    'candidate' => $candidate,
                    'match_score' => $matchScore,
                    'rating' => $talentPoolCandidate->rating,
                    'tags' => $talentPoolCandidate->tags,
                ];
            }
        }
        
        // Sort by match score
        usort($matches, fn($a, $b) => $b['match_score'] <=> $a['match_score']);
        
        return response()->json([
            'success' => true,
            'job' => $job,
            'matches' => $matches,
            'total_matches' => count($matches),
        ]);
    }
    
    /**
     * Personalize message with candidate data
     */
    protected function personalizeMessage(string $message, User $candidate, ?int $jobId = null): string
    {
        $replacements = [
            '{{candidate_name}}' => $candidate->name,
            '{{first_name}}' => explode(' ', $candidate->name)[0],
        ];
        
        if ($jobId) {
            $job = \App\Models\Job::find($jobId);
            if ($job) {
                $replacements['{{job_title}}'] = $job->title;
                $replacements['{{job_location}}'] = $job->location;
            }
        }
        
        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }
    
    /**
     * Calculate match score (simplified version)
     */
    protected function calculateMatchScore(User $candidate, \App\Models\Job $job): float
    {
        $profile = $candidate->profile;
        if (!$profile) {
            return 0;
        }
        
        $score = 0;
        
        // Skills match (50%)
        $candidateSkills = $profile->skills ?? [];
        $requiredSkills = $job->required_skills ?? [];
        if (count($requiredSkills) > 0) {
            $matchedSkills = count(array_intersect($candidateSkills, $requiredSkills));
            $score += ($matchedSkills / count($requiredSkills)) * 50;
        } else {
            $score += 50;
        }
        
        // Experience match (30%)
        $candidateExp = $profile->total_experience ?? 0;
        $requiredExp = $job->min_experience ?? 0;
        if ($candidateExp >= $requiredExp) {
            $score += 30;
        } else {
            $score += ($candidateExp / max(1, $requiredExp)) * 30;
        }
        
        // Location match (20%)
        if ($job->work_mode === 'remote' || $profile->location === $job->location) {
            $score += 20;
        }
        
        return round($score, 1);
    }
}
