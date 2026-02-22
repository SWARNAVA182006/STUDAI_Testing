@extends('layouts.app')

@section('title', 'Salary Intelligence')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-green-50 to-blue-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Salary Intelligence</h1>
            <p class="text-lg text-gray-600">Data-driven insights to maximize your compensation</p>
        </div>

        <!-- Your Salary Position -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-green-100">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Your Salary Position</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Percentile Chart -->
                <div>
                    <div class="relative h-64">
                        <canvas id="salaryPercentileChart"></canvas>
                    </div>
                </div>

                <!-- Stats -->
                <div class="space-y-6">
                    <div>
                        <div class="text-sm text-gray-600 mb-1">Your Current Salary</div>
                        <div class="text-3xl font-bold text-gray-900" id="current-salary">--</div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-600 mb-1">Market Percentile</div>
                        <div class="flex items-center gap-3">
                            <div class="text-3xl font-bold text-green-600" id="salary-percentile">--</div>
                            <div class="px-3 py-1 rounded-full text-sm font-semibold" id="salary-status-badge">
                                Loading...
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-600 mb-1">Market Median</div>
                        <div class="text-2xl font-bold text-gray-700" id="market-median">--</div>
                        <div class="text-sm mt-1" id="diff-from-median">--</div>
                    </div>

                    <div class="pt-4 border-t border-gray-200">
                        <div class="text-sm text-gray-600 mb-2">Market Salary Range</div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">25th Percentile</span>
                                <span class="font-semibold text-gray-900" id="p25">--</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">75th Percentile</span>
                                <span class="font-semibold text-gray-900" id="p75">--</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">90th Percentile</span>
                                <span class="font-semibold text-gray-900" id="p90">--</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Salary Trends -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-blue-100">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Salary Trends</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="text-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl">
                    <div class="text-sm text-gray-600 mb-2">Month-over-Month</div>
                    <div class="text-3xl font-bold mb-1" id="mom-change">--</div>
                    <div class="text-xs text-gray-600">change in avg salary</div>
                </div>

                <div class="text-center p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-xl">
                    <div class="text-sm text-gray-600 mb-2">Year-over-Year</div>
                    <div class="text-3xl font-bold mb-1" id="yoy-change">--</div>
                    <div class="text-xs text-gray-600">annual growth</div>
                </div>

                <div class="text-center p-6 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl">
                    <div class="text-sm text-gray-600 mb-2">Trend Direction</div>
                    <div class="text-2xl font-bold mb-1" id="trend-direction">--</div>
                    <div class="text-xs text-gray-600">market momentum</div>
                </div>
            </div>

            <div class="mb-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Salary Movement Chart</h3>
                <canvas id="salaryTrendChart" height="300"></canvas>
            </div>
        </div>

        <!-- Salary Predictions -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-purple-100">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">📈 Salary Predictions</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-6 bg-purple-50 rounded-xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-sm font-semibold text-gray-700">6-Month Forecast</div>
                        <div class="px-3 py-1 rounded-full text-xs font-semibold" id="confidence-6m-badge">
                            --
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-purple-600 mb-2" id="predicted-6m">--</div>
                    <div class="text-sm text-gray-600">expected change</div>
                </div>

                <div class="p-6 bg-pink-50 rounded-xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-sm font-semibold text-gray-700">12-Month Forecast</div>
                        <div class="px-3 py-1 rounded-full text-xs font-semibold" id="confidence-12m-badge">
                            --
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-pink-600 mb-2" id="predicted-12m">--</div>
                    <div class="text-sm text-gray-600">expected change</div>
                </div>
            </div>
        </div>

        <!-- City Comparison -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-green-100">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">💰 Salary by City</h2>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Role</label>
                <input type="text" id="role-input" placeholder="e.g., Software Engineer" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Cities (hold Ctrl/Cmd for multiple)</label>
                <select multiple id="city-select" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                    size="5">
                    <option value="Bangalore">Bangalore</option>
                    <option value="Mumbai">Mumbai</option>
                    <option value="Delhi">Delhi</option>
                    <option value="Hyderabad">Hyderabad</option>
                    <option value="Pune">Pune</option>
                    <option value="Chennai">Chennai</option>
                    <option value="Kolkata">Kolkata</option>
                    <option value="Gurgaon">Gurgaon</option>
                </select>
            </div>

            <button onclick="compareCities()" 
                class="w-full bg-gradient-to-r from-green-500 to-blue-500 text-white font-semibold py-3 px-6 rounded-lg hover:shadow-lg transition">
                Compare Salaries
            </button>

            <div class="mt-8" id="city-comparison-results" style="display: none;">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Comparison Results</h3>
                <canvas id="cityComparisonChart" height="300"></canvas>
                
                <div class="mt-6 space-y-3" id="city-comparison-list">
                    <!-- City comparison items will be inserted here -->
                </div>
            </div>
        </div>

        <!-- Salary Negotiation Tool -->
        <div class="bg-gradient-to-r from-green-500 to-blue-500 rounded-2xl shadow-lg p-8 text-white">
            <h2 class="text-3xl font-bold mb-6">💼 Salary Negotiation Insights</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-green-100 mb-2">Offered Salary (₹)</label>
                    <input type="number" id="offered-salary" placeholder="1200000" 
                        class="w-full px-4 py-3 bg-white/20 text-white placeholder-white/60 border border-white/30 rounded-lg focus:ring-2 focus:ring-white focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-green-100 mb-2">Role Title</label>
                    <input type="text" id="negotiation-role" placeholder="Senior Software Engineer" 
                        class="w-full px-4 py-3 bg-white/20 text-white placeholder-white/60 border border-white/30 rounded-lg focus:ring-2 focus:ring-white focus:border-transparent">
                </div>
            </div>

            <button onclick="getNegotiationInsights()" 
                class="w-full bg-white text-green-600 font-semibold py-3 px-6 rounded-lg hover:bg-green-50 transition">
                Get Negotiation Strategy
            </button>

            <div class="mt-8" id="negotiation-results" style="display: none;">
                <div class="bg-white/20 rounded-xl p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <div class="text-sm text-green-100 mb-1">Offer Percentile</div>
                            <div class="text-3xl font-bold" id="offer-percentile">--</div>
                        </div>
                        <div>
                            <div class="text-sm text-green-100 mb-1">Recommendation</div>
                            <div class="text-2xl font-bold" id="negotiation-recommendation">--</div>
                        </div>
                        <div>
                            <div class="text-sm text-green-100 mb-1">Target Salary</div>
                            <div class="text-3xl font-bold" id="target-salary">--</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white/20 rounded-xl p-6 mb-6">
                    <h3 class="text-xl font-bold mb-4">Negotiation Range</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-green-100">Minimum Acceptable</span>
                            <span class="font-bold" id="min-acceptable">--</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-green-100">Ideal Target</span>
                            <span class="font-bold" id="ideal-target">--</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-green-100">Stretch Goal</span>
                            <span class="font-bold" id="stretch-goal">--</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white/20 rounded-xl p-6">
                    <h3 class="text-xl font-bold mb-4">💪 Talking Points</h3>
                    <div class="space-y-3" id="talking-points">
                        <!-- Talking points will be inserted here -->
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
    loadSalaryInsights();
});

async function loadSalaryInsights() {
    try {
        const response = await fetch('/api/market/salary-insights', {
            headers: {
                'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content,
                'Accept': 'application/json',
            }
        });
        
        if (!response.ok) throw new Error('Failed to fetch salary insights');
        
        const result = await response.json();
        const data = result.data;
        
        // Update user percentile
        updateUserPercentile(data.user_percentile || {});
        
        // Update trends
        updateSalaryTrends(data.trends || {});
        
        // Render charts
        renderSalaryPercentileChart(data.user_percentile || {});
        renderSalaryTrendChart(data.trends?.historical || []);
        
    } catch (error) {
        console.error('Error loading salary insights:', error);
    }
}

function updateUserPercentile(percentile) {
    document.getElementById('current-salary').textContent = percentile.current_salary 
        ? '₹' + percentile.current_salary.toLocaleString() 
        : 'Not provided';
    
    const pct = Math.round(percentile.percentile || 0);
    document.getElementById('salary-percentile').textContent = pct + 'th';
    
    const badge = document.getElementById('salary-status-badge');
    const status = percentile.status || 'fair';
    badge.textContent = status.replace('_', ' ').toUpperCase();
    badge.className = 'px-3 py-1 rounded-full text-sm font-semibold ' + 
        (status === 'excellent' ? 'bg-green-100 text-green-700' :
         status === 'good' ? 'bg-blue-100 text-blue-700' :
         status === 'fair' ? 'bg-yellow-100 text-yellow-700' :
         'bg-red-100 text-red-700');
    
    document.getElementById('market-median').textContent = percentile.market_median 
        ? '₹' + percentile.market_median.toLocaleString() 
        : '--';
    
    const diffEl = document.getElementById('diff-from-median');
    const diff = percentile.diff_from_median || 0;
    diffEl.textContent = (diff >= 0 ? '+' : '') + diff.toFixed(1) + '% vs median';
    diffEl.className = 'text-sm ' + (diff >= 0 ? 'text-green-600' : 'text-red-600');
    
    document.getElementById('p25').textContent = percentile.percentile_25 
        ? '₹' + percentile.percentile_25.toLocaleString() 
        : '--';
    document.getElementById('p75').textContent = percentile.percentile_75 
        ? '₹' + percentile.percentile_75.toLocaleString() 
        : '--';
    document.getElementById('p90').textContent = percentile.percentile_90 
        ? '₹' + percentile.percentile_90.toLocaleString() 
        : '--';
}

function updateSalaryTrends(trends) {
    const momEl = document.getElementById('mom-change');
    const mom = trends.trends?.month_over_month_change || 0;
    momEl.textContent = (mom >= 0 ? '+' : '') + mom.toFixed(1) + '%';
    momEl.className = 'text-3xl font-bold ' + (mom >= 0 ? 'text-green-600' : 'text-red-600');
    
    const yoyEl = document.getElementById('yoy-change');
    const yoy = trends.trends?.year_over_year_change || 0;
    yoyEl.textContent = (yoy >= 0 ? '+' : '') + yoy.toFixed(1) + '%';
    yoyEl.className = 'text-3xl font-bold ' + (yoy >= 0 ? 'text-green-600' : 'text-red-600');
    
    const direction = trends.trends?.direction || 'stable';
    const directionEl = document.getElementById('trend-direction');
    directionEl.textContent = direction === 'rising' ? '📈 Rising' : 
                              direction === 'falling' ? '📉 Falling' : '➡️ Stable';
    directionEl.className = 'text-2xl font-bold ' + 
        (direction === 'rising' ? 'text-green-600' :
         direction === 'falling' ? 'text-red-600' : 'text-gray-600');
    
    // Update predictions
    const predictions = trends.predictions || {};
    document.getElementById('predicted-6m').textContent = (predictions.predicted_6m >= 0 ? '+' : '') + 
        (predictions.predicted_6m || 0).toFixed(1) + '%';
    document.getElementById('predicted-12m').textContent = (predictions.predicted_12m >= 0 ? '+' : '') + 
        (predictions.predicted_12m || 0).toFixed(1) + '%';
    
    const confidence = predictions.confidence || 'low';
    document.getElementById('confidence-6m-badge').textContent = confidence.toUpperCase();
    document.getElementById('confidence-6m-badge').className = 'px-3 py-1 rounded-full text-xs font-semibold ' +
        (confidence === 'high' ? 'bg-green-100 text-green-700' :
         confidence === 'medium' ? 'bg-yellow-100 text-yellow-700' :
         'bg-gray-100 text-gray-700');
    document.getElementById('confidence-12m-badge').textContent = confidence.toUpperCase();
    document.getElementById('confidence-12m-badge').className = document.getElementById('confidence-6m-badge').className;
}

let salaryPercentileChart = null;
function renderSalaryPercentileChart(percentile) {
    const ctx = document.getElementById('salaryPercentileChart');
    if (!ctx) return;
    
    if (salaryPercentileChart) salaryPercentileChart.destroy();
    
    const pct = percentile.percentile || 0;
    
    salaryPercentileChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['You', '25th', 'Median', '75th', '90th'],
            datasets: [{
                label: 'Salary (₹)',
                data: [
                    percentile.current_salary || 0,
                    percentile.percentile_25 || 0,
                    percentile.market_median || 0,
                    percentile.percentile_75 || 0,
                    percentile.percentile_90 || 0
                ],
                backgroundColor: [
                    pct >= 75 ? 'rgba(34, 197, 94, 0.8)' : 
                    pct >= 50 ? 'rgba(59, 130, 246, 0.8)' :
                    pct >= 25 ? 'rgba(251, 146, 60, 0.8)' :
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(156, 163, 175, 0.5)',
                    'rgba(156, 163, 175, 0.7)',
                    'rgba(156, 163, 175, 0.5)',
                    'rgba(156, 163, 175, 0.3)'
                ],
                borderColor: [
                    pct >= 75 ? 'rgb(34, 197, 94)' : 
                    pct >= 50 ? 'rgb(59, 130, 246)' :
                    pct >= 25 ? 'rgb(251, 146, 60)' :
                    'rgb(239, 68, 68)',
                    'rgb(156, 163, 175)',
                    'rgb(156, 163, 175)',
                    'rgb(156, 163, 175)',
                    'rgb(156, 163, 175)'
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
                    ticks: {
                        callback: function(value) {
                            return '₹' + (value / 100000).toFixed(1) + 'L';
                        }
                    }
                }
            }
        }
    });
}

let salaryTrendChart = null;
function renderSalaryTrendChart(historical) {
    const ctx = document.getElementById('salaryTrendChart');
    if (!ctx) return;
    
    if (salaryTrendChart) salaryTrendChart.destroy();
    
    salaryTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: historical.map(h => h.month || h.date),
            datasets: [{
                label: 'Average Salary',
                data: historical.map(h => h.avg_salary || 0),
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
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
                y: {
                    beginAtZero: false,
                    ticks: {
                        callback: function(value) {
                            return '₹' + (value / 100000).toFixed(1) + 'L';
                        }
                    }
                }
            }
        }
    });
}

let cityComparisonChart = null;
async function compareCities() {
    const role = document.getElementById('role-input').value;
    const citySelect = document.getElementById('city-select');
    const cities = Array.from(citySelect.selectedOptions).map(opt => opt.value);
    
    if (!role || cities.length === 0) {
        alert('Please enter a role and select at least one city');
        return;
    }
    
    try {
        const response = await fetch(`/api/market/salary-insights?role=${encodeURIComponent(role)}&cities=${cities.join(',')}`, {
            headers: {
                'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content,
                'Accept': 'application/json',
            }
        });
        
        if (!response.ok) throw new Error('Failed to fetch city comparison');
        
        const result = await response.json();
        const comparisons = result.data.city_comparisons || {};
        
        document.getElementById('city-comparison-results').style.display = 'block';
        
        // Render chart
        const ctx = document.getElementById('cityComparisonChart');
        if (cityComparisonChart) cityComparisonChart.destroy();
        
        cityComparisonChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Object.keys(comparisons),
                datasets: [{
                    label: 'Average Salary',
                    data: Object.values(comparisons).map(c => c.average || 0),
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgb(59, 130, 246)',
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
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + (value / 100000).toFixed(1) + 'L';
                            }
                        }
                    }
                }
            }
        });
        
        // Render list
        const listHtml = Object.entries(comparisons).map(([city, data]) => `
            <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                <div>
                    <div class="font-semibold text-gray-900">${city}</div>
                    <div class="text-sm text-gray-600">${data.sample_size || 0} jobs analyzed</div>
                </div>
                <div class="text-right">
                    <div class="text-lg font-bold text-gray-900">₹${(data.average || 0).toLocaleString()}</div>
                    <div class="text-sm ${data.yoy_change >= 0 ? 'text-green-600' : 'text-red-600'}">
                        ${data.yoy_change >= 0 ? '+' : ''}${(data.yoy_change || 0).toFixed(1)}% YoY
                    </div>
                </div>
            </div>
        `).join('');
        
        document.getElementById('city-comparison-list').innerHTML = listHtml;
        
    } catch (error) {
        console.error('Error comparing cities:', error);
        alert('Failed to compare cities. Please try again.');
    }
}

async function getNegotiationInsights() {
    const offeredSalary = parseFloat(document.getElementById('offered-salary').value);
    const role = document.getElementById('negotiation-role').value;
    
    if (!offeredSalary || !role) {
        alert('Please enter both offered salary and role');
        return;
    }
    
    try {
        const response = await fetch('/api/market/negotiation-insights', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                offered_salary: offeredSalary,
                role: role
            })
        });
        
        if (!response.ok) throw new Error('Failed to fetch negotiation insights');
        
        const result = await response.json();
        const data = result.data;
        
        document.getElementById('negotiation-results').style.display = 'block';
        
        // Update stats
        document.getElementById('offer-percentile').textContent = Math.round(data.offer_percentile || 0) + 'th';
        document.getElementById('negotiation-recommendation').textContent = data.recommendation?.toUpperCase() || '--';
        document.getElementById('target-salary').textContent = '₹' + (data.target_salary || 0).toLocaleString();
        
        document.getElementById('min-acceptable').textContent = '₹' + (data.min_acceptable || 0).toLocaleString();
        document.getElementById('ideal-target').textContent = '₹' + (data.ideal_target || 0).toLocaleString();
        document.getElementById('stretch-goal').textContent = '₹' + (data.stretch_goal || 0).toLocaleString();
        
        // Render talking points
        const talkingPoints = data.talking_points || [];
        document.getElementById('talking-points').innerHTML = talkingPoints.map(point => `
            <div class="flex items-start gap-3 p-4 bg-white/20 rounded-lg">
                <div class="text-2xl">${point.strength === 'high' ? '💪' : '👍'}</div>
                <div class="flex-1 text-sm">${point.point}</div>
            </div>
        `).join('') || '<p class="text-center text-green-100">No specific talking points available</p>';
        
    } catch (error) {
        console.error('Error getting negotiation insights:', error);
        alert('Failed to get negotiation insights. Please try again.');
    }
}
</script>
@endpush
