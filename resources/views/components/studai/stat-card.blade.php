{{--
    StudAI Stat Card Component
    
    Usage:
    <x-studai.stat-card 
        title="Total Applications" 
        :value="156" 
        icon="heroicon-o-document-text"
        :change="12.5"
    />
--}}

@props([
    'title' => '',
    'value' => '',
    'icon' => null,
    'iconColor' => 'blue', // blue, green, yellow, red, purple
    'change' => null,
    'changeLabel' => 'vs last month',
    'suffix' => '',
    'prefix' => '',
])

@php
    $iconBgColors = [
        'blue' => 'bg-google-blue-50 text-google-blue-600',
        'green' => 'bg-google-green-50 text-google-green-600',
        'yellow' => 'bg-google-yellow-50 text-google-yellow-600',
        'red' => 'bg-google-red-50 text-google-red-600',
        'purple' => 'bg-purple-50 text-purple-600',
    ];
    
    $iconClasses = $iconBgColors[$iconColor] ?? $iconBgColors['blue'];
    
    // Ensure $change is numeric for calculations
    $numericChange = is_numeric($change) ? (float) $change : null;
@endphp

<x-studai.card {{ $attributes }}>
    <div class="flex items-start justify-between">
        <div>
            <p class="text-sm font-medium text-ink-secondary">{{ $title }}</p>
            <p class="mt-2 text-3xl font-bold text-ink-primary">
                {{ $prefix }}{{ is_numeric($value) ? number_format($value) : $value }}{{ $suffix }}
            </p>
            
            @if(!is_null($change))
                <div class="flex items-center gap-1.5 mt-2">
                    @if(!is_null($numericChange))
                        {{-- Numeric change: show with arrow and percentage --}}
                        @if($numericChange >= 0)
                            <svg class="w-4 h-4 text-google-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            <span class="text-sm font-medium text-google-green-600">+{{ abs($numericChange) }}%</span>
                        @else
                            <svg class="w-4 h-4 text-google-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                            </svg>
                            <span class="text-sm font-medium text-google-red-600">{{ $numericChange }}%</span>
                        @endif
                        <span class="text-sm text-ink-tertiary">{{ $changeLabel }}</span>
                    @else
                        {{-- String change: display as-is (e.g., "3 this week") --}}
                        <span class="text-sm text-ink-secondary">{{ $change }}</span>
                    @endif
                </div>
            @endif
        </div>
        
        @if($icon)
            <div class="p-3 rounded-xl {{ $iconClasses }}">
                <x-dynamic-component :component="$icon" class="w-6 h-6" />
            </div>
        @endif
    </div>
    
    @if($slot->isNotEmpty())
        <div class="mt-4 pt-4 border-t border-surface-100">
            {{ $slot }}
        </div>
    @endif
</x-studai.card>
