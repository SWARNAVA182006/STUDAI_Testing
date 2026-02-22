@extends('layouts.app')

@section('title', 'Calendar Events')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <nav class="flex mb-4" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li><a href="{{ route('calendar.dashboard') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">Calendar</a></li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="ml-1 text-gray-700 dark:text-gray-300 font-medium">Events</span>
                        </li>
                    </ol>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Your Events</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Manage your scheduled meetings and interviews.</p>
            </div>
            <button onclick="showCreateEventModal()" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Event
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
            <div class="flex flex-wrap items-center gap-4">
                <!-- Date Filter -->
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">From:</label>
                    <input type="date" name="from_date" value="{{ request('from_date', now()->toDateString()) }}"
                           class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                </div>
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">To:</label>
                    <input type="date" name="to_date" value="{{ request('to_date', now()->addMonth()->toDateString()) }}"
                           class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                </div>
                
                <!-- Status Filter -->
                <select name="status" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">All Status</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="pending">Pending</option>
                    <option value="cancelled">Cancelled</option>
                </select>

                <!-- Event Type Filter -->
                <select name="type" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">All Types</option>
                    <option value="meeting">Meetings</option>
                    <option value="interview">Interviews</option>
                </select>

                <button type="button" onclick="applyFilters()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition text-sm font-medium">
                    Apply Filters
                </button>
            </div>
        </div>

        <!-- Events List -->
        @if($events->isEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">No events found</h3>
                <p class="mt-2 text-gray-500 dark:text-gray-400">
                    You don't have any scheduled events for this period.
                </p>
                <button onclick="showCreateEventModal()" class="mt-6 inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                    Schedule Your First Event
                </button>
            </div>
        @else
            <div class="space-y-4">
                @php
                    $groupedEvents = $events->groupBy(fn($e) => $e->start_time->format('Y-m-d'));
                @endphp

                @foreach($groupedEvents as $date => $dayEvents)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <!-- Date Header -->
                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}
                                @if(\Carbon\Carbon::parse($date)->isToday())
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                        Today
                                    </span>
                                @elseif(\Carbon\Carbon::parse($date)->isTomorrow())
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        Tomorrow
                                    </span>
                                @endif
                            </h3>
                        </div>

                        <!-- Events for this day -->
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($dayEvents as $event)
                                <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-start space-x-4">
                                            <!-- Time Column -->
                                            <div class="flex-shrink-0 w-24 text-center">
                                                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                                    {{ $event->start_time->format('g:i A') }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $event->start_time->diffForHumans() }}
                                                </p>
                                            </div>

                                            <!-- Event Details -->
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-2">
                                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ $event->title }}</h4>
                                                    @if($event->event_type === 'interview')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                                                            Interview
                                                        </span>
                                                    @endif
                                                    @if($event->status === 'cancelled')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                                            Cancelled
                                                        </span>
                                                    @elseif($event->status === 'pending')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                                            Pending
                                                        </span>
                                                    @endif
                                                </div>

                                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ $event->start_time->format('g:i A') }} - {{ $event->end_time->format('g:i A') }}
                                                    <span class="mx-1">•</span>
                                                    {{ $event->start_time->diffInMinutes($event->end_time) }} minutes
                                                </p>

                                                @if($event->description)
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2 line-clamp-2">{{ $event->description }}</p>
                                                @endif

                                                <!-- Participants -->
                                                @if($event->participants->count() > 0)
                                                    <div class="flex items-center mt-3 space-x-2">
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">With:</span>
                                                        <div class="flex -space-x-2">
                                                            @foreach($event->participants->take(3) as $participant)
                                                                <div class="w-6 h-6 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center text-xs font-medium text-gray-700 dark:text-gray-300 ring-2 ring-white dark:ring-gray-800" title="{{ $participant->name }}">
                                                                    {{ substr($participant->name, 0, 1) }}
                                                                </div>
                                                            @endforeach
                                                            @if($event->participants->count() > 3)
                                                                <div class="w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs font-medium text-gray-500 ring-2 ring-white dark:ring-gray-800">
                                                                    +{{ $event->participants->count() - 3 }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                                            {{ $event->participants->pluck('name')->take(2)->implode(', ') }}
                                                            @if($event->participants->count() > 2)
                                                                and {{ $event->participants->count() - 2 }} more
                                                            @endif
                                                        </span>
                                                    </div>
                                                @endif

                                                <!-- Meeting Link -->
                                                @if($event->meeting_link && $event->status !== 'cancelled')
                                                    <a href="{{ $event->meeting_link }}" target="_blank" class="inline-flex items-center mt-3 text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                        </svg>
                                                        Join Meeting
                                                    </a>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex items-center space-x-2">
                                            @if($event->status !== 'cancelled')
                                                <button onclick="editEvent({{ $event->id }})" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition" title="Edit">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                <form action="{{ route('calendar.events.delete', $event) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" onclick="return confirm('Are you sure you want to cancel this event?')" class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition" title="Cancel">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($events->hasPages())
                <div class="mt-6">
                    {{ $events->links() }}
                </div>
            @endif
        @endif
    </div>
</div>

<!-- Create Event Modal -->
<div id="eventModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="hideEventModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full my-8">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 id="eventModalTitle" class="text-xl font-semibold text-gray-900 dark:text-white">Create Event</h3>
                    <button onclick="hideEventModal()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <form id="eventForm" action="{{ route('calendar.events.create') }}" method="POST">
                @csrf
                <input type="hidden" name="_method" id="eventFormMethod" value="POST">
                
                <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title *</label>
                        <input type="text" name="title" id="eventTitle" required
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                               placeholder="Meeting title">
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea name="description" id="eventDescription" rows="3"
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                  placeholder="Add details about this event..."></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date *</label>
                            <input type="date" name="start_date" id="eventStartDate" required
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label for="start_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Time *</label>
                            <input type="time" name="start_time" id="eventStartTime" required
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date *</label>
                            <input type="date" name="end_date" id="eventEndDate" required
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label for="end_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Time *</label>
                            <input type="time" name="end_time" id="eventEndTime" required
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label for="event_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Event Type</label>
                        <select name="event_type" id="eventType"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="meeting">Meeting</option>
                            <option value="interview">Interview</option>
                            <option value="call">Call</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location</label>
                        <input type="text" name="location" id="eventLocation"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                               placeholder="Add location or meeting link">
                    </div>

                    <div>
                        <label for="attendee_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Invite Attendee (email)</label>
                        <input type="email" name="attendee_email" id="eventAttendeeEmail"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                               placeholder="attendee@example.com">
                    </div>
                </div>

                <div class="p-6 bg-gray-50 dark:bg-gray-700/50 flex justify-end space-x-3">
                    <button type="button" onclick="hideEventModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                        Create Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function showCreateEventModal() {
        document.getElementById('eventModalTitle').textContent = 'Create Event';
        document.getElementById('eventForm').action = '{{ route('calendar.events.create') }}';
        document.getElementById('eventFormMethod').value = 'POST';
        document.getElementById('eventForm').reset();
        
        // Set default dates
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('eventStartDate').value = today;
        document.getElementById('eventEndDate').value = today;
        
        document.getElementById('eventModal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function editEvent(id) {
        // Fetch event data and populate form
        fetch(`/api/calendar/events/${id}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('eventModalTitle').textContent = 'Edit Event';
                document.getElementById('eventForm').action = `/calendar/events/${id}`;
                document.getElementById('eventFormMethod').value = 'PUT';
                
                document.getElementById('eventTitle').value = data.title;
                document.getElementById('eventDescription').value = data.description || '';
                document.getElementById('eventStartDate').value = data.start_date;
                document.getElementById('eventStartTime').value = data.start_time;
                document.getElementById('eventEndDate').value = data.end_date;
                document.getElementById('eventEndTime').value = data.end_time;
                document.getElementById('eventType').value = data.event_type;
                document.getElementById('eventLocation').value = data.location || '';

                document.getElementById('eventModal').classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            });
    }

    function hideEventModal() {
        document.getElementById('eventModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function applyFilters() {
        const params = new URLSearchParams();
        
        const fromDate = document.querySelector('input[name="from_date"]').value;
        const toDate = document.querySelector('input[name="to_date"]').value;
        const status = document.querySelector('select[name="status"]').value;
        const type = document.querySelector('select[name="type"]').value;
        
        if (fromDate) params.append('from_date', fromDate);
        if (toDate) params.append('to_date', toDate);
        if (status) params.append('status', status);
        if (type) params.append('type', type);
        
        window.location.href = '{{ route('calendar.events') }}?' + params.toString();
    }

    // Auto-sync end date with start date
    document.getElementById('eventStartDate').addEventListener('change', function() {
        document.getElementById('eventEndDate').value = this.value;
    });

    // Close modal on escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideEventModal();
        }
    });
</script>
@endpush
@endsection
