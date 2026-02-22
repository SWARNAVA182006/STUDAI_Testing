<?php

declare(strict_types=1);

namespace App\Livewire\VideoInterview;

use App\Models\VideoInterviewSession;
use App\Models\VideoInterviewAnalysis;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class SessionResults extends Component
{
    public VideoInterviewSession $session;
    public ?string $selectedRecordingId = null;

    public function mount(VideoInterviewSession $session): void
    {
        // Ensure user owns this session
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        $this->session = $session->load([
            'questions',
            'recordings.analysis',
            'recordings.question',
        ]);
    }

    public function selectRecording(int $recordingId): void
    {
        $this->selectedRecordingId = (string) $recordingId;
    }

    public function getOverallStats(): array
    {
        $analyses = $this->session->recordings
            ->pluck('analysis')
            ->filter();

        if ($analyses->isEmpty()) {
            return [
                'overall_score' => null,
                'content_score' => null,
                'confidence_score' => null,
                'clarity_score' => null,
                'questions_analyzed' => 0,
            ];
        }

        return [
            'overall_score' => round($analyses->avg('overall_score') ?? 0, 1),
            'content_score' => round($analyses->avg('content_score') ?? 0, 1),
            'confidence_score' => round($analyses->avg('confidence_score') ?? 0, 1),
            'clarity_score' => round($analyses->avg('clarity_score') ?? 0, 1),
            'eye_contact_score' => round($analyses->avg('eye_contact_score') ?? 0, 1),
            'speech_pace_wpm' => round($analyses->avg('speech_pace_wpm') ?? 0, 0),
            'total_filler_words' => $analyses->sum('filler_word_count'),
            'questions_analyzed' => $analyses->count(),
        ];
    }

    public function getGrade(float $score): string
    {
        return match (true) {
            $score >= 95 => 'A+',
            $score >= 90 => 'A',
            $score >= 85 => 'B+',
            $score >= 80 => 'B',
            $score >= 75 => 'C+',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F',
        };
    }

    public function getGradeColor(float $score): string
    {
        return match (true) {
            $score >= 85 => 'text-green-600',
            $score >= 70 => 'text-yellow-600',
            default => 'text-red-600',
        };
    }

    public function render()
    {
        $selectedRecording = null;
        if ($this->selectedRecordingId) {
            $selectedRecording = $this->session->recordings
                ->firstWhere('id', $this->selectedRecordingId);
        }

        return view('livewire.video-interview.session-results', [
            'stats' => $this->getOverallStats(),
            'selectedRecording' => $selectedRecording,
        ]);
    }
}
