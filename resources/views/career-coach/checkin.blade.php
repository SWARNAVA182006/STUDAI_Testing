@extends('layouts.app')

@section('title', 'Weekly Check-in - AI Career Coach')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-indigo-50/30 to-purple-50/30">
    <!-- Header -->
    <div class="bg-white/80 backdrop-blur-sm border-b border-slate-200 sticky top-0 z-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('career-coach.index') }}" class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-xl font-semibold text-slate-900">Weekly Check-in</h1>
                        <p class="text-sm text-slate-500">Reflect on your progress and set intentions</p>
                    </div>
                </div>
                <span class="text-sm text-slate-500">{{ now()->format('l, F j, Y') }}</span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if($lastCheckin)
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-green-800">Last check-in completed</p>
                        <p class="text-xs text-green-600">{{ $lastCheckin->completed_at->diffForHumans() }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Check-in Form -->
        <form id="checkin-form" class="space-y-8" x-data="checkinForm()">
            <!-- Mood Section -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">How are you feeling about your career this week?</h2>
                
                <div class="grid grid-cols-5 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="mood" value="great" class="hidden peer" x-model="mood">
                        <div class="peer-checked:ring-2 peer-checked:ring-green-500 peer-checked:bg-green-50 rounded-xl p-4 text-center border border-slate-200 hover:border-slate-300 transition">
                            <span class="text-3xl">🤩</span>
                            <p class="text-xs font-medium text-slate-600 mt-2">Great</p>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="mood" value="good" class="hidden peer" x-model="mood">
                        <div class="peer-checked:ring-2 peer-checked:ring-blue-500 peer-checked:bg-blue-50 rounded-xl p-4 text-center border border-slate-200 hover:border-slate-300 transition">
                            <span class="text-3xl">😊</span>
                            <p class="text-xs font-medium text-slate-600 mt-2">Good</p>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="mood" value="okay" class="hidden peer" x-model="mood">
                        <div class="peer-checked:ring-2 peer-checked:ring-yellow-500 peer-checked:bg-yellow-50 rounded-xl p-4 text-center border border-slate-200 hover:border-slate-300 transition">
                            <span class="text-3xl">😐</span>
                            <p class="text-xs font-medium text-slate-600 mt-2">Okay</p>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="mood" value="struggling" class="hidden peer" x-model="mood">
                        <div class="peer-checked:ring-2 peer-checked:ring-orange-500 peer-checked:bg-orange-50 rounded-xl p-4 text-center border border-slate-200 hover:border-slate-300 transition">
                            <span class="text-3xl">😔</span>
                            <p class="text-xs font-medium text-slate-600 mt-2">Struggling</p>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="mood" value="difficult" class="hidden peer" x-model="mood">
                        <div class="peer-checked:ring-2 peer-checked:ring-red-500 peer-checked:bg-red-50 rounded-xl p-4 text-center border border-slate-200 hover:border-slate-300 transition">
                            <span class="text-3xl">😣</span>
                            <p class="text-xs font-medium text-slate-600 mt-2">Difficult</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Progress Summary -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-2">What progress did you make this week?</h2>
                <p class="text-sm text-slate-500 mb-4">Share your accomplishments, activities, or steps forward</p>
                
                <textarea 
                    x-model="progressSummary"
                    rows="4"
                    placeholder="e.g., Applied to 5 jobs, completed a coding project, had a networking call..."
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
                ></textarea>
            </div>

            <!-- Wins -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-2">🎉 Wins & Celebrations</h2>
                <p class="text-sm text-slate-500 mb-4">What went well? Celebrate your achievements, big or small!</p>
                
                <textarea 
                    x-model="wins"
                    rows="3"
                    placeholder="e.g., Got a callback for an interview, learned a new skill, received positive feedback..."
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
                ></textarea>
            </div>

            <!-- Challenges -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-2">🤔 Challenges & Obstacles</h2>
                <p class="text-sm text-slate-500 mb-4">What challenges did you face? What's blocking your progress?</p>
                
                <textarea 
                    x-model="challenges"
                    rows="3"
                    placeholder="e.g., Not hearing back from applications, unsure about career direction, struggling with motivation..."
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
                ></textarea>
            </div>

            <!-- Goal Updates -->
            @if($goals->count() > 0)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 mb-4">📊 Goal Progress</h2>
                    
                    <div class="space-y-4">
                        @foreach($goals as $goal)
                            <div class="border border-slate-200 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="font-medium text-slate-900">{{ $goal->title }}</h3>
                                    <span class="text-sm text-slate-500">Current: {{ $goal->progress }}%</span>
                                </div>
                                
                                <div class="flex items-center space-x-4">
                                    <input 
                                        type="range" 
                                        min="0" 
                                        max="100" 
                                        step="5"
                                        x-model="goalUpdates['{{ $goal->id }}'].progress"
                                        x-init="goalUpdates['{{ $goal->id }}'] = { progress: {{ $goal->progress }} }"
                                        class="flex-1 h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-indigo-600"
                                    >
                                    <span 
                                        class="text-sm font-medium text-indigo-600 w-12 text-right"
                                        x-text="goalUpdates['{{ $goal->id }}']?.progress + '%'"
                                    >{{ $goal->progress }}%</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Focus Areas for Next Week -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-2">🎯 Focus Areas for Next Week</h2>
                <p class="text-sm text-slate-500 mb-4">What do you want to prioritize?</p>
                
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @php
                        $focusOptions = [
                            'job_applications' => 'Job Applications',
                            'networking' => 'Networking',
                            'skill_building' => 'Skill Building',
                            'interview_prep' => 'Interview Prep',
                            'resume_update' => 'Resume Update',
                            'portfolio' => 'Portfolio Work',
                            'career_research' => 'Career Research',
                            'personal_branding' => 'Personal Branding',
                            'work_life_balance' => 'Work-Life Balance',
                        ];
                    @endphp
                    
                    @foreach($focusOptions as $value => $label)
                        <label class="cursor-pointer">
                            <input type="checkbox" value="{{ $value }}" class="hidden peer" x-model="focusAreas">
                            <div class="peer-checked:ring-2 peer-checked:ring-indigo-500 peer-checked:bg-indigo-50 rounded-lg p-3 text-center border border-slate-200 hover:border-slate-300 transition">
                                <span class="text-sm font-medium text-slate-700">{{ $label }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-between pt-4">
                <a href="{{ route('career-coach.index') }}" class="text-slate-600 hover:text-slate-800 transition">
                    Skip for now
                </a>
                
                <button 
                    type="submit"
                    :disabled="submitting || !mood || !progressSummary"
                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-medium rounded-xl hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span x-show="!submitting">Complete Check-in</span>
                    <span x-show="submitting" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Processing...
                    </span>
                </button>
            </div>
        </form>

        <!-- AI Response Section (shown after submit) -->
        <div x-show="completed" x-cloak class="mt-8 bg-gradient-to-br from-indigo-50 to-purple-50 rounded-2xl border border-indigo-200 p-6">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900">AI Coach Insights</h3>
            </div>
            
            <div class="prose prose-sm max-w-none text-slate-700" x-html="aiResponse"></div>
            
            <div class="mt-6 flex items-center space-x-4">
                <a href="{{ route('career-coach.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    Continue to Coach Dashboard
                </a>
                <a href="{{ route('career-coach.session.create') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                    Start a Conversation
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function checkinForm() {
    return {
        mood: '',
        progressSummary: '',
        wins: '',
        challenges: '',
        focusAreas: [],
        goalUpdates: {},
        submitting: false,
        completed: false,
        aiResponse: '',
        
        async submitForm() {
            if (this.submitting || !this.mood || !this.progressSummary) return;
            
            this.submitting = true;
            
            try {
                const response = await fetch('{{ route("career-coach.checkin.process") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        mood: this.mood,
                        progress_summary: this.progressSummary,
                        wins: this.wins,
                        challenges: this.challenges,
                        focus_areas: this.focusAreas,
                        goal_updates: this.goalUpdates,
                    }),
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.completed = true;
                    this.aiResponse = data.result?.ai_summary || 'Great job completing your weekly check-in! Keep up the momentum.';
                } else {
                    throw new Error(data.message || 'Failed to process check-in');
                }
            } catch (error) {
                console.error('Check-in error:', error);
                alert('There was an error processing your check-in. Please try again.');
            } finally {
                this.submitting = false;
            }
        },
        
        init() {
            this.$watch('$el', () => {
                document.getElementById('checkin-form').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.submitForm();
                });
            });
        }
    };
}
</script>
@endpush
@endsection
