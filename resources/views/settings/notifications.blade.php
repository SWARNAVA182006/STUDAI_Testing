@extends('layouts.dashboard')

@section('title', 'Notification Settings')

@section('page-title', 'Notification Settings')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Push Notification Status Card -->
    <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 mb-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-studai-blue-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-studai-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Push Notifications</h2>
                    <p class="text-sm text-gray-500">Get instant updates on your device</p>
                </div>
            </div>
            <div id="push-status" class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-100">
                <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                <span class="text-sm font-medium text-gray-600">Not enabled</span>
            </div>
        </div>

        <button id="enable-push-btn" class="btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            Enable Push Notifications
        </button>

        <div id="push-subscriptions" class="mt-6 hidden">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Active Devices</h3>
            <div id="subscriptions-list" class="space-y-2">
                <!-- Populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Notification Preferences -->
    <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 mb-6">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-900">Notification Preferences</h2>
            <p class="text-sm text-gray-500 mt-1">Choose what you want to be notified about</p>
        </div>

        <form id="notification-preferences-form" class="space-y-8">
            @csrf
            
            <!-- Job Alerts -->
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-studai-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Job Alerts</h3>
                </div>
                <div class="space-y-3">
                    <label class="flex items-center justify-between p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors group">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-white shadow-xs flex items-center justify-center group-hover:shadow-sm transition-shadow">
                                <svg class="w-5 h-5 text-studai-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">New Job Matches</p>
                                <p class="text-sm text-gray-500">When jobs match your profile preferences</p>
                            </div>
                        </div>
                        <div class="relative">
                            <input type="checkbox" name="job_alert_push" checked class="toggle-switch sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-studai-blue-600"></div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Application Updates -->
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Application Updates</h3>
                </div>
                <div class="space-y-3">
                    <label class="flex items-center justify-between p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors group">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-white shadow-xs flex items-center justify-center group-hover:shadow-sm transition-shadow">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Status Changes</p>
                                <p class="text-sm text-gray-500">When your application status updates</p>
                            </div>
                        </div>
                        <div class="relative">
                            <input type="checkbox" name="application_status_push" checked class="toggle-switch sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-studai-blue-600"></div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Interviews -->
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Interviews</h3>
                </div>
                <div class="space-y-3">
                    <label class="flex items-center justify-between p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors group">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-white shadow-xs flex items-center justify-center group-hover:shadow-sm transition-shadow">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Interview Reminders</p>
                                <p class="text-sm text-gray-500">24 hours before scheduled interviews</p>
                            </div>
                        </div>
                        <div class="relative">
                            <input type="checkbox" name="interview_reminder_push" checked class="toggle-switch sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-studai-blue-600"></div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Messages -->
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Messages</h3>
                </div>
                <div class="space-y-3">
                    <label class="flex items-center justify-between p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors group">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-white shadow-xs flex items-center justify-center group-hover:shadow-sm transition-shadow">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">New Messages</p>
                                <p class="text-sm text-gray-500">When you receive new messages from employers</p>
                            </div>
                        </div>
                        <div class="relative">
                            <input type="checkbox" name="message_received_push" checked class="toggle-switch sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-studai-blue-600"></div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Profile Activity -->
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Profile Activity</h3>
                </div>
                <div class="space-y-3">
                    <label class="flex items-center justify-between p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors group">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-white shadow-xs flex items-center justify-center group-hover:shadow-sm transition-shadow">
                                <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Profile Views</p>
                                <p class="text-sm text-gray-500">When employers view your profile</p>
                            </div>
                        </div>
                        <div class="relative">
                            <input type="checkbox" name="profile_view_push" class="toggle-switch sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-studai-blue-600"></div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end pt-6 border-t border-gray-100">
                <button type="submit" class="btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Preferences
                </button>
            </div>
        </form>
    </div>

    <!-- Test Notification Card -->
    <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Test Notifications</h2>
                <p class="text-sm text-gray-500">Send a test notification to verify everything is working</p>
            </div>
        </div>

        <button id="test-notification-btn" class="btn-secondary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
            </svg>
            Send Test Notification
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check push notification status
    checkPushStatus();

    // Enable push notifications
    document.getElementById('enable-push-btn').addEventListener('click', async function() {
        if (!('Notification' in window)) {
            alert('Your browser does not support notifications');
            return;
        }

        const permission = await Notification.requestPermission();
        
        if (permission === 'granted') {
            await window.pwaManager.subscribeToPush();
            checkPushStatus();
            alert('Push notifications enabled!');
        } else {
            alert('Permission denied for notifications');
        }
    });

    // Test notification
    document.getElementById('test-notification-btn').addEventListener('click', async function() {
        try {
            const response = await fetch('/api/push/test', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            if (response.ok) {
                alert('Test notification sent! Check your notifications.');
            } else {
                alert('Failed to send test notification');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error sending test notification');
        }
    });

    // Save preferences
    document.getElementById('notification-preferences-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const preferences = {};

        for (let [key, value] of formData.entries()) {
            if (key !== '_token') {
                preferences[key] = value === 'on';
            }
        }

        // Save each preference
        for (let [key, enabled] of Object.entries(preferences)) {
            const [type, channel] = key.split('_').slice(0, -1).join('_').split('_');
            
            await fetch('/api/push/preferences', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    channel: 'push',
                    notification_type: type,
                    enabled: enabled,
                }),
            });
        }

        alert('Preferences saved successfully!');
    });

    // Check push notification status
    async function checkPushStatus() {
        if (!('Notification' in window) || Notification.permission !== 'granted') {
            return;
        }

        try {
            const response = await fetch('/api/push/subscriptions');
            const data = await response.json();

            if (data.subscriptions && data.subscriptions.length > 0) {
                document.getElementById('push-status').innerHTML = `
                    <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                    <span class="text-sm text-green-600">Enabled (${data.subscriptions.length} device${data.subscriptions.length > 1 ? 's' : ''})</span>
                `;

                document.getElementById('enable-push-btn').style.display = 'none';
                document.getElementById('push-subscriptions').classList.remove('hidden');

                const list = document.getElementById('subscriptions-list');
                list.innerHTML = data.subscriptions.map(sub => `
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                        <div>
                            <p class="text-sm font-medium">${sub.device_type}</p>
                            <p class="text-xs text-gray-500">Last used: ${sub.last_used_at}</p>
                        </div>
                        <button class="text-red-600 text-sm" onclick="removeSubscription(${sub.id})">Remove</button>
                    </div>
                `).join('');
            }
        } catch (error) {
            console.error('Error checking push status:', error);
        }
    }
});

async function removeSubscription(id) {
    if (!confirm('Remove this device from notifications?')) {
        return;
    }

    try {
        const response = await fetch(`/api/push/subscriptions/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        if (response.ok) {
            window.location.reload();
        }
    } catch (error) {
        console.error('Error removing subscription:', error);
        alert('Failed to remove subscription');
    }
}
</script>
@endsection
