@extends('layouts.app')

@section('title', 'Team Compatibility - S.C.O.U.T.')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-indigo-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Team Compatibility Analysis</h1>
            <p class="mt-2 text-gray-600">Assess how well candidates fit with your existing teams</p>
        </div>

        <!-- Department Selection -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Select Team/Department</h2>
            <select id="department-select" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                <option value="">All Departments</option>
                <option value="Engineering">Engineering</option>
                <option value="Product">Product</option>
                <option value="Design">Design</option>
                <option value="Marketing">Marketing</option>
                <option value="Sales">Sales</option>
            </select>
        </div>

        <!-- Team Health Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Team Health Score</h3>
                <p id="team-health" class="text-4xl font-bold text-indigo-600">--</p>
                <div class="mt-3 h-2 bg-gray-200 rounded-full">
                    <div id="health-bar" class="h-full bg-indigo-500 rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Psychological Safety</h3>
                <p id="psych-safety" class="text-4xl font-bold text-green-600">--</p>
                <div class="mt-3 h-2 bg-gray-200 rounded-full">
                    <div id="safety-bar" class="h-full bg-green-500 rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Collaboration Score</h3>
                <p id="collab-score" class="text-4xl font-bold text-purple-600">--</p>
                <div class="mt-3 h-2 bg-gray-200 rounded-full">
                    <div id="collab-bar" class="h-full bg-purple-500 rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <!-- Ideal Candidate Profile -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                <i data-lucide="user-check" class="w-5 h-5 mr-2 text-indigo-600"></i>
                Ideal Candidate Profile for This Team
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-semibold text-gray-800 mb-3">Required Traits</h3>
                    <div id="required-traits" class="space-y-2"></div>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800 mb-3">Skill Gaps to Fill</h3>
                    <div id="skill-gaps" class="space-y-2"></div>
                </div>
            </div>
        </div>

        <!-- Candidate Assessment Form -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Assess Candidate Compatibility</h2>
            <form id="compatibility-form" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Candidate Skills (comma-separated)</label>
                    <input type="text" id="candidate-skills" class="w-full px-4 py-2 border border-gray-300 rounded-lg" 
                        placeholder="JavaScript, React, Leadership, Communication">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Work Style Preferences</label>
                    <input type="text" id="work-style" class="w-full px-4 py-2 border border-gray-300 rounded-lg" 
                        placeholder="Collaborative, Autonomous, Remote-first">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Key Traits</label>
                    <input type="text" id="candidate-traits" class="w-full px-4 py-2 border border-gray-300 rounded-lg" 
                        placeholder="Problem-solver, Self-directed, Creative">
                </div>
                <button type="submit" class="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Assess Compatibility
                </button>
            </form>
        </div>

        <!-- Compatibility Results -->
        <div id="compatibility-results" class="hidden bg-white rounded-xl shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Compatibility Results</h2>
            
            <div class="text-center mb-6">
                <p class="text-sm text-gray-600 mb-2">Team Fit Score</p>
                <p id="fit-score" class="text-5xl font-bold text-indigo-600">--</p>
                <p id="fit-level" class="mt-2 text-lg font-semibold"></p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                        <i data-lucide="check-circle" class="w-4 h-4 mr-2 text-green-600"></i>
                        Compatibility Strengths
                    </h3>
                    <ul id="fit-strengths" class="space-y-2"></ul>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 mb-3 flex items-center">
                        <i data-lucide="alert-triangle" class="w-4 h-4 mr-2 text-yellow-600"></i>
                        Integration Concerns
                    </h3>
                    <ul id="fit-concerns" class="space-y-2"></ul>
                </div>
            </div>

            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                <h4 class="font-semibold text-gray-900 mb-2">Integration Prediction</h4>
                <p id="integration-prediction" class="text-gray-700"></p>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>
lucide.createIcons();
const companyId = {{ auth()->user()->company_id ?? 'null' }};

document.getElementById('department-select').addEventListener('change', loadTeamAnalysis);
document.getElementById('compatibility-form').addEventListener('submit', assessCompatibility);

document.addEventListener('DOMContentLoaded', loadTeamAnalysis);

async function loadTeamAnalysis() {
    const department = document.getElementById('department-select').value;
    
    try {
        const url = `/api/scout/team-compatibility?company_id=${companyId}` + 
            (department ? `&department=${department}` : '');
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                company_id: companyId,
                department: department,
                candidate: { skills: [], work_style: [], traits: [] }
            })
        });

        const result = await response.json();
        if (result.success && result.data) {
            renderTeamAnalysis(result.data);
        }
    } catch (error) {
        console.error('Failed to load team analysis:', error);
    }
}

function renderTeamAnalysis(data) {
    if (data.team_health_score) {
        updateScore('team-health', 'health-bar', data.team_health_score);
    }
    if (data.psychological_safety?.psychological_safety_score) {
        updateScore('psych-safety', 'safety-bar', data.psychological_safety.psychological_safety_score);
    }
    if (data.collaboration_analysis?.collaboration_frequency_score) {
        updateScore('collab-score', 'collab-bar', data.collaboration_analysis.collaboration_frequency_score);
    }

    if (data.ideal_hire_profile) {
        renderIdealProfile(data.ideal_hire_profile);
    }
}

function updateScore(textId, barId, score) {
    document.getElementById(textId).textContent = score;
    document.getElementById(barId).style.width = score + '%';
}

function renderIdealProfile(profile) {
    const traits = profile.ideal_traits || [];
    const gaps = profile.skill_gaps_to_fill || [];

    document.getElementById('required-traits').innerHTML = traits.map(trait => `
        <div class="flex items-center p-2 bg-indigo-50 rounded">
            <i data-lucide="arrow-right" class="w-4 h-4 text-indigo-600 mr-2"></i>
            <span class="text-gray-700">${trait}</span>
        </div>
    `).join('') || '<p class="text-gray-500">No specific traits required</p>';

    document.getElementById('skill-gaps').innerHTML = gaps.map(gap => `
        <div class="flex items-center p-2 bg-purple-50 rounded">
            <i data-lucide="arrow-right" class="w-4 h-4 text-purple-600 mr-2"></i>
            <span class="text-gray-700">${gap}</span>
        </div>
    `).join('') || '<p class="text-gray-500">No skill gaps identified</p>';

    lucide.createIcons();
}

async function assessCompatibility(e) {
    e.preventDefault();

    const skills = document.getElementById('candidate-skills').value.split(',').map(s => s.trim());
    const workStyle = document.getElementById('work-style').value.split(',').map(s => s.trim());
    const traits = document.getElementById('candidate-traits').value.split(',').map(s => s.trim());
    const department = document.getElementById('department-select').value;

    try {
        const response = await fetch('/api/scout/team-compatibility', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                company_id: companyId,
                department: department,
                candidate: { skills, work_style: workStyle, traits }
            })
        });

        const result = await response.json();
        if (result.success) {
            renderCompatibilityResults(result.data);
        }
    } catch (error) {
        console.error('Compatibility assessment failed:', error);
    }
}

function renderCompatibilityResults(data) {
    document.getElementById('compatibility-results').classList.remove('hidden');
    
    document.getElementById('fit-score').textContent = data.team_fit_score || 0;
    document.getElementById('fit-level').textContent = data.fit_level || 'Unknown';

    const strengths = data.strengths || [];
    document.getElementById('fit-strengths').innerHTML = strengths.map(s => `
        <li class="flex items-start text-gray-700">
            <i data-lucide="check" class="w-4 h-4 text-green-600 mr-2 mt-1"></i>
            ${s}
        </li>
    `).join('') || '<li class="text-gray-500">No strengths identified</li>';

    const concerns = data.concerns || [];
    document.getElementById('fit-concerns').innerHTML = concerns.map(c => `
        <li class="flex items-start text-gray-700">
            <i data-lucide="alert-circle" class="w-4 h-4 text-yellow-600 mr-2 mt-1"></i>
            ${c}
        </li>
    `).join('') || '<li class="text-gray-500">No concerns identified</li>';

    document.getElementById('integration-prediction').textContent = 
        data.integration_prediction || 'No prediction available';

    lucide.createIcons();
}
</script>
@endpush
@endsection
