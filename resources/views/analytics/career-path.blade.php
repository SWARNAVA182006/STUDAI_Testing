@extends('layouts.app')

@section('title', 'Career Path Visualization - Interactive Graph')

@push('styles')
<style>
    #career-graph { height: 600px; background: linear-gradient(to bottom, #f8fafc, #f1f5f9); }
    .node-tooltip { 
        position: absolute; 
        background: white; 
        padding: 16px; 
        border-radius: 12px; 
        box-shadow: 0 10px 40px rgba(0,0,0,0.15); 
        z-index: 1000;
        min-width: 250px;
    }
    .level-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-pink-50 to-purple-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('analytics.dashboard') }}" class="text-pink-600 hover:text-pink-800 text-sm mb-2 inline-block">← Back to Analytics</a>
            <h1 class="text-3xl font-bold text-gray-900">🛤️ Career Path Visualization</h1>
            <p class="text-gray-600">Explore possible career progressions with salary insights</p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-lg p-4 mb-6 border border-pink-100">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Starting Role</label>
                    <input type="text" id="start-role" value="{{ $startRole ?? '' }}" 
                           class="w-full border-gray-200 rounded-lg text-sm" 
                           placeholder="e.g., Junior Developer">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Industry</label>
                    <select id="industry-filter" class="w-full border-gray-200 rounded-lg text-sm">
                        <option value="">All Industries</option>
                        <option value="technology" {{ ($industry ?? '') === 'technology' ? 'selected' : '' }}>Technology</option>
                        <option value="healthcare" {{ ($industry ?? '') === 'healthcare' ? 'selected' : '' }}>Healthcare</option>
                        <option value="finance" {{ ($industry ?? '') === 'finance' ? 'selected' : '' }}>Finance</option>
                        <option value="marketing" {{ ($industry ?? '') === 'marketing' ? 'selected' : '' }}>Marketing</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button id="refresh-graph" class="w-full px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
                        Generate Path
                    </button>
                </div>
            </div>
        </div>

        <!-- Level Legend -->
        <div class="bg-white rounded-xl shadow-lg p-4 mb-6 border border-pink-100">
            <div class="flex flex-wrap items-center gap-4">
                <span class="text-sm font-medium text-gray-600">Career Levels:</span>
                <span class="level-badge bg-green-100 text-green-700">Entry Level</span>
                <span class="level-badge bg-blue-100 text-blue-700">Mid-Level</span>
                <span class="level-badge bg-purple-100 text-purple-700">Senior</span>
                <span class="level-badge bg-orange-100 text-orange-700">Lead</span>
                <span class="level-badge bg-red-100 text-red-700">Manager</span>
                <span class="level-badge bg-gray-800 text-white">Executive</span>
            </div>
        </div>

        <!-- Graph Container -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-pink-100 mb-8">
            <div id="career-graph"></div>
        </div>

        <!-- Selected Role Details -->
        <div id="role-details" class="hidden bg-white rounded-2xl shadow-lg p-8 border border-pink-100 mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4" id="role-title">Role Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <h3 class="font-medium text-gray-600 mb-2">💰 Salary Range</h3>
                    <div class="text-2xl font-bold text-green-600" id="role-salary">--</div>
                </div>
                <div>
                    <h3 class="font-medium text-gray-600 mb-2">⏱️ Avg Experience</h3>
                    <div class="text-2xl font-bold text-blue-600" id="role-experience">--</div>
                </div>
                <div>
                    <h3 class="font-medium text-gray-600 mb-2">📈 Level</h3>
                    <div class="text-2xl font-bold text-purple-600" id="role-level">--</div>
                </div>
            </div>
            
            <div class="mt-6">
                <h3 class="font-medium text-gray-600 mb-3">🎯 Next Career Steps</h3>
                <div id="next-roles" class="grid grid-cols-1 md:grid-cols-2 gap-3"></div>
            </div>
        </div>

        <!-- Path Summary -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl p-4 shadow-lg border border-pink-100 text-center">
                <div class="text-2xl font-bold text-pink-600" id="total-roles">--</div>
                <div class="text-sm text-gray-600">Total Roles</div>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-lg border border-pink-100 text-center">
                <div class="text-2xl font-bold text-purple-600" id="total-paths">--</div>
                <div class="text-sm text-gray-600">Career Paths</div>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-lg border border-pink-100 text-center">
                <div class="text-2xl font-bold text-green-600" id="max-salary">--</div>
                <div class="text-sm text-gray-600">Top Salary</div>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-lg border border-pink-100 text-center">
                <div class="text-2xl font-bold text-blue-600" id="avg-transition">--</div>
                <div class="text-sm text-gray-600">Avg Transition Time</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/vis-network@9.1.6/dist/vis-network.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let network = null;
    let currentData = null;

    loadCareerPath();

    document.getElementById('refresh-graph').addEventListener('click', loadCareerPath);

    function loadCareerPath() {
        const startRole = document.getElementById('start-role').value;
        const industry = document.getElementById('industry-filter').value;

        const params = new URLSearchParams();
        if (startRole) params.append('start_role', startRole);
        if (industry) params.append('industry', industry);

        fetch(`{{ route('analytics.api.career-path') }}?${params}`)
            .then(res => res.json())
            .then(data => {
                currentData = data;
                renderGraph(data);
                updateStats(data);
            });
    }

    function renderGraph(data) {
        const container = document.getElementById('career-graph');
        
        const levelColors = {
            1: { background: '#d1fae5', border: '#10b981' },
            2: { background: '#dbeafe', border: '#3b82f6' },
            3: { background: '#ede9fe', border: '#8b5cf6' },
            4: { background: '#ffedd5', border: '#f97316' },
            5: { background: '#fee2e2', border: '#ef4444' },
            6: { background: '#374151', border: '#1f2937' }
        };

        const nodes = new vis.DataSet(data.nodes.map(node => ({
            id: node.id,
            label: node.label,
            level: node.level,
            title: `${node.label}\n$${Math.round(node.salary || 0).toLocaleString()}`,
            color: levelColors[node.level] || levelColors[3],
            font: { color: node.level === 6 ? '#fff' : '#333', size: 14, face: 'Inter, sans-serif' },
            shape: 'box',
            margin: 10,
            widthConstraint: { minimum: 100, maximum: 150 },
            data: node
        })));

        const edges = new vis.DataSet(data.edges.map(edge => ({
            from: edge.from,
            to: edge.to,
            arrows: 'to',
            color: { color: '#cbd5e1', highlight: '#8b5cf6' },
            width: Math.max(1, Math.min(5, (edge.value || 10) / 20)),
            title: edge.label || '',
            smooth: { type: 'cubicBezier', forceDirection: 'horizontal' }
        })));

        const options = {
            layout: {
                hierarchical: {
                    direction: 'LR',
                    sortMethod: 'directed',
                    levelSeparation: 200,
                    nodeSpacing: 100
                }
            },
            physics: false,
            interaction: {
                hover: true,
                selectConnectedEdges: true
            },
            nodes: {
                borderWidth: 2,
                borderWidthSelected: 3
            }
        };

        if (network) network.destroy();
        network = new vis.Network(container, { nodes, edges }, options);

        network.on('click', function(params) {
            if (params.nodes.length > 0) {
                const nodeId = params.nodes[0];
                const node = nodes.get(nodeId);
                showRoleDetails(node.data, data.edges.filter(e => e.from === nodeId));
            } else {
                document.getElementById('role-details').classList.add('hidden');
            }
        });
    }

    function showRoleDetails(role, edges) {
        const container = document.getElementById('role-details');
        container.classList.remove('hidden');

        document.getElementById('role-title').textContent = role.label;
        document.getElementById('role-salary').textContent = '$' + Math.round(role.salary || 0).toLocaleString();
        document.getElementById('role-experience').textContent = (role.experience || '3-5') + ' years';
        
        const levels = { 1: 'Entry Level', 2: 'Mid-Level', 3: 'Senior', 4: 'Lead', 5: 'Manager', 6: 'Executive' };
        document.getElementById('role-level').textContent = levels[role.level] || 'Mid-Level';

        const nextRolesContainer = document.getElementById('next-roles');
        if (edges.length === 0) {
            nextRolesContainer.innerHTML = '<p class="text-gray-500 col-span-2">No direct career transitions mapped</p>';
        } else {
            nextRolesContainer.innerHTML = edges.map(edge => {
                const targetNode = currentData.nodes.find(n => n.id === edge.to);
                return `
                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="font-medium text-gray-900">${targetNode?.label || 'Unknown'}</div>
                        <div class="text-sm text-gray-600">
                            ${edge.label || '2-3 years'} • 
                            ${edge.salaryIncrease ? '+' + edge.salaryIncrease + '%' : ''} salary
                        </div>
                    </div>
                `;
            }).join('');
        }
    }

    function updateStats(data) {
        document.getElementById('total-roles').textContent = data.nodes?.length || 0;
        document.getElementById('total-paths').textContent = data.edges?.length || 0;
        
        const maxSalary = Math.max(...(data.nodes || []).map(n => n.salary || 0));
        document.getElementById('max-salary').textContent = '$' + Math.round(maxSalary).toLocaleString();
        
        document.getElementById('avg-transition').textContent = '2-4 years';
    }
});
</script>
@endpush
