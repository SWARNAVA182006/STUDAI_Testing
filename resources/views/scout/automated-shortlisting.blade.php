@extends('layouts.app')

@section('title', 'Multi-Stage Automated Shortlisting - S.C.O.U.T.')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-indigo-50 to-purple-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-12 h-12 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl flex items-center justify-center">
                    <i data-lucide="filter" class="w-6 h-6 text-white"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Multi-Stage Automated Shortlisting</h1>
                    <p class="text-gray-600 mt-1">AI-powered 4-round evaluation pipeline</p>
                </div>
            </div>
        </div>

        <!-- Pipeline Overview Card -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i data-lucide="workflow" class="w-5 h-5 text-indigo-600"></i>
                Evaluation Pipeline
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Round 1 -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border-2 border-blue-200">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">1</div>
                        <h3 class="font-semibold text-blue-900">Basic Qualification</h3>
                    </div>
                    <ul class="text-sm text-blue-800 space-y-1">
                        <li>• Education verification</li>
                        <li>• Experience threshold</li>
                        <li>• Legal compliance</li>
                        <li>• Location compatibility</li>
                    </ul>
                </div>

                <!-- Round 2 -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border-2 border-green-200">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center font-bold">2</div>
                        <h3 class="font-semibold text-green-900">Skills & Competency</h3>
                    </div>
                    <ul class="text-sm text-green-800 space-y-1">
                        <li>• Technical skills match</li>
                        <li>• Soft skills evaluation</li>
                        <li>• Success trait alignment</li>
                        <li>• Competency scoring</li>
                    </ul>
                </div>

                <!-- Round 3 -->
                <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg p-4 border-2 border-pink-200">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-8 h-8 bg-pink-600 text-white rounded-full flex items-center justify-center font-bold">3</div>
                        <h3 class="font-semibold text-pink-900">Cultural Fit</h3>
                    </div>
                    <ul class="text-sm text-pink-800 space-y-1">
                        <li>• Value alignment</li>
                        <li>• Work style compatibility</li>
                        <li>• Communication analysis</li>
                        <li>• Team dynamics prediction</li>
                    </ul>
                </div>

                <!-- Round 4 -->
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border-2 border-purple-200">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-8 h-8 bg-purple-600 text-white rounded-full flex items-center justify-center font-bold">4</div>
                        <h3 class="font-semibold text-purple-900">Potential & Growth</h3>
                    </div>
                    <ul class="text-sm text-purple-800 space-y-1">
                        <li>• Learning agility</li>
                        <li>• Career trajectory</li>
                        <li>• Future potential</li>
                        <li>• Long-term value</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Shortlisting Configuration -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i data-lucide="settings" class="w-5 h-5 text-indigo-600"></i>
                Configure Shortlisting
            </h2>

            <form id="shortlisting-form" class="space-y-4">
                <!-- Job Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Job Position *</label>
                    <select id="job-select" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Loading jobs...</option>
                    </select>
                </div>

                <!-- Application Selection Method -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Applications</label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2">
                            <input type="radio" name="selection-method" value="all" checked class="text-indigo-600">
                            <span>All pending applications for this job</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="selection-method" value="specific" class="text-indigo-600">
                            <span>Select specific applications</span>
                        </label>
                    </div>
                </div>

                <!-- Specific Application IDs (hidden by default) -->
                <div id="specific-applications-container" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Application IDs (comma-separated)</label>
                    <textarea id="application-ids" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm" placeholder="e.g., 123, 124, 125"></textarea>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-lg flex items-center justify-center gap-2">
                    <i data-lucide="play" class="w-5 h-5"></i>
                    <span>Run Shortlisting Pipeline</span>
                </button>
            </form>
        </div>

        <!-- Loading State -->
        <div id="loading-state" class="hidden bg-white rounded-xl shadow-md p-8 text-center">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-indigo-600 border-t-transparent mb-4"></div>
            <p class="text-gray-700 font-medium">Running multi-stage evaluation pipeline...</p>
            <p class="text-gray-500 text-sm mt-2">This may take a few moments</p>
        </div>

        <!-- Results Container -->
        <div id="results-container" class="hidden space-y-6">
            
            <!-- Summary Stats -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i data-lucide="bar-chart-3" class="w-5 h-5 text-indigo-600"></i>
                    Shortlisting Summary
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600 mb-1">Total Evaluated</p>
                        <p id="stat-total" class="text-3xl font-bold text-gray-900">0</p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-4">
                        <p class="text-sm text-blue-600 mb-1">Round 1 Pass</p>
                        <p id="stat-round1" class="text-3xl font-bold text-blue-900">0</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <p class="text-sm text-green-600 mb-1">Round 2 Pass</p>
                        <p id="stat-round2" class="text-3xl font-bold text-green-900">0</p>
                    </div>
                    <div class="bg-pink-50 rounded-lg p-4">
                        <p class="text-sm text-pink-600 mb-1">Round 3 Pass</p>
                        <p id="stat-round3" class="text-3xl font-bold text-pink-900">0</p>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4">
                        <p class="text-sm text-purple-600 mb-1">Round 4 Pass</p>
                        <p id="stat-round4" class="text-3xl font-bold text-purple-900">0</p>
                    </div>
                    <div class="bg-gradient-to-br from-indigo-100 to-purple-100 rounded-lg p-4">
                        <p class="text-sm text-indigo-700 mb-1">Shortlisted</p>
                        <p id="stat-shortlisted" class="text-3xl font-bold text-indigo-900">0</p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-sm text-gray-600">Processing Time: <span id="processing-time" class="font-semibold text-gray-900">0s</span></p>
                </div>
            </div>

            <!-- Shortlisted Candidates -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i data-lucide="trophy" class="w-5 h-5 text-yellow-600"></i>
                    Shortlisted Candidates
                </h2>
                <div id="shortlisted-list" class="space-y-4">
                    <!-- Populated by JavaScript -->
                </div>
            </div>

            <!-- Rejected Candidates by Round -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i data-lucide="x-circle" class="w-5 h-5 text-red-600"></i>
                    Rejected Candidates by Round
                </h2>
                
                <div class="space-y-6">
                    <!-- Round 1 Rejections -->
                    <div>
                        <h3 class="font-semibold text-blue-900 mb-2 flex items-center gap-2">
                            <div class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs">1</div>
                            Round 1 - Basic Qualification (<span id="round1-count">0</span> rejected)
                        </h3>
                        <div id="round1-rejections" class="space-y-2">
                            <!-- Populated by JavaScript -->
                        </div>
                    </div>

                    <!-- Round 2 Rejections -->
                    <div>
                        <h3 class="font-semibold text-green-900 mb-2 flex items-center gap-2">
                            <div class="w-6 h-6 bg-green-600 text-white rounded-full flex items-center justify-center text-xs">2</div>
                            Round 2 - Skills & Competency (<span id="round2-count">0</span> rejected)
                        </h3>
                        <div id="round2-rejections" class="space-y-2">
                            <!-- Populated by JavaScript -->
                        </div>
                    </div>

                    <!-- Round 3 Rejections -->
                    <div>
                        <h3 class="font-semibold text-pink-900 mb-2 flex items-center gap-2">
                            <div class="w-6 h-6 bg-pink-600 text-white rounded-full flex items-center justify-center text-xs">3</div>
                            Round 3 - Cultural Fit (<span id="round3-count">0</span> rejected)
                        </h3>
                        <div id="round3-rejections" class="space-y-2">
                            <!-- Populated by JavaScript -->
                        </div>
                    </div>

                    <!-- Round 4 Rejections -->
                    <div>
                        <h3 class="font-semibold text-purple-900 mb-2 flex items-center gap-2">
                            <div class="w-6 h-6 bg-purple-600 text-white rounded-full flex items-center justify-center text-xs">4</div>
                            Round 4 - Potential & Growth (<span id="round4-count">0</span> rejected)
                        </h3>
                        <div id="round4-rejections" class="space-y-2">
                            <!-- Populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Lucide Icons -->
<script src="https://unpkg.com/lucide@latest"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    loadActiveJobs();

    // Toggle specific applications input
    document.querySelectorAll('input[name="selection-method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const specificContainer = document.getElementById('specific-applications-container');
            if (this.value === 'specific') {
                specificContainer.classList.remove('hidden');
            } else {
                specificContainer.classList.add('hidden');
            }
        });
    });

    // Form submission
    document.getElementById('shortlisting-form').addEventListener('submit', function(e) {
        e.preventDefault();
        runShortlisting();
    });
});

async function loadActiveJobs() {
    try {
        const response = await fetch('/api/jobs?status=active', {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
            }
        });

        if (!response.ok) throw new Error('Failed to load jobs');

        const data = await response.json();
        const select = document.getElementById('job-select');
        
        select.innerHTML = '<option value="">Select a job position...</option>';
        data.jobs.forEach(job => {
            const option = document.createElement('option');
            option.value = job.id;
            option.textContent = `${job.title} - ${job.department || 'General'} (${job.applications_count || 0} applications)`;
            select.appendChild(option);
        });

    } catch (error) {
        console.error('Error loading jobs:', error);
        alert('Failed to load active jobs');
    }
}

async function runShortlisting() {
    const jobId = document.getElementById('job-select').value;
    if (!jobId) {
        alert('Please select a job position');
        return;
    }

    const selectionMethod = document.querySelector('input[name="selection-method"]:checked').value;
    let applicationIds;

    if (selectionMethod === 'specific') {
        const idsInput = document.getElementById('application-ids').value;
        if (!idsInput.trim()) {
            alert('Please enter application IDs');
            return;
        }
        applicationIds = idsInput.split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id));
    } else {
        // Fetch all pending applications for the job
        try {
            const response = await fetch(`/api/jobs/${jobId}/applications?status=pending,reviewing`, {
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
                }
            });
            const data = await response.json();
            applicationIds = data.applications.map(app => app.id);
        } catch (error) {
            console.error('Error fetching applications:', error);
            alert('Failed to fetch applications for the job');
            return;
        }
    }

    if (applicationIds.length === 0) {
        alert('No applications to process');
        return;
    }

    // Show loading state
    document.getElementById('shortlisting-form').parentElement.classList.add('hidden');
    document.getElementById('loading-state').classList.remove('hidden');
    document.getElementById('results-container').classList.add('hidden');

    try {
        const response = await fetch('/api/scout/shortlist', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
            },
            body: JSON.stringify({
                job_id: parseInt(jobId),
                application_ids: applicationIds
            })
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Shortlisting failed');
        }

        const result = await response.json();
        displayResults(result);

    } catch (error) {
        console.error('Shortlisting error:', error);
        alert('Shortlisting failed: ' + error.message);
        
        // Show form again
        document.getElementById('shortlisting-form').parentElement.classList.remove('hidden');
        document.getElementById('loading-state').classList.add('hidden');
    }
}

function displayResults(result) {
    const data = result.shortlisting_results;
    const summary = result.summary;

    // Hide loading, show results
    document.getElementById('loading-state').classList.add('hidden');
    document.getElementById('results-container').classList.remove('hidden');

    // Update summary stats
    document.getElementById('stat-total').textContent = summary.total_evaluated;
    document.getElementById('stat-round1').textContent = summary.funnel.round_1_passed;
    document.getElementById('stat-round2').textContent = summary.funnel.round_2_passed;
    document.getElementById('stat-round3').textContent = summary.funnel.round_3_passed;
    document.getElementById('stat-round4').textContent = summary.funnel.round_4_passed;
    document.getElementById('stat-shortlisted').textContent = summary.shortlisted;
    document.getElementById('processing-time').textContent = summary.processing_time_seconds + 's';

    // Display shortlisted candidates
    const shortlistedContainer = document.getElementById('shortlisted-list');
    if (data.shortlisted.length === 0) {
        shortlistedContainer.innerHTML = '<p class="text-gray-500 text-center py-4">No candidates passed all 4 rounds</p>';
    } else {
        shortlistedContainer.innerHTML = data.shortlisted.map((candidate, index) => `
            <div class="border-2 ${getScoreBorderColor(candidate.overall_score)} rounded-lg p-4">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                            ${index + 1}
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg text-gray-900">${candidate.candidate_name}</h3>
                            <p class="text-sm text-gray-600">Application ID: ${candidate.application_id}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold ${getScoreColor(candidate.overall_score)}">${candidate.overall_score}</div>
                        <div class="text-xs text-gray-600">Overall Score</div>
                    </div>
                </div>

                <div class="mb-3">
                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold ${getRecommendationStyle(candidate.recommendation)}">
                        ${candidate.recommendation}
                    </span>
                </div>

                <div class="grid grid-cols-4 gap-2 mb-3">
                    <div class="bg-blue-50 rounded p-2 text-center">
                        <p class="text-xs text-blue-600">Round 1</p>
                        <p class="font-bold text-blue-900">${candidate.round_scores.round_1}</p>
                    </div>
                    <div class="bg-green-50 rounded p-2 text-center">
                        <p class="text-xs text-green-600">Round 2</p>
                        <p class="font-bold text-green-900">${candidate.round_scores.round_2}</p>
                    </div>
                    <div class="bg-pink-50 rounded p-2 text-center">
                        <p class="text-xs text-pink-600">Round 3</p>
                        <p class="font-bold text-pink-900">${candidate.round_scores.round_3}</p>
                    </div>
                    <div class="bg-purple-50 rounded p-2 text-center">
                        <p class="text-xs text-purple-600">Round 4</p>
                        <p class="font-bold text-purple-900">${candidate.round_scores.round_4}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <h4 class="text-sm font-semibold text-green-700 mb-1 flex items-center gap-1">
                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                            Top Strengths
                        </h4>
                        <ul class="text-sm text-gray-700 space-y-1">
                            ${candidate.strengths.slice(0, 3).map(s => `<li>• ${s}</li>`).join('')}
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-orange-700 mb-1 flex items-center gap-1">
                            <i data-lucide="alert-circle" class="w-4 h-4"></i>
                            Considerations
                        </h4>
                        <ul class="text-sm text-gray-700 space-y-1">
                            ${candidate.concerns.length > 0 ? candidate.concerns.slice(0, 3).map(c => `<li>• ${c}</li>`).join('') : '<li class="text-gray-500">None noted</li>'}
                        </ul>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Display rejections by round
    displayRejections('round1', data.rejected_by_round.round_1);
    displayRejections('round2', data.rejected_by_round.round_2);
    displayRejections('round3', data.rejected_by_round.round_3);
    displayRejections('round4', data.rejected_by_round.round_4);

    // Update counts
    document.getElementById('round1-count').textContent = data.rejected_by_round.round_1.length;
    document.getElementById('round2-count').textContent = data.rejected_by_round.round_2.length;
    document.getElementById('round3-count').textContent = data.rejected_by_round.round_3.length;
    document.getElementById('round4-count').textContent = data.rejected_by_round.round_4.length;

    // Recreate icons
    lucide.createIcons();

    // Scroll to results
    document.getElementById('results-container').scrollIntoView({ behavior: 'smooth' });
}

function displayRejections(round, rejections) {
    const container = document.getElementById(`${round}-rejections`);
    
    if (rejections.length === 0) {
        container.innerHTML = '<p class="text-sm text-gray-500 italic">No rejections at this round</p>';
        return;
    }

    container.innerHTML = rejections.map(rejection => `
        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-medium text-gray-900">${rejection.candidate_name}</p>
                    <p class="text-xs text-gray-500">Application ID: ${rejection.application_id}</p>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-gray-700">${rejection.score}</p>
                    <p class="text-xs text-gray-500">Score</p>
                </div>
            </div>
            <div class="mt-2">
                <p class="text-sm text-red-700 font-medium">Reasons:</p>
                <ul class="text-sm text-gray-600 space-y-1 mt-1">
                    ${rejection.reason.map(r => `<li>• ${r}</li>`).join('')}
                </ul>
            </div>
        </div>
    `).join('');
}

function getScoreColor(score) {
    if (score >= 85) return 'text-green-600';
    if (score >= 75) return 'text-blue-600';
    if (score >= 65) return 'text-yellow-600';
    return 'text-gray-600';
}

function getScoreBorderColor(score) {
    if (score >= 85) return 'border-green-400';
    if (score >= 75) return 'border-blue-400';
    if (score >= 65) return 'border-yellow-400';
    return 'border-gray-400';
}

function getRecommendationStyle(rec) {
    if (rec.includes('STRONG HIRE')) return 'bg-green-100 text-green-800 border border-green-300';
    if (rec.includes('RECOMMEND')) return 'bg-blue-100 text-blue-800 border border-blue-300';
    if (rec.includes('CONSIDER')) return 'bg-yellow-100 text-yellow-800 border border-yellow-300';
    return 'bg-gray-100 text-gray-800 border border-gray-300';
}
</script>
@endsection
