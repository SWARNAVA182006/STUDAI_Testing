@extends('layouts.app')

@section('title', 'Salary Benchmark Tool - Real-time Comparison')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-green-50 to-emerald-50 py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('analytics.dashboard') }}" class="text-green-600 hover:text-green-800 text-sm mb-2 inline-block">← Back to Analytics</a>
            <h1 class="text-3xl font-bold text-gray-900">💰 Real-time Salary Benchmark</h1>
            <p class="text-gray-600">Compare your salary against market rates instantly</p>
        </div>

        <!-- Search Form -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8 border border-green-100">
            <form id="salary-search-form" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Job Title *</label>
                        <input type="text" id="job-title" required
                               class="w-full border-gray-200 rounded-lg focus:ring-green-500 focus:border-green-500"
                               placeholder="e.g., Software Engineer">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                        <input type="text" id="location"
                               class="w-full border-gray-200 rounded-lg focus:ring-green-500 focus:border-green-500"
                               placeholder="e.g., San Francisco, CA">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Experience Level</label>
                        <select id="experience-level" class="w-full border-gray-200 rounded-lg">
                            <option value="">All Levels</option>
                            <option value="entry">Entry Level (0-2 years)</option>
                            <option value="mid">Mid Level (3-5 years)</option>
                            <option value="senior">Senior (6-10 years)</option>
                            <option value="lead">Lead (10+ years)</option>
                            <option value="executive">Executive</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button type="submit" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                        🔍 Get Benchmark
                    </button>
                    <div id="loading" class="hidden">
                        <svg class="animate-spin h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Container -->
        <div id="results-container" class="hidden">
            <!-- Summary Card -->
            <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-green-100">
                <h2 class="text-xl font-bold text-gray-900 mb-6" id="result-title">Salary Benchmark Results</h2>
                
                <!-- Salary Range Visualization -->
                <div class="mb-8">
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>25th Percentile</span>
                        <span>Median</span>
                        <span>75th Percentile</span>
                        <span>90th Percentile</span>
                    </div>
                    <div class="relative h-8 bg-gradient-to-r from-green-100 via-green-300 to-green-600 rounded-full">
                        <div id="salary-marker" class="absolute top-0 h-8 w-1 bg-purple-600 rounded hidden"></div>
                    </div>
                    <div class="flex justify-between text-sm font-medium text-gray-900 mt-2">
                        <span id="p25">--</span>
                        <span id="median">--</span>
                        <span id="p75">--</span>
                        <span id="p90">--</span>
                    </div>
                </div>

                <!-- Key Metrics -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-green-50 rounded-xl p-4 text-center">
                        <div class="text-2xl font-bold text-green-700" id="min-salary">--</div>
                        <div class="text-sm text-gray-600">Minimum</div>
                    </div>
                    <div class="bg-green-50 rounded-xl p-4 text-center">
                        <div class="text-2xl font-bold text-green-700" id="max-salary">--</div>
                        <div class="text-sm text-gray-600">Maximum</div>
                    </div>
                    <div class="bg-green-50 rounded-xl p-4 text-center">
                        <div class="text-2xl font-bold text-green-700" id="median-salary">--</div>
                        <div class="text-sm text-gray-600">Median</div>
                    </div>
                    <div class="bg-green-50 rounded-xl p-4 text-center">
                        <div class="text-2xl font-bold" id="sample-size">--</div>
                        <div class="text-sm text-gray-600">Sample Size</div>
                    </div>
                </div>

                <!-- YoY Change -->
                <div id="yoy-container" class="mt-6 p-4 bg-gray-50 rounded-xl hidden">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600">Year-over-Year Change:</span>
                        <span id="yoy-change" class="font-bold">--</span>
                    </div>
                </div>
            </div>

            <!-- Compare Your Salary -->
            <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-green-100">
                <h2 class="text-xl font-bold text-gray-900 mb-4">📊 Compare Your Salary</h2>
                <div class="flex items-end gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Your Current Salary</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                            <input type="number" id="your-salary" 
                                   class="w-full pl-8 border-gray-200 rounded-lg focus:ring-green-500 focus:border-green-500"
                                   placeholder="e.g., 85000">
                        </div>
                    </div>
                    <button type="button" id="compare-salary" class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                        Compare
                    </button>
                </div>

                <!-- Comparison Result -->
                <div id="comparison-result" class="mt-6 hidden">
                    <div class="p-6 rounded-xl" id="comparison-card">
                        <div class="flex items-center gap-4">
                            <div class="text-4xl" id="comparison-emoji">📊</div>
                            <div>
                                <div class="text-lg font-bold" id="comparison-text">--</div>
                                <div class="text-sm text-gray-600" id="comparison-detail">--</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Salary by Company Size -->
            <div class="bg-white rounded-2xl shadow-lg p-8 border border-green-100">
                <h2 class="text-xl font-bold text-gray-900 mb-6">🏢 Salary by Company Size</h2>
                <canvas id="companySizeChart" height="200"></canvas>
            </div>
        </div>

        <!-- No Results -->
        <div id="no-results" class="hidden bg-white rounded-2xl shadow-lg p-8 text-center border border-orange-100">
            <div class="text-6xl mb-4">🔍</div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">No Data Found</h2>
            <p class="text-gray-600" id="no-results-message">Try adjusting your search criteria</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentBenchmark = null;
    let companySizeChart = null;

    document.getElementById('salary-search-form').addEventListener('submit', function(e) {
        e.preventDefault();
        searchSalaryBenchmark();
    });

    document.getElementById('compare-salary').addEventListener('click', compareSalary);

    function searchSalaryBenchmark() {
        const jobTitle = document.getElementById('job-title').value;
        const location = document.getElementById('location').value;
        const experienceLevel = document.getElementById('experience-level').value;

        document.getElementById('loading').classList.remove('hidden');
        document.getElementById('results-container').classList.add('hidden');
        document.getElementById('no-results').classList.add('hidden');

        const params = new URLSearchParams({
            job_title: jobTitle,
            location: location,
            experience_level: experienceLevel
        });

        fetch(`{{ route('analytics.api.salary-benchmark') }}?${params}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('loading').classList.add('hidden');
                
                if (!data.found) {
                    document.getElementById('no-results').classList.remove('hidden');
                    document.getElementById('no-results-message').textContent = data.message || 'No salary data available for this criteria';
                    return;
                }

                currentBenchmark = data;
                displayBenchmarkResults(data);
                document.getElementById('results-container').classList.remove('hidden');
            })
            .catch(err => {
                document.getElementById('loading').classList.add('hidden');
                document.getElementById('no-results').classList.remove('hidden');
                document.getElementById('no-results-message').textContent = 'An error occurred. Please try again.';
            });
    }

    function displayBenchmarkResults(data) {
        // Title
        let title = data.job_title;
        if (data.location) title += ` in ${data.location}`;
        if (data.experience_level) title += ` (${data.experience_level})`;
        document.getElementById('result-title').textContent = title;

        // Percentiles
        document.getElementById('p25').textContent = formatSalary(data.percentile_25);
        document.getElementById('median').textContent = formatSalary(data.median_salary);
        document.getElementById('p75').textContent = formatSalary(data.percentile_75);
        document.getElementById('p90').textContent = formatSalary(data.percentile_90);

        // Key metrics
        document.getElementById('min-salary').textContent = formatSalary(data.min_salary);
        document.getElementById('max-salary').textContent = formatSalary(data.max_salary);
        document.getElementById('median-salary').textContent = formatSalary(data.median_salary);
        document.getElementById('sample-size').textContent = data.sample_size?.toLocaleString() || '--';

        // YoY change
        if (data.yoy_change) {
            document.getElementById('yoy-container').classList.remove('hidden');
            const yoyEl = document.getElementById('yoy-change');
            const change = parseFloat(data.yoy_change);
            yoyEl.textContent = (change >= 0 ? '+' : '') + change.toFixed(1) + '%';
            yoyEl.className = change >= 0 ? 'font-bold text-green-600' : 'font-bold text-red-600';
        } else {
            document.getElementById('yoy-container').classList.add('hidden');
        }

        // Company size chart
        loadCompanySizeChart(data.job_title);
    }

    function compareSalary() {
        const salary = parseFloat(document.getElementById('your-salary').value);
        if (!salary || !currentBenchmark) return;

        const resultContainer = document.getElementById('comparison-result');
        const card = document.getElementById('comparison-card');
        const emoji = document.getElementById('comparison-emoji');
        const text = document.getElementById('comparison-text');
        const detail = document.getElementById('comparison-detail');

        const median = currentBenchmark.median_salary;
        const p25 = currentBenchmark.percentile_25 || median * 0.8;
        const p75 = currentBenchmark.percentile_75 || median * 1.2;
        const p90 = currentBenchmark.percentile_90 || median * 1.4;

        let percentile, message, detailMsg, bgClass;

        if (salary < p25) {
            percentile = Math.round((salary / p25) * 25);
            message = "Below Market Rate";
            detailMsg = `Your salary is in the bottom 25%. Consider negotiating for ${formatSalary(median - salary)} more.`;
            emoji.textContent = "⚠️";
            bgClass = "bg-red-50";
        } else if (salary < median) {
            percentile = 25 + ((salary - p25) / (median - p25)) * 25;
            message = "Slightly Below Median";
            detailMsg = `You're between 25th-50th percentile. Room for ${formatSalary(median - salary)} increase.`;
            emoji.textContent = "📊";
            bgClass = "bg-yellow-50";
        } else if (salary < p75) {
            percentile = 50 + ((salary - median) / (p75 - median)) * 25;
            message = "Above Median - Great!";
            detailMsg = `You're between 50th-75th percentile. Well positioned in the market.`;
            emoji.textContent = "✅";
            bgClass = "bg-green-50";
        } else if (salary < p90) {
            percentile = 75 + ((salary - p75) / (p90 - p75)) * 15;
            message = "Excellent - Top Quartile!";
            detailMsg = `You're in the top 25% of earners for this role.`;
            emoji.textContent = "🌟";
            bgClass = "bg-green-100";
        } else {
            percentile = 90 + Math.min(10, ((salary - p90) / p90) * 10);
            message = "Outstanding - Top 10%!";
            detailMsg = `You're among the highest paid professionals in this role.`;
            emoji.textContent = "🏆";
            bgClass = "bg-purple-50";
        }

        text.textContent = `${message} (${Math.round(percentile)}th percentile)`;
        detail.textContent = detailMsg;
        card.className = `p-6 rounded-xl ${bgClass}`;
        resultContainer.classList.remove('hidden');
    }

    function loadCompanySizeChart(jobTitle) {
        fetch(`{{ route('analytics.api.competitor-salary') }}?job_title=${encodeURIComponent(jobTitle)}`)
            .then(res => res.json())
            .then(data => {
                const sizes = data.by_company_size || {
                    'Startup': currentBenchmark.median_salary * 0.85,
                    'Small': currentBenchmark.median_salary * 0.92,
                    'Medium': currentBenchmark.median_salary,
                    'Large': currentBenchmark.median_salary * 1.1,
                    'Enterprise': currentBenchmark.median_salary * 1.2
                };

                if (companySizeChart) companySizeChart.destroy();

                companySizeChart = new Chart(document.getElementById('companySizeChart'), {
                    type: 'bar',
                    data: {
                        labels: Object.keys(sizes),
                        datasets: [{
                            label: 'Median Salary',
                            data: Object.values(sizes),
                            backgroundColor: ['#10b981', '#34d399', '#6ee7b7', '#a7f3d0', '#d1fae5'],
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: value => '$' + (value / 1000) + 'k'
                                }
                            }
                        }
                    }
                });
            });
    }

    function formatSalary(value) {
        if (!value) return '--';
        return '$' + Math.round(value).toLocaleString();
    }
});
</script>
@endpush
