<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Subscription') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Success/Error Messages -->
            @if(request()->get('payment') == 'success')
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">
                            Payment successful! Your subscription is now active.
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Current Plan Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ $subscription->subscriptionPlan->name }}</h3>
                            <p class="mt-1 text-gray-600">{{ $subscription->subscriptionPlan->description }}</p>
                            
                            <div class="mt-4 flex items-baseline">
                                <span class="text-3xl font-bold text-indigo-600">
                                    ₹{{ number_format($subscription->subscriptionPlan->price_monthly) }}
                                </span>
                                <span class="ml-2 text-gray-500">/month</span>
                            </div>

                            <div class="mt-4 space-y-1 text-sm">
                                <p class="text-gray-600">
                                    <strong>Status:</strong>
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        {{ $subscription->status == 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ ucfirst($subscription->status) }}
                                    </span>
                                </p>
                                <p class="text-gray-600">
                                    <strong>Started:</strong> {{ $subscription->started_at->format('M d, Y') }}
                                </p>
                                @if($subscription->ends_at)
                                <p class="text-gray-600">
                                    <strong>Renews:</strong> {{ $subscription->ends_at->format('M d, Y') }}
                                </p>
                                @endif
                            </div>
                        </div>

                        <div class="text-right">
                            <a href="{{ route('subscriptions.pricing') }}" 
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                                Change Plan
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usage Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Applications Usage -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Job Applications</h4>
                        <div class="mt-4">
                            <div class="flex items-baseline">
                                <span class="text-3xl font-bold text-gray-900">{{ $usage['applications']['used'] }}</span>
                                <span class="ml-2 text-gray-500">/ {{ $usage['applications']['limit'] == -1 ? '∞' : $usage['applications']['limit'] }}</span>
                            </div>
                            @if($usage['applications']['limit'] != -1)
                            <div class="mt-4">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min($usage['applications']['percentage'], 100) }}%"></div>
                                </div>
                                <p class="mt-2 text-xs text-gray-500">{{ $usage['applications']['percentage'] }}% used this month</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- AI Credits Usage -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wide">AI Credits</h4>
                        <div class="mt-4">
                            <div class="flex items-baseline">
                                <span class="text-3xl font-bold text-gray-900">{{ $usage['ai_credits']['used'] }}</span>
                                <span class="ml-2 text-gray-500">/ {{ $usage['ai_credits']['limit'] == -1 ? '∞' : $usage['ai_credits']['limit'] }}</span>
                            </div>
                            @if($usage['ai_credits']['limit'] != -1)
                            <div class="mt-4">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min($usage['ai_credits']['percentage'], 100) }}%"></div>
                                </div>
                                <p class="mt-2 text-xs text-gray-500">{{ $usage['ai_credits']['percentage'] }}% used this month</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Assessments Usage -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Skill Assessments</h4>
                        <div class="mt-4">
                            <div class="flex items-baseline">
                                <span class="text-3xl font-bold text-gray-900">{{ $usage['assessments']['used'] }}</span>
                                <span class="ml-2 text-gray-500">/ {{ $usage['assessments']['limit'] == -1 ? '∞' : $usage['assessments']['limit'] }}</span>
                            </div>
                            @if($usage['assessments']['limit'] != -1)
                            <div class="mt-4">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ min($usage['assessments']['percentage'], 100) }}%"></div>
                                </div>
                                <p class="mt-2 text-xs text-gray-500">{{ $usage['assessments']['percentage'] }}% used this month</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment History</h3>
                    
                    @if($transactions->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($transactions as $transaction)
                                <tr>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $transaction->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $subscription->subscriptionPlan->name }} Subscription
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ₹{{ number_format($transaction->amount / 100, 2) }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            {{ $transaction->status == 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-indigo-600">
                                        <a href="{{ route('payments.show', $transaction) }}" class="hover:underline">View</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-gray-500 text-center py-8">No payment history yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
