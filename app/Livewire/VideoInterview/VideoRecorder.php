<?php

declare(strict_types=1);

namespace App\Livewire\VideoInterview;

use App\Models\VideoInterviewSession;
use App\Models\VideoInterviewQuestion;
use App\Models\VideoInterviewRecording;
use App\Services\VideoInterviewService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

#[Layout('layouts.app')]
class VideoRecorder extends Component
{
    use WithFileUploads;

    public VideoInterviewSession $session;
    public ?VideoInterviewQuestion $currentQuestion = null;
    public int $currentQuestionIndex = 0;
    public string $status = 'ready'; // ready, preparing, recording, reviewing, uploading, completed
    public int $prepTimeRemaining = 0;
    public int $recordTimeRemaining = 0;
    public int $recordingDuration = 0;
    public int $attemptNumber = 1;
    public bool $showInstructions = true;
    public array $completedQuestions = [];
    public ?string $uploadUrl = null;
    public ?string $errorMessage = null;

    protected VideoInterviewService $videoService;

    public function boot(VideoInterviewService $videoService): void
    {
        $this->videoService = $videoService;
    }

    public function mount(VideoInterviewSession $session): void
    {
        // Ensure user owns this session
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        // Ensure session can be started
        if (!$session->canStart() && $session->status !== VideoInterviewSession::STATUS_IN_PROGRESS) {
            session()->flash('error', 'This interview session cannot be started.');
            $this->redirect(route('video-interview.sessions'));
            return;
        }

        $this->session = $session->load('questions');
        
        // Start session if pending
        if ($session->status === VideoInterviewSession::STATUS_PENDING) {
            $this->videoService->startSession($session);
            $this->session->refresh();
        }

        // Find first unanswered question or current progress
        $this->loadProgress();
    }

    protected function loadProgress(): void
    {
        $answeredQuestionIds = $this->session->recordings()
            ->whereNotNull('video_interview_question_id')
            ->pluck('video_interview_question_id')
            ->toArray();

        $this->completedQuestions = $answeredQuestionIds;

        // Find first unanswered question
        foreach ($this->session->questions as $index => $question) {
            if (!in_array($question->id, $answeredQuestionIds)) {
                $this->currentQuestionIndex = $index;
                $this->currentQuestion = $question;
                break;
            }
        }

        // All questions answered
        if ($this->currentQuestion === null && $this->session->questions->isNotEmpty()) {
            $this->status = 'completed';
        }
    }

    public function startInterview(): void
    {
        $this->showInstructions = false;
        $this->loadCurrentQuestion();
    }

    public function loadCurrentQuestion(): void
    {
        if ($this->currentQuestionIndex >= $this->session->questions->count()) {
            $this->status = 'completed';
            return;
        }

        $this->currentQuestion = $this->session->questions[$this->currentQuestionIndex];
        $this->prepTimeRemaining = $this->currentQuestion->prep_time_seconds;
        $this->recordTimeRemaining = $this->currentQuestion->max_response_time_seconds;
        $this->status = 'ready';
        $this->attemptNumber = $this->getAttemptNumber();
    }

    protected function getAttemptNumber(): int
    {
        if (!$this->currentQuestion) {
            return 1;
        }

        return $this->session->recordings()
            ->where('video_interview_question_id', $this->currentQuestion->id)
            ->count() + 1;
    }

    public function startPreparation(): void
    {
        $this->status = 'preparing';
        $this->dispatch('start-prep-timer', seconds: $this->prepTimeRemaining);
    }

    #[On('prep-timer-complete')]
    public function onPrepComplete(): void
    {
        $this->startRecording();
    }

    public function skipPreparation(): void
    {
        $this->startRecording();
    }

    public function startRecording(): void
    {
        $this->status = 'recording';
        $this->recordingDuration = 0;
        $this->dispatch('start-recording', maxDuration: $this->currentQuestion?->max_response_time_seconds ?? 180);
    }

    #[On('recording-complete')]
    public function onRecordingComplete(int $duration): void
    {
        $this->recordingDuration = $duration;
        $this->status = 'reviewing';
    }

    #[On('recording-stopped')]
    public function onRecordingStopped(int $duration): void
    {
        $this->recordingDuration = $duration;
        $this->status = 'reviewing';
    }

    public function stopRecording(): void
    {
        $this->dispatch('stop-recording');
    }

    public function retakeRecording(): void
    {
        if (!$this->currentQuestion) {
            return;
        }

        $maxRetakes = $this->currentQuestion->max_retakes;
        if ($this->attemptNumber > $maxRetakes) {
            $this->errorMessage = 'Maximum retakes reached for this question.';
            return;
        }

        $this->attemptNumber++;
        $this->status = 'ready';
        $this->dispatch('reset-recording');
    }

    public function submitRecording(): void
    {
        $this->status = 'uploading';
        $this->dispatch('submit-recording');
    }

    #[On('upload-started')]
    public function onUploadStarted(): void
    {
        $this->status = 'uploading';
    }

    #[On('upload-complete')]
    public function onUploadComplete(string $filePath, string $fileName, int $fileSize): void
    {
        try {
            // Create recording record
            VideoInterviewRecording::create([
                'video_interview_session_id' => $this->session->id,
                'video_interview_question_id' => $this->currentQuestion?->id,
                'user_id' => Auth::id(),
                'recording_type' => 'response',
                'attempt_number' => $this->attemptNumber,
                'status' => VideoInterviewRecording::STATUS_UPLOADED,
                'storage_disk' => config('video-interview.storage_disk', 'local'),
                'file_path' => $filePath,
                'file_name' => $fileName,
                'mime_type' => 'video/webm',
                'file_size' => $fileSize,
                'duration_seconds' => $this->recordingDuration,
            ]);

            $this->completedQuestions[] = $this->currentQuestion?->id;
            $this->moveToNextQuestion();

        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to save recording. Please try again.';
            $this->status = 'reviewing';
        }
    }

    #[On('upload-error')]
    public function onUploadError(string $error): void
    {
        $this->errorMessage = 'Upload failed: ' . $error;
        $this->status = 'reviewing';
    }

    public function skipQuestion(): void
    {
        if (!$this->currentQuestion?->allow_skip) {
            $this->errorMessage = 'This question cannot be skipped.';
            return;
        }

        $this->moveToNextQuestion();
    }

    protected function moveToNextQuestion(): void
    {
        $this->currentQuestionIndex++;
        
        if ($this->currentQuestionIndex >= $this->session->questions->count()) {
            $this->completeInterview();
            return;
        }

        $this->loadCurrentQuestion();
        $this->dispatch('reset-recording');
    }

    protected function completeInterview(): void
    {
        $this->videoService->completeSession($this->session);
        $this->session->refresh();
        $this->status = 'completed';
    }

    public function getProgressPercentage(): int
    {
        $total = $this->session->questions->count();
        if ($total === 0) {
            return 100;
        }
        return (int) round((count($this->completedQuestions) / $total) * 100);
    }

    public function render()
    {
        return view('livewire.video-interview.video-recorder', [
            'totalQuestions' => $this->session->questions->count(),
            'progressPercentage' => $this->getProgressPercentage(),
        ]);
    }
}
