@extends('layouts.app')

@section('title', 'Skills Demand Forecast - Predictive Analytics')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('analytics.dashboard') }}" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">← Back to Analytics</a>
            <h1 class="text-3xl font-bold text-gray-900">📈 Skills Demand Forecast</h1>
            <p class="text-gray-600">Predictive insights on skill trends to guide your career development</p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-lg p-4 mb-6 border border-blue-100">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Industry</label>
                    <select id="industry-filter" class="w-full border-gray-200 rounded-lg text-sm">
                        <option value="">All Industries</option>
                        <option value="technology">Technology</option>
                        <option value="healthcare">Healthcare</option>
                        <option value="finance">Finance</option>
                        <option value="marketing">Marketing</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Skill Category</label>
                    <select id="category-filter" class="w-full border-gray-200 rounded-lg text-sm">
                        <option value="">All Categories</option>
                        <option value="technical">Technical</option>
                        <option value="soft">Soft Skills</option>
                        <option value="domain">Domain Knowledge</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Trend Direction</label>
                    <select id="trend-filter" class="w-full border-gray-200 rounded-lg text-sm">
                        <option value="">All Trends</option>
                        <option value="rising">🔺 Rising</option>
                        <option value="falling">🔻 Falling</option>
                        <option value="stable">➡️ Stable</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button id="refresh-forecast" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Refresh Forecast
                    </button>
                </div>
            </div>
        </div>

        <!-- Trend Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-6 text-white shadow-lg">
                <h3 class="text-lg font-semibold mb-2 opacity-90">🔺 Rising Skills</h3>
                <div class="text-4xl font-bold mb-4" id="rising-count">--</div>
                <div class="space-y-2" id="top-rising">
                    <div class="animate-pulse h-6 bg-white/20 rounded"></div>
                    <div class="animate-pulse h-6 bg-white/20 rounded"></div>
                    <div class="animate-pulse h-6 bg-white/20 rounded"></div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-gray-500 to-gray-600 rounded-2xl p-6 text-white shadow-lg">
                <h3 class="text-lg font-semibold mb-2 opacity-90">➡️ Stable Skills</h3>
                <div class="text-4xl font-bold mb-4" id="stable-count">--</div>
                <div class="space-y-2" id="top-stable">
                    <div class="animate-pulse h-6 bg-white/20 rounded"></div>
                    <div class="animate-pulse h-6 bg-white/20 rounded"></div>
                    <div class="animate-pulse h-6 bg-white/20 rounded"></div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-red-500 to-rose-600 rounded-2xl p-6 text-white shadow-lg">
                <h3 class="text-lg font-semibold mb-2 opacity-90">🔻 Declining Skills</h3>
                <div class="text-4xl font-bold mb-4" id="declining-count">--</div>
                <div class="space-y-2" id="top-declining">
                    <div class="animate-pulse h-6 bg-white/20 rounded"></div>
                    <div class="animate-pulse h-6 bg-white/20 rounded"></div>
                    <div class="animate-pulse h-6 bg-white/20 rounded"></div>
                </div>
            </div>
        </div>

        <!-- Skills Table -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8 border border-blue-100">
            <h2 class="text-xl font-bold text-gray-900 mb-4">📊 Skills Demand Analysis</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Skill</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Current Demand</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">30-Day Growth</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">90-Day Forecast</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Trend</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600">Confidence</th>
                        </tr>
                    </thead>
                    <tbody id="skills-table">
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Forecast Chart -->
        <div class="bg-white rounded-2xl shadow-lg p-6 border border-blue-100">
            <h2 class="text-xl font-bold text-gray-900 mb-4">📈 Top Skills Forecast Comparison</h2>
            <canvas id="forecastChart" height="120"></canvas>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let forecastChart = null;

    loadForecastData();

    document.getElementById('refresh-forecast').addEventListener('click', loadForecastData);
    document.getElementById('industry-filter').addEventListener('change', loadForecastData);
    document.getElementById('category-filter').addEventListener('change', loadForecastData);
    document.getElementById('trend-filter').addEventListener('change', loadForecastData);

    function loadForecastData() {
        const params = new URLSearchParams({
            industry: document.getElementById('industry-filter').value,
            category: document.getElementById('category-filter').value,
            trend: document.getElementById('trend-filter').value
        });

        fetch(`{{ route('analytics.api.skills-forecast') }}?${params}`)
            .then(res => res.json())
            .then(data => {
                updateSummaryCards(data);
                updateSkillsTable(data.skills || []);
                updateForecastChart(data.skills?.slice(0, 8) || []);
            });
    }

    function updateSummaryCards(data) {
        const rising = data.rising_skills || [];
        const stable = data.stable_skills || [];
        const declining = data.declining_skills || [];

        document.getElementById('rising-count').textContent = rising.length;
        document.getElementById('stable-count').textContent = stable.length;
        document.getElementById('declining-count').textContent = declining.length;

        document.getElementById('top-rising').innerHTML = rising.slice(0, 3)
            .map(s => `<div class="flex justify-between"><span>${s.skill_name || s.skill}</span><span class="opacity-75">+${Math.round(s.growth_rate_30d || s.growth_30d || 0)}%</span></div>`)
            .join('') || '<div class="opacity-75">No rising skills</div>';

        document.getElementById('top-stable').innerHTML = stable.slice(0, 3)
            .map(s => `<div class="flex justify-between"><span>${s.skill_name || s.skill}</span><span class="opacity-75">${s.current_demand} jobs</span></div>`)
            .join('') || '<div class="opacity-75">No stable skills</div>';

        document.getElementById('top-declining').innerHTML = declining.slice(0, 3)
            .map(s => `<div class="flex justify-between"><span>${s.skill_name || s.skill}</span><span class="opacity-75">${Math.round(s.growth_rate_30d || s.growth_30d || 0)}%</span></div>`)
            .join('') || '<div class="opacity-75">No declining skills</div>';
    }

    function updateSkillsTable(skills) {
        const tbody = document.getElementById('skills-table');
        
        if (!skills.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No skills data available</td></tr>';
            return;
        }

        tbody.innerHTML = skills.slice(0, 20).map(skill => {
            const trend = skill.trend || skill.trend_direction || 'stable';
            const trendIcon = trend === 'rising' ? '🔺' : (trend === 'falling' ? '🔻' : '➡️');
            const trendClass = trend === 'rising' ? 'text-green-600' : (trend === 'falling' ? 'text-red-600' : 'text-gray-600');
            const growth = skill.growth_30d || skill.growth_rate_30d || 0;
            const growthClass = growth >= 0 ? 'text-green-600' : 'text-red-600';
            const confidence = skill.confidence || skill.confidence_score || 50;

            return `
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-900">${skill.skill || skill.skill_name}</td>
                    <td class="px-4 py-3 text-center">${skill.current_demand?.toLocaleString() || '--'}</td>
                    <td class="px-4 py-3 text-center ${growthClass}">
                        ${growth >= 0 ? '+' : ''}${Math.round(growth)}%
                    </td>
                    <td class="px-4 py-3 text-center">
                        ${Math.round(skill.predicted_90d || skill.predicted_demand_90d || skill.current_demand * 1.1)?.toLocaleString() || '--'}
                    </td>
                    <td class="px-4 py-3 text-center ${trendClass} text-lg">${trendIcon}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <div class="w-16 h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-600 rounded-full" style="width: ${confidence}%"></div>
                            </div>
                            <span class="text-xs text-gray-500">${Math.round(confidence)}%</span>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function updateForecastChart(skills) {
        const ctx = document.getElementById('forecastChart').getContext('2d');
        
        if (forecastChart) forecastChart.destroy();

        const labels = skills.map(s => s.skill || s.skill_name);
        const currentData = skills.map(s => s.current_demand || 0);
        const forecastData = skills.map(s => s.predicted_90d || s.predicted_demand_90d || s.current_demand * 1.1);

        forecastChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Current Demand',
                        data: currentData,
                        backgroundColor: '#3b82f6',
                        borderRadius: 4
                    },
                    {
                        label: '90-Day Forecast',
                        data: forecastData,
                        backgroundColor: '#10b981',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Job Postings' }
                    }
                }
            }
        });
    }
});
</script>
@endpush
