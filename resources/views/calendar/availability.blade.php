@extends('layouts.app')

@section('title', 'Manage Availability')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li><a href="{{ route('calendar.dashboard') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">Calendar</a></li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                        <span class="ml-1 text-gray-700 dark:text-gray-300 font-medium">Availability</span>
                    </li>
                </ol>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Manage Availability</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Set your available hours for each day of the week. These times will be used when people book meetings with you.</p>
        </div>

        <!-- Timezone Selection -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Timezone</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">All times are displayed in this timezone</p>
                </div>
                <select id="timezone" name="timezone" class="form-select rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    @foreach(timezone_identifiers_list() as $tz)
                        <option value="{{ $tz }}" {{ ($userTimezone ?? 'UTC') === $tz ? 'selected' : '' }}>
                            {{ $tz }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Availability Form -->
        <form id="availabilityForm" action="{{ route('calendar.availability.update') }}" method="POST">
            @csrf
            <input type="hidden" name="timezone" id="formTimezone" value="{{ $userTimezone ?? 'UTC' }}">

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Weekly Availability</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Toggle days on/off and set your available hours</p>
                </div>

                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @php
                        $days = [
                            'monday' => 'Monday',
                            'tuesday' => 'Tuesday',
                            'wednesday' => 'Wednesday',
                            'thursday' => 'Thursday',
                            'friday' => 'Friday',
                            'saturday' => 'Saturday',
                            'sunday' => 'Sunday',
                        ];
                    @endphp

                    @foreach($days as $dayKey => $dayName)
                        @php
                            $dayAvail = $availability->where('day_of_week', $dayKey)->first();
                            $isAvailable = $dayAvail?->is_available ?? in_array($dayKey, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']);
                            $startTime = $dayAvail?->start_time ?? '09:00';
                            $endTime = $dayAvail?->end_time ?? '17:00';
                        @endphp
                        <div class="p-6 availability-row" data-day="{{ $dayKey }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <!-- Toggle -->
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" 
                                               name="availability[{{ $dayKey }}][is_available]" 
                                               value="1"
                                               class="sr-only peer day-toggle"
                                               {{ $isAvailable ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                    </label>
                                    
                                    <!-- Day Name -->
                                    <span class="text-gray-900 dark:text-white font-medium w-24">{{ $dayName }}</span>
                                </div>

                                <!-- Time Inputs -->
                                <div class="flex items-center space-x-3 time-inputs {{ $isAvailable ? '' : 'opacity-50 pointer-events-none' }}">
                                    <input type="time" 
                                           name="availability[{{ $dayKey }}][start_time]" 
                                           value="{{ $startTime }}"
                                           class="form-input rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">to</span>
                                    <input type="time" 
                                           name="availability[{{ $dayKey }}][end_time]" 
                                           value="{{ $endTime }}"
                                           class="form-input rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                    
                                    <!-- Add Time Slot Button -->
                                    <button type="button" 
                                            onclick="addTimeSlot('{{ $dayKey }}')"
                                            class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition"
                                            title="Add another time slot">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Additional Time Slots Container -->
                            <div class="additional-slots mt-3 space-y-3 {{ $isAvailable ? '' : 'hidden' }}" id="slots-{{ $dayKey }}">
                                <!-- Dynamic time slots will be added here -->
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Form Actions -->
                <div class="p-6 bg-gray-50 dark:bg-gray-700/50 flex items-center justify-between">
                    <button type="button" onclick="applyToAllDays()" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        Apply Monday's hours to all weekdays
                    </button>
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('calendar.dashboard') }}" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Tips Section -->
        <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-blue-900 dark:text-blue-200">Tips for Setting Availability</h3>
                    <ul class="mt-2 text-sm text-blue-800 dark:text-blue-300 list-disc list-inside space-y-1">
                        <li>Consider your most productive hours for meetings</li>
                        <li>Leave buffer time between potential meetings</li>
                        <li>Account for time zone differences if you work with international clients</li>
                        <li>Block off time for focused work, lunch, and breaks</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Update timezone in hidden field when select changes
    document.getElementById('timezone').addEventListener('change', function() {
        document.getElementById('formTimezone').value = this.value;
    });

    // Toggle time inputs when day is toggled
    document.querySelectorAll('.day-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const row = this.closest('.availability-row');
            const timeInputs = row.querySelector('.time-inputs');
            const additionalSlots = row.querySelector('.additional-slots');
            
            if (this.checked) {
                timeInputs.classList.remove('opacity-50', 'pointer-events-none');
                additionalSlots.classList.remove('hidden');
            } else {
                timeInputs.classList.add('opacity-50', 'pointer-events-none');
                additionalSlots.classList.add('hidden');
            }
        });
    });

    // Add additional time slot
    let slotCounters = {};
    function addTimeSlot(day) {
        if (!slotCounters[day]) slotCounters[day] = 0;
        slotCounters[day]++;
        
        const container = document.getElementById(`slots-${day}`);
        const slotHtml = `
            <div class="flex items-center space-x-3 pl-28" data-slot="${slotCounters[day]}">
                <input type="time" 
                       name="availability[${day}][additional][${slotCounters[day]}][start_time]" 
                       value="13:00"
                       class="form-input rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <span class="text-gray-500 dark:text-gray-400">to</span>
                <input type="time" 
                       name="availability[${day}][additional][${slotCounters[day]}][end_time]" 
                       value="17:00"
                       class="form-input rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <button type="button" onclick="removeTimeSlot(this)" class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', slotHtml);
    }

    function removeTimeSlot(button) {
        button.closest('[data-slot]').remove();
    }

    // Apply Monday's hours to all weekdays
    function applyToAllDays() {
        const mondayRow = document.querySelector('[data-day="monday"]');
        const mondayToggle = mondayRow.querySelector('.day-toggle');
        const mondayStart = mondayRow.querySelector('input[type="time"]:first-of-type');
        const mondayEnd = mondayRow.querySelector('input[type="time"]:last-of-type');

        const weekdays = ['tuesday', 'wednesday', 'thursday', 'friday'];
        
        weekdays.forEach(day => {
            const row = document.querySelector(`[data-day="${day}"]`);
            const toggle = row.querySelector('.day-toggle');
            const startInput = row.querySelector('input[type="time"]:first-of-type');
            const endInput = row.querySelector('input[type="time"]:last-of-type');
            const timeInputs = row.querySelector('.time-inputs');
            const additionalSlots = row.querySelector('.additional-slots');

            toggle.checked = mondayToggle.checked;
            startInput.value = mondayStart.value;
            endInput.value = mondayEnd.value;

            if (mondayToggle.checked) {
                timeInputs.classList.remove('opacity-50', 'pointer-events-none');
                additionalSlots.classList.remove('hidden');
            } else {
                timeInputs.classList.add('opacity-50', 'pointer-events-none');
                additionalSlots.classList.add('hidden');
            }
        });

        // Show confirmation
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50';
        toast.textContent = 'Applied Monday\'s hours to all weekdays';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    // Form submission with AJAX
    document.getElementById('availabilityForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const button = this.querySelector('button[type="submit"]');
        const originalText = button.textContent;
        button.textContent = 'Saving...';
        button.disabled = true;

        try {
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            });

            const data = await response.json();

            if (data.success) {
                // Show success message
                const toast = document.createElement('div');
                toast.className = 'fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50';
                toast.textContent = 'Availability saved successfully!';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            } else {
                throw new Error(data.message || 'Failed to save');
            }
        } catch (error) {
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 bg-red-600 text-white px-4 py-2 rounded-lg shadow-lg z-50';
            toast.textContent = 'Error saving availability: ' + error.message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        } finally {
            button.textContent = originalText;
            button.disabled = false;
        }
    });
</script>
@endpush
@endsection
