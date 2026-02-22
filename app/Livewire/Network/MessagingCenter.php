<?php

declare(strict_types=1);

namespace App\Livewire\Network;

use App\Models\NetworkConversation;
use App\Models\NetworkMessage;
use App\Models\User;
use App\Services\NetworkingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class MessagingCenter extends Component
{
    use WithFileUploads;
    use WithPagination;

    public ?int $activeConversationId = null;
    public string $messageContent = '';
    public ?int $replyingToId = null;
    public $attachment = null;
    public string $search = '';
    public ?int $startConversationWith = null;

    protected NetworkingService $networkingService;

    public function boot(NetworkingService $networkingService): void
    {
        $this->networkingService = $networkingService;
    }

    #[Computed]
    public function conversations()
    {
        return $this->networkingService->getConversations(auth()->user(), 30);
    }

    #[Computed]
    public function activeConversation()
    {
        if (! $this->activeConversationId) {
            return null;
        }

        return NetworkConversation::with(['participants.user'])
            ->find($this->activeConversationId);
    }

    #[Computed]
    public function messages()
    {
        if (! $this->activeConversation) {
            return collect();
        }

        return $this->networkingService->getMessages(
            $this->activeConversation,
            auth()->user(),
            50
        );
    }

    #[Computed]
    public function unreadCount()
    {
        return $this->networkingService->getUnreadCount(auth()->user());
    }

    #[Computed]
    public function connectedUsers()
    {
        $connectionIds = $this->networkingService->getConnectionIds(auth()->user());

        return User::whereIn('id', $connectionIds)
            ->where('name', 'like', '%' . $this->search . '%')
            ->limit(10)
            ->get();
    }

    public function selectConversation(int $conversationId): void
    {
        $this->activeConversationId = $conversationId;
        $this->messageContent = '';
        $this->replyingToId = null;

        // Mark as read
        unset($this->messages, $this->unreadCount);
    }

    public function startNewConversation(): void
    {
        $this->startConversationWith = null;
        $this->search = '';
    }

    public function selectUserForConversation(int $userId): void
    {
        $user = User::findOrFail($userId);

        // Get or create conversation
        $conversation = $this->networkingService->getOrCreateConversation(
            auth()->user(),
            $user
        );

        $this->activeConversationId = $conversation->id;
        $this->startConversationWith = null;
        $this->search = '';

        unset($this->conversations, $this->messages);
    }

    public function cancelNewConversation(): void
    {
        $this->startConversationWith = null;
        $this->search = '';
    }

    public function replyTo(int $messageId): void
    {
        $this->replyingToId = $messageId;
    }

    public function cancelReply(): void
    {
        $this->replyingToId = null;
    }

    public function sendMessage(): void
    {
        $this->validate([
            'messageContent' => 'required|string|min:1|max:5000',
            'attachment' => 'nullable|file|max:10240',
        ]);

        if (! $this->activeConversation) {
            return;
        }

        $attachments = null;
        if ($this->attachment) {
            $path = $this->attachment->store('messages/attachments', 'public');
            $attachments = [[
                'type' => $this->attachment->getMimeType(),
                'path' => $path,
                'name' => $this->attachment->getClientOriginalName(),
                'size' => $this->attachment->getSize(),
            ]];
        }

        $this->networkingService->sendMessage(
            auth()->user(),
            $this->activeConversation,
            $this->messageContent,
            $attachments,
            $this->replyingToId
        );

        $this->reset(['messageContent', 'replyingToId', 'attachment']);

        unset($this->messages, $this->conversations);

        $this->dispatch('message-sent');
    }

    public function deleteMessage(int $messageId): void
    {
        $message = NetworkMessage::where('id', $messageId)
            ->where('sender_id', auth()->id())
            ->firstOrFail();

        $message->delete();

        unset($this->messages);
        $this->dispatch('notify', type: 'success', message: 'Message deleted.');
    }

    public function reactToMessage(int $messageId, string $reaction): void
    {
        $message = NetworkMessage::findOrFail($messageId);

        $reactions = $message->reactions ?? [];
        $userId = (string) auth()->id();

        if (isset($reactions[$userId]) && $reactions[$userId] === $reaction) {
            // Remove reaction
            unset($reactions[$userId]);
        } else {
            // Add/change reaction
            $reactions[$userId] = $reaction;
        }

        $message->update(['reactions' => $reactions]);

        unset($this->messages);
    }

    public function getOtherParticipant(NetworkConversation $conversation): ?User
    {
        return $conversation->participants
            ->where('user_id', '!=', auth()->id())
            ->first()
            ?->user;
    }

    public function hasUnreadMessages(NetworkConversation $conversation): bool
    {
        $participant = $conversation->participants
            ->where('user_id', auth()->id())
            ->first();

        if (! $participant || ! $conversation->last_message_at) {
            return false;
        }

        if (! $participant->last_read_at) {
            return true;
        }

        return $conversation->last_message_at->gt($participant->last_read_at);
    }

    #[On('echo:conversation.{activeConversationId},MessageSent')]
    public function handleNewMessage(): void
    {
        unset($this->messages, $this->conversations);
    }

    public function render(): View
    {
        return view('livewire.network.messaging-center');
    }
}
