<div class="flex h-[calc(100vh-200px)] bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    {{-- Conversations List --}}
    <div class="w-80 border-r border-gray-200 dark:border-gray-700 flex flex-col">
        {{-- Header --}}
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Messages</h2>
                <button wire:click="startNewConversation"
                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    <x-heroicon-o-pencil-square class="h-5 w-5 text-gray-500" />
                </button>
            </div>

            @if($startConversationWith === null && !$activeConversationId)
                <div class="relative">
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Search messages..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <x-heroicon-o-magnifying-glass class="h-5 w-5 text-gray-400 absolute left-3 top-2.5" />
                </div>
            @endif
        </div>

        {{-- New Conversation User Search --}}
        @if($startConversationWith === null && $search === '' && !$activeConversationId)
            {{-- This state means we clicked new conversation --}}
        @endif

        @if($startConversationWith === null && $activeConversationId === null)
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                <p class="text-sm text-gray-500 mb-2">Start a conversation with:</p>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search connections..."
                       class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">

                @if($search)
                    <div class="mt-2 space-y-1">
                        @foreach($this->connectedUsers as $user)
                            <button wire:click="selectUserForConversation({{ $user->id }})"
                                    class="w-full flex items-center space-x-3 p-2 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition">
                                <img src="{{ $user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) }}"
                                     class="w-8 h-8 rounded-full">
                                <span class="text-sm text-gray-900 dark:text-white">{{ $user->name }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                <button wire:click="$set('activeConversationId', null)"
                        class="mt-2 text-sm text-gray-500 hover:text-gray-700">
                    Cancel
                </button>
            </div>
        @endif

        {{-- Conversations List --}}
        <div class="flex-1 overflow-y-auto">
            @foreach($this->conversations as $conversation)
                @php
                    $otherUser = $this->getOtherParticipant($conversation);
                    $hasUnread = $this->hasUnreadMessages($conversation);
                @endphp
                <button wire:click="selectConversation({{ $conversation->id }})"
                        class="w-full flex items-center space-x-3 p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition {{ $activeConversationId === $conversation->id ? 'bg-indigo-50 dark:bg-indigo-900/30' : '' }}">
                    <div class="relative">
                        <img src="{{ $otherUser?->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($otherUser?->name ?? 'U') }}"
                             alt="{{ $otherUser?->name }}"
                             class="w-12 h-12 rounded-full">
                        @if($hasUnread)
                            <span class="absolute top-0 right-0 w-3 h-3 bg-indigo-600 rounded-full border-2 border-white dark:border-gray-800"></span>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0 text-left">
                        <p class="font-medium text-gray-900 dark:text-white truncate {{ $hasUnread ? 'font-semibold' : '' }}">
                            {{ $otherUser?->name ?? 'Unknown User' }}
                        </p>
                        @if($conversation->lastMessage)
                            <p class="text-sm text-gray-500 truncate {{ $hasUnread ? 'font-medium text-gray-700 dark:text-gray-300' : '' }}">
                                @if($conversation->lastMessage->sender_id === auth()->id())
                                    You:
                                @endif
                                {{ Str::limit($conversation->lastMessage->content, 30) }}
                            </p>
                        @endif
                    </div>
                    <div class="text-xs text-gray-400">
                        {{ $conversation->last_message_at?->shortRelativeDiffForHumans() }}
                    </div>
                </button>
            @endforeach

            @if($this->conversations->isEmpty())
                <div class="p-8 text-center">
                    <x-heroicon-o-chat-bubble-left-right class="h-12 w-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                    <p class="text-sm text-gray-500">No conversations yet</p>
                    <p class="text-xs text-gray-400 mt-1">Start a conversation with your connections</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Message Area --}}
    <div class="flex-1 flex flex-col">
        @if($this->activeConversation)
            @php
                $chatPartner = $this->getOtherParticipant($this->activeConversation);
            @endphp

            {{-- Chat Header --}}
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <a href="{{ $chatPartner ? route('network.profile.show', $chatPartner) : '#' }}">
                        <img src="{{ $chatPartner?->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($chatPartner?->name ?? 'U') }}"
                             class="w-10 h-10 rounded-full">
                    </a>
                    <div>
                        <a href="{{ $chatPartner ? route('network.profile.show', $chatPartner) : '#' }}"
                           class="font-medium text-gray-900 dark:text-white hover:underline">
                            {{ $chatPartner?->name ?? 'Unknown User' }}
                        </a>
                        <p class="text-xs text-gray-500">
                            {{ $chatPartner?->candidateProfile?->current_title ?? 'Professional' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Messages --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-4" id="messages-container">
                @foreach($this->messages->reverse() as $message)
                    @php
                        $isOwn = $message->sender_id === auth()->id();
                    @endphp
                    <div class="flex {{ $isOwn ? 'justify-end' : 'justify-start' }}">
                        <div class="flex items-end space-x-2 max-w-[70%] {{ $isOwn ? 'flex-row-reverse space-x-reverse' : '' }}">
                            @if(!$isOwn)
                                <img src="{{ $message->sender?->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($message->sender?->name ?? 'U') }}"
                                     class="w-8 h-8 rounded-full">
                            @endif

                            <div class="group relative">
                                {{-- Reply indicator --}}
                                @if($message->replyTo)
                                    <div class="mb-1 px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg text-xs text-gray-500 border-l-2 border-indigo-500">
                                        <p class="font-medium">{{ $message->replyTo->sender?->name }}</p>
                                        <p class="truncate">{{ Str::limit($message->replyTo->content, 50) }}</p>
                                    </div>
                                @endif

                                <div class="{{ $isOwn ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' }} rounded-2xl px-4 py-2">
                                    <p class="text-sm whitespace-pre-wrap">{{ $message->content }}</p>

                                    {{-- Attachments --}}
                                    @if($message->attachments)
                                        <div class="mt-2 space-y-1">
                                            @foreach($message->attachments as $attachment)
                                                <a href="{{ asset('storage/' . $attachment['path']) }}"
                                                   target="_blank"
                                                   class="flex items-center space-x-2 text-sm {{ $isOwn ? 'text-indigo-100' : 'text-indigo-600' }} hover:underline">
                                                    <x-heroicon-o-paper-clip class="h-4 w-4" />
                                                    <span>{{ $attachment['name'] }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="flex items-center space-x-2 mt-1 {{ $isOwn ? 'justify-end' : 'justify-start' }}">
                                    <span class="text-xs text-gray-400">{{ $message->created_at->format('g:i A') }}</span>

                                    {{-- Reactions --}}
                                    @if($message->reactions && count($message->reactions) > 0)
                                        <div class="flex items-center space-x-0.5">
                                            @foreach(array_unique($message->reactions) as $reaction)
                                                <span class="text-xs">{{ $reaction }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                {{-- Message Actions --}}
                                <div class="absolute {{ $isOwn ? 'left-0 -translate-x-full pl-2' : 'right-0 translate-x-full pr-2' }} top-1/2 -translate-y-1/2 hidden group-hover:flex items-center space-x-1">
                                    <button wire:click="replyTo({{ $message->id }})"
                                            class="p-1 hover:bg-gray-200 dark:hover:bg-gray-600 rounded"
                                            title="Reply">
                                        <x-heroicon-o-arrow-uturn-left class="h-4 w-4 text-gray-500" />
                                    </button>
                                    <div class="relative" x-data="{ showReactions: false }">
                                        <button @click="showReactions = !showReactions"
                                                class="p-1 hover:bg-gray-200 dark:hover:bg-gray-600 rounded"
                                                title="React">
                                            <x-heroicon-o-face-smile class="h-4 w-4 text-gray-500" />
                                        </button>
                                        <div x-show="showReactions"
                                             @click.away="showReactions = false"
                                             class="absolute bottom-full mb-1 left-1/2 -translate-x-1/2 bg-white dark:bg-gray-700 rounded-full shadow-lg px-2 py-1 flex space-x-1">
                                            @foreach(['👍', '❤️', '😂', '😮', '😢', '🙏'] as $emoji)
                                                <button wire:click="reactToMessage({{ $message->id }}, '{{ $emoji }}')"
                                                        class="text-lg hover:scale-125 transition-transform">
                                                    {{ $emoji }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                    @if($isOwn)
                                        <button wire:click="deleteMessage({{ $message->id }})"
                                                wire:confirm="Delete this message?"
                                                class="p-1 hover:bg-gray-200 dark:hover:bg-gray-600 rounded"
                                                title="Delete">
                                            <x-heroicon-o-trash class="h-4 w-4 text-red-500" />
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Reply Indicator --}}
            @if($replyingToId)
                @php
                    $replyingTo = \App\Models\NetworkMessage::find($replyingToId);
                @endphp
                <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-600 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-1 h-8 bg-indigo-500 rounded"></div>
                        <div>
                            <p class="text-xs text-gray-500">Replying to {{ $replyingTo?->sender?->name }}</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ Str::limit($replyingTo?->content, 50) }}</p>
                        </div>
                    </div>
                    <button wire:click="cancelReply" class="p-1 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">
                        <x-heroicon-o-x-mark class="h-4 w-4 text-gray-500" />
                    </button>
                </div>
            @endif

            {{-- Message Input --}}
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <form wire:submit="sendMessage" class="flex items-end space-x-3">
                    <label class="cursor-pointer p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                        <x-heroicon-o-paper-clip class="h-5 w-5 text-gray-500" />
                        <input type="file" wire:model="attachment" class="hidden">
                    </label>

                    <div class="flex-1">
                        @if($attachment)
                            <div class="mb-2 flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                <x-heroicon-o-document class="h-4 w-4" />
                                <span>{{ $attachment->getClientOriginalName() }}</span>
                                <button type="button" wire:click="$set('attachment', null)" class="text-red-500">
                                    <x-heroicon-o-x-mark class="h-4 w-4" />
                                </button>
                            </div>
                        @endif

                        <textarea wire:model="messageContent"
                                  wire:keydown.enter.prevent="sendMessage"
                                  rows="1"
                                  class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
                                  placeholder="Type a message..."></textarea>
                    </div>

                    <button type="submit"
                            class="p-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition"
                            wire:loading.attr="disabled">
                        <x-heroicon-s-paper-airplane class="h-5 w-5" />
                    </button>
                </form>
            </div>
        @else
            {{-- No Conversation Selected --}}
            <div class="flex-1 flex items-center justify-center">
                <div class="text-center">
                    <x-heroicon-o-chat-bubble-left-right class="h-16 w-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Select a conversation</h3>
                    <p class="text-gray-500">Choose a conversation from the list or start a new one.</p>
                </div>
            </div>
        @endif
    </div>
</div>

@script
<script>
    // Auto-scroll to bottom when new messages arrive
    Livewire.on('message-sent', () => {
        setTimeout(() => {
            const container = document.getElementById('messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }, 100);
    });
</script>
@endscript
