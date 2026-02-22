@extends('layouts.app')

@section('title', $user->name . ' - Profile - StudAI Career')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-4xl mx-auto px-4 py-8">
        {{-- Profile Header --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
            {{-- Cover Image --}}
            <div class="h-40 bg-gradient-to-r from-indigo-500 to-purple-600"></div>
            
            {{-- Profile Info --}}
            <div class="relative px-6 pb-6">
                <div class="flex flex-col md:flex-row md:items-end md:justify-between">
                    <div class="flex items-end space-x-4 -mt-12 md:-mt-16">
                        <img src="{{ $user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&size=128' }}" 
                             alt="{{ $user->name }}"
                             class="w-24 h-24 md:w-32 md:h-32 rounded-full border-4 border-white dark:border-gray-800 shadow-lg">
                        <div class="pb-2">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h1>
                            <p class="text-gray-500">{{ $user->candidateProfile?->current_title ?? 'Professional' }}</p>
                            @if($user->candidateProfile?->location)
                                <p class="text-sm text-gray-400">
                                    <x-heroicon-o-map-pin class="h-4 w-4 inline" />
                                    {{ $user->candidateProfile->location }}
                                </p>
                            @endif
                        </div>
                    </div>
                    
                    @if($user->id !== auth()->id())
                        @php
                            $networkingService = app(\App\Services\NetworkingService::class);
                            $isConnected = $networkingService->areConnected(auth()->id(), $user->id);
                            $connectionDegree = $networkingService->getConnectionDegree(auth()->user(), $user);
                        @endphp
                        <div class="mt-4 md:mt-0 flex items-center space-x-3">
                            @if($isConnected)
                                <span class="px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg text-sm font-medium">
                                    <x-heroicon-s-check class="h-4 w-4 inline mr-1" />
                                    Connected
                                </span>
                                <a href="{{ route('network.messages') }}"
                                   class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                                    Message
                                </a>
                            @else
                                @if($connectionDegree > 0)
                                    <span class="text-sm text-gray-500">
                                        {{ $connectionDegree }}{{ $connectionDegree == 1 ? 'st' : ($connectionDegree == 2 ? 'nd' : 'rd') }} degree connection
                                    </span>
                                @endif
                                <a href="{{ route('network.connections') }}"
                                   class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                                    Connect
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Connection Stats --}}
                <div class="mt-6 flex items-center space-x-6 text-sm">
                    <div>
                        <span class="font-semibold text-gray-900 dark:text-white">
                            {{ $user->sentConnections()->where('status', 'accepted')->count() + $user->receivedConnections()->where('status', 'accepted')->count() }}
                        </span>
                        <span class="text-gray-500">connections</span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-900 dark:text-white">
                            {{ $user->followers()->count() }}
                        </span>
                        <span class="text-gray-500">followers</span>
                    </div>
                    @if($user->id !== auth()->id() && $isConnected ?? false)
                        @php
                            $mutualConnections = $networkingService->getMutualConnections(auth()->user(), $user);
                        @endphp
                        @if($mutualConnections->count() > 0)
                            <div>
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    {{ $mutualConnections->count() }}
                                </span>
                                <span class="text-gray-500">mutual connections</span>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Left Column --}}
            <div class="md:col-span-2 space-y-6">
                {{-- About --}}
                @if($user->candidateProfile?->bio)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">About</h2>
                        <p class="text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ $user->candidateProfile->bio }}</p>
                    </div>
                @endif

                {{-- Experience --}}
                @if($user->candidateProfile?->experience && count($user->candidateProfile->experience) > 0)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Experience</h2>
                        <div class="space-y-4">
                            @foreach($user->candidateProfile->experience as $exp)
                                <div class="flex space-x-4">
                                    <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                        <x-heroicon-o-building-office class="h-6 w-6 text-gray-400" />
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-900 dark:text-white">{{ $exp['title'] ?? 'Position' }}</h3>
                                        <p class="text-sm text-gray-500">{{ $exp['company'] ?? '' }}</p>
                                        <p class="text-xs text-gray-400">{{ $exp['duration'] ?? '' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Skills --}}
                @if($user->candidateProfile?->skills && count($user->candidateProfile->skills) > 0)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Skills</h2>
                        <div class="flex flex-wrap gap-2">
                            @foreach($user->candidateProfile->skills as $skill)
                                <span class="px-3 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-full text-sm">
                                    {{ $skill }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Recent Posts --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Activity</h2>
                    @php
                        $posts = $user->posts()
                            ->where(function($q) use ($isConnected) {
                                if ($isConnected ?? false) {
                                    $q->whereIn('visibility', ['public', 'connections']);
                                } else {
                                    $q->where('visibility', 'public');
                                }
                            })
                            ->latest()
                            ->take(3)
                            ->get();
                    @endphp
                    
                    @forelse($posts as $post)
                        <div class="border-b border-gray-100 dark:border-gray-700 last:border-0 py-4 first:pt-0 last:pb-0">
                            <p class="text-gray-700 dark:text-gray-300 line-clamp-3">{{ $post->content }}</p>
                            <p class="text-xs text-gray-400 mt-2">{{ $post->created_at->diffForHumans() }}</p>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">No recent activity to show.</p>
                    @endforelse
                </div>
            </div>

            {{-- Right Column --}}
            <div class="space-y-6">
                {{-- Profile Strength --}}
                @if($user->candidateProfile)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Profile Info</h3>
                        <div class="space-y-3 text-sm">
                            @if($user->candidateProfile->industry)
                                <div class="flex items-center space-x-2 text-gray-600 dark:text-gray-400">
                                    <x-heroicon-o-briefcase class="h-4 w-4" />
                                    <span>{{ $user->candidateProfile->industry }}</span>
                                </div>
                            @endif
                            @if($user->candidateProfile->years_of_experience)
                                <div class="flex items-center space-x-2 text-gray-600 dark:text-gray-400">
                                    <x-heroicon-o-clock class="h-4 w-4" />
                                    <span>{{ $user->candidateProfile->years_of_experience }}+ years experience</span>
                                </div>
                            @endif
                            @if($user->candidateProfile->education)
                                <div class="flex items-center space-x-2 text-gray-600 dark:text-gray-400">
                                    <x-heroicon-o-academic-cap class="h-4 w-4" />
                                    <span>{{ is_array($user->candidateProfile->education) ? ($user->candidateProfile->education[0]['degree'] ?? 'Education') : 'Education' }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Groups in Common --}}
                @if($user->id !== auth()->id())
                    @php
                        $userGroupIds = $user->groupMemberships()->where('status', 'active')->pluck('group_id');
                        $myGroupIds = auth()->user()->groupMemberships()->where('status', 'active')->pluck('group_id');
                        $commonGroupIds = $userGroupIds->intersect($myGroupIds);
                        $commonGroups = \App\Models\Group::whereIn('id', $commonGroupIds)->take(3)->get();
                    @endphp
                    
                    @if($commonGroups->count() > 0)
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Groups in Common</h3>
                            <div class="space-y-3">
                                @foreach($commonGroups as $group)
                                    <a href="{{ route('network.groups.show', $group) }}" 
                                       class="flex items-center space-x-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg p-2 -m-2 transition">
                                        <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                                            <x-heroicon-o-user-group class="h-5 w-5 text-indigo-600" />
                                        </div>
                                        <span class="text-sm text-gray-900 dark:text-white">{{ $group->name }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
