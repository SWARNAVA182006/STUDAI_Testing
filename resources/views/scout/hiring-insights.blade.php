@extends('layouts.app')

@section('title', 'Hiring Insights - S.C.O.U.T.')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-green-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Hiring Insights & Analytics</h1>
                <p class="mt-2 text-gray-600">Data-driven insights from your hiring patterns</p>
            </div>
            <button onclick="analyzePatterns()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                <i data-lucide="bar-chart" class="w-4 h-4 inline mr-2"></i>
                Analyze Patterns
            </button>
        </div>

        <!-- Source Effectiveness -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Hiring Source Effectiveness</h2>
            <div class="h-80"><canvas id="source-chart"></canvas></div>
        </div>

        <!-- Success Patterns -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i data-lucide="trending-up" class="w-5 h-5 mr-2 text-green-600"></i>
                    Top Performer Traits
                </h3>
                <div id="top-traits" class="space-y-3"></div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i data-lucide="alert-circle" class="w-5 h-5 mr-2 text-red-600"></i>
                    Red Flags to Watch
                </h3>
                <div id="red-flags" class="space-y-3"></div>
            </div>
        </div>

        <!-- AI Recommendations -->
        <div class="bg-gradient-to-r from-blue-50 to-green-50 rounded-xl shadow-md p-6 border-2 border-green-200">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                <i data-lucide="lightbulb" class="w-5 h-5 mr-2 text-yellow-500"></i>
                AI Hiring Recommendations
            </h3>
            <div id="recommendations" class="prose max-w-none text-gray-700"></div>
        </div>

    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script>
lucide.createIcons();
const companyId = {{ auth()->user()->company_id ?? 'null' }};
let sourceChart = null;

document.addEventListener('DOMContentLoaded', loadInsights);

async function loadInsights() {
    try {
        const response = await fetch(`/api/scout/hiring-insights?company_id=${companyId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                'Accept': 'application/json',
            }
        });
        const result = await response.json();
        if (result.success) renderInsights(result.data);
    } catch (error) {
        console.error('Failed to load insights:', error);
    }
}

function renderInsights(data) {
    if (data.hiring_effectiveness) {
        renderSourceChart(data.hiring_effectiveness.source_rankings);
    }
    
    if (data.success_patterns) {
        renderTraits(data.success_patterns.top_performer_traits);
        renderRedFlags(data.success_patterns.red_flags);
    }

    if (data.recommendations) {
        document.getElementById('recommendations').innerHTML = 
            data.recommendations.replace(/\n/g, '<br>');
    }
}

function renderSourceChart(sources) {
    const ctx = document.getElementById('source-chart');
    const labels = sources.sources?.map(s => s.source) || [];
    const scores = sources.sources?.map(s => s.score) || [];

    if (sourceChart) sourceChart.destroy();

    sourceChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Effectiveness Score',
                data: scores,
                backgroundColor: 'rgba(34, 197, 94, 0.8)',
                borderColor: 'rgba(34, 197, 94, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, max: 100 } }
        }
    });
}

function renderTraits(traits) {
    const container = document.getElementById('top-traits');
    container.innerHTML = traits?.map(trait => `
        <div class="p-3 bg-green-50 rounded-lg border border-green-200">
            <p class="font-medium text-gray-900">${trait.trait || trait}</p>
            ${trait.prevalence ? `<p class="text-sm text-green-700">${trait.prevalence}</p>` : ''}
        </div>
    `).join('') || '<p class="text-gray-500">No data available</p>';
}

function renderRedFlags(flags) {
    const container = document.getElementById('red-flags');
    container.innerHTML = flags?.map(flag => `
        <div class="p-3 bg-red-50 rounded-lg border border-red-200 flex items-start">
            <i data-lucide="x-circle" class="w-4 h-4 text-red-600 mr-2 mt-0.5"></i>
            <span class="text-gray-900">${flag}</span>
        </div>
    `).join('') || '<p class="text-gray-500">No red flags identified</p>';
    lucide.createIcons();
}

async function analyzePatterns() {
    try {
        const response = await fetch('/api/scout/analyze-hiring-patterns', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ company_id: companyId })
        });
        const result = await response.json();
        if (result.success) {
            alert('Pattern analysis completed!');
            loadInsights();
        }
    } catch (error) {
        console.error('Analysis failed:', error);
    }
}
</script>
@endpush
@endsection
