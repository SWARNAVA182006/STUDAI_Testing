<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Recovery Codes') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('status'))
                        <div class="mb-6 p-4 bg-green-100 border border-green-200 text-green-700 rounded-lg">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="text-center mb-8">
                        <div class="mx-auto w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            Two-Factor Recovery Codes
                        </h3>
                        <p class="text-sm text-gray-600 max-w-md mx-auto">
                            Store these codes in a safe place. Each code can only be used once.
                        </p>
                    </div>

                    <!-- Warning -->
                    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">Important</h3>
                                <div class="mt-1 text-sm text-yellow-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Save these codes now - you won't see them again</li>
                                        <li>Each code can only be used once</li>
                                        <li>Use them if you lose access to your authenticator app</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recovery Codes -->
                    <div class="mb-6 p-6 bg-gray-50 border border-gray-200 rounded-lg">
                        <div class="grid grid-cols-2 gap-3">
                            @foreach ($recoveryCodes as $code)
                                <div class="flex items-center justify-center p-3 bg-white border border-gray-300 rounded-md">
                                    <code class="text-sm font-mono text-gray-900">{{ $code }}</code>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between space-x-4">
                        <button type="button" onclick="downloadCodes()" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download Codes
                        </button>

                        <button type="button" onclick="printCodes()" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            Print Codes
                        </button>

                        <form method="POST" action="{{ route('two-factor.recovery-codes.regenerate') }}" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-pink-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-pink-700 active:bg-pink-900 focus:outline-none focus:border-pink-900 focus:ring ring-pink-300 disabled:opacity-25 transition ease-in-out duration-150" onclick="return confirm('This will invalidate your old recovery codes. Continue?')">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Regenerate Codes
                            </button>
                        </form>
                    </div>

                    <div class="mt-6 text-center">
                        <a href="{{ route('two-factor.show') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            ← Back to Two-Factor Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const recoveryCodes = @json($recoveryCodes);

        function downloadCodes() {
            const content = 'StudAI Career - Two-Factor Recovery Codes\n\n' +
                           'Store these codes in a safe place.\n' +
                           'Each code can only be used once.\n\n' +
                           recoveryCodes.join('\n') +
                           '\n\nGenerated: ' + new Date().toLocaleString();
            
            const blob = new Blob([content], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'studai-recovery-codes-' + Date.now() + '.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        function printCodes() {
            const printWindow = window.open('', '', 'width=600,height=400');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Recovery Codes</title>
                        <style>
                            body { font-family: monospace; padding: 40px; }
                            h1 { font-size: 24px; margin-bottom: 20px; }
                            .codes { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
                            .code { padding: 10px; border: 1px solid #ccc; }
                        </style>
                    </head>
                    <body>
                        <h1>StudAI Career - Recovery Codes</h1>
                        <p>Store these codes in a safe place. Each code can only be used once.</p>
                        <div class="codes">
                            ${recoveryCodes.map(code => `<div class="code">${code}</div>`).join('')}
                        </div>
                        <p style="margin-top: 20px;">Generated: ${new Date().toLocaleString()}</p>
                    </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
    </script>
    @endpush
</x-app-layout>
