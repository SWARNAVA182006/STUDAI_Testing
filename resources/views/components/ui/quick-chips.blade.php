{{-- 
    Quick Chips Selector - For fast multi-selection without dropdowns
    Usage: <x-ui.quick-chips :options="$skills" wire:model="selectedSkills" />
--}}

@props([
    'name' => '',
    'label' => '',
    'options' => [],
    'selected' => [],
    'maxItems' => null,
    'columns' => 3,
    'size' => 'md', // sm, md, lg
    'showCount' => true,
    'searchable' => false,
    'categorized' => false,
    'error' => null,
])

@php
$sizeClasses = [
    'sm' => 'px-2.5 py-1 text-xs',
    'md' => 'px-3 py-1.5 text-sm',
    'lg' => 'px-4 py-2 text-base',
];
$gridCols = [
    2 => 'grid-cols-2',
    3 => 'grid-cols-2 sm:grid-cols-3',
    4 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4',
    5 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-5',
];
@endphp

<div x-data="{
    selected: @entangle($attributes->wire('model')) || {{ json_encode($selected) }},
    options: {{ json_encode($options) }},
    search: '',
    maxItems: {{ $maxItems ?? 'null' }},
    
    get filteredOptions() {
        if (!this.search) return this.options;
        const searchLower = this.search.toLowerCase();
        return this.options.filter(opt => {
            const label = typeof opt === 'object' ? opt.label : opt;
            return label.toLowerCase().includes(searchLower);
        });
    },
    
    toggle(value) {
        if (!this.selected) this.selected = [];
        const index = this.selected.indexOf(value);
        if (index > -1) {
            this.selected.splice(index, 1);
        } else {
            if (!this.maxItems || this.selected.length < this.maxItems) {
                this.selected.push(value);
            }
        }
    },
    
    isSelected(value) {
        return this.selected && this.selected.includes(value);
    },
    
    get reachedMax() {
        return this.maxItems && this.selected && this.selected.length >= this.maxItems;
    }
}" class="space-y-3">
    
    {{-- Label & Count --}}
    @if($label)
    <div class="flex items-center justify-between">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
        </label>
        @if($showCount)
        <span class="text-sm text-gray-500">
            <span x-text="selected?.length || 0"></span>
            @if($maxItems) / {{ $maxItems }} @endif
            selected
        </span>
        @endif
    </div>
    @endif
    
    {{-- Search --}}
    @if($searchable)
    <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="text"
               x-model="search"
               placeholder="Search options..."
               class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:text-gray-100">
    </div>
    @endif
    
    {{-- Chips Grid --}}
    <div class="grid {{ $gridCols[$columns] ?? 'grid-cols-3' }} gap-2">
        <template x-for="(option, index) in filteredOptions" :key="index">
            <button type="button"
                    @click="toggle(typeof option === 'object' ? option.value : option)"
                    :disabled="reachedMax && !isSelected(typeof option === 'object' ? option.value : option)"
                    :class="{
                        'bg-primary-100 dark:bg-primary-900/30 border-primary-500 text-primary-700 dark:text-primary-300 ring-2 ring-primary-500/20': isSelected(typeof option === 'object' ? option.value : option),
                        'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/10': !isSelected(typeof option === 'object' ? option.value : option),
                        'opacity-50 cursor-not-allowed': reachedMax && !isSelected(typeof option === 'object' ? option.value : option)
                    }"
                    class="{{ $sizeClasses[$size] }} rounded-full border font-medium transition-all flex items-center justify-center gap-1.5 text-center">
                {{-- Selected Checkmark --}}
                <template x-if="isSelected(typeof option === 'object' ? option.value : option)">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </template>
                <span x-text="typeof option === 'object' ? option.label : option" class="truncate"></span>
            </button>
        </template>
    </div>
    
    {{-- Hidden Inputs --}}
    <template x-for="val in selected" :key="val">
        <input type="hidden" :name="'{{ $name }}[]'" :value="val">
    </template>
    
    {{-- Max Items Warning --}}
    <template x-if="reachedMax">
        <p class="text-sm text-yellow-600 dark:text-yellow-400">
            ✓ Maximum {{ $maxItems }} items selected
        </p>
    </template>
    
    {{-- Error --}}
    @if($error)
    <p class="text-sm text-red-600">{{ $error }}</p>
    @endif
</div>
