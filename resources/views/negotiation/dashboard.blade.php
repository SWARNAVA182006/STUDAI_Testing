@extends('layouts.app')

@section('title', 'Negotiation Dashboard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">AI Negotiation Strategist</h1>
            <p class="text-lg text-gray-600">Transform job offers into competitive compensation packages</p>
        </div>

        {{-- Quick Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            {{-- Active Strategies --}}
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-primary">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Active Strategies</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2" id="active-strategies-count">{{ $activeStrategies }}</p>
                    </div>
                    <div class="bg-primary/10 rounded-full p-3">
                        <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Total Sessions --}}
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-secondary">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Coaching Sessions</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2" id="total-sessions-count">{{ $totalSessions }}</p>
                    </div>
                    <div class="bg-secondary/10 rounded-full p-3">
                        <svg class="w-8 h-8 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Average Gain --}}
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-accent-blue">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Avg Salary Gain</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2" id="avg-gain">{{ $avgGainPercent }}%</p>
                    </div>
                    <div class="bg-accent-blue/10 rounded-full p-3">
                        <svg class="w-8 h-8 text-accent-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Success Rate --}}
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-accent-yellow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Success Rate</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2" id="success-rate">{{ $successRate }}%</p>
                    </div>
                    <div class="bg-accent-yellow/10 rounded-full p-3">
                        <svg class="w-8 h-8 text-accent-yellow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-gradient-to-r from-primary to-primary/80 rounded-2xl shadow-xl p-8 mb-8 text-white">
            <h2 class="text-2xl font-bold mb-4">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <button onclick="openNewStrategyModal()" class="bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl p-6 transition-all transform hover:scale-105">
                    <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <h3 class="text-lg font-semibold mb-1">New Strategy</h3>
                    <p class="text-sm text-white/80">Analyze a new job offer</p>
                </button>

                <a href="{{ route('negotiation.tactics') }}" class="bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl p-6 transition-all transform hover:scale-105">
                    <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <h3 class="text-lg font-semibold mb-1">Tactics Library</h3>
                    <p class="text-sm text-white/80">Browse proven techniques</p>
                </a>

                @if($activeSessions > 0)
                <a href="{{ route('negotiation.coaching.active') }}" class="bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl p-6 transition-all transform hover:scale-105">
                    <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-lg font-semibold mb-1">Resume Coaching</h3>
                    <p class="text-sm text-white/80">{{ $activeSessions }} active session(s)</p>
                </a>
                @else
                <button onclick="alert('Create a strategy first to start coaching')" class="bg-white/10 backdrop-blur-sm rounded-xl p-6 opacity-60 cursor-not-allowed">
                    <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-lg font-semibold mb-1">Start Coaching</h3>
                    <p class="text-sm text-white/80">No active sessions</p>
                </button>
                @endif
            </div>
        </div>

        {{-- Recent Strategies --}}
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="px-8 py-6 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900">Your Negotiation Strategies</h2>
            </div>

            @if($strategies->isEmpty())
            <div class="px-8 py-12 text-center">
                <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No Strategies Yet</h3>
                <p class="text-gray-600 mb-6">Create your first negotiation strategy to get started</p>
                <button onclick="openNewStrategyModal()" class="bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary/90 transition-colors">
                    Create New Strategy
                </button>
            </div>
            @else
            <div class="divide-y divide-gray-200">
                @foreach($strategies as $strategy)
                <div class="px-8 py-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $strategy->role }}</h3>
                                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $strategy->confidence_level === 'high' ? 'bg-green-100 text-green-800' : ($strategy->confidence_level === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ ucfirst($strategy->confidence_level) }} Confidence
                                </span>
                            </div>
                            <p class="text-gray-600 mb-3">{{ $strategy->company_name }} · {{ $strategy->location }}</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Offered</p>
                                    <p class="text-lg font-semibold text-gray-900">${{ number_format($strategy->offered_salary) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Optimal Ask</p>
                                    <p class="text-lg font-semibold text-primary">${{ number_format($strategy->optimal_ask) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Market Median</p>
                                    <p class="text-lg font-semibold text-gray-700">${{ number_format($strategy->market_median ?? 0) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Potential Gain</p>
                                    <p class="text-lg font-semibold text-secondary">+{{ $strategy->potential_gain_percentage }}%</p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-4 text-sm text-gray-600">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                    </svg>
                                    {{ $strategy->created_at->diffForHumans() }}
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                    </svg>
                                    {{ $strategy->scenarios()->count() }} scenarios
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                    {{ $strategy->scripts()->count() }} scripts
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-col space-y-2 ml-6">
                            <a href="{{ route('negotiation.strategy', $strategy->id) }}" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-primary/90 transition-colors text-center">
                                View Details
                            </a>
                            <a href="{{ route('negotiation.scenarios', $strategy->id) }}" class="bg-secondary text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-secondary/90 transition-colors text-center">
                                View Scenarios
                            </a>
                            @if($strategy->sessions()->where('outcome', null)->exists())
                            <a href="{{ route('negotiation.coaching', $strategy->sessions()->where('outcome', null)->first()->id) }}" class="bg-accent-blue text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-accent-blue/90 transition-colors text-center">
                                Resume Coaching
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            @if($strategies->hasPages())
            <div class="px-8 py-6 border-t border-gray-200">
                {{ $strategies->links() }}
            </div>
            @endif
            @endif
        </div>

    </div>
</div>

{{-- New Strategy Modal --}}
<div id="newStrategyModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="px-8 py-6 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900">Create New Strategy</h2>
            <button onclick="closeNewStrategyModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="newStrategyForm" class="px-8 py-6 space-y-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Job Title *</label>
                    <input type="text" name="role" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="e.g., Senior Software Engineer">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Company Name *</label>
                    <input type="text" name="company_name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="e.g., TechCorp Inc.">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Location *</label>
                    <input type="text" name="location" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="e.g., San Francisco, CA">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Offered Salary *</label>
                    <div class="relative">
                        <span class="absolute left-4 top-3 text-gray-500">$</span>
                        <input type="number" name="offered_salary" required class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="140000">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Current Salary</label>
                    <div class="relative">
                        <span class="absolute left-4 top-3 text-gray-500">$</span>
                        <input type="number" name="current_salary" class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="120000">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Years of Experience *</label>
                    <input type="number" name="experience_years" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="8">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Education Level</label>
                    <select name="education_level" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">Select...</option>
                        <option value="high_school">High School</option>
                        <option value="associate">Associate Degree</option>
                        <option value="bachelor">Bachelor's Degree</option>
                        <option value="master">Master's Degree</option>
                        <option value="phd">PhD</option>
                        <option value="mba">MBA</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Key Skills (comma-separated)</label>
                    <input type="text" name="skills" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Python, React, AWS, System Design">
                </div>

                <div>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="is_currently_employed" class="w-5 h-5 text-primary rounded border-gray-300 focus:ring-primary">
                        <span class="text-sm font-medium text-gray-700">Currently Employed</span>
                    </label>
                </div>

                <div>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="has_other_offers" class="w-5 h-5 text-primary rounded border-gray-300 focus:ring-primary">
                        <span class="text-sm font-medium text-gray-700">Have Other Offers</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                <button type="button" onclick="closeNewStrategyModal()" class="px-6 py-3 border border-gray-300 rounded-lg font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-3 bg-primary text-white rounded-lg font-semibold hover:bg-primary/90 transition-colors flex items-center">
                    <svg class="w-5 h-5 mr-2 hidden" id="loading-spinner" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Generate Strategy
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openNewStrategyModal() {
    document.getElementById('newStrategyModal').classList.remove('hidden');
}

function closeNewStrategyModal() {
    document.getElementById('newStrategyModal').classList.add('hidden');
    document.getElementById('newStrategyForm').reset();
}

document.getElementById('newStrategyForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const loadingSpinner = document.getElementById('loading-spinner');
    submitBtn.disabled = true;
    loadingSpinner.classList.remove('hidden');
    
    const formData = new FormData(this);
    const data = {
        role: formData.get('role'),
        company_name: formData.get('company_name'),
        location: formData.get('location'),
        offered_salary: parseFloat(formData.get('offered_salary')),
        current_salary: formData.get('current_salary') ? parseFloat(formData.get('current_salary')) : null,
        experience_years: parseInt(formData.get('experience_years')),
        education_level: formData.get('education_level') || null,
        skills: formData.get('skills') ? formData.get('skills').split(',').map(s => s.trim()) : [],
        is_currently_employed: formData.get('is_currently_employed') === 'on',
        has_other_offers: formData.get('has_other_offers') === 'on'
    };
    
    try {
        const response = await fetch('/api/negotiation/strategy', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.location.href = '/negotiation/strategy/' + result.strategy.id;
        } else {
            alert('Error: ' + (result.message || 'Failed to generate strategy'));
            submitBtn.disabled = false;
            loadingSpinner.classList.add('hidden');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        submitBtn.disabled = false;
        loadingSpinner.classList.add('hidden');
    }
});

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeNewStrategyModal();
    }
});
</script>
@endpush
@endsection
