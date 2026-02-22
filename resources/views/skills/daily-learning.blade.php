@extends('layouts.app')

@section('title', 'Daily Learning')

@section('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
@endsection

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-pink-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header with Date --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">📚 Today's Learning</h1>
                    <p class="text-lg text-gray-600">{{ now()->format('l, F j, Y') }}</p>
                </div>
                <a href="{{ route('skills.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Dashboard
                </a>
            </div>
        </div>

        {{-- Learning Streak Card --}}
        <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-xl shadow-2xl p-8 mb-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center mb-2">
                        <span class="text-6xl mr-4">🔥</span>
                        <div>
                            <h2 class="text-4xl font-bold">{{ $learningStreak ?? 0 }} Day Streak</h2>
                            <p class="text-orange-100 text-lg">Keep the momentum going!</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4 mt-4">
                        @if(($learningStreak ?? 0) >= 365)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-white bg-opacity-20">
                            🏆 1 Year Champion
                        </span>
                        @endif
                        @if(($learningStreak ?? 0) >= 90)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-white bg-opacity-20">
                            💎 90 Day Master
                        </span>
                        @endif
                        @if(($learningStreak ?? 0) >= 30)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-white bg-opacity-20">
                            ⭐ 30 Day Hero
                        </span>
                        @endif
                        @if(($learningStreak ?? 0) >= 7)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-white bg-opacity-20">
                            🌟 1 Week Warrior
                        </span>
                        @endif
                    </div>
                </div>
                
                <div class="text-center">
                    <div style="width: 150px; height: 150px; position: relative;">
                        <canvas id="dailyGoalChart"></canvas>
                    </div>
                    <p class="text-sm mt-2">Daily Goal Progress</p>
                </div>
            </div>

            {{-- Week Calendar --}}
            <div class="mt-6 grid grid-cols-7 gap-2">
                @php
                    $weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                    $today = now()->dayOfWeek;
                @endphp
                @foreach($weekDays as $index => $day)
                <div class="text-center">
                    <p class="text-xs text-orange-200 mb-1">{{ $day }}</p>
                    <div class="w-10 h-10 mx-auto rounded-lg {{ $index < $today ? 'bg-white bg-opacity-30' : 'bg-white bg-opacity-10' }} flex items-center justify-center">
                        @if($index < $today)
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Time Today</p>
                        <p class="text-3xl font-bold text-purple-600 mt-1">{{ $timeSpentToday ?? 0 }} min</p>
                    </div>
                    <svg class="w-12 h-12 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Completed Today</p>
                        <p class="text-3xl font-bold text-green-600 mt-1">{{ $resourcesCompletedToday ?? 0 }}</p>
                    </div>
                    <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Active Paths</p>
                        <p class="text-3xl font-bold text-blue-600 mt-1">{{ $activePathsCount ?? 0 }}</p>
                    </div>
                    <svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Daily Goal</p>
                        <p class="text-3xl font-bold text-indigo-600 mt-1">{{ $dailyGoalMinutes ?? 30 }} min</p>
                    </div>
                    <button onclick="editDailyGoal()" class="text-indigo-600 hover:text-indigo-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Today's Recommendations --}}
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">✨ Recommended for You</h2>
            
            @forelse($recommendations as $recommendation)
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-4 hover:shadow-xl transition-all">
                <div class="bg-gradient-to-r from-{{ ['blue', 'purple', 'indigo', 'pink'][$loop->index % 4] }}-500 to-{{ ['indigo', 'pink', 'purple', 'blue'][$loop->index % 4] }}-600 px-6 py-4 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold">{{ $recommendation['skill'] ?? 'Skill Development' }}</h3>
                            <p class="text-sm text-white text-opacity-90">From your {{ $recommendation['path_name'] ?? 'learning path' }}</p>
                        </div>
                        @if(isset($recommendation['duration']) && $recommendation['duration'] <= ($dailyGoalMinutes ?? 30))
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-white bg-opacity-20">
                            ⚡ Fits your schedule
                        </span>
                        @endif
                    </div>
                </div>

                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h4 class="text-lg font-bold text-gray-900 mb-2">{{ $recommendation['title'] ?? 'Learning Resource' }}</h4>
                            <div class="flex items-center space-x-4 text-sm text-gray-600 mb-4">
                                <span class="inline-flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    {{ ucfirst($recommendation['type'] ?? 'article') }}
                                </span>
                                <span class="inline-flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ $recommendation['duration'] ?? 15 }} min
                                </span>
                                @if(isset($recommendation['difficulty']))
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    @if($recommendation['difficulty'] === 'beginner') bg-green-100 text-green-800
                                    @elseif($recommendation['difficulty'] === 'intermediate') bg-blue-100 text-blue-800
                                    @else bg-purple-100 text-purple-800
                                    @endif">
                                    {{ ucfirst($recommendation['difficulty']) }}
                                </span>
                                @endif
                            </div>

                            @if(isset($recommendation['description']))
                            <p class="text-gray-700 mb-4">{{ $recommendation['description'] }}</p>
                            @endif
                        </div>

                        <div class="ml-6">
                            @if(isset($recommendation['url']))
                            <a href="{{ $recommendation['url'] }}" target="_blank" onclick="trackLearning('{{ $recommendation['resource_id'] ?? 0 }}')" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg font-bold hover:from-green-600 hover:to-emerald-700 transition-all shadow-lg hover:shadow-xl">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Start Now
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="bg-white rounded-xl shadow-lg p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <p class="text-lg font-medium text-gray-900 mb-2">No Recommendations Today</p>
                <p class="text-gray-500 mb-4">Start a learning path to get personalized recommendations</p>
                <a href="{{ route('skills.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700">
                    View Skill Gaps
                </a>
            </div>
            @endforelse
        </div>

        {{-- Recent Activity Feed --}}
        @if(isset($recentActivity) && count($recentActivity) > 0)
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">📈 Recent Activity</h2>
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="divide-y divide-gray-200">
                    @foreach($recentActivity as $activity)
                    <div class="p-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    @if($activity['type'] === 'completed')
                                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    @elseif($activity['type'] === 'started')
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    @else
                                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>
                                        </svg>
                                    </div>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $activity['title'] ?? 'Activity' }}</p>
                                    <p class="text-xs text-gray-500">{{ $activity['time'] ?? 'Recently' }}</p>
                                </div>
                            </div>
                            @if(isset($activity['progress']))
                            <div class="flex items-center">
                                <span class="text-sm font-medium text-gray-900 mr-2">{{ $activity['progress'] }}%</span>
                                <div class="w-24 bg-gray-200 rounded-full h-2">
                                    <div class="bg-purple-600 rounded-full h-2" style="width: {{ $activity['progress'] }}%"></div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
const timeSpentToday = {{ $timeSpentToday ?? 0 }};
const dailyGoalMinutes = {{ $dailyGoalMinutes ?? 30 }};

// Daily Goal Chart
const goalCtx = document.getElementById('dailyGoalChart').getContext('2d');
const progress = Math.min(100, (timeSpentToday / dailyGoalMinutes) * 100);

new Chart(goalCtx, {
    type: 'doughnut',
    data: {
        datasets: [{
            data: [progress, 100 - progress],
            backgroundColor: ['rgba(255, 255, 255, 0.9)', 'rgba(255, 255, 255, 0.2)'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        cutout: '75%',
        plugins: {
            legend: { display: false },
            tooltip: { enabled: false }
        }
    }
});

// Add text in center
const centerText = {
    id: 'centerText',
    afterDraw: function(chart) {
        const ctx = chart.ctx;
        ctx.restore();
        const fontSize = (chart.height / 114).toFixed(2);
        ctx.font = fontSize + "em sans-serif";
        ctx.textBaseline = "middle";
        ctx.fillStyle = "white";
        
        const text = Math.round(progress) + "%";
        const textX = Math.round((chart.width - ctx.measureText(text).width) / 2);
        const textY = chart.height / 2;
        
        ctx.fillText(text, textX, textY);
        ctx.save();
    }
};

Chart.register(centerText);

function trackLearning(resourceId) {
    // Track when user starts a resource
    fetch('/api/skills/learning-progress', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Authorization': 'Bearer ' + localStorage.getItem('api_token')
        },
        body: JSON.stringify({
            resource_id: resourceId,
            status: 'in_progress',
            time_spent_minutes: 0
        })
    }).catch(err => console.error('Tracking error:', err));
}

function editDailyGoal() {
    const newGoal = prompt('Enter your daily learning goal (minutes):', dailyGoalMinutes);
    if (newGoal && !isNaN(newGoal) && newGoal > 0) {
        fetch('/api/skills/schedule-preferences', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Authorization': 'Bearer ' + localStorage.getItem('api_token')
            },
            body: JSON.stringify({
                daily_minutes: parseInt(newGoal)
            })
        })
        .then(res => res.json())
        .then(() => location.reload())
        .catch(err => alert('Error updating goal: ' + err.message));
    }
}
</script>
@endsection
