@extends('layouts.dashboard')

@section('title', 'Profile Settings')

@section('page-title', 'Profile Settings')

@section('content')
<div class="max-w-5xl mx-auto" x-data="{ activeTab: 'profile' }">
    <!-- Profile Header Card -->
    <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 mb-6">
        <div class="flex items-center gap-6">
            <!-- Avatar -->
            <div class="relative">
                <div class="w-24 h-24 rounded-full bg-gradient-to-br from-studai-blue-400 to-studai-blue-600 flex items-center justify-center text-white text-3xl font-semibold">
                    {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                </div>
                <button class="absolute bottom-0 right-0 w-8 h-8 bg-white rounded-full shadow-md border border-gray-200 flex items-center justify-center hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </button>
            </div>
            
            <!-- User Info -->
            <div class="flex-1">
                <h1 class="text-2xl font-semibold text-gray-900">{{ auth()->user()->name ?? 'User' }}</h1>
                <p class="text-gray-500">{{ auth()->user()->email ?? 'email@example.com' }}</p>
                <div class="flex items-center gap-4 mt-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-green-50 text-green-700">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                        Profile Complete
                    </span>
                    <span class="text-sm text-gray-500">Member since {{ auth()->user()->created_at?->format('M Y') ?? 'N/A' }}</span>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="flex items-center gap-3">
                <a href="{{ route('features') }}#resume-studio" class="btn-secondary text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    View Resume
                </a>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="bg-white rounded-2xl shadow-xs border border-gray-100 mb-6">
        <div class="flex border-b border-gray-100">
            <button @click="activeTab = 'profile'" 
                    :class="activeTab === 'profile' ? 'border-studai-blue-500 text-studai-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="flex items-center gap-2 px-6 py-4 text-sm font-medium border-b-2 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Profile Information
            </button>
            <button @click="activeTab = 'security'" 
                    :class="activeTab === 'security' ? 'border-studai-blue-500 text-studai-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="flex items-center gap-2 px-6 py-4 text-sm font-medium border-b-2 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Security
            </button>
            <button @click="activeTab = 'notifications'" 
                    :class="activeTab === 'notifications' ? 'border-studai-blue-500 text-studai-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="flex items-center gap-2 px-6 py-4 text-sm font-medium border-b-2 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                Notifications
            </button>
            <button @click="activeTab = 'preferences'" 
                    :class="activeTab === 'preferences' ? 'border-studai-blue-500 text-studai-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="flex items-center gap-2 px-6 py-4 text-sm font-medium border-b-2 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Preferences
            </button>
            <button @click="activeTab = 'danger'" 
                    :class="activeTab === 'danger' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="flex items-center gap-2 px-6 py-4 text-sm font-medium border-b-2 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Danger Zone
            </button>
        </div>
    </div>

    <!-- Profile Information Tab -->
    <div x-show="activeTab === 'profile'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900">Profile Information</h2>
                <p class="text-sm text-gray-500 mt-1">Update your account's profile information and email address.</p>
            </div>

            <form method="post" action="{{ route('profile.update') }}" class="space-y-6">
                @csrf
                @method('patch')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name ?? '') }}" 
                               class="input-google w-full" required autofocus autocomplete="name">
                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" 
                               class="input-google w-full" required autocomplete="username">
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone ?? '') }}" 
                               class="input-google w-full" autocomplete="tel">
                    </div>

                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                        <input type="text" id="location" name="location" value="{{ old('location', $user->location ?? '') }}" 
                               class="input-google w-full" placeholder="City, Country">
                    </div>
                </div>

                <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">Professional Summary</label>
                    <textarea id="bio" name="bio" rows="4" class="input-google w-full" 
                              placeholder="Tell employers about your professional background...">{{ old('bio', $user->bio ?? '') }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-100">
                    @if (session('status') === 'profile-updated')
                        <p class="text-sm text-green-600" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)">
                            <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Saved successfully!
                        </p>
                    @endif
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Security Tab -->
    <div x-show="activeTab === 'security'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 mb-6">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900">Change Password</h2>
                <p class="text-sm text-gray-500 mt-1">Ensure your account uses a strong, unique password.</p>
            </div>

            <form method="post" action="{{ route('password.update') }}" class="space-y-6">
                @csrf
                @method('put')

                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="input-google w-full" autocomplete="current-password">
                    @error('current_password', 'updatePassword')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                        <input type="password" id="password" name="password" class="input-google w-full" autocomplete="new-password">
                        @error('password', 'updatePassword')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="input-google w-full" autocomplete="new-password">
                    </div>
                </div>

                <div class="flex items-center justify-end pt-4 border-t border-gray-100">
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Update Password
                    </button>
                </div>
            </form>
        </div>

        <!-- Two-Factor Authentication -->
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Two-Factor Authentication</h2>
                    <p class="text-sm text-gray-500 mt-1">Add an extra layer of security to your account.</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600">
                    <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                    Not Enabled
                </span>
            </div>

            <button class="btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Enable 2FA
            </button>
        </div>
    </div>

    <!-- Notifications Tab -->
    <div x-show="activeTab === 'notifications'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900">Notification Preferences</h2>
                <p class="text-sm text-gray-500 mt-1">Choose how you want to be notified.</p>
            </div>

            <div class="space-y-6">
                <!-- Email Notifications -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-4">Email Notifications</h3>
                    <div class="space-y-4">
                        <label class="flex items-center justify-between p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                            <div>
                                <p class="font-medium text-gray-900">Job Recommendations</p>
                                <p class="text-sm text-gray-500">Get personalized job matches based on your profile</p>
                            </div>
                            <input type="checkbox" checked class="w-5 h-5 text-studai-blue-600 rounded focus:ring-studai-blue-500">
                        </label>
                        <label class="flex items-center justify-between p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                            <div>
                                <p class="font-medium text-gray-900">Application Updates</p>
                                <p class="text-sm text-gray-500">Status changes on your job applications</p>
                            </div>
                            <input type="checkbox" checked class="w-5 h-5 text-studai-blue-600 rounded focus:ring-studai-blue-500">
                        </label>
                        <label class="flex items-center justify-between p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                            <div>
                                <p class="font-medium text-gray-900">Interview Reminders</p>
                                <p class="text-sm text-gray-500">Reminders before scheduled interviews</p>
                            </div>
                            <input type="checkbox" checked class="w-5 h-5 text-studai-blue-600 rounded focus:ring-studai-blue-500">
                        </label>
                        <label class="flex items-center justify-between p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                            <div>
                                <p class="font-medium text-gray-900">Weekly Digest</p>
                                <p class="text-sm text-gray-500">Summary of your job search activity</p>
                            </div>
                            <input type="checkbox" class="w-5 h-5 text-studai-blue-600 rounded focus:ring-studai-blue-500">
                        </label>
                    </div>
                </div>

                <!-- Push Notifications -->
                <div class="pt-6 border-t border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-4">Push Notifications</h3>
                    <div class="space-y-4">
                        <label class="flex items-center justify-between p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                            <div>
                                <p class="font-medium text-gray-900">Real-time Alerts</p>
                                <p class="text-sm text-gray-500">Instant notifications for important updates</p>
                            </div>
                            <input type="checkbox" checked class="w-5 h-5 text-studai-blue-600 rounded focus:ring-studai-blue-500">
                        </label>
                        <label class="flex items-center justify-between p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                            <div>
                                <p class="font-medium text-gray-900">Messages</p>
                                <p class="text-sm text-gray-500">When employers send you a message</p>
                            </div>
                            <input type="checkbox" checked class="w-5 h-5 text-studai-blue-600 rounded focus:ring-studai-blue-500">
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-6 mt-6 border-t border-gray-100">
                <button class="btn-primary">
                    Save Preferences
                </button>
            </div>
        </div>
    </div>

    <!-- Preferences Tab -->
    <div x-show="activeTab === 'preferences'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 mb-6">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900">Job Preferences</h2>
                <p class="text-sm text-gray-500 mt-1">Set your job search preferences for better recommendations.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Desired Job Title</label>
                    <input type="text" class="input-google w-full" placeholder="e.g., Senior Software Engineer">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Expected Salary</label>
                    <input type="text" class="input-google w-full" placeholder="e.g., $120,000 - $150,000">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Location</label>
                    <input type="text" class="input-google w-full" placeholder="e.g., San Francisco, CA">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Work Type</label>
                    <select class="input-google w-full">
                        <option>Remote</option>
                        <option>Hybrid</option>
                        <option>On-site</option>
                        <option>Any</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end pt-6 mt-6 border-t border-gray-100">
                <button class="btn-primary">Save Preferences</button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900">Privacy Settings</h2>
                <p class="text-sm text-gray-500 mt-1">Control who can see your profile and information.</p>
            </div>

            <div class="space-y-4">
                <label class="flex items-center justify-between p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                    <div>
                        <p class="font-medium text-gray-900">Profile Visibility</p>
                        <p class="text-sm text-gray-500">Allow employers to find your profile in search</p>
                    </div>
                    <input type="checkbox" checked class="w-5 h-5 text-studai-blue-600 rounded focus:ring-studai-blue-500">
                </label>
                <label class="flex items-center justify-between p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                    <div>
                        <p class="font-medium text-gray-900">Show Salary Expectations</p>
                        <p class="text-sm text-gray-500">Display your salary expectations to employers</p>
                    </div>
                    <input type="checkbox" class="w-5 h-5 text-studai-blue-600 rounded focus:ring-studai-blue-500">
                </label>
                <label class="flex items-center justify-between p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                    <div>
                        <p class="font-medium text-gray-900">Open to Opportunities</p>
                        <p class="text-sm text-gray-500">Let recruiters know you're actively looking</p>
                    </div>
                    <input type="checkbox" checked class="w-5 h-5 text-studai-blue-600 rounded focus:ring-studai-blue-500">
                </label>
            </div>
        </div>
    </div>

    <!-- Danger Zone Tab -->
    <div x-show="activeTab === 'danger'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div class="bg-white rounded-2xl shadow-xs border border-red-200 p-6">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-red-600">Danger Zone</h2>
                <p class="text-sm text-gray-500 mt-1">Irreversible and destructive actions.</p>
            </div>

            <div class="space-y-6">
                <!-- Export Data -->
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-xl">
                    <div>
                        <p class="font-medium text-gray-900">Export Your Data</p>
                        <p class="text-sm text-gray-500">Download a copy of all your data</p>
                    </div>
                    <button class="btn-secondary text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Export
                    </button>
                </div>

                <!-- Deactivate Account -->
                <div class="flex items-center justify-between p-4 border border-yellow-200 rounded-xl bg-yellow-50">
                    <div>
                        <p class="font-medium text-gray-900">Deactivate Account</p>
                        <p class="text-sm text-gray-500">Temporarily disable your account</p>
                    </div>
                    <button class="px-4 py-2 bg-yellow-100 text-yellow-700 rounded-lg text-sm font-medium hover:bg-yellow-200 transition-colors">
                        Deactivate
                    </button>
                </div>

                <!-- Delete Account -->
                <div class="flex items-center justify-between p-4 border border-red-200 rounded-xl bg-red-50">
                    <div>
                        <p class="font-medium text-red-600">Delete Account</p>
                        <p class="text-sm text-gray-500">Permanently delete your account and all data</p>
                    </div>
                    <button class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors"
                            onclick="confirmDelete()">
                        Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
        // Handle account deletion
        window.location.href = '{{ route("profile.destroy") ?? "#" }}';
    }
}
</script>
@endsection
