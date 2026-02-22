@extends('layouts.app')

@section('title', 'Skill Trends & Analysis')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-pink-50 to-purple-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Skill Trends & Analysis</h1>
            <p class="text-lg text-gray-600">Discover which skills are rising and falling in demand</p>
        </div>

        <!-- Skill Search -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-purple-100">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">🔍 Analyze a Skill</h2>
            
            <div class="flex gap-4">
                <input type="text" id="skill-search" placeholder="e.g., React, Python, Machine Learning" 
                    class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                <button onclick="analyzeSkill()" 
                    class="bg-gradient-to-r from-purple-500 to-pink-500 text-white font-semibold py-3 px-8 rounded-lg hover:shadow-lg transition">
                    Analyze
                </button>
            </div>

            <div id="skill-analysis-results" class="mt-8" style="display: none;">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="text-center p-6 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl">
                        <div class="text-4xl font-bold text-purple-600 mb-2" id="skill-demand-score">--</div>
                        <div class="text-sm text-gray-600">Demand Score</div>
                        <div class="text-xs text-gray-500 mt-1">0-100 scale</div>
                    </div>

                    <div class="text-center p-6 bg-gradient-to-br from-pink-50 to-pink-100 rounded-xl">
                        <div class="text-4xl font-bold text-pink-600 mb-2" id="skill-growth-rate">--</div>
                        <div class="text-sm text-gray-600">Growth Rate</div>
                        <div class="text-xs text-gray-500 mt-1">month-over-month</div>
                    </div>

                    <div class="text-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl">
                        <div class="text-4xl font-bold text-blue-600 mb-2" id="skill-value-score">--</div>
                        <div class="text-sm text-gray-600">Value Score</div>
                        <div class="text-xs text-gray-500 mt-1">salary premium</div>
                    </div>

                    <div class="text-center p-6 rounded-xl" id="skill-status-card">
                        <div class="text-3xl mb-2" id="skill-status-emoji">--</div>
                        <div class="text-lg font-bold mb-1" id="skill-status">--</div>
                        <div class="text-xs text-gray-600">Trend Status</div>
                    </div>
                </div>

                <!-- Skill Evolution Chart -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">12-Month Evolution</h3>
                    <canvas id="skillEvolutionChart" height="300"></canvas>
                </div>

                <!-- Obsolescence Risk -->
                <div class="bg-gradient-to-r from-orange-50 to-red-50 rounded-xl p-6 border border-orange-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">⚠️ Obsolescence Risk Analysis</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <div class="text-sm text-gray-600 mb-1">Risk Level</div>
                            <div class="text-2xl font-bold" id="obsolescence-risk">--</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600 mb-1">Risk Score</div>
                            <div class="text-2xl font-bold" id="obsolescence-score">--</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600 mb-1">Time to Obsolescence</div>
                            <div class="text-2xl font-bold" id="months-to-obsolescence">--</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trending Skills -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Emerging Skills -->
            <div class="bg-white rounded-2xl shadow-lg p-8 border border-green-100">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">🌱 Emerging Skills</h2>
                <div class="space-y-3" id="emerging-skills">
                    <div class="animate-pulse space-y-3">
                        <div class="h-16 bg-gray-200 rounded-lg"></div>
                        <div class="h-16 bg-gray-200 rounded-lg"></div>
                        <div class="h-16 bg-gray-200 rounded-lg"></div>
                    </div>
                </div>
            </div>

            <!-- Hot Skills -->
            <div class="bg-white rounded-2xl shadow-lg p-8 border border-red-100">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">🔥 Hot Skills</h2>
                <div class="space-y-3" id="hot-skills">
                    <div class="animate-pulse space-y-3">
                        <div class="h-16 bg-gray-200 rounded-lg"></div>
                        <div class="h-16 bg-gray-200 rounded-lg"></div>
                        <div class="h-16 bg-gray-200 rounded-lg"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Declining Skills -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-orange-100">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">📉 Declining Skills</h2>
            <p class="text-gray-600 mb-6">Skills losing market demand - consider transitioning away</p>
            <div class="space-y-3" id="declining-skills">
                <div class="animate-pulse space-y-3">
                    <div class="h-16 bg-gray-200 rounded-lg"></div>
                    <div class="h-16 bg-gray-200 rounded-lg"></div>
                    <div class="h-16 bg-gray-200 rounded-lg"></div>
                </div>
            </div>
        </div>

        <!-- Skill Combinations -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-blue-100">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">💎 High-Value Skill Combinations</h2>
            <p class="text-gray-600 mb-6">These skill pairs command premium salaries</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="skill-combinations">
                <div class="animate-pulse space-y-4">
                    <div class="h-20 bg-gray-200 rounded-lg"></div>
                    <div class="h-20 bg-gray-200 rounded-lg"></div>
                </div>
            </div>
        </div>

        <!-- Your Upskilling Roadmap -->
        <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl shadow-lg p-8 text-white">
            <h2 class="text-3xl font-bold mb-6">🎯 Your Personalized Upskilling Roadmap</h2>
            
            <button onclick="loadUpskillingRoadmap()" 
                class="mb-6 bg-white text-purple-600 font-semibold py-3 px-8 rounded-lg hover:bg-purple-50 transition">
                Generate My Roadmap
            </button>

            <div id="upskilling-roadmap" style="display: none;">
                <!-- Current Skills Analysis -->
                <div class="mb-8">
                    <h3 class="text-xl font-bold mb-4">📊 Your Current Skills Status</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4" id="current-skills-status">
                        <!-- Will be populated -->
                    </div>
                </div>

                <!-- Roadmap Phases -->
                <div class="space-y-6">
                    <!-- Immediate (0-3 months) -->
                    <div class="bg-white/20 rounded-xl p-6">
                        <h3 class="text-xl font-bold mb-4">🚀 Immediate Focus (0-3 months)</h3>
                        <div class="space-y-3" id="immediate-skills">
                            <!-- Will be populated -->
                        </div>
                    </div>

                    <!-- Short Term (3-6 months) -->
                    <div class="bg-white/20 rounded-xl p-6">
                        <h3 class="text-xl font-bold mb-4">📅 Short Term (3-6 months)</h3>
                        <div class="space-y-3" id="short-term-skills">
                            <!-- Will be populated -->
                        </div>
                    </div>

                    <!-- Long Term (6-12 months) -->
                    <div class="bg-white/20 rounded-xl p-6">
                        <h3 class="text-xl font-bold mb-4">🎓 Long Term (6-12 months)</h3>
                        <div class="space-y-3" id="long-term-skills">
                            <!-- Will be populated -->
                        </div>
                    </div>
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
    loadTrendingSkills();
    loadSkillCombinations();
});

async function loadTrendingSkills() {
    try {
        // Load emerging skills
        const emergingResponse = await fetch('/api/market/skill-trends?status=emerging&limit=5', {
            headers: {
                'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content,
                'Accept': 'application/json',
            }
        });
        
        if (emergingResponse.ok) {
            const result = await emergingResponse.json();
            renderSkillList(result.data.skills || [], 'emerging-skills', 'green');
        }

        // Load hot skills
        const hotResponse = await fetch('/api/market/skill-trends?status=hot&limit=5', {
            headers: {
                'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content,
                'Accept': 'application/json',
            }
        });
        
        if (hotResponse.ok) {
            const result = await hotResponse.json();
            renderSkillList(result.data.skills || [], 'hot-skills', 'red');
        }

        // Load declining skills
        const decliningResponse = await fetch('/api/market/skill-trends?status=declining&limit=5', {
            headers: {
                'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content,
                'Accept': 'application/json',
            }
        });
        
        if (decliningResponse.ok) {
            const result = await decliningResponse.json();
            renderSkillList(result.data.skills || [], 'declining-skills', 'orange');
        }

    } catch (error) {
        console.error('Error loading trending skills:', error);
    }
}

function renderSkillList(skills, containerId, color) {
    const container = document.getElementById(containerId);
    
    container.innerHTML = skills.length > 0 
        ? skills.map(skill => `
            <div class="flex items-center justify-between p-4 bg-${color}-50 rounded-lg hover:bg-${color}-100 transition cursor-pointer" 
                onclick="document.getElementById('skill-search').value='${skill.skill_name}'; analyzeSkill();">
                <div class="flex items-center gap-3">
                    <div class="text-2xl">${skill.status_label || '📊'}</div>
                    <div>
                        <div class="font-semibold text-gray-900">${skill.skill_name}</div>
                        <div class="text-sm text-gray-600">Demand: ${Math.round(skill.demand_score || 0)}/100</div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-semibold ${skill.growth_rate >= 0 ? 'text-green-600' : 'text-red-600'}">
                        ${skill.growth_rate >= 0 ? '+' : ''}${(skill.growth_rate || 0).toFixed(1)}%
                    </div>
                    <div class="text-xs text-gray-500">growth</div>
                </div>
            </div>
        `).join('')
        : '<p class="text-gray-500 text-center py-8">No data available</p>';
}

async function analyzeSkill() {
    const skill = document.getElementById('skill-search').value.trim();
    
    if (!skill) {
        alert('Please enter a skill name');
        return;
    }
    
    try {
        const response = await fetch(`/api/market/skill-trends?skill=${encodeURIComponent(skill)}`, {
            headers: {
                'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content,
                'Accept': 'application/json',
            }
        });
        
        if (!response.ok) throw new Error('Failed to analyze skill');
        
        const result = await response.json();
        const data = result.data;
        
        document.getElementById('skill-analysis-results').style.display = 'block';
        
        // Update stats
        document.getElementById('skill-demand-score').textContent = Math.round(data.skill.demand_score || 0);
        
        const growthEl = document.getElementById('skill-growth-rate');
        const growth = data.skill.growth_rate || 0;
        growthEl.textContent = (growth >= 0 ? '+' : '') + growth.toFixed(1) + '%';
        growthEl.className = 'text-4xl font-bold ' + (growth >= 0 ? 'text-pink-600' : 'text-red-600');
        
        document.getElementById('skill-value-score').textContent = Math.round(data.skill.value_score || 0);
        
        // Update status
        const status = data.skill.trend_status || 'stable';
        const statusCard = document.getElementById('skill-status-card');
        const statusEmojis = { emerging: '🌱', hot: '🔥', stable: '✓', declining: '📉', obsolete: '⚠️' };
        const statusColors = { emerging: 'from-green-50 to-green-100', hot: 'from-red-50 to-red-100', stable: 'from-blue-50 to-blue-100', declining: 'from-orange-50 to-orange-100', obsolete: 'from-gray-50 to-gray-100' };
        
        statusCard.className = 'text-center p-6 bg-gradient-to-br ' + (statusColors[status] || statusColors.stable) + ' rounded-xl';
        document.getElementById('skill-status-emoji').textContent = statusEmojis[status] || '📊';
        document.getElementById('skill-status').textContent = status.charAt(0).toUpperCase() + status.slice(1);
        
        // Render evolution chart
        if (data.evolution && data.evolution.evolution) {
            renderSkillEvolutionChart(data.evolution.evolution);
        }
        
        // Update obsolescence risk
        const obsolescence = data.obsolescence || {};
        const riskEl = document.getElementById('obsolescence-risk');
        const risk = obsolescence.obsolescence_risk || 'unknown';
        riskEl.textContent = risk.toUpperCase();
        riskEl.className = 'text-2xl font-bold ' + 
            (risk === 'high' ? 'text-red-600' :
             risk === 'medium' ? 'text-orange-600' :
             risk === 'low' ? 'text-green-600' : 'text-gray-600');
        
        document.getElementById('obsolescence-score').textContent = Math.round(obsolescence.obsolescence_score || 0) + '/100';
        document.getElementById('months-to-obsolescence').textContent = 
            obsolescence.months_to_obsolescence 
                ? obsolescence.months_to_obsolescence + ' months' 
                : 'N/A';
        
        // Scroll to results
        document.getElementById('skill-analysis-results').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
    } catch (error) {
        console.error('Error analyzing skill:', error);
        alert('Failed to analyze skill. Please try again.');
    }
}

let skillEvolutionChart = null;
function renderSkillEvolutionChart(evolution) {
    const ctx = document.getElementById('skillEvolutionChart');
    if (!ctx) return;
    
    if (skillEvolutionChart) skillEvolutionChart.destroy();
    
    skillEvolutionChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: evolution.map(e => e.date),
            datasets: [
                {
                    label: 'Demand Score',
                    data: evolution.map(e => e.demand_score || 0),
                    borderColor: 'rgb(147, 51, 234)',
                    backgroundColor: 'rgba(147, 51, 234, 0.1)',
                    yAxisID: 'y',
                    tension: 0.4
                },
                {
                    label: 'Value Score',
                    data: evolution.map(e => e.value_score || 0),
                    borderColor: 'rgb(236, 72, 153)',
                    backgroundColor: 'rgba(236, 72, 153, 0.1)',
                    yAxisID: 'y',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    min: 0,
                    max: 100
                }
            }
        }
    });
}

async function loadSkillCombinations() {
    try {
        const response = await fetch('/api/market/skill-combinations', {
            headers: {
                'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content,
                'Accept': 'application/json',
            }
        });
        
        if (!response.ok) throw new Error('Failed to load skill combinations');
        
        const result = await response.json();
        const combinations = result.data.combinations || {};
        
        const container = document.getElementById('skill-combinations');
        container.innerHTML = Object.entries(combinations).slice(0, 6).map(([combo, data]) => `
            <div class="p-6 bg-gradient-to-br from-blue-50 to-purple-50 rounded-xl border border-blue-200 hover:shadow-md transition">
                <div class="flex items-center gap-2 mb-3">
                    ${data.skills.map(skill => `<span class="px-3 py-1 bg-white rounded-full text-sm font-semibold text-gray-900">${skill}</span>`).join('<span class="text-gray-400">+</span>')}
                </div>
                <div class="flex justify-between items-center">
                    <div>
                        <div class="text-sm text-gray-600">Average Salary</div>
                        <div class="text-xl font-bold text-blue-600">₹${(data.avg_salary || 0).toLocaleString()}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-600">In ${data.count} jobs</div>
                    </div>
                </div>
            </div>
        `).join('') || '<p class="text-gray-500 text-center py-8">No data available</p>';
        
    } catch (error) {
        console.error('Error loading skill combinations:', error);
    }
}

async function loadUpskillingRoadmap() {
    try {
        const response = await fetch('/api/market/upskilling-roadmap', {
            headers: {
                'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content,
                'Accept': 'application/json',
            }
        });
        
        if (!response.ok) throw new Error('Failed to load roadmap');
        
        const result = await response.json();
        const data = result.data;
        
        document.getElementById('upskilling-roadmap').style.display = 'block';
        
        // Render current skills status
        const currentSkills = data.current_skills_analysis || {};
        const statusHtml = Object.entries(currentSkills).slice(0, 6).map(([skill, analysis]) => `
            <div class="bg-white/20 rounded-lg p-4">
                <div class="font-semibold text-white mb-1">${skill}</div>
                <div class="text-sm text-purple-100">Status: ${analysis.trend_status || 'Unknown'}</div>
                <div class="text-xs text-purple-200">Demand: ${Math.round(analysis.demand_score || 0)}/100</div>
            </div>
        `).join('');
        document.getElementById('current-skills-status').innerHTML = statusHtml || '<p class="text-purple-100">No skills data available</p>';
        
        // Render roadmap phases
        renderRoadmapPhase(data.roadmap?.immediate || [], 'immediate-skills');
        renderRoadmapPhase(data.roadmap?.short_term || [], 'short-term-skills');
        renderRoadmapPhase(data.roadmap?.long_term || [], 'long-term-skills');
        
    } catch (error) {
        console.error('Error loading upskilling roadmap:', error);
        alert('Failed to load upskilling roadmap. Please ensure your profile is complete.');
    }
}

function renderRoadmapPhase(skills, containerId) {
    const container = document.getElementById(containerId);
    
    container.innerHTML = skills.length > 0 
        ? skills.map((skill, index) => `
            <div class="flex items-center justify-between p-4 bg-white/20 rounded-lg hover:bg-white/30 transition">
                <div class="flex items-center gap-4">
                    <div class="text-2xl font-bold text-white/50">${index + 1}</div>
                    <div>
                        <div class="font-semibold text-white">${skill.skill || skill}</div>
                        ${skill.difficulty ? `<div class="text-sm text-purple-100">Difficulty: ${skill.difficulty}</div>` : ''}
                    </div>
                </div>
                <div class="text-right">
                    ${skill.value_score ? `<div class="text-sm font-semibold text-white">Value: ${Math.round(skill.value_score)}/100</div>` : ''}
                    ${skill.growth_rate ? `<div class="text-xs text-purple-100">+${skill.growth_rate.toFixed(1)}% growth</div>` : ''}
                </div>
            </div>
        `).join('')
        : '<p class="text-purple-100 text-center py-4">No skills recommended for this phase</p>';
}
</script>
@endpush
