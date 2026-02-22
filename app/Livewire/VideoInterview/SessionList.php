<?php

declare(strict_types=1);

namespace App\Livewire\VideoInterview;

use App\Models\VideoInterviewSession;
use App\Services\VideoInterviewService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class SessionList extends Component
{
    use WithPagination;

    public string $statusFilter = '';
    public string $typeFilter = '';
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = VideoInterviewSession::forUser(Auth::id())
            ->with(['job', 'company'])
            ->latest();

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }

        if ($this->search) {
            $query->where('title', 'like', "%{$this->search}%");
        }

        return view('livewire.video-interview.session-list', [
            'sessions' => $query->paginate(10),
            'statuses' => [
                VideoInterviewSession::STATUS_PENDING => 'Pending',
                VideoInterviewSession::STATUS_IN_PROGRESS => 'In Progress',
                VideoInterviewSession::STATUS_COMPLETED => 'Completed',
                VideoInterviewSession::STATUS_EXPIRED => 'Expired',
            ],
            'types' => [
                VideoInterviewSession::TYPE_ASYNC => 'Async',
                VideoInterviewSession::TYPE_LIVE => 'Live',
                VideoInterviewSession::TYPE_MOCK => 'Practice',
            ],
        ]);
    }
}
