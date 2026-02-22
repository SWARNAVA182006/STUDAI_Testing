@extends('layouts.app')

@section('title', 'Negotiation Scripts - ' . $strategy->role)

@push('styles')
<style>
    .tab-button {
        transition: all 0.3s ease;
        border-bottom: 2px solid transparent;
    }
    .tab-button.active {
        color: #ec4899;
        border-bottom-color: #ec4899;
    }
    .tab-button:hover:not(.active) {
        color: #f9a8d4;
    }
    .script-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .script-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(236, 72, 153, 0.2);
    }
    .placeholder {
        background: linear-gradient(90deg, rgba(236, 72, 153, 0.2) 0%, rgba(236, 72, 153, 0.4) 50%, rgba(236, 72, 153, 0.2) 100%);
        color: #ec4899;
        padding: 0 4px;
        border-radius: 4px;
        font-weight: 600;
    }
    .tactic-highlight {
        background: rgba(59, 130, 246, 0.15);
        border-left: 3px solid #3b82f6;
        padding: 8px 12px;
        margin: 12px 0;
        border-radius: 4px;
    }
    .copy-btn {
        transition: all 0.2s ease;
    }
    .copy-btn:hover {
        transform: scale(1.05);
    }
    .copy-btn.copied {
        background: #10b981 !important;
    }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('negotiation.strategy', $strategy->id) }}" class="inline-flex items-center text-sm text-gray-400 hover:text-white mb-4 transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Strategy
        </a>
        
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">Negotiation Scripts</h1>
                <p class="text-gray-400">{{ $strategy->role }} at {{ $strategy->company_name }}</p>
            </div>
            
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-400">{{ $scripts->count() }} scripts</span>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="mb-6">
        <div class="border-b border-white/10">
            <div class="flex space-x-8">
                <button class="tab-button active py-3 px-1 text-sm font-semibold" data-communication="email" onclick="filterByCommunication('email')">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Email
                    </div>
                </button>
                <button class="tab-button py-3 px-1 text-sm font-semibold text-gray-400" data-communication="phone" onclick="filterByCommunication('phone')">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        Phone
                    </div>
                </button>
                <button class="tab-button py-3 px-1 text-sm font-semibold text-gray-400" data-communication="in_person" onclick="filterByCommunication('in_person')">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        In-Person
                    </div>
                </button>
                <button class="tab-button py-3 px-1 text-sm font-semibold text-gray-400" data-communication="video_call" onclick="filterByCommunication('video_call')">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        Video Call
                    </div>
                </button>
            </div>
        </div>
    </div>

    <!-- Stage Filters -->
    <div class="mb-6 flex items-center space-x-3">
        <span class="text-sm text-gray-400">Stage:</span>
        <button class="stage-filter active px-4 py-2 rounded-lg bg-white/10 text-white text-sm font-medium hover:bg-white/20 transition" data-stage="all" onclick="filterByStage('all')">
            All Stages
        </button>
        <button class="stage-filter px-4 py-2 rounded-lg bg-white/5 text-gray-400 text-sm font-medium hover:bg-white/10 transition" data-stage="initial_response" onclick="filterByStage('initial_response')">
            Initial Response
        </button>
        <button class="stage-filter px-4 py-2 rounded-lg bg-white/5 text-gray-400 text-sm font-medium hover:bg-white/10 transition" data-stage="counter_offer" onclick="filterByStage('counter_offer')">
            Counter Offer
        </button>
        <button class="stage-filter px-4 py-2 rounded-lg bg-white/5 text-gray-400 text-sm font-medium hover:bg-white/10 transition" data-stage="follow_up" onclick="filterByStage('follow_up')">
            Follow-Up
        </button>
        <button class="stage-filter px-4 py-2 rounded-lg bg-white/5 text-gray-400 text-sm font-medium hover:bg-white/10 transition" data-stage="closing" onclick="filterByStage('closing')">
            Closing
        </button>
    </div>

    <!-- Script Cards Grid -->
    <div id="scriptsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @foreach($scripts as $script)
        <div class="script-card bg-white/5 backdrop-filter backdrop-blur-lg rounded-2xl p-6 border border-white/10" 
             data-communication="{{ $script->communication_method }}" 
             data-stage="{{ $script->stage }}"
             onclick="viewScriptDetail({{ $script->id }})">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-white mb-2">{{ $script->script_name }}</h3>
                    <div class="flex items-center space-x-2 mb-2">
                        <span class="text-xs px-2 py-1 rounded-full bg-primary-color/20 text-primary-light">
                            {{ ucfirst(str_replace('_', ' ', $script->stage)) }}
                        </span>
                        <span class="text-xs px-2 py-1 rounded-full bg-secondary-color/20 text-secondary-color">
                            {{ ucfirst(str_replace('_', ' ', $script->communication_method)) }}
                        </span>
                    </div>
                    @if($script->tone)
                    <p class="text-xs text-gray-400">Tone: {{ ucfirst($script->tone) }}</p>
                    @endif
                </div>
                
                <div class="w-10 h-10 bg-gradient-to-br from-primary-color/20 to-primary-light/20 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-primary-color" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
            
            @if($script->subject_line && $script->communication_method === 'email')
            <div class="mb-3">
                <p class="text-xs text-gray-500 mb-1">Subject:</p>
                <p class="text-sm text-gray-300 italic">{{ Str::limit($script->subject_line, 60) }}</p>
            </div>
            @endif
            
            <p class="text-sm text-gray-400 mb-4 line-clamp-3">
                {{ Str::limit(strip_tags($script->opening ?? $script->body), 120) }}
            </p>
            
            <div class="flex items-center justify-between text-xs text-gray-500">
                <span>{{ count($script->key_talking_points ?? []) }} talking points</span>
                <span class="text-primary-color hover:text-primary-light font-medium">View Full Script →</span>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Script Detail Modals -->
    @foreach($scripts as $script)
    <div id="scriptModal{{ $script->id }}" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50" onclick="if(event.target === this) closeScriptDetail({{ $script->id }})">
        <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl max-w-5xl w-full mx-4 max-h-[90vh] overflow-y-auto border border-white/10 shadow-2xl">
            <div class="sticky top-0 bg-gradient-to-r from-primary-color to-primary-light p-6 rounded-t-2xl z-10">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-white mb-2">{{ $script->script_name }}</h2>
                        <div class="flex items-center space-x-3">
                            <span class="px-3 py-1 rounded-full bg-white/20 text-white text-sm font-medium">
                                {{ ucfirst(str_replace('_', ' ', $script->stage)) }}
                            </span>
                            <span class="px-3 py-1 rounded-full bg-white/20 text-white text-sm font-medium">
                                {{ ucfirst(str_replace('_', ' ', $script->communication_method)) }}
                            </span>
                            @if($script->tone)
                            <span class="text-white/80 text-sm">Tone: {{ ucfirst($script->tone) }}</span>
                            @endif
                        </div>
                    </div>
                    <button onclick="closeScriptDetail({{ $script->id }})" class="text-white/80 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- Personalization Tool -->
                <div class="bg-gradient-to-r from-purple-500/10 to-indigo-500/10 border border-purple-500/20 rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <svg class="w-5 h-5 text-purple-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                        </svg>
                        <h3 class="text-white font-semibold">Personalization Settings</h3>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-300 mb-2">Your Name</label>
                            <input type="text" id="yourName{{ $script->id }}" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-primary-color" placeholder="e.g., John Smith" oninput="updatePreview({{ $script->id }})">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-300 mb-2">Hiring Manager Name</label>
                            <input type="text" id="managerName{{ $script->id }}" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-primary-color" placeholder="e.g., Sarah Johnson" oninput="updatePreview({{ $script->id }})">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-300 mb-2">Role Title</label>
                            <input type="text" id="roleTitle{{ $script->id }}" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-primary-color" value="{{ $strategy->role }}" oninput="updatePreview({{ $script->id }})">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-300 mb-2">Company Name</label>
                            <input type="text" id="companyName{{ $script->id }}" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-primary-color" value="{{ $strategy->company_name }}" oninput="updatePreview({{ $script->id }})">
                        </div>
                    </div>
                </div>

                <!-- Script Preview -->
                <div class="bg-white/5 rounded-xl p-6 border border-white/10" id="scriptPreview{{ $script->id }}">
                    @if($script->subject_line && $script->communication_method === 'email')
                    <div class="mb-6 pb-4 border-b border-white/10">
                        <p class="text-xs text-gray-500 mb-2">Subject:</p>
                        <p class="text-white font-medium">{{ $script->subject_line }}</p>
                    </div>
                    @endif
                    
                    @if($script->opening)
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-400 mb-3">Opening</h4>
                        <div class="text-gray-300 leading-relaxed whitespace-pre-line">{{ $script->opening }}</div>
                    </div>
                    @endif
                    
                    @if($script->body)
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-400 mb-3">Main Body</h4>
                        <div class="text-gray-300 leading-relaxed whitespace-pre-line">{{ $script->body }}</div>
                    </div>
                    @endif
                    
                    @if($script->closing)
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-400 mb-3">Closing</h4>
                        <div class="text-gray-300 leading-relaxed whitespace-pre-line">{{ $script->closing }}</div>
                    </div>
                    @endif
                </div>

                <!-- Key Talking Points -->
                @if($script->key_talking_points && count($script->key_talking_points) > 0)
                <div class="bg-blue-500/10 border-l-4 border-blue-500 rounded-lg p-4">
                    <h4 class="text-white font-semibold mb-3 flex items-center">
                        <svg class="w-5 h-5 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        Key Talking Points
                    </h4>
                    <ul class="space-y-2">
                        @foreach($script->key_talking_points as $point)
                        <li class="flex items-start text-sm text-gray-300">
                            <span class="text-blue-400 mr-2">•</span>
                            <span>{{ $point }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Phrases to Use & Avoid -->
                <div class="grid md:grid-cols-2 gap-4">
                    @if($script->phrases_to_use && count($script->phrases_to_use) > 0)
                    <div class="bg-green-500/10 border border-green-500/20 rounded-lg p-4">
                        <h4 class="text-green-300 font-semibold mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Phrases to Use
                        </h4>
                        <ul class="space-y-2">
                            @foreach($script->phrases_to_use as $phrase)
                            <li class="text-sm text-gray-300 flex items-start">
                                <span class="text-green-400 mr-2">✓</span>
                                <span>"{{ $phrase }}"</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    
                    @if($script->phrases_to_avoid && count($script->phrases_to_avoid) > 0)
                    <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-4">
                        <h4 class="text-red-300 font-semibold mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Phrases to Avoid
                        </h4>
                        <ul class="space-y-2">
                            @foreach($script->phrases_to_avoid as $phrase)
                            <li class="text-sm text-gray-300 flex items-start">
                                <span class="text-red-400 mr-2">✗</span>
                                <span>"{{ $phrase }}"</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>

                <!-- Tactical Annotations -->
                @if($script->tactical_annotations && count($script->tactical_annotations) > 0)
                <div class="space-y-3">
                    <h4 class="text-white font-semibold flex items-center">
                        <svg class="w-5 h-5 text-purple-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        Negotiation Tactics Used
                    </h4>
                    @foreach($script->tactical_annotations as $tactic => $description)
                    <div class="tactic-highlight">
                        <h5 class="text-blue-300 font-semibold text-sm mb-1">{{ ucfirst(str_replace('_', ' ', $tactic)) }}</h5>
                        <p class="text-gray-300 text-sm">{{ $description }}</p>
                    </div>
                    @endforeach
                </div>
                @endif

                <!-- Action Buttons -->
                <div class="flex space-x-4 pt-4 border-t border-white/10">
                    <button onclick="copyScript({{ $script->id }})" class="copy-btn flex-1 py-3 bg-gradient-to-r from-primary-color to-primary-light text-white rounded-lg font-semibold hover:shadow-lg transition flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                        </svg>
                        <span class="copy-text">Copy to Clipboard</span>
                    </button>
                    <button onclick="closeScriptDetail({{ $script->id }})" class="px-6 py-3 bg-white/5 text-white rounded-lg font-semibold hover:bg-white/10 transition">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

@push('scripts')
<script>
// Filter by communication method
let currentCommunication = 'email';
let currentStage = 'all';

function filterByCommunication(method) {
    currentCommunication = method;
    applyFilters();
    
    // Update tab styles
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
        btn.classList.add('text-gray-400');
    });
    document.querySelector(`[data-communication="${method}"]`).classList.add('active');
    document.querySelector(`[data-communication="${method}"]`).classList.remove('text-gray-400');
}

function filterByStage(stage) {
    currentStage = stage;
    applyFilters();
    
    // Update stage filter styles
    document.querySelectorAll('.stage-filter').forEach(btn => {
        btn.classList.remove('active', 'bg-white/10', 'text-white');
        btn.classList.add('bg-white/5', 'text-gray-400');
    });
    document.querySelector(`[data-stage="${stage}"]`).classList.add('active', 'bg-white/10', 'text-white');
    document.querySelector(`[data-stage="${stage}"]`).classList.remove('bg-white/5', 'text-gray-400');
}

function applyFilters() {
    const scripts = document.querySelectorAll('.script-card');
    scripts.forEach(script => {
        const scriptComm = script.getAttribute('data-communication');
        const scriptStage = script.getAttribute('data-stage');
        
        const matchesComm = scriptComm === currentCommunication;
        const matchesStage = currentStage === 'all' || scriptStage === currentStage;
        
        if (matchesComm && matchesStage) {
            script.style.display = 'block';
        } else {
            script.style.display = 'none';
        }
    });
}

// Script detail modal functions
function viewScriptDetail(scriptId) {
    document.getElementById('scriptModal' + scriptId).classList.remove('hidden');
    document.getElementById('scriptModal' + scriptId).classList.add('flex');
}

function closeScriptDetail(scriptId) {
    document.getElementById('scriptModal' + scriptId).classList.add('hidden');
    document.getElementById('scriptModal' + scriptId).classList.remove('flex');
}

// Personalization preview update
function updatePreview(scriptId) {
    const yourName = document.getElementById('yourName' + scriptId).value || '[Your Name]';
    const managerName = document.getElementById('managerName' + scriptId).value || '[Hiring Manager Name]';
    const roleTitle = document.getElementById('roleTitle' + scriptId).value || '[Role]';
    const companyName = document.getElementById('companyName' + scriptId).value || '[Company]';
    
    const preview = document.getElementById('scriptPreview' + scriptId);
    let content = preview.innerHTML;
    
    // Replace placeholders
    content = content.replace(/\[Your Name\]/g, `<span class="placeholder">${yourName}</span>`);
    content = content.replace(/\[Hiring Manager Name\]/g, `<span class="placeholder">${managerName}</span>`);
    content = content.replace(/\[Role\]/g, `<span class="placeholder">${roleTitle}</span>`);
    content = content.replace(/\[Company\]/g, `<span class="placeholder">${companyName}</span>`);
    
    preview.innerHTML = content;
}

// Copy script to clipboard
async function copyScript(scriptId) {
    const preview = document.getElementById('scriptPreview' + scriptId);
    const text = preview.innerText;
    
    try {
        await navigator.clipboard.writeText(text);
        
        // Visual feedback
        const btn = event.currentTarget;
        const originalText = btn.querySelector('.copy-text').textContent;
        btn.classList.add('copied');
        btn.querySelector('.copy-text').textContent = 'Copied!';
        
        setTimeout(() => {
            btn.classList.remove('copied');
            btn.querySelector('.copy-text').textContent = originalText;
        }, 2000);
    } catch (err) {
        alert('Failed to copy script. Please select and copy manually.');
    }
}

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('[id^="scriptModal"]').forEach(modal => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
    }
});

// Initialize filters on page load
document.addEventListener('DOMContentLoaded', function() {
    applyFilters();
});
</script>
@endpush
@endsection
