@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Compare Offers</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Compare your job offers side by side to make the best decision
            </p>
        </div>

        @if($offers->isEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No offers to compare</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    You need at least 2 active offers to use the comparison tool.
                </p>
            </div>
        @else
            <!-- Offer Selection -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Select Offers to Compare</h3>
                <form id="compare-form" class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Offers</label>
                        <select multiple id="offer-select" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" size="4">
                            @foreach($offers as $offer)
                                <option value="{{ $offer->id }}">
                                    {{ $offer->job_title }} @ {{ $offer->company->name ?? 'Unknown' }} - ${{ number_format($offer->total_compensation, 0) }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Hold Ctrl/Cmd to select multiple</p>
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        Compare Selected
                    </button>
                </form>
            </div>

            <!-- Comparison Results (shown via JavaScript) -->
            <div id="comparison-results" class="hidden">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider sticky left-0 bg-gray-50 dark:bg-gray-700">
                                        Criteria
                                    </th>
                                    <!-- Dynamic columns added by JavaScript -->
                                </tr>
                            </thead>
                            <tbody id="comparison-body" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <!-- Dynamic rows added by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- AI Recommendation -->
                <div id="ai-recommendation" class="mt-6 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        AI Recommendation
                    </h3>
                    <p id="recommendation-text" class="text-gray-600 dark:text-gray-400">
                        Select at least 2 offers to get AI-powered recommendations.
                    </p>
                </div>
            </div>

            <!-- Saved Comparisons -->
            @if($comparisons->isNotEmpty())
            <div class="mt-8">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Saved Comparisons</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($comparisons as $comparison)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <h4 class="font-medium text-gray-900 dark:text-white">{{ $comparison->name }}</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ count($comparison->offer_ids ?? []) }} offers compared
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                            {{ $comparison->created_at->diffForHumans() }}
                        </p>
                        <button onclick="loadComparison({{ $comparison->id }})" class="mt-3 text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                            View Comparison →
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        @endif
    </div>
</div>

<script>
const offers = @json($offers);

document.getElementById('compare-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const selected = Array.from(document.getElementById('offer-select').selectedOptions).map(o => parseInt(o.value));
    
    if (selected.length < 2) {
        alert('Please select at least 2 offers to compare');
        return;
    }
    
    const selectedOffers = offers.filter(o => selected.includes(o.id));
    renderComparison(selectedOffers);
});

function renderComparison(selectedOffers) {
    const resultsDiv = document.getElementById('comparison-results');
    const tbody = document.getElementById('comparison-body');
    
    // Clear existing content
    tbody.innerHTML = '';
    
    // Update header
    const thead = document.querySelector('#comparison-results thead tr');
    thead.innerHTML = '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider sticky left-0 bg-gray-50 dark:bg-gray-700">Criteria</th>';
    
    selectedOffers.forEach(offer => {
        thead.innerHTML += `
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                ${offer.job_title}<br>
                <span class="text-indigo-600 dark:text-indigo-400">${offer.company?.name || 'Unknown'}</span>
            </th>
        `;
    });
    
    // Rows to compare
    const rows = [
        { label: 'Base Salary', key: 'base_salary', format: 'money' },
        { label: 'Total Compensation', key: 'total_compensation', format: 'money', highlight: true },
        { label: 'Signing Bonus', key: 'signing_bonus', format: 'money' },
        { label: 'Annual Bonus Target', key: 'annual_bonus_target', format: 'percent' },
        { label: 'Equity (Shares)', key: 'equity_shares', format: 'number' },
        { label: 'Work Arrangement', key: 'work_arrangement', format: 'text' },
        { label: 'Employment Type', key: 'employment_type', format: 'text' },
        { label: 'Start Date', key: 'start_date', format: 'date' },
        { label: 'Offer Expires', key: 'offer_expiry_date', format: 'date' },
    ];
    
    rows.forEach(row => {
        const tr = document.createElement('tr');
        if (row.highlight) {
            tr.className = 'bg-indigo-50 dark:bg-indigo-900/20';
        }
        
        tr.innerHTML = `<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white sticky left-0 ${row.highlight ? 'bg-indigo-50 dark:bg-indigo-900/20' : 'bg-white dark:bg-gray-800'}">${row.label}</td>`;
        
        // Find best value for highlighting
        let values = selectedOffers.map(o => parseFloat(o[row.key]) || 0);
        let bestValue = Math.max(...values);
        
        selectedOffers.forEach(offer => {
            let value = offer[row.key];
            let displayValue = formatValue(value, row.format);
            let isBest = row.format === 'money' || row.format === 'number' ? (parseFloat(value) === bestValue && bestValue > 0) : false;
            
            tr.innerHTML += `
                <td class="px-6 py-4 whitespace-nowrap text-sm ${isBest ? 'text-green-600 dark:text-green-400 font-semibold' : 'text-gray-500 dark:text-gray-400'}">
                    ${displayValue}
                    ${isBest ? '★' : ''}
                </td>
            `;
        });
        
        tbody.appendChild(tr);
    });
    
    // Show results
    resultsDiv.classList.remove('hidden');
    
    // Update recommendation
    updateRecommendation(selectedOffers);
}

function formatValue(value, format) {
    if (value === null || value === undefined || value === '') return '—';
    
    switch (format) {
        case 'money':
            return '$' + parseFloat(value).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        case 'percent':
            return value + '%';
        case 'number':
            return parseInt(value).toLocaleString('en-US');
        case 'date':
            return new Date(value).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        case 'text':
            return value.charAt(0).toUpperCase() + value.slice(1).replace(/-/g, ' ');
        default:
            return value;
    }
}

function updateRecommendation(selectedOffers) {
    const recText = document.getElementById('recommendation-text');
    
    // Simple recommendation logic
    let bestTotal = selectedOffers.reduce((a, b) => a.total_compensation > b.total_compensation ? a : b);
    let bestEquity = selectedOffers.reduce((a, b) => (a.equity_shares || 0) > (b.equity_shares || 0) ? a : b);
    
    let recommendation = `Based on total compensation, <strong>${bestTotal.job_title} at ${bestTotal.company?.name || 'Unknown'}</strong> offers the highest value at $${parseFloat(bestTotal.total_compensation).toLocaleString()}/year.`;
    
    if (bestEquity.equity_shares && bestEquity.id !== bestTotal.id) {
        recommendation += ` However, <strong>${bestEquity.job_title} at ${bestEquity.company?.name || 'Unknown'}</strong> offers more equity (${parseInt(bestEquity.equity_shares).toLocaleString()} shares), which could be valuable if the company grows.`;
    }
    
    recommendation += ` Consider your priorities: immediate compensation, long-term growth potential, work-life balance, and career development opportunities.`;
    
    recText.innerHTML = recommendation;
}

async function loadComparison(comparisonId) {
    try {
        const response = await fetch(`{{ url('/offer-letters/comparison') }}/${comparisonId}/report`);
        const data = await response.json();
        
        if (data.success) {
            // TODO: Render the saved comparison
            console.log(data.report);
        }
    } catch (error) {
        console.error('Error loading comparison:', error);
    }
}
</script>
@endsection
