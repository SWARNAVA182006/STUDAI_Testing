@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('background-checks.index') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 mb-4">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to Background Checks
            </a>
            
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Background Check Details</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ $backgroundCheck->candidate->name ?? 'Candidate' }} • {{ $backgroundCheck->provider_name }}
                    </p>
                </div>
                <div class="mt-4 sm:mt-0 flex gap-2">
                    @if($backgroundCheck->isPending())
                        <button onclick="sendConsent()" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                            Send Consent Request
                        </button>
                    @endif
                    @if($backgroundCheck->isAwaitingConsent())
                        <button onclick="resendConsent()" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700">
                            Resend Consent
                        </button>
                    @endif
                    @if($backgroundCheck->isCompleted() && $backgroundCheck->report_pdf_path)
                        <a href="{{ route('background-checks.download', $backgroundCheck) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Download Report
                        </a>
                    @endif
                    @if(!$backgroundCheck->isCompleted() && !$backgroundCheck->isCancelled())
                        <button onclick="cancelCheck()" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                            Cancel
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Status Card -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Status</h3>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            @switch($backgroundCheck->status_badge_color)
                                @case('success') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 @break
                                @case('warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 @break
                                @case('danger') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 @break
                                @case('info') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 @break
                                @default bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                            @endswitch
                        ">
                            {{ ucfirst(str_replace('_', ' ', $backgroundCheck->status)) }}
                        </span>
                    </div>

                    @if($backgroundCheck->result)
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Result</span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            @switch($backgroundCheck->result_badge_color)
                                @case('success') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 @break
                                @case('warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 @break
                                @case('danger') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 @break
                                @default bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                            @endswitch
                        ">
                            {{ ucfirst($backgroundCheck->result) }}
                        </span>
                    </div>
                    @endif

                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400 mb-1">
                            <span>Progress</span>
                            <span>{{ $backgroundCheck->progress_percentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2.5">
                            <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $backgroundCheck->progress_percentage }}%"></div>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Requested</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $backgroundCheck->created_at->format('M d, Y g:i A') }}</p>
                        </div>
                        @if($backgroundCheck->consent_received_at)
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Consent Received</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $backgroundCheck->consent_received_at->format('M d, Y g:i A') }}</p>
                        </div>
                        @endif
                        @if($backgroundCheck->started_at)
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Started</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $backgroundCheck->started_at->format('M d, Y g:i A') }}</p>
                        </div>
                        @endif
                        @if($backgroundCheck->completed_at)
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Completed</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $backgroundCheck->completed_at->format('M d, Y g:i A') }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Individual Checks -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Verification Checks</h3>
                    
                    <div class="space-y-3">
                        @forelse($backgroundCheck->items as $item)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex items-center">
                                @if($item->isCompleted())
                                    @if($item->isClear())
                                        <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-yellow-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                @elseif($item->isInProgress())
                                    <svg class="w-5 h-5 text-blue-500 mr-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-gray-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                                <span class="font-medium text-gray-900 dark:text-white">{{ $item->check_type_label }}</span>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @switch($item->status_badge_color)
                                    @case('success') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 @break
                                    @case('warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 @break
                                    @case('danger') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 @break
                                    @case('info') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 @break
                                    @default bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                @endswitch
                            ">
                                {{ ucfirst($item->result ?? $item->status) }}
                            </span>
                        </div>
                        @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No individual checks recorded yet.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Flags (if any) -->
                @if($backgroundCheck->has_flags && $backgroundCheck->flags)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-yellow-800 dark:text-yellow-300 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        Findings Requiring Review
                    </h3>
                    
                    <div class="space-y-3">
                        @foreach($backgroundCheck->flags as $flag)
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                            <div class="font-medium text-gray-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $flag['type'] ?? 'Unknown')) }}</div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $flag['details'] ?? 'Additional review recommended' }}</p>
                        </div>
                        @endforeach
                    </div>

                    @if(!$backgroundCheck->adverseAction)
                    <div class="mt-4">
                        <button onclick="showAdverseActionModal()" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                            Initiate Adverse Action
                        </button>
                    </div>
                    @endif
                </div>
                @endif

                <!-- Adverse Action Status -->
                @if($backgroundCheck->adverseAction)
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-red-800 dark:text-red-300 mb-4">Adverse Action Process</h3>
                    
                    @php $adverse = $backgroundCheck->adverseAction; @endphp
                    
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Status</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $adverse->status_label }}</span>
                        </div>
                        @if($adverse->pre_adverse_sent_at)
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Pre-Adverse Notice Sent</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $adverse->pre_adverse_sent_at->format('M d, Y') }}</span>
                        </div>
                        @endif
                        @if($adverse->isInWaitingPeriod())
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Waiting Period Ends</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $adverse->waiting_period_ends_at->format('M d, Y') }} ({{ $adverse->days_remaining }} days left)</span>
                        </div>
                        @endif
                        @if($adverse->candidate_disputed)
                        <div class="mt-4 p-3 bg-white dark:bg-gray-800 rounded-lg">
                            <div class="font-medium text-gray-900 dark:text-white mb-1">Candidate Dispute</div>
                            <p class="text-gray-600 dark:text-gray-400">{{ $adverse->dispute_reason }}</p>
                        </div>
                        @endif
                    </div>

                    <div class="mt-4 flex gap-2">
                        @if($adverse->canSendFinalAction())
                        <button onclick="sendFinalAdverseAction()" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                            Send Final Adverse Action
                        </button>
                        @endif
                        @if(!$adverse->isCompleted())
                        <button onclick="withdrawAdverseAction()" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            Withdraw Adverse Action
                        </button>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Internal Notes -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Internal Notes</h3>
                    <form id="notes-form">
                        <textarea name="internal_notes" rows="4" 
                                  class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                  placeholder="Add internal notes about this background check...">{{ $backgroundCheck->internal_notes }}</textarea>
                        <div class="mt-2 flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white">
                                Save Notes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Candidate Info -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Candidate</h3>
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0 h-12 w-12">
                            <div class="h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                <span class="text-lg font-medium text-indigo-600 dark:text-indigo-300">
                                    {{ strtoupper(substr($backgroundCheck->candidate->name ?? 'U', 0, 2)) }}
                                </span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $backgroundCheck->candidate->name ?? 'Unknown' }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $backgroundCheck->candidate->email ?? '' }}</div>
                        </div>
                    </div>
                    @if($backgroundCheck->application)
                    <div class="text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Applied for:</span>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $backgroundCheck->application->job->title ?? 'Unknown Position' }}</p>
                    </div>
                    @endif
                </div>

                <!-- Check Details -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Details</h3>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Provider</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $backgroundCheck->provider_name }}</dd>
                        </div>
                        @if($backgroundCheck->package)
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Package</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $backgroundCheck->package->name }}</dd>
                        </div>
                        @endif
                        @if($backgroundCheck->cost)
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Cost</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">${{ number_format($backgroundCheck->cost, 2) }}</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Requested By</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $backgroundCheck->requester->name ?? 'Unknown' }}</dd>
                        </div>
                        @if($backgroundCheck->provider_check_id)
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Provider ID</dt>
                            <dd class="font-mono text-xs text-gray-900 dark:text-white">{{ $backgroundCheck->provider_check_id }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>

                <!-- Activity Log -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Activity</h3>
                    <div class="flow-root">
                        <ul class="-mb-8">
                            @foreach($backgroundCheck->activities->take(10) as $activity)
                            <li>
                                <div class="relative pb-8">
                                    @if(!$loop->last)
                                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-800
                                                @switch($activity->action_color)
                                                    @case('success') bg-green-100 text-green-600 @break
                                                    @case('danger') bg-red-100 text-red-600 @break
                                                    @case('warning') bg-yellow-100 text-yellow-600 @break
                                                    @case('info') bg-blue-100 text-blue-600 @break
                                                    @default bg-gray-100 text-gray-600
                                                @endswitch
                                            ">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm text-gray-900 dark:text-white">{{ $activity->action_label }}</p>
                                            @if($activity->description)
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $activity->description }}</p>
                                            @endif
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $activity->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Adverse Action Modal -->
<div id="adverse-action-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="hideAdverseActionModal()"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Initiate Adverse Action</h3>
            <form id="adverse-action-form">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason for Adverse Action</label>
                    <textarea name="reason" rows="4" required class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Waiting Period (Days)</label>
                    <input type="number" name="waiting_period_days" value="5" min="5" max="14" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Minimum 5 days required by FCRA</p>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="hideAdverseActionModal()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Initiate</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const csrfToken = '{{ csrf_token() }}';
const checkId = {{ $backgroundCheck->id }};

async function sendConsent() {
    if (!confirm('Send consent request to the candidate?')) return;
    try {
        const response = await fetch(`/background-checks/${checkId}/send-consent`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        });
        const result = await response.json();
        alert(result.message);
        if (result.success) location.reload();
    } catch (e) { alert('An error occurred'); }
}

async function resendConsent() {
    if (!confirm('Resend consent request?')) return;
    try {
        const response = await fetch(`/background-checks/${checkId}/resend-consent`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        });
        const result = await response.json();
        alert(result.message);
        if (result.success) location.reload();
    } catch (e) { alert('An error occurred'); }
}

async function cancelCheck() {
    const reason = prompt('Please provide a reason for cancellation:');
    if (!reason) return;
    try {
        const response = await fetch(`/background-checks/${checkId}/cancel`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ reason })
        });
        const result = await response.json();
        alert(result.message);
        if (result.success) location.reload();
    } catch (e) { alert('An error occurred'); }
}

function showAdverseActionModal() {
    document.getElementById('adverse-action-modal').classList.remove('hidden');
}

function hideAdverseActionModal() {
    document.getElementById('adverse-action-modal').classList.add('hidden');
}

document.getElementById('adverse-action-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    try {
        const response = await fetch(`/background-checks/${checkId}/adverse-action`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({
                reason: formData.get('reason'),
                waiting_period_days: parseInt(formData.get('waiting_period_days'))
            })
        });
        const result = await response.json();
        alert(result.message);
        if (result.success) location.reload();
    } catch (e) { alert('An error occurred'); }
});

document.getElementById('notes-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    try {
        const response = await fetch(`/background-checks/${checkId}/notes`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ internal_notes: formData.get('internal_notes') })
        });
        const result = await response.json();
        alert(result.message);
    } catch (e) { alert('An error occurred'); }
});

async function sendFinalAdverseAction() {
    const reason = prompt('Provide final reason for adverse action:');
    if (!reason) return;
    try {
        const response = await fetch(`/background-checks/${checkId}/adverse-action/final`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ reason })
        });
        const result = await response.json();
        alert(result.message);
        if (result.success) location.reload();
    } catch (e) { alert('An error occurred'); }
}

async function withdrawAdverseAction() {
    const notes = prompt('Reason for withdrawal (optional):');
    try {
        const response = await fetch(`/background-checks/${checkId}/adverse-action/withdraw`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ notes })
        });
        const result = await response.json();
        alert(result.message);
        if (result.success) location.reload();
    } catch (e) { alert('An error occurred'); }
}
</script>
@endpush
@endsection
