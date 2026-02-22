@extends('layouts.app')

@section('title', 'Job Market Heatmap - Interactive Analytics')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
<style>
    #heatmap-container { height: calc(100vh - 200px); min-height: 500px; }
    .legend { padding: 10px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .legend-item { display: flex; align-items: center; gap: 8px; margin: 4px 0; }
    .legend-color { width: 20px; height: 20px; border-radius: 50%; }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-purple-50 to-pink-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <a href="{{ route('analytics.dashboard') }}" class="text-purple-600 hover:text-purple-800 text-sm mb-2 inline-block">← Back to Analytics</a>
                <h1 class="text-3xl font-bold text-gray-900">🗺️ Job Market Heatmap</h1>
                <p class="text-gray-600">Interactive visualization of job opportunities by location</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-lg p-4 mb-6 border border-purple-100">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Industry</label>
                    <select id="industry-filter" class="w-full border-gray-200 rounded-lg text-sm">
                        <option value="">All Industries</option>
                        <option value="technology">Technology</option>
                        <option value="healthcare">Healthcare</option>
                        <option value="finance">Finance</option>
                        <option value="marketing">Marketing</option>
                        <option value="education">Education</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Job Category</label>
                    <select id="category-filter" class="w-full border-gray-200 rounded-lg text-sm">
                        <option value="">All Categories</option>
                        <option value="engineering">Engineering</option>
                        <option value="design">Design</option>
                        <option value="sales">Sales</option>
                        <option value="marketing">Marketing</option>
                        <option value="operations">Operations</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Display Mode</label>
                    <select id="display-mode" class="w-full border-gray-200 rounded-lg text-sm">
                        <option value="jobs">Job Count</option>
                        <option value="salary">Avg Salary</option>
                        <option value="demand">Demand Score</option>
                        <option value="competition">Competition</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button id="refresh-map" class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                        Refresh Map
                    </button>
                </div>
            </div>
        </div>

        <!-- Map Container -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-purple-100">
            <div id="heatmap-container"></div>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-6">
            <div class="bg-white rounded-xl p-4 shadow-lg border border-purple-100 text-center">
                <div class="text-2xl font-bold text-purple-600" id="stat-total-jobs">--</div>
                <div class="text-sm text-gray-600">Total Jobs</div>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-lg border border-purple-100 text-center">
                <div class="text-2xl font-bold text-green-600" id="stat-avg-salary">--</div>
                <div class="text-sm text-gray-600">Avg Salary</div>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-lg border border-purple-100 text-center">
                <div class="text-2xl font-bold text-blue-600" id="stat-locations">--</div>
                <div class="text-sm text-gray-600">Locations</div>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-lg border border-purple-100 text-center">
                <div class="text-2xl font-bold text-pink-600" id="stat-highest-demand">--</div>
                <div class="text-sm text-gray-600">Highest Demand</div>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-lg border border-purple-100 text-center">
                <div class="text-2xl font-bold text-orange-600" id="stat-lowest-competition">--</div>
                <div class="text-sm text-gray-600">Best Opportunity</div>
            </div>
        </div>

        <!-- Top Locations Table -->
        <div class="bg-white rounded-xl shadow-lg p-6 mt-6 border border-purple-100">
            <h2 class="text-xl font-bold text-gray-900 mb-4">📍 Top Job Locations</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Location</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Jobs</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Avg Salary</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Demand</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Competition</th>
                        </tr>
                    </thead>
                    <tbody id="top-locations-table">
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize map
    const map = L.map('heatmap-container').setView([39.8283, -98.5795], 4);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    let markers = [];
    let displayMode = 'jobs';

    // Load and display heatmap data
    function loadHeatmapData() {
        const industry = document.getElementById('industry-filter').value;
        const category = document.getElementById('category-filter').value;
        displayMode = document.getElementById('display-mode').value;

        const params = new URLSearchParams();
        if (industry) params.append('industry', industry);
        if (category) params.append('category', category);

        fetch(`{{ route('analytics.api.heatmap') }}?${params}`)
            .then(res => res.json())
            .then(data => {
                // Clear existing markers
                markers.forEach(m => map.removeLayer(m));
                markers = [];

                // Update stats
                document.getElementById('stat-total-jobs').textContent = (data.summary?.total_jobs || 0).toLocaleString();
                document.getElementById('stat-avg-salary').textContent = '$' + Math.round(data.summary?.avg_salary || 0).toLocaleString();
                document.getElementById('stat-locations').textContent = data.summary?.total_locations || 0;
                document.getElementById('stat-highest-demand').textContent = data.summary?.highest_demand_location || '--';
                document.getElementById('stat-lowest-competition').textContent = data.summary?.lowest_competition_location || '--';

                // Add markers
                if (data.points) {
                    data.points.forEach(point => {
                        if (!point.lat || !point.lng) return;

                        let value, color;
                        switch (displayMode) {
                            case 'salary':
                                value = point.avgSalary || 0;
                                color = getColorForValue(value, 50000, 200000);
                                break;
                            case 'demand':
                                value = point.demandScore || 0;
                                color = getColorForValue(value, 0, 100);
                                break;
                            case 'competition':
                                value = 100 - (point.competitionScore || 0);
                                color = getColorForValue(value, 0, 100);
                                break;
                            default:
                                value = point.jobCount || 0;
                                color = getColorForValue(value, 0, 500);
                        }

                        const size = Math.min(40, Math.max(8, Math.sqrt(point.jobCount || 1) * 3));

                        const marker = L.circleMarker([point.lat, point.lng], {
                            radius: size,
                            fillColor: color,
                            color: '#fff',
                            weight: 2,
                            opacity: 1,
                            fillOpacity: 0.7
                        });

                        marker.bindPopup(`
                            <div class="font-sans">
                                <h3 class="font-bold text-lg mb-2">${point.location}</h3>
                                <div class="space-y-1 text-sm">
                                    <p><strong>Jobs:</strong> ${point.jobCount?.toLocaleString() || 0}</p>
                                    <p><strong>Avg Salary:</strong> $${Math.round(point.avgSalary || 0).toLocaleString()}</p>
                                    <p><strong>Demand Score:</strong> ${Math.round(point.demandScore || 0)}/100</p>
                                    <p><strong>Competition:</strong> ${Math.round(point.competitionScore || 0)}/100</p>
                                </div>
                            </div>
                        `);

                        marker.addTo(map);
                        markers.push(marker);
                    });
                }

                // Update table
                updateTopLocationsTable(data.top_locations || []);
            });
    }

    function getColorForValue(value, min, max) {
        const ratio = Math.min(1, Math.max(0, (value - min) / (max - min)));
        const r = Math.round(255 * ratio);
        const g = Math.round(100 + 50 * (1 - ratio));
        const b = Math.round(255 * (1 - ratio));
        return `rgb(${r}, ${g}, ${b})`;
    }

    function updateTopLocationsTable(locations) {
        const tbody = document.getElementById('top-locations-table');
        if (!locations.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No location data available</td></tr>';
            return;
        }

        tbody.innerHTML = locations.map(loc => `
            <tr class="border-b hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-900">${loc.location}</td>
                <td class="px-4 py-3 text-right">${loc.job_count?.toLocaleString() || 0}</td>
                <td class="px-4 py-3 text-right text-green-600">$${Math.round(loc.avg_salary || 0).toLocaleString()}</td>
                <td class="px-4 py-3 text-right">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs ${loc.demand_score > 70 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'}">
                        ${Math.round(loc.demand_score || 0)}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs ${(loc.competition_score || 0) < 40 ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'}">
                        ${Math.round(loc.competition_score || 0)}
                    </span>
                </td>
            </tr>
        `).join('');
    }

    // Event listeners
    document.getElementById('refresh-map').addEventListener('click', loadHeatmapData);
    document.getElementById('industry-filter').addEventListener('change', loadHeatmapData);
    document.getElementById('category-filter').addEventListener('change', loadHeatmapData);
    document.getElementById('display-mode').addEventListener('change', loadHeatmapData);

    // Initial load
    loadHeatmapData();
});
</script>
@endpush
