<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Interview Tips</h2>
                <p class="text-sm text-gray-500">
                    @if($job)
                        Focused on {{ $job->title }} @if($company)&middot; {{ $company->name }} @endif
                    @else
                        General guidance for your next conversation
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <a href="{{ route('interview.common-questions', ['job_title' => $job->title ?? '']) }}" class="inline-flex items-center px-4 py-2 border border-gray-200 rounded-md font-semibold text-gray-700 hover:bg-gray-50 transition">
                    <i class="fas fa-layer-group mr-2"></i> Common questions
                </a>
                <a href="{{ route('interview.create', ['job_id' => $job->id ?? null]) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold hover:bg-indigo-700 transition">
                    <i class="fas fa-play mr-2"></i> Start mock interview
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <div class="bg-white shadow-sm rounded-xl p-6">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-indigo-600 uppercase tracking-wide">Read before you interview</p>
                        <p class="text-gray-700 mt-1">We analyzed the role context and surfaced the tips recruiters emphasize most often. Pair this guidance with your STAR stories for maximum impact.</p>
                    </div>
                    <div class="bg-indigo-50 text-indigo-700 px-4 py-3 rounded-lg text-sm max-w-sm">
                        <p class="font-semibold mb-1">Quick prep</p>
                        <p>Practice your opener, refine your closing question, and review the companys latest announcement.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                @forelse($tips as $tip)
                    <div class="bg-white shadow-sm rounded-xl p-5 border border-gray-100">
                        <div class="flex items-center gap-3 text-xs uppercase tracking-wide text-gray-500 mb-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-md bg-gray-100 text-gray-700">{{ ucfirst($tip['category'] ?? 'general') }}</span>
                        </div>
                        <p class="text-base font-semibold text-gray-900 leading-relaxed">{{ $tip['tip'] }}</p>
                    </div>
                @empty
                    <div class="bg-white shadow-sm rounded-xl p-8 text-center text-gray-500">
                        Were gathering tailored tips for this role. Check back in a bit or run a mock interview for fresh coaching feedback.
                    </div>
                @endforelse
            </div>

            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl shadow-lg p-8 text-white flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <h3 class="text-2xl font-bold">Make these tips actionable</h3>
                    <p class="text-indigo-100 mt-2">Turn each tip into a practice question, record yourself, and compare your delivery to the checklist.</p>
                </div>
                <div class="flex flex-col gap-3 min-w-[220px]">
                    <a href="{{ route('interview.create', ['job_id' => $job->id ?? null]) }}" class="inline-flex items-center justify-center px-4 py-2 bg-white text-indigo-600 rounded-md font-semibold hover:bg-indigo-100 transition">
                        <i class="fas fa-microphone mr-2"></i> Practice these tips now
                    </a>
                    <a href="{{ route('interview.star-guide') }}" class="inline-flex items-center justify-center px-4 py-2 border border-white/40 rounded-md font-semibold hover:bg-white/10 transition">
                        <i class="fas fa-compass mr-2"></i> Revisit STAR playbook
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
