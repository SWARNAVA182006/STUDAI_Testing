@extends('layouts.app')

@section('title', 'Achievements')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-indigo-50 to-blue-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Achievements</h1>
                <p class="text-gray-600 mt-1">Complete activities to unlock achievements and earn rewards</p>
            </div>
            <a href="{{ route('gamification.dashboard') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                ← Back to Dashboard
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-8">
            <div class="flex flex-wrap items-center gap-4">
                <span class="text-sm font-medium text-gray-700">Filter by:</span>
                
                <!-- Category Filter -->
                <select id="categoryFilter" onchange="filterAchievements()" 
                        class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $key => $name)
                    <option value="{{ $key }}" {{ $selectedCategory === $key ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>

                <!-- Tier Filter -->
                <select id="tierFilter" onchange="filterAchievements()"
                        class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Tiers</option>
                    @foreach($tiers as $key => $data)
                    <option value="{{ $key }}" {{ $selectedTier === $key ? 'selected' : '' }}>{{ $data['name'] }}</option>
                    @endforeach
                </select>

                <!-- Status Filter -->
                <select id="statusFilter" onchange="filterByStatus()"
                        class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="all">All Status</option>
                    <option value="unlocked">Unlocked</option>
                    <option value="in-progress">In Progress</option>
                    <option value="locked">Locked</option>
                </select>
            </div>
        </div>

        <!-- Achievement Stats -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            @php
                $unlockedCount = collect($achievements)->filter(fn($a) => $a['is_unlocked'])->count();
                $totalCount = count($achievements);
                $tierCounts = collect($achievements)->groupBy(fn($a) => $a['achievement']->tier);
            @endphp
            <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                <div class="text-3xl font-bold text-gray-900">{{ $unlockedCount }}/{{ $totalCount }}</div>
                <div class="text-sm text-gray-500">Unlocked</div>
            </div>
            @foreach(['bronze', 'silver', 'gold', 'platinum', 'diamond'] as $tier)
            <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                <div class="text-3xl font-bold" style="color: {{ $tiers[$tier]['color'] }}">
                    {{ collect($achievements)->filter(fn($a) => $a['achievement']->tier === $tier && $a['is_unlocked'])->count() }}
                </div>
                <div class="text-sm text-gray-500">{{ ucfirst($tier) }}</div>
            </div>
            @endforeach
        </div>

        <!-- Achievements Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($achievements as $item)
            @php
                $achievement = $item['achievement'];
                $progress = $item['progress'];
                $isUnlocked = $item['is_unlocked'];
                $tierColor = $tiers[$achievement->tier]['color'] ?? '#CD7F32';
            @endphp
            <div class="achievement-card bg-white rounded-2xl shadow-lg overflow-hidden transition-all hover:shadow-xl {{ $isUnlocked ? 'ring-2 ring-offset-2' : 'opacity-80 hover:opacity-100' }}"
                 style="{{ $isUnlocked ? "ring-color: {$tierColor}" : '' }}"
                 data-category="{{ $achievement->category }}"
                 data-tier="{{ $achievement->tier }}"
                 data-status="{{ $isUnlocked ? 'unlocked' : ($progress['progress'] > 0 ? 'in-progress' : 'locked') }}">
                
                <!-- Header with Tier Color -->
                <div class="h-2" style="background: {{ $tierColor }}"></div>
                
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <!-- Icon -->
                        <div class="w-16 h-16 rounded-xl flex items-center justify-center text-3xl flex-shrink-0 {{ $isUnlocked ? '' : 'grayscale' }}"
                             style="background: {{ $tierColor }}15">
                            {{ $achievement->icon ?? '🏆' }}
                        </div>
                        
                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-bold text-gray-900 {{ $isUnlocked ? '' : 'text-gray-600' }}">
                                    {{ $achievement->name }}
                                </h3>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full"
                                      style="background: {{ $tierColor }}20; color: {{ $tierColor }}">
                                    {{ ucfirst($achievement->tier) }}
                                </span>
                            </div>
                            
                            <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $achievement->description }}</p>
                            
                            <!-- Category -->
                            <div class="mt-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    {{ $categories[$achievement->category] ?? $achievement->category }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Progress -->
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-500">Progress</span>
                            <span class="font-medium {{ $isUnlocked ? 'text-green-600' : 'text-gray-900' }}">
                                {{ $progress['progress'] }}/{{ $progress['target'] }}
                                @if($isUnlocked)
                                    ✓
                                @endif
                            </span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500 {{ $isUnlocked ? 'bg-green-500' : 'bg-indigo-500' }}"
                                 style="width: {{ $progress['percentage'] }}%"></div>
                        </div>
                    </div>

                    <!-- Rewards -->
                    <div class="mt-4 flex items-center justify-between">
                        <div class="flex items-center gap-3 text-sm">
                            @if($achievement->points_reward > 0)
                            <span class="flex items-center gap-1 text-yellow-600">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.736 6.979C9.208 6.193 9.696 6 10 6c.304 0 .792.193 1.264.979a1 1 0 001.715-1.029C12.279 4.784 11.232 4 10 4s-2.279.784-2.979 1.95c-.285.475-.507 1-.67 1.55H6a1 1 0 000 2h.013a9.358 9.358 0 000 1H6a1 1 0 100 2h.351c.163.55.385 1.075.67 1.55C7.721 15.216 8.768 16 10 16s2.279-.784 2.979-1.95a1 1 0 10-1.715-1.029c-.472.786-.96.979-1.264.979-.304 0-.792-.193-1.264-.979a4.265 4.265 0 01-.264-.521H10a1 1 0 100-2H8.017a7.36 7.36 0 010-1H10a1 1 0 100-2H8.472a4.265 4.265 0 01.264-.521z"/>
                                </svg>
                                {{ number_format($achievement->total_reward_points) }}
                            </span>
                            @endif
                            @if($achievement->xp_reward > 0)
                            <span class="flex items-center gap-1 text-indigo-600">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                                </svg>
                                {{ number_format($achievement->xp_reward) }} XP
                            </span>
                            @endif
                        </div>
                        
                        @if($isUnlocked)
                        <span class="text-green-600 text-sm font-medium flex items-center gap-1">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Unlocked
                        </span>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-full text-center py-12">
                <div class="text-6xl mb-4">🏆</div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">No achievements found</h3>
                <p class="text-gray-500">Try adjusting your filters</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
function filterAchievements() {
    const category = document.getElementById('categoryFilter').value;
    const tier = document.getElementById('tierFilter').value;
    
    let url = new URL(window.location.href);
    if (category) {
        url.searchParams.set('category', category);
    } else {
        url.searchParams.delete('category');
    }
    if (tier) {
        url.searchParams.set('tier', tier);
    } else {
        url.searchParams.delete('tier');
    }
    
    window.location.href = url.toString();
}

function filterByStatus() {
    const status = document.getElementById('statusFilter').value;
    const cards = document.querySelectorAll('.achievement-card');
    
    cards.forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
@endpush
@endsection
