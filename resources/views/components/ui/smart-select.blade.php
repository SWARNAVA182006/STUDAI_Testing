{{-- 
    Smart Select Component with Search & Dynamic Options
    Usage: <x-ui.smart-select :options="$skills" label="Select Skills" multiple />
--}}

@props([
    'name' => '',
    'label' => '',
    'placeholder' => 'Select an option...',
    'searchPlaceholder' => 'Type to search...',
    'options' => [],
    'selected' => null,
    'multiple' => false,
    'required' => false,
    'searchable' => true,
    'creatable' => false,
    'maxItems' => null,
    'grouped' => false,
    'apiEndpoint' => null,
    'error' => null,
    'helpText' => null,
])

@php
$selectedValue = $selected ?? ($multiple ? [] : '');
@endphp

<div x-data="{
    open: false,
    search: '',
    options: {{ json_encode($options) }},
    selected: @entangle($attributes->wire('model')) || {{ json_encode($selectedValue) }},
    multiple: {{ $multiple ? 'true' : 'false' }},
    maxItems: {{ $maxItems ?? 'null' }},
    loading: false,
    apiEndpoint: '{{ $apiEndpoint }}',
    
    get filteredOptions() {
        if (!this.search) return this.options;
        const searchLower = this.search.toLowerCase();
        return this.options.filter(opt => {
            const label = typeof opt === 'object' ? opt.label : opt;
            return label.toLowerCase().includes(searchLower);
        });
    },
    
    get displayValue() {
        if (this.multiple) {
            if (!this.selected || this.selected.length === 0) return '';
            return this.selected.length + ' selected';
        }
        const opt = this.options.find(o => (typeof o === 'object' ? o.value : o) === this.selected);
        return opt ? (typeof opt === 'object' ? opt.label : opt) : '';
    },
    
    get selectedItems() {
        if (!this.multiple || !this.selected) return [];
        return this.selected.map(val => {
            const opt = this.options.find(o => (typeof o === 'object' ? o.value : o) === val);
            return opt ? (typeof opt === 'object' ? opt.label : opt) : val;
        });
    },
    
    toggle(value) {
        if (this.multiple) {
            if (!this.selected) this.selected = [];
            const index = this.selected.indexOf(value);
            if (index > -1) {
                this.selected.splice(index, 1);
            } else {
                if (!this.maxItems || this.selected.length < this.maxItems) {
                    this.selected.push(value);
                }
            }
        } else {
            this.selected = value;
            this.open = false;
        }
        this.search = '';
    },
    
    isSelected(value) {
        if (this.multiple) {
            return this.selected && this.selected.includes(value);
        }
        return this.selected === value;
    },
    
    removeItem(value) {
        if (this.multiple && this.selected) {
            this.selected = this.selected.filter(v => v !== value);
        }
    },
    
    createOption() {
        if (!this.search) return;
        const newVal = this.search.trim();
        this.options.push({ value: newVal, label: newVal });
        this.toggle(newVal);
    },
    
    async fetchOptions() {
        if (!this.apiEndpoint) return;
        this.loading = true;
        try {
            const response = await fetch(this.apiEndpoint + '?q=' + encodeURIComponent(this.search));
            const data = await response.json();
            this.options = data.options || data;
        } catch (e) {
            console.error('Failed to fetch options:', e);
        } finally {
            this.loading = false;
        }
    }
}" 
x-init="if (apiEndpoint) $watch('search', () => fetchOptions())"
@click.away="open = false"
class="relative">
    
    {{-- Label --}}
    @if($label)
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        {{ $label }}
        @if($required) <span class="text-red-500">*</span> @endif
    </label>
    @endif
    
    {{-- Selected Tags (Multiple) --}}
    <template x-if="multiple && selected && selected.length > 0">
        <div class="flex flex-wrap gap-2 mb-2">
            <template x-for="(item, idx) in selectedItems" :key="idx">
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-primary-100 text-primary-800 text-sm font-medium rounded-full">
                    <span x-text="item"></span>
                    <button type="button" @click="removeItem(selected[idx])" class="hover:text-primary-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </span>
            </template>
        </div>
    </template>
    
    {{-- Trigger Button --}}
    <button type="button"
            @click="open = !open"
            class="w-full flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-800 border rounded-xl shadow-sm transition-all
                   {{ $error ? 'border-red-300' : 'border-gray-300 dark:border-gray-600' }}
                   focus:ring-2 focus:ring-primary-500 focus:border-primary-500 hover:border-gray-400">
        <span :class="displayValue ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500'" 
              x-text="displayValue || '{{ $placeholder }}'"></span>
        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    
    {{-- Dropdown Panel --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-cloak
         class="absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl overflow-hidden">
        
        {{-- Search Input --}}
        @if($searchable)
        <div class="p-3 border-b border-gray-200 dark:border-gray-700">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text"
                       x-model="search"
                       placeholder="{{ $searchPlaceholder }}"
                       class="w-full pl-10 pr-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-gray-100"
                       @keydown.escape="open = false">
            </div>
        </div>
        @endif
        
        {{-- Options List --}}
        <div class="max-h-60 overflow-y-auto">
            {{-- Loading State --}}
            <template x-if="loading">
                <div class="p-4 text-center text-gray-500">
                    <svg class="animate-spin w-5 h-5 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Loading...
                </div>
            </template>
            
            {{-- No Results --}}
            <template x-if="!loading && filteredOptions.length === 0 && search">
                <div class="p-4 text-center text-gray-500">
                    @if($creatable)
                    <button type="button" 
                            @click="createOption()"
                            class="text-primary-600 hover:text-primary-800 font-medium">
                        + Create "<span x-text="search"></span>"
                    </button>
                    @else
                    No results found
                    @endif
                </div>
            </template>
            
            {{-- Options --}}
            <template x-for="(option, index) in filteredOptions" :key="index">
                <button type="button"
                        @click="toggle(typeof option === 'object' ? option.value : option)"
                        :class="isSelected(typeof option === 'object' ? option.value : option) 
                            ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' 
                            : 'hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300'"
                        class="w-full flex items-center justify-between px-4 py-3 text-left transition-colors">
                    <span x-text="typeof option === 'object' ? option.label : option"></span>
                    <template x-if="isSelected(typeof option === 'object' ? option.value : option)">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </template>
                </button>
            </template>
        </div>
        
        {{-- Max Items Notice --}}
        <template x-if="multiple && maxItems && selected && selected.length >= maxItems">
            <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300 text-sm text-center border-t">
                Maximum {{ $maxItems }} items selected
            </div>
        </template>
    </div>
    
    {{-- Hidden Input for Form --}}
    <template x-if="!multiple">
        <input type="hidden" name="{{ $name }}" :value="selected">
    </template>
    <template x-if="multiple">
        <template x-for="val in selected" :key="val">
            <input type="hidden" :name="'{{ $name }}[]'" :value="val">
        </template>
    </template>
    
    {{-- Error Message --}}
    @if($error)
    <p class="mt-2 text-sm text-red-600">{{ $error }}</p>
    @endif
    
    {{-- Help Text --}}
    @if($helpText)
    <p class="mt-2 text-sm text-gray-500">{{ $helpText }}</p>
    @endif
</div>
