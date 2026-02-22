<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">STAR Method Playbook</h2>
                <p class="text-sm text-gray-500">Structure compelling stories that land</p>
            </div>
            <a href="{{ route('interview.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold hover:bg-indigo-700 transition">
                <i class="fas fa-play mr-2"></i> Apply this in a mock interview
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <div class="bg-white shadow-sm rounded-xl overflow-hidden">
                <div class="p-6 bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
                    <h3 class="text-2xl font-bold">Situation · Task · Action · Result</h3>
                    <p class="mt-2 text-indigo-100">Use this framework to transform vague answers into persuasive stories with measurable impact. Interviewers remember clarity, structure, and outcomes.</p>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                    @php
                        $blocks = [
                            [
                                'title' => 'Situation',
                                'icon' => 'fa-map-pin',
                                'color' => 'bg-blue-100 text-blue-800',
                                'body' => 'Set the scene in one or two sentences. Provide company, team size, timelines, and why the moment mattered.'
                            ],
                            [
                                'title' => 'Task',
                                'icon' => 'fa-bullseye',
                                'color' => 'bg-amber-100 text-amber-800',
                                'body' => 'Clarify your specific responsibility or goal. What outcome were you on the hook for? Mention constraints if relevant.'
                            ],
                            [
                                'title' => 'Action',
                                'icon' => 'fa-person-running',
                                'color' => 'bg-purple-100 text-purple-800',
                                'body' => 'Describe what you actually did. Highlight collaboration, tools, decision-making, and creativity. Keep the focus on your contributions.'
                            ],
                            [
                                'title' => 'Result',
                                'icon' => 'fa-trophy',
                                'color' => 'bg-emerald-100 text-emerald-800',
                                'body' => 'Share measurable impact: KPIs, revenue, satisfaction, speed. End with what you learned or how the team benefited.'
                            ],
                        ];
                    @endphp

                    @foreach($blocks as $block)
                        <div class="border border-gray-100 rounded-lg p-5">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full {{ $block['color'] }}">
                                    <i class="fas {{ $block['icon'] }}"></i>
                                </span>
                                <h4 class="text-lg font-semibold text-gray-900">{{ $block['title'] }}</h4>
                            </div>
                            <p class="mt-3 text-sm text-gray-600 leading-relaxed">{{ $block['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-xl p-6 space-y-6">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-indigo-100 text-indigo-700">
                        <i class="fas fa-wand-magic-sparkles"></i>
                    </span>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Instant STAR Formatter</h3>
                        <p class="text-sm text-gray-600">Paste a rough answer below and let our AI clean it up with STAR structure.</p>
                    </div>
                </div>
                <form x-data="{
                        answer: '',
                        loading: false,
                        result: null,
                        async submit() {
                            if (!this.answer.trim()) {
                                alert('Add your draft answer first.');
                                return;
                            }
                            this.loading = true;
                            this.result = null;
                            try {
                                const response = await fetch('{{ route('interview.format-star') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({ answer: this.answer })
                                });
                                if (!response.ok) {
                                    throw new Error('Request failed');
                                }
                                this.result = await response.json();
                            } catch (error) {
                                console.error(error);
                                alert('We could not format that answer right now. Try again in a moment.');
                            } finally {
                                this.loading = false;
                            }
                        }
                    }" class="space-y-5" @submit.prevent="submit">
                    <textarea x-model="answer" rows="6" class="w-full rounded-md border border-gray-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="Paste your unstructured answer here..."></textarea>
                    <div class="flex flex-col md:flex-row gap-3">
                        <button type="submit" class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold hover:bg-indigo-700 transition disabled:opacity-70" :disabled="loading">
                            <i class="fas fa-magic mr-2"></i>
                            <span x-text="loading ? 'Formatting…' : 'Format with STAR'"></span>
                        </button>
                        <button type="button" class="inline-flex items-center justify-center px-4 py-2 border border-gray-200 rounded-md font-semibold text-gray-700 hover:bg-gray-50 transition" @click="answer = ''; result = null;">
                            <i class="fas fa-rotate-left mr-2"></i> Reset
                        </button>
                    </div>
                    <template x-if="result">
                        <div class="border border-emerald-200 bg-emerald-50 rounded-lg p-5 space-y-4">
                            <h4 class="text-sm font-semibold text-emerald-800 uppercase tracking-wide">AI-Formatted Response</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-emerald-900">
                                <div>
                                    <p class="font-semibold">Situation</p>
                                    <p x-text="result.situation || '—'" class="mt-1"></p>
                                </div>
                                <div>
                                    <p class="font-semibold">Task</p>
                                    <p x-text="result.task || '—'" class="mt-1"></p>
                                </div>
                                <div>
                                    <p class="font-semibold">Action</p>
                                    <p x-text="result.action || '—'" class="mt-1"></p>
                                </div>
                                <div>
                                    <p class="font-semibold">Result</p>
                                    <p x-text="result.result || '—'" class="mt-1"></p>
                                </div>
                            </div>
                            <div class="bg-white rounded-md p-4 shadow-sm text-sm text-gray-700">
                                <p class="font-semibold text-gray-900">Polished STAR Answer</p>
                                <p class="mt-2" x-text="result.formatted_answer || '—'"></p>
                            </div>
                        </div>
                    </template>
                </form>
            </div>

            <div class="bg-white shadow-sm rounded-xl p-6 space-y-5">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-purple-100 text-purple-700">
                        <i class="fas fa-graduation-cap"></i>
                    </span>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Mastery Checklist</h3>
                        <p class="text-sm text-gray-600">Tick these off to ensure every story resonates.</p>
                    </div>
                </div>
                <ul class="space-y-3 text-sm text-gray-700">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check-circle text-emerald-500 mt-1"></i>
                        Each story highlights a clear challenge, your role, and measurable impact.
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check-circle text-emerald-500 mt-1"></i>
                        You mention numbers, tools, or stakeholders to prove credibility.
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check-circle text-emerald-500 mt-1"></i>
                        You can deliver each story in 90 seconds and extend to 3 minutes if probed.
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check-circle text-emerald-500 mt-1"></i>
                        You tailor the takeaway to match the company's values or role requirements.
                    </li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
