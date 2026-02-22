<div>
    {{-- Tab Navigation --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="flex border-b border-gray-200 dark:border-gray-700">
            <button wire:click="setTab('discover')"
                    class="flex-1 px-4 py-4 text-center font-medium {{ $tab === 'discover' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700' }}">
                Discover Groups
            </button>
            <button wire:click="setTab('my-groups')"
                    class="flex-1 px-4 py-4 text-center font-medium {{ $tab === 'my-groups' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700' }}">
                My Groups
                <span class="ml-1 text-sm text-gray-400">({{ $this->myGroups->count() }})</span>
            </button>
        </div>
    </div>

    {{-- Discover Tab --}}
    @if($tab === 'discover')
        {{-- Search & Filters --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1 relative">
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Search groups..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <x-heroicon-o-magnifying-glass class="h-5 w-5 text-gray-400 absolute left-3 top-2.5" />
                </div>

                <select wire:model.live="industryFilter"
                        class="px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Industries</option>
                    @foreach($this->industries as $industry)
                        <option value="{{ $industry }}">{{ $industry }}</option>
                    @endforeach
                </select>

                <button wire:click="openCreateForm"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition flex items-center space-x-2">
                    <x-heroicon-o-plus class="h-5 w-5" />
                    <span>Create Group</span>
                </button>
            </div>

            @if($search || $industryFilter)
                <div class="mt-3 flex items-center space-x-2">
                    <span class="text-sm text-gray-500">Filters:</span>
                    @if($search)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                            "{{ $search }}"
                            <button wire:click="$set('search', '')" class="ml-1">
                                <x-heroicon-s-x-mark class="h-3 w-3" />
                            </button>
                        </span>
                    @endif
                    @if($industryFilter)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                            {{ $industryFilter }}
                            <button wire:click="$set('industryFilter', null)" class="ml-1">
                                <x-heroicon-s-x-mark class="h-3 w-3" />
                            </button>
                        </span>
                    @endif
                    <button wire:click="clearFilters" class="text-xs text-indigo-600 hover:underline">Clear all</button>
                </div>
            @endif
        </div>

        {{-- Groups Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($this->discoverGroups as $group)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    {{-- Cover Image --}}
                    <div class="h-32 bg-gradient-to-r from-indigo-500 to-purple-600 relative">
                        @if($group->cover_image)
                            <img src="{{ asset('storage/' . $group->cover_image) }}"
                                 alt="{{ $group->name }}"
                                 class="w-full h-full object-cover">
                        @endif
                        @if($group->privacy === 'private')
                            <div class="absolute top-2 right-2 px-2 py-1 bg-black/50 rounded text-white text-xs flex items-center space-x-1">
                                <x-heroicon-s-lock-closed class="h-3 w-3" />
                                <span>Private</span>
                            </div>
                        @endif
                    </div>

                    <div class="p-4">
                        <h3 class="font-semibold text-lg text-gray-900 dark:text-white mb-1">
                            {{ $group->name }}
                        </h3>
                        @if($group->industry)
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 mb-2">
                                {{ $group->industry }}
                            </span>
                        @endif
                        <p class="text-sm text-gray-500 mb-3 line-clamp-2">
                            {{ $group->description ?? 'No description available.' }}
                        </p>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-400">
                                {{ $group->members_count }} {{ Str::plural('member', $group->members_count) }}
                            </span>

                            @if($this->isMember($group->id))
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('candidate.network.groups.show', $group) }}"
                                       class="px-3 py-1.5 text-sm font-medium text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition">
                                        View
                                    </a>
                                    <button wire:click="leaveGroup({{ $group->id }})"
                                            wire:confirm="Leave this group?"
                                            class="px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                                        Leave
                                    </button>
                                </div>
                            @elseif($this->isPending($group->id))
                                <span class="px-3 py-1.5 text-sm font-medium text-yellow-600 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg">
                                    Pending
                                </span>
                            @else
                                <button wire:click="joinGroup({{ $group->id }})"
                                        class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                                    {{ $group->privacy === 'private' ? 'Request to Join' : 'Join' }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <x-heroicon-o-user-group class="h-16 w-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No groups found</h3>
                    <p class="text-gray-500 mb-4">Try adjusting your search or create a new group.</p>
                    <button wire:click="openCreateForm"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                        <x-heroicon-o-plus class="h-5 w-5 mr-2" />
                        Create a Group
                    </button>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $this->discoverGroups->links() }}
        </div>
    @endif

    {{-- My Groups Tab --}}
    @if($tab === 'my-groups')
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($this->myGroups as $group)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="h-32 bg-gradient-to-r from-indigo-500 to-purple-600 relative">
                        @if($group->cover_image)
                            <img src="{{ asset('storage/' . $group->cover_image) }}"
                                 alt="{{ $group->name }}"
                                 class="w-full h-full object-cover">
                        @endif
                        @if($group->owner_id === auth()->id())
                            <div class="absolute top-2 right-2 px-2 py-1 bg-indigo-600 rounded text-white text-xs">
                                Owner
                            </div>
                        @endif
                    </div>

                    <div class="p-4">
                        <h3 class="font-semibold text-lg text-gray-900 dark:text-white mb-1">
                            {{ $group->name }}
                        </h3>
                        @if($group->industry)
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 mb-2">
                                {{ $group->industry }}
                            </span>
                        @endif
                        <p class="text-sm text-gray-500 mb-3 line-clamp-2">
                            {{ $group->description ?? 'No description available.' }}
                        </p>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-400">
                                {{ $group->members_count }} {{ Str::plural('member', $group->members_count) }}
                            </span>
                            <a href="{{ route('candidate.network.groups.show', $group) }}"
                               class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                                Open Group
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <x-heroicon-o-user-group class="h-16 w-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">You haven't joined any groups yet</h3>
                    <p class="text-gray-500 mb-4">Join groups to connect with professionals in your industry.</p>
                    <button wire:click="setTab('discover')"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                        Discover Groups
                    </button>
                </div>
            @endforelse
        </div>
    @endif

    {{-- Create Group Modal --}}
    @if($showCreateForm)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeCreateForm"></div>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit="createGroup">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">Create a New Group</h3>

                            {{-- Cover Image --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Cover Image (optional)
                                </label>
                                <div class="flex items-center justify-center w-full">
                                    <label class="w-full h-32 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-indigo-500 transition flex items-center justify-center overflow-hidden">
                                        @if($groupCover)
                                            <img src="{{ $groupCover->temporaryUrl() }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="text-center">
                                                <x-heroicon-o-photo class="h-8 w-8 text-gray-400 mx-auto mb-2" />
                                                <span class="text-sm text-gray-500">Click to upload</span>
                                            </div>
                                        @endif
                                        <input type="file" wire:model="groupCover" accept="image/*" class="hidden">
                                    </label>
                                </div>
                            </div>

                            {{-- Group Name --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Group Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       wire:model="groupName"
                                       class="w-full px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                       placeholder="e.g., Tech Professionals Network">
                                @error('groupName')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Description --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Description
                                </label>
                                <textarea wire:model="groupDescription"
                                          rows="3"
                                          class="w-full px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
                                          placeholder="What's this group about?"></textarea>
                            </div>

                            {{-- Industry --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Industry
                                </label>
                                <input type="text"
                                       wire:model="groupIndustry"
                                       class="w-full px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                       placeholder="e.g., Technology, Healthcare, Finance">
                            </div>

                            {{-- Privacy --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Privacy
                                </label>
                                <div class="space-y-2">
                                    <label class="flex items-start space-x-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:border-indigo-500 {{ $groupPrivacy === 'public' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : '' }}">
                                        <input type="radio" wire:model="groupPrivacy" value="public" class="mt-1">
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">Public</p>
                                            <p class="text-sm text-gray-500">Anyone can see and join this group</p>
                                        </div>
                                    </label>
                                    <label class="flex items-start space-x-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:border-indigo-500 {{ $groupPrivacy === 'private' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : '' }}">
                                        <input type="radio" wire:model="groupPrivacy" value="private" class="mt-1">
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">Private</p>
                                            <p class="text-sm text-gray-500">People must request to join</p>
                                        </div>
                                    </label>
                                    <label class="flex items-start space-x-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:border-indigo-500 {{ $groupPrivacy === 'secret' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : '' }}">
                                        <input type="radio" wire:model="groupPrivacy" value="secret" class="mt-1">
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">Secret</p>
                                            <p class="text-sm text-gray-500">Only members can see the group</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex items-center justify-end space-x-3">
                            <button type="button"
                                    wire:click="closeCreateForm"
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                                Create Group
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
