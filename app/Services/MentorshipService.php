<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MentorshipMatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class MentorshipService
{
    public function __construct(
        private AIService $aiService,
        private NetworkingService $networkingService
    ) {}

    /**
     * Find potential mentors for a user based on their goals
     */
    public function findPotentialMentors(User $mentee, int $limit = 10): Collection
    {
        // Get mentee's profile and goals
        $profile = $mentee->candidateProfile;
        $skills = $profile?->skills ?? [];
        $desiredSkills = $profile?->desired_skills ?? [];
        $industry = $profile?->industry;

        // Find experienced users who could be mentors
        $potentialMentors = User::where('id', '!=', $mentee->id)
            ->whereHas('candidateProfile', function ($query) use ($skills, $desiredSkills, $industry) {
                // Look for users with skills the mentee wants
                if (! empty($desiredSkills)) {
                    $query->where(function ($q) use ($desiredSkills) {
                        foreach ($desiredSkills as $skill) {
                            $q->orWhereJsonContains('skills', $skill);
                        }
                    });
                }

                // Same industry preference
                if ($industry) {
                    $query->orWhere('industry', $industry);
                }

                // More experience
                $query->whereNotNull('years_of_experience')
                    ->where('years_of_experience', '>=', 3);
            })
            ->with('candidateProfile')
            ->limit($limit * 2) // Get more to filter
            ->get();

        // Exclude already matched mentors
        $existingMentorIds = MentorshipMatch::where('mentee_id', $mentee->id)
            ->whereIn('status', [
                MentorshipMatch::STATUS_PENDING,
                MentorshipMatch::STATUS_ACTIVE,
                MentorshipMatch::STATUS_ACCEPTED,
            ])
            ->pluck('mentor_id')
            ->toArray();

        return $potentialMentors
            ->reject(fn ($user) => in_array($user->id, $existingMentorIds))
            ->take($limit);
    }

    /**
     * AI-powered mentorship matching
     */
    public function findBestMatches(User $mentee, array $goals, int $limit = 5): array
    {
        $potentialMentors = $this->findPotentialMentors($mentee, $limit * 3);
        $matches = [];

        foreach ($potentialMentors as $mentor) {
            try {
                $matchResult = $this->calculateMatchScore($mentee, $mentor, $goals);
                $matches[] = [
                    'mentor' => $mentor,
                    'score' => $matchResult['score'],
                    'reasoning' => $matchResult['reasoning'],
                    'matched_skills' => $matchResult['matched_skills'],
                ];
            } catch (\Exception $e) {
                Log::warning('Failed to calculate match score', [
                    'mentee_id' => $mentee->id,
                    'mentor_id' => $mentor->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Sort by score descending
        usort($matches, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($matches, 0, $limit);
    }

    /**
     * Calculate match score between mentee and potential mentor
     */
    private function calculateMatchScore(User $mentee, User $mentor, array $goals): array
    {
        $menteeProfile = $mentee->candidateProfile;
        $mentorProfile = $mentor->candidateProfile;

        // Basic skill matching
        $menteeDesiredSkills = $menteeProfile?->desired_skills ?? $goals['desired_skills'] ?? [];
        $mentorSkills = $mentorProfile?->skills ?? [];

        $matchedSkills = array_intersect($menteeDesiredSkills, $mentorSkills);
        $skillMatchScore = count($matchedSkills) > 0
            ? min(count($matchedSkills) / max(count($menteeDesiredSkills), 1), 1.0)
            : 0;

        // Experience gap (mentor should have more experience)
        $menteeExperience = $menteeProfile?->years_of_experience ?? 0;
        $mentorExperience = $mentorProfile?->years_of_experience ?? 0;
        $experienceGap = $mentorExperience - $menteeExperience;
        $experienceScore = $experienceGap >= 3 ? 1.0 : max($experienceGap / 3, 0);

        // Industry match
        $industryScore = ($menteeProfile?->industry === $mentorProfile?->industry) ? 1.0 : 0.3;

        // Connection proximity bonus
        $connectionDegree = $this->networkingService->getConnectionDegree($mentee, $mentor);
        $connectionScore = match ($connectionDegree) {
            1 => 1.0,
            2 => 0.7,
            3 => 0.4,
            default => 0.2,
        };

        // Weighted average
        $weights = [
            'skills' => 0.35,
            'experience' => 0.25,
            'industry' => 0.20,
            'connection' => 0.20,
        ];

        $totalScore = ($skillMatchScore * $weights['skills'])
            + ($experienceScore * $weights['experience'])
            + ($industryScore * $weights['industry'])
            + ($connectionScore * $weights['connection']);

        // Try to get AI reasoning if available
        $reasoning = $this->getAIReasoningForMatch($mentee, $mentor, $goals, $totalScore);

        return [
            'score' => round($totalScore, 2),
            'reasoning' => $reasoning,
            'matched_skills' => array_values($matchedSkills),
            'components' => [
                'skill_match' => round($skillMatchScore, 2),
                'experience' => round($experienceScore, 2),
                'industry' => round($industryScore, 2),
                'connection' => round($connectionScore, 2),
            ],
        ];
    }

    /**
     * Get AI reasoning for a mentorship match
     */
    private function getAIReasoningForMatch(
        User $mentee,
        User $mentor,
        array $goals,
        float $score
    ): array {
        try {
            $menteeProfile = $mentee->candidateProfile;
            $mentorProfile = $mentor->candidateProfile;

            $prompt = <<<PROMPT
Analyze this mentorship match and provide brief reasoning:

MENTEE:
- Current Skills: {$this->formatArray($menteeProfile?->skills ?? [])}
- Desired Skills: {$this->formatArray($goals['desired_skills'] ?? [])}
- Goals: {$this->formatArray($goals['goals'] ?? [])}
- Experience: {$menteeProfile?->years_of_experience} years
- Industry: {$menteeProfile?->industry}

MENTOR:
- Skills: {$this->formatArray($mentorProfile?->skills ?? [])}
- Experience: {$mentorProfile?->years_of_experience} years
- Industry: {$mentorProfile?->industry}
- Title: {$mentorProfile?->current_title}

Match Score: {$score}

Provide a JSON response with:
{
    "summary": "One sentence summary of why this is a good/bad match",
    "strengths": ["strength 1", "strength 2"],
    "potential_areas": ["area they could work on together"],
    "recommended_focus": "Primary focus area for mentorship"
}
PROMPT;

            $response = $this->aiService->generateResponse($prompt, [
                'response_format' => ['type' => 'json_object'],
                'max_tokens' => 300,
            ]);

            return json_decode($response, true) ?? [
                'summary' => 'Good potential match based on skills and experience.',
                'strengths' => ['Skill alignment', 'Experience gap'],
                'potential_areas' => ['Career growth'],
                'recommended_focus' => 'Professional development',
            ];
        } catch (\Exception $e) {
            Log::warning('AI reasoning failed for mentorship match', [
                'error' => $e->getMessage(),
            ]);

            return [
                'summary' => 'Match based on skill and experience alignment.',
                'strengths' => ['Compatible background'],
                'potential_areas' => ['Career development'],
                'recommended_focus' => 'Skill building',
            ];
        }
    }

    private function formatArray(array $items): string
    {
        return implode(', ', $items) ?: 'Not specified';
    }

    /**
     * Request mentorship from a mentor
     */
    public function requestMentorship(
        User $mentee,
        User $mentor,
        array $goals,
        ?float $matchScore = null,
        ?array $reasoning = null
    ): MentorshipMatch {
        // Check for existing match
        $existing = MentorshipMatch::where('mentee_id', $mentee->id)
            ->where('mentor_id', $mentor->id)
            ->whereIn('status', [
                MentorshipMatch::STATUS_PENDING,
                MentorshipMatch::STATUS_ACTIVE,
            ])
            ->first();

        if ($existing) {
            throw new \Exception('Mentorship request already exists');
        }

        // Calculate match if not provided
        if ($matchScore === null) {
            $matchResult = $this->calculateMatchScore($mentee, $mentor, $goals);
            $matchScore = $matchResult['score'];
            $reasoning = $matchResult['reasoning'];
        }

        return MentorshipMatch::create([
            'mentor_id' => $mentor->id,
            'mentee_id' => $mentee->id,
            'status' => MentorshipMatch::STATUS_PENDING,
            'match_score' => $matchScore,
            'ai_reasoning' => $reasoning,
            'mentee_goals' => $goals['goals'] ?? [],
            'matched_skills' => $goals['desired_skills'] ?? [],
            'meeting_frequency' => $goals['meeting_frequency'] ?? MentorshipMatch::FREQUENCY_BIWEEKLY,
            'preferred_communication' => $goals['preferred_communication'] ?? ['video_call', 'messaging'],
        ]);
    }

    /**
     * Get mentorship matches for a user (as mentor or mentee)
     */
    public function getMentorshipMatches(
        User $user,
        ?string $role = null,
        ?string $status = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = MentorshipMatch::with(['mentor', 'mentee']);

        if ($role === 'mentor') {
            $query->where('mentor_id', $user->id);
        } elseif ($role === 'mentee') {
            $query->where('mentee_id', $user->id);
        } else {
            $query->forUser($user->id);
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Get pending mentorship requests for a user (as mentor)
     */
    public function getPendingMentorshipRequests(User $user): Collection
    {
        return MentorshipMatch::where('mentor_id', $user->id)
            ->pending()
            ->with('mentee')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Accept a mentorship request
     */
    public function acceptMentorship(MentorshipMatch $match, User $mentor): MentorshipMatch
    {
        if ($match->mentor_id !== $mentor->id) {
            throw new \Exception('Only the mentor can accept this request');
        }

        $match->accept();

        // Create a conversation for mentor-mentee communication
        $this->networkingService->getOrCreateConversation($mentor, $match->mentee);

        return $match;
    }

    /**
     * Reject a mentorship request
     */
    public function rejectMentorship(MentorshipMatch $match, User $mentor): MentorshipMatch
    {
        if ($match->mentor_id !== $mentor->id) {
            throw new \Exception('Only the mentor can reject this request');
        }

        $match->reject();

        return $match;
    }

    /**
     * End a mentorship
     */
    public function completeMentorship(MentorshipMatch $match, User $user): MentorshipMatch
    {
        if ($match->mentor_id !== $user->id && $match->mentee_id !== $user->id) {
            throw new \Exception('Only participants can end this mentorship');
        }

        $match->complete();

        return $match;
    }

    /**
     * Get mentorship statistics for a user
     */
    public function getMentorshipStats(User $user): array
    {
        $asMentor = MentorshipMatch::where('mentor_id', $user->id);
        $asMentee = MentorshipMatch::where('mentee_id', $user->id);

        return [
            'as_mentor' => [
                'active' => (clone $asMentor)->active()->count(),
                'completed' => (clone $asMentor)->where('status', MentorshipMatch::STATUS_COMPLETED)->count(),
                'pending' => (clone $asMentor)->pending()->count(),
                'total_mentees' => (clone $asMentor)->whereIn('status', [
                    MentorshipMatch::STATUS_ACTIVE,
                    MentorshipMatch::STATUS_COMPLETED,
                ])->count(),
            ],
            'as_mentee' => [
                'active' => (clone $asMentee)->active()->count(),
                'completed' => (clone $asMentee)->where('status', MentorshipMatch::STATUS_COMPLETED)->count(),
                'pending' => (clone $asMentee)->pending()->count(),
            ],
        ];
    }
}
