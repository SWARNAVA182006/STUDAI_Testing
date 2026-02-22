@extends('layouts.dashboard')

@section('title', 'Market Intelligence')
@section('page-title', 'Market Intelligence')
@section('page-description', 'Real-time market insights & trends')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@section('content')
<div class="space-y-6">
    {{-- Quick Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-studai.stat-card 
            title="Jobs Analyzed" 
            :value="'0'" 
            id="total-jobs-stat"
            change="Real-time data"
            icon="heroicon-o-chart-bar"
            iconColor="blue"
        />
        <x-studai.stat-card 
            title="Avg. Salary" 
            :value="'--'" 
            id="avg-salary-stat"
            change="+5% vs last month"
            icon="heroicon-o-currency-dollar"
            iconColor="green"
        />
        <x-studai.stat-card 
            title="Top Skill" 
            :value="'--'" 
            id="top-skill-stat"
            change="Most in-demand"
            icon="heroicon-o-bolt"
            iconColor="purple"
        />
        <x-studai.stat-card 
            title="Market Trend" 
            :value="'--'" 
            id="market-trend-stat"
            change="Growth indicator"
            icon="heroicon-o-arrow-trending-up"
            iconColor="yellow"
        />
    </div>

    {{-- Quick Navigation --}}
    <x-studai.card>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('analytics.heatmap') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-studai-blue-50 dark:bg-studai-blue-900/20 text-studai-blue-700 dark:text-studai-blue-300 rounded-xl hover:bg-studai-blue-100 dark:hover:bg-studai-blue-900/40 transition-colors text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                Job Heatmap
            </a>
            <a href="{{ route('analytics.salary-benchmark') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 rounded-xl hover:bg-green-100 dark:hover:bg-green-900/40 transition-colors text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Salary Benchmark
            </a>
            <a href="{{ route('analytics.skills-forecast') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 rounded-xl hover:bg-purple-100 dark:hover:bg-purple-900/40 transition-colors text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                Skills Forecast
            </a>
            <a href="{{ route('analytics.career-path') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-pink-50 dark:bg-pink-900/20 text-pink-700 dark:text-pink-300 rounded-xl hover:bg-pink-100 dark:hover:bg-pink-900/40 transition-colors text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                Career Path
            </a>
            <a href="{{ route('analytics.competitor-salary') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 rounded-xl hover:bg-amber-100 dark:hover:bg-amber-900/40 transition-colors text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Competitor Salaries
            </a>
        </div>
    </x-studai.card>

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Job Market Heatmap --}}
        <x-studai.card>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Job Market Hotspots</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Geographic distribution</p>
                </div>
                <a href="{{ route('analytics.heatmap') }}" class="text-sm font-medium text-studai-blue-600 hover:text-studai-blue-700">
                    Full Map →
                </a>
            </div>
            <div id="mini-map" class="h-64 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700"></div>
            <div class="grid grid-cols-3 gap-4 mt-4">
                <div class="text-center">
                    <p class="font-semibold text-gray-900 dark:text-white" id="top-location-1">--</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">#1 Location</p>
                </div>
                <div class="text-center">
                    <p class="font-semibold text-gray-900 dark:text-white" id="top-location-2">--</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">#2 Location</p>
                </div>
                <div class="text-center">
                    <p class="font-semibold text-gray-900 dark:text-white" id="top-location-3">--</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">#3 Location</p>
                </div>
            </div>
        </x-studai.card>

        {{-- Skills Demand --}}
        <x-studai.card>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Skills Demand</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Trending technologies</p>
                </div>
                <a href="{{ route('analytics.skills-forecast') }}" class="text-sm font-medium text-studai-blue-600 hover:text-studai-blue-700">
                    View All →
                </a>
            </div>
            <div class="space-y-3" id="skills-forecast-list">
                @for($i = 0; $i < 5; $i++)
                    <div class="animate-pulse flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-xl">
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded flex-1"></div>
                        <div class="h-4 w-16 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    </div>
                @endfor
            </div>
        </x-studai.card>
    </div>

    {{-- Salary Trends Chart --}}
    <x-studai.card>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Salary Trends</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">12-month analysis</p>
            </div>
            <select id="salary-industry-filter" class="text-sm border-gray-200 dark:border-gray-700 dark:bg-gray-800 rounded-xl px-4 py-2 focus:ring-studai-blue-500 focus:border-studai-blue-500">
                <option value="">All Industries</option>
                <option value="technology">Technology</option>
                <option value="healthcare">Healthcare</option>
                <option value="finance">Finance</option>
                <option value="marketing">Marketing</option>
            </select>
        </div>
        <canvas id="salaryTrendsChart" height="100"></canvas>
    </x-studai.card>

    {{-- Career Path & Insights --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Career Path Visualization --}}
        <div class="lg:col-span-2">
            <x-studai.card>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Career Path Explorer</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Potential career progressions</p>
                    </div>
                    <a href="{{ route('analytics.career-path') }}" class="text-sm font-medium text-studai-blue-600 hover:text-studai-blue-700">
                        Interactive View →
                    </a>
                </div>
                <div class="h-64">
                    <canvas id="careerPathChart"></canvas>
                </div>
            </x-studai.card>
        </div>

        {{-- AI Insights --}}
        <x-studai.card>
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-gradient-to-br from-studai-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">AI Insights</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Market analysis</p>
                </div>
            </div>
            <div class="space-y-3">
                <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                    <div class="flex items-center gap-2 mb-1">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        <span class="text-sm font-medium text-green-700 dark:text-green-300">Growing Demand</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">AI/ML roles up 32% this quarter</p>
                </div>
                <div class="p-3 bg-studai-blue-50 dark:bg-studai-blue-900/20 rounded-xl">
                    <div class="flex items-center gap-2 mb-1">
                        <svg class="w-4 h-4 text-studai-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm font-medium text-studai-blue-700 dark:text-studai-blue-300">Salary Insight</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Remote roles pay 12% more on average</p>
                </div>
                <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                    <div class="flex items-center gap-2 mb-1">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        <span class="text-sm font-medium text-purple-700 dark:text-purple-300">Skill Tip</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Add Kubernetes to boost match rate by 24%</p>
                </div>
            </div>
        </x-studai.card>
    </div>

    @if(auth()->user()->account_type === 'employer')
    {{-- Employer Analytics Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Application Funnel --}}
        <x-studai.card>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Application Funnel</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Conversion rates</p>
                </div>
                <a href="{{ route('analytics.application-funnel') }}" class="text-sm font-medium text-studai-blue-600 hover:text-studai-blue-700">
                    Details →
                </a>
            </div>
            <div id="funnel-preview" class="space-y-3">
                @foreach(['Views', 'Applications', 'Interviews', 'Offers', 'Hires'] as $stage)
                    <div class="flex items-center gap-3">
                        <span class="w-24 text-sm text-gray-600 dark:text-gray-400">{{ $stage }}</span>
                        <div class="flex-1 h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-studai-blue-500 to-purple-600 rounded-full animate-pulse" style="width: 0%"></div>
                        </div>
                        <span class="w-12 text-sm font-medium text-gray-400">--</span>
                    </div>
                @endforeach
            </div>
        </x-studai.card>

        {{-- Source Attribution --}}
        <x-studai.card>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Top Hiring Sources</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Where candidates come from</p>
                </div>
                <a href="{{ route('analytics.source-attribution') }}" class="text-sm font-medium text-studai-blue-600 hover:text-studai-blue-700">
                    All Sources →
                </a>
            </div>
            <canvas id="sourceChart" height="200"></canvas>
        </x-studai.card>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart.js default styling for dark mode support
    Chart.defaults.color = document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280';
    Chart.defaults.borderColor = document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb';

    // Initialize mini map
    const miniMap = L.map('mini-map').setView([39.8283, -98.5795], 4);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(miniMap);

    // Load heatmap data
    fetch('{{ route("analytics.api.heatmap") }}')
        .then(res => res.json())
        .then(data => {
            // Add markers
            if (data.points) {
                data.points.forEach(point => {
                    if (point.lat && point.lng) {
                        const size = Math.min(25, Math.max(8, point.jobCount / 15));
                        L.circleMarker([point.lat, point.lng], {
                            radius: size,
                            fillColor: '#1A73E8',
                            color: '#fff',
                            weight: 2,
                            opacity: 1,
                            fillOpacity: 0.7
                        }).addTo(miniMap)
                        .bindPopup(`<b>${point.location}</b><br>${point.jobCount} jobs<br>Avg: $${Math.round(point.avgSalary).toLocaleString()}`);
                    }
                });
            }
            
            const topLocs = data.top_locations || [];
            if (topLocs[0]) document.getElementById('top-location-1').textContent = topLocs[0].location;
            if (topLocs[1]) document.getElementById('top-location-2').textContent = topLocs[1].location;
            if (topLocs[2]) document.getElementById('top-location-3').textContent = topLocs[2].location;
        }).catch(() => {});

    // Load skills forecast
    fetch('{{ route("analytics.api.skills-forecast") }}')
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('skills-forecast-list');
            const skills = data.skills?.slice(0, 5) || [];
            
            if (skills.length === 0) {
                container.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm py-4 text-center">No skill data available</p>';
                return;
            }
            
            container.innerHTML = skills.map(skill => {
                const trend = skill.trend || 'stable';
                const trendIcon = trend === 'rising' ? '↗' : (trend === 'falling' ? '↘' : '→');
                const trendColor = trend === 'rising' ? 'text-green-600' : (trend === 'falling' ? 'text-red-600' : 'text-gray-600');
                
                return `
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <span class="font-medium text-gray-900 dark:text-white">${skill.skill}</span>
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400">${skill.current_demand} jobs</span>
                            <span class="${trendColor} text-lg font-bold">${trendIcon}</span>
                        </div>
                    </div>
                `;
            }).join('');
        }).catch(() => {});

    // Salary Trends Chart
    fetch('{{ route("analytics.api.salary-trends") }}')
        .then(res => res.json())
        .then(data => {
            new Chart(document.getElementById('salaryTrendsChart'), {
                type: 'line',
                data: {
                    labels: data.labels || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Average Salary',
                        data: data.data || [85000, 87000, 86500, 88000, 89500, 91000, 90500, 92000, 93500, 95000, 96000, 98000],
                        borderColor: '#1A73E8',
                        backgroundColor: 'rgba(26, 115, 232, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 4,
                        pointBackgroundColor: '#1A73E8'
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
                            grid: { display: true, drawBorder: false },
                            ticks: { callback: value => '$' + (value/1000) + 'k' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }).catch(() => {});

    // Career Path Chart
    fetch('{{ route("analytics.api.career-path") }}')
        .then(res => res.json())
        .then(data => {
            const nodes = data.nodes || [
                { label: 'Junior', level: 1, salary: 65000 },
                { label: 'Mid', level: 2, salary: 95000 },
                { label: 'Senior', level: 3, salary: 130000 },
                { label: 'Lead', level: 4, salary: 165000 },
                { label: 'Principal', level: 5, salary: 200000 }
            ];
            
            new Chart(document.getElementById('careerPathChart'), {
                type: 'bubble',
                data: {
                    datasets: nodes.map((node, i) => ({
                        label: node.label,
                        data: [{ x: node.level, y: node.salary, r: 12 + (i * 2) }],
                        backgroundColor: `hsla(${220 + i * 25}, 70%, 55%, 0.8)`,
                        borderColor: `hsla(${220 + i * 25}, 70%, 45%, 1)`,
                        borderWidth: 2
                    }))
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true, position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: ctx => `${ctx.dataset.label}: $${ctx.parsed.y.toLocaleString()}`
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: { display: true, text: 'Career Level' },
                            min: 0, max: 6,
                            grid: { display: false }
                        },
                        y: {
                            title: { display: true, text: 'Salary' },
                            ticks: { callback: value => '$' + (value/1000) + 'k' }
                        }
                    }
                }
            });
        }).catch(() => {});

    @if(auth()->user()->account_type === 'employer')
    // Application Funnel
    fetch('{{ route("analytics.api.application-funnel") }}')
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('funnel-preview');
            const funnel = data.funnel || [
                { stage: 'Views', count: 1250 },
                { stage: 'Applications', count: 340 },
                { stage: 'Interviews', count: 45 },
                { stage: 'Offers', count: 12 },
                { stage: 'Hires', count: 8 }
            ];
            
            const maxCount = Math.max(...funnel.map(s => s.count)) || 1;
            container.innerHTML = funnel.map(stage => `
                <div class="flex items-center gap-3">
                    <span class="w-24 text-sm text-gray-600 dark:text-gray-400">${stage.stage}</span>
                    <div class="flex-1 h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-studai-blue-500 to-purple-600 rounded-full transition-all duration-500" 
                             style="width: ${(stage.count / maxCount) * 100}%"></div>
                    </div>
                    <span class="w-12 text-sm font-medium text-gray-700 dark:text-gray-300">${stage.count}</span>
                </div>
            `).join('');
        }).catch(() => {});

    // Source Attribution
    fetch('{{ route("analytics.api.source-attribution") }}')
        .then(res => res.json())
        .then(data => {
            const sources = data.sources || [
                { source: 'LinkedIn', applications: 145 },
                { source: 'Indeed', applications: 89 },
                { source: 'Direct', applications: 67 },
                { source: 'Referral', applications: 54 },
                { source: 'Other', applications: 25 }
            ];
            
            new Chart(document.getElementById('sourceChart'), {
                type: 'doughnut',
                data: {
                    labels: sources.map(s => s.source),
                    datasets: [{
                        data: sources.map(s => s.applications),
                        backgroundColor: ['#1A73E8', '#34A853', '#FBBC04', '#EA4335', '#9333EA'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right' }
                    },
                    cutout: '60%'
                }
            });
        }).catch(() => {});
    @endif
});
</script>
@endpush
