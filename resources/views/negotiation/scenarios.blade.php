@extends('layouts.app')

@section('title', 'Negotiation Scenarios - ' . $strategy->role)

@push('styles')
<style>
    .scenario-card {
        transition: all 0.3s ease;
    }
    .scenario-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
    }
    .risk-badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .risk-low { background: rgba(16, 185, 129, 0.2); color: #10b981; }
    .risk-medium { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
    .risk-high { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
    
    .sortable-header {
        cursor: pointer;
        user-select: none;
        transition: background 0.2s;
    }
    .sortable-header:hover {
        background: rgba(255, 255, 255, 0.1);
    }
    .sort-icon {
        display: inline-block;
        margin-left: 0.5rem;
        opacity: 0.5;
        transition: opacity 0.2s;
    }
    .sortable-header:hover .sort-icon {
        opacity: 1;
    }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('negotiation.strategy', $strategy->id) }}" class="inline-flex items-center text-sm text-gray-400 hover:text-white mb-4 transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Strategy
        </a>
        
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">Negotiation Scenarios</h1>
                <p class="text-gray-400">{{ $strategy->role }} at {{ $strategy->company_name }}</p>
            </div>
            
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-400">{{ $scenarios->count() }} scenarios</span>
            </div>
        </div>
    </div>

    <!-- Scenario Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        @foreach($scenarios as $scenario)
        <div class="scenario-card bg-white/5 backdrop-filter backdrop-blur-lg rounded-2xl p-6 border border-white/10">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-white mb-1">{{ $scenario->scenario_name }}</h3>
                    <span class="risk-badge risk-{{ $scenario->risk_level }}">{{ ucfirst($scenario->risk_level) }} Risk</span>
                </div>
                
                @if($scenario->risk_level === 'low')
                    <div class="w-10 h-10 bg-green-500/20 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                @elseif($scenario->risk_level === 'medium')
                    <div class="w-10 h-10 bg-yellow-500/20 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                @else
                    <div class="w-10 h-10 bg-red-500/20 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                @endif
            </div>
            
            <div class="space-y-3 mb-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-400">Counter Amount</span>
                    <span class="text-white font-semibold">${{ number_format($scenario->counter_amount) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-400">Success Probability</span>
                    <span class="text-white font-semibold">{{ $scenario->success_probability }}%</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-400">Expected Outcome</span>
                    <span class="text-white font-semibold">${{ number_format($scenario->expected_outcome) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-400">Potential Gain</span>
                    <span class="text-green-400 font-semibold">+${{ number_format($scenario->expected_outcome - $strategy->offered_salary) }}</span>
                </div>
            </div>
            
            <button onclick="viewScenarioDetail({{ $scenario->id }})" class="w-full py-2 bg-gradient-to-r from-primary-color to-primary-light text-white rounded-lg font-medium hover:shadow-lg transition">
                View Details
            </button>
        </div>
        @endforeach
    </div>

    <!-- Risk/Reward Visualization -->
    <div class="bg-white/5 backdrop-filter backdrop-blur-lg rounded-2xl p-6 border border-white/10 mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-white mb-1">Risk/Reward Analysis</h2>
                <p class="text-sm text-gray-400">Visual comparison of scenario risk levels and potential gains</p>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
                    <span class="text-xs text-gray-400">Low Risk</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full bg-yellow-500 mr-2"></div>
                    <span class="text-xs text-gray-400">Medium Risk</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full bg-red-500 mr-2"></div>
                    <span class="text-xs text-gray-400">High Risk</span>
                </div>
            </div>
        </div>
        
        <div class="relative" style="height: 400px;">
            <canvas id="riskRewardChart"></canvas>
        </div>
        
        <div class="mt-4 text-sm text-gray-400 text-center">
            <p>Bubble size represents success probability. Hover for details.</p>
        </div>
    </div>

    <!-- Comparison Table -->
    <div class="bg-white/5 backdrop-filter backdrop-blur-lg rounded-2xl p-6 border border-white/10 mb-8">
        <h2 class="text-xl font-semibold text-white mb-6">Detailed Comparison</h2>
        
        <div class="overflow-x-auto">
            <table class="w-full" id="scenarioTable">
                <thead>
                    <tr class="border-b border-white/10">
                        <th class="sortable-header px-4 py-3 text-left text-sm font-semibold text-gray-400" onclick="sortTable(0)">
                            Scenario
                            <span class="sort-icon">↕</span>
                        </th>
                        <th class="sortable-header px-4 py-3 text-right text-sm font-semibold text-gray-400" onclick="sortTable(1)">
                            Counter Amount
                            <span class="sort-icon">↕</span>
                        </th>
                        <th class="sortable-header px-4 py-3 text-center text-sm font-semibold text-gray-400" onclick="sortTable(2)">
                            Risk Level
                            <span class="sort-icon">↕</span>
                        </th>
                        <th class="sortable-header px-4 py-3 text-right text-sm font-semibold text-gray-400" onclick="sortTable(3)">
                            Success %
                            <span class="sort-icon">↕</span>
                        </th>
                        <th class="sortable-header px-4 py-3 text-right text-sm font-semibold text-gray-400" onclick="sortTable(4)">
                            Expected
                            <span class="sort-icon">↕</span>
                        </th>
                        <th class="sortable-header px-4 py-3 text-right text-sm font-semibold text-gray-400" onclick="sortTable(5)">
                            Best Case
                            <span class="sort-icon">↕</span>
                        </th>
                        <th class="sortable-header px-4 py-3 text-right text-sm font-semibold text-gray-400" onclick="sortTable(6)">
                            Worst Case
                            <span class="sort-icon">↕</span>
                        </th>
                        <th class="sortable-header px-4 py-3 text-right text-sm font-semibold text-gray-400" onclick="sortTable(7)">
                            ROI
                            <span class="sort-icon">↕</span>
                        </th>
                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-400">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    @foreach($scenarios as $scenario)
                    <tr class="border-b border-white/5 hover:bg-white/5 transition">
                        <td class="px-4 py-4">
                            <div class="flex items-center">
                                <div>
                                    <div class="text-white font-medium">{{ $scenario->scenario_name }}</div>
                                    <div class="text-xs text-gray-500 mt-1">{{ Str::limit($scenario->description, 50) }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-right text-white font-semibold" data-value="{{ $scenario->counter_amount }}">
                            ${{ number_format($scenario->counter_amount) }}
                        </td>
                        <td class="px-4 py-4 text-center" data-value="{{ $scenario->risk_level === 'low' ? 1 : ($scenario->risk_level === 'medium' ? 2 : 3) }}">
                            <span class="risk-badge risk-{{ $scenario->risk_level }}">{{ ucfirst($scenario->risk_level) }}</span>
                        </td>
                        <td class="px-4 py-4 text-right" data-value="{{ $scenario->success_probability }}">
                            <div class="flex items-center justify-end">
                                <div class="w-20 bg-white/10 rounded-full h-2 mr-3">
                                    <div class="bg-gradient-to-r from-green-500 to-green-400 h-2 rounded-full" style="width: {{ $scenario->success_probability }}%"></div>
                                </div>
                                <span class="text-white font-medium">{{ $scenario->success_probability }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-right text-white font-semibold" data-value="{{ $scenario->expected_outcome }}">
                            ${{ number_format($scenario->expected_outcome) }}
                        </td>
                        <td class="px-4 py-4 text-right text-green-400 font-medium" data-value="{{ $scenario->best_case_outcome }}">
                            ${{ number_format($scenario->best_case_outcome) }}
                        </td>
                        <td class="px-4 py-4 text-right text-red-400 font-medium" data-value="{{ $scenario->worst_case_outcome }}">
                            ${{ number_format($scenario->worst_case_outcome) }}
                        </td>
                        <td class="px-4 py-4 text-right" data-value="{{ (($scenario->expected_outcome - $strategy->offered_salary) / $strategy->offered_salary) * 100 }}">
                            <span class="text-green-400 font-semibold">
                                +{{ number_format((($scenario->expected_outcome - $strategy->offered_salary) / $strategy->offered_salary) * 100, 1) }}%
                            </span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <button onclick="viewScenarioDetail({{ $scenario->id }})" class="text-primary-color hover:text-primary-light font-medium text-sm">
                                Details →
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Scenario Detail Modals -->
    @foreach($scenarios as $scenario)
    <div id="scenarioModal{{ $scenario->id }}" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50" onclick="if(event.target === this) closeScenarioDetail({{ $scenario->id }})">
        <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto border border-white/10 shadow-2xl">
            <div class="sticky top-0 bg-gradient-to-r from-primary-color to-primary-light p-6 rounded-t-2xl">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-white mb-2">{{ $scenario->scenario_name }}</h2>
                        <p class="text-white/80">{{ $scenario->description }}</p>
                    </div>
                    <button onclick="closeScenarioDetail({{ $scenario->id }})" class="text-white/80 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- Key Metrics -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white/5 rounded-xl p-4 text-center border border-white/10">
                        <div class="text-2xl font-bold text-white mb-1">${{ number_format($scenario->counter_amount) }}</div>
                        <div class="text-xs text-gray-400">Counter Amount</div>
                    </div>
                    <div class="bg-white/5 rounded-xl p-4 text-center border border-white/10">
                        <div class="text-2xl font-bold text-white mb-1">{{ $scenario->success_probability }}%</div>
                        <div class="text-xs text-gray-400">Success Rate</div>
                    </div>
                    <div class="bg-white/5 rounded-xl p-4 text-center border border-white/10">
                        <div class="text-2xl font-bold text-white mb-1">${{ number_format($scenario->expected_outcome) }}</div>
                        <div class="text-xs text-gray-400">Expected</div>
                    </div>
                    <div class="bg-white/5 rounded-xl p-4 text-center border border-{{ $scenario->risk_level === 'low' ? 'green' : ($scenario->risk_level === 'medium' ? 'yellow' : 'red') }}-500/30">
                        <div class="text-2xl font-bold text-{{ $scenario->risk_level === 'low' ? 'green' : ($scenario->risk_level === 'medium' ? 'yellow' : 'red') }}-400 mb-1">{{ ucfirst($scenario->risk_level) }}</div>
                        <div class="text-xs text-gray-400">Risk Level</div>
                    </div>
                </div>

                <!-- Predicted Response -->
                @if($scenario->predicted_response)
                <div class="bg-blue-500/10 border-l-4 border-blue-500 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-400 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <h3 class="text-sm font-semibold text-blue-300 mb-1">Predicted Employer Response</h3>
                            <p class="text-sm text-gray-300">{{ $scenario->predicted_response }}</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Outcome Range -->
                <div>
                    <h3 class="text-white font-semibold mb-3">Outcome Scenarios</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-green-500/10 rounded-lg border border-green-500/20">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                                <span class="text-sm text-green-300 font-medium">Best Case</span>
                            </div>
                            <span class="text-lg font-bold text-green-400">${{ number_format($scenario->best_case_outcome) }}</span>
                        </div>
                        
                        <div class="flex items-center justify-between p-3 bg-blue-500/10 rounded-lg border border-blue-500/20">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                <span class="text-sm text-blue-300 font-medium">Expected</span>
                            </div>
                            <span class="text-lg font-bold text-blue-400">${{ number_format($scenario->expected_outcome) }}</span>
                        </div>
                        
                        <div class="flex items-center justify-between p-3 bg-red-500/10 rounded-lg border border-red-500/20">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                                </svg>
                                <span class="text-sm text-red-300 font-medium">Worst Case</span>
                            </div>
                            <span class="text-lg font-bold text-red-400">${{ number_format($scenario->worst_case_outcome) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Success & Failure Indicators -->
                <div class="grid md:grid-cols-2 gap-4">
                    @if($scenario->success_indicators)
                    <div>
                        <h3 class="text-white font-semibold mb-3 flex items-center">
                            <svg class="w-5 h-5 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Success Indicators
                        </h3>
                        <ul class="space-y-2">
                            @foreach($scenario->success_indicators as $indicator)
                            <li class="flex items-start text-sm text-gray-300">
                                <span class="text-green-400 mr-2">✓</span>
                                <span>{{ $indicator }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    
                    @if($scenario->failure_indicators)
                    <div>
                        <h3 class="text-white font-semibold mb-3 flex items-center">
                            <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Warning Signs
                        </h3>
                        <ul class="space-y-2">
                            @foreach($scenario->failure_indicators as $indicator)
                            <li class="flex items-start text-sm text-gray-300">
                                <span class="text-red-400 mr-2">⚠</span>
                                <span>{{ $indicator }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>

                <!-- Actions -->
                <div class="flex space-x-4 pt-4 border-t border-white/10">
                    <button onclick="useScenario({{ $scenario->id }})" class="flex-1 py-3 bg-gradient-to-r from-primary-color to-primary-light text-white rounded-lg font-semibold hover:shadow-lg transition">
                        Use This Scenario
                    </button>
                    <button onclick="closeScenarioDetail({{ $scenario->id }})" class="px-6 py-3 bg-white/5 text-white rounded-lg font-semibold hover:bg-white/10 transition">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Risk/Reward Scatter Plot
const scenariosData = @json($scenarios->map(function($s) use ($strategy) {
    return [
        'id' => $s->id,
        'name' => $s->scenario_name,
        'risk' => $s->risk_level === 'low' ? 25 : ($s->risk_level === 'medium' ? 50 : 75),
        'gain' => $s->expected_outcome - $strategy->offered_salary,
        'probability' => $s->success_probability,
        'color' => $s->risk_level === 'low' ? 'rgba(16, 185, 129, 0.8)' : ($s->risk_level === 'medium' ? 'rgba(245, 158, 11, 0.8)' : 'rgba(239, 68, 68, 0.8)')
    ];
})->values());

const ctx = document.getElementById('riskRewardChart').getContext('2d');
new Chart(ctx, {
    type: 'scatter',
    data: {
        datasets: [{
            label: 'Scenarios',
            data: scenariosData.map(s => ({
                x: s.risk,
                y: s.gain,
                r: s.probability / 2, // radius based on probability
                name: s.name,
                probability: s.probability
            })),
            backgroundColor: scenariosData.map(s => s.color),
            borderColor: scenariosData.map(s => s.color.replace('0.8', '1')),
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                cornerRadius: 8,
                callbacks: {
                    title: function(context) {
                        return context[0].raw.name;
                    },
                    label: function(context) {
                        return [
                            `Risk Score: ${context.parsed.x}`,
                            `Expected Gain: $${context.parsed.y.toLocaleString()}`,
                            `Success Rate: ${context.raw.probability}%`
                        ];
                    }
                }
            }
        },
        scales: {
            x: {
                title: {
                    display: true,
                    text: 'Risk Level →',
                    color: '#9ca3af',
                    font: { size: 12 }
                },
                min: 0,
                max: 100,
                ticks: {
                    color: '#6b7280',
                    callback: function(value) {
                        if (value === 25) return 'Low';
                        if (value === 50) return 'Medium';
                        if (value === 75) return 'High';
                        return '';
                    }
                },
                grid: {
                    color: 'rgba(255, 255, 255, 0.05)'
                }
            },
            y: {
                title: {
                    display: true,
                    text: '↑ Expected Gain ($)',
                    color: '#9ca3af',
                    font: { size: 12 }
                },
                ticks: {
                    color: '#6b7280',
                    callback: function(value) {
                        return '$' + (value / 1000).toFixed(0) + 'k';
                    }
                },
                grid: {
                    color: 'rgba(255, 255, 255, 0.05)'
                }
            }
        }
    }
});

// Table Sorting
let sortDirection = {};
function sortTable(columnIndex) {
    const table = document.getElementById('scenarioTable');
    const tbody = document.getElementById('tableBody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Toggle sort direction
    sortDirection[columnIndex] = sortDirection[columnIndex] === 'asc' ? 'desc' : 'asc';
    const direction = sortDirection[columnIndex];
    
    rows.sort((a, b) => {
        let aValue, bValue;
        
        if (columnIndex === 0) {
            // Scenario name (text)
            aValue = a.cells[columnIndex].textContent.trim();
            bValue = b.cells[columnIndex].textContent.trim();
            return direction === 'asc' 
                ? aValue.localeCompare(bValue)
                : bValue.localeCompare(aValue);
        } else {
            // Numeric columns
            const aCell = a.cells[columnIndex];
            const bCell = b.cells[columnIndex];
            aValue = parseFloat(aCell.getAttribute('data-value') || aCell.textContent.replace(/[^0-9.-]/g, ''));
            bValue = parseFloat(bCell.getAttribute('data-value') || bCell.textContent.replace(/[^0-9.-]/g, ''));
            return direction === 'asc' ? aValue - bValue : bValue - aValue;
        }
    });
    
    // Clear and re-append sorted rows
    rows.forEach(row => tbody.appendChild(row));
    
    // Update sort icons
    document.querySelectorAll('.sortable-header .sort-icon').forEach(icon => {
        icon.textContent = '↕';
    });
    document.querySelectorAll('.sortable-header')[columnIndex].querySelector('.sort-icon').textContent = 
        direction === 'asc' ? '↑' : '↓';
}

// Scenario Detail Modal Functions
function viewScenarioDetail(scenarioId) {
    document.getElementById('scenarioModal' + scenarioId).classList.remove('hidden');
    document.getElementById('scenarioModal' + scenarioId).classList.add('flex');
}

function closeScenarioDetail(scenarioId) {
    document.getElementById('scenarioModal' + scenarioId).classList.add('hidden');
    document.getElementById('scenarioModal' + scenarioId).classList.remove('flex');
}

function useScenario(scenarioId) {
    // This would typically update the user's preferred scenario
    // For now, just close the modal and show a message
    closeScenarioDetail(scenarioId);
    alert('Scenario selected! You can now generate scripts based on this scenario.');
    window.location.href = '{{ route("negotiation.scripts", $strategy->id) }}';
}

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('[id^="scenarioModal"]').forEach(modal => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
    }
});
</script>
@endpush
@endsection
