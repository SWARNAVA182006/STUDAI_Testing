@extends('layouts.app')

@section('title', 'Scheduling Links')

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
                            <span class="ml-1 text-gray-700 dark:text-gray-300 font-medium">Scheduling Links</span>
                        </li>
                    </ol>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Scheduling Links</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Create shareable links that let others book time with you.</p>
            </div>
            <button onclick="showCreateModal()" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Link
            </button>
        </div>

        <!-- Links List -->
        @if($links->isEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
                <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">No scheduling links yet</h3>
                <p class="mt-2 text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                    Create your first scheduling link to let others book meetings with you. Share the link via email, social media, or your website.
                </p>
                <button onclick="showCreateModal()" class="mt-6 inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Your First Link
                </button>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($links as $link)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <!-- Color Header -->
                        <div class="h-2" style="background-color: {{ $link->color ?? '#3B82F6' }};"></div>
                        
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $link->name }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $link->duration_minutes }} minutes</p>
                                </div>
                                <div class="flex items-center space-x-1">
                                    @if($link->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400">
                                            Inactive
                                        </span>
                                    @endif
                                </div>
                            </div>

                            @if($link->description)
                                <p class="mt-3 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">{{ $link->description }}</p>
                            @endif

                            <!-- Meeting Type -->
                            <div class="mt-4 flex items-center text-sm text-gray-500 dark:text-gray-400">
                                @if($link->meeting_type === 'google_meet')
                                    <svg class="w-4 h-4 mr-2" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                    </svg>
                                    Google Meet
                                @elseif($link->meeting_type === 'zoom')
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M4 4h10v10H4zM15 7l5-3v12l-5-3z"/>
                                    </svg>
                                    Zoom
                                @elseif($link->meeting_type === 'teams')
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19.5 6h-3v-1.5a1.5 1.5 0 00-3 0v1.5h-3v-1.5a1.5 1.5 0 00-3 0v1.5h-3A1.5 1.5 0 003 7.5v9A1.5 1.5 0 004.5 18h15a1.5 1.5 0 001.5-1.5v-9A1.5 1.5 0 0019.5 6z"/>
                                    </svg>
                                    Microsoft Teams
                                @elseif($link->meeting_type === 'in_person')
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    In Person
                                @else
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    Video Call
                                @endif
                            </div>

                            <!-- Stats -->
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">Total bookings</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $link->times_used ?? 0 }}</span>
                                </div>
                            </div>

                            <!-- Link Preview -->
                            <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <code class="text-xs text-gray-600 dark:text-gray-400 truncate flex-1 mr-2">
                                        {{ route('schedule.show', $link->slug) }}
                                    </code>
                                    <button onclick="copyToClipboard('{{ route('schedule.show', $link->slug) }}')" class="flex-shrink-0 p-1.5 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition" title="Copy link">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="mt-4 flex items-center justify-between">
                                <a href="{{ route('schedule.show', $link->slug) }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                    Preview →
                                </a>
                                <div class="flex items-center space-x-2">
                                    <button onclick="editLink({{ $link->id }})" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition" title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <form action="{{ route('calendar.scheduling-links.delete', $link) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Are you sure you want to delete this scheduling link?')" class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="linkModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="hideModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full my-8">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 id="modalTitle" class="text-xl font-semibold text-gray-900 dark:text-white">Create Scheduling Link</h3>
                    <button onclick="hideModal()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <form id="linkForm" action="{{ route('calendar.scheduling-links.create') }}" method="POST">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                
                <div class="p-6 space-y-6 max-h-[60vh] overflow-y-auto">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Event Name *</label>
                        <input type="text" name="name" id="linkName" required
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                               placeholder="e.g., 30-Minute Meeting, Interview Call">
                    </div>

                    <!-- Slug -->
                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Custom URL</label>
                        <div class="flex rounded-lg shadow-sm">
                            <span class="inline-flex items-center px-3 text-sm text-gray-500 bg-gray-50 dark:bg-gray-600 dark:text-gray-400 border border-r-0 border-gray-300 dark:border-gray-600 rounded-l-lg">
                                {{ url('/schedule/') }}/
                            </span>
                            <input type="text" name="slug" id="linkSlug"
                                   class="flex-1 min-w-0 block rounded-none rounded-r-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                   placeholder="my-meeting">
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Leave blank to auto-generate</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea name="description" id="linkDescription" rows="3"
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                  placeholder="Brief description of what this meeting is for..."></textarea>
                    </div>

                    <!-- Duration -->
                    <div>
                        <label for="duration" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Duration *</label>
                        <select name="duration_minutes" id="linkDuration" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="15">15 minutes</option>
                            <option value="30" selected>30 minutes</option>
                            <option value="45">45 minutes</option>
                            <option value="60">60 minutes</option>
                            <option value="90">90 minutes</option>
                            <option value="120">2 hours</option>
                        </select>
                    </div>

                    <!-- Meeting Type -->
                    <div>
                        <label for="meeting_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Meeting Type *</label>
                        <select name="meeting_type" id="linkMeetingType" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="google_meet">Google Meet</option>
                            <option value="zoom">Zoom</option>
                            <option value="teams">Microsoft Teams</option>
                            <option value="phone">Phone Call</option>
                            <option value="in_person">In Person</option>
                        </select>
                    </div>

                    <!-- Location (conditional) -->
                    <div id="locationField" class="hidden">
                        <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location</label>
                        <input type="text" name="location" id="linkLocation"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                               placeholder="e.g., 123 Main St, Conference Room A">
                    </div>

                    <!-- Buffer Time -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="buffer_before" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buffer Before</label>
                            <select name="buffer_before" id="linkBufferBefore"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="0">No buffer</option>
                                <option value="5">5 minutes</option>
                                <option value="10">10 minutes</option>
                                <option value="15">15 minutes</option>
                                <option value="30">30 minutes</option>
                            </select>
                        </div>
                        <div>
                            <label for="buffer_after" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buffer After</label>
                            <select name="buffer_after" id="linkBufferAfter"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="0">No buffer</option>
                                <option value="5">5 minutes</option>
                                <option value="10">10 minutes</option>
                                <option value="15">15 minutes</option>
                                <option value="30">30 minutes</option>
                            </select>
                        </div>
                    </div>

                    <!-- Color -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Color</label>
                        <div class="flex items-center space-x-3">
                            @foreach(['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#6366F1', '#14B8A6'] as $color)
                                <label class="cursor-pointer">
                                    <input type="radio" name="color" value="{{ $color }}" class="sr-only peer" {{ $loop->first ? 'checked' : '' }}>
                                    <span class="block w-8 h-8 rounded-full ring-2 ring-transparent peer-checked:ring-offset-2 peer-checked:ring-gray-900 dark:peer-checked:ring-white transition" style="background-color: {{ $color }};"></span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Active Status -->
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="linkActive" value="1" checked
                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="linkActive" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            Active (link is bookable)
                        </label>
                    </div>
                </div>

                <div class="p-6 bg-gray-50 dark:bg-gray-700/50 flex justify-end space-x-3">
                    <button type="button" onclick="hideModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                        Save Link
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function showCreateModal() {
        document.getElementById('modalTitle').textContent = 'Create Scheduling Link';
        document.getElementById('linkForm').action = '{{ route('calendar.scheduling-links.create') }}';
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('linkForm').reset();
        document.getElementById('linkModal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function editLink(id) {
        // Fetch link data and populate form
        fetch(`/api/calendar/scheduling-links/${id}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('modalTitle').textContent = 'Edit Scheduling Link';
                document.getElementById('linkForm').action = `/calendar/scheduling-links/${id}`;
                document.getElementById('formMethod').value = 'PUT';
                
                document.getElementById('linkName').value = data.name;
                document.getElementById('linkSlug').value = data.slug;
                document.getElementById('linkDescription').value = data.description || '';
                document.getElementById('linkDuration').value = data.duration_minutes;
                document.getElementById('linkMeetingType').value = data.meeting_type;
                document.getElementById('linkLocation').value = data.location || '';
                document.getElementById('linkBufferBefore').value = data.buffer_before || 0;
                document.getElementById('linkBufferAfter').value = data.buffer_after || 0;
                document.getElementById('linkActive').checked = data.is_active;
                
                // Set color
                const colorInputs = document.querySelectorAll('input[name="color"]');
                colorInputs.forEach(input => {
                    input.checked = input.value === data.color;
                });

                toggleLocationField();
                document.getElementById('linkModal').classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            });
    }

    function hideModal() {
        document.getElementById('linkModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-2 rounded-lg shadow-lg z-50';
            toast.textContent = 'Link copied to clipboard!';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        });
    }

    function toggleLocationField() {
        const meetingType = document.getElementById('linkMeetingType').value;
        const locationField = document.getElementById('locationField');
        if (meetingType === 'in_person' || meetingType === 'phone') {
            locationField.classList.remove('hidden');
        } else {
            locationField.classList.add('hidden');
        }
    }

    // Event listeners
    document.getElementById('linkMeetingType').addEventListener('change', toggleLocationField);

    // Auto-generate slug from name
    document.getElementById('linkName').addEventListener('input', function() {
        const slug = this.value
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '');
        document.getElementById('linkSlug').value = slug;
    });

    // Close modal on escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideModal();
        }
    });
</script>
@endpush
@endsection
