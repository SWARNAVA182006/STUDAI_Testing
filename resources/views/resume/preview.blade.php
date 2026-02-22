<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $resume->full_name }} - Resume</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Action Bar -->
    <div class="no-print bg-white border-b sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-lg font-semibold">Resume Preview</h1>
                <div class="flex gap-2">
                    <button onclick="window.print()" 
                            class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition">
                        <i class="fas fa-print mr-2"></i> Print
                    </button>
                    <a href="{{ route('resume.export.pdf', $resume) }}" 
                       class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                        <i class="fas fa-file-pdf mr-2"></i> Download PDF
                    </a>
                    <a href="{{ route('resume.edit', $resume) }}" 
                       class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
                        <i class="fas fa-edit mr-2"></i> Edit
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Resume Content -->
    <div class="max-w-4xl mx-auto my-8 bg-white shadow-lg">
        <!-- Modern Template -->
        @if($resume->template->slug === 'modern')
        <div class="p-12">
            <!-- Header -->
            <div class="border-b-4 border-indigo-600 pb-6 mb-6">
                <h1 class="text-4xl font-bold text-gray-900 mb-2">{{ $resume->full_name }}</h1>
                <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                    @if($resume->email)
                    <span><i class="fas fa-envelope mr-1"></i> {{ $resume->email }}</span>
                    @endif
                    @if($resume->phone)
                    <span><i class="fas fa-phone mr-1"></i> {{ $resume->phone }}</span>
                    @endif
                    @if($resume->location)
                    <span><i class="fas fa-map-marker-alt mr-1"></i> {{ $resume->location }}</span>
                    @endif
                    @if($resume->linkedin_url)
                    <span><i class="fab fa-linkedin mr-1"></i> LinkedIn</span>
                    @endif
                    @if($resume->github_url)
                    <span><i class="fab fa-github mr-1"></i> GitHub</span>
                    @endif
                </div>
            </div>

            <!-- Professional Summary -->
            @if($resume->professional_summary)
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-3 flex items-center">
                    <div class="w-2 h-6 bg-indigo-600 mr-3"></div>
                    Professional Summary
                </h2>
                <p class="text-gray-700 leading-relaxed">{{ $resume->professional_summary }}</p>
            </div>
            @endif

            <!-- Work Experience -->
            @if($resume->experiences && count($resume->experiences) > 0)
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <div class="w-2 h-6 bg-indigo-600 mr-3"></div>
                    Work Experience
                </h2>
                <div class="space-y-4">
                    @foreach($resume->experiences as $exp)
                    <div>
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">{{ $exp['position'] }}</h3>
                                <p class="text-indigo-600 font-medium">{{ $exp['company'] }}</p>
                            </div>
                            <span class="text-sm text-gray-500">{{ $exp['start_date'] }} - {{ $exp['end_date'] ?? 'Present' }}</span>
                        </div>
                        @if($exp['location'] ?? false)
                        <p class="text-sm text-gray-600 mb-2"><i class="fas fa-map-marker-alt mr-1"></i> {{ $exp['location'] }}</p>
                        @endif
                        @if($exp['description'] ?? false)
                        <p class="text-gray-700 leading-relaxed">{{ $exp['description'] }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Education -->
            @if($resume->education && count($resume->education) > 0)
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <div class="w-2 h-6 bg-indigo-600 mr-3"></div>
                    Education
                </h2>
                <div class="space-y-3">
                    @foreach($resume->education as $edu)
                    <div>
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">{{ $edu['degree'] }}</h3>
                                <p class="text-indigo-600 font-medium">{{ $edu['institution'] }}</p>
                                @if($edu['field'] ?? false)
                                <p class="text-sm text-gray-600">{{ $edu['field'] }}</p>
                                @endif
                            </div>
                            @if(($edu['start_year'] ?? false) || ($edu['end_year'] ?? false))
                            <span class="text-sm text-gray-500">
                                {{ $edu['start_year'] ?? '' }}{{ ($edu['start_year'] ?? false) && ($edu['end_year'] ?? false) ? ' - ' : '' }}{{ $edu['end_year'] ?? '' }}
                            </span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Skills -->
            @if($resume->skills)
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                    <div class="w-2 h-6 bg-indigo-600 mr-3"></div>
                    Skills
                </h2>
                <div class="flex flex-wrap gap-2">
                    @foreach(is_array($resume->skills) ? $resume->skills : explode(',', $resume->skills) as $skill)
                    <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm font-medium">
                        {{ trim($skill) }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        @else
        <!-- Classic Template (Default) -->
        <div class="p-12">
            <!-- Header -->
            <div class="text-center border-b-2 border-gray-300 pb-6 mb-6">
                <h1 class="text-4xl font-bold text-gray-900 mb-2">{{ $resume->full_name }}</h1>
                <div class="text-sm text-gray-600 space-x-3">
                    @if($resume->email)
                    <span>{{ $resume->email }}</span>
                    @endif
                    @if($resume->phone)
                    <span>•</span>
                    <span>{{ $resume->phone }}</span>
                    @endif
                    @if($resume->location)
                    <span>•</span>
                    <span>{{ $resume->location }}</span>
                    @endif
                </div>
                @if($resume->linkedin_url || $resume->github_url || $resume->portfolio_url)
                <div class="mt-2 text-sm text-gray-600 space-x-3">
                    @if($resume->linkedin_url)
                    <span>LinkedIn: {{ $resume->linkedin_url }}</span>
                    @endif
                    @if($resume->github_url)
                    <span>GitHub: {{ $resume->github_url }}</span>
                    @endif
                </div>
                @endif
            </div>

            <!-- Professional Summary -->
            @if($resume->professional_summary)
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-2 uppercase tracking-wide">Professional Summary</h2>
                <p class="text-gray-700 leading-relaxed">{{ $resume->professional_summary }}</p>
            </div>
            @endif

            <!-- Work Experience -->
            @if($resume->experiences && count($resume->experiences) > 0)
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-3 uppercase tracking-wide">Work Experience</h2>
                <div class="space-y-4">
                    @foreach($resume->experiences as $exp)
                    <div>
                        <div class="flex justify-between mb-1">
                            <h3 class="text-lg font-bold text-gray-900">{{ $exp['position'] }}</h3>
                            <span class="text-sm text-gray-600">{{ $exp['start_date'] }} - {{ $exp['end_date'] ?? 'Present' }}</span>
                        </div>
                        <p class="text-gray-700 font-medium mb-1">{{ $exp['company'] }}@if($exp['location'] ?? false), {{ $exp['location'] }}@endif</p>
                        @if($exp['description'] ?? false)
                        <p class="text-gray-700 leading-relaxed">{{ $exp['description'] }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Education -->
            @if($resume->education && count($resume->education) > 0)
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-3 uppercase tracking-wide">Education</h2>
                <div class="space-y-3">
                    @foreach($resume->education as $edu)
                    <div>
                        <div class="flex justify-between">
                            <h3 class="text-lg font-bold text-gray-900">{{ $edu['degree'] }}</h3>
                            @if(($edu['start_year'] ?? false) || ($edu['end_year'] ?? false))
                            <span class="text-sm text-gray-600">
                                {{ $edu['start_year'] ?? '' }}{{ ($edu['start_year'] ?? false) && ($edu['end_year'] ?? false) ? ' - ' : '' }}{{ $edu['end_year'] ?? '' }}
                            </span>
                            @endif
                        </div>
                        <p class="text-gray-700">{{ $edu['institution'] }}</p>
                        @if($edu['field'] ?? false)
                        <p class="text-sm text-gray-600">{{ $edu['field'] }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Skills -->
            @if($resume->skills)
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-3 uppercase tracking-wide">Skills</h2>
                <p class="text-gray-700">
                    {{ is_array($resume->skills) ? implode(', ', $resume->skills) : $resume->skills }}
                </p>
            </div>
            @endif
        </div>
        @endif
    </div>

    <!-- Footer -->
    <div class="no-print text-center py-8 text-gray-500 text-sm">
        Generated by {{ config('app.name') }} • {{ now()->format('F d, Y') }}
    </div>
</body>
</html>
