<?php

declare(strict_types=1);

namespace App\Livewire\VideoInterview;

use App\Models\VideoInterviewSession;
use App\Services\VideoInterviewService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;

#[Layout('layouts.app')]
class CreateMockInterview extends Component
{
    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string')]
    public ?string $roleType = null;

    #[Validate('nullable|exists:companies,id')]
    public ?int $companyId = null;

    public array $roleTypes = [
        'general' => 'General Interview',
        'technical' => 'Technical/Engineering',
        'management' => 'Management/Leadership',
        'sales' => 'Sales/Business Development',
        'creative' => 'Creative/Design',
        'customer_service' => 'Customer Service',
    ];

    protected VideoInterviewService $videoService;

    public function boot(VideoInterviewService $videoService): void
    {
        $this->videoService = $videoService;
    }

    public function createMockInterview(): void
    {
        $this->validate();

        $session = $this->videoService->createMockSession(
            user: Auth::user(),
            title: $this->title ?: 'Practice Interview - ' . now()->format('M d, Y'),
            roleType: $this->roleType,
            companyId: $this->companyId
        );

        session()->flash('success', 'Practice interview created! Good luck!');
        $this->redirect(route('video-interview.record', $session));
    }

    public function render()
    {
        return view('livewire.video-interview.create-mock-interview');
    }
}
