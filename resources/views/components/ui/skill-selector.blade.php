@props([
    'wireModel' => null,
    'maxSkills' => 20,
    'showProficiency' => true,
    'showYears' => true,
    'context' => null,
])

@php
$commonSkills = [
    'technical' => [
        'JavaScript', 'TypeScript', 'Python', 'PHP', 'Java', 'C#', 'C++', 'Ruby', 'Go', 'Rust', 'Swift', 'Kotlin',
        'React', 'Vue.js', 'Angular', 'Next.js', 'Node.js', 'Laravel', 'Django', 'Spring Boot', 'ASP.NET',
        'HTML', 'CSS', 'Tailwind CSS', 'Bootstrap', 'SASS', 'SQL', 'PostgreSQL', 'MySQL', 'MongoDB', 'Redis',
        'Docker', 'Kubernetes', 'AWS', 'Azure', 'GCP', 'Linux', 'Git', 'CI/CD', 'REST API', 'GraphQL',
        'Machine Learning', 'Data Science', 'TensorFlow', 'PyTorch', 'Blockchain', 'Cybersecurity',
    ],
    'soft' => [
        'Communication', 'Leadership', 'Teamwork', 'Problem Solving', 'Critical Thinking', 'Adaptability',
        'Time Management', 'Creativity', 'Emotional Intelligence', 'Conflict Resolution', 'Decision Making',
        'Negotiation', 'Presentation Skills', 'Active Listening', 'Collaboration', 'Mentoring',
        'Strategic Planning', 'Customer Service', 'Attention to Detail', 'Stress Management',
    ],
    'tools' => [
        'VS Code', 'IntelliJ IDEA', 'Figma', 'Adobe XD', 'Photoshop', 'Illustrator', 'Sketch',
        'Jira', 'Confluence', 'Slack', 'Microsoft Office', 'Google Workspace', 'Notion', 'Trello',
        'Postman', 'Swagger', 'GitHub', 'GitLab', 'Bitbucket', 'Jenkins', 'Terraform', 'Ansible',
        'Tableau', 'Power BI', 'Excel', 'Salesforce', 'HubSpot', 'SAP', 'Oracle',
    ],
    'languages' => [
        'English', 'Spanish', 'French', 'German', 'Mandarin Chinese', 'Japanese', 'Korean', 'Portuguese',
        'Italian', 'Russian', 'Arabic', 'Hindi', 'Dutch', 'Swedish', 'Polish', 'Turkish', 'Vietnamese',
    ],
];

$proficiencyLevels = [
    'beginner' => ['label' => 'Beginner', 'dots' => 1, 'color' => 'bg-gray-400'],
    'intermediate' => ['label' => 'Intermediate', 'dots' => 2, 'color' => 'bg-blue-400'],
    'advanced' => ['label' => 'Advanced', 'dots' => 3, 'color' => 'bg-green-500'],
    'expert' => ['label' => 'Expert', 'dots' => 4, 'color' => 'bg-purple-500'],
];

$categoryLabels = [
    'technical' => ['label' => 'Technical Skills', 'icon' => 'code-bracket', 'color' => 'blue'],
    'soft' => ['label' => 'Soft Skills', 'icon' => 'users', 'color' => 'green'],
    'tools' => ['label' => 'Tools & Software', 'icon' => 'wrench-screwdriver', 'color' => 'orange'],
    'languages' => ['label' => 'Languages', 'icon' => 'language', 'color' => 'purple'],
];
@endphp

<div
    x-data="{
        skills: @entangle($wireModel) ?? [],
        searchQuery: '',
        showDropdown: false,
        activeCategory: 'all',
        isLoadingAI: false,
        aiError: null,
        maxSkills: {{ $maxSkills }},
        showProficiency: {{ $showProficiency ? 'true' : 'false' }},
        showYears: {{ $showYears ? 'true' : 'false' }},
        context: '{{ $context }}',
        editingSkill: null,
        
        commonSkills: @js($commonSkills),
        proficiencyLevels: @js($proficiencyLevels),
        categoryLabels: @js($categoryLabels),
        
        get filteredSuggestions() {
            if (!this.searchQuery || this.searchQuery.length < 2) return [];
            
            const query = this.searchQuery.toLowerCase();
            const existingNames = this.skills.map(s => s.name.toLowerCase());
            let results = [];
            
            Object.entries(this.commonSkills).forEach(([category, skills]) => {
                skills.forEach(skill => {
                    if (skill.toLowerCase().includes(query) && !existingNames.includes(skill.toLowerCase())) {
                        results.push({ name: skill, category: category });
                    }
                });
            });
            
            return results.slice(0, 10);
        },
        
        get groupedSkills() {
            const groups = { technical: [], soft: [], tools: [], languages: [] };
            this.skills.forEach(skill => {
                if (groups[skill.category]) {
                    groups[skill.category].push(skill);
                } else {
                    groups['technical'].push(skill);
                }
            });
            return groups;
        },
        
        get skillCount() {
            return this.skills.length;
        },
        
        get canAddMore() {
            return this.skills.length < this.maxSkills;
        },
        
        addSkill(name, category = 'technical') {
            if (!this.canAddMore) {
                this.showMaxLimitWarning();
                return;
            }
            
            const exists = this.skills.some(s => s.name.toLowerCase() === name.toLowerCase());
            if (exists) return;
            
            this.skills.push({
                name: name,
                proficiency: 'intermediate',
                years: 1,
                category: category
            });
            
            this.searchQuery = '';
            this.showDropdown = false;
        },
        
        addCustomSkill() {
            if (!this.searchQuery.trim()) return;
            this.addSkill(this.searchQuery.trim(), 'technical');
        },
        
        removeSkill(index) {
            this.skills.splice(index, 1);
        },
        
        updateProficiency(index, level) {
            this.skills[index].proficiency = level;
        },
        
        updateYears(index, years) {
            this.skills[index].years = parseInt(years);
        },
        
        updateCategory(index, category) {
            this.skills[index].category = category;
        },
        
        showMaxLimitWarning() {
            // Could integrate with Filament notifications
            alert('Maximum of ' + this.maxSkills + ' skills allowed');
        },
        
        async suggestSkillsWithAI() {
            if (this.isLoadingAI) return;
            
            this.isLoadingAI = true;
            this.aiError = null;
            
            try {
                const response = await fetch('/api/skills/suggest', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.content || '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        context: this.context,
                        existing_skills: this.skills.map(s => s.name),
                    }),
                });
                
                if (!response.ok) throw new Error('Failed to get suggestions');
                
                const data = await response.json();
                
                if (data.skills && Array.isArray(data.skills)) {
                    data.skills.forEach(suggestion => {
                        if (this.canAddMore) {
                            this.addSkill(suggestion.name, suggestion.category || 'technical');
                        }
                    });
                }
            } catch (error) {
                this.aiError = 'Unable to get AI suggestions. Please try again.';
                console.error('AI Suggestion Error:', error);
            } finally {
                this.isLoadingAI = false;
            }
        },
        
        getProficiencyDots(level) {
            const levels = { beginner: 1, intermediate: 2, advanced: 3, expert: 4 };
            return levels[level] || 2;
        },
        
        getProficiencyColor(level) {
            const colors = {
                beginner: 'bg-gray-400',
                intermediate: 'bg-blue-500',
                advanced: 'bg-green-500',
                expert: 'bg-purple-500'
            };
            return colors[level] || 'bg-gray-400';
        },
        
        getCategoryColor(category) {
            const colors = {
                technical: 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-700',
                soft: 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-700',
                tools: 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-900/30 dark:text-orange-300 dark:border-orange-700',
                languages: 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-700'
            };
            return colors[category] || colors['technical'];
        }
    }"
    x-init="skills = skills || []"
    class="w-full"
>
    {{-- Header with count and AI button --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Skills
            </span>
            <span 
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="skillCount >= maxSkills ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'"
            >
                <span x-text="skillCount"></span>/<span x-text="maxSkills"></span>
            </span>
        </div>
        
        <button
            type="button"
            @click="suggestSkillsWithAI()"
            :disabled="isLoadingAI || !canAddMore"
            class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-lg transition-all duration-200
                   bg-gradient-to-r from-indigo-500 to-purple-600 text-white
                   hover:from-indigo-600 hover:to-purple-700
                   disabled:opacity-50 disabled:cursor-not-allowed
                   focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
        >
            <template x-if="isLoadingAI">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </template>
            <template x-if="!isLoadingAI">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                </svg>
            </template>
            <span x-text="isLoadingAI ? 'Suggesting...' : 'Suggest Skills with AI'"></span>
        </button>
    </div>
    
    {{-- AI Error message --}}
    <template x-if="aiError">
        <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm dark:bg-red-900/20 dark:text-red-400">
            <span x-text="aiError"></span>
        </div>
    </template>
    
    {{-- Search input with autocomplete --}}
    <div class="relative mb-4">
        <div class="relative">
            <input
                type="text"
                x-model="searchQuery"
                @focus="showDropdown = true"
                @click.away="showDropdown = false"
                @keydown.enter.prevent="filteredSuggestions.length > 0 ? addSkill(filteredSuggestions[0].name, filteredSuggestions[0].category) : addCustomSkill()"
                @keydown.escape="showDropdown = false"
                :disabled="!canAddMore"
                placeholder="Search or type a skill..."
                class="w-full px-4 py-2.5 pl-10 pr-20 text-sm rounded-lg border transition-colors duration-200
                       bg-white dark:bg-gray-800
                       border-gray-300 dark:border-gray-600
                       text-gray-900 dark:text-gray-100
                       placeholder-gray-400 dark:placeholder-gray-500
                       focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/30
                       disabled:opacity-50 disabled:cursor-not-allowed"
            />
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </div>
            <button
                type="button"
                @click="addCustomSkill()"
                :disabled="!searchQuery.trim() || !canAddMore"
                class="absolute inset-y-0 right-0 flex items-center px-3 text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Add
            </button>
        </div>
        
        {{-- Autocomplete dropdown --}}
        <div
            x-show="showDropdown && filteredSuggestions.length > 0"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-1"
            class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 max-h-60 overflow-y-auto"
        >
            <template x-for="(suggestion, index) in filteredSuggestions" :key="index">
                <button
                    type="button"
                    @click="addSkill(suggestion.name, suggestion.category)"
                    class="w-full flex items-center justify-between px-4 py-2.5 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors first:rounded-t-lg last:rounded-b-lg"
                >
                    <span class="text-sm text-gray-900 dark:text-gray-100" x-text="suggestion.name"></span>
                    <span 
                        class="text-xs px-2 py-0.5 rounded-full"
                        :class="getCategoryColor(suggestion.category)"
                        x-text="categoryLabels[suggestion.category]?.label || suggestion.category"
                    ></span>
                </button>
            </template>
        </div>
    </div>
    
    {{-- Category filter tabs --}}
    <div class="flex flex-wrap gap-2 mb-4 border-b border-gray-200 dark:border-gray-700 pb-3">
        <button
            type="button"
            @click="activeCategory = 'all'"
            :class="activeCategory === 'all' ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'"
            class="px-3 py-1.5 text-xs font-medium rounded-full transition-colors"
        >
            All
        </button>
        <template x-for="(info, category) in categoryLabels" :key="category">
            <button
                type="button"
                @click="activeCategory = category"
                :class="activeCategory === category ? 'bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'"
                class="px-3 py-1.5 text-xs font-medium rounded-full transition-colors"
            >
                <span x-text="info.label"></span>
                <span class="ml-1 opacity-60" x-text="'(' + (groupedSkills[category]?.length || 0) + ')'"></span>
            </button>
        </template>
    </div>
    
    {{-- Skills grid --}}
    <div class="space-y-6">
        <template x-for="(categoryInfo, category) in categoryLabels" :key="category">
            <div 
                x-show="activeCategory === 'all' || activeCategory === category"
                x-transition
            >
                <template x-if="groupedSkills[category]?.length > 0">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <span x-text="categoryInfo.label"></span>
                            <span class="text-xs font-normal text-gray-500 dark:text-gray-400" x-text="'(' + groupedSkills[category].length + ')'"></span>
                        </h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            <template x-for="(skill, skillIndex) in skills.filter((s, i) => { skill._originalIndex = i; return s.category === category; })" :key="skill.name">
                                <div 
                                    class="group relative p-3 rounded-lg border transition-all duration-200 hover:shadow-md"
                                    :class="getCategoryColor(skill.category)"
                                >
                                    {{-- Remove button --}}
                                    <button
                                        type="button"
                                        @click="removeSkill(skill._originalIndex)"
                                        class="absolute -top-2 -right-2 w-6 h-6 flex items-center justify-center rounded-full bg-red-500 text-white opacity-0 group-hover:opacity-100 transition-opacity shadow-sm hover:bg-red-600"
                                    >
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    
                                    {{-- Skill name --}}
                                    <div class="font-medium text-sm mb-2" x-text="skill.name"></div>
                                    
                                    {{-- Proficiency indicator --}}
                                    <template x-if="showProficiency">
                                        <div class="mb-2">
                                            <div class="flex items-center gap-2 mb-1">
                                                <div class="flex gap-0.5">
                                                    <template x-for="dot in 4" :key="dot">
                                                        <div 
                                                            class="w-2 h-2 rounded-full transition-colors"
                                                            :class="dot <= getProficiencyDots(skill.proficiency) ? getProficiencyColor(skill.proficiency) : 'bg-gray-300 dark:bg-gray-600'"
                                                        ></div>
                                                    </template>
                                                </div>
                                            </div>
                                            <select
                                                @change="updateProficiency(skill._originalIndex, $event.target.value)"
                                                :value="skill.proficiency"
                                                class="w-full text-xs px-2 py-1 rounded border border-current/20 bg-transparent focus:outline-none focus:ring-1 focus:ring-current/30"
                                            >
                                                <option value="beginner">Beginner</option>
                                                <option value="intermediate">Intermediate</option>
                                                <option value="advanced">Advanced</option>
                                                <option value="expert">Expert</option>
                                            </select>
                                        </div>
                                    </template>
                                    
                                    {{-- Years slider --}}
                                    <template x-if="showYears">
                                        <div>
                                            <div class="flex items-center justify-between text-xs mb-1">
                                                <span class="opacity-70">Experience</span>
                                                <span class="font-medium" x-text="skill.years + (skill.years === 1 ? ' year' : ' years')"></span>
                                            </div>
                                            <input
                                                type="range"
                                                min="0"
                                                max="20"
                                                step="1"
                                                :value="skill.years"
                                                @input="updateYears(skill._originalIndex, $event.target.value)"
                                                class="w-full h-1.5 rounded-full appearance-none cursor-pointer
                                                       bg-current/20
                                                       [&::-webkit-slider-thumb]:appearance-none
                                                       [&::-webkit-slider-thumb]:w-3
                                                       [&::-webkit-slider-thumb]:h-3
                                                       [&::-webkit-slider-thumb]:rounded-full
                                                       [&::-webkit-slider-thumb]:bg-current
                                                       [&::-webkit-slider-thumb]:cursor-pointer
                                                       [&::-moz-range-thumb]:w-3
                                                       [&::-moz-range-thumb]:h-3
                                                       [&::-moz-range-thumb]:rounded-full
                                                       [&::-moz-range-thumb]:bg-current
                                                       [&::-moz-range-thumb]:border-0
                                                       [&::-moz-range-thumb]:cursor-pointer"
                                            />
                                        </div>
                                    </template>
                                    
                                    {{-- Category change dropdown --}}
                                    <div class="mt-2 pt-2 border-t border-current/10">
                                        <select
                                            @change="updateCategory(skill._originalIndex, $event.target.value)"
                                            :value="skill.category"
                                            class="w-full text-xs px-2 py-1 rounded border border-current/20 bg-transparent focus:outline-none focus:ring-1 focus:ring-current/30"
                                        >
                                            <template x-for="(catInfo, cat) in categoryLabels" :key="cat">
                                                <option :value="cat" x-text="catInfo.label"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>
        
        {{-- Empty state --}}
        <template x-if="skills.length === 0">
            <div class="text-center py-12 px-4">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                </svg>
                <h3 class="mt-4 text-sm font-medium text-gray-900 dark:text-gray-100">No skills added yet</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Search for skills above or use AI suggestions to get started.
                </p>
            </div>
        </template>
    </div>
    
    {{-- Quick add popular skills --}}
    <template x-if="skills.length < 5 && canAddMore">
        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Quick add popular skills:</p>
            <div class="flex flex-wrap gap-2">
                <template x-for="skill in ['JavaScript', 'Python', 'Communication', 'Project Management', 'Excel'].filter(s => !skills.some(existing => existing.name.toLowerCase() === s.toLowerCase()))" :key="skill">
                    <button
                        type="button"
                        @click="addSkill(skill, skill === 'Communication' || skill === 'Project Management' ? 'soft' : skill === 'Excel' ? 'tools' : 'technical')"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-full
                               bg-gray-100 text-gray-700 hover:bg-gray-200
                               dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600
                               transition-colors"
                    >
                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span x-text="skill"></span>
                    </button>
                </template>
            </div>
        </div>
    </template>
</div>
