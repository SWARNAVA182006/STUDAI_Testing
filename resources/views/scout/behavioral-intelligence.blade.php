<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Behavioral & Situational Intelligence - S.C.O.U.T.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/lucide.min.css">
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
        
        .score-bar {
            transition: width 0.8s ease-out;
            background: linear-gradient(90deg, #10b981 0%, #3b82f6 50%, #ec4899 100%);
        }
        
        .ei-indicator {
            transition: all 0.3s ease;
        }
        
        .ei-indicator.active {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        
        .approach-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .approach-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        
        .approach-card.selected {
            border: 2px solid #3b82f6;
            background: rgba(59, 130, 246, 0.05);
        }
        
        .thriving-meter {
            position: relative;
            height: 20px;
            background: linear-gradient(90deg, #ef4444 0%, #f59e0b 25%, #eab308 50%, #84cc16 75%, #10b981 100%);
            border-radius: 10px;
        }
        
        .thriving-indicator {
            position: absolute;
            top: -8px;
            width: 4px;
            height: 36px;
            background: #1f2937;
            border-radius: 2px;
            transition: left 0.8s ease-out;
        }
        
        .category-excellent { color: #10b981; }
        .category-good { color: #3b82f6; }
        .category-moderate { color: #f59e0b; }
        .category-poor { color: #ef4444; }
        
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(59, 130, 246, 0.4); }
            50% { box-shadow: 0 0 30px rgba(59, 130, 246, 0.6); }
        }
        
        .active-scenario {
            animation: pulse-glow 2s ease-in-out infinite;
        }
        
        .reasoning-counter {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .reasoning-counter.min-warning {
            color: #f59e0b;
            font-weight: 600;
        }
        
        .reasoning-counter.good {
            color: #10b981;
        }
        
        .hidden { display: none; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen p-6">
        <!-- Header -->
        <div class="max-w-7xl mx-auto mb-8">
            <div class="glass-panel rounded-xl p-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <i data-lucide="brain" class="inline-block w-8 h-8 mr-2 text-purple-600"></i>
                    Behavioral & Situational Intelligence
                </h1>
                <p class="text-gray-600">AI-powered assessment of cultural fit, emotional intelligence, and leadership potential through real workplace scenarios</p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Assessment Area (Left 2 columns) -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Configuration Panel -->
                <div id="configPanel" class="glass-panel rounded-xl p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Configure Assessment</h2>
                    
                    <div class="space-y-4">
                        <!-- Job Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Job Position</label>
                            <select id="jobSelect" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="">-- Select Job --</option>
                            </select>
                        </div>

                        <!-- Candidate Selection -->
                        <div id="candidateContainer" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Candidate</label>
                            <select id="candidateSelect" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="">-- Select Candidate --</option>
                            </select>
                        </div>

                        <!-- Assessment Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Assessment Type</label>
                            <select id="assessmentType" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="comprehensive">Comprehensive (All Areas)</option>
                                <option value="cultural_fit_focus">Cultural Fit Focus</option>
                                <option value="leadership_focus">Leadership Focus</option>
                            </select>
                        </div>

                        <!-- Focus Areas -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Focus Areas (Select Multiple)</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center space-x-2 p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-purple-50">
                                    <input type="checkbox" name="focus_areas" value="cultural_fit" checked class="w-4 h-4 text-purple-600">
                                    <span class="text-sm">Cultural Fit</span>
                                </label>
                                <label class="flex items-center space-x-2 p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-purple-50">
                                    <input type="checkbox" name="focus_areas" value="emotional_intelligence" checked class="w-4 h-4 text-purple-600">
                                    <span class="text-sm">Emotional Intelligence</span>
                                </label>
                                <label class="flex items-center space-x-2 p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-purple-50">
                                    <input type="checkbox" name="focus_areas" value="leadership" checked class="w-4 h-4 text-purple-600">
                                    <span class="text-sm">Leadership</span>
                                </label>
                                <label class="flex items-center space-x-2 p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-purple-50">
                                    <input type="checkbox" name="focus_areas" value="communication" class="w-4 h-4 text-purple-600">
                                    <span class="text-sm">Communication</span>
                                </label>
                                <label class="flex items-center space-x-2 p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-purple-50">
                                    <input type="checkbox" name="focus_areas" value="problem_solving" class="w-4 h-4 text-purple-600">
                                    <span class="text-sm">Problem Solving</span>
                                </label>
                            </div>
                        </div>

                        <!-- Scenario Count -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Number of Scenarios: <span id="scenarioCountValue" class="font-bold text-purple-600">6</span>
                            </label>
                            <input type="range" id="scenarioCount" min="3" max="10" value="6" class="w-full">
                            <div class="flex justify-between text-xs text-gray-500 mt-1">
                                <span>3 (Quick)</span>
                                <span>10 (Comprehensive)</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-3 pt-4">
                            <button id="generateBtn" class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 px-6 rounded-lg font-semibold hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                <i data-lucide="sparkles" class="inline-block w-5 h-5 mr-2"></i>
                                Generate Assessment
                            </button>
                            <button id="howItWorksBtn" class="px-6 py-3 border-2 border-purple-600 text-purple-600 rounded-lg font-semibold hover:bg-purple-50 transition-all">
                                <i data-lucide="help-circle" class="inline-block w-5 h-5"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Scenario Assessment Interface -->
                <div id="assessmentInterface" class="hidden glass-panel rounded-xl p-6 active-scenario">
                    <!-- Progress Header -->
                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-3">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Scenario <span id="currentScenarioNum">1</span> of <span id="totalScenarios">6</span></h3>
                                <p class="text-sm text-gray-600"><span id="candidateName">Candidate Name</span></p>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-600">Estimated Time</div>
                                <div class="text-2xl font-bold text-purple-600"><span id="estimatedTime">5</span> min</div>
                            </div>
                        </div>
                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div id="scenarioProgress" class="h-full bg-gradient-to-r from-purple-600 to-pink-600 transition-all duration-500" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Scenario Content -->
                    <div class="space-y-6">
                        <!-- Scenario Header -->
                        <div>
                            <div class="flex items-center gap-3 mb-3">
                                <span id="scenarioCategory" class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">Category</span>
                                <span id="scenarioDifficulty" class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold">Easy</span>
                                <span id="scenarioDimensions" class="text-sm text-gray-600"></span>
                            </div>
                            <h3 id="scenarioTitle" class="text-2xl font-bold text-gray-900 mb-3">Scenario Title</h3>
                        </div>

                        <!-- Context Section -->
                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
                            <h4 class="font-semibold text-blue-900 mb-2 flex items-center">
                                <i data-lucide="info" class="w-5 h-5 mr-2"></i>
                                Context
                            </h4>
                            <p id="scenarioContext" class="text-blue-800"></p>
                        </div>

                        <!-- Situation -->
                        <div class="bg-purple-50 border-l-4 border-purple-500 p-4 rounded-r-lg">
                            <h4 class="font-semibold text-purple-900 mb-2 flex items-center">
                                <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i>
                                Situation
                            </h4>
                            <p id="scenarioSituation" class="text-purple-800 font-medium"></p>
                        </div>

                        <!-- Approach Selection -->
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                                <i data-lucide="git-branch" class="w-5 h-5 mr-2"></i>
                                Select Your Approach
                            </h4>
                            <div id="approachOptions" class="space-y-3">
                                <!-- Approach cards will be inserted here -->
                            </div>
                        </div>

                        <!-- Reasoning Input -->
                        <div>
                            <label class="block font-semibold text-gray-900 mb-2">
                                <i data-lucide="message-square" class="inline-block w-5 h-5 mr-2"></i>
                                Explain Your Reasoning (Min. 50 characters)
                            </label>
                            <textarea id="reasoningInput" rows="5" 
                                class="w-full p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none"
                                placeholder="Explain why you chose this approach. Consider the company culture, team dynamics, and potential outcomes..."></textarea>
                            <div class="flex justify-between mt-2">
                                <span id="reasoningCounter" class="reasoning-counter">0 / 50 characters (minimum)</span>
                                <span class="text-xs text-gray-500">Recommended: 100+ characters for detailed analysis</span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex gap-3">
                            <button id="submitResponseBtn" disabled class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 px-6 rounded-lg font-semibold hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                <i data-lucide="send" class="inline-block w-5 h-5 mr-2"></i>
                                Submit Response
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Results Display -->
                <div id="resultsPanel" class="hidden glass-panel rounded-xl p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">
                        <i data-lucide="trophy" class="inline-block w-8 h-8 mr-2 text-yellow-500"></i>
                        Assessment Results
                    </h2>

                    <!-- Overall Scores -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-xl text-center">
                            <div class="text-sm text-green-700 mb-1">Cultural Fit</div>
                            <div class="text-3xl font-bold text-green-800"><span id="finalCulturalFit">0</span>%</div>
                            <div id="culturalFitLevel" class="text-xs text-green-600 mt-1"></div>
                        </div>
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-xl text-center">
                            <div class="text-sm text-blue-700 mb-1">Emotional Intelligence</div>
                            <div class="text-3xl font-bold text-blue-800"><span id="finalEI">0</span>%</div>
                            <div id="eiLevel" class="text-xs text-blue-600 mt-1"></div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-xl text-center">
                            <div class="text-sm text-purple-700 mb-1">Leadership</div>
                            <div class="text-3xl font-bold text-purple-800"><span id="finalLeadership">0</span>%</div>
                            <div id="leadershipLevel" class="text-xs text-purple-600 mt-1"></div>
                        </div>
                        <div class="bg-gradient-to-br from-pink-50 to-pink-100 p-4 rounded-xl text-center">
                            <div class="text-sm text-pink-700 mb-1">Communication</div>
                            <div class="text-3xl font-bold text-pink-800"><span id="finalCommunication">0</span>%</div>
                            <div id="communicationStyle" class="text-xs text-pink-600 mt-1"></div>
                        </div>
                    </div>

                    <!-- Thriving Likelihood -->
                    <div class="bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl p-6 mb-6">
                        <h3 class="text-xl font-bold mb-3">Thriving Likelihood Analysis</h3>
                        <div class="flex items-center gap-4 mb-4">
                            <div class="flex-1">
                                <div class="thriving-meter">
                                    <div id="thrivingIndicator" class="thriving-indicator" style="left: 50%"></div>
                                </div>
                                <div class="flex justify-between text-xs mt-2 opacity-90">
                                    <span>Likely to Struggle</span>
                                    <span>May Thrive</span>
                                    <span>Highly Likely to Thrive</span>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-sm opacity-90 mb-1">Probability</div>
                                <div class="text-3xl font-bold"><span id="thrivingProbability">0</span>%</div>
                            </div>
                            <div>
                                <div class="text-sm opacity-90 mb-1">Assessment</div>
                                <div id="thrivingLikelihood" class="text-lg font-semibold">Calculating...</div>
                            </div>
                        </div>
                    </div>

                    <!-- Recommendation Badge -->
                    <div id="recommendationBadge" class="text-center p-6 rounded-xl mb-6 bg-gradient-to-br from-yellow-50 to-yellow-100 border-2 border-yellow-300">
                        <div class="text-sm text-yellow-700 mb-2">HIRING RECOMMENDATION</div>
                        <div id="recommendationText" class="text-2xl font-bold text-yellow-900">EVALUATING...</div>
                        <p id="recommendationDescription" class="text-sm text-yellow-700 mt-2"></p>
                    </div>

                    <!-- Performance Charts -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-3">Competency Breakdown</h4>
                            <canvas id="competencyChart" height="250"></canvas>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-3">EI Dimensions</h4>
                            <canvas id="eiChart" height="250"></canvas>
                        </div>
                    </div>

                    <!-- Key Insights -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                            <h4 class="font-semibold text-green-900 mb-3 flex items-center">
                                <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
                                Key Strengths
                            </h4>
                            <ul id="keyStrengths" class="space-y-2 text-sm text-green-800">
                                <!-- Strengths will be inserted here -->
                            </ul>
                        </div>
                        <div class="bg-orange-50 border border-orange-200 rounded-xl p-4">
                            <h4 class="font-semibold text-orange-900 mb-3 flex items-center">
                                <i data-lucide="alert-triangle" class="w-5 h-5 mr-2"></i>
                                Development Areas
                            </h4>
                            <ul id="developmentAreas" class="space-y-2 text-sm text-orange-800">
                                <!-- Development areas will be inserted here -->
                            </ul>
                        </div>
                    </div>

                    <!-- Comprehensive Insights -->
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 mb-6">
                        <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                            <i data-lucide="lightbulb" class="w-5 h-5 mr-2 text-yellow-500"></i>
                            AI-Generated Insights
                        </h4>
                        <div class="space-y-4">
                            <div>
                                <h5 class="font-medium text-gray-800 mb-2">Executive Summary</h5>
                                <p id="executiveSummary" class="text-gray-700"></p>
                            </div>
                            <div>
                                <h5 class="font-medium text-gray-800 mb-2">Cultural Fit Assessment</h5>
                                <p id="culturalFitAssessment" class="text-gray-700"></p>
                            </div>
                            <div>
                                <h5 class="font-medium text-gray-800 mb-2">90-Day Outlook</h5>
                                <p id="ninetyDayOutlook" class="text-gray-700"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Onboarding Recommendations -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                        <h4 class="font-semibold text-blue-900 mb-3 flex items-center">
                            <i data-lucide="user-plus" class="w-5 h-5 mr-2"></i>
                            Onboarding Recommendations
                        </h4>
                        <ul id="onboardingRecommendations" class="space-y-2 text-sm text-blue-800">
                            <!-- Recommendations will be inserted here -->
                        </ul>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-3 mt-6">
                        <button id="newAssessmentBtn" class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 px-6 rounded-lg font-semibold hover:shadow-lg transition-all">
                            <i data-lucide="plus-circle" class="inline-block w-5 h-5 mr-2"></i>
                            New Assessment
                        </button>
                        <button id="exportResultsBtn" class="px-6 py-3 border-2 border-purple-600 text-purple-600 rounded-lg font-semibold hover:bg-purple-50 transition-all">
                            <i data-lucide="download" class="inline-block w-5 h-5 mr-2"></i>
                            Export PDF
                        </button>
                    </div>
                </div>

            </div>

            <!-- Live Performance Panel (Right sidebar) -->
            <div class="lg:col-span-1">
                <div id="performancePanel" class="hidden glass-panel rounded-xl p-6 sticky top-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <i data-lucide="activity" class="inline-block w-6 h-6 mr-2 text-purple-600"></i>
                        Live Performance
                    </h3>

                    <!-- Current Scores -->
                    <div class="space-y-4 mb-6">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-700">Cultural Fit</span>
                                <span id="liveCulturalFit" class="font-bold text-green-600">0%</span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div id="liveCulturalFitBar" class="h-full bg-green-500 transition-all duration-500" style="width: 0%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-700">Emotional Intelligence</span>
                                <span id="liveEI" class="font-bold text-blue-600">0%</span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div id="liveEIBar" class="h-full bg-blue-500 transition-all duration-500" style="width: 0%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-700">Leadership</span>
                                <span id="liveLeadership" class="font-bold text-purple-600">0%</span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div id="liveLeadershipBar" class="h-full bg-purple-500 transition-all duration-500" style="width: 0%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-700">Communication</span>
                                <span id="liveCommunication" class="font-bold text-pink-600">0%</span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div id="liveCommunicationBar" class="h-full bg-pink-500 transition-all duration-500" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- EI Dimensions Demonstrated -->
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">EI Dimensions Demonstrated</h4>
                        <div class="flex flex-wrap gap-2" id="eiDimensionTags">
                            <!-- EI dimension tags will be inserted here -->
                        </div>
                    </div>

                    <!-- Leadership Competencies -->
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Leadership Competencies</h4>
                        <div class="flex flex-wrap gap-2" id="leadershipTags">
                            <!-- Leadership competency tags will be inserted here -->
                        </div>
                    </div>

                    <!-- Communication Patterns -->
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Communication Patterns</h4>
                        <div class="flex flex-wrap gap-2" id="communicationTags">
                            <!-- Communication pattern tags will be inserted here -->
                        </div>
                    </div>

                    <!-- Scenarios Completed -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="text-sm text-gray-600 mb-1">Progress</div>
                        <div class="text-2xl font-bold text-purple-600">
                            <span id="completedCount">0</span> / <span id="totalCount">6</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">scenarios completed</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- How It Works Modal -->
        <div id="howItWorksModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-6">
            <div class="glass-panel rounded-xl max-w-3xl max-h-[90vh] overflow-y-auto p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">How Behavioral Intelligence Works</h2>
                    <button id="closeModalBtn" class="text-gray-500 hover:text-gray-700">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>

                <div class="space-y-6 text-gray-700">
                    <div>
                        <h3 class="font-semibold text-lg text-purple-900 mb-2">🎯 Situational Judgment Tests</h3>
                        <p>The system generates unique, AI-powered workplace scenarios based on your company's actual culture, values, and work environment. Each scenario presents a realistic challenge that candidates might face in your organization.</p>
                    </div>

                    <div>
                        <h3 class="font-semibold text-lg text-purple-900 mb-2">🧠 Multiple Valid Approaches</h3>
                        <p>Unlike traditional tests, each scenario has 4-5 valid approaches, each representing different but legitimate ways to handle the situation. The system evaluates which approach best aligns with YOUR company's preferred methods and cultural values.</p>
                    </div>

                    <div>
                        <h3 class="font-semibold text-lg text-purple-900 mb-2">📊 Multi-Dimensional Evaluation</h3>
                        <p><strong>Cultural Fit (45% weight):</strong> How well the candidate's approach aligns with your organizational culture</p>
                        <p><strong>Emotional Intelligence (25%):</strong> Demonstrated across 5 dimensions - self-awareness, self-regulation, empathy, social skills, motivation</p>
                        <p><strong>Leadership Potential (15%):</strong> Evaluated through 6 competencies - strategic thinking, people management, decision-making, conflict resolution, vision communication, change management</p>
                        <p><strong>Communication (15%):</strong> Clarity, diplomacy, assertiveness, active listening, adaptability</p>
                    </div>

                    <div>
                        <h3 class="font-semibold text-lg text-purple-900 mb-2">🎖️ Thriving Likelihood</h3>
                        <p>The AI analyzes all responses to predict the probability that a candidate will not just perform, but truly <strong>thrive</strong> in your specific environment. This goes beyond skills to assess genuine person-organization fit.</p>
                    </div>

                    <div>
                        <h3 class="font-semibold text-lg text-purple-900 mb-2">💡 Actionable Insights</h3>
                        <p>Receive comprehensive AI-generated insights including:</p>
                        <ul class="list-disc list-inside space-y-1 mt-2">
                            <li>Executive summary of candidate fit</li>
                            <li>Key strengths and development areas</li>
                            <li>90-day performance outlook</li>
                            <li>Onboarding recommendations tailored to their profile</li>
                            <li>Risk factors and mitigation strategies</li>
                        </ul>
                    </div>

                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
                        <p class="text-blue-900"><strong>💡 Pro Tip:</strong> Encourage candidates to provide detailed reasoning (100+ characters) for the most accurate AI analysis. The quality of their explanation is just as important as their chosen approach!</p>
                    </div>
                </div>

                <button id="closeModalBtn2" class="w-full mt-6 bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 px-6 rounded-lg font-semibold hover:shadow-lg transition-all">
                    Got It!
                </button>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div id="loadingSpinner" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="glass-panel rounded-xl p-8 text-center">
                <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-purple-600 mx-auto mb-4"></div>
                <p id="loadingText" class="text-gray-700 font-semibold">Generating assessment...</p>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // State management
        let currentAssessment = null;
        let currentScenario = null;
        let selectedApproach = null;
        let scenarioStartTime = null;
        let performanceData = {
            cultural_fit_score: 0,
            emotional_intelligence_score: 0,
            leadership_score: 0,
            communication_score: 0,
            responses_completed: 0
        };

        // DOM Elements
        const configPanel = document.getElementById('configPanel');
        const assessmentInterface = document.getElementById('assessmentInterface');
        const resultsPanel = document.getElementById('resultsPanel');
        const performancePanel = document.getElementById('performancePanel');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const howItWorksModal = document.getElementById('howItWorksModal');

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadActiveJobs();
            setupEventListeners();
        });

        // Event Listeners
        function setupEventListeners() {
            document.getElementById('scenarioCount').addEventListener('input', (e) => {
                document.getElementById('scenarioCountValue').textContent = e.target.value;
            });

            document.getElementById('jobSelect').addEventListener('change', handleJobChange);
            document.getElementById('generateBtn').addEventListener('click', generateAssessment);
            document.getElementById('submitResponseBtn').addEventListener('click', submitResponse);
            document.getElementById('newAssessmentBtn').addEventListener('click', resetAssessment);
            document.getElementById('howItWorksBtn').addEventListener('click', () => showModal());
            document.getElementById('closeModalBtn').addEventListener('click', () => hideModal());
            document.getElementById('closeModalBtn2').addEventListener('click', () => hideModal());
            document.getElementById('reasoningInput').addEventListener('input', updateReasoningCounter);
            document.getElementById('exportResultsBtn').addEventListener('click', exportResults);
        }

        // Load active jobs
        async function loadActiveJobs() {
            try {
                const response = await fetch('/api/jobs/active', {
                    headers: {
                        'Authorization': `Bearer ${getAuthToken()}`
                    }
                });

                if (!response.ok) throw new Error('Failed to load jobs');

                const data = await response.json();
                const jobSelect = document.getElementById('jobSelect');
                jobSelect.innerHTML = '<option value="">-- Select Job --</option>';

                data.jobs.forEach(job => {
                    const option = document.createElement('option');
                    option.value = job.id;
                    option.textContent = `${job.title} - ${job.company_name}`;
                    jobSelect.appendChild(option);
                });
            } catch (error) {
                showError('Failed to load jobs: ' + error.message);
            }
        }

        // Handle job selection change
        async function handleJobChange(e) {
            const jobId = e.target.value;
            if (!jobId) {
                document.getElementById('candidateContainer').classList.add('hidden');
                return;
            }

            try {
                const response = await fetch(`/api/jobs/${jobId}/applications`, {
                    headers: {
                        'Authorization': `Bearer ${getAuthToken()}`
                    }
                });

                if (!response.ok) throw new Error('Failed to load candidates');

                const data = await response.json();
                const candidateSelect = document.getElementById('candidateSelect');
                candidateSelect.innerHTML = '<option value="">-- Select Candidate --</option>';

                data.applications.forEach(app => {
                    const option = document.createElement('option');
                    option.value = JSON.stringify({ application_id: app.id, job_id: jobId });
                    option.textContent = `${app.candidate_name} - Applied ${new Date(app.created_at).toLocaleDateString()}`;
                    candidateSelect.appendChild(option);
                });

                document.getElementById('candidateContainer').classList.remove('hidden');
            } catch (error) {
                showError('Failed to load candidates: ' + error.message);
            }
        }

        // Generate behavioral assessment
        async function generateAssessment() {
            const candidateData = document.getElementById('candidateSelect').value;
            if (!candidateData) {
                showError('Please select a candidate');
                return;
            }

            const { application_id, job_id } = JSON.parse(candidateData);
            const focusAreas = Array.from(document.querySelectorAll('input[name="focus_areas"]:checked')).map(cb => cb.value);
            
            if (focusAreas.length === 0) {
                showError('Please select at least one focus area');
                return;
            }

            const payload = {
                application_id: parseInt(application_id),
                job_id: parseInt(job_id),
                scenario_count: parseInt(document.getElementById('scenarioCount').value),
                type: document.getElementById('assessmentType').value,
                focus_areas: focusAreas
            };

            showLoading('Analyzing company culture and generating scenarios...');

            try {
                const response = await fetch('/api/scout/behavioral/generate', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${getAuthToken()}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Failed to generate assessment');
                }

                const data = await response.json();
                currentAssessment = data.data;
                
                hideLoading();
                startAssessment();
            } catch (error) {
                hideLoading();
                showError('Failed to generate assessment: ' + error.message);
            }
        }

        // Start assessment
        function startAssessment() {
            configPanel.classList.add('hidden');
            assessmentInterface.classList.remove('hidden');
            performancePanel.classList.remove('hidden');

            document.getElementById('totalScenarios').textContent = currentAssessment.scenario_count;
            document.getElementById('totalCount').textContent = currentAssessment.scenario_count;
            
            loadScenario(currentAssessment.first_scenario);
        }

        // Load scenario
        function loadScenario(scenario) {
            currentScenario = scenario;
            selectedApproach = null;
            scenarioStartTime = Date.now();

            document.getElementById('currentScenarioNum').textContent = scenario.scenario_number;
            document.getElementById('scenarioTitle').textContent = scenario.title;
            document.getElementById('scenarioContext').textContent = scenario.context;
            document.getElementById('scenarioSituation').textContent = scenario.situation;
            document.getElementById('scenarioCategory').textContent = formatCategory(scenario.category);
            document.getElementById('scenarioDifficulty').textContent = scenario.difficulty_level;
            document.getElementById('estimatedTime').textContent = getEstimatedTime(scenario.difficulty_level);
            
            // Update difficulty badge color
            const difficultyBadge = document.getElementById('scenarioDifficulty');
            difficultyBadge.className = `px-3 py-1 rounded-full text-sm font-semibold ${getDifficultyColor(scenario.difficulty_level)}`;

            // Display dimensions being evaluated
            if (scenario.evaluates_dimensions && scenario.evaluates_dimensions.length > 0) {
                document.getElementById('scenarioDimensions').textContent = 
                    'Evaluates: ' + scenario.evaluates_dimensions.slice(0, 3).map(d => formatCategory(d)).join(', ');
            }

            // Render approach options
            renderApproachOptions(scenario.valid_approaches);

            // Clear reasoning
            document.getElementById('reasoningInput').value = '';
            updateReasoningCounter();

            // Update progress
            updateProgress();
        }

        // Render approach options
        function renderApproachOptions(approaches) {
            const container = document.getElementById('approachOptions');
            container.innerHTML = '';

            approaches.forEach((approach, index) => {
                const card = document.createElement('div');
                card.className = 'approach-card border-2 border-gray-300 rounded-lg p-4 hover:border-purple-400';
                card.dataset.index = index;

                const description = approach.approach_description || approach.description || `Approach ${index + 1}`;
                
                card.innerHTML = `
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-purple-100 text-purple-700 font-bold flex items-center justify-center">
                            ${String.fromCharCode(65 + index)}
                        </div>
                        <div class="flex-1">
                            <p class="text-gray-900 font-medium">${description}</p>
                        </div>
                    </div>
                `;

                card.addEventListener('click', () => selectApproach(index));
                container.appendChild(card);
            });
        }

        // Select approach
        function selectApproach(index) {
            selectedApproach = index;

            // Update UI
            document.querySelectorAll('.approach-card').forEach((card, i) => {
                if (i === index) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }
            });

            checkSubmitReady();
        }

        // Update reasoning counter
        function updateReasoningCounter() {
            const input = document.getElementById('reasoningInput');
            const counter = document.getElementById('reasoningCounter');
            const length = input.value.length;

            counter.textContent = `${length} / 50 characters (minimum)`;
            
            if (length < 50) {
                counter.className = 'reasoning-counter min-warning';
            } else if (length >= 100) {
                counter.className = 'reasoning-counter good';
            } else {
                counter.className = 'reasoning-counter';
            }

            checkSubmitReady();
        }

        // Check if submit button should be enabled
        function checkSubmitReady() {
            const reasoning = document.getElementById('reasoningInput').value;
            const submitBtn = document.getElementById('submitResponseBtn');
            
            submitBtn.disabled = !(selectedApproach !== null && reasoning.length >= 50);
        }

        // Submit response
        async function submitResponse() {
            const reasoning = document.getElementById('reasoningInput').value;
            const timeTaken = Math.floor((Date.now() - scenarioStartTime) / 1000);

            const payload = {
                scenario_id: currentScenario.scenario_id,
                selected_approach: selectedApproach,
                reasoning: reasoning,
                time_taken: timeTaken
            };

            showLoading('Evaluating your response with AI...');

            try {
                const response = await fetch(`/api/scout/behavioral/${currentAssessment.assessment_id}/submit`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${getAuthToken()}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Failed to submit response');
                }

                const data = await response.json();
                hideLoading();

                // Update performance metrics
                if (data.data.performance_metrics) {
                    updatePerformancePanel(data.data.performance_metrics);
                }

                // Show feedback
                showFeedback(data.data.evaluation);

                // Check if complete
                if (data.data.is_complete) {
                    setTimeout(() => showResults(data.data.final_results), 2000);
                } else {
                    // Load next scenario
                    setTimeout(() => loadScenario(data.data.next_scenario), 1500);
                }
            } catch (error) {
                hideLoading();
                showError('Failed to submit response: ' + error.message);
            }
        }

        // Update performance panel
        function updatePerformancePanel(metrics) {
            performanceData = metrics;

            // Update scores
            document.getElementById('liveCulturalFit').textContent = metrics.cultural_fit_score.toFixed(1) + '%';
            document.getElementById('liveCulturalFitBar').style.width = metrics.cultural_fit_score + '%';

            document.getElementById('liveEI').textContent = metrics.emotional_intelligence_score.toFixed(1) + '%';
            document.getElementById('liveEIBar').style.width = metrics.emotional_intelligence_score + '%';

            document.getElementById('liveLeadership').textContent = metrics.leadership_score.toFixed(1) + '%';
            document.getElementById('liveLeadershipBar').style.width = metrics.leadership_score + '%';

            document.getElementById('liveCommunication').textContent = metrics.communication_score.toFixed(1) + '%';
            document.getElementById('liveCommunicationBar').style.width = metrics.communication_score + '%';

            // Update EI dimensions
            if (metrics.ei_dimensions_breakdown) {
                updateTags('eiDimensionTags', Object.keys(metrics.ei_dimensions_breakdown), 'blue');
            }

            // Update leadership competencies
            if (metrics.leadership_competencies_breakdown) {
                updateTags('leadershipTags', Object.keys(metrics.leadership_competencies_breakdown), 'purple');
            }

            // Update completed count
            document.getElementById('completedCount').textContent = metrics.responses_completed;
        }

        // Update tags
        function updateTags(containerId, items, color) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';

            items.forEach(item => {
                const tag = document.createElement('span');
                tag.className = `px-2 py-1 bg-${color}-100 text-${color}-700 rounded-full text-xs`;
                tag.textContent = formatCategory(item);
                container.appendChild(tag);
            });
        }

        // Show feedback
        function showFeedback(evaluation) {
            const message = `Score: ${evaluation.overall_score.toFixed(0)}% - ${evaluation.feedback}`;
            
            // Create toast notification
            const toast = document.createElement('div');
            toast.className = 'fixed top-6 right-6 z-50 glass-panel rounded-lg p-4 max-w-md shadow-xl';
            toast.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        ${evaluation.overall_score >= 70 
                            ? '<i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>'
                            : '<i data-lucide="info" class="w-6 h-6 text-blue-600"></i>'}
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900 mb-1">Response Evaluated</h4>
                        <p class="text-sm text-gray-700">${message}</p>
                    </div>
                </div>
            `;
            document.body.appendChild(toast);
            lucide.createIcons();

            setTimeout(() => {
                toast.remove();
            }, 4000);
        }

        // Update progress
        function updateProgress() {
            const progress = (performanceData.responses_completed / currentAssessment.scenario_count) * 100;
            document.getElementById('scenarioProgress').style.width = progress + '%';
        }

        // Show results
        function showResults(results) {
            assessmentInterface.classList.add('hidden');
            performancePanel.classList.add('hidden');
            resultsPanel.classList.remove('hidden');

            // Update overall scores
            document.getElementById('finalCulturalFit').textContent = results.cultural_fit_score.toFixed(1);
            document.getElementById('culturalFitLevel').textContent = results.cultural_fit_level;

            document.getElementById('finalEI').textContent = results.emotional_intelligence_score.toFixed(1);
            document.getElementById('eiLevel').textContent = results.emotional_intelligence_level;

            document.getElementById('finalLeadership').textContent = results.leadership_score.toFixed(1);
            document.getElementById('leadershipLevel').textContent = results.leadership_potential;

            document.getElementById('finalCommunication').textContent = results.communication_score.toFixed(1);
            document.getElementById('communicationStyle').textContent = results.communication_style;

            // Thriving likelihood
            const thrivingProb = results.thriving_probability;
            document.getElementById('thrivingProbability').textContent = thrivingProb.toFixed(1);
            document.getElementById('thrivingLikelihood').textContent = results.thriving_likelihood;
            document.getElementById('thrivingIndicator').style.left = thrivingProb + '%';

            // Recommendation
            const recommendation = results.recommendation;
            const recBadge = document.getElementById('recommendationBadge');
            document.getElementById('recommendationText').textContent = recommendation.split(' - ')[0];
            document.getElementById('recommendationDescription').textContent = recommendation.split(' - ')[1] || '';

            // Color code recommendation
            if (recommendation.includes('STRONG HIRE')) {
                recBadge.className = 'text-center p-6 rounded-xl mb-6 bg-gradient-to-br from-green-50 to-green-100 border-2 border-green-500';
            } else if (recommendation.includes('RECOMMEND')) {
                recBadge.className = 'text-center p-6 rounded-xl mb-6 bg-gradient-to-br from-blue-50 to-blue-100 border-2 border-blue-500';
            } else if (recommendation.includes('CONSIDER')) {
                recBadge.className = 'text-center p-6 rounded-xl mb-6 bg-gradient-to-br from-yellow-50 to-yellow-100 border-2 border-yellow-500';
            } else {
                recBadge.className = 'text-center p-6 rounded-xl mb-6 bg-gradient-to-br from-red-50 to-red-100 border-2 border-red-500';
            }

            // Key strengths
            const strengthsList = document.getElementById('keyStrengths');
            strengthsList.innerHTML = '';
            (results.comprehensive_insights.key_strengths || []).forEach(strength => {
                const li = document.createElement('li');
                li.innerHTML = `<i data-lucide="check" class="inline-block w-4 h-4 mr-2"></i>${strength}`;
                strengthsList.appendChild(li);
            });

            // Development areas
            const devList = document.getElementById('developmentAreas');
            devList.innerHTML = '';
            (results.comprehensive_insights.development_areas || []).forEach(area => {
                const li = document.createElement('li');
                li.innerHTML = `<i data-lucide="arrow-up-right" class="inline-block w-4 h-4 mr-2"></i>${area}`;
                devList.appendChild(li);
            });

            // Insights
            document.getElementById('executiveSummary').textContent = 
                results.comprehensive_insights.executive_summary || 'No summary available.';
            document.getElementById('culturalFitAssessment').textContent = 
                results.comprehensive_insights.cultural_fit_assessment || 'No assessment available.';
            document.getElementById('ninetyDayOutlook').textContent = 
                results.comprehensive_insights.ninety_day_outlook || 'No outlook available.';

            // Onboarding recommendations
            const onboardingList = document.getElementById('onboardingRecommendations');
            onboardingList.innerHTML = '';
            (results.comprehensive_insights.onboarding_recommendations || []).forEach(rec => {
                const li = document.createElement('li');
                li.innerHTML = `<i data-lucide="arrow-right" class="inline-block w-4 h-4 mr-2"></i>${rec}`;
                onboardingList.appendChild(li);
            });

            // Create charts
            createCompetencyChart(results);
            createEIChart(results);

            lucide.createIcons();
        }

        // Create competency chart
        function createCompetencyChart(results) {
            const ctx = document.getElementById('competencyChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Cultural Fit', 'Emotional Intelligence', 'Leadership', 'Communication'],
                    datasets: [{
                        label: 'Score',
                        data: [
                            results.cultural_fit_score,
                            results.emotional_intelligence_score,
                            results.leadership_score,
                            results.communication_score
                        ],
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.7)',
                            'rgba(59, 130, 246, 0.7)',
                            'rgba(139, 92, 246, 0.7)',
                            'rgba(236, 72, 153, 0.7)'
                        ],
                        borderColor: [
                            'rgb(16, 185, 129)',
                            'rgb(59, 130, 246)',
                            'rgb(139, 92, 246)',
                            'rgb(236, 72, 153)'
                        ],
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
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Create EI chart
        function createEIChart(results) {
            const ctx = document.getElementById('eiChart').getContext('2d');
            const eiData = results.emotional_intelligence_assessment.dimensions_demonstrated || {};
            
            new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: Object.keys(eiData).map(formatCategory),
                    datasets: [{
                        label: 'Demonstrated',
                        data: Object.values(eiData),
                        backgroundColor: 'rgba(59, 130, 246, 0.2)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgb(59, 130, 246)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Reset assessment
        function resetAssessment() {
            resultsPanel.classList.add('hidden');
            configPanel.classList.remove('hidden');
            currentAssessment = null;
            currentScenario = null;
            selectedApproach = null;
            performanceData = {
                cultural_fit_score: 0,
                emotional_intelligence_score: 0,
                leadership_score: 0,
                communication_score: 0,
                responses_completed: 0
            };
        }

        // Export results
        function exportResults() {
            alert('PDF export functionality will be implemented. This will generate a comprehensive report with all assessment data, charts, and recommendations.');
        }

        // Utility functions
        function formatCategory(category) {
            return category.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
        }

        function getDifficultyColor(difficulty) {
            const colors = {
                'easy': 'bg-green-100 text-green-800',
                'medium': 'bg-blue-100 text-blue-800',
                'hard': 'bg-orange-100 text-orange-800',
                'expert': 'bg-purple-100 text-purple-800'
            };
            return colors[difficulty] || 'bg-gray-100 text-gray-800';
        }

        function getEstimatedTime(difficulty) {
            const times = {
                'easy': 3,
                'medium': 5,
                'hard': 7,
                'expert': 10
            };
            return times[difficulty] || 5;
        }

        function showLoading(message) {
            document.getElementById('loadingText').textContent = message;
            loadingSpinner.classList.remove('hidden');
        }

        function hideLoading() {
            loadingSpinner.classList.add('hidden');
        }

        function showModal() {
            howItWorksModal.classList.remove('hidden');
        }

        function hideModal() {
            howItWorksModal.classList.add('hidden');
        }

        function showError(message) {
            alert('Error: ' + message);
        }

        function getAuthToken() {
            // Replace with actual token retrieval logic
            return localStorage.getItem('auth_token') || 'demo-token';
        }
    </script>
</body>
</html>
