@extends('layouts.app')

@section('title', 'Rewards Store')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-indigo-50 to-blue-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Rewards Store</h1>
                <p class="text-gray-600 mt-1">Spend your points on exclusive rewards and perks</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="bg-white rounded-xl shadow-sm px-6 py-3">
                    <div class="text-sm text-gray-500">Available Points</div>
                    <div class="text-2xl font-bold text-indigo-600">{{ number_format($profile->available_points) }}</div>
                </div>
                <a href="{{ route('gamification.dashboard') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                    ← Dashboard
                </a>
            </div>
        </div>

        <!-- Category Tabs -->
        <div class="bg-white rounded-xl shadow-sm p-1 mb-8 flex flex-wrap gap-2">
            <a href="{{ route('gamification.rewards') }}"
               class="px-4 py-2 rounded-lg font-medium transition {{ !$selectedCategory ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                All Rewards
            </a>
            @foreach($categories as $key => $name)
            <a href="{{ route('gamification.rewards', ['category' => $key]) }}"
               class="px-4 py-2 rounded-lg font-medium transition {{ $selectedCategory === $key ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                {{ $name }}
            </a>
            @endforeach
        </div>

        <!-- Featured Rewards -->
        @php $featuredRewards = collect($rewards)->filter(fn($r) => $r['reward']->is_featured)->take(3); @endphp
        @if($featuredRewards->count() > 0)
        <div class="mb-12">
            <h2 class="text-xl font-bold text-gray-900 mb-4">🔥 Featured Rewards</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($featuredRewards as $item)
                @php $reward = $item['reward']; @endphp
                <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl shadow-lg p-6 text-white">
                    @if($reward->image)
                    <img src="{{ $reward->image }}" alt="{{ $reward->name }}" class="w-full h-40 object-cover rounded-xl mb-4">
                    @else
                    <div class="w-full h-40 bg-white/10 rounded-xl flex items-center justify-center text-6xl mb-4">
                        🎁
                    </div>
                    @endif
                    
                    <h3 class="text-xl font-bold mb-2">{{ $reward->name }}</h3>
                    <p class="text-white/80 text-sm mb-4 line-clamp-2">{{ $reward->description }}</p>
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-2xl font-bold">{{ number_format($reward->points_cost) }}</div>
                            <div class="text-white/60 text-sm">points</div>
                        </div>
                        
                        @if($item['can_redeem'])
                        <button onclick="redeemReward({{ $reward->id }}, '{{ $reward->name }}', {{ $reward->points_cost }})"
                                class="px-6 py-2 bg-white text-indigo-600 font-semibold rounded-xl hover:bg-gray-100 transition">
                            Redeem
                        </button>
                        @else
                        <span class="text-white/60 text-sm">{{ $item['reason'] }}</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- All Rewards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @forelse($rewards as $item)
            @php 
                $reward = $item['reward']; 
                if($reward->is_featured) continue;
            @endphp
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden transition-all hover:shadow-xl {{ !$item['can_redeem'] ? 'opacity-75' : '' }}">
                <!-- Image/Icon -->
                @if($reward->image)
                <img src="{{ $reward->image }}" alt="{{ $reward->name }}" class="w-full h-32 object-cover">
                @else
                <div class="w-full h-32 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-5xl">
                    @switch($reward->category)
                        @case('premium_feature') ⭐ @break
                        @case('badge') 🏅 @break
                        @case('boost') 🚀 @break
                        @case('physical') 📦 @break
                        @case('partner') 🤝 @break
                        @default 🎁
                    @endswitch
                </div>
                @endif

                <div class="p-5">
                    <!-- Category & Type -->
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                            {{ $categories[$reward->category] ?? $reward->category }}
                        </span>
                        @if($reward->duration_days)
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-blue-100 text-blue-600">
                            {{ $reward->duration_days }} days
                        </span>
                        @endif
                    </div>

                    <!-- Title & Description -->
                    <h3 class="font-bold text-gray-900 mb-1">{{ $reward->name }}</h3>
                    <p class="text-sm text-gray-500 line-clamp-2 mb-4">{{ $reward->description }}</p>

                    <!-- Stock & Level -->
                    <div class="flex items-center gap-3 text-sm text-gray-500 mb-4">
                        @if($reward->remaining_stock !== null)
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"/>
                                <path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ $reward->remaining_stock }} left
                        </span>
                        @endif
                        @if($reward->level_required > 1)
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                            </svg>
                            Level {{ $reward->level_required }}+
                        </span>
                        @endif
                    </div>

                    <!-- Price & Action -->
                    <div class="flex items-center justify-between">
                        <div class="text-xl font-bold text-indigo-600">
                            {{ number_format($reward->points_cost) }}
                            <span class="text-sm font-normal text-gray-500">pts</span>
                        </div>
                        
                        @if($item['can_redeem'])
                        <button onclick="redeemReward({{ $reward->id }}, '{{ addslashes($reward->name) }}', {{ $reward->points_cost }})"
                                class="px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition text-sm">
                            Redeem
                        </button>
                        @else
                        <span class="text-sm text-gray-400">{{ $item['reason'] }}</span>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-full text-center py-12">
                <div class="text-6xl mb-4">🎁</div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">No rewards available</h3>
                <p class="text-gray-500">Check back later for new rewards!</p>
            </div>
            @endforelse
        </div>

        <!-- Your Rewards -->
        @if($userRewards->count() > 0)
        <div class="mt-12">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900">Your Active Rewards</h2>
                <a href="{{ route('gamification.my-rewards') }}" class="text-indigo-600 hover:text-indigo-700 font-medium text-sm">
                    View All →
                </a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($userRewards->take(3) as $userReward)
                <div class="bg-white rounded-xl shadow-sm p-4 flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center text-2xl">
                        ✓
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900">{{ $userReward->reward->name }}</h4>
                        <p class="text-sm text-gray-500">
                            @if($userReward->expires_at)
                            Expires {{ $userReward->expires_at->diffForHumans() }}
                            @else
                            No expiration
                            @endif
                        </p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Redeem Confirmation Modal -->
<div id="redeemModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4">
        <div class="text-center mb-4">
            <div class="text-5xl mb-2">🎁</div>
            <h3 class="text-xl font-bold text-gray-900">Redeem Reward</h3>
        </div>
        <p class="text-gray-600 text-center mb-4">
            Are you sure you want to redeem <strong id="rewardName"></strong> for <strong id="rewardCost"></strong> points?
        </p>
        <div class="flex gap-3">
            <button onclick="confirmRedeem()" class="flex-1 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
                Confirm
            </button>
            <button onclick="closeRedeemModal()" class="flex-1 py-2 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition">
                Cancel
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
let redeemRewardId = null;

function redeemReward(rewardId, rewardName, cost) {
    redeemRewardId = rewardId;
    document.getElementById('rewardName').textContent = rewardName;
    document.getElementById('rewardCost').textContent = cost.toLocaleString();
    document.getElementById('redeemModal').classList.remove('hidden');
}

function closeRedeemModal() {
    document.getElementById('redeemModal').classList.add('hidden');
    redeemRewardId = null;
}

function confirmRedeem() {
    if (!redeemRewardId) return;
    
    fetch(`/gamification/rewards/${redeemRewardId}/redeem`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        closeRedeemModal();
        if (data.success) {
            alert('Reward redeemed successfully!');
            window.location.reload();
        } else {
            alert(data.message || 'Failed to redeem reward');
        }
    });
}
</script>
@endpush
@endsection
