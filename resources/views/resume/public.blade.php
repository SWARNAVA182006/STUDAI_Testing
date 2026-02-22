<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $resume->full_name }} - Resume</title>
    <meta name="description" content="Professional resume of {{ $resume->full_name }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b shadow-sm">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Public Resume</p>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $resume->full_name }}</h1>
                </div>
                <a href="{{ config('app.url') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition text-sm font-medium">
                    Create Your Resume
                </a>
            </div>
        </div>
    </div>

    <!-- Resume Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="p-8 md:p-12">
                <!-- Header Section -->
                <div class="border-b-4 border-indigo-600 pb-6 mb-8">
                    <h2 class="text-4xl font-bold text-gray-900 mb-4">{{ $resume->full_name }}</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                        <div class="space-y-2">
                            @if($resume->email)
                            <p><i class="fas fa-envelope w-5 mr-2 text-indigo-600"></i> {{ $resume->email }}</p>
                            @endif
                            @if($resume->phone)
                            <p><i class="fas fa-phone w-5 mr-2 text-indigo-600"></i> {{ $resume->phone }}</p>
                            @endif
                            @if($resume->location)
                            <p><i class="fas fa-map-marker-alt w-5 mr-2 text-indigo-600"></i> {{ $resume->location }}</p>
                            @endif
                        </div>
                        <div class="space-y-2">
                            @if($resume->linkedin_url)
                            <p><i class="fab fa-linkedin w-5 mr-2 text-indigo-600"></i> 
                                <a href="{{ $resume->linkedin_url }}" target="_blank" class="text-indigo-600 hover:underline">LinkedIn Profile</a>
                            </p>
                            @endif
                            @if($resume->github_url)
                            <p><i class="fab fa-github w-5 mr-2 text-indigo-600"></i> 
                                <a href="{{ $resume->github_url }}" target="_blank" class="text-indigo-600 hover:underline">GitHub Profile</a>
                            </p>
                            @endif
                            @if($resume->portfolio_url)
                            <p><i class="fas fa-globe w-5 mr-2 text-indigo-600"></i> 
                                <a href="{{ $resume->portfolio_url }}" target="_blank" class="text-indigo-600 hover:underline">Portfolio</a>
                            </p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Professional Summary -->
                @if($resume->professional_summary)
                <section class="mb-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <div class="w-1.5 h-6 bg-indigo-600 mr-3 rounded"></div>
                        Professional Summary
                    </h3>
                    <p class="text-gray-700 leading-relaxed text-justify">{{ $resume->professional_summary }}</p>
                </section>
                @endif

                <!-- Work Experience -->
                @if($resume->experiences && count($resume->experiences) > 0)
                <section class="mb-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <div class="w-1.5 h-6 bg-indigo-600 mr-3 rounded"></div>
                        Work Experience
                    </h3>
                    <div class="space-y-6">
                        @foreach($resume->experiences as $exp)
                        <div class="relative pl-8 border-l-2 border-gray-200">
                            <div class="absolute left-0 top-1 w-4 h-4 bg-indigo-600 rounded-full -translate-x-[9px]"></div>
                            <div class="flex flex-col md:flex-row md:justify-between md:items-start mb-2">
                                <div>
                                    <h4 class="text-xl font-bold text-gray-900">{{ $exp['position'] }}</h4>
                                    <p class="text-lg text-indigo-600 font-semibold">{{ $exp['company'] }}</p>
                                    @if($exp['location'] ?? false)
                                    <p class="text-sm text-gray-500 mt-1">
                                        <i class="fas fa-map-marker-alt mr-1"></i> {{ $exp['location'] }}
                                    </p>
                                    @endif
                                </div>
                                <span class="inline-block mt-2 md:mt-0 px-3 py-1 bg-gray-100 text-gray-700 rounded text-sm font-medium whitespace-nowrap">
                                    {{ $exp['start_date'] }} - {{ $exp['end_date'] ?? 'Present' }}
                                </span>
                            </div>
                            @if($exp['description'] ?? false)
                            <p class="text-gray-700 leading-relaxed mt-3">{{ $exp['description'] }}</p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </section>
                @endif

                <!-- Education -->
                @if($resume->education && count($resume->education) > 0)
                <section class="mb-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <div class="w-1.5 h-6 bg-indigo-600 mr-3 rounded"></div>
                        Education
                    </h3>
                    <div class="space-y-4">
                        @foreach($resume->education as $edu)
                        <div class="border-l-2 border-gray-200 pl-6">
                            <div class="flex flex-col md:flex-row md:justify-between md:items-start">
                                <div>
                                    <h4 class="text-xl font-bold text-gray-900">{{ $edu['degree'] }}</h4>
                                    <p class="text-lg text-indigo-600 font-semibold">{{ $edu['institution'] }}</p>
                                    @if($edu['field'] ?? false)
                                    <p class="text-sm text-gray-600 mt-1">{{ $edu['field'] }}</p>
                                    @endif
                                </div>
                                @if(($edu['start_year'] ?? false) || ($edu['end_year'] ?? false))
                                <span class="inline-block mt-2 md:mt-0 px-3 py-1 bg-gray-100 text-gray-700 rounded text-sm font-medium">
                                    {{ $edu['start_year'] ?? '' }}{{ ($edu['start_year'] ?? false) && ($edu['end_year'] ?? false) ? ' - ' : '' }}{{ $edu['end_year'] ?? '' }}
                                </span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </section>
                @endif

                <!-- Skills -->
                @if($resume->skills)
                <section class="mb-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <div class="w-1.5 h-6 bg-indigo-600 mr-3 rounded"></div>
                        Skills
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach(is_array($resume->skills) ? $resume->skills : explode(',', $resume->skills) as $skill)
                        <span class="px-4 py-2 bg-indigo-50 border border-indigo-200 text-indigo-800 rounded-lg text-sm font-medium hover:bg-indigo-100 transition">
                            {{ trim($skill) }}
                        </span>
                        @endforeach
                    </div>
                </section>
                @endif
            </div>
        </div>

        <!-- CTA Section -->
        <div class="mt-12 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg shadow-lg p-8 text-center text-white">
            <h3 class="text-2xl font-bold mb-2">Create Your Professional Resume</h3>
            <p class="mb-6 text-indigo-100">Build a stunning resume with AI-powered tools in minutes</p>
            <a href="{{ route('register') }}" 
               class="inline-flex items-center px-6 py-3 bg-white text-indigo-600 rounded-lg font-semibold hover:bg-gray-100 transition">
                Get Started Free <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-16 py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-gray-400 text-sm">
                Powered by <a href="{{ config('app.url') }}" class="text-indigo-400 hover:text-indigo-300">{{ config('app.name') }}</a> • 
                Professional AI-Powered Resume Builder
            </p>
            <p class="text-gray-500 text-xs mt-2">
                Generated on {{ now()->format('F d, Y') }}
            </p>
        </div>
    </footer>
</body>
</html>
