@extends('layouts.app')

@section('title', 'Preferences - AI Career Coach')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <nav class="flex mb-2" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1">
                    <li><a href="{{ route('career-coach.index') }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">Career Coach</a></li>
                    <li><span class="mx-2 text-gray-400">/</span></li>
                    <li class="text-gray-900 dark:text-white font-medium">Preferences</li>
                </ol>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">⚙️ Coach Preferences</h1>
            <p class="mt-1 text-gray-600 dark:text-gray-400">Customize your AI coaching experience</p>
        </div>

        <form id="preferencesForm" class="space-y-6">
            <!-- Coaching Style -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Coaching Style</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Choose how you want your AI coach to communicate with you</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($styles as $value => $label)
                    <label class="flex items-start gap-3 p-4 rounded-lg border-2 cursor-pointer transition-all
                        {{ $preferences->coaching_style === $value ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}">
                        <input type="radio" name="coaching_style" value="{{ $value }}" 
                               {{ $preferences->coaching_style === $value ? 'checked' : '' }}
                               class="mt-1 text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $label }}</span>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                @switch($value)
                                    @case('supportive')
                                        Warm, encouraging, celebrates your wins
                                        @break
                                    @case('direct')
                                        Straight to the point, action-focused
                                        @break
                                    @case('analytical')
                                        Data-driven, logical approach
                                        @break
                                    @case('motivational')
                                        Inspiring, pushes you forward
                                        @break
                                @endswitch
                            </p>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            <!-- Weekly Check-ins -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Weekly Check-ins</h2>
                
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">Enable Weekly Check-ins</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Get reminded to review your progress weekly</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="weekly_checkins_enabled" value="1" 
                               {{ $preferences->weekly_checkins_enabled ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Preferred Day</label>
                        <select name="preferred_checkin_day" 
                                class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
                            @foreach($days as $value => $label)
                            <option value="{{ $value }}" {{ $preferences->preferred_checkin_day === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Preferred Time</label>
                        <input type="time" name="preferred_checkin_time" 
                               value="{{ $preferences->preferred_checkin_time }}"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
                    </div>
                </div>
            </div>

            <!-- Proactive Suggestions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Proactive Suggestions</h2>
                
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">Enable Suggestions</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Receive AI-generated tips and reminders</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="proactive_suggestions_enabled" value="1" 
                               {{ $preferences->proactive_suggestions_enabled ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Suggestion Frequency</label>
                    <select name="suggestion_frequency" 
                            class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
                        @foreach($frequencies as $value => $label)
                        <option value="{{ $value }}" {{ $preferences->suggestion_frequency === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Notifications -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Notifications</h2>
                
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Email Notifications</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Receive coaching updates via email</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="email_notifications" value="1" 
                                   {{ $preferences->email_notifications ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Push Notifications</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Receive browser push notifications</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="push_notifications" value="1" 
                                   {{ $preferences->push_notifications ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Voice Features -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Voice Features</h2>
                
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">Enable Voice Interaction</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Talk to your coach using voice input</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="voice_enabled" value="1" 
                               {{ $preferences->voice_enabled ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                    </label>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end">
                <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                    Save Preferences
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('preferencesForm').addEventListener('submit', async function(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const data = {};
    
    // Handle checkboxes properly
    data.weekly_checkins_enabled = formData.has('weekly_checkins_enabled');
    data.proactive_suggestions_enabled = formData.has('proactive_suggestions_enabled');
    data.email_notifications = formData.has('email_notifications');
    data.push_notifications = formData.has('push_notifications');
    data.voice_enabled = formData.has('voice_enabled');
    
    // Handle other fields
    data.coaching_style = formData.get('coaching_style');
    data.preferred_checkin_day = formData.get('preferred_checkin_day');
    data.preferred_checkin_time = formData.get('preferred_checkin_time');
    data.suggestion_frequency = formData.get('suggestion_frequency');

    try {
        const response = await fetch('{{ route("career-coach.preferences.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify(data),
        });

        const result = await response.json();
        if (result.success) {
            alert('Preferences saved successfully!');
        }
    } catch (error) {
        console.error('Failed to save preferences:', error);
        alert('Failed to save preferences. Please try again.');
    }
});
</script>
@endpush
@endsection
