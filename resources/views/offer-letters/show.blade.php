@extends('layouts.app')

@php
    $isEmployer = auth()->user()->hasAnyRole(['employer', 'recruiter', 'admin']);
    $routePrefix = $isEmployer ? 'offer-letters.' : 'candidate.offers.';
@endphp

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route($routePrefix . 'index') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 mb-4">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to Offers
            </a>
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $offerLetter->job_title }}</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        @if(auth()->user()->hasAnyRole(['employer', 'recruiter']))
                            Offer for {{ $offerLetter->candidate->name ?? 'Unknown' }}
                        @else
                            From {{ $offerLetter->company->name ?? 'Unknown Company' }}
                        @endif
                    </p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    @switch($offerLetter->status)
                        @case('draft') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @break
                        @case('sent')
                        @case('viewed')
                        @case('under_review') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 @break
                        @case('accepted') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 @break
                        @case('declined')
                        @case('withdrawn')
                        @case('expired') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 @break
                        @case('counter_offered') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 @break
                        @default bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                    @endswitch
                ">
                    {{ ucfirst(str_replace('_', ' ', $offerLetter->status)) }}
                </span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mb-6 flex flex-wrap gap-3">
            @can('update', $offerLetter)
                @if($offerLetter->isDraft())
                    <a href="{{ route('offer-letters.edit', $offerLetter) }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </a>
                    <form action="{{ route('offer-letters.send', $offerLetter) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            Send Offer
                        </button>
                    </form>
                @endif
                @if($offerLetter->isSent())
                    <form action="{{ route('offer-letters.withdraw', $offerLetter) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to withdraw this offer?')">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Withdraw Offer
                        </button>
                    </form>
                @endif
            @endcan

            @can('respond', $offerLetter)
                <button onclick="acceptOffer()" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Accept Offer
                </button>
                <button onclick="showDeclineModal()" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Decline
                </button>
                <button onclick="showCounterOfferModal()" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                    Counter Offer
                </button>
            @endcan

            <a href="{{ route($routePrefix . 'download', $offerLetter) }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700" target="_blank">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Download PDF
            </a>
        </div>

        <!-- Position Details -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Position Details</h3>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Job Title</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $offerLetter->job_title }}</dd>
                </div>
                @if($offerLetter->department)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Department</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $offerLetter->department }}</dd>
                </div>
                @endif
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Employment Type</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ ucfirst($offerLetter->employment_type) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Work Arrangement</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ ucfirst($offerLetter->work_arrangement) }}</dd>
                </div>
                @if($offerLetter->work_location)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Location</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $offerLetter->work_location }}</dd>
                </div>
                @endif
                @if($offerLetter->reporting_to)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Reports To</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $offerLetter->reporting_to }}</dd>
                </div>
                @endif
            </dl>
        </div>

        <!-- Compensation -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Compensation</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4">
                    <dt class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Base Salary</dt>
                    <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $offerLetter->formatted_salary }}</dd>
                </div>
                @if($offerLetter->signing_bonus)
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                    <dt class="text-sm font-medium text-green-600 dark:text-green-400">Signing Bonus</dt>
                    <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">${{ number_format($offerLetter->signing_bonus, 0) }}</dd>
                </div>
                @endif
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                    <dt class="text-sm font-medium text-purple-600 dark:text-purple-400">Total Compensation</dt>
                    <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">${{ number_format($offerLetter->total_compensation, 0) }}/yr</dd>
                </div>
            </div>
            @if($offerLetter->annual_bonus_target)
            <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                <strong>Annual Bonus Target:</strong> {{ $offerLetter->annual_bonus_target }}% of base salary
            </div>
            @endif
            @if($offerLetter->bonus_structure)
            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                <strong>Bonus Structure:</strong> {{ $offerLetter->bonus_structure }}
            </div>
            @endif
        </div>

        <!-- Equity -->
        @if($offerLetter->equity_shares)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Equity</h3>
            <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Shares</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($offerLetter->equity_shares) }}</dd>
                </div>
                @if($offerLetter->equity_type)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Type</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $offerLetter->equity_type }}</dd>
                </div>
                @endif
                @if($offerLetter->vesting_schedule)
                <div class="md:col-span-3">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Vesting Schedule</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $offerLetter->vesting_schedule }}</dd>
                </div>
                @endif
            </dl>
        </div>
        @endif

        <!-- Benefits -->
        @if($offerLetter->benefitsPackage)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Benefits Package: {{ $offerLetter->benefitsPackage->name }}
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Estimated Annual Value: <span class="font-semibold">${{ number_format($offerLetter->benefitsPackage->total_value, 0) }}</span>
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($offerLetter->benefitsPackage->getFormattedBenefits() as $category => $benefits)
                <div>
                    <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">{{ $category }}</h4>
                    <ul class="space-y-1">
                        @foreach($benefits as $benefit)
                        <li class="text-sm text-gray-600 dark:text-gray-400 flex items-start">
                            <svg class="w-4 h-4 mr-2 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ $benefit['name'] }}
                            @if(!empty($benefit['annual_value']))
                                <span class="ml-1 text-gray-400">(~${{ number_format($benefit['annual_value'], 0) }}/yr)</span>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Timeline -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Timeline</h3>
            <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Start Date</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $offerLetter->start_date?->format('F j, Y') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Offer Expires</dt>
                    <dd class="mt-1 text-sm font-semibold {{ $offerLetter->is_expired ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                        {{ $offerLetter->offer_expiry_date?->format('F j, Y') }}
                        @if($offerLetter->is_expired)
                            (Expired)
                        @endif
                    </dd>
                </div>
                @if($offerLetter->response_deadline)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Response Deadline</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $offerLetter->response_deadline->format('F j, Y') }}</dd>
                </div>
                @endif
            </dl>
        </div>

        <!-- Counter Offers -->
        @if($offerLetter->counterOffers->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Counter Offer History</h3>
            <div class="space-y-4">
                @foreach($offerLetter->counterOffers as $counter)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex justify-between items-start mb-3">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Round {{ $counter->round_number }}</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if($counter->is_accepted) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                            @elseif($counter->is_rejected) bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                            @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                            @endif
                        ">
                            {{ ucfirst(str_replace('_', ' ', $counter->status)) }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        @if($counter->requested_salary)
                        <div>
                            <span class="text-gray-500">Requested Salary:</span>
                            <span class="text-gray-900 dark:text-white ml-1">${{ number_format($counter->requested_salary, 0) }}</span>
                            @if($counter->salary_difference)
                                <span class="text-{{ $counter->salary_difference > 0 ? 'green' : 'red' }}-600 ml-1">
                                    ({{ $counter->salary_difference > 0 ? '+' : '' }}${{ number_format($counter->salary_difference, 0) }})
                                </span>
                            @endif
                        </div>
                        @endif
                        @if($counter->requested_signing_bonus)
                        <div>
                            <span class="text-gray-500">Requested Signing Bonus:</span>
                            <span class="text-gray-900 dark:text-white ml-1">${{ number_format($counter->requested_signing_bonus, 0) }}</span>
                        </div>
                        @endif
                    </div>
                    @if($counter->justification)
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $counter->justification }}</p>
                    @endif
                    @if($counter->employer_response)
                    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <strong>Employer Response:</strong> {{ $counter->employer_response }}
                        </p>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Activity Log -->
        @if($offerLetter->activities->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Activity Log</h3>
            <div class="flow-root">
                <ul class="-mb-8">
                    @foreach($offerLetter->activities as $activity)
                    <li>
                        <div class="relative pb-8">
                            @if(!$loop->last)
                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-800
                                        @switch($activity->color)
                                            @case('success') bg-green-500 @break
                                            @case('danger') bg-red-500 @break
                                            @case('warning') bg-yellow-500 @break
                                            @case('info') bg-blue-500 @break
                                            @default bg-gray-500
                                        @endswitch
                                    ">
                                        <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @switch($activity->action)
                                                @case('created')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                    @break
                                                @case('sent')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                                    @break
                                                @case('viewed')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    @break
                                                @case('accepted')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    @break
                                                @case('declined')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    @break
                                                @default
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            @endswitch
                                        </svg>
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $activity->description }}
                                            @if($activity->user)
                                                <span class="font-medium text-gray-900 dark:text-white">by {{ $activity->user->name }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        {{ $activity->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Decline Modal -->
<div id="decline-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModals()"></div>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Decline Offer</h3>
                <textarea id="decline-reason" rows="3" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" placeholder="Reason for declining (optional)..."></textarea>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                <button onclick="declineOffer()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                    Decline Offer
                </button>
                <button onclick="closeModals()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Counter Offer Modal -->
<div id="counter-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModals()"></div>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Submit Counter Offer</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Requested Salary</label>
                        <input type="number" id="counter-salary" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" value="{{ $offerLetter->base_salary }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Requested Signing Bonus</label>
                        <input type="number" id="counter-signing-bonus" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" value="{{ $offerLetter->signing_bonus }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Justification</label>
                        <textarea id="counter-justification" rows="3" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" placeholder="Explain why you're requesting these changes..."></textarea>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                <button onclick="submitCounterOffer()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 sm:ml-3 sm:w-auto sm:text-sm">
                    Submit Counter Offer
                </button>
                <button onclick="closeModals()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showDeclineModal() {
    document.getElementById('decline-modal').classList.remove('hidden');
}

function showCounterOfferModal() {
    document.getElementById('counter-modal').classList.remove('hidden');
}

function closeModals() {
    document.getElementById('decline-modal').classList.add('hidden');
    document.getElementById('counter-modal').classList.add('hidden');
}

async function sendOffer() {
    if (!confirm('Send this offer letter to the candidate?')) return;
    
    try {
        const response = await fetch('{{ route('offer-letters.send', $offerLetter) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        });
        const result = await response.json();
        if (result.success) {
            location.reload();
        } else {
            alert(result.message || 'Failed to send offer');
        }
    } catch (error) {
        alert('An error occurred');
    }
}

async function withdrawOffer() {
    if (!confirm('Are you sure you want to withdraw this offer? This cannot be undone.')) return;
    
    try {
        const response = await fetch('{{ route('offer-letters.withdraw', $offerLetter) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        });
        const result = await response.json();
        if (result.success) {
            location.reload();
        } else {
            alert(result.message || 'Failed to withdraw offer');
        }
    } catch (error) {
        alert('An error occurred');
    }
}

async function acceptOffer() {
    if (!confirm('Accept this job offer?')) return;
    
    try {
        const response = await fetch('{{ route('candidate.offers.accept', $offerLetter) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        });
        const result = await response.json();
        if (result.success) {
            alert('Congratulations! You have accepted the offer.');
            location.reload();
        } else {
            alert(result.message || 'Failed to accept offer');
        }
    } catch (error) {
        alert('An error occurred');
    }
}

async function declineOffer() {
    const reason = document.getElementById('decline-reason').value;
    
    try {
        const response = await fetch('{{ route('candidate.offers.decline', $offerLetter) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ reason })
        });
        const result = await response.json();
        if (result.success) {
            location.reload();
        } else {
            alert(result.message || 'Failed to decline offer');
        }
    } catch (error) {
        alert('An error occurred');
    }
}

async function submitCounterOffer() {
    const data = {
        requested_salary: document.getElementById('counter-salary').value,
        requested_signing_bonus: document.getElementById('counter-signing-bonus').value,
        justification: document.getElementById('counter-justification').value,
    };
    
    try {
        const response = await fetch('{{ route('candidate.offers.counter-offer', $offerLetter) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            location.reload();
        } else {
            alert(result.message || 'Failed to submit counter offer');
        }
    } catch (error) {
        alert('An error occurred');
    }
}
</script>
@endsection
