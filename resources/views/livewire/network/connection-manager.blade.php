<div>
    {{-- Tab Navigation --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="flex border-b border-gray-200 dark:border-gray-700">
            <button wire:click="setTab('connections')"
                    class="flex-1 px-4 py-4 text-center font-medium {{ $tab === 'connections' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700' }}">
                My Connections
                <span class="ml-1 text-sm text-gray-400">({{ $this->connections->total() }})</span>
            </button>
            <button wire:click="setTab('pending')"
                    class="flex-1 px-4 py-4 text-center font-medium {{ $tab === 'pending' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700' }}">
                Pending
                @if($this->pendingRequests->count() > 0)
                    <span class="ml-1 inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        {{ $this->pendingRequests->count() }}
                    </span>
                @endif
            </button>
            <button wire:click="setTab('sent')"
                    class="flex-1 px-4 py-4 text-center font-medium {{ $tab === 'sent' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700' }}">
                Sent Requests
            </button>
            <button wire:click="setTab('suggestions')"
                    class="flex-1 px-4 py-4 text-center font-medium {{ $tab === 'suggestions' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700' }}">
                People You May Know
            </button>
        </div>
    </div>

    {{-- Connections Tab --}}
    @if($tab === 'connections')
        <div class="space-y-4">
            @forelse($this->connections as $connection)
                @php
                    $connectedUser = $connection->sender_id === auth()->id() ? $connection->recipient : $connection->sender;
                @endphp
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('network.profile.show', $connectedUser) }}">
                                <img src="{{ $connectedUser->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($connectedUser->name) }}"
                                     alt="{{ $connectedUser->name }}"
                                     class="w-14 h-14 rounded-full">
                            </a>
                            <div>
                                <a href="{{ route('network.profile.show', $connectedUser) }}"
                                   class="font-semibold text-gray-900 dark:text-white hover:underline">
                                    {{ $connectedUser->name }}
                                </a>
                                <p class="text-sm text-gray-500">
                                    {{ $connectedUser->candidateProfile?->current_title ?? 'Professional' }}
                                    @if($connectedUser->candidateProfile?->company)
                                        at {{ $connectedUser->candidateProfile->company }}
                                    @endif
                                </p>
                                <p class="text-xs text-gray-400">
                                    Connected {{ $connection->connected_at?->diffForHumans() ?? $connection->updated_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2">
                            <a href="{{ route('candidate.network.messages', ['conversation' => $connectedUser->id]) }}"
                               class="px-4 py-2 text-sm font-medium text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition">
                                Message
                            </a>
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open"
                                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                                    <x-heroicon-o-ellipsis-horizontal class="h-5 w-5 text-gray-500" />
                                </button>
                                <div x-show="open"
                                     @click.away="open = false"
                                     class="absolute right-0 mt-1 w-48 bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 py-1 z-10">
                                    <button wire:click="removeConnection({{ $connection->id }})"
                                            wire:confirm="Remove this connection?"
                                            class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600">
                                        Remove Connection
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <x-heroicon-o-users class="h-16 w-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No connections yet</h3>
                    <p class="text-gray-500 mb-4">Start building your professional network by connecting with others.</p>
                    <button wire:click="setTab('suggestions')"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                        Find People to Connect
                    </button>
                </div>
            @endforelse

            {{ $this->connections->links() }}
        </div>
    @endif

    {{-- Pending Requests Tab --}}
    @if($tab === 'pending')
        <div class="space-y-4">
            @forelse($this->pendingRequests as $request)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('network.profile.show', $request->sender) }}">
                                <img src="{{ $request->sender->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($request->sender->name) }}"
                                     alt="{{ $request->sender->name }}"
                                     class="w-14 h-14 rounded-full">
                            </a>
                            <div>
                                <a href="{{ route('network.profile.show', $request->sender) }}"
                                   class="font-semibold text-gray-900 dark:text-white hover:underline">
                                    {{ $request->sender->name }}
                                </a>
                                <p class="text-sm text-gray-500">
                                    {{ $request->sender->candidateProfile?->current_title ?? 'Professional' }}
                                </p>
                                @php
                                    $mutualCount = $this->getMutualConnectionsCount($request->sender->id);
                                @endphp
                                @if($mutualCount > 0)
                                    <p class="text-xs text-gray-400">
                                        {{ $mutualCount }} mutual {{ Str::plural('connection', $mutualCount) }}
                                    </p>
                                @endif
                                @if($request->message)
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 italic">
                                        "{{ $request->message }}"
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center space-x-2">
                            <button wire:click="acceptRequest({{ $request->id }})"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                                Accept
                            </button>
                            <button wire:click="rejectRequest({{ $request->id }})"
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 text-sm font-medium rounded-lg transition">
                                Ignore
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <x-heroicon-o-inbox class="h-16 w-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No pending requests</h3>
                    <p class="text-gray-500">When someone wants to connect, you'll see their request here.</p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- Sent Requests Tab --}}
    @if($tab === 'sent')
        <div class="space-y-4">
            @forelse($this->sentRequests as $request)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('network.profile.show', $request->recipient) }}">
                                <img src="{{ $request->recipient->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($request->recipient->name) }}"
                                     alt="{{ $request->recipient->name }}"
                                     class="w-14 h-14 rounded-full">
                            </a>
                            <div>
                                <a href="{{ route('network.profile.show', $request->recipient) }}"
                                   class="font-semibold text-gray-900 dark:text-white hover:underline">
                                    {{ $request->recipient->name }}
                                </a>
                                <p class="text-sm text-gray-500">
                                    {{ $request->recipient->candidateProfile?->current_title ?? 'Professional' }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    Sent {{ $request->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>

                        <button wire:click="withdrawRequest({{ $request->id }})"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 transition">
                            Withdraw
                        </button>
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <x-heroicon-o-paper-airplane class="h-16 w-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No sent requests</h3>
                    <p class="text-gray-500">Requests you send will appear here until they're accepted.</p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- Suggestions Tab --}}
    @if($tab === 'suggestions')
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($this->suggestions as $user)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <div class="text-center">
                        <a href="{{ route('network.profile.show', $user) }}">
                            <img src="{{ $user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) }}"
                                 alt="{{ $user->name }}"
                                 class="w-20 h-20 rounded-full mx-auto mb-3">
                        </a>
                        <a href="{{ route('network.profile.show', $user) }}"
                           class="font-semibold text-gray-900 dark:text-white hover:underline">
                            {{ $user->name }}
                        </a>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ $user->candidateProfile?->current_title ?? 'Professional' }}
                        </p>
                        @php
                            $mutualCount = $this->getMutualConnectionsCount($user->id);
                        @endphp
                        @if($mutualCount > 0)
                            <p class="text-xs text-indigo-600 mt-1">
                                {{ $mutualCount }} mutual {{ Str::plural('connection', $mutualCount) }}
                            </p>
                        @endif

                        <button wire:click="openSendRequest({{ $user->id }})"
                                class="mt-4 w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                            Connect
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <x-heroicon-o-user-group class="h-16 w-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No suggestions available</h3>
                    <p class="text-gray-500">We'll show personalized suggestions as your network grows.</p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- Send Request Modal --}}
    @if($sendingRequestTo)
        @php
            $targetUser = \App\Models\User::find($sendingRequestTo);
        @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeSendRequest"></div>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="p-6">
                        <div class="flex items-center space-x-4 mb-4">
                            <img src="{{ $targetUser->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($targetUser->name) }}"
                                 alt="{{ $targetUser->name }}"
                                 class="w-14 h-14 rounded-full">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                    Connect with {{ $targetUser->name }}
                                </h3>
                                <p class="text-sm text-gray-500">
                                    {{ $targetUser->candidateProfile?->current_title ?? 'Professional' }}
                                </p>
                            </div>
                        </div>

                        <textarea wire:model="connectionMessage"
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                  placeholder="Add a note to personalize your invitation (optional)..."></textarea>

                        <div class="mt-4 flex items-center justify-end space-x-3">
                            <button wire:click="closeSendRequest"
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                                Cancel
                            </button>
                            <button wire:click="sendRequest"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                                Send Request
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
