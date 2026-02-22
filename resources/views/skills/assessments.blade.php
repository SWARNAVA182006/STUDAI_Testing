@extends('layouts.app')

@section('title', 'Skill Assessments')

@section('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
@endsection

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">Skill Assessments</h1>
                    <p class="text-lg text-gray-600">Test your skills with AI-generated assessments</p>
                </div>
                <a href="{{ route('skills.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Dashboard
                </a>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Assessments Taken</p>
                        <p class="text-3xl font-bold text-blue-600 mt-1">{{ $gradedAssessments->count() }}</p>
                    </div>
                    <svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Passed</p>
                        <p class="text-3xl font-bold text-green-600 mt-1">{{ $gradedAssessments->where('grade', '>=', 'C')->count() }}</p>
                    </div>
                    <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Average Score</p>
                        <p class="text-3xl font-bold text-purple-600 mt-1">{{ $gradedAssessments->avg('score') ? number_format($gradedAssessments->avg('score'), 1) : 0 }}%</p>
                    </div>
                    <svg class="w-12 h-12 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Certificates Earned</p>
                        <p class="text-3xl font-bold text-yellow-600 mt-1">{{ $gradedAssessments->whereNotNull('certificate_hash')->count() }}</p>
                    </div>
                    <svg class="w-12 h-12 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Active Assessments (In Progress) --}}
        @if($activeAssessments->count() > 0)
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">🔥 In Progress</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($activeAssessments as $assessment)
                <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-xl shadow-lg overflow-hidden text-white">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white bg-opacity-20">
                                {{ ucfirst($assessment->assessment_type) }}
                            </span>
                            <span class="text-sm font-medium">⏱️ {{ $assessment->timeElapsed }}</span>
                        </div>
                        
                        <h3 class="text-xl font-bold mb-2">{{ $assessment->skill_name }}</h3>
                        <p class="text-orange-100 text-sm mb-4">Started {{ $assessment->started_at->diffForHumans() }}</p>

                        <div class="mb-4">
                            <div class="flex justify-between text-sm mb-1">
                                <span>Progress</span>
                                <span>{{ $assessment->progressPercentage }}%</span>
                            </div>
                            <div class="w-full bg-white bg-opacity-20 rounded-full h-2">
                                <div class="bg-white rounded-full h-2" style="width: {{ $assessment->progressPercentage }}%"></div>
                            </div>
                        </div>

                        <a href="{{ route('skills.assessment.take', $assessment->id) }}" class="block w-full text-center bg-white text-orange-600 font-bold py-2 px-4 rounded-lg hover:bg-gray-100 transition-colors">
                            Resume Assessment
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Available Skills to Assess --}}
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-gray-900">📝 Create New Assessment</h2>
                <div class="flex items-center space-x-2">
                    <label for="difficultyFilter" class="text-sm font-medium text-gray-700">Difficulty:</label>
                    <select id="difficultyFilter" onchange="filterSkills()" class="rounded-lg border-gray-300 text-sm">
                        <option value="all">All Levels</option>
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4" id="skillsGrid">
                @forelse($availableSkills as $skill)
                <div class="skill-card bg-white rounded-xl shadow-lg p-4 hover:shadow-xl transition-all" data-difficulty="{{ $skill->proficiency_level ?? 'intermediate' }}">
                    <h4 class="font-bold text-gray-900 mb-2">{{ $skill->skill_name }}</h4>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mb-3
                        @if(($skill->proficiency_level ?? 'intermediate') === 'expert') bg-purple-100 text-purple-800
                        @elseif(($skill->proficiency_level ?? 'intermediate') === 'advanced') bg-blue-100 text-blue-800
                        @elseif(($skill->proficiency_level ?? 'intermediate') === 'intermediate') bg-green-100 text-green-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ ucfirst($skill->proficiency_level ?? 'intermediate') }}
                    </span>
                    <button onclick="showCreateAssessment('{{ $skill->skill_name }}', {{ $skill->id ?? 0 }})" class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-medium py-2 px-4 rounded-lg hover:from-blue-600 hover:to-indigo-700 transition-all">
                        Generate Test
                    </button>
                </div>
                @empty
                <div class="col-span-full text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-lg font-medium text-gray-900">No Verified Skills</p>
                    <p class="text-sm text-gray-500 mt-1">Validate skills from work history first</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Graded Assessments --}}
        @if($gradedAssessments->count() > 0)
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">📊 Test Results</h2>
            
            {{-- Grade Distribution Chart --}}
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Grade Distribution</h3>
                <div style="max-width: 600px; margin: 0 auto;">
                    <canvas id="gradeChart"></canvas>
                </div>
            </div>

            {{-- Results Table --}}
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Skill</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proficiency</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($gradedAssessments as $assessment)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $assessment->skill_name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ ucfirst($assessment->assessment_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-20 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="h-2 rounded-full 
                                                @if($assessment->score >= 90) bg-green-600
                                                @elseif($assessment->score >= 70) bg-blue-600
                                                @elseif($assessment->score >= 50) bg-yellow-600
                                                @else bg-red-600
                                                @endif" 
                                                style="width: {{ $assessment->score }}%"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ $assessment->score }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold
                                        @if($assessment->grade === 'A') bg-green-100 text-green-800
                                        @elseif($assessment->grade === 'B') bg-blue-100 text-blue-800
                                        @elseif($assessment->grade === 'C') bg-yellow-100 text-yellow-800
                                        @elseif($assessment->grade === 'D') bg-orange-100 text-orange-800
                                        @else bg-red-100 text-red-800
                                        @endif">
                                        {{ $assessment->grade }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($assessment->proficiency_awarded)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($assessment->proficiency_awarded === 'expert') bg-purple-100 text-purple-800
                                        @elseif($assessment->proficiency_awarded === 'advanced') bg-blue-100 text-blue-800
                                        @elseif($assessment->proficiency_awarded === 'intermediate') bg-green-100 text-green-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst($assessment->proficiency_awarded) }}
                                    </span>
                                    @else
                                    <span class="text-sm text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $assessment->completed_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex items-center space-x-2">
                                        @if($assessment->certificate_hash)
                                        <a href="{{ route('skills.certificate.public', $assessment->certificate_hash) }}" target="_blank" class="text-yellow-600 hover:text-yellow-700 font-medium">
                                            📜 Certificate
                                        </a>
                                        @endif
                                        <button onclick="showResults({{ $assessment->id }})" class="text-blue-600 hover:text-blue-700 font-medium">
                                            View Details
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Create Assessment Modal --}}
<div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4 text-white rounded-t-xl">
            <h3 class="text-xl font-bold">Create Assessment</h3>
            <p class="text-sm text-blue-100 mt-1" id="modalSkillName"></p>
        </div>

        <div class="p-6">
            <form id="createAssessmentForm" onsubmit="createAssessment(event)">
                <input type="hidden" id="skillNameInput" name="skill_name">
                <input type="hidden" id="userSkillIdInput" name="user_skill_id">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Assessment Type</label>
                    <select name="assessment_type" class="w-full rounded-lg border-gray-300">
                        <option value="multiple_choice">Multiple Choice</option>
                        <option value="coding">Coding Challenge</option>
                        <option value="scenario_based">Scenario-Based</option>
                        <option value="project">Project Assessment</option>
                        <option value="mixed">Mixed (Recommended)</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Difficulty</label>
                    <select name="difficulty" class="w-full rounded-lg border-gray-300">
                        <option value="beginner">Beginner</option>
                        <option value="easy">Easy</option>
                        <option value="moderate">Moderate</option>
                        <option value="intermediate" selected>Intermediate</option>
                        <option value="challenging">Challenging</option>
                        <option value="advanced">Advanced</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Number of Questions</label>
                    <input type="number" name="question_count" min="5" max="50" value="15" class="w-full rounded-lg border-gray-300">
                    <p class="text-xs text-gray-500 mt-1">5-50 questions (recommended: 15)</p>
                </div>

                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="closeCreateModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-lg font-medium hover:from-blue-600 hover:to-indigo-700">
                        Generate Assessment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const gradedAssessments = @json($gradedAssessments);

// Grade Distribution Chart
if (gradedAssessments.length > 0) {
    const gradeCtx = document.getElementById('gradeChart').getContext('2d');
    const gradeCounts = {
        'A': gradedAssessments.filter(a => a.grade === 'A').length,
        'B': gradedAssessments.filter(a => a.grade === 'B').length,
        'C': gradedAssessments.filter(a => a.grade === 'C').length,
        'D': gradedAssessments.filter(a => a.grade === 'D').length,
        'F': gradedAssessments.filter(a => a.grade === 'F').length
    };

    new Chart(gradeCtx, {
        type: 'bar',
        data: {
            labels: ['A (90-100%)', 'B (80-89%)', 'C (70-79%)', 'D (60-69%)', 'F (<60%)'],
            datasets: [{
                label: 'Number of Assessments',
                data: [gradeCounts.A, gradeCounts.B, gradeCounts.C, gradeCounts.D, gradeCounts.F],
                backgroundColor: [
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(234, 179, 8, 0.8)',
                    'rgba(249, 115, 22, 0.8)',
                    'rgba(239, 68, 68, 0.8)'
                ],
                borderColor: [
                    'rgb(34, 197, 94)',
                    'rgb(59, 130, 246)',
                    'rgb(234, 179, 8)',
                    'rgb(249, 115, 22)',
                    'rgb(239, 68, 68)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
}

function filterSkills() {
    const difficulty = document.getElementById('difficultyFilter').value;
    const cards = document.querySelectorAll('.skill-card');
    
    cards.forEach(card => {
        if (difficulty === 'all' || card.dataset.difficulty === difficulty) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function showCreateAssessment(skillName, userSkillId) {
    document.getElementById('modalSkillName').textContent = skillName;
    document.getElementById('skillNameInput').value = skillName;
    document.getElementById('userSkillIdInput').value = userSkillId;
    document.getElementById('createModal').classList.remove('hidden');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
}

function createAssessment(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    button.textContent = 'Generating...';

    fetch('/api/skills/assessment', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Authorization': 'Bearer ' + localStorage.getItem('api_token')
        },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(data => {
        if (data.assessment && data.assessment.id) {
            window.location.href = '/skills/assessment/' + data.assessment.id;
        } else {
            alert('Assessment created! Redirecting...');
            location.reload();
        }
    })
    .catch(err => {
        alert('Error: ' + err.message);
        button.disabled = false;
        button.textContent = 'Generate Assessment';
    });
}

function showResults(assessmentId) {
    window.location.href = '/skills/assessment/' + assessmentId;
}

// Close modal on escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCreateModal();
    }
});
</script>
@endsection
