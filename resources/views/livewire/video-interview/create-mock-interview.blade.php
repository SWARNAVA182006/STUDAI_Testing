<div class="max-w-3xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <!-- Header -->
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Start Practice Interview</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Practice your interview skills with AI-powered feedback
            </p>
        </div>

        <!-- Form -->
        <form wire:submit="createMockInterview" class="px-6 py-5 space-y-6">
            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Session Title (Optional)
                </label>
                <input type="text" 
                       wire:model="title" 
                       id="title"
                       placeholder="e.g., Software Engineer Interview Practice"
                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('title') 
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Role Type -->
            <div>
                <label for="roleType" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Interview Type
                </label>
                <select wire:model="roleType" 
                        id="roleType"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select interview type</option>
                    @foreach($roleTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('roleType') 
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Tips -->
            <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-indigo-800 dark:text-indigo-200">What to expect</h3>
                        <div class="mt-2 text-sm text-indigo-700 dark:text-indigo-300">
                            <ul class="list-disc list-inside space-y-1">
                                <li>5-7 common interview questions</li>
                                <li>30 seconds preparation time per question</li>
                                <li>Up to 3 minutes to record each answer</li>
                                <li>AI-powered feedback on your responses</li>
                                <li>Analysis of speech patterns & body language</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('video-interview.sessions') }}" 
                   class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                    ← Back to sessions
                </a>
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50">
                    <span wire:loading.remove>Start Practice</span>
                    <span wire:loading>Creating...</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Recent Practice Sessions -->
    <div class="mt-6">
        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Tips for Success</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="h-8 w-8 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                            <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Good Lighting</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Position yourself facing a light source</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="h-8 w-8 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                            <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Quiet Environment</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Find a quiet space free from distractions</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="h-8 w-8 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                            <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Look at the Camera</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Maintain eye contact by looking at the camera</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="h-8 w-8 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                            <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Speak Clearly</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Pace yourself and avoid filler words</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
