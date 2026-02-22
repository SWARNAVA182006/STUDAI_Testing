@extends('layouts.app')

@section('title', 'Badges')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-indigo-50 to-blue-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Badge Collection</h1>
                <p class="text-gray-600 mt-1">Collect badges to showcase your achievements and skills</p>
            </div>
            <a href="{{ route('gamification.dashboard') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                ← Back to Dashboard
            </a>
        </div>

        <!-- Tabs: My Badges / All Badges -->
        <div class="bg-white rounded-xl shadow-sm p-1 mb-8 inline-flex">
            <button id="myBadgesTab" onclick="showTab('myBadges')" 
                    class="px-6 py-2 rounded-lg font-medium transition bg-indigo-600 text-white">
                My Badges ({{ $userBadges->count() }})
            </button>
            <button id="allBadgesTab" onclick="showTab('allBadges')"
                    class="px-6 py-2 rounded-lg font-medium transition text-gray-600 hover:bg-gray-100">
                All Badges
            </button>
        </div>

        <!-- My Badges Section -->
        <div id="myBadgesSection">
            @if($userBadges->count() > 0)
            <!-- Featured Badges -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Featured on Profile</h2>
                <p class="text-gray-600 text-sm mb-4">Click badges to toggle display on your public profile (max 6)</p>
                
                <div class="flex flex-wrap gap-4">
                    @foreach($userBadges->where('is_featured', true)->take(6) as $userBadge)
                    @php $badge = $userBadge->badge; @endphp
                    <div class="relative group">
                        <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-4xl cursor-pointer transition-all hover:scale-105 ring-2 ring-indigo-500"
                             style="background: {{ $badge->color }}20"
                             onclick="toggleFeatured({{ $badge->id }})">
                            {{ $badge->icon }}
                        </div>
                        <div class="absolute -top-2 -right-2 w-6 h-6 bg-indigo-500 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- All My Badges Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach($userBadges as $userBadge)
                @php 
                    $badge = $userBadge->badge;
                    $rarityData = App\Models\GamificationBadge::RARITIES[$badge->rarity] ?? App\Models\GamificationBadge::RARITIES['common'];
                @endphp
                <div class="bg-white rounded-2xl shadow-sm p-4 text-center group hover:shadow-lg transition cursor-pointer {{ $userBadge->is_featured ? 'ring-2 ring-indigo-500' : '' }}"
                     onclick="toggleFeatured({{ $badge->id }})">
                    <div class="w-16 h-16 mx-auto rounded-xl flex items-center justify-center text-3xl mb-3"
                         style="background: {{ $badge->color }}20">
                        {{ $badge->icon }}
                    </div>
                    <h3 class="font-semibold text-gray-900 text-sm mb-1 line-clamp-1">{{ $badge->name }}</h3>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium"
                          style="background: {{ $rarityData['color'] }}20; color: {{ $rarityData['color'] }}">
                        {{ $rarityData['name'] }}
                    </span>
                    <p class="text-xs text-gray-400 mt-2">{{ $userBadge->earned_at->format('M d, Y') }}</p>
                </div>
                @endforeach
            </div>
            @else
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <div class="text-6xl mb-4">🏅</div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">No badges yet</h3>
                <p class="text-gray-500 mb-6">Complete achievements and activities to earn badges!</p>
                <a href="{{ route('gamification.achievements') }}" 
                   class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 transition">
                    View Achievements
                </a>
            </div>
            @endif
        </div>

        <!-- All Badges Section (Hidden by default) -->
        <div id="allBadgesSection" class="hidden">
            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm p-4 mb-8">
                <div class="flex flex-wrap items-center gap-4">
                    <span class="text-sm font-medium text-gray-700">Filter by:</span>
                    
                    <select id="categoryFilter" onchange="filterBadges()"
                            class="rounded-lg border-gray-300 text-sm">
                        <option value="">All Categories</option>
                        @foreach($categories as $key => $name)
                        <option value="{{ $key }}">{{ $name }}</option>
                        @endforeach
                    </select>

                    <select id="rarityFilter" onchange="filterBadges()"
                            class="rounded-lg border-gray-300 text-sm">
                        <option value="">All Rarities</option>
                        @foreach($rarities as $key => $data)
                        <option value="{{ $key }}">{{ $data['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Badge Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
                @foreach($badges as $item)
                @php 
                    $badge = $item['badge'];
                    $isOwned = $item['is_owned'];
                    $isPurchasable = $item['is_purchasable'];
                    $rarityData = $rarities[$badge->rarity] ?? $rarities['common'];
                @endphp
                <div class="badge-card bg-white rounded-2xl shadow-sm overflow-hidden transition-all hover:shadow-lg {{ $isOwned ? 'ring-2 ring-green-500' : '' }}"
                     data-category="{{ $badge->category }}"
                     data-rarity="{{ $badge->rarity }}">
                    
                    <!-- Rarity Bar -->
                    <div class="h-1.5" style="background: {{ $rarityData['color'] }}"></div>
                    
                    <div class="p-6">
                        <!-- Badge Icon -->
                        <div class="w-20 h-20 mx-auto rounded-2xl flex items-center justify-center text-4xl mb-4 {{ $isOwned ? '' : 'grayscale opacity-60' }}"
                             style="background: {{ $badge->color }}20">
                            {{ $badge->icon }}
                        </div>

                        <!-- Badge Info -->
                        <h3 class="font-bold text-gray-900 text-center mb-1">{{ $badge->name }}</h3>
                        <p class="text-sm text-gray-500 text-center line-clamp-2 mb-3">{{ $badge->description }}</p>

                        <!-- Rarity & Category -->
                        <div class="flex items-center justify-center gap-2 mb-4">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                  style="background: {{ $rarityData['color'] }}20; color: {{ $rarityData['color'] }}">
                                {{ $rarityData['name'] }}
                            </span>
                        </div>

                        <!-- Status/Action -->
                        @if($isOwned)
                        <div class="text-center">
                            <span class="inline-flex items-center gap-1 text-green-600 font-medium text-sm">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Owned
                            </span>
                        </div>
                        @elseif($isPurchasable)
                        <button onclick="purchaseBadge({{ $badge->id }}, '{{ $badge->name }}', {{ $badge->purchase_cost }})"
                                class="w-full py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition text-sm">
                            {{ number_format($badge->purchase_cost) }} Points
                        </button>
                        @else
                        <div class="text-center text-sm text-gray-400">
                            Unlock via achievements
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Purchase Confirmation Modal -->
<div id="purchaseModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold text-gray-900 mb-2">Purchase Badge</h3>
        <p class="text-gray-600 mb-4">Are you sure you want to purchase <strong id="badgeName"></strong> for <strong id="badgeCost"></strong> points?</p>
        <div class="flex gap-3">
            <button onclick="confirmPurchase()" class="flex-1 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
                Confirm Purchase
            </button>
            <button onclick="closePurchaseModal()" class="flex-1 py-2 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition">
                Cancel
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
let purchaseBadgeId = null;

function showTab(tab) {
    const myBadgesSection = document.getElementById('myBadgesSection');
    const allBadgesSection = document.getElementById('allBadgesSection');
    const myBadgesTab = document.getElementById('myBadgesTab');
    const allBadgesTab = document.getElementById('allBadgesTab');
    
    if (tab === 'myBadges') {
        myBadgesSection.classList.remove('hidden');
        allBadgesSection.classList.add('hidden');
        myBadgesTab.classList.add('bg-indigo-600', 'text-white');
        myBadgesTab.classList.remove('text-gray-600');
        allBadgesTab.classList.remove('bg-indigo-600', 'text-white');
        allBadgesTab.classList.add('text-gray-600');
    } else {
        myBadgesSection.classList.add('hidden');
        allBadgesSection.classList.remove('hidden');
        allBadgesTab.classList.add('bg-indigo-600', 'text-white');
        allBadgesTab.classList.remove('text-gray-600');
        myBadgesTab.classList.remove('bg-indigo-600', 'text-white');
        myBadgesTab.classList.add('text-gray-600');
    }
}

function filterBadges() {
    const category = document.getElementById('categoryFilter').value;
    const rarity = document.getElementById('rarityFilter').value;
    const cards = document.querySelectorAll('.badge-card');
    
    cards.forEach(card => {
        const matchCategory = !category || card.dataset.category === category;
        const matchRarity = !rarity || card.dataset.rarity === rarity;
        
        card.style.display = matchCategory && matchRarity ? '' : 'none';
    });
}

function toggleFeatured(badgeId) {
    fetch(`/gamification/badges/${badgeId}/toggle-featured`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Failed to update badge');
        }
    });
}

function purchaseBadge(badgeId, badgeName, cost) {
    purchaseBadgeId = badgeId;
    document.getElementById('badgeName').textContent = badgeName;
    document.getElementById('badgeCost').textContent = cost.toLocaleString();
    document.getElementById('purchaseModal').classList.remove('hidden');
}

function closePurchaseModal() {
    document.getElementById('purchaseModal').classList.add('hidden');
    purchaseBadgeId = null;
}

function confirmPurchase() {
    if (!purchaseBadgeId) return;
    
    fetch(`/gamification/badges/${purchaseBadgeId}/purchase`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        closePurchaseModal();
        if (data.success) {
            alert('Badge purchased successfully!');
            window.location.reload();
        } else {
            alert(data.message || 'Failed to purchase badge');
        }
    });
}
</script>
@endpush
@endsection
