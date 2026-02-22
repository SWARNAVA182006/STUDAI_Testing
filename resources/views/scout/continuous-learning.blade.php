<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Continuous Learning & Optimization - S.C.O.U.T.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
        }
        
        .metric-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }
        
        .trend-up {
            color: #10b981;
        }
        
        .trend-down {
            color: #ef4444;
        }
        
        .trend-neutral {
            color: #6b7280;
        }
        
        .performance-excellent { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .performance-good { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        .performance-average { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .performance-poor { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        
        .prediction-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .prediction-high { background: #dcfce7; color: #166534; }
        .prediction-medium { background: #fef3c7; color: #92400e; }
        .prediction-low { background: #fee2e2; color: #991b1b; }
        
        .learning-animation {
            animation: pulse-learning 3s ease-in-out infinite;
        }
        
        @keyframes pulse-learning {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .refinement-progress {
            height: 8px;
            background: linear-gradient(90deg, #8b5cf6 0%, #ec4899 50%, #f59e0b 100%);
            border-radius: 4px;
            transition: width 0.5s ease-out;
        }
        
        .insight-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        
        .insight-card:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .hidden { display: none; }
        
        .tab-active {
            background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
            color: white;
        }
        
        .tab-inactive {
            background: #f3f4f6;
            color: #6b7280;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen p-6">
        <!-- Header -->
        <div class="max-w-7xl mx-auto mb-8">
            <div class="glass-panel rounded-xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">
                            <i data-lucide="brain-circuit" class="inline-block w-8 h-8 mr-2 text-purple-600"></i>
                            Continuous Learning & Optimization
                        </h1>
                        <p class="text-gray-600">S.C.O.U.T. evolves with every hiring decision, becoming smarter and more accurate over time</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-600 mb-1">Last Model Update</div>
                        <div class="text-xl font-bold text-purple-600" id="lastUpdateTime">Just now</div>
                        <button id="triggerRefinementBtn" class="mt-2 px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg text-sm font-semibold hover:shadow-lg transition-all">
                            <i data-lucide="refresh-cw" class="inline-block w-4 h-4 mr-1"></i>
                            Refine Model
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="max-w-7xl mx-auto mb-6">
            <div class="glass-panel rounded-xl p-2 flex gap-2">
                <button class="tab-btn flex-1 py-3 px-6 rounded-lg font-semibold transition-all tab-active" data-tab="overview">
                    <i data-lucide="layout-dashboard" class="inline-block w-5 h-5 mr-2"></i>
                    Overview
                </button>
                <button class="tab-btn flex-1 py-3 px-6 rounded-lg font-semibold transition-all tab-inactive" data-tab="performance">
                    <i data-lucide="trending-up" class="inline-block w-5 h-5 mr-2"></i>
                    Performance Tracking
                </button>
                <button class="tab-btn flex-1 py-3 px-6 rounded-lg font-semibold transition-all tab-inactive" data-tab="insights">
                    <i data-lucide="lightbulb" class="inline-block w-5 h-5 mr-2"></i>
                    Learning Insights
                </button>
                <button class="tab-btn flex-1 py-3 px-6 rounded-lg font-semibold transition-all tab-inactive" data-tab="predictions">
                    <i data-lucide="crystal-ball" class="inline-block w-5 h-5 mr-2"></i>
                    Talent Predictions
                </button>
            </div>
        </div>

        <!-- Overview Tab -->
        <div id="overviewTab" class="max-w-7xl mx-auto tab-content">
            <!-- Key Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="glass-panel rounded-xl p-6 metric-card">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-gray-600">Model Accuracy</div>
                        <i data-lucide="target" class="w-5 h-5 text-purple-600"></i>
                    </div>
                    <div class="text-3xl font-bold text-gray-900 mb-1">
                        <span id="modelAccuracy">87.3</span>%
                    </div>
                    <div class="flex items-center text-sm">
                        <i data-lucide="trending-up" class="w-4 h-4 mr-1 trend-up"></i>
                        <span class="trend-up">+4.2%</span>
                        <span class="text-gray-500 ml-1">vs last month</span>
                    </div>
                </div>

                <div class="glass-panel rounded-xl p-6 metric-card">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-gray-600">Hires Tracked</div>
                        <i data-lucide="users-round" class="w-5 h-5 text-blue-600"></i>
                    </div>
                    <div class="text-3xl font-bold text-gray-900 mb-1" id="hiresTracked">156</div>
                    <div class="flex items-center text-sm">
                        <i data-lucide="trending-up" class="w-4 h-4 mr-1 trend-up"></i>
                        <span class="trend-up">+23</span>
                        <span class="text-gray-500 ml-1">this month</span>
                    </div>
                </div>

                <div class="glass-panel rounded-xl p-6 metric-card">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-gray-600">Criteria Refined</div>
                        <i data-lucide="sliders-horizontal" class="w-5 h-5 text-green-600"></i>
                    </div>
                    <div class="text-3xl font-bold text-gray-900 mb-1" id="criteriaRefined">42</div>
                    <div class="flex items-center text-sm">
                        <span class="text-gray-600">Last refined:</span>
                        <span class="text-gray-900 ml-1 font-medium">2 days ago</span>
                    </div>
                </div>

                <div class="glass-panel rounded-xl p-6 metric-card">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm text-gray-600">Success Rate</div>
                        <i data-lucide="award" class="w-5 h-5 text-yellow-600"></i>
                    </div>
                    <div class="text-3xl font-bold text-gray-900 mb-1">
                        <span id="successRate">91.2</span>%
                    </div>
                    <div class="flex items-center text-sm">
                        <i data-lucide="trending-up" class="w-4 h-4 mr-1 trend-up"></i>
                        <span class="trend-up">+2.8%</span>
                        <span class="text-gray-500 ml-1">improvement</span>
                    </div>
                </div>
            </div>

            <!-- Learning Progress & Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="glass-panel rounded-xl p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <i data-lucide="chart-line" class="inline-block w-6 h-6 mr-2 text-purple-600"></i>
                        Prediction Accuracy Trend
                    </h3>
                    <canvas id="accuracyTrendChart" height="250"></canvas>
                </div>

                <div class="glass-panel rounded-xl p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <i data-lucide="pie-chart" class="inline-block w-6 h-6 mr-2 text-blue-600"></i>
                        Performance Distribution
                    </h3>
                    <canvas id="performanceDistChart" height="250"></canvas>
                </div>
            </div>

            <!-- Recent Learning Events -->
            <div class="glass-panel rounded-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">
                    <i data-lucide="activity" class="inline-block w-6 h-6 mr-2 text-green-600"></i>
                    Recent Learning Events
                </h3>
                <div id="recentEvents" class="space-y-3">
                    <!-- Events will be inserted here -->
                </div>
            </div>
        </div>

        <!-- Performance Tracking Tab -->
        <div id="performanceTab" class="max-w-7xl mx-auto tab-content hidden">
            <!-- Add Performance Record Form -->
            <div class="glass-panel rounded-xl p-6 mb-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">
                    <i data-lucide="user-check" class="inline-block w-6 h-6 mr-2 text-purple-600"></i>
                    Track Hire Performance
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Hire</label>
                        <select id="hireSelect" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <option value="">-- Select Hire --</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Review Period</label>
                        <select id="reviewPeriod" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <option value="30_days">30 Days</option>
                            <option value="60_days">60 Days</option>
                            <option value="90_days">90 Days</option>
                            <option value="6_months">6 Months</option>
                            <option value="1_year">1 Year</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Overall Performance</label>
                        <select id="performanceRating" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <option value="">-- Select Rating --</option>
                            <option value="excellent">Excellent (Exceeds Expectations)</option>
                            <option value="good">Good (Meets Expectations)</option>
                            <option value="average">Average (Needs Improvement)</option>
                            <option value="poor">Poor (Below Expectations)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Retention Status</label>
                        <select id="retentionStatus" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            <option value="active">Active</option>
                            <option value="promoted">Promoted</option>
                            <option value="left_voluntary">Left (Voluntary)</option>
                            <option value="left_involuntary">Left (Involuntary)</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Performance Notes</label>
                    <textarea id="performanceNotes" rows="3" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" placeholder="Add detailed observations about cultural fit, skill development, team integration..."></textarea>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Technical Skills</label>
                        <input type="number" id="technicalScore" min="1" max="10" class="w-full p-3 border border-gray-300 rounded-lg" placeholder="1-10">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cultural Fit</label>
                        <input type="number" id="culturalFitScore" min="1" max="10" class="w-full p-3 border border-gray-300 rounded-lg" placeholder="1-10">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Team Collaboration</label>
                        <input type="number" id="teamScore" min="1" max="10" class="w-full p-3 border border-gray-300 rounded-lg" placeholder="1-10">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Leadership</label>
                        <input type="number" id="leadershipScore" min="1" max="10" class="w-full p-3 border border-gray-300 rounded-lg" placeholder="1-10">
                    </div>
                </div>

                <button id="submitPerformanceBtn" class="mt-4 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-semibold hover:shadow-lg transition-all">
                    <i data-lucide="save" class="inline-block w-5 h-5 mr-2"></i>
                    Save Performance Record
                </button>
            </div>

            <!-- Performance History -->
            <div class="glass-panel rounded-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">
                    <i data-lucide="history" class="inline-block w-6 h-6 mr-2 text-blue-600"></i>
                    Performance History
                </h3>
                <div id="performanceHistory" class="space-y-4">
                    <!-- Performance records will be inserted here -->
                </div>
            </div>
        </div>

        <!-- Learning Insights Tab -->
        <div id="insightsTab" class="max-w-7xl mx-auto tab-content hidden">
            <!-- Insights Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Top Predictive Factors -->
                <div class="glass-panel rounded-xl p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <i data-lucide="sparkles" class="inline-block w-6 h-6 mr-2 text-purple-600"></i>
                        Top Success Predictors
                    </h3>
                    <div id="topPredictors" class="space-y-3">
                        <!-- Predictors will be inserted here -->
                    </div>
                </div>

                <!-- Emerging Patterns -->
                <div class="glass-panel rounded-xl p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <i data-lucide="trending-up" class="inline-block w-6 h-6 mr-2 text-green-600"></i>
                        Emerging Success Patterns
                    </h3>
                    <div id="emergingPatterns" class="space-y-3">
                        <!-- Patterns will be inserted here -->
                    </div>
                </div>
            </div>

            <!-- Criteria Adjustments -->
            <div class="glass-panel rounded-xl p-6 mb-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">
                    <i data-lucide="sliders-horizontal" class="inline-block w-6 h-6 mr-2 text-blue-600"></i>
                    Recent Criteria Refinements
                </h3>
                <div id="criteriaAdjustments" class="space-y-4">
                    <!-- Adjustments will be inserted here -->
                </div>
            </div>

            <!-- Manager Override Learnings -->
            <div class="glass-panel rounded-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">
                    <i data-lucide="user-cog" class="inline-block w-6 h-6 mr-2 text-orange-600"></i>
                    Learning from Manager Decisions
                </h3>
                <p class="text-gray-600 mb-4">Understanding nuanced preferences from hiring manager override decisions</p>
                <div id="overrideLearnings" class="space-y-4">
                    <!-- Override insights will be inserted here -->
                </div>
            </div>
        </div>

        <!-- Talent Predictions Tab -->
        <div id="predictionsTab" class="max-w-7xl mx-auto tab-content hidden">
            <!-- Future Needs Forecast -->
            <div class="glass-panel rounded-xl p-6 mb-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">
                    <i data-lucide="trending-up" class="inline-block w-6 h-6 mr-2 text-purple-600"></i>
                    Predicted Talent Needs (Next 6 Months)
                </h3>
                <div id="talentNeeds" class="space-y-4">
                    <!-- Talent need predictions will be inserted here -->
                </div>
            </div>

            <!-- Skills Trend Analysis -->
            <div class="glass-panel rounded-xl p-6 mb-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">
                    <i data-lucide="bar-chart-3" class="inline-block w-6 h-6 mr-2 text-blue-600"></i>
                    Emerging Skills & Qualities
                </h3>
                <canvas id="emergingSkillsChart" height="300"></canvas>
            </div>

            <!-- Growth Pattern Analysis -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="glass-panel rounded-xl p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <i data-lucide="growth" class="inline-block w-6 h-6 mr-2 text-green-600"></i>
                        Growth Trajectory
                    </h3>
                    <canvas id="growthChart" height="250"></canvas>
                </div>

                <div class="glass-panel rounded-xl p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <i data-lucide="target" class="inline-block w-6 h-6 mr-2 text-orange-600"></i>
                        Industry Trends Impact
                    </h3>
                    <div id="industryTrends" class="space-y-3">
                        <!-- Industry trends will be inserted here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div id="loadingSpinner" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="glass-panel rounded-xl p-8 text-center">
                <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-purple-600 mx-auto mb-4"></div>
                <p id="loadingText" class="text-gray-700 font-semibold">Processing...</p>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // State management
        let currentTab = 'overview';
        let learningData = null;
        let predictionData = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            setupTabNavigation();
            loadDashboardData();
            loadHiresForTracking();
        });

        // Tab Navigation
        function setupTabNavigation() {
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const tabName = btn.dataset.tab;
                    switchTab(tabName);
                });
            });
        }

        function switchTab(tabName) {
            currentTab = tabName;

            // Update button styles
            document.querySelectorAll('.tab-btn').forEach(btn => {
                if (btn.dataset.tab === tabName) {
                    btn.classList.remove('tab-inactive');
                    btn.classList.add('tab-active');
                } else {
                    btn.classList.remove('tab-active');
                    btn.classList.add('tab-inactive');
                }
            });

            // Show/hide tab content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });

            const activeTab = document.getElementById(tabName + 'Tab');
            if (activeTab) {
                activeTab.classList.remove('hidden');
            }

            // Load tab-specific data
            if (tabName === 'insights') {
                loadLearningInsights();
            } else if (tabName === 'predictions') {
                loadTalentPredictions();
            } else if (tabName === 'performance') {
                loadPerformanceHistory();
            }
        }

        // Load dashboard data
        async function loadDashboardData() {
            try {
                const response = await fetch('/api/scout/learning/insights', {
                    headers: { 'Authorization': `Bearer ${getAuthToken()}` }
                });

                if (!response.ok) throw new Error('Failed to load insights');

                const data = await response.json();
                learningData = data.data;

                updateOverviewMetrics(learningData);
                createAccuracyTrendChart(learningData.accuracy_trend);
                createPerformanceDistChart(learningData.performance_distribution);
                displayRecentEvents(learningData.recent_events);
            } catch (error) {
                console.error('Error loading dashboard:', error);
                showError('Failed to load learning insights');
            }
        }

        // Update overview metrics
        function updateOverviewMetrics(data) {
            if (data.model_accuracy) {
                document.getElementById('modelAccuracy').textContent = data.model_accuracy.toFixed(1);
            }
            if (data.total_hires_tracked) {
                document.getElementById('hiresTracked').textContent = data.total_hires_tracked;
            }
            if (data.criteria_refined_count) {
                document.getElementById('criteriaRefined').textContent = data.criteria_refined_count;
            }
            if (data.success_rate) {
                document.getElementById('successRate').textContent = data.success_rate.toFixed(1);
            }
            if (data.last_refinement_date) {
                document.getElementById('lastUpdateTime').textContent = formatRelativeTime(data.last_refinement_date);
            }
        }

        // Create accuracy trend chart
        function createAccuracyTrendChart(trendData) {
            const ctx = document.getElementById('accuracyTrendChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trendData?.labels || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Prediction Accuracy',
                        data: trendData?.values || [78, 82, 85, 83, 86, 87],
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 70,
                            max: 100
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Create performance distribution chart
        function createPerformanceDistChart(distData) {
            const ctx = document.getElementById('performanceDistChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Excellent', 'Good', 'Average', 'Poor'],
                    datasets: [{
                        data: distData?.values || [42, 58, 28, 12],
                        backgroundColor: [
                            '#10b981',
                            '#3b82f6',
                            '#f59e0b',
                            '#ef4444'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Display recent events
        function displayRecentEvents(events) {
            const container = document.getElementById('recentEvents');
            container.innerHTML = '';

            if (!events || events.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-8">No recent learning events</p>';
                return;
            }

            events.forEach(event => {
                const eventDiv = document.createElement('div');
                eventDiv.className = 'flex items-start gap-4 p-4 bg-gray-50 rounded-lg';
                eventDiv.innerHTML = `
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                        <i data-lucide="${getEventIcon(event.type)}" class="w-5 h-5 text-purple-600"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-semibold text-gray-900">${event.title}</div>
                        <div class="text-sm text-gray-600">${event.description}</div>
                        <div class="text-xs text-gray-500 mt-1">${formatRelativeTime(event.created_at)}</div>
                    </div>
                `;
                container.appendChild(eventDiv);
            });

            lucide.createIcons();
        }

        // Load hires for tracking
        async function loadHiresForTracking() {
            try {
                const response = await fetch('/api/scout/hires', {
                    headers: { 'Authorization': `Bearer ${getAuthToken()}` }
                });

                if (!response.ok) throw new Error('Failed to load hires');

                const data = await response.json();
                const hireSelect = document.getElementById('hireSelect');
                hireSelect.innerHTML = '<option value="">-- Select Hire --</option>';

                data.hires.forEach(hire => {
                    const option = document.createElement('option');
                    option.value = hire.id;
                    option.textContent = `${hire.name} - ${hire.position} (Hired ${formatDate(hire.hire_date)})`;
                    hireSelect.appendChild(option);
                });
            } catch (error) {
                console.error('Error loading hires:', error);
            }
        }

        // Submit performance tracking
        document.getElementById('submitPerformanceBtn')?.addEventListener('click', async () => {
            const hireId = document.getElementById('hireSelect').value;
            const reviewPeriod = document.getElementById('reviewPeriod').value;
            const performanceRating = document.getElementById('performanceRating').value;
            const retentionStatus = document.getElementById('retentionStatus').value;
            const notes = document.getElementById('performanceNotes').value;

            if (!hireId || !performanceRating) {
                showError('Please select a hire and performance rating');
                return;
            }

            const payload = {
                hire_id: parseInt(hireId),
                review_period: reviewPeriod,
                performance_rating: performanceRating,
                retention_status: retentionStatus,
                performance_notes: notes,
                metrics: {
                    technical_skills: parseInt(document.getElementById('technicalScore').value) || null,
                    cultural_fit: parseInt(document.getElementById('culturalFitScore').value) || null,
                    team_collaboration: parseInt(document.getElementById('teamScore').value) || null,
                    leadership: parseInt(document.getElementById('leadershipScore').value) || null
                }
            };

            showLoading('Saving performance data...');

            try {
                const response = await fetch('/api/scout/learning/performance/track', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${getAuthToken()}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Failed to save performance');
                }

                hideLoading();
                showSuccess('Performance record saved successfully!');
                
                // Clear form
                document.getElementById('performanceNotes').value = '';
                document.getElementById('technicalScore').value = '';
                document.getElementById('culturalFitScore').value = '';
                document.getElementById('teamScore').value = '';
                document.getElementById('leadershipScore').value = '';

                // Reload performance history
                loadPerformanceHistory();
            } catch (error) {
                hideLoading();
                showError('Failed to save performance: ' + error.message);
            }
        });

        // Load performance history
        async function loadPerformanceHistory() {
            try {
                const response = await fetch('/api/scout/learning/performance', {
                    headers: { 'Authorization': `Bearer ${getAuthToken()}` }
                });

                if (!response.ok) throw new Error('Failed to load history');

                const data = await response.json();
                displayPerformanceHistory(data.performance_records);
            } catch (error) {
                console.error('Error loading performance history:', error);
            }
        }

        // Display performance history
        function displayPerformanceHistory(records) {
            const container = document.getElementById('performanceHistory');
            container.innerHTML = '';

            if (!records || records.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-8">No performance records yet</p>';
                return;
            }

            records.forEach(record => {
                const recordDiv = document.createElement('div');
                const perfClass = getPerformanceClass(record.performance_rating);
                recordDiv.className = 'p-4 rounded-lg border-l-4 ' + getBorderColor(record.performance_rating);
                recordDiv.innerHTML = `
                    <div class="flex items-center justify-between mb-2">
                        <div class="font-semibold text-gray-900">${record.hire_name}</div>
                        <div class="text-sm px-3 py-1 rounded-full ${perfClass}">${formatRating(record.performance_rating)}</div>
                    </div>
                    <div class="text-sm text-gray-600 mb-2">${record.position} • ${record.review_period} Review</div>
                    ${record.performance_notes ? `<div class="text-sm text-gray-700 mb-2">${record.performance_notes}</div>` : ''}
                    <div class="flex gap-3 text-xs text-gray-600">
                        <span>Technical: ${record.metrics?.technical_skills || 'N/A'}/10</span>
                        <span>Cultural Fit: ${record.metrics?.cultural_fit || 'N/A'}/10</span>
                        <span>Team: ${record.metrics?.team_collaboration || 'N/A'}/10</span>
                    </div>
                    <div class="text-xs text-gray-500 mt-2">Tracked ${formatRelativeTime(record.created_at)}</div>
                `;
                container.appendChild(recordDiv);
            });
        }

        // Load learning insights
        async function loadLearningInsights() {
            if (learningData) {
                displayLearningInsights(learningData);
                return;
            }

            try {
                const response = await fetch('/api/scout/learning/insights', {
                    headers: { 'Authorization': `Bearer ${getAuthToken()}` }
                });

                if (!response.ok) throw new Error('Failed to load insights');

                const data = await response.json();
                learningData = data.data;
                displayLearningInsights(learningData);
            } catch (error) {
                console.error('Error loading insights:', error);
            }
        }

        // Display learning insights
        function displayLearningInsights(data) {
            // Top predictors
            const predictorsContainer = document.getElementById('topPredictors');
            predictorsContainer.innerHTML = '';
            (data.top_predictive_factors || []).forEach((factor, index) => {
                const div = document.createElement('div');
                div.className = 'insight-card p-4 bg-purple-50 rounded-lg';
                div.style.borderLeftColor = '#8b5cf6';
                div.innerHTML = `
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-semibold text-gray-900">${index + 1}. ${factor.name}</span>
                        <span class="text-purple-600 font-bold">${(factor.correlation * 100).toFixed(0)}%</span>
                    </div>
                    <div class="text-sm text-gray-600">${factor.description}</div>
                `;
                predictorsContainer.appendChild(div);
            });

            // Emerging patterns
            const patternsContainer = document.getElementById('emergingPatterns');
            patternsContainer.innerHTML = '';
            (data.emerging_patterns || []).forEach(pattern => {
                const div = document.createElement('div');
                div.className = 'insight-card p-4 bg-green-50 rounded-lg';
                div.style.borderLeftColor = '#10b981';
                div.innerHTML = `
                    <div class="font-semibold text-gray-900 mb-1">${pattern.pattern_name}</div>
                    <div class="text-sm text-gray-600 mb-2">${pattern.description}</div>
                    <div class="text-xs text-green-700">Found in ${pattern.occurrence_count} successful hires</div>
                `;
                patternsContainer.appendChild(div);
            });

            // Criteria adjustments
            const adjustmentsContainer = document.getElementById('criteriaAdjustments');
            adjustmentsContainer.innerHTML = '';
            (data.recent_refinements || []).forEach(refinement => {
                const div = document.createElement('div');
                div.className = 'p-4 bg-blue-50 rounded-lg';
                div.innerHTML = `
                    <div class="flex items-center justify-between mb-2">
                        <div class="font-semibold text-gray-900">${refinement.criterion_name}</div>
                        <div class="text-sm text-blue-600">${formatRelativeTime(refinement.refined_at)}</div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-sm mb-2">
                        <div>
                            <span class="text-gray-600">Old Weight:</span>
                            <span class="font-semibold ml-1">${(refinement.old_weight * 100).toFixed(0)}%</span>
                        </div>
                        <div>
                            <span class="text-gray-600">New Weight:</span>
                            <span class="font-semibold ml-1 text-blue-700">${(refinement.new_weight * 100).toFixed(0)}%</span>
                        </div>
                    </div>
                    <div class="text-sm text-gray-700">${refinement.reason}</div>
                `;
                adjustmentsContainer.appendChild(div);
            });

            // Override learnings
            const overridesContainer = document.getElementById('overrideLearnings');
            overridesContainer.innerHTML = '';
            (data.manager_preferences_learned || []).forEach(learning => {
                const div = document.createElement('div');
                div.className = 'p-4 bg-orange-50 rounded-lg';
                div.innerHTML = `
                    <div class="font-semibold text-gray-900 mb-2">${learning.preference_type}</div>
                    <div class="text-sm text-gray-700 mb-2">${learning.description}</div>
                    <div class="text-xs text-orange-700">Learned from ${learning.override_count} manager decisions</div>
                `;
                overridesContainer.appendChild(div);
            });
        }

        // Load talent predictions
        async function loadTalentPredictions() {
            if (predictionData) {
                displayTalentPredictions(predictionData);
                return;
            }

            try {
                const response = await fetch('/api/scout/learning/predictions', {
                    headers: { 'Authorization': `Bearer ${getAuthToken()}` }
                });

                if (!response.ok) throw new Error('Failed to load predictions');

                const data = await response.json();
                predictionData = data.data;
                displayTalentPredictions(predictionData);
            } catch (error) {
                console.error('Error loading predictions:', error);
            }
        }

        // Display talent predictions
        function displayTalentPredictions(data) {
            // Talent needs
            const needsContainer = document.getElementById('talentNeeds');
            needsContainer.innerHTML = '';
            (data.predicted_needs || []).forEach(need => {
                const div = document.createElement('div');
                div.className = 'p-4 border-2 border-gray-200 rounded-lg hover:border-purple-300 transition-all';
                const confidenceBadge = getConfidenceBadge(need.confidence);
                div.innerHTML = `
                    <div class="flex items-center justify-between mb-2">
                        <div class="font-semibold text-gray-900 text-lg">${need.role_title}</div>
                        <div class="prediction-badge ${confidenceBadge.class}">${confidenceBadge.text}</div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm mb-3">
                        <div>
                            <span class="text-gray-600">Timeframe:</span>
                            <span class="font-semibold ml-1">${need.timeframe}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Priority:</span>
                            <span class="font-semibold ml-1">${need.priority}</span>
                        </div>
                    </div>
                    <div class="text-sm text-gray-700 mb-3">${need.reasoning}</div>
                    <div class="flex flex-wrap gap-2">
                        ${(need.key_skills || []).map(skill => `
                            <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs">${skill}</span>
                        `).join('')}
                    </div>
                `;
                needsContainer.appendChild(div);
            });

            // Emerging skills chart
            createEmergingSkillsChart(data.emerging_skills);

            // Growth chart
            createGrowthChart(data.growth_trajectory);

            // Industry trends
            const trendsContainer = document.getElementById('industryTrends');
            trendsContainer.innerHTML = '';
            (data.industry_trends || []).forEach(trend => {
                const div = document.createElement('div');
                div.className = 'p-3 bg-gray-50 rounded-lg';
                div.innerHTML = `
                    <div class="font-semibold text-gray-900 mb-1">${trend.trend_name}</div>
                    <div class="text-sm text-gray-600">${trend.impact}</div>
                `;
                trendsContainer.appendChild(div);
            });
        }

        // Create emerging skills chart
        function createEmergingSkillsChart(skillsData) {
            const ctx = document.getElementById('emergingSkillsChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: skillsData?.labels || ['Cloud Architecture', 'AI/ML', 'DevOps', 'Cybersecurity', 'Data Engineering'],
                    datasets: [{
                        label: 'Demand Trend',
                        data: skillsData?.values || [85, 92, 78, 88, 81],
                        backgroundColor: 'rgba(139, 92, 246, 0.7)',
                        borderColor: '#8b5cf6',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }

        // Create growth chart
        function createGrowthChart(growthData) {
            const ctx = document.getElementById('growthChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: growthData?.labels || ['Q1', 'Q2', 'Q3', 'Q4', 'Q1 (Projected)', 'Q2 (Projected)'],
                    datasets: [{
                        label: 'Hiring Velocity',
                        data: growthData?.values || [12, 18, 22, 28, 35, 42],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Trigger model refinement
        document.getElementById('triggerRefinementBtn')?.addEventListener('click', async () => {
            if (!confirm('This will trigger a comprehensive model refinement based on all available data. This may take several minutes. Continue?')) {
                return;
            }

            showLoading('Refining learning model...');

            try {
                const response = await fetch('/api/scout/learning/refine', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${getAuthToken()}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        refinement_type: 'full',
                        include_predictions: true
                    })
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Refinement failed');
                }

                hideLoading();
                showSuccess('Model refinement started! Results will be available shortly.');
                
                // Reload dashboard after delay
                setTimeout(() => {
                    loadDashboardData();
                }, 5000);
            } catch (error) {
                hideLoading();
                showError('Failed to trigger refinement: ' + error.message);
            }
        });

        // Utility functions
        function getEventIcon(type) {
            const icons = {
                'performance_tracked': 'user-check',
                'criteria_refined': 'sliders-horizontal',
                'override_recorded': 'user-cog',
                'pattern_discovered': 'sparkles',
                'prediction_generated': 'trending-up'
            };
            return icons[type] || 'activity';
        }

        function getPerformanceClass(rating) {
            const classes = {
                'excellent': 'bg-green-100 text-green-800',
                'good': 'bg-blue-100 text-blue-800',
                'average': 'bg-yellow-100 text-yellow-800',
                'poor': 'bg-red-100 text-red-800'
            };
            return classes[rating] || 'bg-gray-100 text-gray-800';
        }

        function getBorderColor(rating) {
            const colors = {
                'excellent': 'border-green-500',
                'good': 'border-blue-500',
                'average': 'border-yellow-500',
                'poor': 'border-red-500'
            };
            return colors[rating] || 'border-gray-500';
        }

        function formatRating(rating) {
            return rating.charAt(0).toUpperCase() + rating.slice(1);
        }

        function getConfidenceBadge(confidence) {
            if (confidence >= 0.8) return { class: 'prediction-high', text: 'High Confidence' };
            if (confidence >= 0.6) return { class: 'prediction-medium', text: 'Medium Confidence' };
            return { class: 'prediction-low', text: 'Low Confidence' };
        }

        function formatRelativeTime(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 60) return `${diffMins} minutes ago`;
            if (diffHours < 24) return `${diffHours} hours ago`;
            if (diffDays < 30) return `${diffDays} days ago`;
            return formatDate(dateString);
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        function showLoading(message) {
            document.getElementById('loadingText').textContent = message;
            document.getElementById('loadingSpinner').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loadingSpinner').classList.add('hidden');
        }

        function showError(message) {
            alert('Error: ' + message);
        }

        function showSuccess(message) {
            alert('Success: ' + message);
        }

        function getAuthToken() {
            return localStorage.getItem('auth_token') || 'demo-token';
        }
    </script>
</body>
</html>
