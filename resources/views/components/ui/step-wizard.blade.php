@props([
    'steps' => [],
    'currentStep' => 1,
    'completedSteps' => [],
    'allowJumpAhead' => false,
    'showDescription' => true,
    'size' => 'md',
])

@php
    $sizeClasses = [
        'sm' => [
            'step' => 'w-8 h-8 text-sm',
            'title' => 'text-xs',
            'description' => 'text-xs',
            'connector' => 'h-0.5',
            'icon' => 'w-4 h-4',
        ],
        'md' => [
            'step' => 'w-10 h-10 text-base',
            'title' => 'text-sm',
            'description' => 'text-xs',
            'connector' => 'h-0.5',
            'icon' => 'w-5 h-5',
        ],
        'lg' => [
            'step' => 'w-12 h-12 text-lg',
            'title' => 'text-base',
            'description' => 'text-sm',
            'connector' => 'h-1',
            'icon' => 'w-6 h-6',
        ],
    ];

    $sizes = $sizeClasses[$size] ?? $sizeClasses['md'];
    $totalSteps = count($steps);
    $completedCount = count($completedSteps);
    $progressPercentage = $totalSteps > 0 ? round(($completedCount / $totalSteps) * 100) : 0;
@endphp

<div
    x-data="{
        currentStep: @js($currentStep),
        completedSteps: @js($completedSteps),
        allowJumpAhead: @js($allowJumpAhead),
        totalSteps: @js($totalSteps),
        
        isCompleted(step) {
            return this.completedSteps.includes(step);
        },
        
        isCurrent(step) {
            return this.currentStep === step;
        },
        
        isClickable(step) {
            if (this.isCompleted(step)) return true;
            if (this.isCurrent(step)) return true;
            if (this.allowJumpAhead) return true;
            return step <= Math.max(...this.completedSteps, this.currentStep);
        },
        
        goToStep(step) {
            if (!this.isClickable(step)) return;
            
            this.currentStep = step;
            $dispatch('step-changed', { step: step });
            
            // Emit Livewire event if available
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('stepChanged', { step: step });
            }
        },
        
        getProgressPercentage() {
            return this.totalSteps > 0 ? Math.round((this.completedSteps.length / this.totalSteps) * 100) : 0;
        }
    }"
    {{ $attributes->merge(['class' => 'w-full']) }}
>
    {{-- Progress Bar --}}
    <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Progress</span>
            <span class="text-sm font-medium text-primary-600 dark:text-primary-400" x-text="getProgressPercentage() + '%'"></span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full {{ $sizes['connector'] }} overflow-hidden">
            <div
                class="bg-gradient-to-r from-primary-500 to-primary-600 {{ $sizes['connector'] }} rounded-full transition-all duration-500 ease-out"
                :style="'width: ' + getProgressPercentage() + '%'"
            ></div>
        </div>
    </div>

    {{-- Desktop Step Wizard --}}
    <div class="hidden md:block">
        <div class="flex items-start justify-between relative">
            {{-- Connector Line (Background) --}}
            <div class="absolute top-5 left-0 right-0 flex items-center" style="z-index: 0;">
                <div class="w-full mx-auto px-6">
                    <div class="bg-gray-200 dark:bg-gray-700 {{ $sizes['connector'] }} w-full rounded-full"></div>
                </div>
            </div>

            @foreach($steps as $index => $step)
                @php
                    $stepNumber = $index + 1;
                    $isFirst = $index === 0;
                    $isLast = $index === count($steps) - 1;
                @endphp

                <div
                    class="flex flex-col items-center relative flex-1"
                    :class="{ 'cursor-pointer': isClickable({{ $stepNumber }}), 'cursor-not-allowed opacity-60': !isClickable({{ $stepNumber }}) }"
                    @click="goToStep({{ $stepNumber }})"
                >
                    {{-- Step Circle --}}
                    <div
                        class="relative z-10 flex items-center justify-center rounded-full border-2 transition-all duration-300 transform {{ $sizes['step'] }}"
                        :class="{
                            'bg-primary-600 border-primary-600 text-white scale-110 ring-4 ring-primary-100 dark:ring-primary-900': isCurrent({{ $stepNumber }}),
                            'bg-green-500 border-green-500 text-white': isCompleted({{ $stepNumber }}) && !isCurrent({{ $stepNumber }}),
                            'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400': !isCompleted({{ $stepNumber }}) && !isCurrent({{ $stepNumber }}),
                            'hover:border-primary-400 hover:text-primary-600': isClickable({{ $stepNumber }}) && !isCurrent({{ $stepNumber }}) && !isCompleted({{ $stepNumber }})
                        }"
                    >
                        {{-- Pulse Animation for Current Step --}}
                        <template x-if="isCurrent({{ $stepNumber }})">
                            <span class="absolute inset-0 rounded-full bg-primary-400 animate-ping opacity-25"></span>
                        </template>

                        {{-- Step Content (Icon or Number) --}}
                        <template x-if="isCompleted({{ $stepNumber }}) && !isCurrent({{ $stepNumber }})">
                            <svg class="{{ $sizes['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </template>

                        <template x-if="!isCompleted({{ $stepNumber }}) || isCurrent({{ $stepNumber }})">
                            @if(!empty($step['icon']))
                                <span class="{{ $sizes['icon'] }}">{!! $step['icon'] !!}</span>
                            @else
                                <span class="font-semibold">{{ $stepNumber }}</span>
                            @endif
                        </template>
                    </div>

                    {{-- Step Title --}}
                    <div class="mt-3 text-center">
                        <p
                            class="font-medium transition-colors duration-200 {{ $sizes['title'] }}"
                            :class="{
                                'text-primary-600 dark:text-primary-400': isCurrent({{ $stepNumber }}),
                                'text-green-600 dark:text-green-400': isCompleted({{ $stepNumber }}) && !isCurrent({{ $stepNumber }}),
                                'text-gray-500 dark:text-gray-400': !isCompleted({{ $stepNumber }}) && !isCurrent({{ $stepNumber }})
                            }"
                        >
                            {{ $step['title'] ?? "Step $stepNumber" }}
                        </p>

                        {{-- Step Description --}}
                        @if($showDescription && !empty($step['description']))
                            <p class="mt-1 text-gray-400 dark:text-gray-500 max-w-[120px] mx-auto {{ $sizes['description'] }}">
                                {{ $step['description'] }}
                            </p>
                        @endif
                    </div>

                    {{-- Connector Line (Colored for completed) --}}
                    @if(!$isLast)
                        <div
                            class="absolute top-5 left-1/2 w-full {{ $sizes['connector'] }} transition-all duration-500"
                            style="z-index: 1;"
                        >
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="isCompleted({{ $stepNumber }}) ? 'bg-green-500' : 'bg-transparent'"
                                :style="isCompleted({{ $stepNumber }}) ? 'width: 100%' : 'width: 0%'"
                            ></div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Mobile Step Wizard --}}
    <div class="md:hidden">
        <div class="space-y-4">
            @foreach($steps as $index => $step)
                @php
                    $stepNumber = $index + 1;
                    $isLast = $index === count($steps) - 1;
                @endphp

                <div
                    class="flex items-start transition-all duration-200"
                    :class="{ 'cursor-pointer': isClickable({{ $stepNumber }}), 'cursor-not-allowed opacity-60': !isClickable({{ $stepNumber }}) }"
                    @click="goToStep({{ $stepNumber }})"
                >
                    {{-- Step Indicator --}}
                    <div class="flex flex-col items-center mr-4">
                        {{-- Step Circle --}}
                        <div
                            class="relative flex items-center justify-center rounded-full border-2 transition-all duration-300 {{ $sizes['step'] }}"
                            :class="{
                                'bg-primary-600 border-primary-600 text-white ring-4 ring-primary-100 dark:ring-primary-900': isCurrent({{ $stepNumber }}),
                                'bg-green-500 border-green-500 text-white': isCompleted({{ $stepNumber }}) && !isCurrent({{ $stepNumber }}),
                                'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400': !isCompleted({{ $stepNumber }}) && !isCurrent({{ $stepNumber }})
                            }"
                        >
                            {{-- Pulse Animation for Current Step --}}
                            <template x-if="isCurrent({{ $stepNumber }})">
                                <span class="absolute inset-0 rounded-full bg-primary-400 animate-ping opacity-25"></span>
                            </template>

                            {{-- Step Content --}}
                            <template x-if="isCompleted({{ $stepNumber }}) && !isCurrent({{ $stepNumber }})">
                                <svg class="{{ $sizes['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </template>

                            <template x-if="!isCompleted({{ $stepNumber }}) || isCurrent({{ $stepNumber }})">
                                @if(!empty($step['icon']))
                                    <span class="{{ $sizes['icon'] }}">{!! $step['icon'] !!}</span>
                                @else
                                    <span class="font-semibold">{{ $stepNumber }}</span>
                                @endif
                            </template>
                        </div>

                        {{-- Vertical Connector Line --}}
                        @if(!$isLast)
                            <div class="w-0.5 h-8 mt-2 transition-colors duration-300"
                                :class="isCompleted({{ $stepNumber }}) ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700'"
                            ></div>
                        @endif
                    </div>

                    {{-- Step Content --}}
                    <div class="flex-1 pb-4">
                        <p
                            class="font-medium transition-colors duration-200 {{ $sizes['title'] }}"
                            :class="{
                                'text-primary-600 dark:text-primary-400': isCurrent({{ $stepNumber }}),
                                'text-green-600 dark:text-green-400': isCompleted({{ $stepNumber }}) && !isCurrent({{ $stepNumber }}),
                                'text-gray-700 dark:text-gray-300': !isCompleted({{ $stepNumber }}) && !isCurrent({{ $stepNumber }})
                            }"
                        >
                            {{ $step['title'] ?? "Step $stepNumber" }}
                        </p>

                        @if($showDescription && !empty($step['description']))
                            <p class="mt-1 text-gray-500 dark:text-gray-400 {{ $sizes['description'] }}">
                                {{ $step['description'] }}
                            </p>
                        @endif

                        {{-- Current Step Indicator Badge --}}
                        <template x-if="isCurrent({{ $stepNumber }})">
                            <span class="inline-flex items-center mt-2 px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                                <span class="w-1.5 h-1.5 mr-1.5 bg-primary-500 rounded-full animate-pulse"></span>
                                In Progress
                            </span>
                        </template>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Step Counter (Mobile) --}}
    <div class="md:hidden mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600 dark:text-gray-400">
                Step <span class="font-semibold text-gray-900 dark:text-white" x-text="currentStep"></span> of <span x-text="totalSteps"></span>
            </span>
            <span class="text-gray-600 dark:text-gray-400">
                <span class="font-semibold text-green-600 dark:text-green-400" x-text="completedSteps.length"></span> completed
            </span>
        </div>
    </div>
</div>
