<div class="bg-gray-50 rounded-lg p-3 cursor-move hover:shadow-md transition-shadow" data-application-id="{{ $application->id }}">
    <div class="flex items-center mb-2">
        <div class="h-8 w-8 bg-gradient-to-br from-pink-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-sm mr-2">
            {{ substr($application->user->name, 0, 1) }}
        </div>
        <div class="flex-1">
            <h4 class="font-semibold text-sm text-gray-900">{{ $application->user->name }}</h4>
        </div>
    </div>
    <p class="text-xs text-gray-600 mb-2">{{ Str::limit($application->job->title, 40) }}</p>
    <div class="flex items-center justify-between text-xs text-gray-500">
        <span>{{ $application->created_at->diffForHumans() }}</span>
        <a href="{{ route('employer.applicants.show', $application->id) }}" class="text-pink-600 hover:text-pink-800 font-semibold">
            View →
        </a>
    </div>
</div>
