@props([
    'wireModel' => 'education',
    'maxEntries' => 5,
])

@php
    $degreeOptions = [
        'high_school' => 'High School Diploma',
        'ged' => 'GED',
        'associate' => "Associate's Degree",
        'bachelor' => "Bachelor's Degree",
        'master' => "Master's Degree",
        'mba' => 'MBA',
        'phd' => 'PhD / Doctorate',
        'md' => 'Medical Degree (MD)',
        'jd' => 'Juris Doctor (JD)',
        'certification' => 'Professional Certification',
        'bootcamp' => 'Bootcamp / Training Program',
        'diploma' => 'Diploma / Certificate',
        'other' => 'Other',
    ];

    $currentYear = (int) date('Y');
    $years = range($currentYear + 5, $currentYear - 60);

    $commonInstitutions = [
        'Harvard University',
        'Stanford University',
        'MIT',
        'Yale University',
        'Princeton University',
        'Columbia University',
        'University of Chicago',
        'Duke University',
        'Northwestern University',
        'California Institute of Technology',
        'University of Pennsylvania',
        'Johns Hopkins University',
        'University of California, Berkeley',
        'UCLA',
        'University of Michigan',
        'New York University',
        'Carnegie Mellon University',
        'University of Texas at Austin',
        'University of Washington',
        'Georgia Institute of Technology',
    ];
@endphp

<div
    x-data="{
        entries: @entangle($wireModel).live || [],
        maxEntries: {{ $maxEntries }},
        expandedCards: {},
        institutionSuggestions: @js($commonInstitutions),
        showSuggestions: {},
        filteredSuggestions: {},
        
        init() {
            if (!this.entries || this.entries.length === 0) {
                this.entries = [];
            }
            // Expand all cards by default on desktop
            this.entries.forEach((_, index) => {
                this.expandedCards[index] = window.innerWidth >= 768;
            });
        },
        
        addEntry() {
            if (this.entries.length < this.maxEntries) {
                this.entries.push({
                    degree: '',
                    institution: '',
                    field: '',
                    graduation_year: {{ $currentYear }},
                    gpa: null
                });
                const newIndex = this.entries.length - 1;
                this.expandedCards[newIndex] = true;
            }
        },
        
        removeEntry(index) {
            this.entries.splice(index, 1);
            // Reindex expanded cards
            const newExpanded = {};
            Object.keys(this.expandedCards).forEach(key => {
                const numKey = parseInt(key);
                if (numKey < index) {
                    newExpanded[numKey] = this.expandedCards[numKey];
                } else if (numKey > index) {
                    newExpanded[numKey - 1] = this.expandedCards[numKey];
                }
            });
            this.expandedCards = newExpanded;
        },
        
        toggleCard(index) {
            this.expandedCards[index] = !this.expandedCards[index];
        },
        
        filterInstitutions(query, index) {
            if (query.length < 2) {
                this.filteredSuggestions[index] = [];
                this.showSuggestions[index] = false;
                return;
            }
            const lowerQuery = query.toLowerCase();
            this.filteredSuggestions[index] = this.institutionSuggestions
                .filter(inst => inst.toLowerCase().includes(lowerQuery))
                .slice(0, 5);
            this.showSuggestions[index] = this.filteredSuggestions[index].length > 0;
        },
        
        selectInstitution(index, institution) {
            this.entries[index].institution = institution;
            this.showSuggestions[index] = false;
        },
        
        hideSuggestions(index) {
            setTimeout(() => {
                this.showSuggestions[index] = false;
            }, 200);
        },
        
        getCardTitle(entry, index) {
            if (entry.degree && entry.institution) {
                const degreeLabel = this.getDegreeLabel(entry.degree);
                return `${degreeLabel} - ${entry.institution}`;
            }
            return `Education #${index + 1}`;
        },
        
        getDegreeLabel(value) {
            const options = @js($degreeOptions);
            return options[value] || value || 'Not Selected';
        },
        
        isValid(entry) {
            return entry.degree && entry.institution && entry.field && entry.graduation_year;
        },
        
        getValidationClass(field, value) {
            if (!value || value === '') return '';
            return 'border-green-300 focus:border-green-500 focus:ring-green-500';
        }
    }"
    {{ $attributes->merge(['class' => 'space-y-4']) }}
>
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Education</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Add your educational background</p>
        </div>
        <button
            type="button"
            x-on:click="addEntry()"
            x-show="entries.length < maxEntries"
            class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-gray-800"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span class="hidden sm:inline">Add Education</span>
            <span class="sm:hidden">Add</span>
        </button>
    </div>

    {{-- Entry limit indicator --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <span x-text="`${entries.length} of ${maxEntries} entries`"></span>
        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
            <div
                class="h-full rounded-full bg-primary-600 transition-all duration-300"
                :style="`width: ${(entries.length / maxEntries) * 100}%`"
            ></div>
        </div>
    </div>

    {{-- Empty state --}}
    <div
        x-show="entries.length === 0"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-8 text-center dark:border-gray-600 dark:bg-gray-800/50"
    >
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l9-5-9-5-9 5 9 5z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
        </svg>
        <h4 class="mt-4 text-base font-medium text-gray-900 dark:text-white">No education added yet</h4>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Click the button above to add your first education entry.</p>
        <button
            type="button"
            x-on:click="addEntry()"
            class="mt-4 inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Education
        </button>
    </div>

    {{-- Education entries --}}
    <div class="space-y-4">
        <template x-for="(entry, index) in entries" :key="index">
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all duration-200 dark:border-gray-700 dark:bg-gray-800"
                :class="{ 'ring-2 ring-primary-500': expandedCards[index] }"
            >
                {{-- Card header (collapsible trigger) --}}
                <div
                    class="flex cursor-pointer items-center justify-between gap-4 bg-gray-50 px-4 py-3 transition-colors hover:bg-gray-100 dark:bg-gray-700/50 dark:hover:bg-gray-700 md:cursor-default md:hover:bg-gray-50 md:dark:hover:bg-gray-700/50"
                    x-on:click="if (window.innerWidth < 768) toggleCard(index)"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        {{-- Status indicator --}}
                        <div
                            class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full"
                            :class="isValid(entry) ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-400 dark:bg-gray-600 dark:text-gray-500'"
                        >
                            <template x-if="isValid(entry)">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                            <template x-if="!isValid(entry)">
                                <span class="text-sm font-medium" x-text="index + 1"></span>
                            </template>
                        </div>
                        <div class="min-w-0">
                            <h4 class="truncate text-sm font-medium text-gray-900 dark:text-white" x-text="getCardTitle(entry, index)"></h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-show="entry.field" x-text="entry.field"></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        {{-- Year badge --}}
                        <span
                            x-show="entry.graduation_year"
                            class="hidden rounded-full bg-primary-100 px-2.5 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900/30 dark:text-primary-300 sm:inline-block"
                            x-text="entry.graduation_year"
                        ></span>
                        {{-- Remove button --}}
                        <button
                            type="button"
                            x-on:click.stop="removeEntry(index)"
                            class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-red-100 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 dark:hover:bg-red-900/30 dark:hover:text-red-400"
                            title="Remove entry"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                        {{-- Expand/collapse indicator (mobile only) --}}
                        <div class="md:hidden">
                            <svg
                                class="h-5 w-5 text-gray-400 transition-transform duration-200"
                                :class="{ 'rotate-180': expandedCards[index] }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Card body (form fields) --}}
                <div
                    x-show="expandedCards[index] || window.innerWidth >= 768"
                    x-collapse
                    class="border-t border-gray-200 p-4 dark:border-gray-700"
                >
                    <div class="grid gap-4 sm:grid-cols-2">
                        {{-- Degree --}}
                        <div class="sm:col-span-1">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Degree <span class="text-red-500">*</span>
                            </label>
                            <select
                                x-model="entry.degree"
                                class="block w-full rounded-lg border-gray-300 shadow-sm transition-colors focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                :class="entry.degree ? 'border-green-300 dark:border-green-600' : ''"
                            >
                                <option value="">Select degree...</option>
                                @foreach ($degreeOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Graduation Year --}}
                        <div class="sm:col-span-1">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Graduation Year <span class="text-red-500">*</span>
                            </label>
                            <select
                                x-model="entry.graduation_year"
                                class="block w-full rounded-lg border-gray-300 shadow-sm transition-colors focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                :class="entry.graduation_year ? 'border-green-300 dark:border-green-600' : ''"
                            >
                                <option value="">Select year...</option>
                                @foreach ($years as $year)
                                    <option value="{{ $year }}">{{ $year }}{{ $year > $currentYear ? ' (Expected)' : '' }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Institution --}}
                        <div class="relative sm:col-span-2">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Institution <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                x-model="entry.institution"
                                x-on:input="filterInstitutions($event.target.value, index)"
                                x-on:focus="filterInstitutions(entry.institution || '', index)"
                                x-on:blur="hideSuggestions(index)"
                                placeholder="e.g., Harvard University"
                                class="block w-full rounded-lg border-gray-300 shadow-sm transition-colors focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 sm:text-sm"
                                :class="entry.institution ? 'border-green-300 dark:border-green-600' : ''"
                                autocomplete="off"
                            />
                            {{-- Institution suggestions dropdown --}}
                            <div
                                x-show="showSuggestions[index]"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute z-10 mt-1 max-h-48 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-700"
                            >
                                <template x-for="(suggestion, sIndex) in filteredSuggestions[index]" :key="sIndex">
                                    <button
                                        type="button"
                                        x-on:mousedown.prevent="selectInstitution(index, suggestion)"
                                        class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-700 dark:text-gray-200 dark:hover:bg-primary-900/30 dark:hover:text-primary-300"
                                        x-text="suggestion"
                                    ></button>
                                </template>
                            </div>
                        </div>

                        {{-- Field of Study --}}
                        <div class="sm:col-span-1">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Field of Study <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                x-model="entry.field"
                                placeholder="e.g., Computer Science"
                                class="block w-full rounded-lg border-gray-300 shadow-sm transition-colors focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 sm:text-sm"
                                :class="entry.field ? 'border-green-300 dark:border-green-600' : ''"
                            />
                        </div>

                        {{-- GPA --}}
                        <div class="sm:col-span-1">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                GPA <span class="text-gray-400 dark:text-gray-500">(Optional)</span>
                            </label>
                            <div class="relative">
                                <input
                                    type="number"
                                    x-model="entry.gpa"
                                    placeholder="e.g., 3.8"
                                    min="0"
                                    max="4"
                                    step="0.01"
                                    class="block w-full rounded-lg border-gray-300 pr-12 shadow-sm transition-colors focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 sm:text-sm"
                                    :class="entry.gpa ? 'border-green-300 dark:border-green-600' : ''"
                                />
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span class="text-sm text-gray-400 dark:text-gray-500">/ 4.0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Validation summary --}}
                    <div
                        x-show="!isValid(entry)"
                        class="mt-4 flex items-start gap-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-700 dark:bg-amber-900/20 dark:text-amber-400"
                    >
                        <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Please fill in all required fields (Degree, Institution, Field of Study, Year)</span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Max entries reached message --}}
    <div
        x-show="entries.length >= maxEntries"
        x-transition
        class="rounded-lg bg-blue-50 p-3 text-center text-sm text-blue-700 dark:bg-blue-900/20 dark:text-blue-400"
    >
        <span>You've reached the maximum of <strong x-text="maxEntries"></strong> education entries.</span>
    </div>
</div>
