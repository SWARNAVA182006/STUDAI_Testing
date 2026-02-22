@extends('layouts.app')

@section('title', 'Skill Gap Dashboard')

@section('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endsection

@section('content')
<div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Skill Gap Analyzer</h1>
            <p class="text-lg text-gray-600">Track your skills, close gaps, and stay ahead of market trends</p>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Critical Gaps</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $gaps->where('gap_severity', 'critical')->count() }}</p>
                    </div>
                    <div class="bg-red-100 rounded-full p-3">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-orange-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">High Priority</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $gaps->filter(fn($g) => $g->priorityScore >= 80)->count() }}</p>
                    </div>
                    <div class="bg-orange-100 rounded-full p-3">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Emerging Skills</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $gaps->where('is_emerging_skill', true)->count() }}</p>
                    </div>
                    <div class="bg-purple-100 rounded-full p-3">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Active Paths</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $activePaths->count() }}</p>
                    </div>
                    <div class="bg-green-100 rounded-full p-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {{-- Gap Severity Pie Chart --}}
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Gaps by Severity</h3>
                <canvas id="severityChart" width="400" height="300"></canvas>
            </div>

            {{-- Priority vs Impact Scatter --}}
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Priority vs Market Demand</h3>
                <canvas id="priorityScatterChart" width="400" height="300"></canvas>
            </div>
        </div>

        {{-- Top Priority Gaps Table --}}
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-indigo-500 to-purple-600">
                <h3 class="text-xl font-bold text-white">Top Priority Skill Gaps</h3>
                <p class="text-indigo-100 text-sm mt-1">Ranked by impact on your career goals</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Skill</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Impact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salary Impact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trend</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($gaps->take(10) as $gap)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $gap->skill_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $gap->category }}</div>
                                    </div>
                                    @if($gap->is_emerging_skill)
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                        🚀 Emerging
                                    </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($gap->gap_severity === 'critical') bg-red-100 text-red-800
                                    @elseif($gap->gap_severity === 'high') bg-orange-100 text-orange-800
                                    @elseif($gap->gap_severity === 'medium') bg-yellow-100 text-yellow-800
                                    @else bg-green-100 text-green-800
                                    @endif">
                                    {{ $gap->severityBadge }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $gap->priorityScore }}%"></div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-900">{{ round($gap->priorityScore) }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $gap->impact_score }}/100</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">{{ $gap->salaryImpactFormatted }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $gap->trendIndicator }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($gap->learningPath)
                                <a href="{{ route('skills.learning-path.show', $gap->learningPath->id) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                    View Path
                                </a>
                                @else
                                <button onclick="generatePath({{ $gap->id }})" class="text-green-600 hover:text-green-900 font-medium">
                                    Generate Path
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-lg font-medium text-gray-900">Great job! No skill gaps detected.</p>
                                    <p class="text-sm text-gray-500 mt-1">Your skills align well with your career goals.</p>
                                    <a href="{{ route('skills.validation') }}" class="mt-4 inline-block text-indigo-600 hover:text-indigo-700 font-medium">
                                        Validate Your Skills →
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($gaps->count() > 10)
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                <a href="{{ route('skills.dashboard') }}#skill-gaps" class="text-indigo-600 hover:text-indigo-700 font-medium text-sm">
                    View All {{ $gaps->count() }} Skill Gaps →
                </a>
            </div>
            @endif
        </div>

        {{-- Quick Actions --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="{{ route('skills.learning-paths') }}" class="block bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl p-6 text-white hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold">Learning Paths</h3>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </div>
                <p class="text-indigo-100 text-sm">{{ $activePaths->count() }} active paths personalized for you</p>
            </a>

            <a href="{{ route('skills.validation') }}" class="block bg-gradient-to-br from-green-500 to-teal-600 rounded-xl p-6 text-white hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold">Skill Validation</h3>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-green-100 text-sm">{{ $validations->count() }} skills verified from work history</p>
            </a>

            <a href="{{ route('skills.assessments') }}" class="block bg-gradient-to-br from-orange-500 to-red-600 rounded-xl p-6 text-white hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold">Skill Assessments</h3>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <p class="text-orange-100 text-sm">{{ $recentAssessments->count() }} assessments taken</p>
            </a>
        </div>
    </div>
</div>

<script>
// Severity Pie Chart
const severityData = {
    labels: ['Critical', 'High', 'Medium', 'Low'],
    datasets: [{
        data: [
            {{ $gaps->where('gap_severity', 'critical')->count() }},
            {{ $gaps->where('gap_severity', 'high')->count() }},
            {{ $gaps->where('gap_severity', 'medium')->count() }},
            {{ $gaps->where('gap_severity', 'low')->count() }}
        ],
        backgroundColor: ['#EF4444', '#F97316', '#FBBF24', '#10B981'],
        borderWidth: 2,
        borderColor: '#fff'
    }]
};

new Chart(document.getElementById('severityChart'), {
    type: 'doughnut',
    data: severityData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Priority vs Demand Scatter Chart
const scatterData = {
    datasets: [{
        label: 'Skill Gaps',
        data: [
            @foreach($gaps->take(20) as $gap)
            {
                x: {{ $gap->market_demand_score }},
                y: {{ $gap->priorityScore }},
                label: '{{ $gap->skill_name }}'
            },
            @endforeach
        ],
        backgroundColor: 'rgba(99, 102, 241, 0.6)',
        borderColor: 'rgba(99, 102, 241, 1)',
        borderWidth: 2
    }]
};

new Chart(document.getElementById('priorityScatterChart'), {
    type: 'scatter',
    data: scatterData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: { title: { display: true, text: 'Market Demand Score' } },
            y: { title: { display: true, text: 'Priority Score' } }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.raw.label + ': (' + context.parsed.x + ', ' + context.parsed.y + ')';
                    }
                }
            }
        }
    }
});

function generatePath(gapId) {
    if (confirm('Generate a personalized learning path for this skill?')) {
        fetch(`/api/skills/learning-path/${gapId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Authorization': 'Bearer ' + '{{ auth()->user()->createToken("temp")->plainTextToken }}'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.href = `/skills/learning-path/${data.data.learning_path.id}`;
            } else {
                alert('Failed to generate path: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => alert('Error: ' + err.message));
    }
}
</script>
@endsection
