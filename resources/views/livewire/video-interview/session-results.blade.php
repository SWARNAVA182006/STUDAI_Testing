<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Interview Results</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ $session->title }} • {{ $session->completed_at?->format('M d, Y') ?? $session->created_at->format('M d, Y') }}
            </p>
        </div>
        <a href="{{ route('video-interview.sessions') }}" 
           class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
            ← Back to Sessions
        </a>
    </div>

    <!-- Overall Score Card -->
    @if($stats['overall_score'])
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                <!-- Overall Score -->
                <div class="text-center">
                    <div class="text-5xl font-bold {{ $this->getGradeColor($stats['overall_score']) }}">
                        {{ $this->getGrade($stats['overall_score']) }}
                    </div>
                    <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">Overall Grade</div>
                    <div class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['overall_score'] }}%</div>
                </div>

                <!-- Individual Scores -->
                <div class="flex flex-col items-center">
                    <div class="w-20 h-20 rounded-full border-4 {{ $stats['content_score'] >= 70 ? 'border-green-500' : 'border-yellow-500' }} flex items-center justify-center">
                        <span class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['content_score'] }}</span>
                    </div>
                    <span class="mt-2 text-sm text-gray-500 dark:text-gray-400">Content</span>
                </div>

                <div class="flex flex-col items-center">
                    <div class="w-20 h-20 rounded-full border-4 {{ $stats['confidence_score'] >= 70 ? 'border-green-500' : 'border-yellow-500' }} flex items-center justify-center">
                        <span class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['confidence_score'] }}</span>
                    </div>
                    <span class="mt-2 text-sm text-gray-500 dark:text-gray-400">Confidence</span>
                </div>

                <div class="flex flex-col items-center">
                    <div class="w-20 h-20 rounded-full border-4 {{ $stats['clarity_score'] >= 70 ? 'border-green-500' : 'border-yellow-500' }} flex items-center justify-center">
                        <span class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['clarity_score'] }}</span>
                    </div>
                    <span class="mt-2 text-sm text-gray-500 dark:text-gray-400">Clarity</span>
                </div>

                <div class="flex flex-col items-center">
                    <div class="w-20 h-20 rounded-full border-4 {{ ($stats['eye_contact_score'] ?? 0) >= 70 ? 'border-green-500' : 'border-yellow-500' }} flex items-center justify-center">
                        <span class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['eye_contact_score'] ?? 'N/A' }}</span>
                    </div>
                    <span class="mt-2 text-sm text-gray-500 dark:text-gray-400">Eye Contact</span>
                </div>
            </div>

            <!-- Speech Stats -->
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['speech_pace_wpm'] ?? 0 }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Words/Min</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_filler_words'] ?? 0 }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Filler Words</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['questions_analyzed'] }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Questions Analyzed</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $session->recordings->count() }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Recordings</div>
                </div>
            </div>
        </div>
    @else
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
            <div class="flex">
                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Analysis in Progress</h3>
                    <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">Your responses are being analyzed. Check back in a few minutes for detailed feedback.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Question-by-Question Results -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Questions List -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Responses</h2>
                </div>
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($session->recordings as $recording)
                        <li>
                            <button wire:click="selectRecording({{ $recording->id }})"
                                    class="w-full px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 text-left transition
                                           {{ $selectedRecordingId == $recording->id ? 'bg-indigo-50 dark:bg-indigo-900/20' : '' }}">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white text-sm">
                                            Q{{ $recording->question?->order ?? $loop->iteration }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[200px]">
                                            {{ Str::limit($recording->question?->question_text ?? 'Full Session', 40) }}
                                        </p>
                                    </div>
                                    @if($recording->analysis)
                                        <span class="text-sm font-semibold {{ $recording->analysis->overall_score >= 70 ? 'text-green-600' : 'text-yellow-600' }}">
                                            {{ $recording->analysis->overall_score }}%
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">Pending</span>
                                    @endif
                                </div>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <!-- Selected Recording Details -->
        <div class="lg:col-span-2">
            @if($selectedRecording)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <!-- Video Player -->
                    <div class="aspect-video bg-gray-900">
                        <video 
                            x-data="{ src: null }"
                            x-init="
                                fetch('{{ route('video-interview.api.playback-url', $selectedRecording) }}')
                                    .then(r => r.json())
                                    .then(data => { src = data.url; })
                            "
                            :src="src"
                            controls
                            class="w-full h-full object-cover">
                        </video>
                    </div>

                    <!-- Analysis Details -->
                    @if($selectedRecording->analysis)
                        <div class="p-6">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Analysis</h3>
                            
                            <!-- Scores Grid -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 text-center">
                                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ $selectedRecording->analysis->overall_score }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Overall</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 text-center">
                                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ $selectedRecording->analysis->content_score ?? '-' }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Content</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 text-center">
                                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ $selectedRecording->analysis->confidence_score ?? '-' }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Confidence</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 text-center">
                                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ $selectedRecording->analysis->speech_pace_wpm ?? '-' }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Words/Min</div>
                                </div>
                            </div>

                            <!-- AI Feedback -->
                            @if($selectedRecording->analysis->ai_feedback)
                                <div class="mb-6">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Feedback</h4>
                                    <p class="text-gray-600 dark:text-gray-400">
                                        {{ $selectedRecording->analysis->ai_feedback }}
                                    </p>
                                </div>
                            @endif

                            <!-- Strengths & Improvements -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @if($selectedRecording->analysis->strengths && count($selectedRecording->analysis->strengths) > 0)
                                    <div>
                                        <h4 class="text-sm font-medium text-green-700 dark:text-green-400 mb-2 flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Strengths
                                        </h4>
                                        <ul class="space-y-1">
                                            @foreach($selectedRecording->analysis->strengths as $strength)
                                                <li class="text-sm text-gray-600 dark:text-gray-400">• {{ $strength }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if($selectedRecording->analysis->improvements && count($selectedRecording->analysis->improvements) > 0)
                                    <div>
                                        <h4 class="text-sm font-medium text-yellow-700 dark:text-yellow-400 mb-2 flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                            </svg>
                                            Areas to Improve
                                        </h4>
                                        <ul class="space-y-1">
                                            @foreach($selectedRecording->analysis->improvements as $improvement)
                                                <li class="text-sm text-gray-600 dark:text-gray-400">• {{ $improvement }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>

                            <!-- Filler Words -->
                            @if($selectedRecording->analysis->filler_words_detected && count($selectedRecording->analysis->filler_words_detected) > 0)
                                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Filler Words Detected</h4>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($selectedRecording->analysis->filler_words_detected as $word => $count)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                                "{{ $word }}" × {{ $count }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Transcription -->
                            @if($selectedRecording->transcription_text)
                                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Transcription</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 italic">
                                        "{{ $selectedRecording->transcription_text }}"
                                    </p>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p>Analysis is being processed...</p>
                            <p class="text-sm mt-1">Check back in a few minutes.</p>
                        </div>
                    @endif
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">Select a recording to view details</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-8 flex items-center justify-center gap-4">
        <a href="{{ route('video-interview.create') }}" 
           class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
            Practice Again
        </a>
        <a href="{{ route('video-interview.sessions') }}" 
           class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white font-semibold rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            View All Sessions
        </a>
    </div>
</div>
