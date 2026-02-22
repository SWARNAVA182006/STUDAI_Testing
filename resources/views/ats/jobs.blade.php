@extends('layouts.employer')

@section('title', 'Synced Jobs - ' . $connection->name)

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
            Synced Jobs
        </h2>
        <p class="mt-1 text-sm text-gray-500">
            Jobs synchronized from {{ $connection->provider->name ?? 'ATS' }}
        </p>
    </div>

    <!-- Jobs Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">External Job</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Local Job</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Direction</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Synced</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($mappings as $mapping)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $mapping->external_job_id }}</div>
                            @if($mapping->external_requisition_id)
                                <div class="text-sm text-gray-500">Req: {{ $mapping->external_requisition_id }}</div>
                            @endif
                            @if($mapping->external_url)
                                <a href="{{ $mapping->external_url }}" target="_blank" class="text-xs text-primary-600 hover:text-primary-800">View in ATS →</a>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($mapping->jobPosting)
                                <div class="text-sm font-medium text-gray-900">{{ $mapping->jobPosting->title }}</div>
                                <div class="text-sm text-gray-500">{{ $mapping->jobPosting->location }}</div>
                            @else
                                <span class="text-sm text-gray-500">Not linked</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($mapping->sync_status === 'synced')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Synced
                                </span>
                            @elseif($mapping->sync_status === 'pending')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Pending
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Failed
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ ucfirst($mapping->sync_direction) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $mapping->last_synced_at ? $mapping->last_synced_at->diffForHumans() : 'Never' }}
                        </td>
                    </tr>
                    @if($mapping->sync_error)
                        <tr class="bg-red-50">
                            <td colspan="5" class="px-6 py-2 text-sm text-red-700">
                                <strong>Error:</strong> {{ $mapping->sync_error }}
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                            No jobs synced yet. Run a sync to import jobs.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($mappings->hasPages())
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $mappings->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
