@extends('layouts.app')

@section('title', 'Agent Performance Metrics')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-pink-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('agent.dashboard') }}" class="text-gray-600 hover:text-gray-900">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <h1 class="text-3xl font-bold text-gray-900">Performance Metrics</h1>
            </div>
            <p class="text-gray-600">Analyze your agent's performance and success patterns</p>
        </div>

        {{-- Overview Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm text-gray-600 font-medium">Total Applications</p>
                    <i data-lucide="file-text" class="w-5 h-5 text-primary"></i>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ $metrics['total_applications'] }}</p>
                <p class="text-sm text-gray-500 mt-1">All time</p>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm text-gray-600 font-medium">Success Rate</p>
                    <i data-lucide="trending-up" class="w-5 h-5 text-secondary-color"></i>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ $metrics['success_rate'] }}%</p>
                <p class="text-sm text-gray-500 mt-1">{{ $metrics['successful_outcomes'] }} interviews+</p>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm text-gray-600 font-medium">Avg Match Score</p>
                    <i data-lucide="target" class="w-5 h-5 text-accent-blue"></i>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ $metrics['avg_match_score'] }}%</p>
                <p class="text-sm text-gray-500 mt-1">
                    Successful: {{ $metrics['avg_successful_score'] }}%
                </p>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm text-gray-600 font-medium">Response Rate</p>
                    <i data-lucide="message-circle" class="w-5 h-5 text-accent-yellow"></i>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ $metrics['response_rate'] }}%</p>
                <p class="text-sm text-gray-500 mt-1">
                    Avg: {{ $metrics['avg_days_to_response'] }} days
                </p>
            </div>
        </div>

        {{-- Charts Row 1 --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- Applications Over Time --}}
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Applications Over Time</h3>
                <canvas id="applicationsChart" class="w-full" style="height: 300px;"></canvas>
            </div>

            {{-- Outcome Distribution --}}
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Outcome Distribution</h3>
                <canvas id="outcomeChart" class="w-full" style="height: 300px;"></canvas>
            </div>
        </div>

        {{-- Company Performance --}}
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Performance by Company</h3>
            @if($performanceByCompany && count($performanceByCompany) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Company</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Applications</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Successful</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Success Rate</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Performance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($performanceByCompany as $company => $data)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <p class="font-semibold text-gray-900">{{ $company }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-gray-900">{{ $data['total_applications'] }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-gray-900">{{ $data['successful_applications'] }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="font-semibold {{ $data['success_rate'] >= 50 ? 'text-green-600' : ($data['success_rate'] >= 25 ? 'text-yellow-600' : 'text-red-600') }}">
                                            {{ $data['success_rate'] }}%
                                        </p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="h-2 rounded-full {{ $data['success_rate'] >= 50 ? 'bg-green-500' : ($data['success_rate'] >= 25 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                                                 style="width: {{ $data['success_rate'] }}%"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <i data-lucide="bar-chart" class="w-12 h-12 text-gray-400 mx-auto mb-3"></i>
                    <p class="text-gray-600">No company data available yet</p>
                </div>
            @endif
        </div>

        {{-- Job Type Performance --}}
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Performance by Job Type</h3>
            @if($performanceByJobType && count($performanceByJobType) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach($performanceByJobType as $jobType => $data)
                        <div class="p-4 border border-gray-200 rounded-lg hover:border-primary transition-colors">
                            <p class="text-sm font-semibold text-gray-900 mb-2">{{ ucwords(str_replace('_', ' ', $jobType)) }}</p>
                            <p class="text-2xl font-bold text-gray-900 mb-1">{{ $data['success_rate'] }}%</p>
                            <p class="text-xs text-gray-500">{{ $data['successful_applications'] }}/{{ $data['total_applications'] }} successful</p>
                            <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2">
                                <div class="h-1.5 rounded-full bg-primary" style="width: {{ $data['success_rate'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <i data-lucide="briefcase" class="w-12 h-12 text-gray-400 mx-auto mb-3"></i>
                    <p class="text-gray-600">No job type data available yet</p>
                </div>
            @endif
        </div>

        {{-- Learning Insights --}}
        @if($config->enable_learning && isset($learningInsights))
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                        <i data-lucide="brain" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">AI Learning Insights</h3>
                        <p class="text-sm text-gray-600">What the agent has learned from your applications</p>
                    </div>
                </div>

                @if(!empty($learningInsights['insights']))
                    <div class="space-y-4 mb-6">
                        @foreach($learningInsights['insights'] as $insight)
                            <div class="p-4 bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg border-l-4 border-purple-500">
                                <div class="flex items-start gap-3">
                                    <i data-lucide="lightbulb" class="w-5 h-5 text-purple-600 mt-0.5"></i>
                                    <div>
                                        <p class="font-semibold text-gray-900 mb-1">{{ $insight['title'] }}</p>
                                        <p class="text-sm text-gray-700">{{ $insight['description'] }}</p>
                                        @if(isset($insight['metric']))
                                            <p class="text-xs text-gray-600 mt-1">{{ $insight['metric'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(!empty($learningInsights['recommendations']))
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-3">Recommendations</h4>
                        <div class="space-y-3">
                            @foreach($learningInsights['recommendations'] as $recommendation)
                                <div class="p-4 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                                    <div class="flex items-start gap-3">
                                        <i data-lucide="arrow-right-circle" class="w-5 h-5 text-blue-600 mt-0.5"></i>
                                        <p class="text-sm text-gray-900">{{ $recommendation }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(isset($config->last_optimization_at))
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <p class="text-sm text-gray-600">
                            <i data-lucide="refresh-cw" class="w-4 h-4 inline"></i>
                            Last optimized: {{ $config->last_optimization_at->diffForHumans() }}
                        </p>
                    </div>
                @endif
            </div>
        @endif

        {{-- Learning Metrics Chart --}}
        @if($config->enable_learning && isset($learningMetrics) && count($learningMetrics) > 0)
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Learning Progress</h3>
                <canvas id="learningChart" class="w-full" style="height: 300px;"></canvas>
                <p class="text-xs text-gray-500 mt-4 text-center">
                    Track how the agent's performance improves over time through machine learning
                </p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    lucide.createIcons();

    // Applications Over Time Chart
    const applicationsCtx = document.getElementById('applicationsChart');
    if (applicationsCtx) {
        new Chart(applicationsCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($applicationsOverTime['labels']) !!},
                datasets: [{
                    label: 'Applications',
                    data: {!! json_encode($applicationsOverTime['data']) !!},
                    borderColor: 'rgb(236, 72, 153)',
                    backgroundColor: 'rgba(236, 72, 153, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // Outcome Distribution Chart
    const outcomeCtx = document.getElementById('outcomeChart');
    if (outcomeCtx) {
        new Chart(outcomeCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($outcomeDistribution['labels']) !!},
                datasets: [{
                    data: {!! json_encode($outcomeDistribution['data']) !!},
                    backgroundColor: [
                        'rgb(34, 197, 94)',   // green - success
                        'rgb(239, 68, 68)',   // red - rejected
                        'rgb(251, 191, 36)',  // yellow - no response
                        'rgb(156, 163, 175)', // gray - withdrawn
                        'rgb(59, 130, 246)',  // blue - pending
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Learning Progress Chart
    const learningCtx = document.getElementById('learningChart');
    if (learningCtx) {
        new Chart(learningCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($learningMetrics['labels'] ?? []) !!},
                datasets: [
                    {
                        label: 'Success Rate',
                        data: {!! json_encode($learningMetrics['success_rate'] ?? []) !!},
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Avg Match Score',
                        data: {!! json_encode($learningMetrics['avg_score'] ?? []) !!},
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        yAxisID: 'y'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        min: 0,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    }
</script>
@endpush
@endsection
