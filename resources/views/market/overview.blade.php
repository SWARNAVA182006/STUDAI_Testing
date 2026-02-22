@extends('layouts.app')

@section('title', 'Market Intelligence Overview')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-purple-50 to-pink-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Market Intelligence</h1>
            <p class="text-lg text-gray-600">Real-time insights from millions of job postings</p>
        </div>

        <!-- Market Health Score -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-purple-100">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Market Health</h2>
                <div class="flex items-center gap-2">
                    <div class="h-3 w-3 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-sm text-gray-600">Live Data</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-4xl font-bold text-purple-600 mb-2" id="market-health-score">--</div>
                    <div class="text-sm text-gray-600">Market Health Score</div>
                    <div class="text-xs text-gray-500 mt-1" id="market-health-status">Loading...</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-blue-600 mb-2" id="total-jobs">--</div>
                    <div class="text-sm text-gray-600">Active Job Postings</div>
                    <div class="text-xs text-green-500 mt-1" id="jobs-change">--</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-green-600 mb-2" id="avg-salary">--</div>
                    <div class="text-sm text-gray-600">Average Salary</div>
                    <div class="text-xs text-green-500 mt-1" id="salary-change">--</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-pink-600 mb-2" id="demand-score">--</div>
                    <div class="text-sm text-gray-600">Demand Score</div>
                    <div class="text-xs text-gray-500 mt-1">0-100 scale</div>
                </div>
            </div>
        </div>

        <!-- Trending Roles -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-8 border border-purple-100">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">🔥 Trending Roles</h2>
                <div class="space-y-4" id="trending-roles">
                    <!-- Loading skeleton -->
                    <div class="animate-pulse space-y-4">
                        <div class="h-16 bg-gray-200 rounded-lg"></div>
                        <div class="h-16 bg-gray-200 rounded-lg"></div>
                        <div class="h-16 bg-gray-200 rounded-lg"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-8 border border-purple-100">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">📍 Top Locations</h2>
                <div class="space-y-4" id="top-locations">
                    <!-- Loading skeleton -->
                    <div class="animate-pulse space-y-4">
                        <div class="h-16 bg-gray-200 rounded-lg"></div>
                        <div class="h-16 bg-gray-200 rounded-lg"></div>
                        <div class="h-16 bg-gray-200 rounded-lg"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Emerging Skills -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-purple-100">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">🌱 Emerging Skills</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4" id="emerging-skills">
                <!-- Loading skeleton -->
                <div class="animate-pulse space-y-2">
                    <div class="h-12 bg-gray-200 rounded-lg"></div>
                    <div class="h-12 bg-gray-200 rounded-lg"></div>
                    <div class="h-12 bg-gray-200 rounded-lg"></div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-8 border border-purple-100">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Job Posting Trends</h2>
                <canvas id="jobTrendsChart" height="300"></canvas>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-8 border border-purple-100">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Salary Distribution</h2>
                <canvas id="salaryDistributionChart" height="300"></canvas>
            </div>
        </div>

        <!-- Remote Work Stats -->
        <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl shadow-lg p-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold mb-2">Remote Work Trend</h2>
                    <p class="text-purple-100">Percentage of remote-friendly positions</p>
                </div>
                <div class="text-center">
                    <div class="text-6xl font-bold" id="remote-percentage">--</div>
                    <div class="text-sm text-purple-100 mt-2">of all jobs</div>
                </div>
            </div>
            <div class="mt-6 bg-white/20 rounded-full h-4 overflow-hidden">
                <div class="bg-white h-full transition-all duration-500" id="remote-progress" style="width: 0%"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadMarketOverview();
    
    // Refresh every 5 minutes
    setInterval(loadMarketOverview, 300000);
});

async function loadMarketOverview() {
    try {
        const response = await fetch('/api/market/overview', {
            headers: {
                'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content,
                'Accept': 'application/json',
            }
        });
        
        if (!response.ok) throw new Error('Failed to fetch market overview');
        
        const result = await response.json();
        const data = result.data;
        
        // Update market health
        document.getElementById('market-health-score').textContent = data.market_health_score || '--';
        document.getElementById('market-health-status').textContent = data.market_health || 'stable';
        document.getElementById('total-jobs').textContent = (data.total_jobs || 0).toLocaleString();
        document.getElementById('avg-salary').textContent = '₹' + (data.avg_salary || 0).toLocaleString();
        document.getElementById('demand-score').textContent = Math.round(data.demand_score || 0);
        
        // Update change indicators
        document.getElementById('jobs-change').textContent = '+' + (data.job_growth || 0) + '% this week';
        document.getElementById('salary-change').textContent = '+' + (data.salary_growth || 0) + '% YoY';
        
        // Update remote percentage
        const remotePercentage = Math.round(data.remote_percentage || 0);
        document.getElementById('remote-percentage').textContent = remotePercentage + '%';
        document.getElementById('remote-progress').style.width = remotePercentage + '%';
        
        // Render trending roles
        renderTrendingRoles(data.top_roles || []);
        
        // Render top locations
        renderTopLocations(data.top_locations || []);
        
        // Render emerging skills
        renderEmergingSkills(data.emerging_skills || []);
        
        // Render charts
        renderJobTrendsChart(data.job_trends || []);
        renderSalaryDistributionChart(data.salary_distribution || {});
        
    } catch (error) {
        console.error('Error loading market overview:', error);
    }
}

function renderTrendingRoles(roles) {
    const container = document.getElementById('trending-roles');
    container.innerHTML = roles.slice(0, 5).map((role, index) => `
        <div class="flex items-center justify-between p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
            <div class="flex items-center gap-3">
                <div class="text-2xl font-bold text-purple-600">${index + 1}</div>
                <div>
                    <div class="font-semibold text-gray-900">${role.title}</div>
                    <div class="text-sm text-gray-600">${role.job_count} openings</div>
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm font-semibold text-green-600">+${role.growth || 0}%</div>
                <div class="text-xs text-gray-500">growth</div>
            </div>
        </div>
    `).join('');
}

function renderTopLocations(locations) {
    const container = document.getElementById('top-locations');
    container.innerHTML = locations.slice(0, 5).map((loc, index) => `
        <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
            <div class="flex items-center gap-3">
                <div class="text-2xl font-bold text-blue-600">${index + 1}</div>
                <div>
                    <div class="font-semibold text-gray-900">${loc.location}</div>
                    <div class="text-sm text-gray-600">${loc.job_count} openings</div>
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm font-semibold text-gray-900">₹${(loc.avg_salary || 0).toLocaleString()}</div>
                <div class="text-xs text-gray-500">avg salary</div>
            </div>
        </div>
    `).join('');
}

function renderEmergingSkills(skills) {
    const container = document.getElementById('emerging-skills');
    container.innerHTML = skills.slice(0, 12).map(skill => `
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 text-center hover:shadow-md transition">
            <div class="text-sm font-semibold text-gray-900 mb-1">${skill.name || skill}</div>
            <div class="text-xs text-green-600 font-semibold">+${skill.growth || 0}% 🌱</div>
        </div>
    `).join('');
}

let jobTrendsChart = null;
function renderJobTrendsChart(trends) {
    const ctx = document.getElementById('jobTrendsChart');
    if (!ctx) return;
    
    if (jobTrendsChart) jobTrendsChart.destroy();
    
    jobTrendsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: trends.map(t => t.date || t.month),
            datasets: [{
                label: 'Job Postings',
                data: trends.map(t => t.count || 0),
                borderColor: 'rgb(147, 51, 234)',
                backgroundColor: 'rgba(147, 51, 234, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

let salaryDistributionChart = null;
function renderSalaryDistributionChart(distribution) {
    const ctx = document.getElementById('salaryDistributionChart');
    if (!ctx) return;
    
    if (salaryDistributionChart) salaryDistributionChart.destroy();
    
    salaryDistributionChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['0-5L', '5-10L', '10-15L', '15-20L', '20-30L', '30L+'],
            datasets: [{
                label: 'Number of Jobs',
                data: [
                    distribution.range_0_5 || 0,
                    distribution.range_5_10 || 0,
                    distribution.range_10_15 || 0,
                    distribution.range_15_20 || 0,
                    distribution.range_20_30 || 0,
                    distribution.range_30_plus || 0
                ],
                backgroundColor: 'rgba(236, 72, 153, 0.8)',
                borderColor: 'rgb(236, 72, 153)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}
</script>
@endpush
