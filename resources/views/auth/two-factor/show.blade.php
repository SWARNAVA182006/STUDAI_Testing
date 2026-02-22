<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Two-Factor Authentication') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('status'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded-lg">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (auth()->user()->two_factor_secret)
                        <!-- 2FA is enabled -->
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">Two-Factor Authentication Enabled</h3>
                                    <p class="mt-1 text-sm text-gray-600">
                                        Your account is protected with two-factor authentication.
                                    </p>
                                </div>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    Active
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Recovery Codes -->
                                <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                                    <h4 class="text-sm font-medium text-gray-900 mb-2">Recovery Codes</h4>
                                    <p class="text-sm text-gray-600 mb-4">
                                        Store these codes in a safe place. They can be used to access your account if you lose your authenticator device.
                                    </p>
                                    <a href="{{ route('two-factor.recovery-codes') }}" class="text-sm font-medium text-pink-600 hover:text-pink-500">
                                        View Recovery Codes →
                                    </a>
                                </div>

                                <!-- Disable 2FA -->
                                <div class="bg-red-50 p-6 rounded-lg border border-red-200">
                                    <h4 class="text-sm font-medium text-gray-900 mb-2">Disable Two-Factor Authentication</h4>
                                    <p class="text-sm text-gray-600 mb-4">
                                        This will reduce your account security. You'll only need your password to log in.
                                    </p>
                                    <form method="POST" action="{{ route('two-factor.disable') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500" onclick="return confirm('Are you sure you want to disable two-factor authentication?')">
                                            Disable 2FA →
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- 2FA is disabled -->
                        <div class="text-center py-8">
                            <div class="mx-auto w-16 h-16 bg-pink-100 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">
                                Enable Two-Factor Authentication
                            </h3>
                            <p class="text-sm text-gray-600 mb-6 max-w-md mx-auto">
                                Add an extra layer of security to your account. In addition to your password, you'll need to enter a code from your authenticator app.
                            </p>
                            
                            <form method="POST" action="{{ route('two-factor.enable') }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-pink-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-pink-700 active:bg-pink-900 focus:outline-none focus:border-pink-900 focus:ring ring-pink-300 disabled:opacity-25 transition ease-in-out duration-150">
                                    Enable Two-Factor Authentication
                                </button>
                            </form>

                            <div class="mt-8 text-left max-w-2xl mx-auto">
                                <h4 class="text-sm font-medium text-gray-900 mb-3">How it works:</h4>
                                <ol class="space-y-3">
                                    <li class="flex">
                                        <span class="flex-shrink-0 w-6 h-6 bg-pink-100 text-pink-600 rounded-full flex items-center justify-center text-xs font-medium mr-3">1</span>
                                        <span class="text-sm text-gray-600">Download an authenticator app (Google Authenticator, Authy, 1Password, etc.)</span>
                                    </li>
                                    <li class="flex">
                                        <span class="flex-shrink-0 w-6 h-6 bg-pink-100 text-pink-600 rounded-full flex items-center justify-center text-xs font-medium mr-3">2</span>
                                        <span class="text-sm text-gray-600">Scan the QR code we provide with your authenticator app</span>
                                    </li>
                                    <li class="flex">
                                        <span class="flex-shrink-0 w-6 h-6 bg-pink-100 text-pink-600 rounded-full flex items-center justify-center text-xs font-medium mr-3">3</span>
                                        <span class="text-sm text-gray-600">Enter the verification code to confirm setup</span>
                                    </li>
                                    <li class="flex">
                                        <span class="flex-shrink-0 w-6 h-6 bg-pink-100 text-pink-600 rounded-full flex items-center justify-center text-xs font-medium mr-3">4</span>
                                        <span class="text-sm text-gray-600">Use the code from your app each time you log in</span>
                                    </li>
                                </ol>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
