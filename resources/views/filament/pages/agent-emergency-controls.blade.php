<x-filament-panels::page>
    {{-- Kill Switch Status Banner --}}
    @if($this->globalKillSwitchActive)
        <div class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 px-4 py-3 rounded-lg mb-6">
            <div class="flex items-center">
                <x-heroicon-o-exclamation-triangle class="h-6 w-6 mr-2" />
                <div>
                    <p class="font-bold">Global Kill Switch is ACTIVE</p>
                    <p class="text-sm">
                        All agent operations are currently suspended.
                        @if($this->killSwitchInfo)
                            Activated at {{ \Carbon\Carbon::parse($this->killSwitchInfo['activated_at'])->format('M d, Y H:i') }}.
                            Reason: {{ $this->killSwitchInfo['reason'] }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 px-4 py-3 rounded-lg mb-6">
            <div class="flex items-center">
                <x-heroicon-o-shield-check class="h-6 w-6 mr-2" />
                <div>
                    <p class="font-bold">System Operating Normally</p>
                    <p class="text-sm">Global kill switch is not active. Agents are operating normally.</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Stats Overview --}}
    @php
        $stats = $this->getStatsData();
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Agents</div>
            <div class="text-2xl font-bold">{{ $stats['total_agents'] }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Active Agents</div>
            <div class="text-2xl font-bold text-green-600">{{ $stats['active_agents'] }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Emergency Stopped</div>
            <div class="text-2xl font-bold text-red-600">{{ $stats['emergency_stopped'] }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500 dark:text-gray-400">Globally Stopped</div>
            <div class="text-2xl font-bold text-orange-600">{{ $stats['globally_stopped'] }}</div>
        </div>
    </div>

    {{-- Agent Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-4 border-b dark:border-gray-700">
            <h3 class="text-lg font-medium">Agent Configurations</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage individual agent emergency stops</p>
        </div>
        {{ $this->table }}
    </div>
</x-filament-panels::page>
