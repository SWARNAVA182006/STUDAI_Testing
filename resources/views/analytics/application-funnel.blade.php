@extends('layouts.app')

@section('title', 'Application Funnel Analytics')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-orange-50 to-amber-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('analytics.dashboard') }}" class="text-orange-600 hover:text-orange-800 text-sm mb-2 inline-block">← Back to Analytics</a>
            <h1 class="text-3xl font-bold text-gray-900">🔄 Application Funnel Analytics</h1>
            <p class="text-gray-600">Track your hiring pipeline from view to hire</p>
        </div>

        <!-- Date Range Filter -->
        <div class="bg-white rounded-xl shadow-lg p-4 mb-6 border border-orange-100">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" id="start-date" class="w-full border-gray-200 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" id="end-date" class="w-full border-gray-200 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Job</label>
                    <select id="job-filter" class="w-full border-gray-200 rounded-lg text-sm">
                        <option value="">All Jobs</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button id="refresh-funnel" class="w-full px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                        Update Funnel
                    </button>
                </div>
            </div>
        </div>

        <!-- Funnel Visualization -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-orange-100">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Hiring Funnel</h2>
            <div id="funnel-container" class="space-y-4">
                <!-- Dynamic funnel stages -->
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl p-4 shadow-lg border border-orange-100 text-center">
                <div class="text-3xl font-bold text-orange-600" id="total-views">--</div>
                <div class="text-sm text-gray-600">Total Views</div>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-lg border border-orange-100 text-center">
                <div class="text-3xl font-bold text-blue-600" id="total-applications">--</div>
                <div class="text-sm text-gray-600">Applications</div>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-lg border border-orange-100 text-center">
                <div class="text-3xl font-bold text-purple-600" id="total-interviews">--</div>
                <div class="text-sm text-gray-600">Interviews</div>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-lg border border-orange-100 text-center">
                <div class="text-3xl font-bold text-green-600" id="total-hires">--</div>
                <div class="text-sm text-gray-600">Hires</div>
            </div>
        </div>

        <!-- Conversion Rates -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-orange-100">
            <h2 class="text-xl font-bold text-gray-900 mb-6">📊 Stage Conversion Rates</h2>
            <div class="space-y-6" id="conversion-rates">
                <!-- Dynamic conversion rate bars -->
            </div>
        </div>

        <!-- Dropoff Analysis -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-orange-100">
                <h2 class="text-xl font-bold text-gray-900 mb-4">⚠️ Biggest Dropoff</h2>
                <div id="dropoff-analysis" class="p-4 bg-red-50 rounded-xl">
                    <div class="text-lg font-bold text-red-700" id="dropoff-stage">--</div>
                    <div class="text-sm text-red-600" id="dropoff-rate">--</div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-orange-100">
                <h2 class="text-xl font-bold text-gray-900 mb-4">💡 Recommendations</h2>
                <ul id="recommendations" class="space-y-2 text-sm text-gray-700">
                    <li class="flex items-start gap-2">
                        <span>•</span>
                        <span>Loading recommendations...</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Overall Conversion -->
        <div class="bg-gradient-to-r from-orange-500 to-amber-500 rounded-2xl shadow-lg p-8 text-white text-center">
            <div class="text-lg font-medium opacity-90 mb-2">Overall Conversion Rate</div>
            <div class="text-5xl font-bold mb-2" id="overall-conversion">--</div>
            <div class="text-sm opacity-75">View to Hire</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set default date range (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date(today - 30 * 24 * 60 * 60 * 1000);
    document.getElementById('end-date').value = today.toISOString().split('T')[0];
    document.getElementById('start-date').value = thirtyDaysAgo.toISOString().split('T')[0];

    loadFunnelData();

    document.getElementById('refresh-funnel').addEventListener('click', loadFunnelData);

    function loadFunnelData() {
        const params = new URLSearchParams({
            start_date: document.getElementById('start-date').value,
            end_date: document.getElementById('end-date').value,
            job_id: document.getElementById('job-filter').value
        });

        fetch(`{{ route('analytics.api.application-funnel') }}?${params}`)
            .then(res => res.json())
            .then(data => {
                renderFunnel(data.funnel || []);
                updateMetrics(data.metrics || {});
                updateConversionRates(data.funnel || []);
                updateDropoffAnalysis(data.dropoff_analysis || {});
                updateRecommendations(data.dropoff_analysis?.recommendations || []);
            });
    }

    function renderFunnel(stages) {
        const container = document.getElementById('funnel-container');
        if (!stages.length) {
            container.innerHTML = '<p class="text-center text-gray-500 py-8">No funnel data available</p>';
            return;
        }

        const maxCount = Math.max(...stages.map(s => s.count)) || 1;
        const colors = ['#f97316', '#fb923c', '#fdba74', '#fed7aa', '#ffedd5', '#fff7ed'];

        container.innerHTML = stages.map((stage, i) => {
            const width = 95 - (i * 10);
            return `
                <div class="relative">
                    <div class="flex items-center gap-4">
                        <div class="w-24 text-right text-sm font-medium text-gray-600">${stage.stage}</div>
                        <div class="flex-1 relative">
                            <div class="h-12 rounded-lg transition-all duration-500" 
                                 style="width: ${width}%; background: ${colors[i] || colors[5]};">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="font-bold text-gray-800">${stage.count.toLocaleString()}</span>
                                </div>
                            </div>
                        </div>
                        <div class="w-16 text-right text-sm text-gray-500">${stage.rate.toFixed(1)}%</div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function updateMetrics(metrics) {
        document.getElementById('total-views').textContent = (metrics.views || 0).toLocaleString();
        document.getElementById('total-applications').textContent = (metrics.applications || metrics.total_applications || 0).toLocaleString();
        document.getElementById('total-interviews').textContent = (metrics.interviews || 0).toLocaleString();
        document.getElementById('total-hires').textContent = (metrics.hires || 0).toLocaleString();
        document.getElementById('overall-conversion').textContent = (metrics.overall_conversion || 0).toFixed(2) + '%';
    }

    function updateConversionRates(stages) {
        const container = document.getElementById('conversion-rates');
        if (stages.length < 2) {
            container.innerHTML = '<p class="text-gray-500">Insufficient data for conversion analysis</p>';
            return;
        }

        const conversions = [];
        for (let i = 1; i < stages.length; i++) {
            const prevCount = stages[i - 1].count || 1;
            const currCount = stages[i].count;
            const rate = (currCount / prevCount) * 100;
            conversions.push({
                from: stages[i - 1].stage,
                to: stages[i].stage,
                rate: rate
            });
        }

        container.innerHTML = conversions.map(conv => {
            const color = conv.rate >= 50 ? 'bg-green-500' : conv.rate >= 25 ? 'bg-yellow-500' : 'bg-red-500';
            return `
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">${conv.from} → ${conv.to}</span>
                        <span class="font-medium">${conv.rate.toFixed(1)}%</span>
                    </div>
                    <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full ${color} rounded-full transition-all" style="width: ${conv.rate}%"></div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function updateDropoffAnalysis(analysis) {
        if (analysis.biggest_dropoff) {
            document.getElementById('dropoff-stage').textContent = analysis.biggest_dropoff.stage;
            document.getElementById('dropoff-rate').textContent = `${analysis.biggest_dropoff.dropoff_rate.toFixed(1)}% dropoff at this stage`;
        } else {
            document.getElementById('dropoff-stage').textContent = 'No significant dropoff detected';
            document.getElementById('dropoff-rate').textContent = 'Your funnel is performing well';
        }
    }

    function updateRecommendations(recommendations) {
        const container = document.getElementById('recommendations');
        if (!recommendations.length) {
            container.innerHTML = '<li class="flex items-start gap-2"><span>✅</span><span>Your hiring funnel looks healthy!</span></li>';
            return;
        }

        container.innerHTML = recommendations.map(rec => `
            <li class="flex items-start gap-2">
                <span>💡</span>
                <span>${rec}</span>
            </li>
        `).join('');
    }
});
</script>
@endpush
