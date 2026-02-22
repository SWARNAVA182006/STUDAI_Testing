@extends('layouts.app')

@section('title', 'Strategy Analyzer - ' . $strategy->role)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Back Button --}}
        <div class="mb-6">
            <a href="{{ route('negotiation.dashboard') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </a>
        </div>

        {{-- Header Card --}}
        <div class="bg-gradient-to-r from-primary to-primary/80 rounded-2xl shadow-2xl p-8 mb-8 text-white">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h1 class="text-4xl font-bold mb-2">{{ $strategy->role }}</h1>
                    <p class="text-xl text-white/90 mb-4">{{ $strategy->company_name }} · {{ $strategy->location }}</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4">
                            <p class="text-sm text-white/80 mb-1">Current Offer</p>
                            <p class="text-3xl font-bold">${{ number_format($strategy->offered_salary) }}</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4">
                            <p class="text-sm text-white/80 mb-1">Optimal Ask</p>
                            <p class="text-3xl font-bold">${{ number_format($strategy->optimal_ask) }}</p>
                            <p class="text-sm text-white/80 mt-1">+{{ $strategy->potential_gain_percentage }}% potential gain</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4">
                            <p class="text-sm text-white/80 mb-1">Confidence Level</p>
                            <p class="text-3xl font-bold">{{ $strategy->confidence_score }}%</p>
                            <p class="text-sm text-white/80 mt-1">{{ ucfirst($strategy->confidence_level) }}</p>
                        </div>
                    </div>
                </div>

                <div class="ml-6">
                    <div class="bg-white/20 backdrop-blur-sm rounded-full p-4">
                        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Readiness Score --}}
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Negotiation Readiness</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="flex items-center justify-center">
                    <canvas id="readinessChart" width="300" height="300"></canvas>
                </div>
                <div class="space-y-4">
                    @foreach($readinessFactors as $factor)
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-gray-700">{{ $factor['factor'] }}</span>
                            <span class="text-sm font-medium text-gray-900">{{ $factor['points'] }}/{{ $factor['max_points'] ?? 25 }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full transition-all" style="width: {{ ($factor['points'] / ($factor['max_points'] ?? 25)) * 100 }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            @if($factor['status'] === 'complete') ✓ Complete
                            @elseif($factor['status'] === 'strong') ⚡ Strong position
                            @else ⚠ Needs attention
                            @endif
                        </p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Market Comparison --}}
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Market Position Analysis</h2>
            <div class="mb-6">
                <canvas id="marketChart" height="100"></canvas>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-center">
                <div>
                    <p class="text-xs text-gray-500 mb-1">25th Percentile</p>
                    <p class="text-lg font-bold text-gray-700">${{ number_format($strategy->market_25th_percentile ?? 0) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Median (50th)</p>
                    <p class="text-lg font-bold text-gray-900">${{ number_format($strategy->market_median ?? 0) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">75th Percentile</p>
                    <p class="text-lg font-bold text-secondary">${{ number_format($strategy->market_75th_percentile ?? 0) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">90th Percentile</p>
                    <p class="text-lg font-bold text-accent-yellow">${{ number_format($strategy->market_90th_percentile ?? 0) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Your Offer</p>
                    <p class="text-lg font-bold text-primary">${{ number_format($strategy->offered_salary) }}</p>
                    <p class="text-xs {{ $strategy->offer_strength === 'excellent' ? 'text-green-600' : ($strategy->offer_strength === 'below_market' ? 'text-red-600' : 'text-yellow-600') }}">
                        {{ ucfirst(str_replace('_', ' ', $strategy->offer_strength)) }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Leverage Analysis --}}
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Your Negotiation Leverage</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <canvas id="leverageChart" width="400" height="400"></canvas>
                </div>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Strongest Points</h3>
                        <ul class="space-y-2">
                            @foreach($strategy->strongest_points as $point)
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-secondary mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-700">{{ $point }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    @if(!empty($strategy->value_propositions))
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Value Propositions</h3>
                        <ul class="space-y-2">
                            @foreach(array_slice($strategy->value_propositions, 0, 3) as $value)
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-accent-blue mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <span class="text-gray-700">{{ $value }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    @if(!empty($strategy->risk_factors))
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Potential Risks</h3>
                        <ul class="space-y-2">
                            @foreach(array_slice($strategy->risk_factors, 0, 2) as $risk)
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-yellow-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-700">{{ $risk }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Company Intelligence --}}
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Company Intelligence</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="text-center p-4 bg-gray-50 rounded-xl">
                    <p class="text-sm text-gray-600 mb-2">Negotiation Flexibility</p>
                    <p class="text-2xl font-bold {{ $strategy->company_negotiation_flexibility === 'high' ? 'text-green-600' : ($strategy->company_negotiation_flexibility === 'low' ? 'text-red-600' : 'text-yellow-600') }}">
                        {{ ucfirst($strategy->company_negotiation_flexibility ?? 'Medium') }}
                    </p>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-xl">
                    <p class="text-sm text-gray-600 mb-2">Recommended Tone</p>
                    <p class="text-2xl font-bold text-primary">{{ ucfirst($strategy->recommended_tone ?? 'Professional') }}</p>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-xl">
                    <p class="text-sm text-gray-600 mb-2">Recommended Timing</p>
                    <p class="text-2xl font-bold text-secondary">{{ ucfirst(str_replace('_', ' ', $strategy->recommended_timing ?? 'within 48h')) }}</p>
                </div>
            </div>

            @if($strategy->company_culture_analysis)
            <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mb-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Culture Analysis</h3>
                <p class="text-gray-700 whitespace-pre-line">{{ $strategy->company_culture_analysis }}</p>
            </div>
            @endif

            @if(!empty($strategy->recommended_tactics))
            <div class="bg-purple-50 border-l-4 border-purple-500 p-6 rounded-lg">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Recommended Tactics</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($strategy->recommended_tactics as $tactic)
                    <span class="px-3 py-1 bg-white border border-purple-200 rounded-full text-sm font-medium text-purple-800">
                        {{ ucfirst(str_replace('_', ' ', $tactic)) }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- AI Insights --}}
        @if($strategy->ai_summary)
        <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
            <div class="flex items-start space-x-4">
                <div class="bg-white/20 backdrop-blur-sm rounded-full p-3 flex-shrink-0">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-2xl font-bold mb-4">AI Strategic Insights</h2>
                    <div class="space-y-4">
                        <div>
                            <h3 class="font-semibold text-lg mb-2">Executive Summary</h3>
                            <p class="text-white/90 whitespace-pre-line">{{ $strategy->ai_summary }}</p>
                        </div>
                        @if($strategy->ai_rationale)
                        <div>
                            <h3 class="font-semibold text-lg mb-2">Strategic Rationale</h3>
                            <p class="text-white/90 whitespace-pre-line">{{ $strategy->ai_rationale }}</p>
                        </div>
                        @endif
                        @if($strategy->ai_warnings)
                        <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4">
                            <h3 class="font-semibold text-lg mb-2 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                Important Warnings
                            </h3>
                            <p class="text-white/90 whitespace-pre-line">{{ $strategy->ai_warnings }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Action Buttons --}}
        <div class="flex items-center justify-center space-x-4">
            <a href="{{ route('negotiation.scenarios', $strategy->id) }}" class="bg-primary text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-primary/90 transition-all transform hover:scale-105 shadow-lg">
                View Scenarios →
            </a>
            <a href="{{ route('negotiation.scripts', $strategy->id) }}" class="bg-secondary text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-secondary/90 transition-all transform hover:scale-105 shadow-lg">
                View Scripts →
            </a>
            @if($strategy->sessions()->where('outcome', null)->exists())
            <a href="{{ route('negotiation.coaching', $strategy->sessions()->where('outcome', null)->first()->id) }}" class="bg-accent-blue text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-accent-blue/90 transition-all transform hover:scale-105 shadow-lg">
                Resume Coaching →
            </a>
            @else
            <button onclick="startCoachingSession()" class="bg-accent-blue text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-accent-blue/90 transition-all transform hover:scale-105 shadow-lg">
                Start Coaching →
            </button>
            @endif
        </div>

    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Readiness Donut Chart
const readinessCtx = document.getElementById('readinessChart').getContext('2d');
new Chart(readinessCtx, {
    type: 'doughnut',
    data: {
        labels: ['Ready', 'Remaining'],
        datasets: [{
            data: [{{ $readinessScore }}, {{ 100 - $readinessScore }}],
            backgroundColor: ['#ec4899', '#e5e7eb'],
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
    },
    plugins: [{
        id: 'text',
        beforeDraw: function(chart) {
            const width = chart.width;
            const height = chart.height;
            const ctx = chart.ctx;
            ctx.restore();
            const fontSize = (height / 114).toFixed(2);
            ctx.font = fontSize + "em sans-serif";
            ctx.textBaseline = "middle";
            const text = "{{ $readinessScore }}%";
            const textX = Math.round((width - ctx.measureText(text).width) / 2);
            const textY = height / 2;
            ctx.fillText(text, textX, textY);
            ctx.save();
        }
    }]
});

// Market Comparison Bar Chart
const marketCtx = document.getElementById('marketChart').getContext('2d');
new Chart(marketCtx, {
    type: 'bar',
    data: {
        labels: ['25th', 'Median', '75th', '90th', 'Your Offer'],
        datasets: [{
            label: 'Salary ($)',
            data: [
                {{ $strategy->market_25th_percentile ?? 0 }},
                {{ $strategy->market_median ?? 0 }},
                {{ $strategy->market_75th_percentile ?? 0 }},
                {{ $strategy->market_90th_percentile ?? 0 }},
                {{ $strategy->offered_salary }}
            ],
            backgroundColor: ['#9ca3af', '#374151', '#10b981', '#f59e0b', '#ec4899']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: false,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Leverage Radar Chart
const leverageCtx = document.getElementById('leverageChart').getContext('2d');
const leverageData = @json($leverageAnalysis ?? ['market_position' => 70, 'experience' => 60, 'skills' => 50, 'alternatives' => 40]);
new Chart(leverageCtx, {
    type: 'radar',
    data: {
        labels: ['Market Position', 'Experience', 'Skills', 'Alternatives'],
        datasets: [{
            label: 'Your Leverage',
            data: [
                leverageData.market_position ?? 50,
                leverageData.experience ?? 50,
                leverageData.skills ?? 50,
                leverageData.alternatives ?? 50
            ],
            backgroundColor: 'rgba(236, 72, 153, 0.2)',
            borderColor: '#ec4899',
            borderWidth: 2,
            pointBackgroundColor: '#ec4899',
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            r: {
                beginAtZero: true,
                max: 100,
                ticks: { stepSize: 20 }
            }
        }
    }
});

async function startCoachingSession() {
    try {
        const response = await fetch('/api/negotiation/session', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                strategy_id: {{ $strategy->id }},
                session_type: 'live_coaching',
                communication_mode: 'email'
            })
        });
        
        const result = await response.json();
        if (result.success) {
            window.location.href = '/negotiation/coaching/' + result.session.id;
        } else {
            alert('Failed to start session: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
}
</script>
@endpush
@endsection
