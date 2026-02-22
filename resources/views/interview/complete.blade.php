@php
    $questionIndex = 0;
    $questionLookup = [];
    foreach (($session['questions'] ?? []) as $type => $items) {
        foreach ($items as $item) {
            $questionLookup[$questionIndex] = [
                'type' => ucfirst($type),
                'meta' => $item,
            ];
            $questionIndex++;
        }
    }

    $answers = $session['answers'] ?? [];
    $totalAnswered = count($answers);
    $scoredCount = 0;
    $scoreSum = 0;
    $categoryStats = [
        'Behavioral' => ['count' => 0, 'score' => 0],
        'Technical' => ['count' => 0, 'score' => 0],
        'Situational' => ['count' => 0, 'score' => 0],
        'General' => ['count' => 0, 'score' => 0],
    ];
    $allStrengths = [];
    $allImprovements = [];
    $allSuggestions = [];

    foreach ($answers as $index => $answer) {
        $index = (int) $index;
        $type = $questionLookup[$index]['type'] ?? 'General';
        $categoryStats[$type]['count']++;

        $score = $answer['evaluation']['score'] ?? null;
        if (is_numeric($score)) {
            $scoredCount++;
            $scoreSum += $score;
            $categoryStats[$type]['score'] += $score;
        }

        $allStrengths = array_merge($allStrengths, $answer['evaluation']['strengths'] ?? []);
        $allImprovements = array_merge($allImprovements, $answer['evaluation']['areas_for_improvement'] ?? []);
        $allSuggestions = array_merge($allSuggestions, $answer['evaluation']['suggestions'] ?? []);
    }

    $averageScore = $scoredCount > 0 ? round($scoreSum / $scoredCount) : 0;
    $gradeConfig = $grade ?? ['grade' => 'N/A', 'label' => 'Need more data', 'color' => 'gray'];

    $categoryAverages = collect($categoryStats)->map(function ($data) {
        return [
            'count' => $data['count'],
            'average' => $data['count'] ? round($data['score'] / $data['count']) : null,
        ];
    });

    $topStrengths = collect($allStrengths)->filter()->unique()->take(5);
    $topImprovements = collect($allImprovements)->filter()->unique()->take(5);
    $topSuggestions = collect($allSuggestions)->filter()->unique()->take(5);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mock Interview Report</h2>
                <p class="text-sm text-gray-500">Role: {{ $session['job_title'] }} &middot; Level: {{ ucfirst($session['experience_level']) }} &middot; Questions answered: {{ $totalAnswered }} / {{ $totalQuestions }}</p>
            </div>
            <a href="{{ route('interview.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Back to dashboard</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <div class="bg-white shadow-lg rounded-xl overflow-hidden">
                <div class="p-6 bg-gradient-to-r from-indigo-600 to-purple-600 text-white flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <p class="text-sm uppercase tracking-widest text-indigo-100">Overall Assessment</p>
                        <div class="flex items-end gap-4 mt-2">
                            <span class="text-5xl font-bold">{{ $averageScore }}</span>
                            <span class="text-2xl mb-1">/ 100</span>
                        </div>
                        <p class="text-indigo-100 mt-2">Consistent practice will keep pushing your score higher. Review the insights below to strengthen weak spots.</p>
                    </div>
                    <div class="flex flex-col items-end gap-3">
                        <span class="text-sm uppercase tracking-wide text-indigo-100">Performance Grade</span>
                        <span class="inline-flex items-center px-4 py-2 rounded-lg font-semibold bg-white/20 text-white">
                            {{ $gradeConfig['grade'] }} &middot; {{ $gradeConfig['label'] }}
                        </span>
                        <span class="text-sm text-indigo-100">Completed at {{ now()->format('d M Y, h:i A') }}</span>
                    </div>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach(['Behavioral', 'Technical', 'Situational'] as $segment)
                        @php
                            $data = $categoryAverages[$segment];
                            $segmentWidth = sprintf('width: %s%%;', $data['average'] ?? 0);
                        @endphp
                        <div class="border border-gray-100 rounded-lg p-4">
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ $segment }}
                                <span class="text-[10px] text-gray-400">({{ $categoryStats[$segment]['count'] }} questions)</span>
                            </p>
                            <div class="flex items-baseline gap-2 mt-2">
                                <span class="text-3xl font-bold text-gray-900">{{ $data['average'] ?? '—' }}</span>
                                @if(!is_null($data['average']))
                                    <span class="text-sm text-gray-500">/ 100</span>
                                @endif
                            </div>
                            <div class="mt-3 h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-2 bg-indigo-500" style="<?php echo e($segmentWidth); ?>"></div>
                            </div>
                        </div>
                    @endforeach
                    <div class="border border-gray-100 rounded-lg p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Completion</p>
                        <div class="flex items-baseline gap-2 mt-2">
                            <span class="text-3xl font-bold text-gray-900">{{ $totalAnswered }}</span>
                            <span class="text-sm text-gray-500">of {{ $totalQuestions }} questions</span>
                        </div>
                        @php
                            $completionWidth = sprintf('width: %s%%;', $totalQuestions > 0 ? round(($totalAnswered / $totalQuestions) * 100) : 0);
                        @endphp
                        <div class="mt-3 h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-2 bg-emerald-500" style="<?php echo e($completionWidth); ?>"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Insights -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white shadow-sm rounded-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">AI Highlights</h3>
                            <span class="text-xs uppercase tracking-wide text-gray-500">Generated from your answers</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="text-sm font-semibold text-emerald-700 uppercase tracking-wide mb-3">Top Strengths</h4>
                                <ul class="space-y-2">
                                    @forelse ($topStrengths as $strength)
                                        <li class="text-sm text-gray-700 flex items-start gap-2">
                                            <i class="fas fa-plus text-emerald-500 mt-1"></i>
                                            <span>{{ $strength }}</span>
                                        </li>
                                    @empty
                                        <li class="text-sm text-gray-500">Complete more answers to unlock tailored strengths.</li>
                                    @endforelse
                                </ul>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-amber-700 uppercase tracking-wide mb-3">Focus Areas</h4>
                                <ul class="space-y-2">
                                    @forelse ($topImprovements as $item)
                                        <li class="text-sm text-gray-700 flex items-start gap-2">
                                            <i class="fas fa-arrow-trend-up text-amber-500 mt-1"></i>
                                            <span>{{ $item }}</span>
                                        </li>
                                    @empty
                                        <li class="text-sm text-gray-500">No improvement notes yet. Try saving more answers for deeper insights.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                        <div class="mt-6">
                            <h4 class="text-sm font-semibold text-indigo-700 uppercase tracking-wide mb-3">Actionable Suggestions</h4>
                            <ul class="space-y-2">
                                @forelse ($topSuggestions as $suggestion)
                                    <li class="text-sm text-gray-700 flex items-start gap-2">
                                        <i class="fas fa-check text-indigo-500 mt-1"></i>
                                        <span>{{ $suggestion }}</span>
                                    </li>
                                @empty
                                    <li class="text-sm text-gray-500">We need at least one evaluated answer to provide suggestions.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm rounded-xl overflow-hidden">
                        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">Question-by-Question Review</h3>
                            <span class="text-xs uppercase tracking-wide text-gray-500">Scores & feedback</span>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @forelse ($answers as $index => $answer)
                                @php
                                    $meta = $questionLookup[(int) $index] ?? [];
                                    $score = $answer['evaluation']['score'] ?? null;
                                @endphp
                                <div class="p-6">
                                    <div class="flex flex-wrap justify-between gap-3">
                                        <div>
                                            <p class="text-xs uppercase tracking-wide text-gray-500">Question {{ (int) $index + 1 }} &middot; {{ $meta['type'] ?? 'General' }}</p>
                                            <p class="text-sm font-semibold text-gray-900 mt-1">{{ $answer['question'] }}</p>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-xs uppercase tracking-wide text-gray-500">Score</span>
                                            <p class="text-2xl font-bold text-indigo-600">{{ $score !== null ? $score : '—' }}</p>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-700 mt-3">{{ $answer['evaluation']['overall_feedback'] ?? 'Answer recorded. Add more detail and request AI feedback to unlock insights.' }}</p>
                                </div>
                            @empty
                                <div class="p-6 text-center text-gray-500">
                                    No answers saved yet. Go back to the session and submit at least one answer to generate a report.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white shadow-sm rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recommended Next Steps</h3>
                        <ul class="space-y-3 text-sm text-gray-700">
                            <li class="flex items-start gap-3">
                                <i class="fas fa-repeat text-indigo-600 mt-1"></i>
                                Schedule another mock session focusing on your lowest scoring area.
                            </li>
                            <li class="flex items-start gap-3">
                                <i class="fas fa-microphone text-indigo-600 mt-1"></i>
                                Practice speaking your answers aloud and compare them against the written feedback.
                            </li>
                            <li class="flex items-start gap-3">
                                <i class="fas fa-users text-indigo-600 mt-1"></i>
                                Share this report with a mentor or coach to get human feedback too.
                            </li>
                            <li class="flex items-start gap-3">
                                <i class="fas fa-book text-indigo-600 mt-1"></i>
                                Review our STAR method guide to strengthen storytelling in behavioral answers.
                            </li>
                        </ul>
                        <div class="mt-6 flex flex-col gap-2">
                            <a href="{{ route('interview.star-guide') }}" class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold hover:bg-indigo-700 transition">
                                <i class="fas fa-compass mr-2"></i> Review STAR guide
                            </a>
                            <a href="{{ route('interview.tips') }}" class="inline-flex items-center justify-center px-4 py-2 border border-gray-200 rounded-md font-semibold text-gray-700 hover:bg-gray-50 transition">
                                <i class="fas fa-lightbulb mr-2"></i> Browse interview tips
                            </a>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Download & Share</h3>
                        <p class="text-sm text-gray-600 mb-4">Export your responses and AI feedback to review offline or share with your accountability partner.</p>
                        <div class="space-y-3">
                            <button class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-200 rounded-md text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                                <i class="fas fa-file-pdf mr-2 text-red-500"></i> Download PDF Report (coming soon)
                            </button>
                            <button class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-200 rounded-md text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                                <i class="fas fa-share-alt mr-2 text-indigo-500"></i> Share with mentor (coming soon)
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-3">Export options will automatically include new sessions as we release them.</p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl shadow-lg p-8 text-white text-center">
                <h3 class="text-2xl font-bold mb-3">Keep Your Momentum Going</h3>
                <p class="text-indigo-100 max-w-2xl mx-auto">Every practice session compounds your confidence. Revisit tough questions, explore common interview question banks, and keep negotiating for what you deserve.</p>
                <div class="mt-6 flex flex-wrap justify-center gap-4">
                    <a href="{{ route('interview.common-questions') }}" class="inline-flex items-center px-6 py-3 bg-white text-indigo-600 rounded-lg font-semibold hover:bg-indigo-100 transition">
                        <i class="fas fa-layer-group mr-2"></i> Explore common questions
                    </a>
                    <a href="{{ route('interview.create') }}" class="inline-flex items-center px-6 py-3 bg-purple-500 text-white rounded-lg font-semibold hover:bg-purple-400 transition">
                        <i class="fas fa-play mr-2"></i> Start another practice session
                    </a>
                    <a href="{{ route('interview.salary-negotiation') }}" class="inline-flex items-center px-6 py-3 bg-indigo-500 text-white rounded-lg font-semibold hover:bg-indigo-400 transition">
                        <i class="fas fa-handshake mr-2"></i> Prep salary negotiation
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
