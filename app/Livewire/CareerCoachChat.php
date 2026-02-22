<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\CareerCoachMessage;
use App\Models\CareerCoachSession;
use App\Services\AI\CareerCoachService;
use Livewire\Component;
use Livewire\Attributes\On;

class CareerCoachChat extends Component
{
    public CareerCoachSession $session;
    public string $message = '';
    public bool $isLoading = false;
    public bool $isVoiceInput = false;
    public array $messages = [];

    protected CareerCoachService $coachService;

    public function boot(CareerCoachService $coachService): void
    {
        $this->coachService = $coachService;
    }

    public function mount(CareerCoachSession $session): void
    {
        $this->session = $session;
        $this->loadMessages();
    }

    public function loadMessages(): void
    {
        $this->messages = $this->session->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at')
            ->get()
            ->map(fn($msg) => [
                'id' => $msg->id,
                'role' => $msg->role,
                'content' => $msg->content,
                'created_at' => $msg->created_at->format('g:i A'),
                'is_voice' => $msg->is_voice_input,
            ])
            ->toArray();
    }

    public function sendMessage(): void
    {
        if (empty(trim($this->message))) {
            return;
        }

        $userMessage = trim($this->message);
        $this->message = '';
        $this->isLoading = true;

        // Add user message to UI immediately
        $this->messages[] = [
            'id' => 'temp-' . time(),
            'role' => 'user',
            'content' => $userMessage,
            'created_at' => now()->format('g:i A'),
            'is_voice' => $this->isVoiceInput,
        ];

        $this->dispatch('scroll-to-bottom');

        try {
            $this->coachService->forUser(auth()->user());
            
            $response = $this->coachService->sendMessage(
                $this->session,
                $userMessage,
                $this->isVoiceInput
            );

            // Add assistant message
            $this->messages[] = [
                'id' => $response->id,
                'role' => 'assistant',
                'content' => $response->content,
                'created_at' => $response->created_at->format('g:i A'),
                'is_voice' => false,
            ];

            $this->dispatch('scroll-to-bottom');
            $this->dispatch('message-received');

        } catch (\Exception $e) {
            $this->dispatch('show-error', message: 'Failed to get response. Please try again.');
        } finally {
            $this->isLoading = false;
            $this->isVoiceInput = false;
        }
    }

    #[On('voice-transcribed')]
    public function handleVoiceTranscription(string $text): void
    {
        $this->message = $text;
        $this->isVoiceInput = true;
        $this->sendMessage();
    }

    public function endSession(): void
    {
        try {
            $this->coachService->forUser(auth()->user());
            $this->coachService->generateSessionSummary($this->session);
            $this->session->markCompleted();

            $this->dispatch('session-ended');
            $this->redirect(route('career-coach.index'));
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: 'Failed to end session.');
        }
    }

    public function render()
    {
        return view('livewire.career-coach-chat');
    }
}
