{{-- 
    Slider Range Component - For numeric inputs without typing
    Usage: <x-ui.range-slider min="0" max="500000" step="10000" label="Salary Range" />
--}}

@props([
    'name' => '',
    'label' => '',
    'min' => 0,
    'max' => 100,
    'step' => 1,
    'value' => null,
    'minValue' => null,
    'maxValue' => null,
    'range' => false,
    'prefix' => '',
    'suffix' => '',
    'format' => 'number', // number, currency, percentage
    'showInput' => false,
    'showTicks' => false,
    'ticks' => [],
    'error' => null,
])

@php
$formatters = [
    'number' => fn($v) => number_format($v),
    'currency' => fn($v) => '₹' . number_format($v),
    'percentage' => fn($v) => $v . '%',
];
@endphp

<div x-data="{
    range: {{ $range ? 'true' : 'false' }},
    min: {{ $min }},
    max: {{ $max }},
    step: {{ $step }},
    value: @entangle($attributes->wire('model')) || {{ $value ?? $min }},
    minVal: {{ $minValue ?? $min }},
    maxVal: {{ $maxValue ?? $max }},
    
    get displayValue() {
        return this.formatNumber(this.value);
    },
    
    get displayRange() {
        return this.formatNumber(this.minVal) + ' - ' + this.formatNumber(this.maxVal);
    },
    
    get leftPercent() {
        return ((this.minVal - this.min) / (this.max - this.min)) * 100;
    },
    
    get rightPercent() {
        return ((this.maxVal - this.min) / (this.max - this.min)) * 100;
    },
    
    formatNumber(val) {
        @if($format === 'currency')
        return '₹' + new Intl.NumberFormat('en-IN').format(val);
        @elseif($format === 'percentage')
        return val + '%';
        @else
        return new Intl.NumberFormat().format(val);
        @endif
    },
    
    updateMin(val) {
        val = parseInt(val);
        if (val >= this.maxVal) val = this.maxVal - this.step;
        if (val < this.min) val = this.min;
        this.minVal = val;
    },
    
    updateMax(val) {
        val = parseInt(val);
        if (val <= this.minVal) val = this.minVal + this.step;
        if (val > this.max) val = this.max;
        this.maxVal = val;
    }
}" class="space-y-3">
    
    {{-- Label & Value Display --}}
    <div class="flex items-center justify-between">
        @if($label)
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
        </label>
        @endif
        <span class="text-sm font-semibold text-primary-600 dark:text-primary-400"
              x-text="range ? displayRange : displayValue"></span>
    </div>
    
    {{-- Single Slider --}}
    <template x-if="!range">
        <div class="relative">
            <input type="range"
                   x-model="value"
                   :min="min"
                   :max="max"
                   :step="step"
                   class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-primary-600
                          [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-5 [&::-webkit-slider-thumb]:h-5 
                          [&::-webkit-slider-thumb]:bg-primary-600 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:shadow-lg
                          [&::-webkit-slider-thumb]:cursor-grab [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-white">
            
            {{-- Value Tooltip --}}
            <div class="absolute -top-8 transform -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 hover:opacity-100 transition-opacity"
                 :style="'left: ' + ((value - min) / (max - min) * 100) + '%'"
                 x-text="displayValue"></div>
        </div>
    </template>
    
    {{-- Range Slider (Two Handles) --}}
    <template x-if="range">
        <div class="relative pt-1">
            {{-- Track --}}
            <div class="relative h-2 bg-gray-200 dark:bg-gray-700 rounded-lg">
                {{-- Active Range --}}
                <div class="absolute h-2 bg-gradient-to-r from-primary-500 to-primary-600 rounded-lg"
                     :style="'left: ' + leftPercent + '%; right: ' + (100 - rightPercent) + '%'"></div>
            </div>
            
            {{-- Min Handle --}}
            <input type="range"
                   :value="minVal"
                   @input="updateMin($event.target.value)"
                   :min="min"
                   :max="max"
                   :step="step"
                   class="absolute w-full h-2 -top-0 appearance-none cursor-pointer bg-transparent pointer-events-none
                          [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:appearance-none 
                          [&::-webkit-slider-thumb]:w-5 [&::-webkit-slider-thumb]:h-5 [&::-webkit-slider-thumb]:bg-white 
                          [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-primary-600 
                          [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:shadow-lg 
                          [&::-webkit-slider-thumb]:cursor-grab">
            
            {{-- Max Handle --}}
            <input type="range"
                   :value="maxVal"
                   @input="updateMax($event.target.value)"
                   :min="min"
                   :max="max"
                   :step="step"
                   class="absolute w-full h-2 -top-0 appearance-none cursor-pointer bg-transparent pointer-events-none
                          [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:appearance-none 
                          [&::-webkit-slider-thumb]:w-5 [&::-webkit-slider-thumb]:h-5 [&::-webkit-slider-thumb]:bg-white 
                          [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-primary-600 
                          [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:shadow-lg 
                          [&::-webkit-slider-thumb]:cursor-grab">
        </div>
    </template>
    
    {{-- Quick Select Buttons --}}
    @if(count($ticks) > 0)
    <div class="flex flex-wrap gap-2 mt-3">
        @foreach($ticks as $tick)
        <button type="button"
                @click="range ? (minVal = {{ $tick['min'] ?? $tick }}, maxVal = {{ $tick['max'] ?? $tick }}) : (value = {{ $tick['value'] ?? $tick }})"
                class="px-3 py-1 text-xs font-medium border border-gray-300 dark:border-gray-600 rounded-full hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors">
            {{ $tick['label'] ?? ($formatters[$format])($tick['value'] ?? $tick) }}
        </button>
        @endforeach
    </div>
    @endif
    
    {{-- Min/Max Labels --}}
    <div class="flex justify-between text-xs text-gray-500">
        <span x-text="formatNumber(min)"></span>
        <span x-text="formatNumber(max)"></span>
    </div>
    
    {{-- Hidden Inputs --}}
    <template x-if="!range">
        <input type="hidden" name="{{ $name }}" :value="value">
    </template>
    <template x-if="range">
        <div>
            <input type="hidden" name="{{ $name }}_min" :value="minVal">
            <input type="hidden" name="{{ $name }}_max" :value="maxVal">
        </div>
    </template>
    
    {{-- Error --}}
    @if($error)
    <p class="text-sm text-red-600">{{ $error }}</p>
    @endif
</div>
