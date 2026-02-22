<?php

declare(strict_types=1);

namespace App\Livewire\Network;

use App\Models\MentorshipMatch;
use App\Models\User;
use App\Services\MentorshipService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class MentorshipHub extends Component
{
    use WithPagination;

    public string $tab = 'find';
    public string $roleFilter = 'all';

    // Find mentor form
    public bool $showFindForm = false;
    public array $selectedGoals = [];
    public array $desiredSkills = [];
    public string $meetingFrequency = 'biweekly';
    public string $newSkill = '';

    // Request mentorship
    public ?int $selectedMentorId = null;
    public array $potentialMatches = [];
    public bool $isSearching = false;

    protected MentorshipService $mentorshipService;

    public function boot(MentorshipService $mentorshipService): void
    {
        $this->mentorshipService = $mentorshipService;
    }

    #[Computed]
    public function stats()
    {
        return $this->mentorshipService->getMentorshipStats(auth()->user());
    }

    #[Computed]
    public function mentorshipMatches()
    {
        $role = $this->roleFilter !== 'all' ? $this->roleFilter : null;

        return $this->mentorshipService->getMentorshipMatches(
            auth()->user(),
            $role,
            null,
            15
        );
    }

    #[Computed]
    public function pendingRequests()
    {
        return $this->mentorshipService->getPendingMentorshipRequests(auth()->user());
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    public function setRoleFilter(string $role): void
    {
        $this->roleFilter = $role;
        $this->resetPage();
    }

    public function openFindForm(): void
    {
        $this->showFindForm = true;
        $this->resetFindForm();
    }

    public function closeFindForm(): void
    {
        $this->showFindForm = false;
        $this->resetFindForm();
    }

    private function resetFindForm(): void
    {
        $this->selectedGoals = [];
        $this->desiredSkills = [];
        $this->meetingFrequency = 'biweekly';
        $this->potentialMatches = [];
        $this->selectedMentorId = null;
        $this->isSearching = false;
    }

    public function addSkill(): void
    {
        $skill = trim($this->newSkill);
        if ($skill && ! in_array($skill, $this->desiredSkills)) {
            $this->desiredSkills[] = $skill;
        }
        $this->newSkill = '';
    }

    public function removeSkill(int $index): void
    {
        unset($this->desiredSkills[$index]);
        $this->desiredSkills = array_values($this->desiredSkills);
    }

    public function searchMentors(): void
    {
        $this->validate([
            'desiredSkills' => 'required|array|min:1',
            'selectedGoals' => 'required|array|min:1',
        ], [
            'desiredSkills.required' => 'Please add at least one skill you want to learn.',
            'desiredSkills.min' => 'Please add at least one skill you want to learn.',
            'selectedGoals.required' => 'Please select at least one goal.',
            'selectedGoals.min' => 'Please select at least one goal.',
        ]);

        $this->isSearching = true;

        $goals = [
            'goals' => $this->selectedGoals,
            'desired_skills' => $this->desiredSkills,
            'meeting_frequency' => $this->meetingFrequency,
        ];

        $this->potentialMatches = $this->mentorshipService->findBestMatches(
            auth()->user(),
            $goals,
            5
        );

        $this->isSearching = false;
    }

    public function selectMentor(int $mentorId): void
    {
        $this->selectedMentorId = $mentorId;
    }

    public function requestMentorship(): void
    {
        if (! $this->selectedMentorId) {
            return;
        }

        try {
            $mentor = User::findOrFail($this->selectedMentorId);

            // Find the match data
            $matchData = collect($this->potentialMatches)
                ->first(fn ($m) => $m['mentor']->id === $this->selectedMentorId);

            $goals = [
                'goals' => $this->selectedGoals,
                'desired_skills' => $this->desiredSkills,
                'meeting_frequency' => $this->meetingFrequency,
                'preferred_communication' => ['video_call', 'messaging'],
            ];

            $this->mentorshipService->requestMentorship(
                auth()->user(),
                $mentor,
                $goals,
                $matchData['score'] ?? null,
                $matchData['reasoning'] ?? null
            );

            $this->closeFindForm();
            $this->dispatch('notify', type: 'success', message: 'Mentorship request sent!');

            unset($this->mentorshipMatches);
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function acceptRequest(int $matchId): void
    {
        try {
            $match = MentorshipMatch::findOrFail($matchId);

            $this->mentorshipService->acceptMentorship($match, auth()->user());

            $this->dispatch('notify', type: 'success', message: 'Mentorship accepted! A conversation has been created.');

            unset($this->pendingRequests, $this->mentorshipMatches, $this->stats);
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function rejectRequest(int $matchId): void
    {
        try {
            $match = MentorshipMatch::findOrFail($matchId);

            $this->mentorshipService->rejectMentorship($match, auth()->user());

            $this->dispatch('notify', type: 'success', message: 'Mentorship request declined.');

            unset($this->pendingRequests);
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function completeMentorship(int $matchId): void
    {
        try {
            $match = MentorshipMatch::findOrFail($matchId);

            $this->mentorshipService->completeMentorship($match, auth()->user());

            $this->dispatch('notify', type: 'success', message: 'Mentorship marked as complete. Great work!');

            unset($this->mentorshipMatches, $this->stats);
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function recordMeeting(int $matchId): void
    {
        try {
            $match = MentorshipMatch::findOrFail($matchId);

            if ($match->mentor_id !== auth()->id() && $match->mentee_id !== auth()->id()) {
                throw new \Exception('Not authorized');
            }

            $match->recordMeeting();

            $this->dispatch('notify', type: 'success', message: 'Meeting recorded!');

            unset($this->mentorshipMatches);
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function addMilestone(int $matchId, string $title): void
    {
        try {
            $match = MentorshipMatch::findOrFail($matchId);

            if ($match->mentor_id !== auth()->id() && $match->mentee_id !== auth()->id()) {
                throw new \Exception('Not authorized');
            }

            $match->addMilestone($title);

            $this->dispatch('notify', type: 'success', message: 'Milestone added!');

            unset($this->mentorshipMatches);
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function getAvailableGoals(): array
    {
        return [
            'career_transition' => 'Career Transition',
            'skill_development' => 'Skill Development',
            'leadership_growth' => 'Leadership Growth',
            'industry_insights' => 'Industry Insights',
            'interview_prep' => 'Interview Preparation',
            'salary_negotiation' => 'Salary Negotiation',
            'work_life_balance' => 'Work-Life Balance',
            'networking' => 'Professional Networking',
            'entrepreneurship' => 'Entrepreneurship',
            'job_search' => 'Job Search Strategy',
        ];
    }

    public function render(): View
    {
        return view('livewire.network.mentorship-hub');
    }
}
