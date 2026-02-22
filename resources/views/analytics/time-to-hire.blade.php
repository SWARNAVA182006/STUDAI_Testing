@extends('layouts.app')

@section('title', 'Time-to-Hire Analytics')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-orange-50 to-amber-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('analytics.dashboard') }}" class="text-orange-600 hover:text-orange-800 text-sm mb-2 inline-block">← Back to Analytics</a>
            <h1 class="text-3xl font-bold text-gray-900">⏱️ Time-to-Hire Analytics</h1>
            <p class="text-gray-600">Track and optimize your hiring speed across stages</p>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6 border border-orange-100 text-center">
                <div class="text-4xl font-bold text-orange-600" id="avg-total">--</div>
                <div class="text-sm text-gray-600">Avg Total Days</div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 border border-orange-100 text-center">
                <div class="text-4xl font-bold text-blue-600" id="avg-to-interview">--</div>
                <div class="text-sm text-gray-600">Days to Interview</div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 border border-orange-100 text-center">
                <div class="text-4xl font-bold text-purple-600" id="avg-to-offer">--</div>
                <div class="text-sm text-gray-600">Interview to Offer</div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 border border-orange-100 text-center">
                <div class="text-4xl font-bold text-green-600" id="avg-to-hire">--</div>
                <div class="text-sm text-gray-600">Offer to Hire</div>
            </div>
        </div>

        <!-- Time Breakdown Chart -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-orange-100">
            <h2 class="text-xl font-bold text-gray-900 mb-6">📊 Time Breakdown by Stage</h2>
            <div class="h-80">
                <canvas id="time-breakdown-chart"></canvas>
            </div>
        </div>

        <!-- Trend Over Time -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-orange-100">
            <h2 class="text-xl font-bold text-gray-900 mb-6">📈 Time-to-Hire Trend</h2>
            <div class="h-80">
                <canvas id="trend-chart"></canvas>
            </div>
        </div>

        <!-- By Department / Job Type -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-orange-100">
                <h2 class="text-xl font-bold text-gray-900 mb-4">📂 By Department</h2>
                <div class="h-64">
                    <canvas id="dept-chart"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-orange-100">
                <h2 class="text-xl font-bold text-gray-900 mb-4">💼 By Job Type</h2>
                <div class="h-64">
                    <canvas id="job-type-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Stage Analysis -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-orange-100">
            <h2 class="text-xl font-bold text-gray-900 mb-6">🔍 Stage-by-Stage Analysis</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="stage-table">
                    <thead>
                        <tr class="bg-orange-50 text-left">
                            <th class="px-4 py-3 font-medium text-gray-700">Stage</th>
                            <th class="px-4 py-3 font-medium text-gray-700 text-center">Min Days</th>
                            <th class="px-4 py-3 font-medium text-gray-700 text-center">Avg Days</th>
                            <th class="px-4 py-3 font-medium text-gray-700 text-center">Max Days</th>
                            <th class="px-4 py-3 font-medium text-gray-700 text-center">% of Total</th>
                            <th class="px-4 py-3 font-medium text-gray-700">Trend</th>
                        </tr>
                    </thead>
                    <tbody id="stage-table-body">
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bottleneck Alert -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-orange-100">
            <h2 class="text-xl font-bold text-gray-900 mb-4">⚠️ Bottleneck Detection</h2>
            <div id="bottleneck-alert" class="p-4 rounded-xl bg-gray-100">
                <div class="flex items-center gap-3">
                    <div class="text-2xl" id="bottleneck-icon">⏳</div>
                    <div>
                        <div class="font-bold text-gray-800" id="bottleneck-title">Analyzing...</div>
                        <div class="text-sm text-gray-600" id="bottleneck-description">Identifying slowest stages</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Benchmark Comparison -->
        <div class="bg-gradient-to-r from-orange-500 to-amber-500 rounded-2xl shadow-lg p-8 text-white">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="text-lg opacity-75 mb-1">Your Average</div>
                    <div class="text-4xl font-bold" id="your-avg">--</div>
                    <div class="text-sm opacity-75">days</div>
                </div>
                <div class="text-center">
                    <div class="text-lg opacity-75 mb-1">Industry Average</div>
                    <div class="text-4xl font-bold" id="industry-avg">36</div>
                    <div class="text-sm opacity-75">days</div>
                </div>
                <div class="text-center">
                    <div class="text-lg opacity-75 mb-1">Difference</div>
                    <div class="text-4xl font-bold" id="avg-diff">--</div>
                    <div class="text-sm opacity-75" id="diff-label">vs industry</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let breakdownChart = null;
    let trendChart = null;
    let deptChart = null;
    let jobTypeChart = null;

    loadTimeToHireData();

    function loadTimeToHireData() {
        fetch('{{ route('analytics.time-to-hire') }}?format=json')
            .then(res => res.json())
            .then(data => {
                updateSummaryCards(data.summary || {});
                renderBreakdownChart(data.breakdown || []);
                renderTrendChart(data.trend || []);
                renderDeptChart(data.by_department || []);
                renderJobTypeChart(data.by_job_type || []);
                updateStageTable(data.stages || []);
                updateBottleneck(data.bottleneck || null);
                updateBenchmark(data.summary?.avg_total || null);
            })
            .catch(() => {
                // Use demo data if API fails
                useDemoData();
            });
    }

    function useDemoData() {
        updateSummaryCards({ avg_total: 32, avg_to_interview: 8, avg_to_offer: 18, avg_to_hire: 6 });
        renderBreakdownChart([
            { stage: 'Application Review', days: 3 },
            { stage: 'Phone Screen', days: 5 },
            { stage: 'Technical Interview', days: 10 },
            { stage: 'On-site Interview', days: 7 },
            { stage: 'Offer', days: 4 },
            { stage: 'Negotiation', days: 3 }
        ]);
        renderTrendChart([
            { month: 'Jan', days: 38 },
            { month: 'Feb', days: 35 },
            { month: 'Mar', days: 33 },
            { month: 'Apr', days: 31 },
            { month: 'May', days: 32 },
            { month: 'Jun', days: 30 }
        ]);
        renderDeptChart([
            { name: 'Engineering', days: 35 },
            { name: 'Sales', days: 25 },
            { name: 'Marketing', days: 28 },
            { name: 'Product', days: 32 }
        ]);
        renderJobTypeChart([
            { name: 'Full-time', days: 30 },
            { name: 'Contract', days: 18 },
            { name: 'Part-time', days: 14 }
        ]);
        updateStageTable([
            { stage: 'Application Review', min: 1, avg: 3, max: 7, percent: 9, trend: 'down' },
            { stage: 'Phone Screen', min: 2, avg: 5, max: 10, percent: 16, trend: 'stable' },
            { stage: 'Technical Interview', min: 5, avg: 10, max: 21, percent: 31, trend: 'up' },
            { stage: 'On-site Interview', min: 3, avg: 7, max: 14, percent: 22, trend: 'down' },
            { stage: 'Offer & Close', min: 3, avg: 7, max: 14, percent: 22, trend: 'stable' }
        ]);
        updateBottleneck({ stage: 'Technical Interview', days: 10 });
        updateBenchmark(32);
    }

    function updateSummaryCards(summary) {
        document.getElementById('avg-total').textContent = summary.avg_total || '--';
        document.getElementById('avg-to-interview').textContent = summary.avg_to_interview || '--';
        document.getElementById('avg-to-offer').textContent = summary.avg_to_offer || '--';
        document.getElementById('avg-to-hire').textContent = summary.avg_to_hire || '--';
    }

    function renderBreakdownChart(data) {
        if (breakdownChart) breakdownChart.destroy();
        const ctx = document.getElementById('time-breakdown-chart').getContext('2d');
        
        breakdownChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => d.stage),
                datasets: [{
                    label: 'Days',
                    data: data.map(d => d.days),
                    backgroundColor: [
                        '#f97316', '#fb923c', '#fdba74', '#fcd34d', '#fbbf24', '#f59e0b'
                    ],
                    borderRadius: 8
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
                        beginAtZero: true,
                        title: { display: true, text: 'Days' }
                    }
                }
            }
        });
    }

    function renderTrendChart(data) {
        if (trendChart) trendChart.destroy();
        const ctx = document.getElementById('trend-chart').getContext('2d');
        
        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(d => d.month),
                datasets: [{
                    label: 'Time to Hire',
                    data: data.map(d => d.days),
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6
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
                        title: { display: true, text: 'Days' }
                    }
                }
            }
        });
    }

    function renderDeptChart(data) {
        if (deptChart) deptChart.destroy();
        const ctx = document.getElementById('dept-chart').getContext('2d');
        
        deptChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(d => d.name),
                datasets: [{
                    data: data.map(d => d.days),
                    backgroundColor: ['#f97316', '#3b82f6', '#8b5cf6', '#10b981']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    function renderJobTypeChart(data) {
        if (jobTypeChart) jobTypeChart.destroy();
        const ctx = document.getElementById('job-type-chart').getContext('2d');
        
        jobTypeChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => d.name),
                datasets: [{
                    label: 'Avg Days',
                    data: data.map(d => d.days),
                    backgroundColor: ['#f97316', '#fb923c', '#fdba74'],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    function updateStageTable(stages) {
        const tbody = document.getElementById('stage-table-body');
        if (!stages.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No stage data available</td></tr>';
            return;
        }

        tbody.innerHTML = stages.map(s => {
            const trendIcon = s.trend === 'up' ? '📈' : s.trend === 'down' ? '📉' : '➡️';
            const trendColor = s.trend === 'up' ? 'text-red-600' : s.trend === 'down' ? 'text-green-600' : 'text-gray-600';
            return `
                <tr class="border-b border-gray-100 hover:bg-orange-50">
                    <td class="px-4 py-3 font-medium text-gray-900">${s.stage}</td>
                    <td class="px-4 py-3 text-center text-gray-600">${s.min}</td>
                    <td class="px-4 py-3 text-center font-bold text-orange-600">${s.avg}</td>
                    <td class="px-4 py-3 text-center text-gray-600">${s.max}</td>
                    <td class="px-4 py-3 text-center text-gray-600">${s.percent}%</td>
                    <td class="px-4 py-3 ${trendColor}">${trendIcon}</td>
                </tr>
            `;
        }).join('');
    }

    function updateBottleneck(bottleneck) {
        const container = document.getElementById('bottleneck-alert');
        if (!bottleneck) {
            container.className = 'p-4 rounded-xl bg-green-100';
            document.getElementById('bottleneck-icon').textContent = '✅';
            document.getElementById('bottleneck-title').textContent = 'No significant bottlenecks detected';
            document.getElementById('bottleneck-description').textContent = 'Your hiring process is running smoothly';
            return;
        }

        container.className = 'p-4 rounded-xl bg-red-100';
        document.getElementById('bottleneck-icon').textContent = '⚠️';
        document.getElementById('bottleneck-title').textContent = `${bottleneck.stage} is taking ${bottleneck.days} days`;
        document.getElementById('bottleneck-description').textContent = 'Consider streamlining this stage to reduce overall time-to-hire';
    }

    function updateBenchmark(yourAvg) {
        const industryAvg = 36;
        document.getElementById('your-avg').textContent = yourAvg || '--';
        
        if (yourAvg) {
            const diff = yourAvg - industryAvg;
            const sign = diff > 0 ? '+' : '';
            document.getElementById('avg-diff').textContent = `${sign}${diff}`;
            document.getElementById('diff-label').textContent = diff > 0 ? 'slower than industry' : 'faster than industry';
        }
    }
});
</script>
@endpush
