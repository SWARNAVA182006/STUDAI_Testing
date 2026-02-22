@extends('layouts.app')

@section('title', 'Company DNA Dashboard - S.C.O.U.T.')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Corporate DNA Profile</h1>
                    <p class="mt-2 text-gray-600">AI-powered organizational analysis for smarter hiring</p>
                </div>
                <div class="flex space-x-3">
                    <button onclick="refreshAnalysis()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        <i data-lucide="refresh-cw" class="w-4 h-4 inline mr-2"></i>
                        Refresh Analysis
                    </button>
                    <button onclick="analyzeDNA()" class="px-4 py-2 bg-gradient-to-r from-pink-500 to-pink-600 text-white rounded-lg hover:from-pink-600 hover:to-pink-700 transition shadow-md">
                        <i data-lucide="cpu" class="w-4 h-4 inline mr-2"></i>
                        Run DNA Analysis
                    </button>
                </div>
            </div>
        </div>

        <!-- Health Score Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-pink-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">DNA Health Score</p>
                        <p class="text-3xl font-bold text-gray-900" id="health-score">--</p>
                    </div>
                    <div class="w-12 h-12 bg-pink-100 rounded-full flex items-center justify-center">
                        <i data-lucide="heart-pulse" class="w-6 h-6 text-pink-600"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div id="health-progress" class="h-full bg-gradient-to-r from-pink-500 to-pink-600 transition-all duration-500" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Completeness</p>
                        <p class="text-3xl font-bold text-gray-900" id="completeness-score">--</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i data-lucide="pie-chart" class="w-6 h-6 text-blue-600"></i>
                    </div>
                </div>
                <p class="mt-3 text-xs text-gray-500" id="completeness-status">Loading...</p>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Confidence</p>
                        <p class="text-3xl font-bold text-gray-900" id="confidence-level">--</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i data-lucide="target" class="w-6 h-6 text-green-600"></i>
                    </div>
                </div>
                <p class="mt-3 text-xs text-gray-500" id="confidence-desc">Loading...</p>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Data Quality</p>
                        <p class="text-3xl font-bold" id="data-quality">--</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-between">
                        <i data-lucide="award" class="w-6 h-6 text-yellow-600"></i>
                    </div>
                </div>
                <p class="mt-3 text-xs text-gray-500" id="quality-desc">Loading...</p>
            </div>
        </div>

        <!-- Cultural DNA Radar Chart -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i data-lucide="radar" class="w-5 h-5 mr-2 text-pink-600"></i>
                    Cultural DNA Profile
                </h2>
                <div class="relative h-80">
                    <canvas id="cultural-dna-chart"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i data-lucide="layers" class="w-5 h-5 mr-2 text-blue-600"></i>
                    Cultural Archetypes
                </h2>
                <div id="archetypes-container" class="space-y-3">
                    <!-- Archetypes will be inserted here -->
                </div>
            </div>
        </div>

        <!-- Success Traits & Core Values -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i data-lucide="trophy" class="w-5 h-5 mr-2 text-green-600"></i>
                    Top Success Traits
                </h2>
                <div id="success-traits-container" class="space-y-3">
                    <!-- Success traits will be inserted here -->
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i data-lucide="compass" class="w-5 h-5 mr-2 text-purple-600"></i>
                    Core Values
                </h2>
                <div id="core-values-container" class="flex flex-wrap gap-2">
                    <!-- Core values will be inserted here -->
                </div>
            </div>
        </div>

        <!-- Work Style & Communication Patterns -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i data-lucide="briefcase" class="w-5 h-5 mr-2 text-indigo-600"></i>
                    Work Style Preferences
                </h2>
                <div id="work-style-container" class="space-y-2">
                    <!-- Work styles will be inserted here -->
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i data-lucide="message-circle" class="w-5 h-5 mr-2 text-teal-600"></i>
                    Communication Patterns
                </h2>
                <div id="communication-container" class="space-y-2">
                    <!-- Communication patterns will be inserted here -->
                </div>
            </div>
        </div>

        <!-- Analysis Metadata -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                <i data-lucide="info" class="w-5 h-5 mr-2 text-gray-600"></i>
                Analysis Information
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Last Analyzed</p>
                    <p class="text-lg font-semibold text-gray-900" id="last-analyzed">--</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Employees Analyzed</p>
                    <p class="text-lg font-semibold text-gray-900" id="employees-analyzed">--</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Hires Analyzed</p>
                    <p class="text-lg font-semibold text-gray-900" id="hires-analyzed">--</p>
                </div>
            </div>
            <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <p class="text-sm text-gray-700" id="ai-summary">Loading analysis summary...</p>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script>
let culturalChart = null;
const companyId = {{ auth()->user()->company_id ?? 'null' }};

// Initialize Lucide icons
lucide.createIcons();

// Load DNA profile on page load
document.addEventListener('DOMContentLoaded', function() {
    loadDNAProfile();
});

async function loadDNAProfile() {
    if (!companyId) {
        alert('No company associated with your account');
        return;
    }

    try {
        const response = await fetch(`/api/scout/dna-profile?company_id=${companyId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                'Accept': 'application/json',
            }
        });

        const result = await response.json();

        if (result.success && result.data) {
            renderDNAProfile(result.data);
        } else {
            showEmptyState(result.message);
        }
    } catch (error) {
        console.error('Failed to load DNA profile:', error);
        showError('Failed to load DNA profile');
    }
}

function renderDNAProfile(data) {
    const profile = data.dna_profile;
    const metrics = data.health_metrics;
    const insights = data.cultural_insights;
    const metadata = data.analysis_metadata;

    // Health metrics
    document.getElementById('health-score').textContent = metrics.dna_health_score || 0;
    document.getElementById('health-progress').style.width = (metrics.dna_health_score || 0) + '%';
    
    document.getElementById('completeness-score').textContent = profile.dna_completeness_score || 0;
    document.getElementById('completeness-status').textContent = metrics.completion_status || 'Unknown';
    
    document.getElementById('confidence-level').textContent = profile.analysis_confidence || 0;
    document.getElementById('confidence-desc').textContent = metrics.confidence_level || 'Unknown';
    
    document.getElementById('data-quality').textContent = metrics.data_quality || '—';

    // Cultural DNA radar chart
    renderCulturalDNAChart(profile.cultural_dna || []);

    // Archetypes
    renderArchetypes(insights.archetypes || []);

    // Success traits
    renderSuccessTraits(insights.top_success_traits || []);

    // Core values
    renderCoreValues(profile.core_values || []);

    // Work style & communication
    renderWorkStyles(profile.work_style_preferences || []);
    renderCommunication(profile.communication_patterns || []);

    // Metadata
    document.getElementById('last-analyzed').textContent = metadata.last_analyzed ? 
        new Date(metadata.last_analyzed).toLocaleDateString() : 'Never';
    document.getElementById('employees-analyzed').textContent = profile.total_employees_analyzed || 0;
    document.getElementById('hires-analyzed').textContent = profile.total_hires_analyzed || 0;
    document.getElementById('ai-summary').textContent = profile.ai_analysis_summary?.summary || 'No summary available';
}

function renderCulturalDNAChart(culturalDNA) {
    const ctx = document.getElementById('cultural-dna-chart');
    
    if (culturalChart) {
        culturalChart.destroy();
    }

    const labels = culturalDNA.slice(0, 8).map(item => item.trait || '');
    const scores = culturalDNA.slice(0, 8).map(item => item.score || 0);

    culturalChart = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Cultural DNA Score',
                data: scores,
                backgroundColor: 'rgba(236, 72, 153, 0.2)',
                borderColor: 'rgba(236, 72, 153, 1)',
                borderWidth: 2,
                pointBackgroundColor: 'rgba(236, 72, 153, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(236, 72, 153, 1)'
            }]
        },
        options: {
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { stepSize: 20 }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
}

function renderArchetypes(archetypes) {
    const container = document.getElementById('archetypes-container');
    container.innerHTML = archetypes.map(archetype => `
        <div class="flex items-center p-3 bg-blue-50 rounded-lg border border-blue-200">
            <i data-lucide="check-circle" class="w-5 h-5 text-blue-600 mr-3"></i>
            <span class="text-gray-900 font-medium">${archetype}</span>
        </div>
    `).join('') || '<p class="text-gray-500">No archetypes identified</p>';
    lucide.createIcons();
}

function renderSuccessTraits(traits) {
    const container = document.getElementById('success-traits-container');
    container.innerHTML = traits.map((trait, index) => `
        <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg border border-green-200">
            <div class="flex items-center">
                <span class="w-6 h-6 bg-green-600 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3">${index + 1}</span>
                <span class="text-gray-900 font-medium">${trait.trait || trait}</span>
            </div>
            ${trait.score ? `<span class="text-sm text-green-700 font-semibold">${trait.score}</span>` : ''}
        </div>
    `).join('') || '<p class="text-gray-500">No success traits identified</p>';
}

function renderCoreValues(values) {
    const container = document.getElementById('core-values-container');
    container.innerHTML = values.map(value => `
        <span class="px-4 py-2 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">${value}</span>
    `).join('') || '<p class="text-gray-500">No core values defined</p>';
}

function renderWorkStyles(styles) {
    const container = document.getElementById('work-style-container');
    container.innerHTML = styles.map(style => `
        <div class="flex items-center p-2 hover:bg-gray-50 rounded">
            <i data-lucide="arrow-right" class="w-4 h-4 text-indigo-600 mr-2"></i>
            <span class="text-gray-700">${style}</span>
        </div>
    `).join('') || '<p class="text-gray-500">No work style data</p>';
    lucide.createIcons();
}

function renderCommunication(patterns) {
    const container = document.getElementById('communication-container');
    container.innerHTML = patterns.map(pattern => `
        <div class="flex items-center p-2 hover:bg-gray-50 rounded">
            <i data-lucide="arrow-right" class="w-4 h-4 text-teal-600 mr-2"></i>
            <span class="text-gray-700">${pattern}</span>
        </div>
    `).join('') || '<p class="text-gray-500">No communication data</p>';
    lucide.createIcons();
}

async function analyzeDNA() {
    if (!companyId) return;

    const button = event.target.closest('button');
    button.disabled = true;
    button.innerHTML = '<i data-lucide="loader" class="w-4 h-4 inline mr-2 animate-spin"></i>Analyzing...';

    try {
        const response = await fetch('/api/scout/analyze-dna', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ company_id: companyId, force_refresh: true })
        });

        const result = await response.json();

        if (result.success) {
            alert('DNA analysis completed successfully!');
            loadDNAProfile();
        } else {
            alert('Analysis failed: ' + result.message);
        }
    } catch (error) {
        console.error('DNA analysis failed:', error);
        alert('Failed to analyze DNA');
    } finally {
        button.disabled = false;
        button.innerHTML = '<i data-lucide="cpu" class="w-4 h-4 inline mr-2"></i>Run DNA Analysis';
        lucide.createIcons();
    }
}

function refreshAnalysis() {
    loadDNAProfile();
}

function showEmptyState(message) {
    document.querySelector('.max-w-7xl').innerHTML = `
        <div class="text-center py-16">
            <i data-lucide="database" class="w-16 h-16 text-gray-400 mx-auto mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No DNA Profile Found</h3>
            <p class="text-gray-600 mb-6">${message || 'Run DNA analysis to get started'}</p>
            <button onclick="analyzeDNA()" class="px-6 py-3 bg-pink-600 text-white rounded-lg hover:bg-pink-700">
                Run DNA Analysis
            </button>
        </div>
    `;
    lucide.createIcons();
}

function showError(message) {
    alert(message);
}
</script>
@endpush
@endsection
