<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $link->name }} - Book a Meeting with {{ $user->name }}</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .time-slot {
            transition: all 0.2s ease;
        }
        .time-slot:hover {
            transform: translateY(-1px);
        }
        .time-slot.selected {
            background-color: #3B82F6;
            color: white;
            border-color: #3B82F6;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 py-8 md:py-16">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="md:flex">
                <!-- Left Panel - Event Info -->
                <div class="md:w-1/3 p-8 border-r border-gray-200" style="border-top: 4px solid {{ $link->color ?? '#3B82F6' }};">
                    <!-- Host Info -->
                    <div class="flex items-center mb-6">
                        @if($user->avatar)
                            <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="w-12 h-12 rounded-full">
                        @else
                            <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-semibold text-lg">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                        @endif
                        <div class="ml-3">
                            <p class="text-sm text-gray-500">Meeting with</p>
                            <p class="font-semibold text-gray-900">{{ $user->name }}</p>
                        </div>
                    </div>

                    <!-- Event Details -->
                    <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ $link->name }}</h1>
                    
                    @if($link->description)
                        <p class="text-gray-600 mb-6">{{ $link->description }}</p>
                    @endif

                    <div class="space-y-3">
                        <!-- Duration -->
                        <div class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ $link->duration_minutes }} minutes</span>
                        </div>

                        <!-- Meeting Type -->
                        <div class="flex items-center text-gray-600">
                            @if($link->meeting_type === 'google_meet')
                                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span>Google Meet</span>
                            @elseif($link->meeting_type === 'zoom')
                                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span>Zoom</span>
                            @elseif($link->meeting_type === 'teams')
                                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span>Microsoft Teams</span>
                            @elseif($link->meeting_type === 'phone')
                                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span>Phone Call</span>
                            @elseif($link->meeting_type === 'in_person')
                                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>In Person</span>
                            @endif
                        </div>

                        @if($link->location)
                            <div class="flex items-center text-gray-600">
                                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>{{ $link->location }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Right Panel - Calendar & Form -->
                <div class="md:w-2/3 p-8">
                    <!-- Step Indicator -->
                    <div class="flex items-center mb-8" id="stepIndicator">
                        <div class="step-item active flex items-center">
                            <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-medium">1</div>
                            <span class="ml-2 text-sm font-medium text-gray-900">Select Date & Time</span>
                        </div>
                        <div class="flex-1 h-0.5 bg-gray-200 mx-4"></div>
                        <div class="step-item flex items-center opacity-50" id="step2">
                            <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center text-sm font-medium">2</div>
                            <span class="ml-2 text-sm font-medium text-gray-600">Your Details</span>
                        </div>
                    </div>

                    <!-- Step 1: Date & Time Selection -->
                    <div id="dateTimeStep">
                        <!-- Date Selection -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Select a Date</h3>
                            <div class="flex items-center justify-between mb-4">
                                <button id="prevMonth" class="p-2 hover:bg-gray-100 rounded-lg transition">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <h4 id="currentMonth" class="text-lg font-medium text-gray-900"></h4>
                                <button id="nextMonth" class="p-2 hover:bg-gray-100 rounded-lg transition">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Calendar Grid -->
                            <div class="grid grid-cols-7 gap-1 text-center mb-4">
                                <div class="text-xs font-medium text-gray-500 py-2">Sun</div>
                                <div class="text-xs font-medium text-gray-500 py-2">Mon</div>
                                <div class="text-xs font-medium text-gray-500 py-2">Tue</div>
                                <div class="text-xs font-medium text-gray-500 py-2">Wed</div>
                                <div class="text-xs font-medium text-gray-500 py-2">Thu</div>
                                <div class="text-xs font-medium text-gray-500 py-2">Fri</div>
                                <div class="text-xs font-medium text-gray-500 py-2">Sat</div>
                            </div>
                            <div id="calendarDays" class="grid grid-cols-7 gap-1"></div>
                        </div>

                        <!-- Timezone -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
                            <select id="timezone" class="w-full rounded-lg border-gray-300 text-sm">
                                @foreach(['America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles', 'Europe/London', 'Europe/Paris', 'Asia/Tokyo', 'Asia/Shanghai', 'Australia/Sydney'] as $tz)
                                    <option value="{{ $tz }}">{{ $tz }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Time Slots -->
                        <div id="timeSlotsContainer" class="hidden">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Available Times on <span id="selectedDateDisplay"></span></h3>
                            <div id="timeSlots" class="grid grid-cols-3 gap-2 max-h-64 overflow-y-auto"></div>
                            <p id="noSlotsMessage" class="hidden text-gray-500 text-center py-8">No available times on this date.</p>
                        </div>
                    </div>

                    <!-- Step 2: User Details Form -->
                    <div id="detailsStep" class="hidden">
                        <button onclick="goToStep1()" class="flex items-center text-blue-600 hover:text-blue-700 mb-6">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Back to date selection
                        </button>

                        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-900" id="confirmDateTime"></p>
                                    <p class="text-sm text-gray-600" id="confirmTimezone"></p>
                                </div>
                            </div>
                        </div>

                        <form id="bookingForm">
                            <div class="space-y-4">
                                <div>
                                    <label for="attendee_name" class="block text-sm font-medium text-gray-700 mb-1">Your Name *</label>
                                    <input type="text" id="attendee_name" name="attendee_name" required
                                           class="w-full rounded-lg border-gray-300"
                                           placeholder="John Doe">
                                </div>

                                <div>
                                    <label for="attendee_email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                                    <input type="email" id="attendee_email" name="attendee_email" required
                                           class="w-full rounded-lg border-gray-300"
                                           placeholder="john@example.com">
                                </div>

                                <div>
                                    <label for="attendee_phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="tel" id="attendee_phone" name="attendee_phone"
                                           class="w-full rounded-lg border-gray-300"
                                           placeholder="+1 (555) 123-4567">
                                </div>

                                <div>
                                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Additional Notes</label>
                                    <textarea id="notes" name="notes" rows="3"
                                              class="w-full rounded-lg border-gray-300"
                                              placeholder="Please share anything that will help prepare for our meeting..."></textarea>
                                </div>
                            </div>

                            <button type="submit" id="submitBtn" class="w-full mt-6 py-3 px-6 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                                Confirm Booking
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Powered By -->
        <p class="text-center text-gray-400 text-sm mt-8">
            Powered by <a href="/" class="text-blue-600 hover:underline">StudAI Career</a>
        </p>
    </div>

    <script>
        const linkSlug = '{{ $link->slug }}';
        let currentDate = new Date();
        let selectedDate = null;
        let selectedSlot = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Try to detect timezone
            const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            const timezoneSelect = document.getElementById('timezone');
            for (let i = 0; i < timezoneSelect.options.length; i++) {
                if (timezoneSelect.options[i].value === userTimezone) {
                    timezoneSelect.selectedIndex = i;
                    break;
                }
            }

            renderCalendar();

            document.getElementById('prevMonth').addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() - 1);
                renderCalendar();
            });

            document.getElementById('nextMonth').addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() + 1);
                renderCalendar();
            });

            document.getElementById('timezone').addEventListener('change', () => {
                if (selectedDate) {
                    loadTimeSlots(selectedDate);
                }
            });

            document.getElementById('bookingForm').addEventListener('submit', submitBooking);
        });

        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            document.getElementById('currentMonth').textContent = 
                new Date(year, month).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            let html = '';
            
            // Empty cells for days before the first of the month
            for (let i = 0; i < firstDay; i++) {
                html += '<div class="p-2"></div>';
            }

            // Days of the month
            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(year, month, day);
                const dateStr = date.toISOString().split('T')[0];
                const isPast = date < today;
                const isSelected = selectedDate === dateStr;
                
                let classes = 'p-2 text-center rounded-lg cursor-pointer transition ';
                if (isPast) {
                    classes += 'text-gray-300 cursor-not-allowed';
                } else if (isSelected) {
                    classes += 'bg-blue-600 text-white';
                } else {
                    classes += 'hover:bg-blue-50 text-gray-900';
                }

                html += `<div class="${classes}" ${isPast ? '' : `onclick="selectDate('${dateStr}')"`}>${day}</div>`;
            }

            document.getElementById('calendarDays').innerHTML = html;
        }

        function selectDate(dateStr) {
            selectedDate = dateStr;
            selectedSlot = null;
            renderCalendar();
            loadTimeSlots(dateStr);
        }

        async function loadTimeSlots(dateStr) {
            const container = document.getElementById('timeSlotsContainer');
            const slotsDiv = document.getElementById('timeSlots');
            const noSlotsMsg = document.getElementById('noSlotsMessage');
            const timezone = document.getElementById('timezone').value;

            container.classList.remove('hidden');
            slotsDiv.innerHTML = '<div class="col-span-3 text-center py-4 text-gray-500">Loading...</div>';
            noSlotsMsg.classList.add('hidden');

            const date = new Date(dateStr);
            document.getElementById('selectedDateDisplay').textContent = 
                date.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });

            try {
                const response = await fetch(`/schedule/${linkSlug}/times?date=${dateStr}&timezone=${timezone}`);
                const data = await response.json();

                if (data.available_slots && data.available_slots.length > 0) {
                    slotsDiv.innerHTML = data.available_slots.map(slot => `
                        <button type="button" 
                                class="time-slot p-3 border-2 border-gray-200 rounded-lg text-center text-sm font-medium hover:border-blue-500 transition"
                                onclick="selectSlot(this, '${slot.start}')"
                                data-start="${slot.start}">
                            ${slot.display.split(' - ')[0]}
                        </button>
                    `).join('');
                } else {
                    slotsDiv.innerHTML = '';
                    noSlotsMsg.classList.remove('hidden');
                }
            } catch (error) {
                slotsDiv.innerHTML = '<div class="col-span-3 text-center py-4 text-red-500">Failed to load times. Please try again.</div>';
            }
        }

        function selectSlot(element, startTime) {
            // Remove selection from all slots
            document.querySelectorAll('.time-slot').forEach(el => el.classList.remove('selected'));
            
            // Add selection to clicked slot
            element.classList.add('selected');
            selectedSlot = startTime;

            // Move to step 2
            goToStep2();
        }

        function goToStep2() {
            document.getElementById('dateTimeStep').classList.add('hidden');
            document.getElementById('detailsStep').classList.remove('hidden');
            
            // Update step indicator
            document.querySelector('#stepIndicator .step-item').classList.add('opacity-50');
            document.querySelector('#stepIndicator .step-item').classList.remove('active');
            document.getElementById('step2').classList.remove('opacity-50');
            document.getElementById('step2').querySelector('div').classList.remove('bg-gray-200', 'text-gray-600');
            document.getElementById('step2').querySelector('div').classList.add('bg-blue-600', 'text-white');

            // Show confirmation
            const date = new Date(selectedSlot);
            const timezone = document.getElementById('timezone').value;
            document.getElementById('confirmDateTime').textContent = 
                date.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', hour: 'numeric', minute: '2-digit' });
            document.getElementById('confirmTimezone').textContent = timezone;
        }

        function goToStep1() {
            document.getElementById('detailsStep').classList.add('hidden');
            document.getElementById('dateTimeStep').classList.remove('hidden');
            
            // Update step indicator
            document.querySelector('#stepIndicator .step-item').classList.remove('opacity-50');
            document.querySelector('#stepIndicator .step-item').classList.add('active');
            document.getElementById('step2').classList.add('opacity-50');
            document.getElementById('step2').querySelector('div').classList.add('bg-gray-200', 'text-gray-600');
            document.getElementById('step2').querySelector('div').classList.remove('bg-blue-600', 'text-white');
        }

        async function submitBooking(e) {
            e.preventDefault();
            
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.textContent = 'Booking...';

            const formData = {
                start_time: selectedSlot,
                timezone: document.getElementById('timezone').value,
                attendee_name: document.getElementById('attendee_name').value,
                attendee_email: document.getElementById('attendee_email').value,
                attendee_phone: document.getElementById('attendee_phone').value,
                notes: document.getElementById('notes').value,
            };

            try {
                const response = await fetch(`/schedule/${linkSlug}/book`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(formData),
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = data.confirmation_url;
                } else {
                    alert(data.message || 'Failed to book. Please try again.');
                    btn.disabled = false;
                    btn.textContent = 'Confirm Booking';
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
                btn.disabled = false;
                btn.textContent = 'Confirm Booking';
            }
        }
    </script>
</body>
</html>
