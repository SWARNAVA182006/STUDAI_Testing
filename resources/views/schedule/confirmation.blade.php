<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-2xl mx-auto px-4 py-16">
        <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
            <!-- Success Icon -->
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">You're all set!</h1>
            <p class="text-gray-600 mb-8">Your meeting has been scheduled. A confirmation email has been sent to {{ $attendee->email ?? 'your email' }}.</p>

            <!-- Meeting Details Card -->
            <div class="bg-gray-50 rounded-xl p-6 text-left mb-8">
                <h2 class="font-semibold text-gray-900 mb-4">Meeting Details</h2>
                
                <div class="space-y-4">
                    <!-- Event Title -->
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900">{{ $event->title }}</p>
                            <p class="text-sm text-gray-600">
                                {{ $event->start_time->format('l, F j, Y') }}
                            </p>
                        </div>
                    </div>

                    <!-- Time -->
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900">
                                {{ $event->start_time->format('g:i A') }} - {{ $event->end_time->format('g:i A') }}
                            </p>
                            <p class="text-sm text-gray-600">{{ $event->timezone }}</p>
                        </div>
                    </div>

                    <!-- Host -->
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900">{{ $host->name }}</p>
                            <p class="text-sm text-gray-600">Host</p>
                        </div>
                    </div>

                    <!-- Meeting Link -->
                    @if($event->meeting_link)
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-gray-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <div>
                                <p class="font-medium text-gray-900">Video Conference</p>
                                <a href="{{ $event->meeting_link }}" target="_blank" class="text-sm text-blue-600 hover:underline break-all">
                                    {{ $event->meeting_link }}
                                </a>
                            </div>
                        </div>
                    @endif

                    @if($event->location)
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-gray-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <div>
                                <p class="font-medium text-gray-900">Location</p>
                                <p class="text-sm text-gray-600">{{ $event->location }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Add to Calendar Buttons -->
            <div class="mb-8">
                <p class="text-sm text-gray-600 mb-3">Add to your calendar:</p>
                <div class="flex justify-center space-x-3">
                    <a href="{{ $googleCalendarUrl ?? '#' }}" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Google
                    </a>
                    <a href="{{ $outlookCalendarUrl ?? '#' }}" target="_blank" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                            <path fill="#0078D4" d="M24 7.387v10.478c0 .23-.08.424-.238.576-.158.152-.352.228-.583.228h-8.502v-6.996l1.666 1.22 2.124-1.756V9.27l-2.124 1.755-1.666-1.22V6.342h8.502c.223 0 .413.076.57.228.156.152.251.346.251.576v.24zM8.501 6.342h5.168V9.805l-2.584 1.898-2.584-1.898V6.342zm-7.08.817L0 7.638v9.93l1.421.48V7.16z"/>
                            <path fill="#0078D4" d="M1.422 7.16l6.746 5.17v6.34L1.422 18.05V7.16z"/>
                            <path fill="#28A8EA" d="M8.501 6.342v12.328H1.422V18.05l6.746.62v-6.34L1.422 7.16l.421-.48 7.08.578v-.916h-.422z"/>
                        </svg>
                        Outlook
                    </a>
                    <a href="{{ $icsDownloadUrl ?? '#' }}" download="meeting.ics" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download .ics
                    </a>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row justify-center space-y-3 sm:space-y-0 sm:space-x-4">
                @if($event->meeting_link)
                    <a href="{{ $event->meeting_link }}" target="_blank" class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Join Meeting
                    </a>
                @endif
                <a href="/" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                    Return to Home
                </a>
            </div>
        </div>

        <!-- Need to reschedule? -->
        <div class="text-center mt-8">
            <p class="text-gray-600 text-sm">
                Need to reschedule or cancel? Check your confirmation email for options.
            </p>
        </div>

        <!-- Powered By -->
        <p class="text-center text-gray-400 text-sm mt-8">
            Powered by <a href="/" class="text-blue-600 hover:underline">StudAI Career</a>
        </p>
    </div>
</body>
</html>
