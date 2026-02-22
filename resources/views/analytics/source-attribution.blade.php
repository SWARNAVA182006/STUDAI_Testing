@extends('layouts.app')

@section('title', 'Source Attribution Analytics')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-orange-50 to-amber-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('analytics.dashboard') }}" class="text-orange-600 hover:text-orange-800 text-sm mb-2 inline-block">← Back to Analytics</a>
            <h1 class="text-3xl font-bold text-gray-900">📍 Source Attribution Analytics</h1>
            <p class="text-gray-600">Track where your best candidates come from</p>
        </div>

        <!-- Source Performance Overview -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-4 border border-orange-100 text-center">
                <div class="text-2xl font-bold text-orange-600" id="total-sources">--</div>
                <div class="text-xs text-gray-600">Active Sources</div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-4 border border-orange-100 text-center">
                <div class="text-2xl font-bold text-blue-600" id="total-candidates">--</div>
                <div class="text-xs text-gray-600">Total Candidates</div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-4 border border-orange-100 text-center">
                <div class="text-2xl font-bold text-purple-600" id="total-hires">--</div>
                <div class="text-xs text-gray-600">Total Hires</div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-4 border border-orange-100 text-center">
                <div class="text-2xl font-bold text-green-600" id="avg-conversion">--</div>
                <div class="text-xs text-gray-600">Avg Conversion</div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-4 border border-orange-100 text-center">
                <div class="text-2xl font-bold text-amber-600" id="best-source">--</div>
                <div class="text-xs text-gray-600">Top Source</div>
            </div>
        </div>

        <!-- Source Distribution Chart -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-orange-100">
                <h2 class="text-xl font-bold text-gray-900 mb-4">📊 Candidates by Source</h2>
                <div class="h-80">
                    <canvas id="source-pie-chart"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-orange-100">
                <h2 class="text-xl font-bold text-gray-900 mb-4">💰 Cost per Hire by Source</h2>
                <div class="h-80">
                    <canvas id="cost-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Conversion Rates by Source -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-orange-100">
            <h2 class="text-xl font-bold text-gray-900 mb-6">📈 Conversion Rates by Source</h2>
            <div class="space-y-4" id="conversion-bars">
                <!-- Dynamic conversion bars -->
            </div>
        </div>

        <!-- Source Performance Table -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-orange-100">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-900">📋 Detailed Source Performance</h2>
                <select id="sort-by" class="border-gray-200 rounded-lg text-sm">
                    <option value="candidates">Sort by Candidates</option>
                    <option value="hires">Sort by Hires</option>
                    <option value="conversion">Sort by Conversion</option>
                    <option value="cost">Sort by Cost/Hire</option>
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-orange-50 text-left">
                            <th class="px-4 py-3 font-medium text-gray-700">Source</th>
                            <th class="px-4 py-3 font-medium text-gray-700 text-center">Candidates</th>
                            <th class="px-4 py-3 font-medium text-gray-700 text-center">Interviews</th>
                            <th class="px-4 py-3 font-medium text-gray-700 text-center">Hires</th>
                            <th class="px-4 py-3 font-medium text-gray-700 text-center">Conversion</th>
                            <th class="px-4 py-3 font-medium text-gray-700 text-center">Cost/Hire</th>
                            <th class="px-4 py-3 font-medium text-gray-700 text-center">Quality Score</th>
                            <th class="px-4 py-3 font-medium text-gray-700">Trend</th>
                        </tr>
                    </thead>
                    <tbody id="source-table-body">
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Source Trends Over Time -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-orange-100">
            <h2 class="text-xl font-bold text-gray-900 mb-6">📅 Source Trends Over Time</h2>
            <div class="h-80">
                <canvas id="trends-chart"></canvas>
            </div>
        </div>

        <!-- ROI Analysis -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-lg p-6 text-white">
                <div class="text-lg opacity-90 mb-2">🏆 Best ROI Source</div>
                <div class="text-3xl font-bold" id="best-roi-source">--</div>
                <div class="text-sm opacity-75 mt-1" id="best-roi-value">--</div>
            </div>
            <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-lg p-6 text-white">
                <div class="text-lg opacity-90 mb-2">⭐ Highest Quality</div>
                <div class="text-3xl font-bold" id="best-quality-source">--</div>
                <div class="text-sm opacity-75 mt-1" id="best-quality-value">--</div>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-lg p-6 text-white">
                <div class="text-lg opacity-90 mb-2">📈 Fastest Growing</div>
                <div class="text-3xl font-bold" id="fastest-growing">--</div>
                <div class="text-sm opacity-75 mt-1" id="fastest-growing-value">--</div>
            </div>
        </div>

        <!-- Recommendations -->
        <div class="bg-white rounded-2xl shadow-lg p-8 border border-orange-100">
            <h2 class="text-xl font-bold text-gray-900 mb-4">💡 Sourcing Recommendations</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="recommendations">
                <div class="p-4 bg-green-50 rounded-xl">
                    <div class="font-medium text-green-700">✅ Increase Investment</div>
                    <div class="text-sm text-green-600" id="rec-increase">Analyzing...</div>
                </div>
                <div class="p-4 bg-red-50 rounded-xl">
                    <div class="font-medium text-red-700">⚠️ Review Effectiveness</div>
                    <div class="text-sm text-red-600" id="rec-review">Analyzing...</div>
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
    let pieChart = null;
    let costChart = null;
    let trendsChart = null;
    let sourceData = [];

    loadSourceData();

    document.getElementById('sort-by').addEventListener('change', function() {
        sortAndRenderTable(this.value);
    });

    function loadSourceData() {
        fetch('{{ route('analytics.source-attribution') }}?format=json')
            .then(res => res.json())
            .then(data => {
                sourceData = data.sources || [];
                updateSummaryCards(data.summary || {});
                renderPieChart(sourceData);
                renderCostChart(sourceData);
                renderConversionBars(sourceData);
                renderTable(sourceData);
                renderTrendsChart(data.trends || []);
                updateROICards(data.analysis || {});
                updateRecommendations(data.recommendations || {});
            })
            .catch(() => useDemoData());
    }

    function useDemoData() {
        sourceData = [
            { name: 'LinkedIn', candidates: 450, interviews: 120, hires: 35, conversion: 7.8, cost_per_hire: 2500, quality_score: 8.2, trend: 'up' },
            { name: 'Indeed', candidates: 680, interviews: 95, hires: 28, conversion: 4.1, cost_per_hire: 1800, quality_score: 7.5, trend: 'stable' },
            { name: 'Referrals', candidates: 120, interviews: 65, hires: 25, conversion: 20.8, cost_per_hire: 500, quality_score: 9.1, trend: 'up' },
            { name: 'Company Website', candidates: 340, interviews: 85, hires: 22, conversion: 6.5, cost_per_hire: 300, quality_score: 8.0, trend: 'up' },
            { name: 'Glassdoor', candidates: 180, interviews: 40, hires: 12, conversion: 6.7, cost_per_hire: 2200, quality_score: 7.8, trend: 'down' },
            { name: 'Campus', candidates: 95, interviews: 35, hires: 10, conversion: 10.5, cost_per_hire: 1500, quality_score: 7.2, trend: 'stable' }
        ];

        updateSummaryCards({
            total_sources: 6,
            total_candidates: 1865,
            total_hires: 132,
            avg_conversion: 7.9,
            best_source: 'Referrals'
        });
        renderPieChart(sourceData);
        renderCostChart(sourceData);
        renderConversionBars(sourceData);
        renderTable(sourceData);
        renderTrendsChart([
            { month: 'Jan', LinkedIn: 40, Indeed: 55, Referrals: 12, Website: 28 },
            { month: 'Feb', LinkedIn: 45, Indeed: 60, Referrals: 15, Website: 32 },
            { month: 'Mar', LinkedIn: 52, Indeed: 58, Referrals: 18, Website: 35 },
            { month: 'Apr', LinkedIn: 48, Indeed: 65, Referrals: 22, Website: 38 },
            { month: 'May', LinkedIn: 55, Indeed: 62, Referrals: 20, Website: 42 },
            { month: 'Jun', LinkedIn: 60, Indeed: 68, Referrals: 25, Website: 45 }
        ]);
        updateROICards({
            best_roi: { source: 'Referrals', value: '$500/hire' },
            best_quality: { source: 'Referrals', value: '9.1/10' },
            fastest_growing: { source: 'Company Website', value: '+35% MoM' }
        });
        updateRecommendations({
            increase: 'Employee Referrals - Best conversion rate and lowest cost',
            review: 'Glassdoor - Declining performance, review ad spend'
        });
    }

    function updateSummaryCards(summary) {
        document.getElementById('total-sources').textContent = summary.total_sources || '--';
        document.getElementById('total-candidates').textContent = (summary.total_candidates || 0).toLocaleString();
        document.getElementById('total-hires').textContent = summary.total_hires || '--';
        document.getElementById('avg-conversion').textContent = (summary.avg_conversion || 0).toFixed(1) + '%';
        document.getElementById('best-source').textContent = summary.best_source || '--';
    }

    function renderPieChart(data) {
        if (pieChart) pieChart.destroy();
        const ctx = document.getElementById('source-pie-chart').getContext('2d');
        
        pieChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(d => d.name),
                datasets: [{
                    data: data.map(d => d.candidates),
                    backgroundColor: [
                        '#f97316', '#3b82f6', '#10b981', '#8b5cf6', '#f59e0b', '#ef4444'
                    ]
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

    function renderCostChart(data) {
        if (costChart) costChart.destroy();
        const ctx = document.getElementById('cost-chart').getContext('2d');
        
        costChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => d.name),
                datasets: [{
                    label: 'Cost per Hire ($)',
                    data: data.map(d => d.cost_per_hire),
                    backgroundColor: data.map(d => d.cost_per_hire < 1000 ? '#10b981' : d.cost_per_hire < 2000 ? '#f59e0b' : '#ef4444'),
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: val => '$' + val.toLocaleString()
                        }
                    }
                }
            }
        });
    }

    function renderConversionBars(data) {
        const container = document.getElementById('conversion-bars');
        const sorted = [...data].sort((a, b) => b.conversion - a.conversion);
        
        container.innerHTML = sorted.map(s => {
            const color = s.conversion >= 15 ? 'bg-green-500' : s.conversion >= 8 ? 'bg-orange-500' : 'bg-red-500';
            return `
                <div class="space-y-1">
                    <div class="flex justify-between text-sm">
                        <span class="font-medium text-gray-700">${s.name}</span>
                        <span class="font-bold">${s.conversion.toFixed(1)}%</span>
                    </div>
                    <div class="h-4 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full ${color} rounded-full transition-all" style="width: ${Math.min(s.conversion * 4, 100)}%"></div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderTable(data) {
        const tbody = document.getElementById('source-table-body');
        
        tbody.innerHTML = data.map(s => {
            const trendIcon = s.trend === 'up' ? '📈' : s.trend === 'down' ? '📉' : '➡️';
            const trendColor = s.trend === 'up' ? 'text-green-600' : s.trend === 'down' ? 'text-red-600' : 'text-gray-600';
            const qualityColor = s.quality_score >= 8.5 ? 'text-green-600' : s.quality_score >= 7 ? 'text-orange-600' : 'text-red-600';
            
            return `
                <tr class="border-b border-gray-100 hover:bg-orange-50">
                    <td class="px-4 py-3 font-medium text-gray-900">${s.name}</td>
                    <td class="px-4 py-3 text-center text-gray-600">${s.candidates.toLocaleString()}</td>
                    <td class="px-4 py-3 text-center text-gray-600">${s.interviews}</td>
                    <td class="px-4 py-3 text-center font-bold text-green-600">${s.hires}</td>
                    <td class="px-4 py-3 text-center font-medium">${s.conversion.toFixed(1)}%</td>
                    <td class="px-4 py-3 text-center text-gray-600">$${s.cost_per_hire.toLocaleString()}</td>
                    <td class="px-4 py-3 text-center font-medium ${qualityColor}">${s.quality_score.toFixed(1)}</td>
                    <td class="px-4 py-3 ${trendColor}">${trendIcon}</td>
                </tr>
            `;
        }).join('');
    }

    function sortAndRenderTable(sortKey) {
        const sortMap = {
            candidates: (a, b) => b.candidates - a.candidates,
            hires: (a, b) => b.hires - a.hires,
            conversion: (a, b) => b.conversion - a.conversion,
            cost: (a, b) => a.cost_per_hire - b.cost_per_hire
        };
        
        const sorted = [...sourceData].sort(sortMap[sortKey] || sortMap.candidates);
        renderTable(sorted);
    }

    function renderTrendsChart(data) {
        if (trendsChart) trendsChart.destroy();
        const ctx = document.getElementById('trends-chart').getContext('2d');
        
        const sources = Object.keys(data[0] || {}).filter(k => k !== 'month');
        const colors = ['#f97316', '#3b82f6', '#10b981', '#8b5cf6'];
        
        trendsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(d => d.month),
                datasets: sources.map((source, i) => ({
                    label: source,
                    data: data.map(d => d[source]),
                    borderColor: colors[i % colors.length],
                    backgroundColor: 'transparent',
                    tension: 0.4
                }))
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Candidates' }
                    }
                }
            }
        });
    }

    function updateROICards(analysis) {
        if (analysis.best_roi) {
            document.getElementById('best-roi-source').textContent = analysis.best_roi.source;
            document.getElementById('best-roi-value').textContent = analysis.best_roi.value;
        }
        if (analysis.best_quality) {
            document.getElementById('best-quality-source').textContent = analysis.best_quality.source;
            document.getElementById('best-quality-value').textContent = analysis.best_quality.value;
        }
        if (analysis.fastest_growing) {
            document.getElementById('fastest-growing').textContent = analysis.fastest_growing.source;
            document.getElementById('fastest-growing-value').textContent = analysis.fastest_growing.value;
        }
    }

    function updateRecommendations(recommendations) {
        document.getElementById('rec-increase').textContent = recommendations.increase || 'Insufficient data';
        document.getElementById('rec-review').textContent = recommendations.review || 'All sources performing well';
    }
});
</script>
@endpush
