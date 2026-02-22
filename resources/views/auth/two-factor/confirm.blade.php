<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Confirm Two-Factor Authentication') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="text-center mb-8">
                        <div class="mx-auto w-16 h-16 bg-pink-100 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            Scan this QR code
                        </h3>
                        <p class="text-sm text-gray-600">
                            Open your authenticator app and scan this QR code
                        </p>
                    </div>

                    <!-- QR Code -->
                    <div class="flex justify-center mb-6">
                        <div class="p-4 bg-white border-4 border-gray-200 rounded-lg">
                            {!! $qrCodeSvg !!}
                        </div>
                    </div>

                    <!-- Manual Entry -->
                    <div class="mb-8">
                        <details class="group">
                            <summary class="flex items-center justify-center cursor-pointer text-sm text-gray-600 hover:text-gray-900">
                                <span>Can't scan the QR code?</span>
                                <svg class="ml-2 h-5 w-5 transform group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </summary>
                            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600 mb-2">Enter this code manually in your authenticator app:</p>
                                <div class="flex items-center justify-between p-3 bg-white border border-gray-300 rounded-md">
                                    <code class="text-sm font-mono text-gray-900">{{ $secret }}</code>
                                    <button type="button" onclick="copyToClipboard('{{ $secret }}')" class="text-pink-600 hover:text-pink-700 text-sm font-medium">
                                        Copy
                                    </button>
                                </div>
                            </div>
                        </details>
                    </div>

                    <!-- Verification Form -->
                    <form method="POST" action="{{ route('two-factor.verify') }}">
                        @csrf

                        <div class="mb-6">
                            <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                                Enter the 6-digit code from your authenticator app
                            </label>
                            <input 
                                type="text" 
                                id="code" 
                                name="code" 
                                class="block w-full px-4 py-3 text-center text-2xl tracking-widest border-gray-300 rounded-lg focus:ring-pink-500 focus:border-pink-500" 
                                maxlength="6" 
                                pattern="[0-9]{6}" 
                                required 
                                autofocus
                                placeholder="000000"
                            />
                            @error('code')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between">
                            <a href="{{ route('two-factor.show') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                ← Back
                            </a>
                            <button type="submit" class="inline-flex items-center px-6 py-3 bg-pink-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-wider hover:bg-pink-700 active:bg-pink-900 focus:outline-none focus:border-pink-900 focus:ring ring-pink-300 disabled:opacity-25 transition ease-in-out duration-150">
                                Verify & Enable
                            </button>
                        </div>
                    </form>

                    <!-- Tips -->
                    <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">Tips</h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Make sure your phone's clock is set correctly</li>
                                        <li>The code refreshes every 30 seconds</li>
                                        <li>You'll receive recovery codes after verification - save them safely</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Copied to clipboard!');
            });
        }

        // Auto-format code input
        document.getElementById('code').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Auto-submit when 6 digits entered
        document.getElementById('code').addEventListener('input', function(e) {
            if (this.value.length === 6) {
                // Optional: auto-submit
                // this.form.submit();
            }
        });
    </script>
    @endpush
</x-app-layout>
