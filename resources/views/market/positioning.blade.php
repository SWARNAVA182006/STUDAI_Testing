@extends('layouts.app')

@section('title', 'Your Market Position')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-purple-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Your Market Position</h1>
            <p class="text-lg text-gray-600">See exactly where you stand in the market</p>
        </div>

        <!-- Market Readiness Score -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-blue-100">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Market Readiness Score</h2>
            
            <div class="flex flex-col md:flex-row items-center gap-8">
                <!-- Score Gauge -->
                <div class="relative w-64 h-64">
                    <canvas id="readinessGauge"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <div class="text-5xl font-bold text-gray-900" id="readiness-score">--</div>
                        <div class="text-sm text-gray-600">out of 100</div>
                        <div class="mt-2 px-4 py-1 rounded-full text-sm font-semibold" id="readiness-status-badge">
                            Loading...
                        </div>
                    </div>
                </div>

                <!-- Score Breakdown -->
                <div class="flex-1 space-y-4 w-full">
                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Profile Completeness (15%)</span>
                            <span class="text-sm font-semibold text-gray-900" id="profile-score">--</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-blue-600 h-3 rounded-full transition-all" id="profile-bar" style="width: 0%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Experience Quality (30%)</span>
                            <span class="text-sm font-semibold text-gray-900" id="experience-score">--</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-purple-600 h-3 rounded-full transition-all" id="experience-bar" style="width: 0%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Skills Modernity (25%)</span>
                            <span class="text-sm font-semibold text-gray-900" id="skills-score">--</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-pink-600 h-3 rounded-full transition-all" id="skills-bar" style="width: 0%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Education Relevance (10%)</span>
                            <span class="text-sm font-semibold text-gray-900" id="education-score">--</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-green-600 h-3 rounded-full transition-all" id="education-bar" style="width: 0%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Market Alignment (20%)</span>
                            <span class="text-sm font-semibold text-gray-900" id="market-score">--</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-orange-600 h-3 rounded-full transition-all" id="market-bar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Percentile Rankings -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-purple-100">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Percentile Rankings</h2>
            <p class="text-gray-600 mb-6">How you compare to other job seekers in your field</p>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="text-center p-6 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl">
                    <div class="text-4xl font-bold text-purple-600 mb-2" id="overall-percentile">--</div>
                    <div class="text-sm font-semibold text-gray-700">Overall</div>
                    <div class="text-xs text-gray-600 mt-1">Top <span id="overall-top">--</span>%</div>
                </div>

                <div class="text-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl">
                    <div class="text-4xl font-bold text-blue-600 mb-2" id="experience-percentile">--</div>
                    <div class="text-sm font-semibold text-gray-700">Experience</div>
                    <div class="text-xs text-gray-600 mt-1">Top <span id="experience-top">--</span>%</div>
                </div>

                <div class="text-center p-6 bg-gradient-to-br from-pink-50 to-pink-100 rounded-xl">
                    <div class="text-4xl font-bold text-pink-600 mb-2" id="skills-percentile">--</div>
                    <div class="text-sm font-semibold text-gray-700">Skills</div>
                    <div class="text-xs text-gray-600 mt-1">Top <span id="skills-top">--</span>%</div>
                </div>

                <div class="text-center p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-xl">
                    <div class="text-4xl font-bold text-green-600 mb-2" id="compensation-percentile">--</div>
                    <div class="text-sm font-semibold text-gray-700">Compensation</div>
                    <div class="text-xs text-gray-600 mt-1">Top <span id="compensation-top">--</span>%</div>
                </div>
            </div>
        </div>

        <!-- Competitive Analysis -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Competitive Advantages -->
            <div class="bg-white rounded-2xl shadow-lg p-8 border border-green-100">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">✨ Competitive Advantages</h2>
                <div class="space-y-3" id="competitive-advantages">
                    <div class="animate-pulse space-y-3">
                        <div class="h-12 bg-gray-200 rounded-lg"></div>
                        <div class="h-12 bg-gray-200 rounded-lg"></div>
                        <div class="h-12 bg-gray-200 rounded-lg"></div>
                    </div>
                </div>
            </div>

            <!-- Areas to Improve -->
            <div class="bg-white rounded-2xl shadow-lg p-8 border border-orange-100">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">📈 Areas to Improve</h2>
                <div class="space-y-3" id="competitive-weaknesses">
                    <div class="animate-pulse space-y-3">
                        <div class="h-12 bg-gray-200 rounded-lg"></div>
                        <div class="h-12 bg-gray-200 rounded-lg"></div>
                        <div class="h-12 bg-gray-200 rounded-lg"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Skill Gaps -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-purple-100">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">🎯 Skills to Develop</h2>
            <p class="text-gray-600 mb-6">High-value skills that will boost your market position</p>
            
            <div class="space-y-4" id="skill-gaps">
                <div class="animate-pulse space-y-4">
                    <div class="h-16 bg-gray-200 rounded-lg"></div>
                    <div class="h-16 bg-gray-200 rounded-lg"></div>
                    <div class="h-16 bg-gray-200 rounded-lg"></div>
                </div>
            </div>
        </div>

        <!-- Role Fit Analysis -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-blue-100">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">🎯 Role Fit Analysis</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Best Fit Roles -->
                <div>
                    <h3 class="text-lg font-semibold text-green-600 mb-4">✓ Best Fit Roles</h3>
                    <div class="space-y-2" id="best-fit-roles">
                        <div class="animate-pulse space-y-2">
                            <div class="h-10 bg-gray-200 rounded"></div>
                            <div class="h-10 bg-gray-200 rounded"></div>
                        </div>
                    </div>
                </div>

                <!-- Trending Opportunities -->
                <div>
                    <h3 class="text-lg font-semibold text-blue-600 mb-4">🔥 Trending Opportunities</h3>
                    <div class="space-y-2" id="trending-opportunities">
                        <div class="animate-pulse space-y-2">
                            <div class="h-10 bg-gray-200 rounded"></div>
                            <div class="h-10 bg-gray-200 rounded"></div>
                        </div>
                    </div>
                </div>

                <!-- Roles to Avoid -->
                <div>
                    <h3 class="text-lg font-semibold text-red-600 mb-4">⚠️ Roles to Avoid</h3>
                    <div class="space-y-2" id="roles-to-avoid">
                        <div class="animate-pulse space-y-2">
                            <div class="h-10 bg-gray-200 rounded"></div>
                            <div class="h-10 bg-gray-200 rounded"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recommendations -->
        <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl shadow-lg p-8 text-white">
            <h2 class="text-3xl font-bold mb-6">🚀 Recommended Actions</h2>
            <div class="space-y-4" id="recommendations">
                <div class="animate-pulse space-y-4">
                    <div class="h-16 bg-white/20 rounded-lg"></div>
                    <div class="h-16 bg-white/20 rounded-lg"></div>
                    <div class="h-16 bg-white/20 rounded-lg"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadUserPosition();
});

async function loadUserPosition() {
    try {
        const response = await fetch('/api/market/user-position', {
            headers: {
                'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content,
                'Accept': 'application/json',
            }
        });
        
        if (!response.ok) throw new Error('Failed to fetch user position');
        
        const result = await response.json();
        const data = result.data;
        
        // Update readiness score
        const score = Math.round(data.readiness_score || 0);
        document.getElementById('readiness-score').textContent = score;
        
        const statusBadge = document.getElementById('readiness-status-badge');
        statusBadge.textContent = data.status_label || data.status;
        statusBadge.className = 'mt-2 px-4 py-1 rounded-full text-sm font-semibold bg-' + (data.status_color || 'gray') + '-100 text-' + (data.status_color || 'gray') + '-700';
        
        // Render readiness gauge
        renderReadinessGauge(score);
        
        // Update score breakdown (assuming we get component scores)
        updateScoreBreakdown(data.score_breakdown || {});
        
        // Update percentiles
        const percentiles = data.percentiles || {};
        document.getElementById('overall-percentile').textContent = Math.round(percentiles.overall || 0) + 'th';
        document.getElementById('overall-top').textContent = 100 - Math.round(percentiles.overall || 0);
        
        document.getElementById('experience-percentile').textContent = Math.round(percentiles.experience || 0) + 'th';
        document.getElementById('experience-top').textContent = 100 - Math.round(percentiles.experience || 0);
        
        document.getElementById('skills-percentile').textContent = Math.round(percentiles.skills || 0) + 'th';
        document.getElementById('skills-top').textContent = 100 - Math.round(percentiles.skills || 0);
        
        document.getElementById('compensation-percentile').textContent = Math.round(percentiles.compensation || 0) + 'th';
        document.getElementById('compensation-top').textContent = 100 - Math.round(percentiles.compensation || 0);
        
        // Render competitive analysis
        renderCompetitiveAnalysis(data.competitive_analysis || {});
        
        // Render skill gaps
        renderSkillGaps(data.competitive_analysis?.skill_gaps || []);
        
        // Render role fit
        renderRoleFit(data.role_fit || {});
        
        // Render recommendations
        renderRecommendations(data.recommendations || []);
        
    } catch (error) {
        console.error('Error loading user position:', error);
    }
}

let readinessGaugeChart = null;
function renderReadinessGauge(score) {
    const ctx = document.getElementById('readinessGauge');
    if (!ctx) return;
    
    if (readinessGaugeChart) readinessGaugeChart.destroy();
    
    readinessGaugeChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [score, 100 - score],
                backgroundColor: [
                    score >= 80 ? 'rgb(34, 197, 94)' : score >= 60 ? 'rgb(59, 130, 246)' : score >= 40 ? 'rgb(251, 146, 60)' : 'rgb(239, 68, 68)',
                    'rgb(229, 231, 235)'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '75%',
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false }
            }
        }
    });
}

function updateScoreBreakdown(breakdown) {
    const components = {
        profile: breakdown.profile || 0,
        experience: breakdown.experience || 0,
        skills: breakdown.skills || 0,
        education: breakdown.education || 0,
        market: breakdown.market || 0
    };
    
    Object.keys(components).forEach(key => {
        const score = Math.round(components[key]);
        document.getElementById(key + '-score').textContent = score;
        document.getElementById(key + '-bar').style.width = score + '%';
    });
}

function renderCompetitiveAnalysis(analysis) {
    const advantages = analysis.advantages || [];
    const weaknesses = analysis.weaknesses || [];
    
    document.getElementById('competitive-advantages').innerHTML = advantages.length > 0 
        ? advantages.map(adv => `
            <div class="flex items-start gap-3 p-4 bg-green-50 rounded-lg">
                <div class="text-green-600 text-xl">✓</div>
                <div class="flex-1 text-sm text-gray-700">${adv}</div>
            </div>
        `).join('')
        : '<p class="text-gray-500 text-center py-8">Complete your profile to see your advantages</p>';
    
    document.getElementById('competitive-weaknesses').innerHTML = weaknesses.length > 0
        ? weaknesses.map(weak => `
            <div class="flex items-start gap-3 p-4 bg-orange-50 rounded-lg">
                <div class="text-orange-600 text-xl">→</div>
                <div class="flex-1 text-sm text-gray-700">${weak}</div>
            </div>
        `).join('')
        : '<p class="text-gray-500 text-center py-8">No areas to improve identified</p>';
}

function renderSkillGaps(gaps) {
    document.getElementById('skill-gaps').innerHTML = gaps.length > 0
        ? gaps.map(gap => {
            const skillName = typeof gap === 'string' ? gap : gap.skill;
            const priority = gap.priority || 'medium';
            const value = gap.value_score || 0;
            
            return `
                <div class="flex items-center justify-between p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                    <div class="flex items-center gap-4">
                        <div class="px-3 py-1 rounded-full text-xs font-semibold ${
                            priority === 'high' ? 'bg-red-100 text-red-700' :
                            priority === 'medium' ? 'bg-orange-100 text-orange-700' :
                            'bg-yellow-100 text-yellow-700'
                        }">
                            ${priority.toUpperCase()}
                        </div>
                        <div class="font-semibold text-gray-900">${skillName}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-semibold text-purple-600">Value: ${value}/100</div>
                    </div>
                </div>
            `;
        }).join('')
        : '<p class="text-gray-500 text-center py-8">No critical skill gaps identified</p>';
}

function renderRoleFit(roleFit) {
    const bestFit = roleFit.best_fit || [];
    const trending = roleFit.trending || [];
    const avoid = roleFit.avoid || [];
    
    document.getElementById('best-fit-roles').innerHTML = bestFit.length > 0
        ? bestFit.map(role => `<div class="p-3 bg-green-50 rounded-lg text-sm font-medium text-gray-900">${role}</div>`).join('')
        : '<p class="text-gray-500 text-sm">No data yet</p>';
    
    document.getElementById('trending-opportunities').innerHTML = trending.length > 0
        ? trending.map(role => `<div class="p-3 bg-blue-50 rounded-lg text-sm font-medium text-gray-900">${role}</div>`).join('')
        : '<p class="text-gray-500 text-sm">No data yet</p>';
    
    document.getElementById('roles-to-avoid').innerHTML = avoid.length > 0
        ? avoid.map(role => `<div class="p-3 bg-red-50 rounded-lg text-sm font-medium text-gray-900">${role}</div>`).join('')
        : '<p class="text-gray-500 text-sm">No roles to avoid</p>';
}

function renderRecommendations(recommendations) {
    document.getElementById('recommendations').innerHTML = recommendations.length > 0
        ? recommendations.map((rec, index) => {
            const action = typeof rec === 'string' ? rec : rec.action;
            const priority = rec.priority || 'medium';
            
            return `
                <div class="flex items-start gap-4 p-4 bg-white/20 rounded-lg hover:bg-white/30 transition">
                    <div class="text-2xl font-bold text-white/70">${index + 1}</div>
                    <div class="flex-1">
                        <div class="text-white font-medium">${action}</div>
                        ${rec.impact ? `<div class="text-sm text-purple-100 mt-1">Impact: ${rec.impact}</div>` : ''}
                    </div>
                </div>
            `;
        }).join('')
        : '<p class="text-white/70 text-center py-8">Keep up the great work!</p>';
}
</script>
@endpush
