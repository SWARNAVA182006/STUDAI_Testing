@extends('layouts.app')

@section('title', 'Skill Validation')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-green-50 via-white to-teal-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">Skill Validation</h1>
                    <p class="text-lg text-gray-600">AI-powered analysis of your work history and achievements</p>
                </div>
                <button onclick="runValidation()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Re-analyze Skills
                </button>
            </div>
        </div>

        {{-- Summary Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Verified Skills</p>
                        <p class="text-3xl font-bold text-green-600 mt-1">{{ $validations->where('is_verified', true)->count() }}</p>
                    </div>
                    <svg class="w-12 h-12 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">High Confidence</p>
                        <p class="text-3xl font-bold text-blue-600 mt-1">{{ $validations->where('confidence_score', '>=', 80)->count() }}</p>
                    </div>
                    <svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Needs Evidence</p>
                        <p class="text-3xl font-bold text-orange-600 mt-1">{{ $validations->where('confidence_score', '<', 70)->count() }}</p>
                    </div>
                    <svg class="w-12 h-12 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Validated</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $validations->count() }}</p>
                    </div>
                    <svg class="w-12 h-12 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Validated Skills Table --}}
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-green-500 to-teal-600">
                <h3 class="text-xl font-bold text-white">Validated Skills from Work History</h3>
                <p class="text-green-100 text-sm mt-1">Skills detected and verified from your professional experience</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Skill</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Confidence</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proficiency</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Experience</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Evidence</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($validations as $validation)
                        <tr class="hover:bg-gray-50 cursor-pointer" onclick="showValidationDetails({{ $validation->id }})">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $validation->skill_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $validation->validation_source }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-20 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="h-2 rounded-full transition-all
                                            @if($validation->confidence_score >= 80) bg-green-600
                                            @elseif($validation->confidence_score >= 60) bg-blue-600
                                            @else bg-orange-600
                                            @endif" 
                                            style="width: {{ $validation->confidence_score }}%"></div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-900">{{ $validation->confidence_score }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($validation->proficiency_detected === 'expert') bg-purple-100 text-purple-800
                                    @elseif($validation->proficiency_detected === 'advanced') bg-blue-100 text-blue-800
                                    @elseif($validation->proficiency_detected === 'intermediate') bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($validation->proficiency_detected) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ number_format($validation->years_of_experience, 1) }} years
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ count($validation->key_achievements ?? []) }} achievements
                            </td>
                            <td class="px-6 py-4">
                                @if($validation->is_verified)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Verified
                                </span>
                                @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Pending
                                </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <p class="text-lg font-medium text-gray-900">No Validated Skills Yet</p>
                                <p class="text-sm text-gray-500 mt-1">Click "Re-analyze Skills" to validate your skills from work history</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Validation Details Modal --}}
<div id="validationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-gradient-to-r from-green-500 to-teal-600 px-6 py-4 text-white">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold" id="modalSkillName">Skill Details</h3>
                <button onclick="closeModal()" class="text-white hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="p-6" id="modalContent">
            {{-- Content loaded dynamically --}}
        </div>
    </div>
</div>

<script>
const validationsData = @json($validations);

function showValidationDetails(validationId) {
    const validation = validationsData.find(v => v.id === validationId);
    if (!validation) return;

    document.getElementById('modalSkillName').textContent = validation.skill_name;
    
    const aiAnalysis = validation.ai_analysis || {};
    const keyAchievements = validation.key_achievements || [];
    const suggestions = validation.demonstration_suggestions || [];
    
    const content = `
        <div class="space-y-6">
            <div>
                <h4 class="font-bold text-gray-900 mb-2">Confidence Score</h4>
                <div class="flex items-center">
                    <div class="flex-1 bg-gray-200 rounded-full h-3">
                        <div class="h-3 rounded-full ${validation.confidence_score >= 80 ? 'bg-green-600' : validation.confidence_score >= 60 ? 'bg-blue-600' : 'bg-orange-600'}" style="width: ${validation.confidence_score}%"></div>
                    </div>
                    <span class="ml-3 text-lg font-bold text-gray-900">${validation.confidence_score}%</span>
                </div>
                <p class="text-sm text-gray-600 mt-2">${aiAnalysis.validation_strength || 'Moderate'} validation strength</p>
            </div>

            <div>
                <h4 class="font-bold text-gray-900 mb-2">Detected Proficiency</h4>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${
                    validation.proficiency_detected === 'expert' ? 'bg-purple-100 text-purple-800' :
                    validation.proficiency_detected === 'advanced' ? 'bg-blue-100 text-blue-800' :
                    validation.proficiency_detected === 'intermediate' ? 'bg-green-100 text-green-800' :
                    'bg-gray-100 text-gray-800'
                }">
                    ${validation.proficiency_detected.charAt(0).toUpperCase() + validation.proficiency_detected.slice(1)}
                </span>
                <p class="text-sm text-gray-600 mt-2">${validation.years_of_experience} years of experience</p>
            </div>

            <div>
                <h4 class="font-bold text-gray-900 mb-2">Evidence Source</h4>
                <p class="text-sm text-gray-700">${validation.evidence_description || 'Work history analysis'}</p>
            </div>

            ${keyAchievements.length > 0 ? `
            <div>
                <h4 class="font-bold text-gray-900 mb-2">Key Evidence</h4>
                <ul class="space-y-2">
                    ${keyAchievements.map(achievement => `
                        <li class="flex items-start text-sm text-gray-700">
                            <svg class="w-4 h-4 mr-2 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            ${achievement}
                        </li>
                    `).join('')}
                </ul>
            </div>
            ` : ''}

            ${aiAnalysis.reasoning ? `
            <div>
                <h4 class="font-bold text-gray-900 mb-2">AI Analysis</h4>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-gray-700">${aiAnalysis.reasoning}</p>
                </div>
            </div>
            ` : ''}

            ${suggestions.length > 0 ? `
            <div>
                <h4 class="font-bold text-gray-900 mb-2">💡 Demonstration Suggestions</h4>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <ul class="space-y-2">
                        ${suggestions.map(suggestion => `
                            <li class="flex items-start text-sm text-gray-700">
                                <svg class="w-4 h-4 mr-2 text-yellow-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                ${suggestion}
                            </li>
                        `).join('')}
                    </ul>
                </div>
            </div>
            ` : ''}
        </div>
    `;

    document.getElementById('modalContent').innerHTML = content;
    document.getElementById('validationModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('validationModal').classList.add('hidden');
}

function runValidation() {
    if (confirm('Re-analyze your skills from work history? This may take a few moments.')) {
        const button = event.target;
        button.disabled = true;
        button.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2 inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Analyzing...';
        
        fetch('/api/skills/validate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Authorization': 'Bearer ' + localStorage.getItem('api_token')
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Validation failed: ' + (data.error || 'Unknown error'));
                button.disabled = false;
                button.innerHTML = 'Re-analyze Skills';
            }
        })
        .catch(err => {
            alert('Error: ' + err.message);
            button.disabled = false;
            button.innerHTML = 'Re-analyze Skills';
        });
    }
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});
</script>
@endsection
