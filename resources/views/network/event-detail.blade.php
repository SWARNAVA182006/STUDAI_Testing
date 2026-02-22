@extends('layouts.app')

@section('title', $event->title . ' - Events')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Back Link -->
        <a href="{{ route('network.events') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-6">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Events
        </a>

        <!-- Event Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Cover Image -->
            <div class="aspect-video bg-gradient-to-br from-blue-500 to-purple-600 relative">
                @if($event->cover_image)
                    <img src="{{ $event->cover_image }}" alt="{{ $event->title }}" class="w-full h-full object-cover">
                @else
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg class="w-24 h-24 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                @endif
                <div class="absolute top-4 left-4 flex gap-2">
                    <span class="px-3 py-1 text-sm font-medium rounded-full {{ $event->type === 'virtual' ? 'bg-blue-100 text-blue-800' : ($event->type === 'in_person' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800') }}">
                        {{ ucfirst(str_replace('_', ' ', $event->type)) }}
                    </span>
                    @if($event->is_featured)
                        <span class="px-3 py-1 text-sm font-medium bg-yellow-100 text-yellow-800 rounded-full">Featured</span>
                    @endif
                </div>
            </div>

            <!-- Event Content -->
            <div class="p-6 lg:p-8">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                    <!-- Main Info -->
                    <div class="flex-1">
                        <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $event->title }}</h1>

                        <!-- Organizer -->
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 bg-gray-200 rounded-full overflow-hidden">
                                @if($event->organizer->profile?->avatar)
                                    <img src="{{ $event->organizer->profile->avatar }}" alt="{{ $event->organizer->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-500 font-medium">
                                        {{ strtoupper(substr($event->organizer->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Organized by</p>
                                <a href="{{ route('network.profile.show', $event->organizer) }}" class="font-medium text-gray-900 hover:text-blue-600">
                                    {{ $event->organizer->name }}
                                </a>
                            </div>
                        </div>

                        <!-- Event Details -->
                        <div class="space-y-4 mb-8">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $event->starts_at->format('l, F j, Y') }}</p>
                                    <p class="text-gray-600">{{ $event->starts_at->format('g:i A') }}
                                        @if($event->ends_at)
                                            - {{ $event->ends_at->format('g:i A') }}
                                        @endif
                                        ({{ $event->timezone }})
                                    </p>
                                </div>
                            </div>

                            @if($event->location)
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $event->location }}</p>
                                        <a href="https://maps.google.com/?q={{ urlencode($event->location) }}" target="_blank" class="text-blue-600 hover:underline text-sm">
                                            View on Map
                                        </a>
                                    </div>
                                </div>
                            @endif

                            @if($event->virtual_link)
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">Virtual Event</p>
                                        @php
                                            $userRsvp = $event->getRsvpForUser(auth()->user());
                                        @endphp
                                        @if($userRsvp?->status === 'going')
                                            <a href="{{ $event->virtual_link }}" target="_blank" class="text-blue-600 hover:underline text-sm">
                                                Join Meeting
                                            </a>
                                        @else
                                            <p class="text-sm text-gray-500">RSVP to get the meeting link</p>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $event->attendee_count }} attending</p>
                                    @if($event->capacity)
                                        <p class="text-sm text-gray-600">{{ $event->capacity - $event->attendee_count }} spots remaining</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        @if($event->description)
                            <div class="mb-8">
                                <h2 class="text-lg font-semibold text-gray-900 mb-3">About this event</h2>
                                <div class="prose prose-sm max-w-none text-gray-600">
                                    {!! nl2br(e($event->description)) !!}
                                </div>
                            </div>
                        @endif

                        <!-- Tags -->
                        @if($event->tags && count($event->tags) > 0)
                            <div class="mb-8">
                                <h2 class="text-lg font-semibold text-gray-900 mb-3">Tags</h2>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($event->tags as $tag)
                                        <span class="px-3 py-1 bg-gray-100 text-gray-700 text-sm rounded-full">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Group Info -->
                        @if($event->group)
                            <div class="mb-8">
                                <h2 class="text-lg font-semibold text-gray-900 mb-3">Hosted by</h2>
                                <a href="{{ route('network.groups.show', $event->group) }}" class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition">
                                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                        @if($event->group->icon)
                                            <img src="{{ $event->group->icon }}" alt="{{ $event->group->name }}" class="w-full h-full object-cover rounded-lg">
                                        @else
                                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $event->group->name }}</p>
                                        <p class="text-sm text-gray-500">{{ $event->group->member_count }} members</p>
                                    </div>
                                </a>
                            </div>
                        @endif
                    </div>

                    <!-- RSVP Card -->
                    <div class="w-full lg:w-80 flex-shrink-0">
                        <div class="sticky top-6 bg-gray-50 rounded-xl p-6 border border-gray-200">
                            @php
                                $userRsvp = $event->getRsvpForUser(auth()->user());
                                $isPast = $event->isPast();
                            @endphp

                            @if($isPast)
                                <div class="text-center py-4">
                                    <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <p class="font-medium text-gray-900">This event has ended</p>
                                    <p class="text-sm text-gray-500 mt-1">{{ $event->starts_at->diffForHumans() }}</p>
                                </div>
                            @elseif($userRsvp?->status === 'going')
                                <div class="text-center mb-4">
                                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <p class="font-medium text-green-700">You're going!</p>
                                    <p class="text-sm text-gray-500 mt-1">See you there</p>
                                </div>
                                <livewire:network.event-rsvp-button :event="$event" />
                            @elseif($event->isFull())
                                <div class="text-center py-4">
                                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </div>
                                    <p class="font-medium text-gray-900">This event is full</p>
                                    <p class="text-sm text-gray-500 mt-1">No more spots available</p>
                                </div>
                            @else
                                <div class="space-y-3">
                                    <livewire:network.event-rsvp-button :event="$event" />
                                </div>
                            @endif

                            <!-- Share -->
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <p class="text-sm font-medium text-gray-700 mb-3">Share this event</p>
                                <div class="flex gap-2">
                                    <button onclick="navigator.share ? navigator.share({title: '{{ $event->title }}', url: window.location.href}) : navigator.clipboard.writeText(window.location.href).then(() => alert('Link copied!'))"
                                            class="flex-1 px-3 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                        </svg>
                                        Share
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendees Section -->
        <div class="mt-8 bg-white rounded-2xl shadow-sm border border-gray-200 p-6 lg:p-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Attendees ({{ $event->attendee_count }})</h2>
            
            @php
                $attendees = $event->attendees()->with('user.profile')->limit(20)->get();
            @endphp

            @if($attendees->count() > 0)
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 gap-4">
                    @foreach($attendees as $rsvp)
                        <a href="{{ route('network.profile.show', $rsvp->user) }}" class="group text-center">
                            <div class="w-16 h-16 mx-auto bg-gray-200 rounded-full overflow-hidden mb-2 group-hover:ring-2 group-hover:ring-blue-500 transition">
                                @if($rsvp->user->profile?->avatar)
                                    <img src="{{ $rsvp->user->profile->avatar }}" alt="{{ $rsvp->user->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-500 font-bold text-lg">
                                        {{ strtoupper(substr($rsvp->user->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <p class="text-sm font-medium text-gray-900 truncate group-hover:text-blue-600 transition">{{ $rsvp->user->name }}</p>
                        </a>
                    @endforeach
                </div>

                @if($event->attendee_count > 20)
                    <p class="text-center text-gray-500 text-sm mt-4">
                        And {{ $event->attendee_count - 20 }} more...
                    </p>
                @endif
            @else
                <p class="text-center text-gray-500 py-8">No attendees yet. Be the first to RSVP!</p>
            @endif
        </div>
    </div>
</div>
@endsection
