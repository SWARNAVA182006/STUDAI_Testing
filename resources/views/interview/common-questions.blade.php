<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Common Interview Questions</h2>
                <p class="text-sm text-gray-500">Role focus: {{ $jobTitle }}</p>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <a href="{{ route('interview.create', ['job_title' => $jobTitle]) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold hover:bg-indigo-700 transition">
                    <i class="fas fa-play mr-2"></i> Practice mock interview
                </a>
                <a href="{{ route('interview.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-200 rounded-md font-semibold text-gray-700 hover:bg-gray-50 transition">
                    Back to dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <div class="bg-white shadow-sm rounded-xl p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-indigo-600 uppercase tracking-wide">How to use this list</p>
                        <p class="text-gray-700 mt-1">These {{ count($questions) }} questions surface the topics recruiters most often explore for {{ $jobTitle }} candidates. Work through them using the STAR method and capture bullet-point responses you can adapt live.</p>
                    </div>
                    <div class="bg-indigo-50 text-indigo-700 px-4 py-3 rounded-lg text-sm max-w-sm">
                        <p class="font-semibold mb-1">Pro Tip</p>
                        <p>Group similar questions together and craft a core story. Tailor the introduction and metrics for each variation.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @forelse($questions as $question)
                    <div class="bg-white shadow-sm rounded-xl p-5 space-y-3 border border-gray-100">
                        <div class="flex items-center gap-2 text-xs uppercase tracking-wide text-gray-500">
                            <span class="inline-flex items-center px-2 py-1 rounded-md bg-gray-100 text-gray-700">{{ ucfirst($question['type'] ?? 'general') }}</span>
                            <span class="inline-flex items-center px-2 py-1 rounded-md bg-indigo-50 text-indigo-700">{{ str_replace('_', ' ', $question['frequency'] ?? 'common') }}</span>
                        </div>
                        <p class="text-base font-semibold text-gray-900 leading-relaxed">{{ $question['question'] }}</p>
                        <p class="text-sm text-gray-600">{{ $question['tips'] ?? 'Highlight a measurable impact and connect it back to the role requirements.' }}</p>
                        <div class="bg-gray-50 rounded-lg p-3 text-xs text-gray-500 flex items-center gap-2">
                            <i class="fas fa-pen-to-square"></i>
                            <span>Create a STAR outline: Situation ➝ Task ➝ Action ➝ Result</span>
                        </div>
                    </div>
                @empty
                    <div class="md:col-span-2 bg-white shadow-sm rounded-xl p-8 text-center text-gray-500">
                        We could not load the question bank for this role. Try again in a moment or start a mock interview for fresh AI-generated prompts.
                    </div>
                @endforelse
            </div>

            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl shadow-lg p-8 text-white flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <h3 class="text-2xl font-bold">Ready to level up further?</h3>
                    <p class="text-indigo-100 mt-2">Use these questions inside a timed mock session to get instant scoring and feedback tailored to {{ $jobTitle }} interviews.</p>
                </div>
                <div class="flex flex-col gap-3 min-w-[220px]">
                    <a href="{{ route('interview.create', ['job_title' => $jobTitle]) }}" class="inline-flex items-center justify-center px-4 py-2 bg-white text-indigo-600 rounded-md font-semibold hover:bg-indigo-100 transition">
                        <i class="fas fa-microphone-lines mr-2"></i> Start timed practice
                    </a>
                    <a href="{{ route('interview.star-guide') }}" class="inline-flex items-center justify-center px-4 py-2 border border-white/40 rounded-md font-semibold hover:bg-white/10 transition">
                        <i class="fas fa-compass mr-2"></i> Review STAR method
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
