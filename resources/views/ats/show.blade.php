@extends('layouts.employer')

@section('title', $connection->name . ' - ATS Integration')

@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-4">
                    <li>
                        <a href="{{ route('ats.index') }}" class="text-gray-400 hover:text-gray-500">
                            <svg class="flex-shrink-0 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('ats.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">ATS Integrations</a>
                    </li>
                </ol>
            </nav>
            <div class="flex items-center">
                <div class="flex-shrink-0 h-12 w-12 bg-gray-100 rounded-lg flex items-center justify-center mr-4">
                    @if($connection->provider?->logo_url)
                        <img src="{{ $connection->provider->logo_url }}" alt="{{ $connection->provider->name }}" class="h-8 w-8">
                    @else
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    @endif
                </div>
                <div>
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                        {{ $connection->name }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ $connection->provider?->name ?? 'Unknown Provider' }}
                    </p>
                </div>
            </div>
        </div>
        <div class="mt-4 flex flex-wrap gap-2 md:mt-0 md:ml-4">
            <button type="button" onclick="testConnection()" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Test Connection
            </button>
            <form action="{{ route('ats.sync', $connection) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center px-3 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Sync Now
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-md bg-green-50 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('warning'))
        <div class="rounded-md bg-yellow-50 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-yellow-800">{{ session('warning') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Connection Status Card -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Status -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <dt class="text-sm font-medium text-gray-500 truncate">Status</dt>
                    <dd class="mt-1 flex items-center">
                        @if($connection->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <span class="w-2 h-2 mr-1.5 bg-green-400 rounded-full"></span>
                                Connected
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <span class="w-2 h-2 mr-1.5 bg-red-400 rounded-full"></span>
                                Disconnected
                            </span>
                        @endif
                    </dd>
                </div>

                <!-- Last Sync -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <dt class="text-sm font-medium text-gray-500 truncate">Last Sync</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900">
                        {{ $connection->last_sync_at ? $connection->last_sync_at->diffForHumans() : 'Never' }}
                    </dd>
                </div>

                <!-- Synced Candidates -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <dt class="text-sm font-medium text-gray-500 truncate">Synced Candidates</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $stats['synced_candidates'] ?? 0 }}</dd>
                </div>

                <!-- Synced Jobs -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <dt class="text-sm font-medium text-gray-500 truncate">Synced Jobs</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $stats['synced_jobs'] ?? 0 }}</dd>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-6">
        <a href="{{ route('ats.candidates', $connection) }}" class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">View Candidates</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['total_candidates'] ?? 0 }} mapped</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </a>

        <a href="{{ route('ats.jobs', $connection) }}" class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">View Jobs</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['total_jobs'] ?? 0 }} mapped</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </a>

        <a href="{{ route('ats.logs', $connection) }}" class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">View Sync Logs</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $recentLogs->count() }} recent</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Recent Sync Logs -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Sync Activity</h3>
        </div>
        @if($recentLogs->count() > 0)
            <ul class="divide-y divide-gray-200">
                @foreach($recentLogs as $log)
                    <li class="px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                @if($log->status === 'completed')
                                    <span class="flex-shrink-0 w-2.5 h-2.5 rounded-full bg-green-400 mr-3"></span>
                                @elseif($log->status === 'running')
                                    <span class="flex-shrink-0 w-2.5 h-2.5 rounded-full bg-blue-400 mr-3 animate-pulse"></span>
                                @else
                                    <span class="flex-shrink-0 w-2.5 h-2.5 rounded-full bg-red-400 mr-3"></span>
                                @endif
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ ucfirst($log->sync_type) }} Sync ({{ ucfirst($log->direction) }})
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ $log->records_processed ?? 0 }} processed • 
                                        {{ $log->records_created ?? 0 }} created • 
                                        {{ $log->records_updated ?? 0 }} updated
                                        @if($log->records_failed > 0)
                                            • <span class="text-red-600">{{ $log->records_failed }} failed</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $log->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="px-4 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No sync activity yet</h3>
                <p class="mt-1 text-sm text-gray-500">Run your first sync to see activity here.</p>
            </div>
        @endif
    </div>

    <!-- Settings & Danger Zone -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Settings</h3>
        </div>
        <div class="px-4 py-5 sm:p-6">
            <div class="space-y-6">
                <!-- Toggle Active -->
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">Connection Status</h4>
                        <p class="text-sm text-gray-500">Enable or disable this ATS connection</p>
                    </div>
                    <form action="{{ route('ats.update', $connection) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="is_active" value="{{ $connection->is_active ? '0' : '1' }}">
                        <button type="submit" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 {{ $connection->is_active ? 'bg-primary-600' : 'bg-gray-200' }}">
                            <span class="translate-x-0 inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $connection->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                        </button>
                    </form>
                </div>

                <!-- Danger Zone -->
                <div class="pt-6 border-t border-gray-200">
                    <h4 class="text-sm font-medium text-red-600">Danger Zone</h4>
                    <p class="mt-1 text-sm text-gray-500">Permanently delete this connection and all synced data.</p>
                    <form action="{{ route('ats.destroy', $connection) }}" method="POST" class="mt-3" onsubmit="return confirm('Are you sure you want to delete this connection? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Delete Connection
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function testConnection() {
        fetch('{{ route('ats.test', $connection) }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Error testing connection');
            });
    }
</script>
@endsection
