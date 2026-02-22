@extends('layouts.employer')

@section('title', 'Sync Logs - ' . $connection->name)

@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-4">
                <li>
                    <a href="{{ route('ats.index') }}" class="text-gray-400 hover:text-gray-500">ATS Integrations</a>
                </li>
                <li>
                    <span class="text-gray-400">/</span>
                    <a href="{{ route('ats.show', $connection) }}" class="ml-4 text-gray-400 hover:text-gray-500">{{ $connection->name }}</a>
                </li>
            </ol>
        </nav>
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl">
            Sync Logs
        </h2>
        <p class="mt-1 text-sm text-gray-500">
            View sync history and troubleshoot issues
        </p>
    </div>

    <!-- Logs Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Direction</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Records</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($logs as $log)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($log->status === 'completed')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Completed
                                </span>
                            @elseif($log->status === 'running')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    Running
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Failed
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ ucfirst($log->sync_type) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ ucfirst($log->direction) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="text-gray-900">{{ $log->records_processed ?? 0 }}</span> processed
                            @if($log->records_failed > 0)
                                <span class="text-red-600">({{ $log->records_failed }} failed)</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $log->duration ? $log->duration . 's' : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $log->created_at->format('M d, Y H:i') }}
                        </td>
                    </tr>
                    @if($log->error_details)
                        <tr class="bg-red-50">
                            <td colspan="6" class="px-6 py-3 text-sm text-red-700">
                                <strong>Error:</strong> {{ $log->error_details['message'] ?? json_encode($log->error_details) }}
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                            No sync logs yet. Run a sync to see logs here.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($logs->hasPages())
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
