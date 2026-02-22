<nav class="fixed bottom-0 inset-x-0 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 z-40"
     style="padding-bottom: calc(var(--sab, 0));">
    <div class="flex items-stretch justify-around h-16">
        @foreach($navItems as $item)
            <a href="{{ route($item['route']) }}" 
               class="flex-1 flex flex-col items-center justify-center py-2 relative
                      {{ $this->isActive($item['route']) 
                         ? 'text-pink-500' 
                         : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}"
               wire:navigate>
                
                <!-- Icon -->
                <div class="relative">
                    @switch($item['icon'])
                        @case('home')
                            <svg class="w-6 h-6" fill="{{ $this->isActive($item['route']) ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $this->isActive($item['route']) ? '0' : '2' }}" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            @break
                        @case('briefcase')
                            <svg class="w-6 h-6" fill="{{ $this->isActive($item['route']) ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $this->isActive($item['route']) ? '0' : '2' }}" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            @break
                        @case('heart')
                            <svg class="w-6 h-6" fill="{{ $this->isActive($item['route']) ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $this->isActive($item['route']) ? '0' : '2' }}" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                            @break
                        @case('document-text')
                            <svg class="w-6 h-6" fill="{{ $this->isActive($item['route']) ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $this->isActive($item['route']) ? '0' : '2' }}" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            @break
                        @case('user-circle')
                            <svg class="w-6 h-6" fill="{{ $this->isActive($item['route']) ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $this->isActive($item['route']) ? '0' : '2' }}" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            @break
                    @endswitch
                    
                    <!-- Badge -->
                    @if($item['badge'])
                        <span class="absolute -top-1 -right-1 w-4 h-4 bg-pink-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                            {{ $item['badge'] > 9 ? '9+' : $item['badge'] }}
                        </span>
                    @endif
                </div>
                
                <!-- Label -->
                <span class="text-xs mt-1 font-medium">{{ $item['name'] }}</span>
                
                <!-- Active Indicator -->
                @if($this->isActive($item['route']))
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-0.5 bg-pink-500 rounded-full"></div>
                @endif
            </a>
        @endforeach
    </div>
</nav>
