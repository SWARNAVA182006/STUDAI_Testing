@extends('layouts.app')

@section('title', $profile->professional_title . ' - ' . $profile->user->name)

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-8" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('marketplace.index') }}" class="text-gray-500 hover:text-indigo-600">Marketplace</a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <a href="{{ route('marketplace.freelancers') }}" class="text-gray-500 hover:text-indigo-600 ml-1">Freelancers</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-700 ml-1 font-medium">{{ $profile->user->name }}</span>
                    </div>
                </li>
            </ol>
        </nav>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Main Content -->
            <div class="lg:w-2/3">
                <!-- Profile Header -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <div class="flex flex-col md:flex-row md:items-start gap-6">
                        <div class="relative">
                            <img src="{{ $profile->user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($profile->user->name) . '&size=150' }}" 
                                 alt="{{ $profile->user->name }}"
                                 class="w-32 h-32 rounded-full object-cover border-4 border-gray-100 shadow">
                            @if($profile->is_verified)
                                <div class="absolute bottom-0 right-0 bg-blue-500 text-white p-2 rounded-full">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        
                        <div class="flex-1">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900">{{ $profile->user->name }}</h1>
                                    <p class="text-lg text-indigo-600 font-medium mt-1">{{ $profile->professional_title }}</p>
                                </div>
                                <div class="hidden md:block">
                                    <div class="text-2xl font-bold text-gray-900">₹{{ number_format($profile->hourly_rate) }}/hr</div>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap items-center gap-4 mt-4 text-sm text-gray-500">
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-1 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    <span class="font-semibold text-gray-900">{{ number_format($profile->average_rating, 1) }}</span>
                                    <span class="ml-1">({{ $profile->total_reviews }} reviews)</span>
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    </svg>
                                    {{ $profile->user->location ?? 'India' }}
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Member since {{ $profile->user->created_at->format('M Y') }}
                                </span>
                            </div>

                            <div class="md:hidden mt-4">
                                <div class="text-2xl font-bold text-gray-900">₹{{ number_format($profile->hourly_rate) }}/hr</div>
                            </div>

                            <!-- Badges -->
                            @if($profile->badges && $profile->badges->isNotEmpty())
                                <div class="flex flex-wrap gap-2 mt-4">
                                    @foreach($profile->badges as $userBadge)
                                        <span class="inline-flex items-center px-3 py-1 bg-indigo-100 text-indigo-700 text-sm font-medium rounded-full">
                                            {{ $userBadge->badge->icon ?? '🏆' }} {{ $userBadge->badge->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- About -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">About</h2>
                    <div class="prose prose-indigo max-w-none text-gray-600">
                        {!! nl2br(e($profile->bio)) !!}
                    </div>
                    @if($profile->overview)
                        <div class="mt-4 prose prose-indigo max-w-none text-gray-600">
                            {!! nl2br(e($profile->overview)) !!}
                        </div>
                    @endif
                </div>

                <!-- Skills -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Skills</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach($profile->skills ?? [] as $skill)
                            <span class="px-3 py-1.5 bg-indigo-100 text-indigo-700 text-sm font-medium rounded-full">
                                {{ $skill }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <!-- Portfolio -->
                @if($profile->portfolio && is_array($profile->portfolio) && count($profile->portfolio) > 0)
                    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Portfolio</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($profile->portfolio as $item)
                                <div class="border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition">
                                    @if(isset($item['image']))
                                        <img src="{{ $item['image'] }}" alt="{{ $item['title'] ?? 'Portfolio item' }}" class="w-full h-40 object-cover">
                                    @endif
                                    <div class="p-4">
                                        <h3 class="font-medium text-gray-900">{{ $item['title'] ?? 'Project' }}</h3>
                                        <p class="text-gray-600 text-sm mt-1">{{ $item['description'] ?? '' }}</p>
                                        @if(isset($item['url']))
                                            <a href="{{ $item['url'] }}" target="_blank" class="text-indigo-600 text-sm hover:text-indigo-700 mt-2 inline-block">
                                                View Project →
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Reviews -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">Reviews</h2>
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span class="text-xl font-bold text-gray-900 ml-1">{{ number_format($profile->average_rating, 1) }}</span>
                            <span class="text-gray-500 ml-2">({{ $profile->total_reviews }} reviews)</span>
                        </div>
                    </div>

                    <div class="space-y-6">
                        @forelse($reviews ?? [] as $review)
                            <div class="border-b border-gray-100 pb-6 last:border-0 last:pb-0">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center">
                                        <img src="{{ $review->reviewer->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($review->reviewer->name ?? 'R') }}" 
                                             alt="{{ $review->reviewer->name ?? 'Reviewer' }}"
                                             class="w-10 h-10 rounded-full mr-3">
                                        <div>
                                            <h4 class="font-medium text-gray-900">{{ $review->reviewer->name ?? 'Anonymous' }}</h4>
                                            <p class="text-gray-500 text-sm">{{ $review->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg class="w-5 h-5 {{ $i <= $review->overall_rating ? 'text-yellow-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        @endfor
                                    </div>
                                </div>
                                <p class="text-gray-600">{{ $review->review_text }}</p>
                                
                                <!-- Rating Breakdown -->
                                @if($review->communication_rating || $review->quality_rating || $review->timeliness_rating)
                                    <div class="flex flex-wrap gap-4 mt-3 text-sm">
                                        @if($review->communication_rating)
                                            <span class="text-gray-500">Communication: <span class="text-gray-900 font-medium">{{ $review->communication_rating }}/5</span></span>
                                        @endif
                                        @if($review->quality_rating)
                                            <span class="text-gray-500">Quality: <span class="text-gray-900 font-medium">{{ $review->quality_rating }}/5</span></span>
                                        @endif
                                        @if($review->timeliness_rating)
                                            <span class="text-gray-500">Timeliness: <span class="text-gray-900 font-medium">{{ $review->timeliness_rating }}/5</span></span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <div class="text-gray-400 text-4xl mb-2">💬</div>
                                <p class="text-gray-500">No reviews yet</p>
                            </div>
                        @endforelse
                    </div>

                    @if(isset($reviews) && $reviews->hasPages())
                        <div class="mt-6">
                            {{ $reviews->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:w-1/3">
                <!-- Contact Card -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6 sticky top-8">
                    <div class="text-center mb-6">
                        <div class="text-3xl font-bold text-gray-900">₹{{ number_format($profile->hourly_rate) }}/hr</div>
                        <p class="text-gray-500 text-sm mt-1">Hourly Rate</p>
                    </div>

                    @auth
                        @if(auth()->id() !== $profile->user_id)
                            <a href="{{ route('marketplace.employer.invite', $profile) }}" 
                               class="block w-full px-6 py-3 bg-indigo-600 text-white font-semibold text-center rounded-lg hover:bg-indigo-700 transition mb-3">
                                Invite to Project
                            </a>
                            <a href="{{ route('marketplace.message', $profile) }}" 
                               class="block w-full px-6 py-3 border border-indigo-600 text-indigo-600 font-semibold text-center rounded-lg hover:bg-indigo-50 transition">
                                Send Message
                            </a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" 
                           class="block w-full px-6 py-3 bg-indigo-600 text-white font-semibold text-center rounded-lg hover:bg-indigo-700 transition mb-3">
                            Sign in to Contact
                        </a>
                    @endauth
                </div>

                <!-- Stats -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Statistics</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Completed Projects</span>
                            <span class="font-semibold text-gray-900">{{ $profile->completed_projects ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Ongoing Projects</span>
                            <span class="font-semibold text-gray-900">{{ $profile->ongoing_projects ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Success Rate</span>
                            <span class="font-semibold text-green-600">{{ $profile->success_rate ?? 100 }}%</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total Earnings</span>
                            <span class="font-semibold text-gray-900">₹{{ number_format($profile->total_earnings ?? 0) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Availability -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Availability</h3>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full mr-2 
                                {{ $profile->availability == 'available' ? 'bg-green-500' : ($profile->availability == 'limited' ? 'bg-yellow-500' : 'bg-gray-400') }}"></span>
                            <span class="text-gray-700">{{ ucfirst($profile->availability ?? 'Available') }}</span>
                        </div>
                        @if($profile->hours_per_week)
                            <p class="text-gray-600 text-sm">{{ $profile->hours_per_week }} hrs/week</p>
                        @endif
                        <div class="flex flex-wrap gap-2 mt-2">
                            @if($profile->available_for_remote)
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">Remote</span>
                            @endif
                            @if($profile->available_for_onsite)
                                <span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded-full">On-site</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Languages -->
                @if($profile->languages && count($profile->languages) > 0)
                    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Languages</h3>
                        <div class="space-y-2">
                            @foreach($profile->languages as $language)
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-700">{{ $language['name'] ?? $language }}</span>
                                    <span class="text-gray-500 text-sm">{{ $language['level'] ?? 'Fluent' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Certifications -->
                @if($profile->certifications && count($profile->certifications) > 0)
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Certifications</h3>
                        <div class="space-y-3">
                            @foreach($profile->certifications as $cert)
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                    </svg>
                                    <div>
                                        <p class="text-gray-900 font-medium">{{ $cert['name'] ?? $cert }}</p>
                                        @if(isset($cert['issuer']))
                                            <p class="text-gray-500 text-sm">{{ $cert['issuer'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
