@extends('layouts.app')

@section('title', 'Learning Paths')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">Learning Paths</h1>
                    <p class="text-lg text-gray-600">Personalized journeys to master your skill gaps</p>
                </div>
                <a href="{{ route('skills.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Dashboard
                </a>
            </div>
        </div>

        {{-- Active Learning Paths --}}
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Active Learning Paths</h2>
            
            @if($activePaths->count() > 0)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($activePaths as $path)
                <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow border border-gray-200">
                    {{-- Path Header --}}
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6 text-white">
                        <div class="flex items-start justify-between mb-3">
                            <h3 class="text-xl font-bold">{{ $path->title }}</h3>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-white bg-opacity-20">
                                {{ $path->statusBadge }}
                            </span>
                        </div>
                        <p class="text-indigo-100 text-sm mb-4">{{ $path->description }}</p>
                        
                        {{-- Progress Bar --}}
                        <div class="mb-2">
                            <div class="flex justify-between text-xs text-indigo-100 mb-1">
                                <span>Progress</span>
                                <span>{{ $path->progressPercentage }}%</span>
                            </div>
                            <div class="w-full bg-indigo-700 rounded-full h-2">
                                <div class="bg-white rounded-full h-2 transition-all duration-500" style="width: {{ $path->progressPercentage }}%"></div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between text-xs text-indigo-100">
                            <span>{{ $path->resources->where('step_order', '<=', ($path->completion_percentage / 100) * $path->total_resources)->count() }} / {{ $path->total_resources }} resources completed</span>
                            <span>{{ $path->remainingHours }}</span>
                        </div>
                    </div>

                    {{-- Path Body --}}
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ $path->dailyTimeCommitment }} daily
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ $path->estimatedCompletionDate }}
                            </div>
                        </div>

                        {{-- Next Resource Preview --}}
                        @php $nextResource = $path->getNextResource(); @endphp
                        @if($nextResource)
                        <div class="bg-blue-50 rounded-lg p-4 mb-4 border border-blue-200">
                            <p class="text-xs font-medium text-blue-800 mb-2">NEXT UP</p>
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 text-sm">{{ $nextResource->title }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $nextResource->typeBadge }} · {{ $nextResource->durationFormatted }}</p>
                                </div>
                                <a href="{{ $nextResource->url }}" target="_blank" class="ml-3 inline-flex items-center px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-medium hover:bg-blue-700 transition-colors">
                                    Start
                                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                        @endif

                        {{-- Milestones --}}
                        @if(!empty($path->steps))
                        <div class="mb-4">
                            <p class="text-xs font-medium text-gray-500 uppercase mb-2">Milestones</p>
                            <div class="space-y-2">
                                @foreach($path->steps as $step)
                                <div class="flex items-center text-sm">
                                    @if($path->completion_percentage >= ($step['target_proficiency'] ?? 0))
                                    <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-gray-400 line-through">{{ $step['name'] }}</span>
                                    @else
                                    <svg class="w-4 h-4 mr-2 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm0-2a6 6 0 100-12 6 6 0 000 12z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-gray-700">{{ $step['name'] }}</span>
                                    @endif
                                    <span class="ml-auto text-xs text-gray-500">{{ $step['target_proficiency'] }}%</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Actions --}}
                        <div class="flex items-center space-x-3">
                            <a href="{{ route('skills.learning-path.show', $path->id) }}" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                                View Details
                            </a>
                            @if($path->status === 'active')
                            <button onclick="pausePath({{ $path->id }})" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300 transition-colors">
                                Pause
                            </button>
                            @else
                            <button onclick="resumePath({{ $path->id }})" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                                Resume
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="bg-white rounded-xl shadow-lg p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Active Learning Paths</h3>
                <p class="text-gray-500 mb-4">Generate personalized learning paths from your skill gaps</p>
                <a href="{{ route('skills.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">
                    View Skill Gaps
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            </div>
            @endif
        </div>

        {{-- Completed Paths --}}
        @if($completedPaths->count() > 0)
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Completed Learning Paths</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($completedPaths as $path)
                <div class="bg-white rounded-xl shadow-md overflow-hidden border-2 border-green-500">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-3">
                            <h3 class="text-lg font-bold text-gray-900">{{ $path->title }}</h3>
                            <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">{{ $path->description }}</p>
                        
                        <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                            <span>{{ $path->total_resources }} resources</span>
                            <span>{{ round($path->total_duration_minutes / 60, 1) }} hours</span>
                        </div>

                        <div class="pt-4 border-t border-gray-200">
                            <a href="{{ route('skills.learning-path.show', $path->id) }}" class="text-indigo-600 hover:text-indigo-700 font-medium text-sm">
                                Review Path →
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<script>
function pausePath(pathId) {
    if (confirm('Pause this learning path?')) {
        // Implementation would call API to pause path
        alert('Path paused. You can resume it anytime from this page.');
    }
}

function resumePath(pathId) {
    if (confirm('Resume this learning path?')) {
        // Implementation would call API to resume path
        alert('Path resumed. Continue where you left off!');
    }
}
</script>
@endsection
