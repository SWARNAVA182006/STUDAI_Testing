@extends('layouts.app')

@section('title', 'Dynamic Adaptive Assessment - S.C.O.U.T.')

@section('head')
<!-- Prism.js for syntax highlighting -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-python.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-java.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>

<!-- Chart.js for performance visualizations -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    .difficulty-easy { background: linear-gradient(135deg, #10b981, #34d399); color: white; }
    .difficulty-medium { background: linear-gradient(135deg, #3b82f6, #60a5fa); color: white; }
    .difficulty-hard { background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white; }
    .difficulty-expert { background: linear-gradient(135deg, #8b5cf6, #a78bfa); color: white; }
    
    .category-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
    }
    
    .proficiency-expert { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
    .proficiency-advanced { background: linear-gradient(135deg, #10b981, #34d399); }
    .proficiency-intermediate { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
    .proficiency-basic { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
    .proficiency-beginner { background: linear-gradient(135deg, #ef4444, #f87171); }
    
    .recommendation-strong { background: linear-gradient(135deg, #10b981, #34d399); }
    .recommendation-recommend { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
    .recommendation-consider { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
    .recommendation-not { background: linear-gradient(135deg, #ef4444, #f87171); }
    
    .glass-panel {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
    }
    
    .pulse-animation {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: .5; }
    }
    
    .progress-bar-animated {
        animation: progress-bar-stripes 1s linear infinite;
        background-image: linear-gradient(45deg, rgba(255,255,255,.15) 25%, transparent 25%, transparent 50%, rgba(255,255,255,.15) 50%, rgba(255,255,255,.15) 75%, transparent 75%, transparent);
        background-size: 1rem 1rem;
    }
    
    @keyframes progress-bar-stripes {
        0% { background-position: 1rem 0; }
        100% { background-position: 0 0; }
    }
    
    .code-editor {
        font-family: 'Courier New', monospace;
        font-size: 0.875rem;
        line-height: 1.5;
        background: #2d2d2d;
        color: #f8f8f2;
        padding: 1rem;
        border-radius: 0.5rem;
        min-height: 200px;
    }
    
    .timer-warning {
        animation: blink 1s ease-in-out infinite;
    }
    
    @keyframes blink {
        0%, 50%, 100% { opacity: 1; }
        25%, 75% { opacity: 0.5; }
    }
</style>
@endsection

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 py-8">
    <div class="container mx-auto px-4">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                Dynamic Adaptive Assessment
            </h1>
            <p class="text-gray-600 mt-2">AI-powered candidate evaluation system with real-time difficulty adaptation</p>
        </div>

        <!-- Assessment Configuration Panel -->
        <div id="configurationPanel" class="glass-panel rounded-2xl p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-3">
                <i data-lucide="settings" class="w-6 h-6 text-blue-600"></i>
                Assessment Configuration
            </h2>
            
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Job Selection -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Select Job</label>
                    <select id="jobSelect" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Loading jobs...</option>
                    </select>
                </div>
                
                <!-- Application Selection -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Select Candidate</label>
                    <select id="applicationSelect" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent" disabled>
                        <option value="">First select a job</option>
                    </select>
                </div>
                
                <!-- Assessment Type -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Assessment Type</label>
                    <select id="assessmentType" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="comprehensive">Comprehensive (Technical + Behavioral)</option>
                        <option value="technical">Technical Only</option>
                        <option value="behavioral">Behavioral Only</option>
                        <option value="case_study">Case Study Focused</option>
                    </select>
                </div>
                
                <!-- Initial Difficulty -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Starting Difficulty</label>
                    <select id="initialDifficulty" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="easy">Easy (Basic Concepts)</option>
                        <option value="medium" selected>Medium (Practical Application)</option>
                        <option value="hard">Hard (Complex Scenarios)</option>
                        <option value="expert">Expert (Cutting-Edge)</option>
                    </select>
                </div>
                
                <!-- Question Count -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Number of Questions</label>
                    <input type="number" id="questionCount" min="3" max="20" value="5" 
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Recommended: 5-10 questions</p>
                </div>
                
                <!-- Time Limit -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Time Limit (minutes)</label>
                    <input type="number" id="timeLimit" min="15" max="180" value="60" 
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">15-180 minutes allowed</p>
                </div>
            </div>
            
            <div class="mt-6 flex gap-4">
                <button id="generateAssessmentBtn" 
                        class="px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-lg hover:shadow-lg transform hover:scale-105 transition-all duration-200 flex items-center gap-2">
                    <i data-lucide="play-circle" class="w-5 h-5"></i>
                    Generate Assessment
                </button>
                
                <button id="showInstructionsBtn" 
                        class="px-8 py-4 border-2 border-blue-600 text-blue-600 font-semibold rounded-lg hover:bg-blue-50 transition-all duration-200 flex items-center gap-2">
                    <i data-lucide="info" class="w-5 h-5"></i>
                    How It Works
                </button>
            </div>
        </div>

        <!-- Assessment Interface (Hidden Initially) -->
        <div id="assessmentInterface" class="hidden">
            
            <!-- Progress Header -->
            <div class="glass-panel rounded-2xl p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">Assessment in Progress</h3>
                        <p class="text-sm text-gray-600" id="candidateInfo"></p>
                    </div>
                    <div class="text-right">
                        <div id="timer" class="text-3xl font-bold text-gray-800"></div>
                        <p class="text-sm text-gray-600">Time Remaining</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                            <span>Progress</span>
                            <span id="progressText">0 / 5</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div id="progressBar" class="h-3 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full transition-all duration-500" style="width: 0%"></div>
                        </div>
                    </div>
                    <div id="currentDifficultyBadge" class="difficulty-medium px-4 py-2 rounded-lg font-semibold text-sm whitespace-nowrap">
                        Medium
                    </div>
                </div>
            </div>

            <div class="grid lg:grid-cols-3 gap-6">
                
                <!-- Main Question Area -->
                <div class="lg:col-span-2">
                    <div class="glass-panel rounded-2xl p-8">
                        
                        <!-- Question Header -->
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <span id="questionNumber" class="text-2xl font-bold text-gray-800">Question 1</span>
                                <span id="questionDifficultyBadge" class="difficulty-medium px-3 py-1 rounded-full text-sm font-semibold"></span>
                                <span id="questionCategoryBadge" class="category-badge"></span>
                            </div>
                            <div id="questionTimer" class="text-xl font-semibold text-gray-600"></div>
                        </div>
                        
                        <!-- Question Text -->
                        <div id="questionText" class="prose prose-lg max-w-none mb-8 text-gray-800"></div>
                        
                        <!-- Question Context (for case studies) -->
                        <div id="questionContext" class="hidden bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded">
                            <h4 class="font-semibold text-blue-900 mb-2">Background Context:</h4>
                            <div id="questionContextText" class="text-blue-800"></div>
                        </div>
                        
                        <!-- Answer Input Area -->
                        <div id="answerArea">
                            
                            <!-- Multiple Choice -->
                            <div id="multipleChoiceArea" class="hidden space-y-3">
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Select your answer:</label>
                                <div id="optionsContainer"></div>
                            </div>
                            
                            <!-- Coding Question -->
                            <div id="codingArea" class="hidden">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Write your code:</label>
                                <div id="codeTemplate" class="text-xs text-gray-500 mb-2 bg-gray-50 p-2 rounded"></div>
                                <textarea id="codeInput" class="code-editor w-full" rows="15"></textarea>
                            </div>
                            
                            <!-- Essay Question -->
                            <div id="essayArea" class="hidden">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Your answer:</label>
                                <textarea id="essayInput" rows="10" 
                                          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                          placeholder="Type your detailed answer here..."></textarea>
                                <div class="flex justify-between mt-2 text-sm text-gray-500">
                                    <span id="wordCount">0 words</span>
                                    <span>Minimum recommended: 100 words</span>
                                </div>
                            </div>
                            
                            <!-- Case Study -->
                            <div id="caseStudyArea" class="hidden">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Your analysis and recommendations:</label>
                                <textarea id="caseStudyInput" rows="15" 
                                          class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                          placeholder="Provide a comprehensive analysis with specific recommendations..."></textarea>
                                <div class="flex justify-between mt-2 text-sm text-gray-500">
                                    <span id="caseStudyWordCount">0 words</span>
                                    <span>Minimum recommended: 200 words</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Confidence Slider -->
                        <div class="mt-8 mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-3">
                                How confident are you in your answer?
                            </label>
                            <div class="flex items-center gap-4">
                                <span class="text-sm text-gray-500">Not Confident</span>
                                <input type="range" id="confidenceSlider" min="1" max="5" value="3" 
                                       class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                                <span class="text-sm text-gray-500">Very Confident</span>
                            </div>
                            <div class="text-center mt-2">
                                <span id="confidenceValue" class="text-lg font-semibold text-blue-600">3 - Moderate</span>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <button id="submitAnswerBtn" 
                                class="w-full py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-lg hover:shadow-lg transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2">
                            <i data-lucide="send" class="w-5 h-5"></i>
                            Submit Answer
                        </button>
                    </div>
                </div>

                <!-- Live Performance Panel -->
                <div class="lg:col-span-1">
                    <div class="glass-panel rounded-2xl p-6 sticky top-4">
                        <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                            <i data-lucide="activity" class="w-5 h-5 text-blue-600"></i>
                            Live Performance
                        </h3>
                        
                        <!-- Accuracy -->
                        <div class="mb-6">
                            <div class="flex justify-between text-sm font-semibold mb-2">
                                <span class="text-gray-700">Accuracy</span>
                                <span id="accuracyPercentage" class="text-blue-600">0%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div id="accuracyBar" class="h-3 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full transition-all duration-500" style="width: 0%"></div>
                            </div>
                        </div>
                        
                        <!-- Weighted Score -->
                        <div class="mb-6">
                            <div class="flex justify-between text-sm font-semibold mb-2">
                                <span class="text-gray-700">Weighted Score</span>
                                <span id="weightedScore" class="text-purple-600">0 / 0</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div id="weightedScoreBar" class="h-3 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full transition-all duration-500" style="width: 0%"></div>
                            </div>
                        </div>
                        
                        <!-- Category Performance -->
                        <div class="mb-6">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Category Breakdown</h4>
                            <div id="categoryBreakdown" class="space-y-2 text-xs">
                                <p class="text-gray-500 italic">Complete questions to see breakdown</p>
                            </div>
                        </div>
                        
                        <!-- Difficulty Performance -->
                        <div class="mb-6">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Difficulty Performance</h4>
                            <div id="difficultyBreakdown" class="space-y-2 text-xs">
                                <p class="text-gray-500 italic">Complete questions to see breakdown</p>
                            </div>
                        </div>
                        
                        <!-- Strong/Weak Areas -->
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Strong Areas</h4>
                            <div id="strongAreas" class="mb-3 flex flex-wrap gap-2">
                                <span class="text-xs text-gray-500 italic">None yet</span>
                            </div>
                            
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Areas for Improvement</h4>
                            <div id="weakAreas" class="flex flex-wrap gap-2">
                                <span class="text-xs text-gray-500 italic">None yet</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Display (Hidden Initially) -->
        <div id="resultsDisplay" class="hidden">
            
            <!-- Final Score Card -->
            <div class="glass-panel rounded-2xl p-8 mb-8 text-center">
                <h2 class="text-3xl font-bold text-gray-800 mb-4">Assessment Complete!</h2>
                
                <div class="flex items-center justify-center gap-8 mb-6">
                    <div>
                        <div id="finalScore" class="text-6xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            0
                        </div>
                        <p class="text-gray-600 mt-2">Final Score</p>
                    </div>
                    
                    <div class="h-24 w-px bg-gray-300"></div>
                    
                    <div>
                        <div id="proficiencyBadge" class="proficiency-intermediate px-6 py-3 rounded-lg text-white text-2xl font-bold mb-2">
                            Intermediate
                        </div>
                        <p class="text-gray-600">Proficiency Level</p>
                    </div>
                </div>
                
                <div id="recommendationBadge" class="recommendation-recommend inline-block px-8 py-4 rounded-lg text-white text-xl font-bold">
                    RECOMMEND
                </div>
            </div>

            <!-- Performance Metrics Grid -->
            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <div class="glass-panel rounded-xl p-6 text-center">
                    <i data-lucide="target" class="w-12 h-12 text-blue-600 mx-auto mb-3"></i>
                    <div id="resultsAccuracy" class="text-3xl font-bold text-gray-800 mb-1">0%</div>
                    <p class="text-gray-600">Accuracy</p>
                </div>
                
                <div class="glass-panel rounded-xl p-6 text-center">
                    <i data-lucide="clock" class="w-12 h-12 text-purple-600 mx-auto mb-3"></i>
                    <div id="resultsTimeTaken" class="text-3xl font-bold text-gray-800 mb-1">0 min</div>
                    <p class="text-gray-600">Time Taken</p>
                </div>
                
                <div class="glass-panel rounded-xl p-6 text-center">
                    <i data-lucide="award" class="w-12 h-12 text-pink-600 mx-auto mb-3"></i>
                    <div id="resultsPoints" class="text-3xl font-bold text-gray-800 mb-1">0 / 0</div>
                    <p class="text-gray-600">Points Earned</p>
                </div>
            </div>

            <!-- Performance Charts -->
            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <div class="glass-panel rounded-xl p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Category Performance</h3>
                    <canvas id="categoryChart" height="200"></canvas>
                </div>
                
                <div class="glass-panel rounded-xl p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Difficulty Distribution</h3>
                    <canvas id="difficultyChart" height="200"></canvas>
                </div>
            </div>

            <!-- Detailed Question Review -->
            <div class="glass-panel rounded-2xl p-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-3">
                    <i data-lucide="list" class="w-6 h-6 text-blue-600"></i>
                    Detailed Question Review
                </h3>
                <div id="questionReview" class="space-y-6"></div>
            </div>

            <!-- Actions -->
            <div class="mt-8 flex gap-4 justify-center">
                <button id="newAssessmentBtn" 
                        class="px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-lg hover:shadow-lg transform hover:scale-105 transition-all duration-200">
                    Create New Assessment
                </button>
                <button id="exportResultsBtn" 
                        class="px-8 py-4 border-2 border-blue-600 text-blue-600 font-semibold rounded-lg hover:bg-blue-50 transition-all duration-200">
                    Export Results (PDF)
                </button>
            </div>
        </div>

    </div>
</div>

<!-- Instructions Modal -->
<div id="instructionsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="glass-panel rounded-2xl p-8 max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-start mb-6">
            <h2 class="text-2xl font-bold text-gray-800">How Adaptive Assessments Work</h2>
            <button id="closeInstructionsBtn" class="text-gray-500 hover:text-gray-700">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        
        <div class="space-y-4 text-gray-700">
            <div>
                <h3 class="font-bold text-lg mb-2">🎯 Adaptive Difficulty System</h3>
                <p>Questions automatically adjust based on your performance:</p>
                <ul class="list-disc ml-6 mt-2 space-y-1">
                    <li><strong>80%+ accuracy:</strong> Questions get harder</li>
                    <li><strong>40% or below:</strong> Questions get easier</li>
                    <li><strong>41-79%:</strong> Difficulty stays the same</li>
                </ul>
            </div>
            
            <div>
                <h3 class="font-bold text-lg mb-2">📊 Difficulty Levels</h3>
                <div class="space-y-2">
                    <div class="difficulty-easy px-3 py-2 rounded">
                        <strong>Easy:</strong> Basic concepts (80%+ pass rate)
                    </div>
                    <div class="difficulty-medium px-3 py-2 rounded">
                        <strong>Medium:</strong> Practical application (50-60% pass rate)
                    </div>
                    <div class="difficulty-hard px-3 py-2 rounded">
                        <strong>Hard:</strong> Complex scenarios (20-30% pass rate)
                    </div>
                    <div class="difficulty-expert px-3 py-2 rounded">
                        <strong>Expert:</strong> Cutting-edge knowledge (top 10%)
                    </div>
                </div>
            </div>
            
            <div>
                <h3 class="font-bold text-lg mb-2">🎯 Question Types</h3>
                <ul class="list-disc ml-6 space-y-1">
                    <li><strong>Multiple Choice:</strong> Select the best answer</li>
                    <li><strong>Coding:</strong> Write code to solve problems</li>
                    <li><strong>Essay:</strong> Detailed written responses</li>
                    <li><strong>Case Study:</strong> Real-world scenario analysis</li>
                </ul>
            </div>
            
            <div>
                <h3 class="font-bold text-lg mb-2">⭐ Weighted Scoring</h3>
                <p>Harder questions are worth more points:</p>
                <ul class="list-disc ml-6 mt-2 space-y-1">
                    <li>Easy: 1.0× multiplier</li>
                    <li>Medium: 1.5× multiplier</li>
                    <li>Hard: 2.0× multiplier</li>
                    <li>Expert: 2.5× multiplier</li>
                </ul>
            </div>
            
            <div>
                <h3 class="font-bold text-lg mb-2">🎓 Proficiency Levels</h3>
                <ul class="list-disc ml-6 space-y-1">
                    <li><strong>Expert:</strong> 90+ score</li>
                    <li><strong>Advanced:</strong> 75-89 score</li>
                    <li><strong>Intermediate:</strong> 60-74 score</li>
                    <li><strong>Basic:</strong> 45-59 score</li>
                    <li><strong>Beginner:</strong> Below 45</li>
                </ul>
            </div>
            
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <p class="font-semibold text-blue-900">💡 Pro Tip:</p>
                <p class="text-blue-800">No two candidates receive identical assessments. Questions are AI-generated based on the candidate's resume, experience, and the specific job requirements.</p>
            </div>
        </div>
    </div>
</div>

<!-- Loading Spinner -->
<div id="loadingSpinner" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="glass-panel rounded-2xl p-8 text-center">
        <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-500 border-t-transparent mx-auto mb-4"></div>
        <p id="loadingText" class="text-gray-700 font-semibold">Generating assessment...</p>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Initialize Lucide icons
lucide.createIcons();

// State management
let currentAssessment = null;
let currentQuestion = null;
let questionStartTime = null;
let assessmentTimer = null;
let questionTimer = null;

// Load active jobs on page load
document.addEventListener('DOMContentLoaded', function() {
    loadActiveJobs();
    setupEventListeners();
});

// Setup event listeners
function setupEventListeners() {
    document.getElementById('jobSelect').addEventListener('change', handleJobChange);
    document.getElementById('generateAssessmentBtn').addEventListener('click', generateAssessment);
    document.getElementById('submitAnswerBtn').addEventListener('click', submitAnswer);
    document.getElementById('showInstructionsBtn').addEventListener('click', showInstructions);
    document.getElementById('closeInstructionsBtn').addEventListener('click', hideInstructions);
    document.getElementById('newAssessmentBtn').addEventListener('click', resetToConfiguration);
    document.getElementById('confidenceSlider').addEventListener('input', updateConfidenceDisplay);
    document.getElementById('essayInput').addEventListener('input', updateWordCount);
    document.getElementById('caseStudyInput').addEventListener('input', updateCaseStudyWordCount);
}

// Load active jobs for the company
async function loadActiveJobs() {
    try {
        const response = await fetch('/api/jobs/active', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) throw new Error('Failed to load jobs');
        
        const data = await response.json();
        const jobSelect = document.getElementById('jobSelect');
        jobSelect.innerHTML = '<option value="">Select a job...</option>';
        
        data.jobs.forEach(job => {
            const option = document.createElement('option');
            option.value = job.id;
            option.textContent = `${job.title} (${job.applications_count} applicants)`;
            jobSelect.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading jobs:', error);
        showError('Failed to load jobs');
    }
}

// Handle job selection change
async function handleJobChange(e) {
    const jobId = e.target.value;
    const applicationSelect = document.getElementById('applicationSelect');
    
    if (!jobId) {
        applicationSelect.disabled = true;
        applicationSelect.innerHTML = '<option value="">First select a job</option>';
        return;
    }
    
    try {
        showLoading('Loading candidates...');
        
        const response = await fetch(`/api/jobs/${jobId}/applications`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) throw new Error('Failed to load applications');
        
        const data = await response.json();
        applicationSelect.innerHTML = '<option value="">Select a candidate...</option>';
        
        data.applications.forEach(app => {
            const option = document.createElement('option');
            option.value = app.id;
            option.textContent = `${app.user.name} - ${app.status}`;
            option.dataset.userId = app.user.id;
            applicationSelect.appendChild(option);
        });
        
        applicationSelect.disabled = false;
        hideLoading();
    } catch (error) {
        console.error('Error loading applications:', error);
        showError('Failed to load candidates');
        hideLoading();
    }
}

// Generate assessment
async function generateAssessment() {
    const jobId = document.getElementById('jobSelect').value;
    const applicationId = document.getElementById('applicationSelect').value;
    const type = document.getElementById('assessmentType').value;
    const initialDifficulty = document.getElementById('initialDifficulty').value;
    const questionCount = parseInt(document.getElementById('questionCount').value);
    const timeLimit = parseInt(document.getElementById('timeLimit').value);
    
    if (!jobId || !applicationId) {
        showError('Please select both a job and a candidate');
        return;
    }
    
    if (questionCount < 3 || questionCount > 20) {
        showError('Question count must be between 3 and 20');
        return;
    }
    
    try {
        showLoading('Generating AI-powered assessment...');
        
        const response = await fetch('/api/scout/assessment/generate', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                application_id: parseInt(applicationId),
                job_id: parseInt(jobId),
                type,
                initial_difficulty: initialDifficulty,
                question_count: questionCount,
                time_limit: timeLimit
            })
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
        console.error('Error generating assessment:', error);
        showError(error.message);
        hideLoading();
    }
}

// Start assessment
function startAssessment() {
    // Hide configuration panel
    document.getElementById('configurationPanel').classList.add('hidden');
    
    // Show assessment interface
    document.getElementById('assessmentInterface').classList.remove('hidden');
    
    // Set candidate info
    const candidateName = document.getElementById('applicationSelect').selectedOptions[0].textContent.split(' - ')[0];
    const jobTitle = document.getElementById('jobSelect').selectedOptions[0].textContent.split(' (')[0];
    document.getElementById('candidateInfo').textContent = `${candidateName} • ${jobTitle}`;
    
    // Initialize timers
    startAssessmentTimer(currentAssessment.time_limit_minutes * 60);
    
    // Load first question
    loadQuestion(currentAssessment.first_question);
    
    // Update progress
    updateProgress(0, currentAssessment.total_questions);
}

// Load question
function loadQuestion(question) {
    currentQuestion = question;
    questionStartTime = Date.now();
    
    // Update question header
    const questionNum = (currentAssessment.questions_answered || 0) + 1;
    document.getElementById('questionNumber').textContent = `Question ${questionNum}`;
    
    // Update difficulty badge
    const difficultyBadge = document.getElementById('questionDifficultyBadge');
    difficultyBadge.textContent = question.difficulty.charAt(0).toUpperCase() + question.difficulty.slice(1);
    difficultyBadge.className = `difficulty-${question.difficulty} px-3 py-1 rounded-full text-sm font-semibold`;
    
    // Update category badge
    document.getElementById('questionCategoryBadge').textContent = formatCategory(question.category);
    
    // Set question text
    document.getElementById('questionText').innerHTML = question.question_text;
    
    // Show context if available
    if (question.context) {
        document.getElementById('questionContext').classList.remove('hidden');
        document.getElementById('questionContextText').textContent = question.context;
    } else {
        document.getElementById('questionContext').classList.add('hidden');
    }
    
    // Hide all answer areas
    document.getElementById('multipleChoiceArea').classList.add('hidden');
    document.getElementById('codingArea').classList.add('hidden');
    document.getElementById('essayArea').classList.add('hidden');
    document.getElementById('caseStudyArea').classList.add('hidden');
    
    // Show appropriate answer area
    switch (question.question_type) {
        case 'multiple_choice':
            showMultipleChoice(question.options);
            break;
        case 'coding':
            showCodingArea(question.code_template);
            break;
        case 'essay':
            showEssayArea();
            break;
        case 'case_study':
            showCaseStudyArea();
            break;
    }
    
    // Reset confidence slider
    document.getElementById('confidenceSlider').value = 3;
    updateConfidenceDisplay();
    
    // Start question timer
    startQuestionTimer(question.time_limit_seconds);
}

// Show multiple choice options
function showMultipleChoice(options) {
    const area = document.getElementById('multipleChoiceArea');
    const container = document.getElementById('optionsContainer');
    container.innerHTML = '';
    
    options.forEach((option, index) => {
        const div = document.createElement('div');
        div.className = 'flex items-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition-all';
        div.onclick = () => selectOption(index);
        
        div.innerHTML = `
            <input type="radio" name="answer" value="${option}" id="option${index}" class="w-5 h-5 text-blue-600">
            <label for="option${index}" class="ml-3 flex-1 cursor-pointer">${option}</label>
        `;
        
        container.appendChild(div);
    });
    
    area.classList.remove('hidden');
}

// Select option
function selectOption(index) {
    document.getElementById(`option${index}`).checked = true;
}

// Show coding area
function showCodingArea(template) {
    const area = document.getElementById('codingArea');
    const templateDiv = document.getElementById('codeTemplate');
    const codeInput = document.getElementById('codeInput');
    
    if (template) {
        templateDiv.textContent = `Starter code: ${template}`;
        codeInput.value = template;
    } else {
        templateDiv.textContent = 'Write your solution from scratch';
        codeInput.value = '';
    }
    
    area.classList.remove('hidden');
}

// Show essay area
function showEssayArea() {
    document.getElementById('essayArea').classList.remove('hidden');
    document.getElementById('essayInput').value = '';
    updateWordCount();
}

// Show case study area
function showCaseStudyArea() {
    document.getElementById('caseStudyArea').classList.remove('hidden');
    document.getElementById('caseStudyInput').value = '';
    updateCaseStudyWordCount();
}

// Submit answer
async function submitAnswer() {
    let answer = null;
    
    switch (currentQuestion.question_type) {
        case 'multiple_choice':
            const selected = document.querySelector('input[name="answer"]:checked');
            if (!selected) {
                showError('Please select an answer');
                return;
            }
            answer = selected.value;
            break;
        case 'coding':
            answer = document.getElementById('codeInput').value.trim();
            break;
        case 'essay':
            answer = document.getElementById('essayInput').value.trim();
            break;
        case 'case_study':
            answer = document.getElementById('caseStudyInput').value.trim();
            break;
    }
    
    if (!answer) {
        showError('Please provide an answer');
        return;
    }
    
    const timeTaken = Math.floor((Date.now() - questionStartTime) / 1000);
    const confidenceLevel = parseInt(document.getElementById('confidenceSlider').value);
    
    try {
        showLoading('Evaluating your answer...');
        
        const payload = {
            question_id: currentQuestion.id,
            time_taken: timeTaken,
            confidence_level: confidenceLevel
        };
        
        if (currentQuestion.question_type === 'coding') {
            payload.code_submission = answer;
        } else {
            payload.answer = answer;
        }
        
        const response = await fetch(`/api/scout/assessment/${currentAssessment.assessment_id}/submit`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Failed to submit answer');
        }
        
        const data = await response.json();
        hideLoading();
        
        // Show feedback
        showAnswerFeedback(data.data.evaluation);
        
        // Update performance panel
        if (data.data.performance_metrics) {
            updatePerformancePanel(data.data.performance_metrics);
        }
        
        // Check if assessment is complete
        if (data.data.completed) {
            showFinalResults(data.data.final_results);
        } else {
            // Load next question after a short delay
            setTimeout(() => {
                loadQuestion(data.data.next_question);
                updateProgress(data.data.questions_answered, currentAssessment.total_questions);
                
                // Update difficulty badge if changed
                if (data.data.difficulty_adjusted) {
                    updateCurrentDifficulty(data.data.next_question.difficulty);
                }
            }, 2000);
        }
    } catch (error) {
        console.error('Error submitting answer:', error);
        showError(error.message);
        hideLoading();
    }
}

// Show answer feedback
function showAnswerFeedback(evaluation) {
    const message = evaluation.is_correct 
        ? `✅ Correct! Score: ${evaluation.score}/${evaluation.max_score}`
        : `❌ Incorrect. Score: ${evaluation.score}/${evaluation.max_score}`;
    
    const feedbackDiv = document.createElement('div');
    feedbackDiv.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg ${evaluation.is_correct ? 'bg-green-500' : 'bg-orange-500'} text-white font-semibold z-50`;
    feedbackDiv.innerHTML = `
        <p class="text-lg">${message}</p>
        ${evaluation.feedback ? `<p class="text-sm mt-2">${evaluation.feedback}</p>` : ''}
    `;
    
    document.body.appendChild(feedbackDiv);
    
    setTimeout(() => {
        feedbackDiv.remove();
    }, 3000);
}

// Update performance panel
function updatePerformancePanel(metrics) {
    // Accuracy
    document.getElementById('accuracyPercentage').textContent = `${metrics.accuracy.toFixed(1)}%`;
    document.getElementById('accuracyBar').style.width = `${metrics.accuracy}%`;
    
    // Weighted score
    document.getElementById('weightedScore').textContent = `${metrics.total_points_earned} / ${metrics.total_points_possible}`;
    const scorePercentage = metrics.total_points_possible > 0 
        ? (metrics.total_points_earned / metrics.total_points_possible * 100) 
        : 0;
    document.getElementById('weightedScoreBar').style.width = `${scorePercentage}%`;
    
    // Category breakdown
    if (metrics.category_breakdown) {
        const categoryDiv = document.getElementById('categoryBreakdown');
        categoryDiv.innerHTML = '';
        
        Object.entries(metrics.category_breakdown).forEach(([category, stats]) => {
            if (stats.attempted > 0) {
                const accuracy = (stats.correct / stats.attempted * 100).toFixed(0);
                categoryDiv.innerHTML += `
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700">${formatCategory(category)}</span>
                        <span class="font-semibold ${accuracy >= 75 ? 'text-green-600' : accuracy >= 50 ? 'text-blue-600' : 'text-orange-600'}">
                            ${stats.correct}/${stats.attempted} (${accuracy}%)
                        </span>
                    </div>
                `;
            }
        });
    }
    
    // Difficulty breakdown
    if (metrics.difficulty_breakdown) {
        const difficultyDiv = document.getElementById('difficultyBreakdown');
        difficultyDiv.innerHTML = '';
        
        ['easy', 'medium', 'hard', 'expert'].forEach(level => {
            const stats = metrics.difficulty_breakdown[level];
            if (stats && stats.attempted > 0) {
                const accuracy = (stats.correct / stats.attempted * 100).toFixed(0);
                difficultyDiv.innerHTML += `
                    <div class="flex items-center justify-between">
                        <span class="difficulty-${level} px-2 py-1 rounded text-xs">${level.charAt(0).toUpperCase() + level.slice(1)}</span>
                        <span class="font-semibold">${stats.correct}/${stats.attempted} (${accuracy}%)</span>
                    </div>
                `;
            }
        });
    }
    
    // Strong areas
    if (metrics.strong_categories && metrics.strong_categories.length > 0) {
        const strongDiv = document.getElementById('strongAreas');
        strongDiv.innerHTML = '';
        metrics.strong_categories.forEach(cat => {
            strongDiv.innerHTML += `<span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">${formatCategory(cat)}</span>`;
        });
    }
    
    // Weak areas
    if (metrics.weak_categories && metrics.weak_categories.length > 0) {
        const weakDiv = document.getElementById('weakAreas');
        weakDiv.innerHTML = '';
        metrics.weak_categories.forEach(cat => {
            weakDiv.innerHTML += `<span class="px-2 py-1 bg-orange-100 text-orange-700 rounded text-xs">${formatCategory(cat)}</span>`;
        });
    }
}

// Update current difficulty badge
function updateCurrentDifficulty(difficulty) {
    const badge = document.getElementById('currentDifficultyBadge');
    badge.textContent = difficulty.charAt(0).toUpperCase() + difficulty.slice(1);
    badge.className = `difficulty-${difficulty} px-4 py-2 rounded-lg font-semibold text-sm whitespace-nowrap`;
}

// Update progress
function updateProgress(current, total) {
    const percentage = (current / total * 100).toFixed(0);
    document.getElementById('progressText').textContent = `${current} / ${total}`;
    document.getElementById('progressBar').style.width = `${percentage}%`;
}

// Show final results
function showFinalResults(results) {
    // Stop timers
    clearInterval(assessmentTimer);
    clearInterval(questionTimer);
    
    // Hide assessment interface
    document.getElementById('assessmentInterface').classList.add('hidden');
    
    // Show results display
    document.getElementById('resultsDisplay').classList.remove('hidden');
    
    // Set final score
    document.getElementById('finalScore').textContent = results.weighted_score.toFixed(1);
    
    // Set proficiency level
    const proficiencyBadge = document.getElementById('proficiencyBadge');
    proficiencyBadge.textContent = results.proficiency_level;
    proficiencyBadge.className = `proficiency-${results.proficiency_level.toLowerCase()} px-6 py-3 rounded-lg text-white text-2xl font-bold mb-2`;
    
    // Set recommendation
    const recommendationBadge = document.getElementById('recommendationBadge');
    recommendationBadge.textContent = results.recommendation;
    const recClass = results.recommendation.replace(' ', '-').toLowerCase();
    recommendationBadge.className = `recommendation-${recClass.split('-')[0]} inline-block px-8 py-4 rounded-lg text-white text-xl font-bold`;
    
    // Set metrics
    document.getElementById('resultsAccuracy').textContent = `${results.summary.accuracy.toFixed(1)}%`;
    document.getElementById('resultsTimeTaken').textContent = `${results.summary.time_taken_minutes} min`;
    document.getElementById('resultsPoints').textContent = `${results.total_points_earned} / ${results.total_points_possible}`;
    
    // Create charts
    createCategoryChart(results.category_breakdown);
    createDifficultyChart(results.difficulty_breakdown);
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Create category performance chart
function createCategoryChart(categoryData) {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    
    const categories = Object.keys(categoryData);
    const accuracies = categories.map(cat => {
        const stats = categoryData[cat];
        return stats.attempted > 0 ? (stats.correct / stats.attempted * 100) : 0;
    });
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: categories.map(formatCategory),
            datasets: [{
                label: 'Accuracy %',
                data: accuracies,
                backgroundColor: 'rgba(59, 130, 246, 0.6)',
                borderColor: 'rgba(59, 130, 246, 1)',
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

// Create difficulty distribution chart
function createDifficultyChart(difficultyData) {
    const ctx = document.getElementById('difficultyChart').getContext('2d');
    
    const levels = ['easy', 'medium', 'hard', 'expert'];
    const attempted = levels.map(level => difficultyData[level]?.attempted || 0);
    const correct = levels.map(level => difficultyData[level]?.correct || 0);
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: levels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
            datasets: [
                {
                    label: 'Attempted',
                    data: attempted,
                    backgroundColor: 'rgba(156, 163, 175, 0.6)',
                    borderColor: 'rgba(156, 163, 175, 1)',
                    borderWidth: 2
                },
                {
                    label: 'Correct',
                    data: correct,
                    backgroundColor: 'rgba(16, 185, 129, 0.6)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 2
                }
            ]
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

// Start assessment timer
function startAssessmentTimer(seconds) {
    let remaining = seconds;
    
    const updateTimer = () => {
        const minutes = Math.floor(remaining / 60);
        const secs = remaining % 60;
        const display = `${minutes}:${secs.toString().padStart(2, '0')}`;
        
        document.getElementById('timer').textContent = display;
        
        // Warning when 5 minutes remaining
        if (remaining <= 300 && remaining > 0) {
            document.getElementById('timer').classList.add('timer-warning', 'text-orange-600');
        }
        
        // Time's up
        if (remaining <= 0) {
            clearInterval(assessmentTimer);
            showError('Time\'s up! Submitting assessment...');
            // Auto-submit or mark as expired
        }
        
        remaining--;
    };
    
    updateTimer();
    assessmentTimer = setInterval(updateTimer, 1000);
}

// Start question timer
function startQuestionTimer(seconds) {
    let remaining = seconds;
    
    const updateTimer = () => {
        const minutes = Math.floor(remaining / 60);
        const secs = remaining % 60;
        const display = `${minutes}:${secs.toString().padStart(2, '0')}`;
        
        document.getElementById('questionTimer').textContent = display;
        
        if (remaining <= 0) {
            clearInterval(questionTimer);
        }
        
        remaining--;
    };
    
    updateTimer();
    if (questionTimer) clearInterval(questionTimer);
    questionTimer = setInterval(updateTimer, 1000);
}

// Update confidence display
function updateConfidenceDisplay() {
    const value = document.getElementById('confidenceSlider').value;
    const labels = ['Very Low', 'Low', 'Moderate', 'High', 'Very High'];
    document.getElementById('confidenceValue').textContent = `${value} - ${labels[value - 1]}`;
}

// Update word count
function updateWordCount() {
    const text = document.getElementById('essayInput').value;
    const words = text.trim().split(/\s+/).filter(w => w.length > 0).length;
    document.getElementById('wordCount').textContent = `${words} words`;
}

// Update case study word count
function updateCaseStudyWordCount() {
    const text = document.getElementById('caseStudyInput').value;
    const words = text.trim().split(/\s+/).filter(w => w.length > 0).length;
    document.getElementById('caseStudyWordCount').textContent = `${words} words`;
}

// Show/hide instructions modal
function showInstructions() {
    document.getElementById('instructionsModal').classList.remove('hidden');
}

function hideInstructions() {
    document.getElementById('instructionsModal').classList.add('hidden');
}

// Reset to configuration
function resetToConfiguration() {
    document.getElementById('resultsDisplay').classList.add('hidden');
    document.getElementById('configurationPanel').classList.remove('hidden');
    currentAssessment = null;
    currentQuestion = null;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Show/hide loading spinner
function showLoading(text = 'Loading...') {
    document.getElementById('loadingText').textContent = text;
    document.getElementById('loadingSpinner').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loadingSpinner').classList.add('hidden');
}

// Show error message
function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'fixed top-4 right-4 p-4 rounded-lg shadow-lg bg-red-500 text-white font-semibold z-50';
    errorDiv.textContent = message;
    
    document.body.appendChild(errorDiv);
    
    setTimeout(() => {
        errorDiv.remove();
    }, 4000);
}

// Format category name
function formatCategory(category) {
    return category.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
}
</script>
@endsection
