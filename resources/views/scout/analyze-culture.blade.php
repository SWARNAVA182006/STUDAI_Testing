@extends('layouts.app')

@section('title', 'Culture Analysis - S.C.O.U.T.')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-teal-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Culture Analysis & Assessment</h1>
            <p class="mt-2 text-gray-600">Deep cultural insights powered by Hofstede dimensions</p>
        </div>

        <!-- Hofstede Cultural Dimensions -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Hofstede Cultural Dimensions</h2>
            
            <div class="space-y-6">
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Power Distance</span>
                        <span id="power-distance-value" class="text-sm font-bold text-teal-600">--</span>
                    </div>
                    <div class="h-4 bg-gray-200 rounded-full overflow-hidden">
                        <div id="power-distance-bar" class="h-full bg-teal-500 transition-all duration-500" style="width: 0%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Hierarchical (High) vs. Flat (Low)</p>
                </div>

                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Individualism</span>
                        <span id="individualism-value" class="text-sm font-bold text-blue-600">--</span>
                    </div>
                    <div class="h-4 bg-gray-200 rounded-full overflow-hidden">
                        <div id="individualism-bar" class="h-full bg-blue-500 transition-all duration-500" style="width: 0%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Individual (High) vs. Collective (Low)</p>
                </div>

                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Uncertainty Avoidance</span>
                        <span id="uncertainty-value" class="text-sm font-bold text-purple-600">--</span>
                    </div>
                    <div class="h-4 bg-gray-200 rounded-full overflow-hidden">
                        <div id="uncertainty-bar" class="h-full bg-purple-500 transition-all duration-500" style="width: 0%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Risk Averse (High) vs. Risk Tolerant (Low)</p>
                </div>

                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Long-term Orientation</span>
                        <span id="longterm-value" class="text-sm font-bold text-green-600">--</span>
                    </div>
                    <div class="h-4 bg-gray-200 rounded-full overflow-hidden">
                        <div id="longterm-bar" class="h-full bg-green-500 transition-all duration-500" style="width: 0%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Long-term (High) vs. Short-term (Low)</p>
                </div>

                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Indulgence (Work-Life Balance)</span>
                        <span id="indulgence-value" class="text-sm font-bold text-pink-600">--</span>
                    </div>
                    <div class="h-4 bg-gray-200 rounded-full overflow-hidden">
                        <div id="indulgence-bar" class="h-full bg-pink-500 transition-all duration-500" style="width: 0%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Indulgent (High) vs. Restrained (Low)</p>
                </div>
            </div>
        </div>

        <!-- Culture Type & Work Environment -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Culture Type</h3>
                <div class="text-center p-6 bg-gradient-to-r from-teal-50 to-blue-50 rounded-lg">
                    <p id="culture-type" class="text-2xl font-bold text-teal-900">--</p>
                    <p id="culture-desc" class="mt-2 text-sm text-gray-600"></p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Work Environment</h3>
                <div class="text-center p-6 bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg">
                    <p id="work-env" class="text-2xl font-bold text-purple-900">--</p>
                    <p id="work-env-desc" class="mt-2 text-sm text-gray-600"></p>
                </div>
            </div>
        </div>

        <!-- Innovation & Learning Culture -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6">
                <h4 class="text-sm font-semibold text-gray-700 mb-2">Innovation Index</h4>
                <p id="innovation-score" class="text-4xl font-bold text-yellow-600">--</p>
                <div class="mt-3 h-2 bg-gray-200 rounded-full">
                    <div id="innovation-bar" class="h-full bg-yellow-500 rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <h4 class="text-sm font-semibold text-gray-700 mb-2">Learning Culture</h4>
                <p id="learning-score" class="text-4xl font-bold text-blue-600">--</p>
                <div class="mt-3 h-2 bg-gray-200 rounded-full">
                    <div id="learning-bar" class="h-full bg-blue-500 rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <h4 class="text-sm font-semibold text-gray-700 mb-2">Diversity Score</h4>
                <p id="diversity-score" class="text-4xl font-bold text-green-600">--</p>
                <div class="mt-3 h-2 bg-gray-200 rounded-full">
                    <div id="diversity-bar" class="h-full bg-green-500 rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <!-- Culture Strengths & Challenges -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i data-lucide="star" class="w-5 h-5 mr-2 text-yellow-500"></i>
                    Cultural Strengths
                </h3>
                <ul id="culture-strengths" class="space-y-2"></ul>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i data-lucide="target" class="w-5 h-5 mr-2 text-orange-500"></i>
                    Growth Opportunities
                </h3>
                <ul id="culture-challenges" class="space-y-2"></ul>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
lucide.createIcons();
const companyId = {{ auth()->user()->company_id ?? 'null' }};

document.addEventListener('DOMContentLoaded', loadCultureAnalysis);

async function loadCultureAnalysis() {
    try {
        const response = await fetch(`/api/scout/dna-profile?company_id=${companyId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                'Accept': 'application/json',
            }
        });

        const result = await response.json();
        if (result.success && result.data.dna_profile.culture_analysis) {
            renderCultureAnalysis(result.data.dna_profile.culture_analysis);
        }
    } catch (error) {
        console.error('Failed to load culture analysis:', error);
    }
}

function renderCultureAnalysis(culture) {
    // Hofstede dimensions
    updateDimension('power-distance', culture.power_distance_score);
    updateDimension('individualism', culture.individualism_score);
    updateDimension('uncertainty', culture.uncertainty_avoidance_score);
    updateDimension('longterm', culture.long_term_orientation_score);
    updateDimension('indulgence', culture.indulgence_score);

    // Culture type
    document.getElementById('culture-type').textContent = culture.culture_type || 'Unknown';
    document.getElementById('work-env').textContent = culture.work_environment_type || 'Unknown';

    // Scores
    updateScore('innovation-score', 'innovation-bar', culture.innovation_index);
    updateScore('learning-score', 'learning-bar', culture.learning_culture_score);
    updateScore('diversity-score', 'diversity-bar', culture.diversity_score || 50);

    // Strengths & challenges
    renderList('culture-strengths', culture.culture_strengths || [], 'yellow');
    renderList('culture-challenges', culture.culture_challenges || [], 'orange');
}

function updateDimension(name, value) {
    document.getElementById(`${name}-value`).textContent = value || 0;
    document.getElementById(`${name}-bar`).style.width = (value || 0) + '%';
}

function updateScore(textId, barId, score) {
    document.getElementById(textId).textContent = score || 0;
    document.getElementById(barId).style.width = (score || 0) + '%';
}

function renderList(containerId, items, color) {
    const container = document.getElementById(containerId);
    container.innerHTML = items.map(item => `
        <li class="flex items-start">
            <i data-lucide="check" class="w-4 h-4 text-${color}-600 mr-2 mt-1"></i>
            <span class="text-gray-700">${item}</span>
        </li>
    `).join('') || `<li class="text-gray-500">No data available</li>`;
    lucide.createIcons();
}
</script>
@endpush
@endsection
