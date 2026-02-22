@extends('layouts.app')

@section('title', 'Negotiation Coaching - ' . $strategy->role)

@push('styles')
<style>
    .chat-container {
        height: calc(100vh - 300px);
        min-height: 500px;
    }
    .messages-area {
        height: 100%;
        overflow-y: auto;
        scroll-behavior: smooth;
    }
    .message-bubble {
        max-width: 70%;
        animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .employer-message {
        background: linear-gradient(135deg, rgba(107, 114, 128, 0.2) 0%, rgba(75, 85, 99, 0.2) 100%);
        border-left: 3px solid #6b7280;
    }
    .user-message {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(37, 99, 235, 0.2) 100%);
        border-right: 3px solid #3b82f6;
    }
    .ai-message {
        background: linear-gradient(135deg, rgba(168, 85, 247, 0.2) 0%, rgba(147, 51, 234, 0.2) 100%);
        border-left: 3px solid #a855f7;
    }
    .tone-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-weight: 600;
    }
    .tone-positive { background: rgba(16, 185, 129, 0.2); color: #10b981; }
    .tone-neutral { background: rgba(107, 114, 128, 0.2); color: #9ca3af; }
    .tone-negative { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
    .suggestion-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .suggestion-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(236, 72, 153, 0.2);
    }
    .coaching-panel {
        height: calc(100vh - 300px);
        min-height: 500px;
        overflow-y: auto;
    }
    .confidence-meter {
        height: 8px;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.1);
        overflow: hidden;
    }
    .confidence-fill {
        height: 100%;
        border-radius: 9999px;
        transition: width 0.3s ease;
    }
    .stage-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        transition: all 0.3s ease;
    }
    .stage-dot.active {
        background: #ec4899;
        box-shadow: 0 0 0 4px rgba(236, 72, 153, 0.2);
    }
    .stage-dot.completed {
        background: #10b981;
    }
    .stage-dot.pending {
        background: rgba(255, 255, 255, 0.2);
    }
</style>
@endpush

@section('content')
<div class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('negotiation.strategy', $strategy->id) }}" class="inline-flex items-center text-sm text-gray-400 hover:text-white mb-4 transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Strategy
        </a>
        
        <div class="flex items-start justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">Live Negotiation Coaching</h1>
                <p class="text-gray-400">{{ $strategy->role }} at {{ $strategy->company_name }}</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <span class="px-4 py-2 rounded-lg bg-green-500/20 text-green-300 text-sm font-medium">
                    Session Active
                </span>
                <button onclick="endSession()" class="px-4 py-2 rounded-lg bg-red-500/20 text-red-300 text-sm font-medium hover:bg-red-500/30 transition">
                    End Session
                </button>
            </div>
        </div>
        
        <!-- Progress Tracker -->
        <div class="bg-white/5 backdrop-filter backdrop-blur-lg rounded-xl p-4 border border-white/10">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-400">Negotiation Stage</span>
                <span class="text-sm font-medium text-white">{{ ucfirst(str_replace('_', ' ', $session->current_stage ?? 'initial_response')) }}</span>
            </div>
            <div class="flex items-center space-x-4">
                <div class="flex items-center">
                    <div class="stage-dot {{ ($session->current_stage ?? 'initial_response') === 'initial_response' ? 'active' : 'completed' }}"></div>
                    <span class="ml-2 text-xs text-gray-400">Initial</span>
                </div>
                <div class="flex-1 h-0.5 bg-white/10"></div>
                <div class="flex items-center">
                    <div class="stage-dot {{ in_array($session->current_stage ?? 'initial_response', ['counter_offer', 'negotiation', 'closing']) ? 'active' : 'pending' }}"></div>
                    <span class="ml-2 text-xs text-gray-400">Counter</span>
                </div>
                <div class="flex-1 h-0.5 bg-white/10"></div>
                <div class="flex items-center">
                    <div class="stage-dot {{ in_array($session->current_stage ?? 'initial_response', ['negotiation', 'closing']) ? 'active' : 'pending' }}"></div>
                    <span class="ml-2 text-xs text-gray-400">Negotiation</span>
                </div>
                <div class="flex-1 h-0.5 bg-white/10"></div>
                <div class="flex items-center">
                    <div class="stage-dot {{ ($session->current_stage ?? 'initial_response') === 'closing' ? 'active' : 'pending' }}"></div>
                    <span class="ml-2 text-xs text-gray-400">Closing</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content: Chat + Coaching Panel -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Chat Area (2/3 width) -->
        <div class="lg:col-span-2">
            <div class="bg-white/5 backdrop-filter backdrop-blur-lg rounded-2xl border border-white/10 overflow-hidden">
                <div class="bg-gradient-to-r from-primary-color to-primary-light p-4">
                    <h2 class="text-white font-semibold">Conversation</h2>
                    <p class="text-white/80 text-sm">Real-time negotiation exchange</p>
                </div>
                
                <div class="chat-container">
                    <div class="messages-area p-6 space-y-4" id="messagesArea">
                        @forelse($messages as $message)
                            @if($message->sender === 'employer')
                            <!-- Employer Message (Left) -->
                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 rounded-full bg-gray-500/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="message-bubble employer-message rounded-2xl p-4 flex-1">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs text-gray-400">Employer</span>
                                        <span class="text-xs text-gray-500">{{ $message->created_at->format('g:i A') }}</span>
                                    </div>
                                    <p class="text-white text-sm leading-relaxed">{{ $message->content }}</p>
                                    
                                    @if($message->ai_analysis)
                                    <div class="mt-3 pt-3 border-t border-white/10">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <span class="tone-badge tone-{{ $message->ai_analysis['tone'] ?? 'neutral' }}">
                                                {{ ucfirst($message->ai_analysis['tone'] ?? 'Neutral') }} Tone
                                            </span>
                                            @if(isset($message->ai_analysis['signals']) && count($message->ai_analysis['signals']) > 0)
                                            <span class="text-xs text-purple-400">{{ count($message->ai_analysis['signals']) }} signals detected</span>
                                            @endif
                                        </div>
                                        @if(isset($message->ai_analysis['key_phrases']) && count($message->ai_analysis['key_phrases']) > 0)
                                        <div class="flex flex-wrap gap-2">
                                            @foreach(array_slice($message->ai_analysis['key_phrases'], 0, 3) as $phrase)
                                            <span class="text-xs px-2 py-1 rounded bg-purple-500/20 text-purple-300">{{ $phrase }}</span>
                                            @endforeach
                                        </div>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                            
                            @elseif($message->sender === 'user')
                            <!-- User Message (Right) -->
                            <div class="flex items-start space-x-3 justify-end">
                                <div class="message-bubble user-message rounded-2xl p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs text-gray-400">You</span>
                                        <span class="text-xs text-gray-500">{{ $message->created_at->format('g:i A') }}</span>
                                    </div>
                                    <p class="text-white text-sm leading-relaxed">{{ $message->content }}</p>
                                </div>
                                <div class="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                            </div>
                            
                            @else
                            <!-- AI Coach Message (Center) -->
                            <div class="flex justify-center">
                                <div class="message-bubble ai-message rounded-2xl p-4 max-w-2xl">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                        </svg>
                                        <span class="text-xs text-purple-300 font-medium">AI Coach</span>
                                        <span class="text-xs text-gray-500">{{ $message->created_at->format('g:i A') }}</span>
                                    </div>
                                    <p class="text-white text-sm leading-relaxed">{{ $message->content }}</p>
                                </div>
                            </div>
                            @endif
                        @empty
                        <div class="flex items-center justify-center h-full">
                            <div class="text-center">
                                <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                </svg>
                                <p class="text-gray-500 mb-2">No messages yet</p>
                                <p class="text-gray-600 text-sm">Start by adding an employer message below</p>
                            </div>
                        </div>
                        @endforelse
                    </div>
                </div>
                
                <!-- Message Input -->
                <div class="p-4 border-t border-white/10">
                    <div class="flex items-center space-x-2 mb-3">
                        <button id="employerBtn" class="sender-toggle px-4 py-2 rounded-lg bg-gray-500/20 text-gray-300 text-sm font-medium hover:bg-gray-500/30 transition" onclick="setSender('employer')">
                            Employer Said
                        </button>
                        <button id="userBtn" class="sender-toggle px-4 py-2 rounded-lg bg-blue-500/20 text-blue-300 text-sm font-medium hover:bg-blue-500/30 transition active" onclick="setSender('user')">
                            I Replied
                        </button>
                    </div>
                    <form id="messageForm" onsubmit="sendMessage(event)">
                        <div class="flex items-end space-x-3">
                            <div class="flex-1">
                                <textarea id="messageInput" rows="2" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-primary-color resize-none" placeholder="Type message..."></textarea>
                            </div>
                            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-primary-color to-primary-light text-white rounded-xl font-semibold hover:shadow-lg transition flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- AI Coaching Panel (1/3 width) -->
        <div class="lg:col-span-1">
            <div class="bg-white/5 backdrop-filter backdrop-blur-lg rounded-2xl border border-white/10 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 p-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        <h2 class="text-white font-semibold">AI Coach</h2>
                    </div>
                </div>
                
                <div class="coaching-panel p-4 space-y-4">
                    <!-- Latest Analysis -->
                    @if($messages->where('sender', 'employer')->last())
                    @php $lastEmployerMessage = $messages->where('sender', 'employer')->last(); @endphp
                    @if($lastEmployerMessage->ai_analysis)
                    <div>
                        <h3 class="text-white font-semibold mb-3 text-sm">Message Interpretation</h3>
                        <div class="bg-purple-500/10 border border-purple-500/20 rounded-lg p-3 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400">Tone</span>
                                <span class="tone-badge tone-{{ $lastEmployerMessage->ai_analysis['tone'] ?? 'neutral' }}">
                                    {{ ucfirst($lastEmployerMessage->ai_analysis['tone'] ?? 'Neutral') }}
                                </span>
                            </div>
                            @if(isset($lastEmployerMessage->ai_analysis['interpretation']))
                            <p class="text-sm text-gray-300">{{ $lastEmployerMessage->ai_analysis['interpretation'] }}</p>
                            @endif
                        </div>
                    </div>
                    @endif
                    @endif
                    
                    <!-- Tactical Analysis -->
                    <div>
                        <h3 class="text-white font-semibold mb-3 text-sm">Tactical Analysis</h3>
                        <div class="space-y-2">
                            <div class="bg-blue-500/10 border border-blue-500/20 rounded-lg p-3">
                                <div class="flex items-start">
                                    <svg class="w-4 h-4 text-blue-400 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                    <div>
                                        <p class="text-xs font-semibold text-blue-300 mb-1">Leverage Points</p>
                                        <ul class="text-xs text-gray-300 space-y-1">
                                            <li>• Market position ({{ $strategy->market_position_percentile ?? 'N/A' }}th percentile)</li>
                                            <li>• {{ $strategy->experience_years }} years experience</li>
                                            @if($strategy->has_other_offers)
                                            <li>• Alternative offers available</li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-yellow-500/10 border border-yellow-500/20 rounded-lg p-3">
                                <div class="flex items-start">
                                    <svg class="w-4 h-4 text-yellow-400 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <div>
                                        <p class="text-xs font-semibold text-yellow-300 mb-1">Watch Out For</p>
                                        <ul class="text-xs text-gray-300 space-y-1">
                                            <li>• Pressure tactics or deadlines</li>
                                            <li>• Deflection to non-salary benefits</li>
                                            <li>• "Take it or leave it" ultimatums</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recommended Strategy -->
                    <div>
                        <h3 class="text-white font-semibold mb-3 text-sm">Recommended Strategy</h3>
                        <div class="bg-green-500/10 border border-green-500/20 rounded-lg p-3">
                            <p class="text-sm text-gray-300 mb-3">{{ $strategy->recommended_tone ? 'Use a ' . $strategy->recommended_tone . ' tone. ' : '' }}{{ $strategy->ai_summary ?? 'Continue building rapport while anchoring your value proposition.' }}</p>
                            <div class="space-y-2">
                                <div class="flex items-center text-xs text-green-300">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Reference market data
                                </div>
                                <div class="flex items-center text-xs text-green-300">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Highlight unique value
                                </div>
                                <div class="flex items-center text-xs text-green-300">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Maintain collaborative tone
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Response Suggestions -->
                    <div>
                        <h3 class="text-white font-semibold mb-3 text-sm">Suggested Responses</h3>
                        <div class="space-y-3">
                            <div class="suggestion-card bg-white/5 rounded-lg p-3 border border-white/10" onclick="useSuggestion(1)">
                                <div class="flex items-start justify-between mb-2">
                                    <span class="text-xs px-2 py-1 rounded bg-blue-500/20 text-blue-300">Professional</span>
                                    <div class="flex items-center">
                                        <div class="confidence-meter w-16 mr-2">
                                            <div class="confidence-fill bg-gradient-to-r from-green-500 to-green-400" style="width: 85%"></div>
                                        </div>
                                        <span class="text-xs text-gray-400">85%</span>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-300 mb-2">Thank you for the offer. Based on my research and experience in similar roles, I was expecting a range of $X-Y. Can we discuss aligning the compensation with market standards?</p>
                                <button class="text-xs text-primary-color hover:text-primary-light font-medium">Use This Response →</button>
                            </div>
                            
                            <div class="suggestion-card bg-white/5 rounded-lg p-3 border border-white/10" onclick="useSuggestion(2)">
                                <div class="flex items-start justify-between mb-2">
                                    <span class="text-xs px-2 py-1 rounded bg-purple-500/20 text-purple-300">Collaborative</span>
                                    <div class="flex items-center">
                                        <div class="confidence-meter w-16 mr-2">
                                            <div class="confidence-fill bg-gradient-to-r from-green-500 to-green-400" style="width: 75%"></div>
                                        </div>
                                        <span class="text-xs text-gray-400">75%</span>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-300 mb-2">I'm excited about this opportunity. I believe my skills in [key areas] will bring significant value. Could we explore options to reach a compensation that reflects this mutual benefit?</p>
                                <button class="text-xs text-primary-color hover:text-primary-light font-medium">Use This Response →</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div>
                        <h3 class="text-white font-semibold mb-3 text-sm">Quick Actions</h3>
                        <div class="space-y-2">
                            <button class="w-full py-2 bg-green-500/20 text-green-300 rounded-lg text-sm font-medium hover:bg-green-500/30 transition">
                                Accept Current Offer
                            </button>
                            <button class="w-full py-2 bg-primary-color/20 text-primary-light rounded-lg text-sm font-medium hover:bg-primary-color/30 transition">
                                Present Counter Offer
                            </button>
                            <button class="w-full py-2 bg-yellow-500/20 text-yellow-300 rounded-lg text-sm font-medium hover:bg-yellow-500/30 transition">
                                Request More Time
                            </button>
                        </div>
                    </div>
                    
                    <!-- Session Tracker -->
                    <div>
                        <h3 class="text-white font-semibold mb-3 text-sm">Session Insights</h3>
                        <div class="space-y-2">
                            <div class="bg-white/5 rounded-lg p-3">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-xs text-gray-400">Messages Exchanged</span>
                                    <span class="text-sm font-semibold text-white">{{ $messages->count() }}</span>
                                </div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-xs text-gray-400">Key Points Discussed</span>
                                    <span class="text-sm font-semibold text-white">{{ $session->key_points_discussed ? count($session->key_points_discussed) : 0 }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-400">Positive Signals</span>
                                    <span class="text-sm font-semibold text-green-400">{{ $session->employer_signals ? count(array_filter($session->employer_signals, fn($s) => str_contains(strtolower($s), 'positive') || str_contains(strtolower($s), 'open'))) : 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Export Session -->
                    <div>
                        <button onclick="exportSession()" class="w-full py-3 bg-white/10 text-white rounded-lg font-medium hover:bg-white/20 transition flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Download Session PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentSender = 'user';

function setSender(sender) {
    currentSender = sender;
    
    // Update button styles
    document.querySelectorAll('.sender-toggle').forEach(btn => {
        btn.classList.remove('active');
        if (sender === 'employer') {
            btn.classList.remove('bg-blue-500/20', 'text-blue-300');
            btn.classList.add('bg-gray-500/20', 'text-gray-300');
        } else {
            btn.classList.remove('bg-gray-500/20', 'text-gray-300');
            btn.classList.add('bg-blue-500/20', 'text-blue-300');
        }
    });
    
    if (sender === 'employer') {
        document.getElementById('employerBtn').classList.add('active');
        document.getElementById('employerBtn').classList.remove('bg-gray-500/20', 'text-gray-300');
        document.getElementById('employerBtn').classList.add('bg-gray-500/30', 'text-white');
    } else {
        document.getElementById('userBtn').classList.add('active');
        document.getElementById('userBtn').classList.remove('bg-blue-500/20', 'text-blue-300');
        document.getElementById('userBtn').classList.add('bg-blue-500/30', 'text-white');
    }
}

async function sendMessage(event) {
    event.preventDefault();
    
    const input = document.getElementById('messageInput');
    const content = input.value.trim();
    
    if (!content) return;
    
    try {
        const response = await fetch('/api/negotiation/session/{{ $session->id }}/message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                content: content,
                sender: currentSender
            })
        });
        
        if (!response.ok) throw new Error('Failed to send message');
        
        const data = await response.json();
        
        // Clear input
        input.value = '';
        
        // Reload page to show new message and AI analysis
        // In production, use WebSocket or polling for real-time updates
        window.location.reload();
        
    } catch (error) {
        console.error('Error sending message:', error);
        alert('Failed to send message. Please try again.');
    }
}

function useSuggestion(suggestionId) {
    // In production, this would populate the message input with the suggestion
    const suggestions = [
        '',
        'Thank you for the offer. Based on my research and experience in similar roles, I was expecting a range of $X-Y. Can we discuss aligning the compensation with market standards?',
        'I\'m excited about this opportunity. I believe my skills in [key areas] will bring significant value. Could we explore options to reach a compensation that reflects this mutual benefit?'
    ];
    
    if (suggestions[suggestionId]) {
        document.getElementById('messageInput').value = suggestions[suggestionId];
        document.getElementById('messageInput').focus();
    }
}

async function endSession() {
    if (!confirm('Are you sure you want to end this coaching session?')) return;
    
    try {
        const response = await fetch('/api/negotiation/session/{{ $session->id }}/end', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        if (!response.ok) throw new Error('Failed to end session');
        
        window.location.href = '{{ route("negotiation.strategy", $strategy->id) }}';
        
    } catch (error) {
        console.error('Error ending session:', error);
        alert('Failed to end session. Please try again.');
    }
}

async function exportSession() {
    try {
        window.open('/api/negotiation/session/{{ $session->id }}/export-pdf', '_blank');
    } catch (error) {
        console.error('Error exporting session:', error);
        alert('Failed to export session. Please try again.');
    }
}

// Auto-scroll to bottom of messages
document.addEventListener('DOMContentLoaded', function() {
    const messagesArea = document.getElementById('messagesArea');
    messagesArea.scrollTop = messagesArea.scrollHeight;
});

// Auto-focus message input
document.getElementById('messageInput').focus();
</script>
@endpush
@endsection
