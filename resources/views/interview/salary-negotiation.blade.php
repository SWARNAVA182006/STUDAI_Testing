<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Salary Negotiation Strategist</h2>
                <p class="text-sm text-gray-500">Craft persuasive compensation conversations</p>
            </div>
            <a href="{{ route('negotiation.dashboard') }}" class="inline-flex items-center px-4 py-2 border border-gray-200 rounded-md font-semibold text-gray-700 hover:bg-gray-50 transition">
                View negotiation playbooks
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8" x-data="salaryNegotiation()" x-init="prefill()">
            <div class="bg-white shadow-sm rounded-xl p-6 space-y-6">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-indigo-600 text-white">
                        <i class="fas fa-handshake"></i>
                    </span>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Tell us about the offer</h3>
                        <p class="text-sm text-gray-600">Well tailor a negotiation script based on your goals, experience, and differentiators.</p>
                    </div>
                </div>

                <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="generateGuide">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Target Job Title <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.job_title" class="w-full rounded-md border border-gray-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="e.g., Senior Product Manager" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current / Last Salary (₹) <span class="text-red-500">*</span></label>
                        <input type="number" min="0" x-model="form.current_salary" class="w-full rounded-md border border-gray-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="e.g., 1800000" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Target Salary (₹) <span class="text-red-500">*</span></label>
                        <input type="number" min="0" x-model="form.target_salary" class="w-full rounded-md border border-gray-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="e.g., 2400000" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Years of Experience</label>
                        <input type="number" min="0" x-model="form.years_experience" class="w-full rounded-md border border-gray-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="e.g., 7">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unique Skills or Certifications</label>
                        <input type="text" x-model="skillInput" @keydown.enter.prevent="addSkill" class="w-full rounded-md border border-gray-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="Press Enter to add each skill">
                        <div class="flex flex-wrap gap-2 mt-2">
                            <template x-for="(skill, idx) in form.unique_skills" :key="skill">
                                <span class="inline-flex items-center px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 text-xs font-semibold">
                                    <span x-text="skill"></span>
                                    <button type="button" class="ml-2 text-indigo-500 hover:text-indigo-700" @click="removeSkill(idx)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </span>
                            </template>
                        </div>
                    </div>
                    <div class="md:col-span-2 flex flex-col md:flex-row gap-3">
                        <button type="submit" class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white rounded-md font-semibold hover:bg-indigo-700 transition disabled:opacity-70" :disabled="loading">
                            <i class="fas fa-microphone-lines mr-2"></i>
                            <span x-text="loading ? 'Crafting strategy…' : 'Generate negotiation game plan'"></span>
                        </button>
                        <button type="button" class="inline-flex items-center justify-center px-4 py-2 border border-gray-200 rounded-md font-semibold text-gray-700 hover:bg-gray-50 transition" @click="resetForm">
                            <i class="fas fa-rotate-left mr-2"></i> Reset
                        </button>
                    </div>
                </form>
            </div>

            <template x-if="guide">
                <div class="bg-white shadow-sm rounded-xl p-6 space-y-6">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-emerald-500 text-white">
                            <i class="fas fa-bullhorn"></i>
                        </span>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Your personalized negotiation plan</h3>
                            <p class="text-sm text-gray-600">Anchor your conversation with the opening script, then use the talking points and objections to stay confident.</p>
                        </div>
                    </div>

                    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-5">
                        <h4 class="text-sm font-semibold text-emerald-900 uppercase tracking-wide mb-2">Opening statement</h4>
                        <p class="text-sm text-emerald-800 leading-relaxed" x-text="guide.opening_statement || 'Well surface your opening when data is ready.'"></p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-3">
                            <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Justification points</h4>
                            <template x-if="guide.justification_points?.length">
                                <ul class="space-y-2 text-sm text-gray-700">
                                    <template x-for="point in guide.justification_points" :key="point">
                                        <li class="flex items-start gap-2">
                                            <i class="fas fa-circle-check text-emerald-500 mt-1"></i>
                                            <span x-text="point"></span>
                                        </li>
                                    </template>
                                </ul>
                            </template>
                            <template x-if="!(guide.justification_points?.length)">
                                <p class="text-sm text-gray-500">Add more details about your achievements to unlock targeted value statements.</p>
                            </template>
                        </div>
                        <div class="space-y-3">
                            <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Negotiation tactics</h4>
                            <template x-if="guide.negotiation_tactics?.length">
                                <ul class="space-y-2 text-sm text-gray-700">
                                    <template x-for="tactic in guide.negotiation_tactics" :key="tactic">
                                        <li class="flex items-start gap-2">
                                            <i class="fas fa-lightbulb text-indigo-500 mt-1"></i>
                                            <span x-text="tactic"></span>
                                        </li>
                                    </template>
                                </ul>
                            </template>
                            <template x-if="!(guide.negotiation_tactics?.length)">
                                <p class="text-sm text-gray-500">Well suggest tactical moves once the AI has more context about the offer.</p>
                            </template>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Handling objections</h4>
                        <template x-if="guide.counter_responses?.length">
                            <div class="space-y-3">
                                <template x-for="item in guide.counter_responses" :key="item.objection">
                                    <div class="border border-gray-100 rounded-lg p-4">
                                        <p class="text-xs uppercase tracking-wide text-gray-400">Objection</p>
                                        <p class="text-sm font-semibold text-gray-900" x-text="item.objection"></p>
                                        <p class="mt-2 text-sm text-gray-700" x-text="item.response"></p>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!(guide.counter_responses?.length)">
                            <p class="text-sm text-gray-500">Share potential pushbacks you expect so we can craft confident responses.</p>
                        </template>
                    </div>

                    <div class="space-y-4">
                        <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Alternative benefits</h4>
                        <template x-if="guide.alternative_benefits?.length">
                            <div class="flex flex-wrap gap-2">
                                <template x-for="benefit in guide.alternative_benefits" :key="benefit">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-purple-100 text-purple-700 text-xs font-semibold" x-text="benefit"></span>
                                </template>
                            </div>
                        </template>
                        <template x-if="!(guide.alternative_benefits?.length)">
                            <p class="text-sm text-gray-500">Think about flexible benefits (ESOPs, remote days, learning) you value. Well weave them into the script.</p>
                        </template>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-5">
                        <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Sample script</h4>
                        <p class="text-sm text-gray-700 leading-relaxed" x-text="guide.sample_script || 'Once you generate a negotiation, your full script will appear here.'"></p>
                    </div>
                </div>
            </template>

            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl shadow-lg p-8 text-white flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <h3 class="text-2xl font-bold">Boost your leverage before the conversation</h3>
                    <p class="text-indigo-100 mt-2">Review market intelligence, rehearse mock negotiations, and align with your BATNA so you walk in with conviction.</p>
                </div>
                <div class="flex flex-col gap-3 min-w-[220px]">
                    <a href="{{ route('market.salary-intelligence') }}" class="inline-flex items-center justify-center px-4 py-2 bg-white text-indigo-600 rounded-md font-semibold hover:bg-indigo-100 transition">
                        <i class="fas fa-chart-line mr-2"></i> Market salary intel
                    </a>
                    <a href="{{ route('negotiation.dashboard') }}" class="inline-flex items-center justify-center px-4 py-2 border border-white/40 rounded-md font-semibold hover:bg-white/10 transition">
                        <i class="fas fa-chess-knight mr-2"></i> Negotiation strategies
                    </a>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function salaryNegotiation() {
            return {
                form: {
                    job_title: '',
                    current_salary: '',
                    target_salary: '',
                    years_experience: '',
                    unique_skills: [],
                },
                guide: null,
                loading: false,
                skillInput: '',
                prefill() {
                    this.form.job_title = @json(optional($user->careerProfile)->target_title ?? '') || '';
                    this.form.years_experience = @json(optional($user->careerProfile)->years_of_experience ?? '') || '';
                },
                addSkill() {
                    if (!this.skillInput.trim()) return;
                    this.form.unique_skills.push(this.skillInput.trim());
                    this.skillInput = '';
                },
                removeSkill(index) {
                    this.form.unique_skills.splice(index, 1);
                },
                resetForm() {
                    this.form = { job_title: '', current_salary: '', target_salary: '', years_experience: '', unique_skills: [] };
                    this.skillInput = '';
                    this.guide = null;
                },
                async generateGuide() {
                    this.loading = true;
                    this.guide = null;
                    try {
                        const response = await fetch('{{ route('interview.get-negotiation-guide') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(this.form)
                        });
                        if (!response.ok) {
                            throw new Error('Unable to build negotiation guide');
                        }
                        this.guide = await response.json();
                    } catch (error) {
                        console.error(error);
                        alert('We could not build a negotiation plan right now. Double-check your inputs and try again.');
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
