@extends('layouts.app')

@section('title', 'Career Goals - AI Career Coach')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <nav class="flex mb-2" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1">
                        <li><a href="{{ route('career-coach.index') }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">Career Coach</a></li>
                        <li><span class="mx-2 text-gray-400">/</span></li>
                        <li class="text-gray-900 dark:text-white font-medium">Goals</li>
                    </ol>
                </nav>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🎯 Career Goals</h1>
                <p class="mt-1 text-gray-600 dark:text-gray-400">Track and manage your career objectives</p>
            </div>
            <button onclick="openCreateModal()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Goal
            </button>
        </div>

        <!-- Active Goals -->
        <div class="mb-8">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Active Goals ({{ $activeGoals->count() }})</h2>
            
            @if($activeGoals->isEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-8 text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                    <span class="text-3xl">🎯</span>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No active goals yet</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">Set your first career goal to start tracking progress</p>
                <button onclick="openCreateModal()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Create Your First Goal
                </button>
            </div>
            @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($activeGoals as $goal)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">
                                    {{ $goal->getCategoryLabel() }}
                                </span>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full 
                                    @if($goal->priority === 'critical') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                    @elseif($goal->priority === 'high') bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400
                                    @elseif($goal->priority === 'medium') bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                                    @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                                    @endif">
                                    {{ ucfirst($goal->priority) }}
                                </span>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $goal->title }}</h3>
                            @if($goal->description)
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ Str::limit($goal->description, 100) }}</p>
                            @endif
                        </div>
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-cloak
                                 class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-10">
                                <button onclick="editGoal({{ $goal->id }})" class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Edit Goal</button>
                                <button onclick="deleteGoal({{ $goal->id }})" class="w-full px-4 py-2 text-left text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">Delete Goal</button>
                            </div>
                        </div>
                    </div>

                    <!-- Progress -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between text-sm mb-2">
                            <span class="text-gray-500 dark:text-gray-400">Progress</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $goal->progress_percentage }}%</span>
                        </div>
                        <div class="relative">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div class="bg-gradient-to-r from-indigo-500 to-purple-500 h-3 rounded-full transition-all duration-500" 
                                     style="width: {{ $goal->progress_percentage }}%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Update Progress Slider -->
                    <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg" x-data="{ progress: {{ $goal->progress_percentage }} }">
                        <label class="text-sm text-gray-600 dark:text-gray-400 mb-2 block">Update Progress</label>
                        <div class="flex items-center gap-3">
                            <input type="range" min="0" max="100" x-model="progress" 
                                   class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700">
                            <span x-text="progress + '%'" class="text-sm font-medium text-gray-700 dark:text-gray-300 w-12"></span>
                            <button @click="updateGoalProgress({{ $goal->id }}, progress)" 
                                    class="px-3 py-1 text-xs bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                Save
                            </button>
                        </div>
                    </div>

                    <!-- Meta -->
                    <div class="flex items-center justify-between text-sm">
                        @if($goal->target_date)
                        <div class="flex items-center gap-1 
                            @if($goal->isOverdue()) text-red-600 dark:text-red-400
                            @elseif($goal->getDaysRemaining() <= 7) text-orange-600 dark:text-orange-400
                            @else text-gray-500 dark:text-gray-400
                            @endif">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>{{ $goal->target_date->format('M d, Y') }}</span>
                            @if($goal->isOverdue())
                            <span class="font-medium">(Overdue)</span>
                            @elseif($goal->getDaysRemaining() !== null)
                            <span>({{ $goal->getDaysRemaining() }} days)</span>
                            @endif
                        </div>
                        @else
                        <span class="text-gray-400 dark:text-gray-500">No deadline</span>
                        @endif
                        <span class="text-gray-400 dark:text-gray-500">
                            {{ \App\Models\CareerGoal::getTimeframeLabels()[$goal->timeframe] ?? $goal->timeframe }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <!-- Completed Goals -->
        @if($completedGoals->isNotEmpty())
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🎉 Completed Goals ({{ $completedGoals->count() }})</h2>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($completedGoals as $goal)
                <div class="p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900 dark:text-white">{{ $goal->title }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $goal->getCategoryLabel() }} • Completed {{ $goal->completed_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Create Goal Modal -->
<div id="createModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" onclick="if(event.target === this) closeCreateModal()">
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Create New Goal</h2>
        <form id="createGoalForm" onsubmit="submitGoal(event)">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Goal Title *</label>
                    <input type="text" name="title" required 
                           class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                           placeholder="e.g., Get promoted to Senior Developer">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                    <textarea name="description" rows="3"
                              class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                              placeholder="Describe your goal in detail..."></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category *</label>
                        <select name="category" required 
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
                            @foreach($categories as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Timeframe *</label>
                        <select name="timeframe" required 
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
                            @foreach($timeframes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target Date</label>
                        <input type="date" name="target_date" 
                               min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority *</label>
                        <select name="priority" required 
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
                            @foreach($priorities as $value => $label)
                            <option value="{{ $value }}" {{ $value === 'medium' ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" onclick="closeCreateModal()" 
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Create Goal
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
    document.getElementById('createModal').classList.add('flex');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
    document.getElementById('createModal').classList.remove('flex');
    document.getElementById('createGoalForm').reset();
}

async function submitGoal(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    try {
        const response = await fetch('{{ route("career-coach.goals.create") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify(data),
        });

        const result = await response.json();
        if (result.success) {
            location.reload();
        }
    } catch (error) {
        console.error('Failed to create goal:', error);
    }
}

async function updateGoalProgress(goalId, progress) {
    try {
        const response = await fetch(`/career-coach/goals/${goalId}/progress`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ progress: parseInt(progress) }),
        });

        const result = await response.json();
        if (result.success) {
            location.reload();
        }
    } catch (error) {
        console.error('Failed to update progress:', error);
    }
}

async function deleteGoal(goalId) {
    if (!confirm('Are you sure you want to delete this goal?')) return;

    try {
        await fetch(`/career-coach/goals/${goalId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
        });
        location.reload();
    } catch (error) {
        console.error('Failed to delete goal:', error);
    }
}

function editGoal(goalId) {
    // TODO: Implement edit modal
    alert('Edit functionality coming soon!');
}
</script>
@endpush
@endsection
