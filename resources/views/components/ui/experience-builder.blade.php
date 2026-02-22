@props([
    'experiences' => [],
    'wireModel' => 'experiences',
    'maxEntries' => 10,
])

<div
    x-data="{
        entries: @entangle($wireModel).live,
        collapsed: {},
        loading: {},
        
        init() {
            if (!this.entries || this.entries.length === 0) {
                this.entries = [this.createEntry()];
            }
            // Initialize all entries as expanded
            this.entries.forEach((_, index) => {
                this.collapsed[index] = false;
            });
        },
        
        createEntry() {
            return {
                id: Date.now(),
                title: '',
                company: '',
                start_date: '',
                end_date: '',
                current: false,
                description: '',
                achievements: []
            };
        },
        
        addEntry() {
            if (this.entries.length < {{ $maxEntries }}) {
                const newIndex = this.entries.length;
                this.entries.push(this.createEntry());
                this.collapsed[newIndex] = false;
            }
        },
        
        removeEntry(index) {
            if (this.entries.length > 1) {
                this.entries.splice(index, 1);
                // Reindex collapsed state
                const newCollapsed = {};
                this.entries.forEach((_, i) => {
                    newCollapsed[i] = this.collapsed[i < index ? i : i + 1] || false;
                });
                this.collapsed = newCollapsed;
            }
        },
        
        toggleCollapse(index) {
            this.collapsed[index] = !this.collapsed[index];
        },
        
        async generateDescription(index) {
            const entry = this.entries[index];
            if (!entry.title || !entry.company) {
                this.showNotification('Please enter a job title and company first.', 'warning');
                return;
            }
            
            this.loading[index] = 'description';
            
            try {
                const response = await fetch('/api/ai/generate-experience-description', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        title: entry.title,
                        company: entry.company,
                        current_description: entry.description
                    })
                });
                
                const data = await response.json();
                
                if (data.success && data.description) {
                    this.entries[index].description = data.description;
                    this.showNotification('Description generated successfully!', 'success');
                } else {
                    this.showNotification(data.message || 'Failed to generate description.', 'error');
                }
            } catch (error) {
                console.error('Error generating description:', error);
                this.showNotification('An error occurred. Please try again.', 'error');
            } finally {
                this.loading[index] = null;
            }
        },
        
        async suggestAchievements(index) {
            const entry = this.entries[index];
            if (!entry.title || !entry.company) {
                this.showNotification('Please enter a job title and company first.', 'warning');
                return;
            }
            
            this.loading[index] = 'achievements';
            
            try {
                const response = await fetch('/api/ai/suggest-achievements', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        title: entry.title,
                        company: entry.company,
                        description: entry.description,
                        existing_achievements: entry.achievements
                    })
                });
                
                const data = await response.json();
                
                if (data.success && data.achievements) {
                    // Merge new achievements with existing ones
                    const existingAchievements = entry.achievements || [];
                    const newAchievements = [...existingAchievements, ...data.achievements];
                    this.entries[index].achievements = newAchievements.slice(0, 8); // Max 8 achievements
                    this.showNotification('Achievements suggested successfully!', 'success');
                } else {
                    this.showNotification(data.message || 'Failed to suggest achievements.', 'error');
                }
            } catch (error) {
                console.error('Error suggesting achievements:', error);
                this.showNotification('An error occurred. Please try again.', 'error');
            } finally {
                this.loading[index] = null;
            }
        },
        
        addAchievement(index) {
            if (!this.entries[index].achievements) {
                this.entries[index].achievements = [];
            }
            if (this.entries[index].achievements.length < 8) {
                this.entries[index].achievements.push('');
            }
        },
        
        removeAchievement(entryIndex, achievementIndex) {
            this.entries[entryIndex].achievements.splice(achievementIndex, 1);
        },
        
        showNotification(message, type = 'info') {
            // Dispatch a custom event for notification handling
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { message, type }
            }));
            
            // Fallback: Use Livewire notification if available
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('notify', { message, type });
            }
        },
        
        getEntryTitle(entry, index) {
            if (entry.title && entry.company) {
                return entry.title + ' at ' + entry.company;
            }
            return 'Experience #' + (index + 1);
        }
    }"
    class="space-y-4"
>
    {{-- Experience Entries --}}
    <template x-for="(entry, index) in entries" :key="entry.id || index">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden transition-all duration-200">
            {{-- Card Header --}}
            <div 
                class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-750 border-b border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                @click="toggleCollapse(index)"
            >
                <div class="flex items-center gap-3">
                    {{-- Drag Handle (for future sorting) --}}
                    <div class="text-gray-400 dark:text-gray-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                        </svg>
                    </div>
                    
                    {{-- Entry Title --}}
                    <div>
                        <h3 class="font-medium text-gray-900 dark:text-white text-sm sm:text-base" x-text="getEntryTitle(entry, index)"></h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-show="entry.start_date">
                            <span x-text="entry.start_date"></span>
                            <span x-show="!entry.current && entry.end_date"> - <span x-text="entry.end_date"></span></span>
                            <span x-show="entry.current" class="text-green-600 dark:text-green-400"> - Present</span>
                        </p>
                    </div>
                </div>
                
                <div class="flex items-center gap-2">
                    {{-- Remove Button --}}
                    <button
                        type="button"
                        @click.stop="removeEntry(index)"
                        x-show="entries.length > 1"
                        class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                        title="Remove entry"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                    
                    {{-- Collapse Toggle --}}
                    <div class="text-gray-400 dark:text-gray-500 transition-transform duration-200" :class="{ 'rotate-180': !collapsed[index] }">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            {{-- Card Body --}}
            <div 
                x-show="!collapsed[index]"
                x-collapse
                class="p-4 sm:p-6 space-y-4"
            >
                {{-- Row 1: Title & Company --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Job Title --}}
                    <div>
                        <label :for="'title-' + index" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Job Title <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            :id="'title-' + index"
                            x-model="entry.title"
                            placeholder="e.g., Senior Software Engineer"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm"
                        >
                    </div>
                    
                    {{-- Company --}}
                    <div>
                        <label :for="'company-' + index" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Company <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            :id="'company-' + index"
                            x-model="entry.company"
                            placeholder="e.g., Google"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm"
                        >
                    </div>
                </div>
                
                {{-- Row 2: Dates --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    {{-- Start Date --}}
                    <div>
                        <label :for="'start_date-' + index" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Start Date <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="month"
                            :id="'start_date-' + index"
                            x-model="entry.start_date"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm"
                        >
                    </div>
                    
                    {{-- End Date --}}
                    <div>
                        <label :for="'end_date-' + index" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            End Date
                        </label>
                        <input
                            type="month"
                            :id="'end_date-' + index"
                            x-model="entry.end_date"
                            :disabled="entry.current"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                    </div>
                    
                    {{-- Current Position --}}
                    <div class="flex items-end pb-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                x-model="entry.current"
                                @change="if(entry.current) entry.end_date = ''"
                                class="w-4 h-4 text-primary-600 border-gray-300 dark:border-gray-600 rounded focus:ring-primary-500 dark:bg-gray-700"
                            >
                            <span class="text-sm text-gray-700 dark:text-gray-300">I currently work here</span>
                        </label>
                    </div>
                </div>
                
                {{-- Row 3: Description --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label :for="'description-' + index" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Role Description
                        </label>
                        <button
                            type="button"
                            @click="generateDescription(index)"
                            :disabled="loading[index] === 'description'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-primary-700 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 hover:bg-primary-100 dark:hover:bg-primary-900/50 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-wait"
                        >
                            <template x-if="loading[index] !== 'description'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </template>
                            <template x-if="loading[index] === 'description'">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <span x-text="loading[index] === 'description' ? 'Generating...' : 'AI Generate'"></span>
                        </button>
                    </div>
                    <textarea
                        :id="'description-' + index"
                        x-model="entry.description"
                        rows="4"
                        placeholder="Describe your responsibilities and impact in this role..."
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm resize-none"
                    ></textarea>
                </div>
                
                {{-- Row 4: Achievements --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Key Achievements
                        </label>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="suggestAchievements(index)"
                                :disabled="loading[index] === 'achievements'"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-purple-700 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30 hover:bg-purple-100 dark:hover:bg-purple-900/50 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-wait"
                            >
                                <template x-if="loading[index] !== 'achievements'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                    </svg>
                                </template>
                                <template x-if="loading[index] === 'achievements'">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </template>
                                <span x-text="loading[index] === 'achievements' ? 'Suggesting...' : 'AI Suggest'"></span>
                            </button>
                            <button
                                type="button"
                                @click="addAchievement(index)"
                                :disabled="entry.achievements && entry.achievements.length >= 8"
                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors disabled:opacity-50"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Add
                            </button>
                        </div>
                    </div>
                    
                    {{-- Achievement List --}}
                    <div class="space-y-2">
                        <template x-for="(achievement, achIndex) in entry.achievements || []" :key="achIndex">
                            <div class="flex items-start gap-2 group">
                                <div class="flex-shrink-0 mt-2.5">
                                    <div class="w-2 h-2 bg-primary-500 rounded-full"></div>
                                </div>
                                <input
                                    type="text"
                                    x-model="entry.achievements[achIndex]"
                                    placeholder="e.g., Increased revenue by 25% through..."
                                    class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm"
                                >
                                <button
                                    type="button"
                                    @click="removeAchievement(index, achIndex)"
                                    class="flex-shrink-0 p-2 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity"
                                    title="Remove achievement"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </template>
                        
                        {{-- Empty State --}}
                        <div 
                            x-show="!entry.achievements || entry.achievements.length === 0"
                            class="text-center py-4 text-sm text-gray-500 dark:text-gray-400 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg"
                        >
                            <p>No achievements added yet.</p>
                            <p class="text-xs mt-1">Click "AI Suggest" to get AI-powered suggestions or "Add" to add manually.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
    
    {{-- Add New Entry Button --}}
    <div class="flex justify-center">
        <button
            type="button"
            @click="addEntry()"
            :disabled="entries.length >= {{ $maxEntries }}"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-primary-700 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 hover:bg-primary-100 dark:hover:bg-primary-900/50 border-2 border-dashed border-primary-300 dark:border-primary-700 rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add Another Experience
        </button>
    </div>
    
    {{-- Entry Count --}}
    <p class="text-center text-xs text-gray-500 dark:text-gray-400" x-show="entries.length > 0">
        <span x-text="entries.length"></span> of {{ $maxEntries }} experiences added
    </p>
</div>
