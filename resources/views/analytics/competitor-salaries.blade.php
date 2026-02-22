@extends('layouts.app')

@section('title', 'Competitor Salary Comparison')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-orange-50 to-amber-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('analytics.dashboard') }}" class="text-orange-600 hover:text-orange-800 text-sm mb-2 inline-block">← Back to Analytics</a>
            <h1 class="text-3xl font-bold text-gray-900">🏢 Competitor Salary Comparison</h1>
            <p class="text-gray-600">Benchmark your compensation against market competitors</p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-lg p-4 mb-6 border border-orange-100">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select id="role-filter" class="w-full border-gray-200 rounded-lg text-sm">
                        <option value="">Select Role</option>
                        <option value="software-engineer">Software Engineer</option>
                        <option value="product-manager">Product Manager</option>
                        <option value="data-scientist">Data Scientist</option>
                        <option value="ux-designer">UX Designer</option>
                        <option value="devops-engineer">DevOps Engineer</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Experience</label>
                    <select id="experience-filter" class="w-full border-gray-200 rounded-lg text-sm">
                        <option value="">All Levels</option>
                        <option value="junior">Junior (0-2 years)</option>
                        <option value="mid">Mid (3-5 years)</option>
                        <option value="senior">Senior (6-10 years)</option>
                        <option value="lead">Lead (10+ years)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                    <select id="location-filter" class="w-full border-gray-200 rounded-lg text-sm">
                        <option value="">All Locations</option>
                        <option value="san-francisco">San Francisco</option>
                        <option value="new-york">New York</option>
                        <option value="seattle">Seattle</option>
                        <option value="austin">Austin</option>
                        <option value="remote">Remote</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button id="compare-btn" class="w-full px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                        Compare Salaries
                    </button>
                </div>
            </div>
        </div>

        <!-- Your Position Card -->
        <div class="bg-gradient-to-r from-orange-500 to-amber-500 rounded-2xl shadow-lg p-6 mb-8 text-white">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="text-center md:border-r md:border-white/30">
                    <div class="text-sm opacity-75 mb-1">Your Offer</div>
                    <div class="text-3xl font-bold" id="your-salary">$--</div>
                </div>
                <div class="text-center md:border-r md:border-white/30">
                    <div class="text-sm opacity-75 mb-1">Market Average</div>
                    <div class="text-3xl font-bold" id="market-avg">$--</div>
                </div>
                <div class="text-center md:border-r md:border-white/30">
                    <div class="text-sm opacity-75 mb-1">Percentile</div>
                    <div class="text-3xl font-bold" id="your-percentile">--%</div>
                </div>
                <div class="text-center">
                    <div class="text-sm opacity-75 mb-1">vs Market</div>
                    <div class="text-3xl font-bold" id="vs-market">--</div>
                </div>
            </div>
        </div>

        <!-- Competitor Comparison Chart -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-orange-100">
            <h2 class="text-xl font-bold text-gray-900 mb-6">📊 Competitor Salary Ranges</h2>
            <div class="h-96">
                <canvas id="competitor-chart"></canvas>
            </div>
        </div>

        <!-- Detailed Competitor Table -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-orange-100">
            <h2 class="text-xl font-bold text-gray-900 mb-6">🏢 Detailed Comparison</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-orange-50 text-left">
                            <th class="px-4 py-3 font-medium text-gray-700">Company</th>
                            <th class="px-4 py-3 font-medium text-gray-700 text-center">Base Salary</th>
                            <th class="px-4 py-3 font-medium text-gray-700 text-center">Bonus</th>
                            <th class="px-4 py-3 font-medium text-gray-700 text-center">Equity</th>
                            <th class="px-4 py-3 font-medium text-gray-700 text-center">Total Comp</th>
                            <th class="px-4 py-3 font-medium text-gray-700 text-center">vs Avg</th>
                            <th class="px-4 py-3 font-medium text-gray-700 text-center">Benefits</th>
                        </tr>
                    </thead>
                    <tbody id="competitor-table-body">
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Select filters and click Compare to view data</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Compensation Breakdown -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-orange-100">
                <h2 class="text-xl font-bold text-gray-900 mb-4">💰 Compensation Mix</h2>
                <div class="h-64">
                    <canvas id="comp-mix-chart"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-orange-100">
                <h2 class="text-xl font-bold text-gray-900 mb-4">📈 Market Trends</h2>
                <div class="h-64">
                    <canvas id="trend-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Insights -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-orange-100">
            <h2 class="text-xl font-bold text-gray-900 mb-4">💡 Market Insights</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 bg-blue-50 rounded-xl">
                    <div class="text-2xl mb-2">📊</div>
                    <div class="font-medium text-blue-800">Salary Growth</div>
                    <div class="text-sm text-blue-600" id="insight-growth">+8.5% YoY average increase</div>
                </div>
                <div class="p-4 bg-green-50 rounded-xl">
                    <div class="text-2xl mb-2">🎯</div>
                    <div class="font-medium text-green-800">Hot Skills Premium</div>
                    <div class="text-sm text-green-600" id="insight-skills">AI/ML skills command +25% premium</div>
                </div>
                <div class="p-4 bg-purple-50 rounded-xl">
                    <div class="text-2xl mb-2">🏠</div>
                    <div class="font-medium text-purple-800">Remote Adjustment</div>
                    <div class="text-sm text-purple-600" id="insight-remote">Remote roles average -10% vs on-site</div>
                </div>
            </div>
        </div>

        <!-- Negotiation Tips -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl shadow-lg p-8 text-white">
            <h2 class="text-xl font-bold mb-6">🎯 Negotiation Leverage Points</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="negotiation-tips">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">1️⃣</span>
                    <div>
                        <div class="font-medium">Market Rate Justification</div>
                        <div class="text-sm text-gray-300">Your skills are in the 75th percentile for this market</div>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="text-2xl">2️⃣</span>
                    <div>
                        <div class="font-medium">Competitor Reference</div>
                        <div class="text-sm text-gray-300">3 competitors pay 15% above market average</div>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="text-2xl">3️⃣</span>
                    <div>
                        <div class="font-medium">Skill Premium</div>
                        <div class="text-sm text-gray-300">Your specialized skills warrant additional compensation</div>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="text-2xl">4️⃣</span>
                    <div>
                        <div class="font-medium">Total Comp Focus</div>
                        <div class="text-sm text-gray-300">Consider negotiating equity or signing bonus if base is fixed</div>
                    </div>
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
    let competitorChart = null;
    let compMixChart = null;
    let trendChart = null;

    document.getElementById('compare-btn').addEventListener('click', loadComparisonData);

    // Load demo data on page load
    loadDemoData();

    function loadComparisonData() {
        const role = document.getElementById('role-filter').value;
        const experience = document.getElementById('experience-filter').value;
        const location = document.getElementById('location-filter').value;

        if (!role) {
            alert('Please select a role to compare');
            return;
        }

        fetch(`{{ route('analytics.competitor-salaries') }}?format=json&role=${role}&experience=${experience}&location=${location}`)
            .then(res => res.json())
            .then(data => {
                updatePositionCard(data.your_position || {});
                renderCompetitorChart(data.competitors || []);
                renderCompetitorTable(data.competitors || []);
                renderCompMixChart(data.comp_mix || {});
                renderTrendChart(data.trends || []);
                updateInsights(data.insights || {});
            })
            .catch(() => loadDemoData());
    }

    function loadDemoData() {
        const competitors = [
            { company: 'Google', base: 180000, bonus: 36000, equity: 50000, total: 266000, vs_avg: '+18%', benefits: '⭐⭐⭐⭐⭐' },
            { company: 'Meta', base: 175000, bonus: 35000, equity: 60000, total: 270000, vs_avg: '+20%', benefits: '⭐⭐⭐⭐⭐' },
            { company: 'Amazon', base: 165000, bonus: 20000, equity: 40000, total: 225000, vs_avg: '+0%', benefits: '⭐⭐⭐⭐' },
            { company: 'Microsoft', base: 170000, bonus: 25000, equity: 35000, total: 230000, vs_avg: '+2%', benefits: '⭐⭐⭐⭐⭐' },
            { company: 'Apple', base: 178000, bonus: 30000, equity: 45000, total: 253000, vs_avg: '+12%', benefits: '⭐⭐⭐⭐⭐' },
            { company: 'Netflix', base: 200000, bonus: 0, equity: 20000, total: 220000, vs_avg: '-2%', benefits: '⭐⭐⭐⭐' },
            { company: 'Stripe', base: 185000, bonus: 25000, equity: 55000, total: 265000, vs_avg: '+18%', benefits: '⭐⭐⭐⭐' }
        ];

        updatePositionCard({
            your_salary: 160000,
            market_avg: 225000,
            percentile: 42,
            vs_market: '-29%'
        });
        renderCompetitorChart(competitors);
        renderCompetitorTable(competitors);
        renderCompMixChart({ base: 72, bonus: 12, equity: 16 });
        renderTrendChart([
            { year: '2020', salary: 155000 },
            { year: '2021', salary: 168000 },
            { year: '2022', salary: 185000 },
            { year: '2023', salary: 210000 },
            { year: '2024', salary: 225000 }
        ]);
    }

    function updatePositionCard(position) {
        document.getElementById('your-salary').textContent = position.your_salary ? '$' + position.your_salary.toLocaleString() : '$--';
        document.getElementById('market-avg').textContent = position.market_avg ? '$' + position.market_avg.toLocaleString() : '$--';
        document.getElementById('your-percentile').textContent = position.percentile ? position.percentile + '%' : '--%';
        document.getElementById('vs-market').textContent = position.vs_market || '--';
    }

    function renderCompetitorChart(competitors) {
        if (competitorChart) competitorChart.destroy();
        const ctx = document.getElementById('competitor-chart').getContext('2d');
        
        competitorChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: competitors.map(c => c.company),
                datasets: [
                    {
                        label: 'Base Salary',
                        data: competitors.map(c => c.base),
                        backgroundColor: '#f97316',
                        borderRadius: 4
                    },
                    {
                        label: 'Bonus',
                        data: competitors.map(c => c.bonus),
                        backgroundColor: '#fb923c',
                        borderRadius: 4
                    },
                    {
                        label: 'Equity',
                        data: competitors.map(c => c.equity),
                        backgroundColor: '#fcd34d',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    x: { stacked: true },
                    y: {
                        stacked: true,
                        ticks: {
                            callback: val => '$' + (val / 1000) + 'K'
                        }
                    }
                }
            }
        });
    }

    function renderCompetitorTable(competitors) {
        const tbody = document.getElementById('competitor-table-body');
        
        tbody.innerHTML = competitors.map(c => {
            const vsColor = c.vs_avg.startsWith('+') ? 'text-green-600' : c.vs_avg.startsWith('-') ? 'text-red-600' : 'text-gray-600';
            return `
                <tr class="border-b border-gray-100 hover:bg-orange-50">
                    <td class="px-4 py-3 font-medium text-gray-900">${c.company}</td>
                    <td class="px-4 py-3 text-center text-gray-600">$${c.base.toLocaleString()}</td>
                    <td class="px-4 py-3 text-center text-gray-600">$${c.bonus.toLocaleString()}</td>
                    <td class="px-4 py-3 text-center text-gray-600">$${c.equity.toLocaleString()}</td>
                    <td class="px-4 py-3 text-center font-bold text-orange-600">$${c.total.toLocaleString()}</td>
                    <td class="px-4 py-3 text-center font-medium ${vsColor}">${c.vs_avg}</td>
                    <td class="px-4 py-3 text-center">${c.benefits}</td>
                </tr>
            `;
        }).join('');
    }

    function renderCompMixChart(mix) {
        if (compMixChart) compMixChart.destroy();
        const ctx = document.getElementById('comp-mix-chart').getContext('2d');
        
        compMixChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Base Salary', 'Bonus', 'Equity'],
                datasets: [{
                    data: [mix.base || 70, mix.bonus || 15, mix.equity || 15],
                    backgroundColor: ['#f97316', '#fb923c', '#fcd34d']
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

    function renderTrendChart(trends) {
        if (trendChart) trendChart.destroy();
        const ctx = document.getElementById('trend-chart').getContext('2d');
        
        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: trends.map(t => t.year),
                datasets: [{
                    label: 'Average Salary',
                    data: trends.map(t => t.salary),
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.1)',
                    fill: true,
                    tension: 0.4
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
                        ticks: {
                            callback: val => '$' + (val / 1000) + 'K'
                        }
                    }
                }
            }
        });
    }

    function updateInsights(insights) {
        if (insights.growth) {
            document.getElementById('insight-growth').textContent = insights.growth;
        }
        if (insights.skills) {
            document.getElementById('insight-skills').textContent = insights.skills;
        }
        if (insights.remote) {
            document.getElementById('insight-remote').textContent = insights.remote;
        }
    }
});
</script>
@endpush
