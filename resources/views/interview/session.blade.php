@php
    $questionIndex = 0;
    $flattenedQuestions = [];
    foreach (($session['questions'] ?? []) as $type => $items) {
        foreach ($items as $item) {
            $flattenedQuestions[] = array_merge($item, [
                'type' => $type,
                'index' => $questionIndex,
            ]);
            $questionIndex++;
        }
    }
    $savedAnswers = $session['answers'] ?? [];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Interview Practice Session') }}
                </h2>
                <p class="text-sm text-gray-500">Role: {{ $session['job_title'] }} &middot; Level: {{ ucfirst($session['experience_level']) }}</p>
            </div>
            <div class="flex items-center gap-3 text-sm text-gray-600">
                <span class="inline-flex items-center px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 font-medium">
                    {{ count($flattenedQuestions) }} Questions
                </span>
                @if(!empty($session['company']))
                    <span class="hidden md:inline">|</span>
                    <span>Company focus: {{ $session['company'] }}</span>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div x-data="interviewSession({
                    questions: @json($flattenedQuestions),
                    answers: @json($savedAnswers),
                    sessionId: '{{ $sessionId }}',
                    submitUrl: '{{ route('interview.submit-answer', $sessionId) }}',
                    followUpUrl: '{{ route('interview.follow-up', $sessionId) }}',
                    completeUrl: '{{ route('interview.complete', $sessionId) }}'
                })" x-init="init()" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Primary Column -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Progress Bar -->
                    <div>
                        <div class="flex justify-between items-center mb-2 text-sm text-gray-600">
                            <span>Question <span x-text="currentQuestionNumber"></span> of <span x-text="totalQuestions"></span></span>
                            <span>Time on this question: <span x-text="formattedQuestionTimer"></span></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-indigo-600 h-2 transition-all duration-500" :style="`width: ${progressPercent}%`"></div>
                        </div>
                    </div>

                    <!-- Question Card -->
                    <div class="bg-white rounded-lg shadow-lg p-6 space-y-6">
                        <div class="flex flex-wrap items-start gap-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold tracking-wide" :class="questionBadgeClass">
                                <i class="fas fa-question-circle mr-2"></i>
                                <span x-text="currentQuestion.typeLabel"></span>
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700" x-show="currentQuestion.difficulty">
                                <i class="fas fa-signal mr-2"></i>
                                <span class="capitalize" x-text="currentQuestion.difficulty"></span>
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700" x-show="answers[currentIndex]">
                                <i class="fas fa-star mr-1"></i>
                                Answer saved
                            </span>
                        </div>

                        <div>
                            <h3 class="text-2xl font-bold text-gray-900" x-text="currentQuestion.question"></h3>
                            <template x-if="currentQuestion.context">
                                <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                                    <p class="text-sm text-gray-700" x-text="currentQuestion.context"></p>
                                </div>
                            </template>
                            <template x-if="currentQuestion.topic">
                                <p class="mt-2 text-xs uppercase tracking-wide text-indigo-600">Topic: <span class="font-semibold" x-text="currentQuestion.topic"></span></p>
                            </template>
                            <template x-if="currentQuestion.category && !currentQuestion.topic">
                                <p class="mt-2 text-xs uppercase tracking-wide text-indigo-600">Category: <span class="font-semibold" x-text="currentQuestion.category"></span></p>
                            </template>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Your Answer</label>
                            <textarea x-model="answerText" rows="8" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Type your response here... Include specific examples and quantify your impact where possible."></textarea>
                            <p class="mt-2 text-xs text-gray-500">Tip: Speak your answer out loud first, then summarize it here to capture key points.</p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button type="button" @click="saveAnswer" :disabled="saving" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold hover:bg-indigo-700 transition disabled:opacity-70">
                                <i class="fas fa-save mr-2"></i>
                                <span x-text="saving ? 'Saving...' : 'Save & Get Feedback'"></span>
                            </button>
                            <button type="button" @click="fetchFollowUps" :disabled="loadingFollowUps" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-md font-semibold hover:bg-gray-50 transition disabled:opacity-60">
                                <i class="fas fa-comments mr-2"></i>
                                <span x-text="loadingFollowUps ? 'Loading...' : 'Ask for follow-up questions'"></span>
                            </button>
                            <button type="button" @click="skipQuestion" class="inline-flex items-center px-4 py-2 border border-transparent text-red-600 font-semibold rounded-md hover:bg-red-50 transition">
                                <i class="fas fa-forward mr-2"></i> Skip Question
                            </button>
                        </div>

                        <!-- Evaluation -->
                        <template x-if="answers[currentIndex]?.evaluation">
                            <div class="border border-green-200 bg-green-50 rounded-lg p-5 space-y-4">
                                <div class="flex flex-wrap justify-between items-center">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-full bg-white shadow flex items-center justify-center">
                                            <span class="text-lg font-bold text-green-600" x-text="answers[currentIndex].evaluation.score ?? '—'"></span>
                                        </div>
                                        <div>
                                            <p class="text-sm text-green-700 font-semibold">AI Feedback</p>
                                            <p class="text-xs text-green-600">Higher scores mean stronger, more structured answers.</p>
                                        </div>
                                    </div>
                                    <button type="button" @click="toggleFeedback" class="text-xs uppercase tracking-wide font-semibold text-green-600">Toggle Details</button>
                                </div>

                                <div x-show="showFeedback" class="space-y-4">
                                    <div>
                                        <p class="text-sm font-semibold text-green-900">Strengths</p>
                                        <ul class="mt-2 space-y-1 text-sm text-green-800 list-disc list-inside">
                                            <template x-for="strength in answers[currentIndex].evaluation.strengths ?? []" :key="strength">
                                                <li x-text="strength"></li>
                                            </template>
                                        </ul>
                                    </div>

                                    <div>
                                        <p class="text-sm font-semibold text-amber-900">Areas to Improve</p>
                                        <ul class="mt-2 space-y-1 text-sm text-amber-800 list-disc list-inside">
                                            <template x-for="gap in answers[currentIndex].evaluation.areas_for_improvement ?? []" :key="gap">
                                                <li x-text="gap"></li>
                                            </template>
                                        </ul>
                                    </div>

                                    <template x-if="answers[currentIndex].evaluation.star_method_usage">
                                        <div class="bg-white rounded-md p-4 shadow-sm">
                                            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">STAR Method Coverage</p>
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                                                <template x-for="(used, key) in answers[currentIndex].evaluation.star_method_usage" :key="key">
                                                    <div class="flex items-center gap-2">
                                                        <div :class="used ? 'bg-green-500' : 'bg-gray-300'" class="w-2 h-2 rounded-full"></div>
                                                        <span class="capitalize" x-text="key"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <p class="text-sm text-gray-700" x-text="answers[currentIndex].evaluation.overall_feedback"></p>
                                </div>
                            </div>
                        </template>

                        <!-- Follow Up Questions -->
                        <template x-if="followUps.length">
                            <div class="border border-purple-200 bg-purple-50 rounded-lg p-4 space-y-2">
                                <div class="flex items-center gap-2 text-purple-700 font-semibold">
                                    <i class="fas fa-question mr-1"></i> Consider these follow-up questions
                                </div>
                                <ul class="text-sm text-purple-800 space-y-2">
                                    <template x-for="item in followUps" :key="item.question">
                                        <li>
                                            <p class="font-medium" x-text="item.question"></p>
                                            <p class="text-xs uppercase tracking-wide" x-text="item.purpose" ></p>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </template>

                        <!-- Navigation -->
                        <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-100">
                            <button type="button" @click="previousQuestion" :disabled="currentIndex === 0" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 font-semibold hover:bg-gray-50 disabled:opacity-50">
                                <i class="fas fa-arrow-left mr-2"></i> Previous
                            </button>
                            <button type="button" @click="nextQuestion" :disabled="currentIndex === totalQuestions - 1" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 font-semibold hover:bg-gray-50 disabled:opacity-50">
                                Next <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                            <div class="flex-1"></div>
                            <button type="button" @click="finishSession" class="inline-flex items-center px-5 py-2 bg-emerald-600 text-white rounded-md font-semibold hover:bg-emerald-700 transition">
                                <i class="fas fa-flag-checkered mr-2"></i> Finish & View Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Secondary Column -->
                <div class="space-y-6">
                    <!-- Question Navigator -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Question Navigator</h3>
                            <span class="text-xs text-gray-500">Click to jump</span>
                        </div>
                        <div class="grid grid-cols-5 gap-2">
                            <template x-for="question in questions" :key="question.index">
                                <button type="button" @click="goTo(question.index)" :class="navigatorClass(question.index)" class="aspect-square rounded-md text-xs font-semibold flex items-center justify-center">
                                    <span x-text="question.index + 1"></span>
                                </button>
                            </template>
                        </div>
                        <div class="mt-4 text-xs text-gray-500 space-y-1">
                            <p><span class="inline-block w-3 h-3 bg-indigo-500 rounded-sm mr-2"></span> Current question</p>
                            <p><span class="inline-block w-3 h-3 bg-emerald-500 rounded-sm mr-2"></span> Answer saved</p>
                            <p><span class="inline-block w-3 h-3 bg-gray-200 rounded-sm mr-2"></span> Pending</p>
                        </div>
                    </div>

                    <!-- Session Summary -->
                    <div class="bg-white rounded-lg shadow-sm p-6 space-y-4">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Session Snapshot</h3>
                        <ul class="space-y-3 text-sm text-gray-700">
                            <li class="flex items-center justify-between">
                                <span>Total time elapsed</span>
                                <span class="font-semibold" x-text="formattedTotalTimer"></span>
                            </li>
                            <li class="flex items-center justify-between">
                                <span>Questions answered</span>
                                <span class="font-semibold" x-text="answeredCount + ' / ' + totalQuestions"></span>
                            </li>
                            <li class="flex items-center justify-between">
                                <span>Average score</span>
                                <span class="font-semibold" x-text="averageScore"></span>
                            </li>
                        </ul>
                        <div class="pt-3 border-t border-gray-100 text-xs text-gray-500">
                            Answers auto-save locally. Remember to click "Finish" to generate your detailed report.
                        </div>
                    </div>

                    <!-- Quick Tips -->
                    <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-lg shadow-sm p-6 space-y-4">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-lightbulb text-indigo-600 text-xl"></i>
                            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Coach's Corner</h3>
                        </div>
                        <ul class="space-y-3 text-sm text-gray-700">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-600 mt-1"></i>
                                Lead with the Situation/Task, highlight your Action, and quantify the Result.
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-600 mt-1"></i>
                                Pause after reading the question—take 20 seconds to outline your answer.
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-600 mt-1"></i>
                                Mirror company values or job requirements mentioned in the role description.
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-600 mt-1"></i>
                                Mention tools, metrics, and collaboration partners to show depth and credibility.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('interviewSession', (config) => ({
                questions: config.questions || [],
                answers: config.answers || {},
                sessionId: config.sessionId,
                submitUrl: config.submitUrl,
                followUpUrl: config.followUpUrl,
                completeUrl: config.completeUrl,
                currentIndex: 0,
                answerText: '',
                saving: false,
                showFeedback: true,
                followUps: [],
                loadingFollowUps: false,
                questionSeconds: 0,
                totalSeconds: 0,
                timerInterval: null,
                questionInterval: null,

                init() {
                    if (this.questions.length === 0) {
                        return;
                    }
                    this.loadAnswer();
                    this.startTimers();
                },

                get currentQuestion() {
                    return this.questions[this.currentIndex] || {};
                },

                get currentQuestionNumber() {
                    return this.currentIndex + 1;
                },

                get totalQuestions() {
                    return this.questions.length;
                },

                get progressPercent() {
                    return Math.round(((this.currentIndex + 1) / this.totalQuestions) * 100);
                },

                get answeredCount() {
                    return Object.keys(this.answers).length;
                },

                get averageScore() {
                    const scores = Object.values(this.answers)
                        .map(entry => entry.evaluation?.score)
                        .filter(score => typeof score === 'number');
                    if (scores.length === 0) {
                        return '—';
                    }
                    const total = scores.reduce((acc, val) => acc + val, 0);
                    return Math.round(total / scores.length) + '%';
                },

                get formattedQuestionTimer() {
                    return this.formatSeconds(this.questionSeconds);
                },

                get formattedTotalTimer() {
                    return this.formatSeconds(this.totalSeconds);
                },

                get questionBadgeClass() {
                    switch (this.currentQuestion.type) {
                        case 'behavioral':
                            return 'bg-yellow-100 text-yellow-800';
                        case 'technical':
                            return 'bg-blue-100 text-blue-800';
                        case 'situational':
                            return 'bg-purple-100 text-purple-800';
                        default:
                            return 'bg-gray-100 text-gray-700';
                    }
                },

                startTimers() {
                    this.timerInterval = setInterval(() => {
                        this.totalSeconds++;
                    }, 1000);

                    this.questionInterval = setInterval(() => {
                        this.questionSeconds++;
                    }, 1000);
                },

                resetQuestionTimer() {
                    this.questionSeconds = 0;
                },

                formatSeconds(seconds) {
                    const mins = Math.floor(seconds / 60);
                    const secs = seconds % 60;
                    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
                },

                loadAnswer() {
                    const existing = this.answers[this.currentIndex];
                    this.answerText = existing?.answer || '';
                    this.followUps = [];
                    this.resetQuestionTimer();
                },

                goTo(index) {
                    if (index < 0 || index >= this.totalQuestions) {
                        return;
                    }
                    this.currentIndex = index;
                    this.loadAnswer();
                    this.showFeedback = true;
                },

                previousQuestion() {
                    this.goTo(this.currentIndex - 1);
                },

                nextQuestion() {
                    this.goTo(this.currentIndex + 1);
                },

                navigatorClass(index) {
                    if (index === this.currentIndex) {
                        return 'bg-indigo-500 text-white shadow';
                    }
                    if (this.answers[index]) {
                        return 'bg-emerald-500 text-white shadow';
                    }
                    return 'bg-gray-200 text-gray-600 hover:bg-gray-300';
                },

                async saveAnswer() {
                    if (!this.answerText.trim()) {
                        alert('Please type your answer before requesting feedback.');
                        return;
                    }

                    this.saving = true;
                    try {
                        const response = await fetch(this.submitUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                question_index: this.currentIndex,
                                question: this.currentQuestion.question,
                                answer: this.answerText,
                            })
                        });

                        if (!response.ok) {
                            throw new Error('Failed to save answer.');
                        }

                        const data = await response.json();
                        if (data.success) {
                            this.answers[this.currentIndex] = {
                                question: this.currentQuestion.question,
                                answer: this.answerText,
                                evaluation: data.evaluation || null,
                                saved_at: new Date().toISOString(),
                            };
                            this.showFeedback = true;
                        }
                    } catch (error) {
                        console.error(error);
                        alert('We could not save your answer right now. Please try again.');
                    } finally {
                        this.saving = false;
                    }
                },

                async fetchFollowUps() {
                    if (!this.answerText.trim()) {
                        alert('Provide an answer first to get follow-up questions.');
                        return;
                    }
                    this.loadingFollowUps = true;
                    this.followUps = [];

                    try {
                        const response = await fetch(this.followUpUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                question: this.currentQuestion.question,
                                answer: this.answerText,
                            })
                        });

                        if (!response.ok) {
                            throw new Error('Failed to fetch follow-up questions.');
                        }

                        this.followUps = await response.json();
                    } catch (error) {
                        console.error(error);
                        alert('Unable to fetch follow-up questions at the moment.');
                    } finally {
                        this.loadingFollowUps = false;
                    }
                },

                skipQuestion() {
                    if (confirm('Skip this question and return later?')) {
                        this.nextQuestion();
                    }
                },

                toggleFeedback() {
                    this.showFeedback = !this.showFeedback;
                },

                finishSession() {
                    if (this.answeredCount === 0) {
                        if (!confirm('You have not saved any answers yet. Are you sure you want to finish?')) {
                            return;
                        }
                    }
                    window.location.href = this.completeUrl;
                },

                beforeDestroy() {
                    clearInterval(this.timerInterval);
                    clearInterval(this.questionInterval);
                }
            }));
        });
    </script>
    @endpush
</x-app-layout>
