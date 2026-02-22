@extends('layouts.app')

@section('title', 'Candidate Matching - S.C.O.U.T.')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-purple-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">AI-Powered Candidate Matching</h1>
            <p class="mt-2 text-gray-600">Predict candidate success based on your company's DNA</p>
        </div>

        <!-- Candidate Search -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Select Candidate</h2>
            <div class="flex gap-4">
                <input type="text" id="candidate-search" placeholder="Search candidates by name or email..." 
                    class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <button onclick="searchCandidates()" class="px-6 py-3 bg-pink-600 text-white rounded-lg hover:bg-pink-700">
                    <i data-lucide="search" class="w-4 h-4 inline mr-2"></i>Search
                </button>
            </div>
            <div id="candidates-list" class="mt-4 space-y-2"></div>
        </div>

        <!-- Match Results -->
        <div id="match-results" class="hidden">
            <!-- Overall Score -->
            <div class="bg-white rounded-xl shadow-md p-8 mb-8 text-center">
                <p class="text-sm text-gray-600 mb-2">Overall Success Score</p>
                <div class="relative inline-block">
                    <svg class="w-48 h-48">
                        <circle cx="96" cy="96" r="88" stroke="#e5e7eb" stroke-width="8" fill="none"></circle>
                        <circle id="score-circle" cx="96" cy="96" r="88" stroke="url(#gradient)" stroke-width="8" fill="none" 
                            stroke-dasharray="553" stroke-dashoffset="553" transform="rotate(-90 96 96)" class="transition-all duration-1000"></circle>
                        <defs>
                            <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" style="stop-color:#ec4899;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#8b5cf6;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <p id="overall-score" class="text-5xl font-bold text-gray-900">0</p>
                        <p class="text-sm text-gray-600">/ 100</p>
                    </div>
                </div>
                <p id="recommendation" class="mt-4 text-lg font-semibold"></p>
            </div>

            <!-- Fit Breakdown -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-700">Cultural Fit</h3>
                        <span id="cultural-score" class="text-2xl font-bold text-pink-600">--</span>
                    </div>
                    <div class="h-2 bg-gray-200 rounded-full">
                        <div id="cultural-bar" class="h-full bg-pink-500 rounded-full transition-all duration-500" style="width: 0%"></div>
                    </div>
                    <p id="cultural-level" class="mt-2 text-xs text-gray-600"></p>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-700">Skill Fit</h3>
                        <span id="skill-score" class="text-2xl font-bold text-blue-600">--</span>
                    </div>
                    <div class="h-2 bg-gray-200 rounded-full">
                        <div id="skill-bar" class="h-full bg-blue-500 rounded-full transition-all duration-500" style="width: 0%"></div>
                    </div>
                    <p id="skill-level" class="mt-2 text-xs text-gray-600"></p>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-700">Work Style Fit</h3>
                        <span id="workstyle-score" class="text-2xl font-bold text-purple-600">--</span>
                    </div>
                    <div class="h-2 bg-gray-200 rounded-full">
                        <div id="workstyle-bar" class="h-full bg-purple-500 rounded-full transition-all duration-500" style="width: 0%"></div>
                    </div>
                    <p id="workstyle-level" class="mt-2 text-xs text-gray-600"></p>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-700">Performance</h3>
                        <span id="performance-score" class="text-2xl font-bold text-green-600">--</span>
                    </div>
                    <div class="h-2 bg-gray-200 rounded-full">
                        <div id="performance-bar" class="h-full bg-green-500 rounded-full transition-all duration-500" style="width: 0%"></div>
                    </div>
                    <p id="performance-level" class="mt-2 text-xs text-gray-600"></p>
                </div>
            </div>

            <!-- Strengths & Concerns -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <i data-lucide="thumbs-up" class="w-5 h-5 mr-2 text-green-600"></i>
                        Key Strengths
                    </h3>
                    <ul id="strengths-list" class="space-y-2"></ul>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <i data-lucide="alert-triangle" class="w-5 h-5 mr-2 text-yellow-600"></i>
                        Potential Concerns
                    </h3>
                    <ul id="concerns-list" class="space-y-2"></ul>
                </div>
            </div>

            <!-- AI Assessment -->
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl shadow-md p-6 border-2 border-purple-200">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i data-lucide="sparkles" class="w-5 h-5 mr-2 text-purple-600"></i>
                    AI Holistic Assessment
                </h3>
                <p id="ai-assessment" class="text-gray-700 mb-4"></p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Success Probability</p>
                        <p id="success-probability" class="text-lg font-bold text-purple-900"></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Final Recommendation</p>
                        <p id="final-recommendation" class="text-lg font-bold text-purple-900"></p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
lucide.createIcons();
const companyId = {{ auth()->user()->company_id ?? 'null' }};

async function searchCandidates() {
    const query = document.getElementById('candidate-search').value;
    // Implement candidate search API call
    alert('Candidate search feature - integrate with your user search API');
}

async function analyzeCandidate(candidateId) {
    try {
        const response = await fetch(`/api/scout/candidate-match/${candidateId}?company_id=${companyId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                'Accept': 'application/json',
            }
        });

        const result = await response.json();
        if (result.success) {
            renderMatchResults(result.data);
        }
    } catch (error) {
        console.error('Match analysis failed:', error);
    }
}

function renderMatchResults(data) {
    const prediction = data.success_prediction;
    document.getElementById('match-results').classList.remove('hidden');

    // Overall score
    const score = prediction.overall_success_score;
    document.getElementById('overall-score').textContent = score;
    document.getElementById('recommendation').textContent = prediction.recommendation;
    
    // Animate circle
    const circle = document.getElementById('score-circle');
    const circumference = 553;
    const offset = circumference - (score / 100) * circumference;
    circle.style.strokeDashoffset = offset;

    // Fit scores
    updateFitScore('cultural', prediction.cultural_fit);
    updateFitScore('skill', prediction.skill_fit);
    updateFitScore('workstyle', prediction.work_style_fit);
    updateFitScore('performance', prediction.performance_prediction);

    // Strengths & concerns
    renderList('strengths-list', prediction.strengths, 'green');
    renderList('concerns-list', prediction.concerns, 'yellow');

    // AI assessment
    if (prediction.ai_assessment) {
        document.getElementById('ai-assessment').textContent = prediction.ai_assessment.overall_assessment;
        document.getElementById('success-probability').textContent = prediction.ai_assessment.success_probability;
        document.getElementById('final-recommendation').textContent = prediction.ai_assessment.recommendation;
    }
}

function updateFitScore(type, data) {
    document.getElementById(`${type}-score`).textContent = data.score;
    document.getElementById(`${type}-bar`).style.width = data.score + '%';
    document.getElementById(`${type}-level`).textContent = data.level;
}

function renderList(containerId, items, color) {
    const container = document.getElementById(containerId);
    container.innerHTML = items.map(item => `
        <li class="flex items-start">
            <i data-lucide="check" class="w-4 h-4 text-${color}-600 mr-2 mt-0.5"></i>
            <span class="text-gray-700">${item}</span>
        </li>
    `).join('') || `<li class="text-gray-500">None identified</li>`;
    lucide.createIcons();
}
</script>
@endpush
@endsection
