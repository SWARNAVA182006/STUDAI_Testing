@extends('layouts.app')

@section('title', $company->name . ' Salaries | StudAI Career')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    {{-- Company Header --}}
    @include('companies.partials.header', ['company' => $company, 'activeTab' => 'salaries'])

    {{-- Main Content --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Salaries List --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Salary Overview --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                        Salary Overview at {{ $company->name }}
                    </h2>
                    
                    @php
                        $salaryStats = $company->salaryReports()
                            ->where('status', 'approved')
                            ->selectRaw('
                                COUNT(*) as total_reports,
                                AVG(base_salary) as avg_base,
                                MIN(base_salary) as min_base,
                                MAX(base_salary) as max_base,
                                AVG(total_compensation) as avg_total
                            ')
                            ->first();
                    @endphp
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                {{ $salaryStats->total_reports ?? 0 }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Salaries Reported</p>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                                ${{ number_format($salaryStats->avg_base ?? 0) }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Avg Base Salary</p>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                ${{ number_format($salaryStats->avg_total ?? 0) }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Avg Total Comp</p>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-2xl font-bold text-gray-700 dark:text-gray-300">
                                ${{ number_format($salaryStats->min_base ?? 0) }} - ${{ number_format($salaryStats->max_base ?? 0) }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Salary Range</p>
                        </div>
                    </div>
                </div>

                {{-- Salary Filters --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-48">
                            <input type="text" 
                                   placeholder="Search job titles..." 
                                   class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500">
                        </div>
                        <select class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="">All Locations</option>
                            @foreach($company->salaryReports()->where('status', 'approved')->distinct('location')->pluck('location') as $location)
                                <option value="{{ $location }}">{{ $location }}</option>
                            @endforeach
                        </select>
                        <select class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="">All Experience Levels</option>
                            <option value="entry">Entry Level (0-2 years)</option>
                            <option value="mid">Mid Level (3-5 years)</option>
                            <option value="senior">Senior (6-10 years)</option>
                            <option value="executive">Executive (10+ years)</option>
                        </select>
                    </div>
                </div>

                {{-- Salaries List --}}
                <div class="space-y-4">
                    @forelse($salaryReports as $salary)
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $salary->job_title }}
                                    </h3>
                                    <div class="flex flex-wrap items-center gap-2 mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        @if($salary->department)
                                            <span class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                </svg>
                                                {{ $salary->department }}
                                            </span>
                                            <span class="text-gray-300 dark:text-gray-600">•</span>
                                        @endif
                                        @if($salary->location)
                                            <span class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                                {{ $salary->location }}
                                            </span>
                                            <span class="text-gray-300 dark:text-gray-600">•</span>
                                        @endif
                                        <span>{{ $salary->years_of_experience }} years experience</span>
                                        <span class="text-gray-300 dark:text-gray-600">•</span>
                                        <span>{{ ucfirst($salary->employment_type) }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                                        ${{ number_format($salary->base_salary) }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        /{{ $salary->pay_period ?? 'year' }}
                                    </p>
                                </div>
                            </div>

                            {{-- Compensation Breakdown --}}
                            @if($salary->bonus || $salary->stock_options || $salary->signing_bonus)
                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Additional Compensation</p>
                                    <div class="flex flex-wrap gap-4">
                                        @if($salary->bonus)
                                            <div class="flex items-center gap-2 text-sm">
                                                <span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded">
                                                    Bonus: ${{ number_format($salary->bonus) }}
                                                </span>
                                            </div>
                                        @endif
                                        @if($salary->stock_options)
                                            <div class="flex items-center gap-2 text-sm">
                                                <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded">
                                                    Stock: ${{ number_format($salary->stock_options) }}
                                                </span>
                                            </div>
                                        @endif
                                        @if($salary->signing_bonus)
                                            <div class="flex items-center gap-2 text-sm">
                                                <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded">
                                                    Signing: ${{ number_format($salary->signing_bonus) }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                    @if($salary->total_compensation)
                                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                            <span class="font-medium">Total Compensation:</span> 
                                            <span class="text-green-600 dark:text-green-400 font-semibold">${{ number_format($salary->total_compensation) }}</span>/year
                                        </p>
                                    @endif
                                </div>
                            @endif

                            {{-- Benefits & Meta --}}
                            <div class="mt-4 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                                <span>Reported {{ $salary->created_at->diffForHumans() }}</span>
                                @if($salary->is_current_employee)
                                    <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded text-xs">
                                        Current Employee
                                    </span>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded text-xs">
                                        Former Employee
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Salary Data Yet</h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                Be the first to share your salary at {{ $company->name }}!
                            </p>
                            <a href="{{ route('companies.salaries.create', $company) }}" 
                               class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors">
                                Share Your Salary
                            </a>
                        </div>
                    @endforelse
                </div>

                {{-- Pagination --}}
                @if($salaryReports->hasPages())
                    <div class="mt-6">
                        {{ $salaryReports->links() }}
                    </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Share Salary CTA --}}
                <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
                    <h3 class="text-lg font-bold mb-2">Share Your Salary</h3>
                    <p class="text-green-100 text-sm mb-4">
                        Help others negotiate better offers. Your submission is completely anonymous.
                    </p>
                    <a href="{{ route('companies.salaries.create', $company) }}" 
                       class="block w-full text-center px-4 py-2 bg-white text-green-600 font-medium rounded-lg hover:bg-green-50 transition-colors">
                        Add Salary
                    </a>
                </div>

                {{-- Salary by Job Title --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Popular Job Titles</h3>
                    <div class="space-y-3">
                        @php
                            $popularTitles = $company->salaryReports()
                                ->where('status', 'approved')
                                ->selectRaw('job_title, COUNT(*) as count, AVG(base_salary) as avg_salary')
                                ->groupBy('job_title')
                                ->orderByDesc('count')
                                ->limit(8)
                                ->get();
                        @endphp
                        @forelse($popularTitles as $title)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $title->job_title }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $title->count }} {{ Str::plural('salary', $title->count) }}</p>
                                </div>
                                <span class="text-sm font-semibold text-green-600 dark:text-green-400">
                                    ${{ number_format($title->avg_salary) }}
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No salary data available yet.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Salary by Location --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Salary by Location</h3>
                    <div class="space-y-3">
                        @php
                            $locationSalaries = $company->salaryReports()
                                ->where('status', 'approved')
                                ->whereNotNull('location')
                                ->selectRaw('location, COUNT(*) as count, AVG(base_salary) as avg_salary')
                                ->groupBy('location')
                                ->orderByDesc('count')
                                ->limit(6)
                                ->get();
                        @endphp
                        @forelse($locationSalaries as $loc)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    </svg>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $loc->location }}</span>
                                </div>
                                <span class="text-sm font-semibold text-green-600 dark:text-green-400">
                                    ${{ number_format($loc->avg_salary) }}
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No location data available.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
