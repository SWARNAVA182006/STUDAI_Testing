@extends('layouts.app')

@section('title', 'Bias Elimination & Ethical AI - S.C.O.U.T.')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-pink-50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 bg-clip-text text-transparent">
                        Bias Elimination & Ethical AI
                    </h1>
                    <p class="mt-2 text-lg text-gray-600">
                        Ensuring fair, unbiased, and transparent hiring decisions
                    </p>
                </div>
                
                <div class="flex space-x-3">
                    <button onclick="runFullAudit()" class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Run Full Audit</span>
                    </button>
                    
                    <button onclick="exportReport()" class="px-4 py-3 bg-white border-2 border-gray-200 text-gray-700 rounded-lg hover:border-purple-300 transition-all duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Tab Navigation --}}
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
            <div class="flex border-b border-gray-200">
                <button onclick="switchTab('overview')" id="tab-overview" class="tab-button active flex-1 px-6 py-4 text-center font-semibold transition-all duration-300">
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span>Overview</span>
                    </div>
                </button>
                <button onclick="switchTab('fairness')" id="tab-fairness" class="tab-button flex-1 px-6 py-4 text-center font-semibold transition-all duration-300">
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                        </svg>
                        <span>Fairness Metrics</span>
                    </div>
                </button>
                <button onclick="switchTab('alerts')" id="tab-alerts" class="tab-button flex-1 px-6 py-4 text-center font-semibold transition-all duration-300">
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Proxy Alerts</span>
                        <span id="alert-badge" class="hidden px-2 py-1 text-xs bg-red-500 text-white rounded-full">0</span>
                    </div>
                </button>
                <button onclick="switchTab('diversity')" id="tab-diversity" class="tab-button flex-1 px-6 py-4 text-center font-semibold transition-all duration-300">
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Diversity Analytics</span>
                    </div>
                </button>
                <button onclick="switchTab('audits')" id="tab-audits" class="tab-button flex-1 px-6 py-4 text-center font-semibold transition-all duration-300">
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Audit History</span>
                    </div>
                </button>
            </div>
        </div>

        {{-- Tab Content --}}
        <div id="tab-content">
            
            {{-- Overview Tab --}}
            <div id="content-overview" class="tab-content active">
                
                {{-- Key Metrics Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="glass-card p-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 font-medium">Overall Fairness</p>
                                <p class="text-3xl font-bold text-green-600 mt-2" id="overall-fairness">Excellent</p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="flex items-center space-x-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2">
                                    <div id="fairness-progress" class="bg-green-600 h-2 rounded-full" style="width: 92%"></div>
                                </div>
                                <span class="text-sm font-semibold text-gray-700" id="fairness-score">92%</span>
                            </div>
                        </div>
                    </div>

                    <div class="glass-card p-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 font-medium">Bias Score</p>
                                <p class="text-3xl font-bold text-blue-600 mt-2" id="bias-score">8%</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Lower is better • Target: <10%</p>
                    </div>

                    <div class="glass-card p-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 font-medium">Active Alerts</p>
                                <p class="text-3xl font-bold text-yellow-600 mt-2" id="active-alerts">3</p>
                            </div>
                            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Proxy discrimination warnings</p>
                    </div>

                    <div class="glass-card p-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 font-medium">Anonymized</p>
                                <p class="text-3xl font-bold text-purple-600 mt-2" id="anonymized-count">1,247</p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Candidate screenings</p>
                    </div>
                </div>

                {{-- Charts Row --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    {{-- Fairness Trend Chart --}}
                    <div class="glass-card p-6 rounded-xl shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Fairness Trend (6 Months)</h3>
                        <canvas id="fairnessTrendChart" height="250"></canvas>
                    </div>

                    {{-- Disparate Impact Analysis --}}
                    <div class="glass-card p-6 rounded-xl shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Disparate Impact Analysis</h3>
                        <canvas id="disparateImpactChart" height="250"></canvas>
                    </div>
                </div>

                {{-- Recent Audits Timeline --}}
                <div class="glass-card p-6 rounded-xl shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6">Recent Audit Activities</h3>
                    <div class="space-y-4" id="recent-audits">
                        <div class="flex items-start space-x-4 p-4 bg-green-50 rounded-lg border-l-4 border-green-500">
                            <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-800">Bias Audit Completed</p>
                                <p class="text-sm text-gray-600">Analyzed 234 applications • Fairness Rating: Excellent</p>
                                <p class="text-xs text-gray-500 mt-1">2 hours ago</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-4 p-4 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-800">Candidate Anonymization</p>
                                <p class="text-sm text-gray-600">45 new applications anonymized for screening</p>
                                <p class="text-xs text-gray-500 mt-1">5 hours ago</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-4 p-4 bg-yellow-50 rounded-lg border-l-4 border-yellow-500">
                            <div class="w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-800">Proxy Discrimination Alert</p>
                                <p class="text-sm text-gray-600">Geographic bias detected in university selection criteria</p>
                                <p class="text-xs text-gray-500 mt-1">1 day ago</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Fairness Metrics Tab --}}
            <div id="content-fairness" class="tab-content hidden">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <div class="glass-card p-6 rounded-xl shadow-lg">
                        <h4 class="font-semibold text-gray-700 mb-2">Selection Rate Parity</h4>
                        <p class="text-3xl font-bold text-green-600">0.87</p>
                        <p class="text-sm text-gray-600 mt-2">Target: ≥0.80 (4/5ths rule)</p>
                        <div class="mt-4 flex items-center space-x-2">
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">Passing</span>
                        </div>
                    </div>

                    <div class="glass-card p-6 rounded-xl shadow-lg">
                        <h4 class="font-semibold text-gray-700 mb-2">Advancement Rate</h4>
                        <p class="text-3xl font-bold text-blue-600">0.92</p>
                        <p class="text-sm text-gray-600 mt-2">Consistency across groups</p>
                        <div class="mt-4 flex items-center space-x-2">
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">Excellent</span>
                        </div>
                    </div>

                    <div class="glass-card p-6 rounded-xl shadow-lg">
                        <h4 class="font-semibold text-gray-700 mb-2">Offer Rate Consistency</h4>
                        <p class="text-3xl font-bold text-purple-600">0.88</p>
                        <p class="text-sm text-gray-600 mt-2">Balanced offer distribution</p>
                        <div class="mt-4 flex items-center space-x-2">
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">Good</span>
                        </div>
                    </div>
                </div>

                <div class="glass-card p-6 rounded-xl shadow-lg mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Fairness Metrics by Criterion</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metric Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Threshold</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sample Size</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="fairness-metrics-table">
                                {{-- Dynamically populated --}}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="glass-card p-6 rounded-xl shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Statistical Significance Analysis</h3>
                    <canvas id="significanceChart" height="300"></canvas>
                </div>
            </div>

            {{-- Proxy Alerts Tab --}}
            <div id="content-alerts" class="tab-content hidden">
                <div class="mb-6 flex items-center justify-between">
                    <div class="flex space-x-3">
                        <button onclick="filterAlerts('all')" class="filter-btn active px-4 py-2 rounded-lg">All Alerts</button>
                        <button onclick="filterAlerts('critical')" class="filter-btn px-4 py-2 rounded-lg">Critical</button>
                        <button onclick="filterAlerts('pending')" class="filter-btn px-4 py-2 rounded-lg">Pending Review</button>
                        <button onclick="filterAlerts('resolved')" class="filter-btn px-4 py-2 rounded-lg">Resolved</button>
                    </div>
                </div>

                <div class="space-y-4" id="proxy-alerts-list">
                    {{-- Critical Alert Example --}}
                    <div class="glass-card p-6 rounded-xl shadow-lg border-l-4 border-red-500">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-bold">CRITICAL</span>
                                    <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-semibold">Geographic Bias</span>
                                    <span class="text-xs text-gray-500">Detected 2 days ago</span>
                                </div>
                                <h4 class="font-semibold text-gray-800 text-lg">Zip Code Correlation with Hiring Decisions</h4>
                                <p class="text-sm text-gray-600 mt-2">
                                    Strong correlation (87%) detected between candidate zip codes and rejection rates. 
                                    This may indicate unintentional geographic bias in screening.
                                </p>
                                <div class="mt-4 grid grid-cols-3 gap-4">
                                    <div>
                                        <p class="text-xs text-gray-500">Correlation Strength</p>
                                        <p class="text-lg font-bold text-red-600">87%</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Affected Applications</p>
                                        <p class="text-lg font-bold text-gray-800">142</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Priority Score</p>
                                        <p class="text-lg font-bold text-gray-800">348</p>
                                    </div>
                                </div>
                                <div class="mt-4 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                                    <p class="text-sm font-semibold text-yellow-800 mb-2">Recommendation:</p>
                                    <p class="text-sm text-yellow-700">
                                        Review screening criteria to remove location-based factors. Consider implementing 
                                        strict anonymization to eliminate geographic identifiers from initial evaluation.
                                    </p>
                                </div>
                            </div>
                            <div class="ml-4 flex flex-col space-y-2">
                                <button onclick="investigateAlert(1)" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                                    Investigate
                                </button>
                                <button onclick="resolveAlert(1)" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                                    Resolve
                                </button>
                                <button onclick="dismissAlert(1)" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">
                                    False Positive
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Medium Alert Example --}}
                    <div class="glass-card p-6 rounded-xl shadow-lg border-l-4 border-yellow-500">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-bold">MEDIUM</span>
                                    <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-xs font-semibold">Socioeconomic Proxy</span>
                                    <span class="text-xs text-gray-500">Detected 5 days ago</span>
                                </div>
                                <h4 class="font-semibold text-gray-800 text-lg">University Prestige Weighting</h4>
                                <p class="text-sm text-gray-600 mt-2">
                                    Moderate correlation (62%) between university tier and advancement to interview stage. 
                                    May inadvertently favor candidates from privileged backgrounds.
                                </p>
                                <div class="mt-4 grid grid-cols-3 gap-4">
                                    <div>
                                        <p class="text-xs text-gray-500">Correlation Strength</p>
                                        <p class="text-lg font-bold text-yellow-600">62%</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Affected Applications</p>
                                        <p class="text-lg font-bold text-gray-800">78</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Priority Score</p>
                                        <p class="text-lg font-bold text-gray-800">124</p>
                                    </div>
                                </div>
                            </div>
                            <div class="ml-4 flex flex-col space-y-2">
                                <button onclick="investigateAlert(2)" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                                    Investigate
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Diversity Analytics Tab --}}
            <div id="content-diversity" class="tab-content hidden">
                <div class="mb-6 p-4 bg-purple-50 rounded-lg border border-purple-200">
                    <div class="flex items-start space-x-3">
                        <svg class="w-6 h-6 text-purple-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <div class="flex-1">
                            <p class="font-semibold text-purple-900">Privacy-Preserving Analytics</p>
                            <p class="text-sm text-purple-700 mt-1">
                                All data is aggregated with a minimum group size of 10 to protect individual privacy. 
                                Groups smaller than 10 are not reported.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="glass-card p-6 rounded-xl shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Hiring Funnel Diversity</h3>
                        <canvas id="funnelDiversityChart" height="300"></canvas>
                    </div>

                    <div class="glass-card p-6 rounded-xl shadow-lg">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Role Distribution</h3>
                        <canvas id="roleDistributionChart" height="300"></canvas>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <div class="glass-card p-6 rounded-xl shadow-lg">
                        <h4 class="font-semibold text-gray-700 mb-4">Pay Equity Score</h4>
                        <div class="relative pt-1">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold inline-block text-purple-600">0%</span>
                                <span class="text-xs font-semibold inline-block text-purple-600">100%</span>
                            </div>
                            <div class="flex h-2 mb-4 overflow-hidden bg-gray-200 rounded">
                                <div style="width: 92%" class="flex flex-col justify-center bg-gradient-to-r from-purple-500 to-pink-500"></div>
                            </div>
                            <p class="text-3xl font-bold text-center text-purple-600">92%</p>
                            <p class="text-sm text-center text-gray-600 mt-2">Excellent equity across roles</p>
                        </div>
                    </div>

                    <div class="glass-card p-6 rounded-xl shadow-lg">
                        <h4 class="font-semibold text-gray-700 mb-4">Retention Consistency</h4>
                        <div class="relative pt-1">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold inline-block text-blue-600">0%</span>
                                <span class="text-xs font-semibold inline-block text-blue-600">100%</span>
                            </div>
                            <div class="flex h-2 mb-4 overflow-hidden bg-gray-200 rounded">
                                <div style="width: 88%" class="flex flex-col justify-center bg-gradient-to-r from-blue-500 to-cyan-500"></div>
                            </div>
                            <p class="text-3xl font-bold text-center text-blue-600">88%</p>
                            <p class="text-sm text-center text-gray-600 mt-2">Balanced retention rates</p>
                        </div>
                    </div>

                    <div class="glass-card p-6 rounded-xl shadow-lg">
                        <h4 class="font-semibold text-gray-700 mb-4">Inclusion Index</h4>
                        <div class="relative pt-1">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold inline-block text-green-600">0%</span>
                                <span class="text-xs font-semibold inline-block text-green-600">100%</span>
                            </div>
                            <div class="flex h-2 mb-4 overflow-hidden bg-gray-200 rounded">
                                <div style="width: 85%" class="flex flex-col justify-center bg-gradient-to-r from-green-500 to-emerald-500"></div>
                            </div>
                            <p class="text-3xl font-bold text-center text-green-600">85%</p>
                            <p class="text-sm text-center text-gray-600 mt-2">Strong inclusive practices</p>
                        </div>
                    </div>
                </div>

                <div class="glass-card p-6 rounded-xl shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Representation Trends (12 Months)</h3>
                    <canvas id="representationTrendsChart" height="250"></canvas>
                </div>
            </div>

            {{-- Audit History Tab --}}
            <div id="content-audits" class="tab-content hidden">
                <div class="glass-card p-6 rounded-xl shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-800 mb-6">Bias Audit History</h3>
                    <div class="space-y-6" id="audit-history-list">
                        {{-- Audit entries will be populated here --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Loading Overlay --}}
<div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl p-8 shadow-2xl">
        <div class="flex flex-col items-center space-y-4">
            <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-purple-600"></div>
            <p class="text-lg font-semibold text-gray-800" id="loading-message">Processing...</p>
        </div>
    </div>
</div>

@push('styles')
<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .tab-button {
        color: #6b7280;
        border-bottom: 3px solid transparent;
    }

    .tab-button.active {
        color: #9333ea;
        border-bottom-color: #9333ea;
        background: linear-gradient(to bottom, rgba(147, 51, 234, 0.05), transparent);
    }

    .tab-button:hover:not(.active) {
        color: #4b5563;
        background: rgba(243, 244, 246, 0.5);
    }

    .filter-btn {
        background: white;
        border: 2px solid #e5e7eb;
        color: #6b7280;
        transition: all 0.3s;
    }

    .filter-btn.active {
        background: linear-gradient(to right, #9333ea, #ec4899);
        border-color: #9333ea;
        color: white;
    }

    .filter-btn:hover:not(.active) {
        border-color: #d1d5db;
        background: #f9fafb;
    }

    .tab-content {
        animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Tab switching
    function switchTab(tabName) {
        // Hide all content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
            content.classList.remove('active');
        });
        
        // Remove active from all buttons
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
        });
        
        // Show selected content
        document.getElementById('content-' + tabName).classList.remove('hidden');
        document.getElementById('content-' + tabName).classList.add('active');
        document.getElementById('tab-' + tabName).classList.add('active');
        
        // Load data for the tab
        loadTabData(tabName);
    }

    // Filter alerts
    function filterAlerts(filter) {
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        // Implement filtering logic
    }

    // Load tab data
    function loadTabData(tabName) {
        switch(tabName) {
            case 'fairness':
                loadFairnessMetrics();
                break;
            case 'alerts':
                loadProxyAlerts();
                break;
            case 'diversity':
                loadDiversityAnalytics();
                break;
            case 'audits':
                loadAuditHistory();
                break;
        }
    }

    // Run full audit
    async function runFullAudit() {
        showLoading('Running comprehensive bias audit...');
        
        try {
            const response = await fetch('/api/scout/bias/audit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('api_token')}`
                },
                body: JSON.stringify({
                    timeframe: '6_months'
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast('Bias audit completed successfully', 'success');
                refreshDashboard();
            } else {
                showToast(result.message || 'Audit failed', 'error');
            }
        } catch (error) {
            console.error('Audit error:', error);
            showToast('Failed to run audit', 'error');
        } finally {
            hideLoading();
        }
    }

    // Load fairness metrics
    async function loadFairnessMetrics() {
        try {
            const response = await fetch('/api/scout/bias/metrics', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('api_token')}`
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                renderFairnessMetricsTable(result.data.metrics);
                renderSignificanceChart(result.data.metrics);
            }
        } catch (error) {
            console.error('Error loading fairness metrics:', error);
        }
    }

    // Load proxy alerts
    async function loadProxyAlerts() {
        try {
            const response = await fetch('/api/scout/bias/alerts', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('api_token')}`
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                document.getElementById('alert-badge').textContent = result.data.unresolved_count;
                document.getElementById('alert-badge').classList.toggle('hidden', result.data.unresolved_count === 0);
            }
        } catch (error) {
            console.error('Error loading alerts:', error);
        }
    }

    // Load diversity analytics
    async function loadDiversityAnalytics() {
        try {
            const response = await fetch('/api/scout/bias/diversity', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('api_token')}`
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                renderDiversityCharts(result.data);
            }
        } catch (error) {
            console.error('Error loading diversity analytics:', error);
        }
    }

    // Charts initialization
    function initCharts() {
        // Fairness Trend Chart
        new Chart(document.getElementById('fairnessTrendChart'), {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Fairness Score',
                    data: [88, 90, 89, 92, 91, 92],
                    borderColor: '#9333ea',
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
                    y: { beginAtZero: false, min: 80, max: 100 }
                }
            }
        });

        // Disparate Impact Chart
        new Chart(document.getElementById('disparateImpactChart'), {
            type: 'bar',
            data: {
                labels: ['Selection Rate', 'Interview Rate', 'Offer Rate', 'Rejection Rate'],
                datasets: [{
                    label: 'Impact Ratio',
                    data: [0.87, 0.92, 0.88, 0.85],
                    backgroundColor: ['#10b981', '#3b82f6', '#8b5cf6', '#ec4899']
                }, {
                    label: 'Threshold',
                    data: [0.8, 0.8, 0.8, 0.8],
                    type: 'line',
                    borderColor: '#ef4444',
                    borderDash: [5, 5],
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: false, min: 0.5, max: 1.0 }
                }
            }
        });
    }

    // Utility functions
    function showLoading(message = 'Processing...') {
        document.getElementById('loading-message').textContent = message;
        document.getElementById('loading-overlay').classList.remove('hidden');
    }

    function hideLoading() {
        document.getElementById('loading-overlay').classList.add('hidden');
    }

    function showToast(message, type = 'info') {
        // Implement toast notification
        console.log(`${type.toUpperCase()}: ${message}`);
    }

    function refreshDashboard() {
        loadTabData('overview');
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        initCharts();
        loadTabData('overview');
    });
</script>
@endpush
@endsection
