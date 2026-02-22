@extends('layouts.app')

@section('title', 'Resume Analysis - S.C.O.U.T.')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-indigo-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Intelligent Resume Analysis</h1>
            <p class="mt-2 text-gray-600">AI-powered semantic analysis beyond keyword matching</p>
        </div>

        <!-- Upload Resume Section -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Upload or Paste Resume</h2>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Candidate Name</label>
                    <input type="text" id="candidate-name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="John Doe">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Professional Summary</label>
                    <textarea id="candidate-summary" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Brief professional summary..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Skills (comma-separated)</label>
                    <textarea id="candidate-skills" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="JavaScript, React, Node.js, Team Leadership, Agile"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Experience (JSON format or paste resume text)</label>
                    <textarea id="candidate-experience" rows="8" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm" placeholder='[{"title": "Senior Developer", "company": "Tech Corp", "start_date": "2020-01", "end_date": "2024-10", "description": "Led development team..."}]'></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Education (JSON format)</label>
                    <textarea id="candidate-education" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm" placeholder='[{"degree": "Bachelor of Science", "field": "Computer Science", "institution": "MIT", "year": "2016"}]'></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Achievements (one per line)</label>
                    <textarea id="candidate-achievements" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Increased revenue by 45%&#10;Led team of 12 engineers&#10;Published 3 technical papers"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Target Job (Optional)</label>
                    <select id="target-job" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="">General Analysis (No Specific Job)</option>
                        <!-- Jobs populated dynamically -->
                    </select>
                </div>

                <button onclick="analyzeResume()" class="w-full px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold rounded-lg shadow-md hover:from-indigo-600 hover:to-purple-700 transition-all duration-200">
                    <i data-lucide="brain" class="inline w-5 h-5 mr-2"></i>
                    Analyze Resume with AI
                </button>
            </div>
        </div>

        <!-- Analysis Results (Hidden by default) -->
        <div id="results-container" class="hidden space-y-8">
            
            <!-- Overall Assessment Card -->
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                <h3 class="text-2xl font-bold mb-4">Overall Assessment</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <p class="text-sm opacity-90">Match Score</p>
                        <p id="overall-score" class="text-5xl font-bold">--</p>
                    </div>
                    <div>
                        <p class="text-sm opacity-90">Recommendation</p>
                        <p id="overall-recommendation" class="text-2xl font-bold mt-2">--</p>
                    </div>
                    <div>
                        <p class="text-sm opacity-90">Candidate Archetype</p>
                        <p id="candidate-archetype" class="text-xl font-semibold mt-2">--</p>
                    </div>
                </div>
            </div>

            <!-- Semantic Skills Analysis -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i data-lucide="sparkles" class="w-6 h-6 mr-2 text-indigo-600"></i>
                    Semantic Skills Analysis
                </h3>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-2">Explicit Skills</h4>
                        <ul id="explicit-skills" class="space-y-2"></ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-2">Transferable Skills (AI-Inferred)</h4>
                        <ul id="transferable-skills" class="space-y-2"></ul>
                    </div>
                </div>

                <div class="mt-6">
                    <h4 class="font-semibold text-gray-700 mb-2">Skill Gaps</h4>
                    <ul id="skill-gaps" class="space-y-2"></ul>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-4">
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <p class="text-sm text-gray-600">Domain Expertise</p>
                        <p id="domain-score" class="text-3xl font-bold text-blue-600">--</p>
                    </div>
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <p class="text-sm text-gray-600">Skill Diversity</p>
                        <p id="skill-diversity" class="text-3xl font-bold text-green-600">--</p>
                    </div>
                </div>
            </div>

            <!-- Career Progression -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i data-lucide="trending-up" class="w-6 h-6 mr-2 text-green-600"></i>
                    Career Progression Analysis
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="text-center p-4 bg-purple-50 rounded-lg">
                        <p class="text-sm text-gray-600">Pattern Type</p>
                        <p id="pattern-type" class="text-lg font-bold text-purple-600">--</p>
                    </div>
                    <div class="text-center p-4 bg-orange-50 rounded-lg">
                        <p class="text-sm text-gray-600">Ambition Score</p>
                        <p id="ambition-score" class="text-3xl font-bold text-orange-600">--</p>
                    </div>
                    <div class="text-center p-4 bg-teal-50 rounded-lg">
                        <p class="text-sm text-gray-600">Stability Score</p>
                        <p id="stability-score" class="text-3xl font-bold text-teal-600">--</p>
                    </div>
                </div>

                <div class="mb-4">
                    <h4 class="font-semibold text-gray-700 mb-2">Career Narrative</h4>
                    <p id="career-narrative" class="text-gray-700 bg-gray-50 p-4 rounded-lg"></p>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-700 mb-2">Strategic Choices</h4>
                    <ul id="strategic-choices" class="space-y-2"></ul>
                </div>
            </div>

            <!-- Achievement Validation -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i data-lucide="award" class="w-6 h-6 mr-2 text-yellow-600"></i>
                    Achievement Validation
                </h3>
                
                <div id="quantified-achievements" class="mb-4"></div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-2">Exceptional Performer Indicators</h4>
                        <ul id="exceptional-indicators" class="space-y-2"></ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700 mb-2">Innovation Examples</h4>
                        <ul id="innovation-examples" class="space-y-2"></ul>
                    </div>
                </div>
            </div>

            <!-- Red Flags & Concerns -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i data-lucide="alert-triangle" class="w-6 h-6 mr-2 text-red-600"></i>
                    Context-Aware Red Flag Analysis
                </h3>
                
                <div class="space-y-4">
                    <div id="job-hopping" class="p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded"></div>
                    <div id="inconsistencies" class="space-y-2"></div>
                    <div id="cultural-concerns" class="space-y-2"></div>
                </div>
            </div>

            <!-- Cultural DNA Alignment -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i data-lucide="heart" class="w-6 h-6 mr-2 text-pink-600"></i>
                    Cultural DNA Alignment
                </h3>
                
                <div class="mb-6">
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Value Alignment</span>
                        <span id="value-alignment-score" class="text-sm font-bold text-pink-600">--</span>
                    </div>
                    <div class="h-4 bg-gray-200 rounded-full">
                        <div id="value-alignment-bar" class="h-full bg-pink-500 rounded-full transition-all duration-500" style="width: 0%"></div>
                    </div>
                </div>

                <div class="mb-6">
                    <h4 class="font-semibold text-gray-700 mb-2">Alignment Evidence</h4>
                    <ul id="alignment-evidence" class="space-y-2"></ul>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <p class="text-sm text-gray-600">Work Style Fit</p>
                        <p id="work-style-fit" class="text-3xl font-bold text-blue-600">--</p>
                    </div>
                    <div class="text-center p-4 bg-purple-50 rounded-lg">
                        <p class="text-sm text-gray-600">Innovation Orientation</p>
                        <p id="innovation-orientation" class="text-3xl font-bold text-purple-600">--</p>
                    </div>
                </div>
            </div>

            <!-- Interview Guidance -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i data-lucide="message-square" class="w-6 h-6 mr-2 text-indigo-600"></i>
                    Interview Focus Areas
                </h3>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-semibold text-green-700 mb-2">Top Strengths to Validate</h4>
                        <ul id="top-strengths" class="space-y-2"></ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-orange-700 mb-2">Areas to Explore</h4>
                        <ul id="interview-focus" class="space-y-2"></ul>
                    </div>
                </div>

                <div class="mt-6">
                    <h4 class="font-semibold text-gray-700 mb-2">Onboarding Support Needed</h4>
                    <ul id="onboarding-support" class="space-y-2"></ul>
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

// Load active jobs for dropdown
document.addEventListener('DOMContentLoaded', loadActiveJobs);

async function loadActiveJobs() {
    try {
        const response = await fetch('/api/jobs?status=active', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                'Accept': 'application/json',
            }
        });
        
        const result = await response.json();
        if (result.success && result.data) {
            const select = document.getElementById('target-job');
            result.data.forEach(job => {
                const option = document.createElement('option');
                option.value = job.id;
                option.textContent = `${job.title} - ${job.department || 'General'}`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Failed to load jobs:', error);
    }
}

async function analyzeResume() {
    const name = document.getElementById('candidate-name').value;
    const summary = document.getElementById('candidate-summary').value;
    const skillsText = document.getElementById('candidate-skills').value;
    const experienceText = document.getElementById('candidate-experience').value;
    const educationText = document.getElementById('candidate-education').value;
    const achievementsText = document.getElementById('candidate-achievements').value;
    const jobId = document.getElementById('target-job').value;

    if (!name) {
        alert('Please enter candidate name');
        return;
    }

    // Parse inputs
    const skills = skillsText.split(',').map(s => s.trim()).filter(s => s);
    const achievements = achievementsText.split('\n').map(a => a.trim()).filter(a => a);
    
    let experience = [];
    let education = [];
    
    try {
        if (experienceText) experience = JSON.parse(experienceText);
    } catch (e) {
        console.warn('Experience not valid JSON, treating as text');
        experience = [{ title: 'See Resume', company: 'Various', description: experienceText }];
    }
    
    try {
        if (educationText) education = JSON.parse(educationText);
    } catch (e) {
        console.warn('Education not valid JSON, treating as text');
        education = [{ degree: educationText, field: '', institution: '', year: '' }];
    }

    const resumeData = {
        name,
        summary,
        skills,
        experience,
        education,
        achievements
    };

    try {
        const response = await fetch('/api/scout/analyze-resume', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                resume_data: resumeData,
                job_id: jobId || null
            })
        });

        const result = await response.json();
        if (result.success) {
            renderAnalysisResults(result.data.resume_analysis);
        } else {
            alert('Analysis failed: ' + result.message);
        }
    } catch (error) {
        console.error('Resume analysis failed:', error);
        alert('Failed to analyze resume');
    }
}

function renderAnalysisResults(analysis) {
    document.getElementById('results-container').classList.remove('hidden');
    
    // Overall Assessment
    document.getElementById('overall-score').textContent = analysis.overall_assessment.overall_match_score || '--';
    document.getElementById('overall-recommendation').textContent = analysis.overall_assessment.recommendation || '--';
    document.getElementById('candidate-archetype').textContent = analysis.candidate_archetype || '--';

    // Semantic Skills
    renderSkillList('explicit-skills', analysis.semantic_skills.explicit_skills, 'skill', 'proficiency');
    renderSkillList('transferable-skills', analysis.semantic_skills.transferable_skills, 'skill', 'inferred_from');
    renderList('skill-gaps', analysis.semantic_skills.skill_gaps.map(g => `${g.skill} (${g.trainability})`), 'orange');
    
    document.getElementById('domain-score').textContent = analysis.semantic_skills.domain_expertise.depth_score || '--';
    document.getElementById('skill-diversity').textContent = analysis.skill_diversity_score || '--';

    // Career Progression
    document.getElementById('pattern-type').textContent = analysis.career_progression.pattern_type || '--';
    document.getElementById('ambition-score').textContent = analysis.career_progression.ambition_score || '--';
    document.getElementById('stability-score').textContent = analysis.career_progression.stability_score || '--';
    document.getElementById('career-narrative').textContent = analysis.career_progression.career_narrative || 'Not available';
    renderList('strategic-choices', analysis.career_progression.strategic_choices || [], 'purple');

    // Achievement Validation
    const achievementsHtml = (analysis.achievement_validation.quantified_achievements || []).map(a => `
        <div class="p-3 bg-yellow-50 border-l-4 border-yellow-500 rounded mb-2">
            <p class="font-semibold">${a.achievement}</p>
            <p class="text-sm text-gray-600">Impact: ${a.impact_scale} | Percentile: ${a.percentile_estimate}</p>
        </div>
    `).join('');
    document.getElementById('quantified-achievements').innerHTML = achievementsHtml || '<p class="text-gray-500">No quantified achievements found</p>';
    
    renderList('exceptional-indicators', analysis.achievement_validation.exceptional_performer_indicators || [], 'yellow');
    renderList('innovation-examples', analysis.achievement_validation.innovation_examples || [], 'blue');

    // Red Flags
    const jobHoppingHtml = `
        <p class="font-semibold">${analysis.red_flags.job_hopping.pattern || 'No pattern detected'}</p>
        <p class="text-sm">Severity: ${analysis.red_flags.job_hopping.severity || 'N/A'}</p>
        <p class="text-sm mt-1">${analysis.red_flags.job_hopping.context || ''}</p>
    `;
    document.getElementById('job-hopping').innerHTML = jobHoppingHtml;
    renderList('inconsistencies', analysis.red_flags.inconsistencies || [], 'red');
    renderList('cultural-concerns', analysis.red_flags.cultural_fit_concerns || [], 'orange');

    // Cultural Alignment
    const valueScore = analysis.cultural_dna_alignment.value_alignment_score || 0;
    document.getElementById('value-alignment-score').textContent = valueScore;
    document.getElementById('value-alignment-bar').style.width = valueScore + '%';
    renderList('alignment-evidence', analysis.cultural_dna_alignment.evidence || [], 'green');
    document.getElementById('work-style-fit').textContent = analysis.cultural_dna_alignment.work_style_compatibility || '--';
    document.getElementById('innovation-orientation').textContent = analysis.cultural_dna_alignment.innovation_orientation || '--';

    // Interview Guidance
    renderList('top-strengths', analysis.overall_assessment.top_strengths || [], 'green');
    renderList('interview-focus', analysis.overall_assessment.interview_focus_areas || [], 'indigo');
    renderList('onboarding-support', analysis.overall_assessment.onboarding_support_needed || [], 'blue');

    lucide.createIcons();
    
    // Scroll to results
    document.getElementById('results-container').scrollIntoView({ behavior: 'smooth' });
}

function renderSkillList(containerId, items, nameKey, detailKey) {
    const container = document.getElementById(containerId);
    container.innerHTML = items.map(item => `
        <li class="flex items-start">
            <i data-lucide="check-circle" class="w-4 h-4 text-green-600 mr-2 mt-1"></i>
            <div>
                <span class="font-semibold">${item[nameKey]}</span>
                <span class="text-sm text-gray-600 ml-2">(${item[detailKey]})</span>
            </div>
        </li>
    `).join('') || '<li class="text-gray-500">None identified</li>';
}

function renderList(containerId, items, color) {
    const container = document.getElementById(containerId);
    container.innerHTML = items.map(item => `
        <li class="flex items-start">
            <i data-lucide="arrow-right" class="w-4 h-4 text-${color}-600 mr-2 mt-1"></i>
            <span class="text-gray-700">${item}</span>
        </li>
    `).join('') || `<li class="text-gray-500">None</li>`;
}
</script>
@endpush
@endsection
