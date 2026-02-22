<div class="flex flex-col h-full max-w-4xl mx-auto" x-data="{ showVoice: false }">
    <!-- Messages Container -->
    <div class="flex-1 overflow-y-auto p-4 space-y-4" id="messages-container" wire:poll.30s>
        @foreach($messages as $msg)
        <div class="flex {{ $msg['role'] === 'user' ? 'justify-end' : 'justify-start' }}" wire:key="msg-{{ $msg['id'] }}">
            <div class="max-w-[80%] {{ $msg['role'] === 'user' ? 'order-2' : '' }}">
                @if($msg['role'] === 'assistant')
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center flex-shrink-0">
                        <span class="text-lg">🎯</span>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl rounded-tl-none px-4 py-3 shadow-sm">
                        <div class="prose prose-sm dark:prose-invert max-w-none">
                            {!! \Illuminate\Support\Str::markdown($msg['content']) !!}
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">{{ $msg['created_at'] }}</p>
                    </div>
                </div>
                @else
                <div class="bg-indigo-600 text-white rounded-2xl rounded-tr-none px-4 py-3">
                    <p class="whitespace-pre-wrap">{{ $msg['content'] }}</p>
                    <p class="text-xs text-indigo-200 mt-2 flex items-center gap-1">
                        @if($msg['is_voice'] ?? false)
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm-1-9c0-.55.45-1 1-1s1 .45 1 1v6c0 .55-.45 1-1 1s-1-.45-1-1V5zm6 6c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                        </svg>
                        @endif
                        {{ $msg['created_at'] }}
                    </p>
                </div>
                @endif
            </div>
        </div>
        @endforeach

        <!-- Loading Indicator -->
        <div wire:loading wire:target="sendMessage" class="flex justify-start">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center flex-shrink-0">
                    <span class="text-lg">🎯</span>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl rounded-tl-none px-4 py-3 shadow-sm">
                    <div class="flex items-center gap-2">
                        <div class="flex gap-1">
                            <span class="w-2 h-2 bg-indigo-600 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                            <span class="w-2 h-2 bg-indigo-600 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                            <span class="w-2 h-2 bg-indigo-600 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Thinking...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Input Area -->
    <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 p-4">
        <form wire:submit="sendMessage" class="flex items-end gap-3">
            <div class="flex-1 relative">
                <textarea 
                    wire:model="message"
                    placeholder="Type your message..."
                    rows="1"
                    class="w-full px-4 py-3 pr-12 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white resize-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    x-on:keydown.enter.prevent="if (!$event.shiftKey) { $wire.sendMessage(); }"
                    x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 150) + 'px';"
                ></textarea>
                
                <!-- Voice Button -->
                <button 
                    type="button"
                    @click="showVoice = !showVoice"
                    class="absolute right-3 bottom-3 p-1 rounded-full text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors"
                    title="Voice input"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm-1-9c0-.55.45-1 1-1s1 .45 1 1v6c0 .55-.45 1-1 1s-1-.45-1-1V5zm6 6c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                    </svg>
                </button>
            </div>
            
            <button 
                type="submit"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50 cursor-not-allowed"
                class="px-4 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors flex items-center gap-2"
            >
                <span wire:loading.remove wire:target="sendMessage">Send</span>
                <span wire:loading wire:target="sendMessage">Sending...</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </form>

        <!-- Session Actions -->
        <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ count($messages) }} messages
            </div>
            <div class="flex items-center gap-2">
                <button 
                    wire:click="endSession"
                    wire:confirm="Are you sure you want to end this session?"
                    class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                >
                    End Session
                </button>
            </div>
        </div>
    </div>

    <!-- Voice Input Modal -->
    <div 
        x-show="showVoice" 
        x-cloak
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
        @click.self="showVoice = false"
    >
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 max-w-sm w-full mx-4 text-center">
            <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center animate-pulse">
                <svg class="w-10 h-10 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm-1-9c0-.55.45-1 1-1s1 .45 1 1v6c0 .55-.45 1-1 1s-1-.45-1-1V5zm6 6c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Listening...</h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm mb-4">Speak now. Click to stop.</p>
            <p class="text-xs text-gray-400 dark:text-gray-500">Voice input requires browser support for Speech Recognition API</p>
            <button @click="showVoice = false" class="mt-4 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                Cancel
            </button>
        </div>
    </div>
</div>

@script
<script>
    // Auto-scroll to bottom when new messages arrive
    $wire.on('scroll-to-bottom', () => {
        setTimeout(() => {
            const container = document.getElementById('messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }, 100);
    });

    // Initial scroll to bottom
    document.addEventListener('livewire:navigated', () => {
        const container = document.getElementById('messages-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    });

    // Scroll on page load
    window.addEventListener('load', () => {
        const container = document.getElementById('messages-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    });
</script>
@endscript
