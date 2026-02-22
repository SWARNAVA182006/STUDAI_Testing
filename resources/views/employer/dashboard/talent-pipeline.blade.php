@extends('layouts.app')

@section('title', 'Talent Pipeline Management')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50 py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Talent Pipeline Command Center</h1>
                <p class="mt-2 text-gray-600 max-w-2xl">
                    Monitor pipeline health, re-engage silver medalists, discover passive talent, and protect your employer brand in one intelligent workspace.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button id="refreshDashboard" type="button" class="inline-flex items-center px-5 py-2.5 bg-white border border-gray-200 rounded-lg shadow-sm text-sm font-semibold text-gray-700 hover:border-pink-400 hover:text-pink-600 transition-colors">
                    <svg class="w-5 h-5 mr-2 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5 19a9 9 0 0114-7.5M19 5a9 9 0 01-14 7.5" />
                    </svg>
                    Refresh Data
                </button>
                <a href="{{ route('employer.dashboard') }}" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-pink-500 to-purple-600 text-white rounded-lg shadow-md hover:shadow-lg text-sm font-semibold transition-all">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h5l2 2h8a1 1 0 011 1v12a1 1 0 01-1 1H4a1 1 0 01-1-1V4z" />
                    </svg>
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- High-Level Summary -->
        <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl shadow-lg p-6 border border-pink-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Active Pipelines</p>
                        <p id="totalPipelinesValue" class="text-3xl font-bold text-gray-900">--</p>
                    </div>
                    <div class="h-12 w-12 rounded-lg bg-gradient-to-br from-pink-500 to-purple-600 flex items-center justify-center text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l6 6-6 6M21 7l-6 6 6 6" />
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-sm text-gray-600">{{ __('Total number of talent pipelines currently managed by your team.') }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 border border-pink-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Pipeline Candidates</p>
                        <p id="totalCandidatesValue" class="text-3xl font-bold text-gray-900">--</p>
                    </div>
                    <div class="h-12 w-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M9 20H4v-2a3 3 0 015.356-1.857M15 11a4 4 0 10-8 0 4 4 0 008 0z" />
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-sm text-gray-600">{{ __('Combined number of candidates across all active pipelines.') }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 border border-pink-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Avg. Health Score</p>
                        <p id="averageHealthScoreValue" class="text-3xl font-bold text-gray-900">--</p>
                    </div>
                    <div class="h-12 w-12 rounded-lg bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 11V3a1 1 0 012 0v8h3l-4 4-4-4h3zM5 19h14" />
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-sm text-gray-600">{{ __('Average weighted health score across pipelines (target ≥ 70).') }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 border border-pink-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Needs Attention</p>
                        <p id="pipelinesAttentionValue" class="text-3xl font-bold text-gray-900">--</p>
                    </div>
                    <div class="h-12 w-12 rounded-lg bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.29 3.86L1.82 18a1 1 0 00.86 1.5h18.64a1 1 0 00.86-1.5L12.71 3.86a1 1 0 00-1.72 0zM12 9v4m0 4h.01" />
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-sm text-gray-600">{{ __('Pipelines below the health threshold or under-staffed vs. target size.') }}</p>
            </div>
        </div>

        <!-- Pipeline Overview -->
        <div class="mt-10 grid grid-cols-1 xl:grid-cols-3 gap-8">
            <div class="xl:col-span-2 space-y-8">
                <div class="bg-white rounded-2xl shadow-xl border border-pink-100 p-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                                <span id="selectedPipelineTitle">Select a Pipeline</span>
                                <span id="selectedPipelineStatusBadge" class="hidden text-xs px-3 py-1 rounded-full bg-gray-100 text-gray-600">
                                    --
                                </span>
                            </h2>
                            <p id="selectedPipelineDescription" class="mt-2 text-gray-600 max-w-2xl">
                                Choose a pipeline to reveal health metrics, candidate distribution, and engagement insights.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <div class="relative">
                                <select id="pipelineSelector" class="appearance-none w-64 px-4 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-pink-400 focus:border-pink-400 text-sm text-gray-700">
                                    <option value="">Loading pipelines...</option>
                                </select>
                                <svg class="w-5 h-5 text-gray-500 absolute right-3 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                            <button id="viewPipelineMatches" type="button" class="inline-flex items-center px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow-sm hover:shadow lg:ml-auto">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317a1 1 0 011.35-.447l7.4 3.7a1 1 0 01.553.894v7.272a1 1 0 01-.553.894l-7.4 3.7a1 1 0 01-.894 0l-7.4-3.7A1 1 0 013 15.736V8.464a1 1 0 01.553-.894l7.4-3.7z" />
                                </svg>
                                Match To Opening
                            </button>
                        </div>
                    </div>

                    <div id="pipelineMeta" class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-gradient-to-br from-pink-50 to-purple-50 border border-pink-100 rounded-xl p-4">
                            <p class="text-xs uppercase text-gray-500 tracking-wide">Target Role</p>
                            <p id="pipelineTargetRole" class="mt-2 text-sm font-semibold text-gray-900">—</p>
                        </div>
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-100 rounded-xl p-4">
                            <p class="text-xs uppercase text-gray-500 tracking-wide">Target Size</p>
                            <p id="pipelineTargetSize" class="mt-2 text-sm font-semibold text-gray-900">—</p>
                        </div>
                        <div class="bg-gradient-to-br from-emerald-50 to-green-50 border border-emerald-100 rounded-xl p-4">
                            <p class="text-xs uppercase text-gray-500 tracking-wide">Current Size</p>
                            <p id="pipelineCurrentSize" class="mt-2 text-sm font-semibold text-gray-900">—</p>
                        </div>
                        <div class="bg-gradient-to-br from-yellow-50 to-amber-50 border border-yellow-100 rounded-xl p-4">
                            <p class="text-xs uppercase text-gray-500 tracking-wide">Next Hire Window</p>
                            <p id="pipelineNextHire" class="mt-2 text-sm font-semibold text-gray-900">—</p>
                        </div>
                    </div>

                    <div class="mt-8 grid grid-cols-1 xl:grid-cols-2 gap-6">
                        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">Pipeline Health</h3>
                                <span id="pipelineHealthLabel" class="text-sm font-medium text-gray-600">—</span>
                            </div>
                            <div class="mt-4 flex items-center justify-center">
                                <canvas id="pipelineHealthGauge" width="320" height="200"></canvas>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">Stage Distribution</h3>
                                <span class="text-sm text-gray-500">Current candidate mix</span>
                            </div>
                            <div class="mt-4">
                                <canvas id="stageDistributionChart" height="220"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Pipeline Candidates</h3>
                            <div class="flex gap-3">
                                <select id="candidateStageFilter" class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-pink-400">
                                    <option value="">All Stages</option>
                                    <option value="hot">Hot</option>
                                    <option value="warm">Warm</option>
                                    <option value="qualified">Qualified</option>
                                    <option value="pre_screened">Pre-screened</option>
                                    <option value="engaged">Engaged</option>
                                    <option value="sourced">Sourced</option>
                                    <option value="cool">Cool</option>
                                </select>
                                <select id="candidateAvailabilityFilter" class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-pink-400">
                                    <option value="">All Availability</option>
                                    <option value="immediately_available">Immediate</option>
                                    <option value="open_to_opportunities">Open to opportunities</option>
                                    <option value="passive">Passive</option>
                                </select>
                            </div>
                        </div>
                        <div id="pipelineCandidatesContainer" class="grid grid-cols-1 lg:grid-cols-2 gap-4"></div>
                        <div id="pipelineCandidatesEmpty" class="hidden text-center border-2 border-dashed border-gray-200 rounded-xl py-12">
                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-7 7-7-7" />
                            </svg>
                            <p class="text-gray-500">No candidates match the current filters.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-xl border border-pink-100 p-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Silver Medalists</h2>
                            <p class="text-gray-600">Re-engage near-miss finalists and convert them into future hires.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-4">
                            <div>
                                <label for="silverReasonFilter" class="block text-xs uppercase text-gray-500 tracking-wide">Filter Reason</label>
                                <select id="silverReasonFilter" class="mt-1 px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 focus:ring-2 focus:ring-pink-400">
                                    <option value="">All reasons</option>
                                    <option value="strong_second_choice">Strong second choice</option>
                                    <option value="overqualified">Overqualified</option>
                                    <option value="timing_mismatch">Timing mismatch</option>
                                    <option value="budget_constraints">Budget constraints</option>
                                    <option value="team_fit_preference">Team fit preference</option>
                                    <option value="skill_mismatch_minor">Minor skill mismatch</option>
                                    <option value="cultural_potential">Cultural potential</option>
                                </select>
                            </div>
                            <div>
                                <label for="silverSearch" class="block text-xs uppercase text-gray-500 tracking-wide">Search</label>
                                <input id="silverSearch" type="text" placeholder="Name, role, or company" class="mt-1 px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 focus:ring-2 focus:ring-pink-400" />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-gradient-to-r from-pink-500 to-purple-600 text-white rounded-xl p-4 shadow">
                            <p class="text-xs uppercase tracking-wide">Ready for Re-engagement</p>
                            <p id="silverReadyCount" class="mt-2 text-2xl font-bold">--</p>
                        </div>
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl p-4 shadow">
                            <p class="text-xs uppercase tracking-wide">High Potential</p>
                            <p id="silverHighPotentialCount" class="mt-2 text-2xl font-bold">--</p>
                        </div>
                        <div class="bg-gradient-to-r from-emerald-500 to-green-600 text-white rounded-xl p-4 shadow">
                            <p class="text-xs uppercase tracking-wide">Total Silver Medalists</p>
                            <p id="silverTotalCount" class="mt-2 text-2xl font-bold">--</p>
                        </div>
                    </div>

                    <div class="overflow-hidden border border-gray-200 rounded-xl">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Candidate</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Original Role</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reason</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Score</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Next Action</th>
                                </tr>
                            </thead>
                            <tbody id="silverMedalistTable" class="bg-white divide-y divide-gray-200"></tbody>
                        </table>
                        <div id="silverEmptyState" class="hidden text-center py-10">
                            <p class="text-gray-500">No silver medalists match the selected filters.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                <div class="bg-white rounded-2xl shadow-xl border border-pink-100 p-8">
                    <h2 class="text-xl font-bold text-gray-900">Pipeline Insights</h2>
                    <p class="text-sm text-gray-600">Real-time signal detections for proactive talent engagement.</p>
                    <dl class="mt-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-gray-600">Warm &amp; Hot Candidates</dt>
                            <dd id="warmCandidatesValue" class="text-lg font-semibold text-gray-900">--</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-gray-600">Need Follow-up</dt>
                            <dd id="followUpNeededValue" class="text-lg font-semibold text-gray-900">--</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-gray-600">Average Match Score</dt>
                            <dd id="averageMatchScoreValue" class="text-lg font-semibold text-gray-900">--</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-gray-600">DNA Compatibility</dt>
                            <dd id="averageDnaScoreValue" class="text-lg font-semibold text-gray-900">--</dd>
                        </div>
                    </dl>
                    <div id="followUpList" class="mt-6 space-y-3 max-h-64 overflow-y-auto pr-1"></div>
                </div>

                <div class="bg-white rounded-2xl shadow-xl border border-blue-100 p-8">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-gray-900">Passive Talent Radar</h2>
                        <button id="discoverPassiveBtn" type="button" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow-sm hover:shadow">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
                            </svg>
                            Discover Candidates
                        </button>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">Leverage AI scouting to surface passive candidates aligned with your cultural DNA.</p>

                    <form id="discoverPassiveForm" class="mt-4 bg-blue-50 border border-blue-100 rounded-xl p-4 space-y-3 hidden">
                        <div>
                            <label for="discoverLimit" class="block text-xs uppercase text-blue-700 tracking-wide">Candidates to discover</label>
                            <input id="discoverLimit" type="number" min="1" max="50" value="20" class="mt-1 w-full px-3 py-2 border border-blue-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-400" />
                        </div>
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow-sm hover:shadow">
                            Run Discovery
                        </button>
                    </form>

                    <div class="mt-6">
                        <h3 class="text-sm font-semibold text-gray-900">Engagement Metrics</h3>
                        <dl class="mt-3 space-y-2 text-sm text-gray-600">
                            <div class="flex items-center justify-between">
                                <dt>Ready for Outreach</dt>
                                <dd id="passiveReadyCount" class="font-semibold text-gray-900">--</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt>Active Monitoring</dt>
                                <dd id="passiveMonitoringCount" class="font-semibold text-gray-900">--</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt>High DNA Alignment</dt>
                                <dd id="passiveHighAlignment" class="font-semibold text-gray-900">--</dd>
                            </div>
                        </dl>
                    </div>

                    <div id="passiveCandidatesList" class="mt-6 space-y-4 max-h-72 overflow-y-auto pr-1"></div>

                    <div id="engagementStrategyPanel" class="mt-6 hidden bg-white border border-blue-100 rounded-xl p-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">Suggested Engagement Strategy</h3>
                            <button type="button" id="closeStrategyPanel" class="text-sm text-blue-600 hover:underline">Close</button>
                        </div>
                        <pre id="strategyContent" class="mt-3 text-sm text-gray-700 whitespace-pre-wrap"></pre>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-xl border border-purple-100 p-8">
                    <h2 class="text-xl font-bold text-gray-900">Candidate Experience Journey</h2>
                    <p class="text-sm text-gray-600">Understand each candidate's experience and sentiment across touchpoints.</p>

                    <div id="candidateJourneyEmpty" class="mt-6 text-center border border-dashed border-purple-200 rounded-xl py-10 px-4">
                        <p class="text-gray-500">Select a candidate to view their journey timeline.</p>
                    </div>

                    <div id="candidateJourneyContainer" class="mt-6 hidden">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h3 id="journeyCandidateName" class="text-lg font-semibold text-gray-900">--</h3>
                                <p class="text-sm text-gray-600">Total interactions: <span id="journeyInteractionCount">0</span></p>
                            </div>
                            <p class="text-sm text-gray-600">Average response time: <span id="journeyAverageResponse">--</span> hrs</p>
                        </div>
                        <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div class="lg:col-span-2">
                                <div id="journeyTimeline" class="relative border-l-2 border-purple-200 pl-6 space-y-6 max-h-80 overflow-y-auto"></div>
                            </div>
                            <div>
                                <canvas id="journeySentimentChart" height="220"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-xl border border-emerald-100 p-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Employer Brand Scorecard</h2>
                            <p class="text-sm text-gray-600">Track the quality of your candidate experience and brand sentiment.</p>
                        </div>
                        <form id="brandPeriodForm" class="flex flex-wrap items-center gap-3">
                            <div>
                                <label for="brandStartDate" class="block text-xs uppercase text-gray-500 tracking-wide">Start</label>
                                <input id="brandStartDate" type="date" class="mt-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-emerald-400" />
                            </div>
                            <div>
                                <label for="brandEndDate" class="block text-xs uppercase text-gray-500 tracking-wide">End</label>
                                <input id="brandEndDate" type="date" class="mt-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-emerald-400" />
                            </div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-emerald-500 to-green-600 rounded-lg shadow-sm hover:shadow">
                                Recalculate
                            </button>
                        </form>
                    </div>

                    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-1 bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-100 rounded-xl p-6 flex flex-col items-center text-center">
                            <p class="text-sm text-gray-600">Overall Brand Score</p>
                            <p id="brandOverallValue" class="mt-2 text-4xl font-bold text-gray-900">--</p>
                            <span id="brandHealthBadge" class="mt-2 inline-flex items-center px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold">--</span>
                            <p class="mt-4 text-sm text-gray-600">
                                Trend: <span id="brandTrendValue" class="font-semibold text-gray-900">--</span>
                            </p>
                            <p class="mt-1 text-sm text-gray-600">NPS: <span id="brandNpsValue" class="font-semibold text-gray-900">--</span></p>
                            <p class="mt-1 text-sm text-gray-600">Feedback collected: <span id="brandFeedbackValue" class="font-semibold text-gray-900">--</span></p>
                        </div>
                        <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm">
                            <h3 class="text-sm font-semibold text-gray-900">Experience Components</h3>
                            <canvas id="brandComponentsChart" class="mt-4" height="220"></canvas>
                        </div>
                        <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm">
                            <h3 class="text-sm font-semibold text-gray-900">Sentiment &amp; NPS</h3>
                            <canvas id="brandSentimentChart" class="mt-4" height="220"></canvas>
                        </div>
                    </div>

                    <div class="mt-6">
                        <h3 class="text-sm font-semibold text-gray-900">Identified Risks</h3>
                        <ul id="brandRiskList" class="mt-2 space-y-2 text-sm text-gray-600"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Convert Silver Medalist Modal -->
<div id="convertModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-900">Convert to Pipeline Candidate</h3>
            <button id="closeConvertModal" type="button" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <p class="mt-2 text-sm text-gray-600">
            Select a destination pipeline. The candidate will be added with historical feedback and match scores preserved.
        </p>
        <form id="convertForm" class="mt-6 space-y-4">
            <div>
                <label for="convertPipelineSelect" class="block text-sm font-medium text-gray-700">Talent Pipeline</label>
                <select id="convertPipelineSelect" class="mt-1 w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-400 focus:border-pink-400">
                </select>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="cancelConvert" class="px-4 py-2 text-sm font-semibold text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-pink-500 to-purple-600 rounded-lg shadow hover:shadow-lg">
                    Convert Candidate
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer" class="fixed bottom-6 right-6 z-50 space-y-3"></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const apiBase = '{{ url('/api/scout') }}';
        const endpoints = {
            pipelines: '{{ route('api.scout.pipelines.index') }}',
            pipeline: (id) => `${apiBase}/pipeline/${id}`,
            silverMedalists: '{{ route('api.scout.silver-medalists.index') }}',
            convertSilver: (id) => `${apiBase}/silver-medalist/${id}/convert`,
            passiveReady: '{{ route('api.scout.passive-candidates.ready') }}',
            passiveDiscover: '{{ route('api.scout.passive-candidates.discover') }}',
            passiveStrategy: (id) => `${apiBase}/passive-candidate/${id}/engagement-strategy`,
            passiveEngage: (id) => `${apiBase}/passive-candidate/${id}/engage`,
            advanceCandidate: (id) => `${apiBase}/pipeline-candidate/${id}/advance`,
            candidateExperience: (id) => `${apiBase}/candidate-experience/${id}`,
            employerBrand: '{{ route('api.scout.employer-brand-score.show') }}',
            employerBrandCalculate: '{{ route('api.scout.employer-brand-score.calculate') }}',
            matchToJob: '{{ route('api.scout.pipeline.match-to-job') }}'
        };

        if (window.axios) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
            window.axios.defaults.withCredentials = true;
        }

        const state = {
            pipelines: [],
            pipelineSummary: null,
            selectedPipelineId: null,
            selectedPipeline: null,
            silverMedalists: [],
            passiveCandidates: [],
            passiveMetrics: {},
            discoveryResults: [],
            candidateJourney: null,
            selectedCandidateName: null,
            charts: {}
        };

        const elements = {
            refreshButton: document.getElementById('refreshDashboard'),
            pipelineSelector: document.getElementById('pipelineSelector'),
            pipelineStatusBadge: document.getElementById('selectedPipelineStatusBadge'),
            pipelineDescription: document.getElementById('selectedPipelineDescription'),
            pipelineTargetRole: document.getElementById('pipelineTargetRole'),
            pipelineTargetSize: document.getElementById('pipelineTargetSize'),
            pipelineCurrentSize: document.getElementById('pipelineCurrentSize'),
            pipelineNextHire: document.getElementById('pipelineNextHire'),
            pipelineHealthLabel: document.getElementById('pipelineHealthLabel'),
            pipelineCandidatesContainer: document.getElementById('pipelineCandidatesContainer'),
            pipelineCandidatesEmpty: document.getElementById('pipelineCandidatesEmpty'),
            candidateStageFilter: document.getElementById('candidateStageFilter'),
            candidateAvailabilityFilter: document.getElementById('candidateAvailabilityFilter'),
            totalPipelinesValue: document.getElementById('totalPipelinesValue'),
            totalCandidatesValue: document.getElementById('totalCandidatesValue'),
            averageHealthScoreValue: document.getElementById('averageHealthScoreValue'),
            pipelinesAttentionValue: document.getElementById('pipelinesAttentionValue'),
            warmCandidatesValue: document.getElementById('warmCandidatesValue'),
            followUpNeededValue: document.getElementById('followUpNeededValue'),
            averageMatchScoreValue: document.getElementById('averageMatchScoreValue'),
            averageDnaScoreValue: document.getElementById('averageDnaScoreValue'),
            followUpList: document.getElementById('followUpList'),
            selectedPipelineTitle: document.getElementById('selectedPipelineTitle'),
            silverReadyCount: document.getElementById('silverReadyCount'),
            silverHighPotentialCount: document.getElementById('silverHighPotentialCount'),
            silverTotalCount: document.getElementById('silverTotalCount'),
            silverTable: document.getElementById('silverMedalistTable'),
            silverReasonFilter: document.getElementById('silverReasonFilter'),
            silverSearch: document.getElementById('silverSearch'),
            silverEmptyState: document.getElementById('silverEmptyState'),
            convertModal: document.getElementById('convertModal'),
            closeConvertModal: document.getElementById('closeConvertModal'),
            cancelConvert: document.getElementById('cancelConvert'),
            convertForm: document.getElementById('convertForm'),
            convertPipelineSelect: document.getElementById('convertPipelineSelect'),
            discoverPassiveBtn: document.getElementById('discoverPassiveBtn'),
            discoverPassiveForm: document.getElementById('discoverPassiveForm'),
            discoverLimit: document.getElementById('discoverLimit'),
            passiveReadyCount: document.getElementById('passiveReadyCount'),
            passiveMonitoringCount: document.getElementById('passiveMonitoringCount'),
            passiveHighAlignment: document.getElementById('passiveHighAlignment'),
            passiveCandidatesList: document.getElementById('passiveCandidatesList'),
            engagementStrategyPanel: document.getElementById('engagementStrategyPanel'),
            strategyContent: document.getElementById('strategyContent'),
            closeStrategyPanel: document.getElementById('closeStrategyPanel'),
            candidateJourneyEmpty: document.getElementById('candidateJourneyEmpty'),
            candidateJourneyContainer: document.getElementById('candidateJourneyContainer'),
            journeyCandidateName: document.getElementById('journeyCandidateName'),
            journeyInteractionCount: document.getElementById('journeyInteractionCount'),
            journeyAverageResponse: document.getElementById('journeyAverageResponse'),
            journeyTimeline: document.getElementById('journeyTimeline'),
            brandOverallValue: document.getElementById('brandOverallValue'),
            brandHealthBadge: document.getElementById('brandHealthBadge'),
            brandTrendValue: document.getElementById('brandTrendValue'),
            brandNpsValue: document.getElementById('brandNpsValue'),
            brandFeedbackValue: document.getElementById('brandFeedbackValue'),
            brandRiskList: document.getElementById('brandRiskList'),
            brandPeriodForm: document.getElementById('brandPeriodForm'),
            brandStartDate: document.getElementById('brandStartDate'),
            brandEndDate: document.getElementById('brandEndDate'),
            toastContainer: document.getElementById('toastContainer'),
            pipelineHealthCanvas: document.getElementById('pipelineHealthGauge'),
            stageDistributionCanvas: document.getElementById('stageDistributionChart'),
            journeySentimentCanvas: document.getElementById('journeySentimentChart'),
            brandComponentsCanvas: document.getElementById('brandComponentsChart'),
            brandSentimentCanvas: document.getElementById('brandSentimentChart'),
            viewPipelineMatches: document.getElementById('viewPipelineMatches')
        };

        const stageLabels = {
            sourced: 'Sourced',
            engaged: 'Engaged',
            qualified: 'Qualified',
            pre_screened: 'Pre-screened',
            warm: 'Warm',
            hot: 'Hot',
            cool: 'Cooling',
            archived: 'Archived'
        };

        const statusStyles = {
            excellent: { label: 'Excellent', classes: 'bg-emerald-100 text-emerald-700' },
            good: { label: 'Good', classes: 'bg-blue-100 text-blue-700' },
            fair: { label: 'Fair', classes: 'bg-yellow-100 text-yellow-700' },
            needs_improvement: { label: 'Needs Improvement', classes: 'bg-red-100 text-red-700' }
        };

        function showToast(type, message) {
            const toast = document.createElement('div');
            const palette = type === 'success'
                ? 'bg-emerald-100 border-emerald-300 text-emerald-800'
                : type === 'error'
                    ? 'bg-red-100 border-red-300 text-red-800'
                    : 'bg-blue-100 border-blue-300 text-blue-800';

            toast.className = `max-w-sm w-full border ${palette} rounded-xl shadow-lg px-4 py-3 flex items-start gap-3 animate-slide-in`;
            toast.innerHTML = `
                <svg class="w-5 h-5 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'success' ? 'M5 13l4 4L19 7' : type === 'error' ? 'M6 18L18 6M6 6l12 12' : 'M13 16h-1v-4h-1m1-4h.01'}" />
                </svg>
                <div class="text-sm">${message}</div>
            `;
            elements.toastContainer.appendChild(toast);
            setTimeout(() => toast.remove(), 4800);
        }

        function formatNumber(value) {
            if (value === null || value === undefined || Number.isNaN(value)) {
                return '--';
            }
            return Intl.NumberFormat().format(value);
        }

        function formatPercent(value) {
            if (value === null || value === undefined || Number.isNaN(value)) {
                return '--';
            }
            return `${Math.round(value)}%`;
        }

        function formatDate(value) {
            if (!value) return '--';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return value;
            }
            return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
        }

        function upsertChart(key, configCallback) {
            if (!window.Chart || !configCallback) {
                return null;
            }
            if (state.charts[key]) {
                state.charts[key].destroy();
                state.charts[key] = null;
            }
            const config = configCallback();
            if (!config) {
                return null;
            }
            state.charts[key] = new Chart(config.ctx, config.options);
            return state.charts[key];
        }

        function renderPipelineHealthGauge(score) {
            if (!elements.pipelineHealthCanvas) return;
            const value = Math.max(0, Math.min(100, Number(score || 0)));
            upsertChart('pipelineHealth', () => ({
                ctx: elements.pipelineHealthCanvas,
                options: {
                    type: 'doughnut',
                    data: {
                        labels: ['Health', 'Gap'],
                        datasets: [{
                            data: [value, 100 - value],
                            backgroundColor: [
                                value >= 80 ? '#34d399' : value >= 60 ? '#60a5fa' : value >= 40 ? '#fbbf24' : '#f87171',
                                '#e5e7eb'
                            ],
                            borderWidth: 0,
                            cutout: '75%',
                            rotation: -90,
                            circumference: 180
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false },
                            tooltip: { enabled: true },
                            annotation: {
                                annotations: {}
                            }
                        }
                    }
                }
            }));
        }

        function renderStageDistribution(data) {
            if (!elements.stageDistributionCanvas) return;
            const labels = Object.keys(stageLabels);
            const dataset = labels.map((key) => data[key] || 0);
            upsertChart('stageDistribution', () => ({
                ctx: elements.stageDistributionCanvas,
                options: {
                    type: 'bar',
                    data: {
                        labels: labels.map((key) => stageLabels[key]),
                        datasets: [{
                            label: 'Candidates',
                            data: dataset,
                            backgroundColor: '#f472b6',
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                }
            }));
        }

        function renderJourneySentiment(breakdown) {
            if (!elements.journeySentimentCanvas) return;
            const data = [breakdown.positive || 0, breakdown.neutral || 0, breakdown.negative || 0];
            upsertChart('journeySentiment', () => ({
                ctx: elements.journeySentimentCanvas,
                options: {
                    type: 'doughnut',
                    data: {
                        labels: ['Positive', 'Neutral', 'Negative'],
                        datasets: [{
                            data,
                            backgroundColor: ['#34d399', '#93c5fd', '#f87171'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                }
            }));
        }

        function renderBrandComponents(components) {
            if (!elements.brandComponentsCanvas) return;
            const labels = [
                'Application Experience',
                'Communication',
                'Interview Experience',
                'Feedback Quality',
                'Transparency',
                'Respect'
            ];
            const values = [
                components.application_experience || 0,
                components.communication || 0,
                components.interview_experience || 0,
                components.feedback_quality || 0,
                components.transparency || 0,
                components.respect || 0
            ];
            upsertChart('brandComponents', () => ({
                ctx: elements.brandComponentsCanvas,
                options: {
                    type: 'radar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Score',
                            data: values,
                            backgroundColor: 'rgba(52, 211, 153, 0.2)',
                            borderColor: '#34d399',
                            pointBackgroundColor: '#10b981'
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            r: {
                                suggestedMin: 0,
                                suggestedMax: 100,
                                ticks: { stepSize: 20 }
                            }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                }
            }));
        }

        function renderBrandSentiment(metrics) {
            if (!elements.brandSentimentCanvas) return;
            const data = [
                metrics.sentiment_distribution?.positive_rate || 0,
                metrics.sentiment_distribution?.negative_rate || 0,
                Math.max(0, 100 - (metrics.sentiment_distribution?.positive_rate || 0) - (metrics.sentiment_distribution?.negative_rate || 0))
            ];
            upsertChart('brandSentiment', () => ({
                ctx: elements.brandSentimentCanvas,
                options: {
                    type: 'bar',
                    data: {
                        labels: ['Positive', 'Negative', 'Neutral'],
                        datasets: [{
                            data,
                            backgroundColor: ['#34d399', '#f87171', '#60a5fa'],
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                suggestedMax: 100
                            }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                }
            }));
        }

        function populatePipelineSelector(pipelines) {
            const selector = elements.pipelineSelector;
            selector.innerHTML = '';
            if (!pipelines.length) {
                selector.innerHTML = '<option value="">No pipelines available</option>';
                elements.selectedPipelineTitle.textContent = 'No pipelines available';
                elements.pipelineDescription.textContent = 'Create a talent pipeline to start tracking candidates.';
                return;
            }
            selector.innerHTML = '<option value="">Select a pipeline</option>' + pipelines.map((pipeline) => `
                <option value="${pipeline.id}">${pipeline.pipeline_name} · ${pipeline.target_role}</option>
            `).join('');
        }

        function updatePipelineSummary(summary) {
            if (!summary) return;
            elements.totalPipelinesValue.textContent = formatNumber(summary.total_pipelines);
            elements.totalCandidatesValue.textContent = formatNumber(summary.total_candidates);
            elements.averageHealthScoreValue.textContent = formatNumber(summary.average_health_score || 0);
            elements.pipelinesAttentionValue.textContent = formatNumber(summary.pipelines_needing_attention);
            elements.warmCandidatesValue.textContent = formatNumber(summary.warm_candidates);
            elements.followUpNeededValue.textContent = formatNumber(summary.candidates_needing_follow_up);
        }

        function mapStageCounts(candidates) {
            const counts = {};
            Object.keys(stageLabels).forEach((stage) => { counts[stage] = 0; });
            candidates.forEach((candidate) => {
                const stage = candidate.pipeline_stage || 'sourced';
                counts[stage] = (counts[stage] || 0) + 1;
            });
            return counts;
        }

        function renderFollowUps(candidates) {
            elements.followUpList.innerHTML = '';
            const needingFollowUp = candidates.filter((candidate) => candidate.next_follow_up_date);
            if (!needingFollowUp.length) {
                elements.followUpList.innerHTML = '<p class="text-sm text-gray-500">No candidates require follow-up right now.</p>';
                return;
            }
            needingFollowUp.sort((a, b) => new Date(a.next_follow_up_date) - new Date(b.next_follow_up_date));
            needingFollowUp.forEach((candidate) => {
                const card = document.createElement('div');
                card.className = 'border border-gray-200 rounded-xl px-4 py-3 bg-gray-50';
                card.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">${candidate.user?.name || 'Candidate'}</p>
                            <p class="text-xs text-gray-500">
                                Stage: ${stageLabels[candidate.pipeline_stage] || candidate.pipeline_stage}
                            </p>
                        </div>
                        <span class="text-xs font-semibold text-pink-600">${formatDate(candidate.next_follow_up_date)}</span>
                    </div>
                `;
                elements.followUpList.appendChild(card);
            });
        }

        function averageMetric(list, key) {
            if (!list.length) return 0;
            const sum = list.reduce((acc, item) => acc + (Number(item[key]) || 0), 0);
            return sum / list.length;
        }

        function renderPipelineCandidates(candidates) {
            elements.pipelineCandidatesContainer.innerHTML = '';
            if (!candidates.length) {
                elements.pipelineCandidatesEmpty.classList.remove('hidden');
                return;
            }
            elements.pipelineCandidatesEmpty.classList.add('hidden');
            candidates.forEach((candidate) => {
                const card = document.createElement('div');
                const priority = candidate.priority_level || 'medium';
                const priorityClasses = priority === 'critical'
                    ? 'bg-red-100 text-red-600'
                    : priority === 'high'
                        ? 'bg-orange-100 text-orange-600'
                        : priority === 'medium'
                            ? 'bg-blue-100 text-blue-600'
                            : 'bg-gray-100 text-gray-600';
                card.className = 'rounded-2xl border border-gray-200 bg-white shadow-sm p-5 flex flex-col gap-4';
                card.innerHTML = `
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-lg font-semibold text-gray-900">${candidate.user?.name || 'Candidate'}</p>
                            <p class="text-sm text-gray-500">${candidate.user?.current_title || 'Role not specified'}</p>
                        </div>
                        <span class="text-xs px-3 py-1 rounded-full font-semibold ${priorityClasses}">
                            ${priority.charAt(0).toUpperCase() + priority.slice(1)} priority
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm text-gray-600">
                        <div>
                            <p class="font-semibold text-gray-700">Stage</p>
                            <p>${stageLabels[candidate.pipeline_stage] || candidate.pipeline_stage}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-700">Match Score</p>
                            <p>${Math.round(candidate.match_score || 0)}%</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-700">DNA Compatibility</p>
                            <p>${Math.round(candidate.dna_compatibility_score || 0)}%</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-700">Last Engaged</p>
                            <p>${formatDate(candidate.last_engaged_at)}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" data-action="view-journey" data-user-id="${candidate.user_id}" data-user-name="${candidate.user?.name || 'Candidate'}" class="inline-flex items-center px-3 py-2 text-xs font-semibold text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100">
                            View journey
                        </button>
                        <div class="flex items-center gap-2">
                            <select data-candidate-id="${candidate.id}" class="stage-select px-3 py-1.5 border border-gray-200 rounded-lg text-xs text-gray-700 focus:ring-2 focus:ring-pink-400">
                                ${Object.keys(stageLabels).map((stage) => `
                                    <option value="${stage}" ${stage === candidate.pipeline_stage ? 'selected' : ''}>${stageLabels[stage]}</option>
                                `).join('')}
                            </select>
                            <button type="button" data-action="advance-stage" data-candidate-id="${candidate.id}" class="inline-flex items-center px-3 py-2 text-xs font-semibold text-white bg-gradient-to-r from-pink-500 to-purple-600 rounded-lg shadow">
                                Update stage
                            </button>
                        </div>
                    </div>
                `;
                elements.pipelineCandidatesContainer.appendChild(card);
            });
        }

        function filterCandidates() {
            if (!state.selectedPipeline) return;
            const stageFilter = elements.candidateStageFilter.value;
            const availabilityFilter = elements.candidateAvailabilityFilter.value;
            let filtered = [...state.selectedPipeline.candidates];
            if (stageFilter) {
                filtered = filtered.filter((candidate) => candidate.pipeline_stage === stageFilter);
            }
            if (availabilityFilter) {
                filtered = filtered.filter((candidate) => candidate.availability_status === availabilityFilter);
            }
            renderPipelineCandidates(filtered);
        }

        async function loadPipelines() {
            try {
                const response = await axios.get(endpoints.pipelines);
                if (!response.data.success) throw new Error(response.data.message || 'Unable to load pipelines');
                state.pipelines = response.data.data.pipelines || [];
                state.pipelineSummary = response.data.data.summary || null;
                populatePipelineSelector(state.pipelines);
                updatePipelineSummary(state.pipelineSummary);
            } catch (error) {
                console.error(error);
                showToast('error', error.response?.data?.message || 'Failed to load pipelines');
            }
        }

        function updatePipelineMeta(pipeline, metrics) {
            if (!pipeline) return;
            elements.selectedPipelineTitle.textContent = pipeline.pipeline_name;
            elements.pipelineDescription.textContent = pipeline.role_description || 'No description provided for this pipeline yet.';
            elements.pipelineTargetRole.textContent = pipeline.target_role || '—';
            elements.pipelineTargetSize.textContent = pipeline.target_pipeline_size ? `${pipeline.target_pipeline_size} candidates` : '—';
            elements.pipelineCurrentSize.textContent = pipeline.current_pipeline_size ? `${pipeline.current_pipeline_size} candidates` : '—';
            elements.pipelineNextHire.textContent = formatDate(pipeline.next_projected_hire_date) || '—';
            const status = metrics.health_status || pipeline.health_status || 'fair';
            const statusStyle = statusStyles[status] || statusStyles.fair;
            elements.pipelineStatusBadge.textContent = statusStyle.label;
            elements.pipelineStatusBadge.className = `text-xs px-3 py-1 rounded-full font-semibold ${statusStyle.classes}`;
            elements.pipelineStatusBadge.classList.remove('hidden');
            elements.pipelineHealthLabel.textContent = `${Math.round(pipeline.pipeline_health_score || 0)} / 100`;
        }

        async function loadPipelineDetails(pipelineId) {
            if (!pipelineId) return;
            try {
                const response = await axios.get(endpoints.pipeline(pipelineId));
                if (!response.data.success) throw new Error(response.data.message || 'Unable to load pipeline');
                const pipeline = response.data.data.pipeline;
                const metrics = response.data.data.health_metrics || {};
                state.selectedPipeline = pipeline;
                state.selectedPipelineId = pipelineId;
                updatePipelineMeta(pipeline, metrics);
                renderPipelineHealthGauge(pipeline.pipeline_health_score || 0);
                renderStageDistribution(mapStageCounts(pipeline.candidates || []));
                renderFollowUps(pipeline.candidates || []);
                elements.averageMatchScoreValue.textContent = `${Math.round(averageMetric(pipeline.candidates || [], 'match_score'))}%`;
                elements.averageDnaScoreValue.textContent = `${Math.round(averageMetric(pipeline.candidates || [], 'dna_compatibility_score'))}%`;
                filterCandidates();
            } catch (error) {
                console.error(error);
                showToast('error', error.response?.data?.message || 'Failed to load pipeline details');
            }
        }

        function renderSilverMedalists(list) {
            const reasonFilter = elements.silverReasonFilter.value;
            const search = elements.silverSearch.value?.toLowerCase() || '';
            let filtered = [...list];
            if (reasonFilter) {
                filtered = filtered.filter((item) => item.silver_medal_reason === reasonFilter);
            }
            if (search) {
                filtered = filtered.filter((item) => [
                    item.user?.name,
                    item.job?.title,
                    item.job?.company?.name
                ].some((field) => field && field.toLowerCase().includes(search)));
            }
            elements.silverTable.innerHTML = '';
            if (!filtered.length) {
                elements.silverEmptyState.classList.remove('hidden');
                return;
            }
            elements.silverEmptyState.classList.add('hidden');
            filtered.forEach((item) => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50';
                row.innerHTML = `
                    <td class="px-4 py-4">
                        <div class="flex flex-col">
                            <span class="text-sm font-semibold text-gray-900">${item.user?.name || 'Candidate'}</span>
                            <span class="text-xs text-gray-500">${item.user?.current_title || 'Role unknown'}</span>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-600">${item.job?.title || '—'}</td>
                    <td class="px-4 py-4 text-sm text-gray-600 capitalize">${(item.silver_medal_reason || '—').replace(/_/g, ' ')}</td>
                    <td class="px-4 py-4">
                        <div class="flex flex-col">
                            <span class="text-sm font-semibold text-gray-900">${Math.round(item.overall_score || 0)}%</span>
                            <span class="text-xs text-gray-500">Next touchpoint: ${formatDate(item.next_reach_out_date)}</span>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-right">
                        <div class="flex flex-col items-end gap-2">
                            <button type="button" class="inline-flex items-center px-3 py-2 text-xs font-semibold text-white bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg" data-action="convert-silver" data-silver-id="${item.id}">
                                Convert to pipeline
                            </button>
                            <button type="button" class="inline-flex items-center px-3 py-2 text-xs font-semibold text-blue-600 bg-blue-50 rounded-lg" data-action="view-journey" data-user-id="${item.user_id}" data-user-name="${item.user?.name || 'Candidate'}">
                                View journey
                            </button>
                        </div>
                    </td>
                `;
                elements.silverTable.appendChild(row);
            });
        }

        async function loadSilverMedalists() {
            try {
                const response = await axios.get(endpoints.silverMedalists);
                if (!response.data.success) throw new Error(response.data.message || 'Unable to load silver medalists');
                state.silverMedalists = response.data.data.silver_medalists || [];
                elements.silverReadyCount.textContent = formatNumber(response.data.data.ready_for_engagement || 0);
                elements.silverHighPotentialCount.textContent = formatNumber(response.data.data.high_potential || 0);
                elements.silverTotalCount.textContent = formatNumber(response.data.data.total_count || state.silverMedalists.length);
                renderSilverMedalists(state.silverMedalists);
            } catch (error) {
                console.error(error);
                showToast('error', error.response?.data?.message || 'Failed to load silver medalists');
            }
        }

        function openConvertModal(silverId) {
            elements.convertForm.setAttribute('data-silver-id', silverId);
            elements.convertPipelineSelect.innerHTML = state.pipelines.map((pipeline) => `
                <option value="${pipeline.id}">${pipeline.pipeline_name} · ${pipeline.target_role}</option>
            `).join('');
            elements.convertModal.classList.remove('hidden');
        }

        function closeConvertModal() {
            elements.convertModal.classList.add('hidden');
            elements.convertForm.removeAttribute('data-silver-id');
        }

        async function submitConvert(event) {
            event.preventDefault();
            const silverId = elements.convertForm.getAttribute('data-silver-id');
            const pipelineId = elements.convertPipelineSelect.value;
            if (!silverId || !pipelineId) {
                showToast('error', 'Select a pipeline to convert the candidate.');
                return;
            }
            try {
                await axios.post(endpoints.convertSilver(silverId), { pipeline_id: pipelineId });
                showToast('success', 'Candidate moved into talent pipeline.');
                closeConvertModal();
                await Promise.all([loadSilverMedalists(), loadPipelineDetails(pipelineId)]);
            } catch (error) {
                console.error(error);
                showToast('error', error.response?.data?.message || 'Conversion failed');
            }
        }

        function normalizePassiveCandidate(item) {
            if (!item) {
                return null;
            }
            const profileId = item.id || item.profile_id || null;
            const user = item.user || item.user_data || null;
            const name = user?.name || item.name || 'Candidate';
            const role = user?.profile?.current_title || item.current_role || user?.current_title || 'Current role not provided';
            const dnaScore = Number(item.dna_match_score ?? item.dna_match ?? item.score ?? 0);
            const readiness = item.engagement_readiness || item.readiness || null;
            const userId = user?.id || item.user_id || null;
            return {
                profileId,
                userId,
                name,
                role,
                dnaScore,
                readiness,
                source: profileId ? 'profile' : 'discovery'
            };
        }

        function renderPassiveCandidates(list, isDiscovery = false) {
            elements.passiveCandidatesList.innerHTML = '';
            const normalized = list.map(normalizePassiveCandidate).filter(Boolean);
            if (!normalized.length) {
                elements.passiveCandidatesList.innerHTML = `<p class="text-sm text-gray-500">${isDiscovery ? 'No new passive talent surfaced in this discovery run.' : 'No passive candidates ready at this moment.'}</p>`;
                return;
            }

            normalized.forEach((candidate) => {
                const card = document.createElement('div');
                const readinessBadge = candidate.source === 'profile' && candidate.readiness
                    ? `<span class="text-xs px-3 py-1 rounded-full bg-blue-100 text-blue-600 font-semibold">${candidate.readiness.replace(/_/g, ' ')}</span>`
                    : '<span class="text-xs px-3 py-1 rounded-full bg-gray-100 text-gray-600 font-semibold">Discovery suggestion</span>';
                const actionButtons = candidate.profileId
                    ? `
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="button" data-action="view-strategy" data-profile-id="${candidate.profileId}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg">
                                Engagement strategy
                            </button>
                            <button type="button" data-action="engage-passive" data-profile-id="${candidate.profileId}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-blue-600 bg-blue-50 rounded-lg">
                                Mark engaged
                            </button>
                        </div>
                    `
                    : '<p class="mt-3 text-xs text-gray-500">Review candidate profile and create a passive record to initiate outreach.</p>';

                card.className = 'border border-blue-100 rounded-xl bg-white px-4 py-3 shadow-sm';
                card.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">${candidate.name}</p>
                            <p class="text-xs text-gray-500">${candidate.role}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            ${readinessBadge}
                            <span class="text-xs px-3 py-1 rounded-full bg-blue-100 text-blue-600 font-semibold">${Math.round(candidate.dnaScore)}% DNA</span>
                        </div>
                    </div>
                    ${actionButtons}
                `;
                if (candidate.profileId) {
                    card.querySelectorAll('[data-action="view-strategy"], [data-action="engage-passive"]').forEach((button) => {
                        button.dataset.userId = candidate.userId || '';
                    });
                }
                elements.passiveCandidatesList.appendChild(card);
            });
        }

        async function loadPassiveCandidates() {
            try {
                const response = await axios.get(endpoints.passiveReady);
                if (!response.data.success) throw new Error(response.data.message || 'Unable to load passive candidates');
                state.passiveCandidates = response.data.data.candidates || [];
                state.passiveMetrics = response.data.data.metrics || {};
                elements.passiveReadyCount.textContent = formatNumber(state.passiveMetrics.ready_for_engagement || state.passiveCandidates.length || 0);
                elements.passiveMonitoringCount.textContent = formatNumber(state.passiveMetrics.by_readiness?.monitor || 0);
                elements.passiveHighAlignment.textContent = formatNumber(state.passiveMetrics.high_dna_matches || 0);
                renderPassiveCandidates(state.passiveCandidates);
            } catch (error) {
                console.error(error);
                showToast('error', error.response?.data?.message || 'Failed to load passive candidates');
            }
        }

        async function discoverPassiveCandidates(event) {
            event.preventDefault();
            if (!state.selectedPipelineId) {
                showToast('error', 'Pick a pipeline before running passive discovery.');
                return;
            }
            const limit = Number(elements.discoverLimit.value) || 20;
            try {
                const response = await axios.post(endpoints.passiveDiscover, {
                    pipeline_id: state.selectedPipelineId,
                    limit
                });
                if (!response.data.success) throw new Error(response.data.message || 'Discovery failed');
                state.discoveryResults = response.data.data.candidates || [];
                showToast('success', `Discovered ${response.data.data.total_discovered || state.discoveryResults.length} passive candidates.`);
                renderPassiveCandidates(state.discoveryResults, true);
            } catch (error) {
                console.error(error);
                showToast('error', error.response?.data?.message || 'Passive discovery failed');
            }
        }

        async function generateEngagementStrategy(profileId) {
            try {
                elements.engagementStrategyPanel.classList.remove('hidden');
                elements.strategyContent.textContent = 'Generating engagement strategy...';
                const response = await axios.post(endpoints.passiveStrategy(profileId));
                if (!response.data.success) throw new Error(response.data.message || 'Unable to generate strategy');
                const strategy = response.data.data;
                elements.strategyContent.textContent = strategy.recommendation || JSON.stringify(strategy, null, 2);
            } catch (error) {
                console.error(error);
                elements.strategyContent.textContent = 'An error occurred while generating the strategy.';
                showToast('error', error.response?.data?.message || 'Failed to generate strategy');
            }
        }

        async function markPassiveEngaged(profileId) {
            try {
                await axios.post(endpoints.passiveEngage(profileId), {
                    method: 'email',
                    message_sent: true
                });
                showToast('success', 'Engagement recorded.');
                await loadPassiveCandidates();
            } catch (error) {
                console.error(error);
                showToast('error', error.response?.data?.message || 'Unable to update engagement status');
            }
        }

        function renderJourneyTimeline(journey) {
            elements.journeyTimeline.innerHTML = '';
            if (!journey.timeline?.length) {
                elements.journeyTimeline.innerHTML = '<p class="text-sm text-gray-500">No recorded interactions for this candidate yet.</p>';
                return;
            }
            journey.timeline.forEach((step) => {
                const item = document.createElement('div');
                item.className = 'relative pl-6';
                item.innerHTML = `
                    <span class="absolute left-[-9px] top-2 h-4 w-4 rounded-full ${step.sentiment === 'positive' ? 'bg-emerald-400' : step.sentiment === 'negative' ? 'bg-red-400' : 'bg-blue-400'}"></span>
                    <div class="bg-white border border-purple-100 rounded-xl px-4 py-3 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-900 capitalize">${step.type.replace(/_/g, ' ')}</p>
                            <span class="text-xs text-gray-500">${formatDate(step.date)}</span>
                        </div>
                        <p class="mt-2 text-sm text-gray-600">${step.summary || 'No summary provided.'}</p>
                        <p class="mt-1 text-xs text-gray-500">${step.automated ? 'Automated touchpoint' : 'Human touchpoint'} · Sentiment: ${step.sentiment || 'neutral'}</p>
                    </div>
                `;
                elements.journeyTimeline.appendChild(item);
            });
        }

        async function loadCandidateJourney(userId, candidateName) {
            if (!userId) return;
            try {
                const response = await axios.get(endpoints.candidateExperience(userId));
                if (!response.data.success) throw new Error(response.data.message || 'Unable to load candidate journey');
                const journey = response.data.data;
                state.candidateJourney = journey;
                state.selectedCandidateName = candidateName;
                elements.candidateJourneyEmpty.classList.add('hidden');
                elements.candidateJourneyContainer.classList.remove('hidden');
                elements.journeyCandidateName.textContent = candidateName;
                elements.journeyInteractionCount.textContent = formatNumber(journey.total_interactions || 0);
                elements.journeyAverageResponse.textContent = journey.response_time_avg ? journey.response_time_avg.toFixed(1) : '--';
                renderJourneyTimeline(journey);
                renderJourneySentiment(journey.sentiment_breakdown || {});
            } catch (error) {
                console.error(error);
                showToast('error', error.response?.data?.message || 'Failed to load candidate journey');
            }
        }

        async function advanceCandidateStage(candidateId, newStage) {
            try {
                await axios.put(endpoints.advanceCandidate(candidateId), {
                    new_stage: newStage
                });
                showToast('success', 'Candidate stage updated.');
                if (state.selectedPipelineId) {
                    await loadPipelineDetails(state.selectedPipelineId);
                }
            } catch (error) {
                console.error(error);
                showToast('error', error.response?.data?.message || 'Failed to update candidate stage');
            }
        }

        async function loadEmployerBrand() {
            try {
                const response = await axios.get(endpoints.employerBrand);
                if (!response.data.success) throw new Error(response.data.message || 'Unable to load employer brand metrics');
                renderEmployerBrand(response.data.data);
            } catch (error) {
                console.error(error);
                showToast('error', error.response?.data?.message || 'Failed to load employer brand metrics');
            }
        }

        function renderEmployerBrand(metrics) {
            if (!metrics) return;
            elements.brandOverallValue.textContent = Math.round(metrics.overall_brand_score || 0);
            const status = metrics.brand_health_status || 'fair';
            const statusStyle = statusStyles[status] || statusStyles.fair;
            elements.brandHealthBadge.textContent = statusStyle.label;
            elements.brandHealthBadge.className = `mt-2 inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ${statusStyle.classes}`;
            elements.brandTrendValue.textContent = (metrics.trend || 'stable').replace(/_/g, ' ');
            elements.brandNpsValue.textContent = metrics.nps_score || 0;
            elements.brandFeedbackValue.textContent = metrics.total_feedback_submissions || 0;
            elements.brandRiskList.innerHTML = '';
            if (!metrics.identified_risks?.length) {
                elements.brandRiskList.innerHTML = '<li class="text-sm text-gray-500">No risks detected.</li>';
            } else {
                metrics.identified_risks.forEach((risk) => {
                    const item = document.createElement('li');
                    item.className = 'flex items-start gap-2';
                    item.innerHTML = `
                        <span class="mt-1 h-2 w-2 rounded-full bg-red-500"></span>
                        <span>${risk}</span>
                    `;
                    elements.brandRiskList.appendChild(item);
                });
            }
            renderBrandComponents(metrics.component_scores || {});
            renderBrandSentiment(metrics);
        }

        async function recalculateBrandScore(event) {
            event.preventDefault();
            try {
                const payload = {};
                if (elements.brandStartDate.value) payload.start_date = elements.brandStartDate.value;
                if (elements.brandEndDate.value) payload.end_date = elements.brandEndDate.value;
                const response = await axios.post(endpoints.employerBrandCalculate, payload);
                if (!response.data.success) throw new Error(response.data.message || 'Unable to recalculate brand score');
                showToast('success', 'Employer brand score recalculated.');
                renderEmployerBrand(response.data.data);
            } catch (error) {
                console.error(error);
                showToast('error', error.response?.data?.message || 'Failed to recalculate brand score');
            }
        }

        async function executePipelineMatch() {
            if (!state.selectedPipelineId) {
                showToast('error', 'Select a pipeline before running match recommendations.');
                return;
            }
            const jobId = prompt('Enter the job ID to match against:');
            if (!jobId) return;
            try {
                const response = await axios.post(endpoints.matchToJob, { job_id: jobId });
                if (!response.data.success) throw new Error(response.data.message || 'Matching failed');
                const total = response.data.data.total_matches || 0;
                const immediate = response.data.data.immediate_contact || 0;
                showToast('success', `Found ${total} matches. ${immediate} ready for immediate contact.`);
            } catch (error) {
                console.error(error);
                showToast('error', error.response?.data?.message || 'Unable to match candidates to job');
            }
        }

        function attachEventListeners() {
            elements.refreshButton.addEventListener('click', async () => {
                showToast('info', 'Refreshing dashboard data...');
                await Promise.all([
                    loadPipelines(),
                    loadSilverMedalists(),
                    loadPassiveCandidates(),
                    loadEmployerBrand()
                ]);
                if (state.selectedPipelineId) {
                    await loadPipelineDetails(state.selectedPipelineId);
                }
            });

            elements.pipelineSelector.addEventListener('change', async (event) => {
                const pipelineId = event.target.value;
                if (!pipelineId) return;
                await loadPipelineDetails(pipelineId);
                await loadPassiveCandidates();
            });

            elements.candidateStageFilter.addEventListener('change', filterCandidates);
            elements.candidateAvailabilityFilter.addEventListener('change', filterCandidates);
            elements.silverReasonFilter.addEventListener('change', () => renderSilverMedalists(state.silverMedalists));
            elements.silverSearch.addEventListener('input', () => renderSilverMedalists(state.silverMedalists));

            document.addEventListener('click', async (event) => {
                const action = event.target.getAttribute('data-action');
                if (!action && !event.target.closest('[data-action]')) return;
                const target = event.target.closest('[data-action]');
                const actionType = target.getAttribute('data-action');
                if (actionType === 'convert-silver') {
                    openConvertModal(target.getAttribute('data-silver-id'));
                } else if (actionType === 'view-journey') {
                    await loadCandidateJourney(target.getAttribute('data-user-id'), target.getAttribute('data-user-name'));
                } else if (actionType === 'advance-stage') {
                    const candidateId = target.getAttribute('data-candidate-id');
                    const select = document.querySelector(`select[data-candidate-id="${candidateId}"]`);
                    const newStage = select?.value;
                    if (newStage) {
                        await advanceCandidateStage(candidateId, newStage);
                    }
                } else if (actionType === 'view-strategy') {
                    await generateEngagementStrategy(target.getAttribute('data-profile-id'));
                } else if (actionType === 'engage-passive') {
                    await markPassiveEngaged(target.getAttribute('data-profile-id'));
                }
            });

            elements.convertForm.addEventListener('submit', submitConvert);
            elements.closeConvertModal.addEventListener('click', closeConvertModal);
            elements.cancelConvert.addEventListener('click', closeConvertModal);
            elements.closeStrategyPanel.addEventListener('click', () => elements.engagementStrategyPanel.classList.add('hidden'));
            elements.discoverPassiveBtn.addEventListener('click', () => elements.discoverPassiveForm.classList.toggle('hidden'));
            elements.discoverPassiveForm.addEventListener('submit', discoverPassiveCandidates);
            elements.brandPeriodForm.addEventListener('submit', recalculateBrandScore);
            elements.viewPipelineMatches.addEventListener('click', executePipelineMatch);
        }

        (async function init() {
            attachEventListeners();
            await Promise.all([
                loadPipelines(),
                loadSilverMedalists(),
                loadPassiveCandidates(),
                loadEmployerBrand()
            ]);
        })();
    });
</script>
@endsection
