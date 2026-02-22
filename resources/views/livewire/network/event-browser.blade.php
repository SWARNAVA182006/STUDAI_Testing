<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Events</h1>
                <p class="text-gray-600 mt-1">Discover networking events and grow your connections</p>
            </div>
            <button wire:click="openCreateModal" 
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Create Event
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $this->eventStats['upcoming'] }}</p>
                        <p class="text-sm text-gray-500">Upcoming</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $this->eventStats['attended'] }}</p>
                        <p class="text-sm text-gray-500">Attended</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $this->eventStats['organized'] }}</p>
                        <p class="text-sm text-gray-500">Organized</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Happening Soon Alert -->
        @if($this->happeningSoon->count() > 0)
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-8">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h3 class="font-semibold text-yellow-800">Events happening soon!</h3>
                        <div class="mt-2 space-y-1">
                            @foreach($this->happeningSoon as $event)
                                <p class="text-sm text-yellow-700">
                                    <a href="{{ route('network.events.show', $event) }}" class="font-medium hover:underline">{{ $event->title }}</a>
                                    starts {{ $event->starts_at->diffForHumans() }}
                                </p>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-3 space-y-6">
                <!-- Tabs & Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <!-- Tab Navigation -->
                    <div class="border-b border-gray-200">
                        <nav class="flex overflow-x-auto px-4" aria-label="Tabs">
                            @foreach([
                                'discover' => 'Discover',
                                'my-events' => 'My Events',
                                'interested' => 'Interested',
                                'organized' => 'Organized',
                                'past' => 'Past',
                            ] as $tab => $label)
                                <button wire:click="setTab('{{ $tab }}')"
                                        class="flex-shrink-0 px-4 py-4 text-sm font-medium border-b-2 transition {{ $activeTab === $tab ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </nav>
                    </div>

                    <!-- Search & Filters -->
                    @if($activeTab === 'discover')
                        <div class="p-4 border-b border-gray-200">
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="flex-1 relative">
                                    <input type="text" 
                                           wire:model.live.debounce.300ms="searchQuery"
                                           placeholder="Search events..."
                                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                <select wire:model.live="typeFilter"
                                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">All Types</option>
                                    <option value="virtual">Virtual</option>
                                    <option value="in_person">In Person</option>
                                    <option value="hybrid">Hybrid</option>
                                </select>
                            </div>
                        </div>
                    @endif

                    <!-- Events Grid -->
                    <div class="p-4">
                        @if($events->isEmpty())
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900">No events found</h3>
                                <p class="text-gray-500 mt-1">
                                    @if($activeTab === 'organized')
                                        Create your first event to get started!
                                    @elseif($activeTab === 'my-events')
                                        RSVP to events to see them here.
                                    @else
                                        Check back later for new events.
                                    @endif
                                </p>
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($events as $event)
                                    <div class="border border-gray-200 rounded-xl overflow-hidden hover:shadow-md transition group">
                                        <!-- Event Image -->
                                        <div class="aspect-video bg-gradient-to-br from-blue-500 to-purple-600 relative">
                                            @if($event->cover_image)
                                                <img src="{{ $event->cover_image }}" alt="{{ $event->title }}" class="w-full h-full object-cover">
                                            @endif
                                            <div class="absolute top-3 left-3">
                                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $event->type === 'virtual' ? 'bg-blue-100 text-blue-800' : ($event->type === 'in_person' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800') }}">
                                                    {{ ucfirst(str_replace('_', ' ', $event->type)) }}
                                                </span>
                                            </div>
                                            @if($event->is_featured)
                                                <div class="absolute top-3 right-3">
                                                    <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">Featured</span>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Event Details -->
                                        <div class="p-4">
                                            <div class="flex items-start justify-between gap-2 mb-2">
                                                <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 transition line-clamp-2">
                                                    <a href="{{ route('network.events.show', $event) }}">{{ $event->title }}</a>
                                                </h3>
                                            </div>

                                            <div class="space-y-2 text-sm text-gray-600 mb-4">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                    <span>{{ $event->starts_at->format('M j, Y') }} at {{ $event->starts_at->format('g:i A') }}</span>
                                                </div>
                                                @if($event->location)
                                                    <div class="flex items-center gap-2">
                                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        </svg>
                                                        <span class="truncate">{{ $event->location }}</span>
                                                    </div>
                                                @endif
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                    </svg>
                                                    <span>{{ $event->attendee_count }} attending</span>
                                                    @if($event->capacity)
                                                        <span class="text-gray-400">/ {{ $event->capacity }}</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- RSVP Buttons -->
                                            @php
                                                $userRsvp = $event->getRsvpForUser(auth()->user());
                                            @endphp
                                            <div class="flex gap-2">
                                                @if($userRsvp?->status === 'going')
                                                    <button wire:click="cancelRsvp({{ $event->id }})"
                                                            class="flex-1 px-3 py-2 bg-green-100 text-green-700 font-medium rounded-lg hover:bg-green-200 transition">
                                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                        Going
                                                    </button>
                                                @elseif($userRsvp?->status === 'interested')
                                                    <button wire:click="rsvp({{ $event->id }}, 'going')"
                                                            class="flex-1 px-3 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition"
                                                            @if($event->isFull()) disabled @endif>
                                                        {{ $event->isFull() ? 'Full' : 'Go' }}
                                                    </button>
                                                    <button wire:click="cancelRsvp({{ $event->id }})"
                                                            class="px-3 py-2 bg-yellow-100 text-yellow-700 font-medium rounded-lg hover:bg-yellow-200 transition">
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                                        </svg>
                                                    </button>
                                                @else
                                                    <button wire:click="rsvp({{ $event->id }}, 'going')"
                                                            class="flex-1 px-3 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                                            @if($event->isFull()) disabled @endif>
                                                        {{ $event->isFull() ? 'Full' : 'Attend' }}
                                                    </button>
                                                    <button wire:click="rsvp({{ $event->id }}, 'interested')"
                                                            class="px-3 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Pagination -->
                            <div class="mt-6">
                                {{ $events->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Featured Events -->
                @if($this->featuredEvents->count() > 0)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <h3 class="font-semibold text-gray-900 mb-4">Featured Events</h3>
                        <div class="space-y-4">
                            @foreach($this->featuredEvents->take(3) as $event)
                                <a href="{{ route('network.events.show', $event) }}" class="block group">
                                    <div class="flex gap-3">
                                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold flex-shrink-0">
                                            {{ $event->starts_at->format('d') }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-gray-900 truncate group-hover:text-blue-600 transition">{{ $event->title }}</p>
                                            <p class="text-sm text-gray-500">{{ $event->starts_at->format('M j, g:i A') }}</p>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Suggested Events -->
                @if($this->suggestedEvents->count() > 0)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <h3 class="font-semibold text-gray-900 mb-4">Suggested for You</h3>
                        <div class="space-y-4">
                            @foreach($this->suggestedEvents as $event)
                                <a href="{{ route('network.events.show', $event) }}" class="block group">
                                    <div class="flex gap-3">
                                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-gray-900 truncate group-hover:text-blue-600 transition">{{ $event->title }}</p>
                                            <p class="text-sm text-gray-500">{{ $event->starts_at->format('M j, g:i A') }}</p>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Create Event Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.away="$wire.closeCreateModal()">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-gray-900">Create Event</h2>
                        <button wire:click="closeCreateModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <form wire:submit="createEvent" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Event Title *</label>
                        <input type="text" wire:model="newEventTitle" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter event title">
                        @error('newEventTitle') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea wire:model="newEventDescription" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Describe your event..."></textarea>
                        @error('newEventDescription') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Event Type *</label>
                        <select wire:model.live="newEventType"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="virtual">Virtual</option>
                            <option value="in_person">In Person</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>

                    @if(in_array($newEventType, ['in_person', 'hybrid']))
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Location *</label>
                            <input type="text" wire:model="newEventLocation"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Enter venue address">
                            @error('newEventLocation') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if(in_array($newEventType, ['virtual', 'hybrid']))
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Virtual Link *</label>
                            <input type="url" wire:model="newEventVirtualLink"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="https://zoom.us/j/...">
                            @error('newEventVirtualLink') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date & Time *</label>
                            <input type="datetime-local" wire:model="newEventStartsAt"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            @error('newEventStartsAt') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date & Time</label>
                            <input type="datetime-local" wire:model="newEventEndsAt"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            @error('newEventEndsAt') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Capacity (optional)</label>
                        <input type="number" wire:model="newEventCapacity" min="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Leave empty for unlimited">
                        @error('newEventCapacity') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="checkbox" wire:model="newEventRequiresApproval" id="requiresApproval"
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="requiresApproval" class="text-sm text-gray-700">Require approval for attendees</label>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" wire:click="closeCreateModal"
                                class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit"
                                class="flex-1 px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                            Create Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
