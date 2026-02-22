<?php

declare(strict_types=1);

namespace App\Livewire\Network;

use App\Models\Connection;
use App\Models\User;
use App\Services\NetworkingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ConnectionManager extends Component
{
    use WithPagination;

    public string $tab = 'connections';
    public string $search = '';
    public ?string $connectionMessage = null;
    public ?int $sendingRequestTo = null;

    protected NetworkingService $networkingService;

    public function boot(NetworkingService $networkingService): void
    {
        $this->networkingService = $networkingService;
    }

    #[Computed]
    public function connections()
    {
        return $this->networkingService->getConnections(auth()->user(), 20);
    }

    #[Computed]
    public function pendingRequests()
    {
        return $this->networkingService->getPendingRequests(auth()->user());
    }

    #[Computed]
    public function sentRequests()
    {
        return $this->networkingService->getSentRequests(auth()->user());
    }

    #[Computed]
    public function suggestions()
    {
        return $this->networkingService->getConnectionSuggestions(auth()->user(), 10);
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    public function openSendRequest(int $userId): void
    {
        $this->sendingRequestTo = $userId;
        $this->connectionMessage = null;
    }

    public function closeSendRequest(): void
    {
        $this->sendingRequestTo = null;
        $this->connectionMessage = null;
    }

    public function sendRequest(): void
    {
        if (! $this->sendingRequestTo) {
            return;
        }

        try {
            $recipient = User::findOrFail($this->sendingRequestTo);

            $this->networkingService->sendConnectionRequest(
                auth()->user(),
                $recipient,
                $this->connectionMessage
            );

            $this->dispatch('notify', type: 'success', message: 'Connection request sent!');
            $this->closeSendRequest();

            // Refresh suggestions
            unset($this->suggestions);
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function acceptRequest(int $connectionId): void
    {
        try {
            $connection = Connection::findOrFail($connectionId);

            $this->networkingService->acceptConnection($connection, auth()->user());

            $this->dispatch('notify', type: 'success', message: 'Connection accepted!');

            // Refresh lists
            unset($this->pendingRequests, $this->connections);
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function rejectRequest(int $connectionId): void
    {
        try {
            $connection = Connection::findOrFail($connectionId);

            $this->networkingService->rejectConnection($connection, auth()->user());

            $this->dispatch('notify', type: 'success', message: 'Request declined.');

            unset($this->pendingRequests);
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function withdrawRequest(int $connectionId): void
    {
        try {
            $connection = Connection::where('id', $connectionId)
                ->where('sender_id', auth()->id())
                ->where('status', Connection::STATUS_PENDING)
                ->firstOrFail();

            $connection->delete();

            $this->dispatch('notify', type: 'success', message: 'Request withdrawn.');

            unset($this->sentRequests);
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function removeConnection(int $connectionId): void
    {
        try {
            $connection = Connection::findOrFail($connectionId);

            $this->networkingService->removeConnection($connection, auth()->user());

            $this->dispatch('notify', type: 'success', message: 'Connection removed.');

            unset($this->connections);
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function getMutualConnectionsCount(int $userId): int
    {
        $user = User::find($userId);
        if (! $user) {
            return 0;
        }

        return $this->networkingService->getMutualConnections(auth()->user(), $user)->count();
    }

    public function render(): View
    {
        return view('livewire.network.connection-manager');
    }
}
