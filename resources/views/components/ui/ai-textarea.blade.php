{{-- 
    AI-Enhanced Textarea Component
    Usage: <x-ui.ai-textarea name="summary" :suggestions="['Professional summary', 'Career highlights']" />
--}}

@props([
    'name' => '',
    'label' => '',
    'placeholder' => 'Start typing or click ✨ to write with AI...',
    'value' => '',
    'required' => false,
    'rows' => 4,
    'maxLength' => 1000,
    'aiPrompt' => '',
    'aiContext' => '',
    'suggestions' => [],
    'showCharCount' => true,
    'error' => null,
])

<div x-data="{
    content: @entangle($attributes->wire('model')) || '{{ $value }}',
    showAiPanel: false,
    aiLoading: false,
    charCount: 0,
    selectedTone: 'professional',
    suggestions: {{ json_encode($suggestions) }},
    
    init() {
        this.charCount = this.content?.length || 0;
        this.$watch('content', (val) => this.charCount = val?.length || 0);
    },
    
    async generateWithAI() {
        this.aiLoading = true;
        try {
            const response = await fetch('/api/ai/generate-text', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({
                    context: '{{ $aiContext }}',
                    prompt: '{{ $aiPrompt }}',
                    tone: this.selectedTone,
                    current_text: this.content,
                    field: '{{ $name }}'
                })
            });
            const data = await response.json();
            if (data.text) {
                this.content = data.text;
            }
        } catch (e) {
            console.error('AI generation failed:', e);
        } finally {
            this.aiLoading = false;
            this.showAiPanel = false;
        }
    },
    
    async enhanceText() {
        if (!this.content) return;
        this.aiLoading = true;
        try {
            const response = await fetch('/api/ai/enhance-text', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({
                    text: this.content,
                    tone: this.selectedTone,
                    field: '{{ $name }}'
                })
            });
            const data = await response.json();
            if (data.enhanced) {
                this.content = data.enhanced;
            }
        } catch (e) {
            console.error('AI enhancement failed:', e);
        } finally {
            this.aiLoading = false;
        }
    },
    
    useSuggestion(text) {
        this.content = text;
        this.showAiPanel = false;
    }
}" class="relative">
    {{-- Label --}}
    @if($label)
    <label for="{{ $name }}" class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span> @endif
        </span>
        
        {{-- AI Actions --}}
        <div class="flex items-center gap-2">
            <button type="button" 
                    @click="enhanceText()"
                    :disabled="!content || aiLoading"
                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-purple-600 hover:text-purple-800 hover:bg-purple-50 rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Enhance
            </button>
            <button type="button" 
                    @click="showAiPanel = !showAiPanel"
                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-primary-600 hover:text-primary-800 hover:bg-primary-50 rounded-md transition-colors">
                <span class="text-base">✨</span>
                Write with AI
            </button>
        </div>
    </label>
    @endif
    
    {{-- AI Panel --}}
    <div x-show="showAiPanel" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-cloak
         class="mb-3 p-4 bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
        
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">✨ AI Writing Assistant</span>
            <button @click="showAiPanel = false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        {{-- Tone Selector --}}
        <div class="flex flex-wrap gap-2 mb-3">
            <span class="text-xs text-gray-500">Tone:</span>
            <template x-for="tone in ['professional', 'friendly', 'confident', 'creative']">
                <button type="button"
                        @click="selectedTone = tone"
                        :class="selectedTone === tone ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                        class="px-3 py-1 text-xs font-medium rounded-full border transition-colors capitalize">
                    <span x-text="tone"></span>
                </button>
            </template>
        </div>
        
        {{-- Quick Suggestions --}}
        @if(count($suggestions) > 0)
        <div class="mb-3">
            <span class="text-xs text-gray-500 block mb-2">Quick suggestions:</span>
            <div class="flex flex-wrap gap-2">
                @foreach($suggestions as $suggestion)
                <button type="button" 
                        @click="useSuggestion('{{ addslashes($suggestion) }}')"
                        class="px-3 py-1.5 text-xs bg-white hover:bg-gray-50 text-gray-700 rounded-lg border shadow-sm transition-colors">
                    {{ Str::limit($suggestion, 40) }}
                </button>
                @endforeach
            </div>
        </div>
        @endif
        
        {{-- Generate Button --}}
        <button type="button"
                @click="generateWithAI()"
                :disabled="aiLoading"
                class="w-full py-2 px-4 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white text-sm font-medium rounded-lg transition-all disabled:opacity-50 flex items-center justify-center gap-2">
            <template x-if="aiLoading">
                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </template>
            <span x-text="aiLoading ? 'Generating...' : '✨ Generate with AI'"></span>
        </button>
    </div>
    
    {{-- Textarea --}}
    <div class="relative">
        <textarea
            id="{{ $name }}"
            name="{{ $name }}"
            x-model="content"
            rows="{{ $rows }}"
            maxlength="{{ $maxLength }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge([
                'class' => 'w-full px-4 py-3 border rounded-xl shadow-sm transition-all duration-200
                           focus:ring-2 focus:ring-primary-500 focus:border-primary-500
                           ' . ($error ? 'border-red-300 bg-red-50' : 'border-gray-300 dark:border-gray-600')
                           . ' dark:bg-gray-800 dark:text-gray-100 resize-none'
            ]) }}
        ></textarea>
        
        {{-- Loading Overlay --}}
        <div x-show="aiLoading" 
             class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 rounded-xl flex items-center justify-center">
            <div class="flex items-center gap-2 text-primary-600">
                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span class="text-sm font-medium">AI is writing...</span>
            </div>
        </div>
    </div>
    
    {{-- Footer --}}
    <div class="flex items-center justify-between mt-2">
        @if($error)
            <p class="text-sm text-red-600">{{ $error }}</p>
        @else
            <span></span>
        @endif
        
        @if($showCharCount)
        <span class="text-xs" :class="charCount > {{ $maxLength * 0.9 }} ? 'text-orange-500' : 'text-gray-400'">
            <span x-text="charCount"></span>/{{ $maxLength }}
        </span>
        @endif
    </div>
</div>
