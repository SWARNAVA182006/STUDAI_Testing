<div class="max-w-7xl mx-auto" x-data="{ showReportModal: false, reportReviewId: null }">
    {{-- Rating Summary Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            {{-- Overall Rating --}}
            <div class="flex items-center gap-6">
                <div class="text-center">
                    <div class="text-5xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($this->ratingSummary['overall_rating'] ?? 0, 1) }}
                    </div>
                    <div class="flex items-center justify-center mt-1">
                        @for ($i = 1; $i <= 5; $i++)
                            <svg class="w-5 h-5 {{ $i <= round($this->ratingSummary['overall_rating'] ?? 0) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ number_format($this->ratingSummary['review_count'] ?? 0) }} reviews
                    </div>
                </div>

                {{-- Rating Breakdown --}}
                <div class="flex-1 max-w-xs">
                    @foreach ($this->ratingSummary['ratings_breakdown'] ?? [] as $stars => $data)
                        <div class="flex items-center gap-2 text-sm">
                            <span class="w-3 text-gray-600 dark:text-gray-400">{{ $stars }}</span>
                            <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-yellow-400 rounded-full transition-all duration-300" style="width: {{ $data['percentage'] }}%"></div>
                            </div>
                            <span class="w-8 text-gray-500 dark:text-gray-400 text-right">{{ $data['percentage'] }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Category Ratings --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                @foreach (['culture' => 'Culture', 'compensation' => 'Compensation', 'work_life_balance' => 'Work-Life', 'career_growth' => 'Growth', 'management' => 'Management'] as $key => $label)
                    @php $rating = $this->ratingSummary['category_ratings'][$key] ?? null; @endphp
                    <div class="text-center">
                        <div class="text-lg font-semibold {{ $rating ? ($rating >= 4 ? 'text-green-600' : ($rating >= 3 ? 'text-yellow-600' : 'text-red-600')) : 'text-gray-400' }}">
                            {{ $rating ? number_format($rating, 1) : 'N/A' }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Quick Stats --}}
            <div class="flex flex-wrap gap-4">
                @if ($this->ratingSummary['recommend_rate'])
                    <div class="bg-green-50 dark:bg-green-900/30 rounded-xl px-4 py-3 text-center">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $this->ratingSummary['recommend_rate'] }}%</div>
                        <div class="text-xs text-green-700 dark:text-green-300">Recommend</div>
                    </div>
                @endif
                @if ($this->ratingSummary['ceo_approval'])
                    <div class="bg-blue-50 dark:bg-blue-900/30 rounded-xl px-4 py-3 text-center">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $this->ratingSummary['ceo_approval'] }}%</div>
                        <div class="text-xs text-blue-700 dark:text-blue-300">CEO Approval</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Actions Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        {{-- Filters --}}
        <div class="flex flex-wrap items-center gap-2">
            {{-- Sort Dropdown --}}
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
                    </svg>
                    {{ match($sort) { 'helpful' => 'Most Helpful', 'rating_high' => 'Highest Rated', 'rating_low' => 'Lowest Rated', default => 'Most Recent' } }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false" x-transition class="absolute z-10 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1">
                    @foreach (['recent' => 'Most Recent', 'helpful' => 'Most Helpful', 'rating_high' => 'Highest Rated', 'rating_low' => 'Lowest Rated'] as $value => $label)
                        <button wire:click="setSort('{{ $value }}')" @click="open = false" class="w-full px-4 py-2 text-left text-sm {{ $sort === $value ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Rating Filter --}}
            <div class="flex items-center gap-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-1">
                @for ($i = 5; $i >= 1; $i--)
                    <button wire:click="setRating({{ $i }})" class="p-2 rounded-md transition {{ $rating === $i ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400' }}">
                        <span class="sr-only">{{ $i }} stars</span>
                        <span class="flex items-center gap-0.5">
                            <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span class="text-xs font-medium">{{ $i }}</span>
                        </span>
                    </button>
                @endfor
            </div>

            {{-- Current Employee Toggle --}}
            <button wire:click="toggleCurrentOnly" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition {{ $currentOnly ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300 border border-primary-200 dark:border-primary-800' : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                Current Employees
            </button>

            {{-- Clear Filters --}}
            @if ($rating || $employmentStatus || $currentOnly || $department)
                <button wire:click="clearFilters" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 underline">
                    Clear filters
                </button>
            @endif
        </div>

        {{-- Write Review Button --}}
        <a href="{{ route('companies.reviews.create', $company) }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-xl shadow-sm transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Write a Review
        </a>
    </div>

    {{-- Reviews List --}}
    <div class="space-y-4">
        @forelse ($this->reviews as $review)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition" wire:key="review-{{ $review->id }}">
                {{-- Review Header --}}
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-4">
                    <div>
                        {{-- Title & Rating --}}
                        <div class="flex items-center gap-3 mb-2">
                            <div class="flex items-center">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg class="w-5 h-5 {{ $i <= $review->overall_rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                            @if ($review->review_title)
                                <h3 class="font-semibold text-lg text-gray-900 dark:text-white">{{ $review->review_title }}</h3>
                            @endif
                        </div>

                        {{-- Meta Info --}}
                        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $review->display_author }}</span>
                            <span>•</span>
                            <span>{{ $review->job_title }}</span>
                            @if ($review->is_current_employee)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                    Current Employee
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    Former Employee
                                </span>
                            @endif
                            @if ($review->is_verified)
                                <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Verified
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $review->created_at->diffForHumans() }}
                    </div>
                </div>

                {{-- Category Ratings (Compact) --}}
                @if ($review->culture_rating || $review->compensation_rating || $review->worklife_rating)
                    <div class="flex flex-wrap gap-4 mb-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        @foreach (['culture_rating' => 'Culture', 'compensation_rating' => 'Pay', 'worklife_rating' => 'Work-Life', 'growth_rating' => 'Growth', 'management_rating' => 'Mgmt'] as $field => $label)
                            @if ($review->$field)
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}:</span>
                                    <span class="font-semibold {{ $review->$field >= 4 ? 'text-green-600' : ($review->$field >= 3 ? 'text-yellow-600' : 'text-red-600') }}">{{ $review->$field }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                {{-- Pros & Cons --}}
                <div class="grid md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <div class="flex items-center gap-2 text-green-600 dark:text-green-400 font-medium mb-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Pros
                        </div>
                        <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed">{{ $review->pros }}</p>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 text-red-600 dark:text-red-400 font-medium mb-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            Cons
                        </div>
                        <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed">{{ $review->cons }}</p>
                    </div>
                </div>

                {{-- Advice to Management --}}
                @if ($review->advice_to_management)
                    <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800">
                        <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300 font-medium mb-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Advice to Management
                        </div>
                        <p class="text-blue-800 dark:text-blue-200 text-sm">{{ $review->advice_to_management }}</p>
                    </div>
                @endif

                {{-- Quick Badges --}}
                <div class="flex flex-wrap gap-2 mb-4">
                    @if ($review->recommend_to_friend)
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z"/>
                            </svg>
                            Recommends
                        </span>
                    @endif
                    @if ($review->ceo_approval)
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                            Approves of CEO
                        </span>
                    @endif
                    @if ($review->business_outlook === 'positive')
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                            📈 Positive Outlook
                        </span>
                    @endif
                </div>

                {{-- Actions Footer --}}
                <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-4">
                        <button wire:click="markHelpful({{ $review->id }})" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                            </svg>
                            Helpful ({{ $review->helpful_count }})
                        </button>
                        <button wire:click="markNotHelpful({{ $review->id }})" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 transition">
                            <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                            </svg>
                            {{ $review->not_helpful_count }}
                        </button>
                    </div>
                    <button @click="showReportModal = true; reportReviewId = {{ $review->id }}" class="text-sm text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition">
                        Report
                    </button>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No reviews yet</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Be the first to share your experience at {{ $company->name }}</p>
                <a href="{{ route('companies.reviews.create', $company) }}" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-xl transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Write a Review
                </a>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($this->reviews->hasPages())
        <div class="mt-6">
            {{ $this->reviews->links() }}
        </div>
    @endif

    {{-- Report Modal --}}
    <div x-show="showReportModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showReportModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showReportModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="showReportModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Report Review</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Please select a reason for reporting this review.</p>
                <div class="space-y-2">
                    @foreach (['spam' => 'Spam or fake review', 'inappropriate' => 'Inappropriate content', 'irrelevant' => 'Not relevant to company', 'other' => 'Other'] as $value => $label)
                        <button class="w-full text-left px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition text-sm text-gray-700 dark:text-gray-300">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button @click="showReportModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>
