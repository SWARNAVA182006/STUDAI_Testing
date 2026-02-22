<div>
    @if($userRsvp?->status === 'going')
        <button wire:click="cancelRsvp"
                class="w-full px-4 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
            Cancel RSVP
        </button>
    @elseif($userRsvp?->status === 'interested')
        <div class="space-y-3">
            <button wire:click="rsvp('going')"
                    class="w-full px-4 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition"
                    @if($event->isFull()) disabled @endif>
                {{ $event->isFull() ? 'Event is Full' : 'Attend Event' }}
            </button>
            <button wire:click="cancelRsvp"
                    class="w-full px-4 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                Remove Interest
            </button>
        </div>
    @else
        <div class="space-y-3">
            <button wire:click="rsvp('going')"
                    class="w-full px-4 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    @if($event->isFull()) disabled @endif>
                {{ $event->isFull() ? 'Event is Full' : 'Attend Event' }}
            </button>
            <button wire:click="rsvp('interested')"
                    class="w-full px-4 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
                Interested
            </button>
        </div>
    @endif
</div>
