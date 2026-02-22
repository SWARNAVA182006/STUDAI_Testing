<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Create your account</h2>
        <p class="mt-2 text-sm text-gray-600">Join thousands of professionals finding their dream jobs</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-6">
        @csrf

        <!-- Account Type Selection -->
        <div>
            <x-input-label for="account_type" value="I am a..." />
            <div class="mt-2 grid grid-cols-2 gap-3">
                <label class="relative flex cursor-pointer rounded-lg border border-gray-300 bg-white p-4 shadow-sm focus:outline-none hover:border-pink-500 transition">
                    <input type="radio" name="account_type" value="job_seeker" class="sr-only" checked />
                    <span class="flex flex-1">
                        <span class="flex flex-col">
                            <span class="block text-sm font-medium text-gray-900">Job Seeker</span>
                            <span class="mt-1 flex items-center text-xs text-gray-500">Find opportunities</span>
                        </span>
                    </span>
                    <svg class="h-5 w-5 text-pink-600 hidden check-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </label>
                
                <label class="relative flex cursor-pointer rounded-lg border border-gray-300 bg-white p-4 shadow-sm focus:outline-none hover:border-pink-500 transition">
                    <input type="radio" name="account_type" value="employer" class="sr-only" />
                    <span class="flex flex-1">
                        <span class="flex flex-col">
                            <span class="block text-sm font-medium text-gray-900">Employer</span>
                            <span class="mt-1 flex items-center text-xs text-gray-500">Post jobs & hire</span>
                        </span>
                    </span>
                    <svg class="h-5 w-5 text-pink-600 hidden check-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </label>
            </div>
            <x-input-error :messages="$errors->get('account_type')" class="mt-2" />
        </div>

        <!-- Name -->
        <div>
            <x-input-label for="name" value="Full Name" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="John Doe" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email Address" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="you@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Phone (Optional) -->
        <div>
            <x-input-label for="phone" value="Phone Number (Optional)" />
            <x-text-input id="phone" class="block mt-1 w-full" type="tel" name="phone" :value="old('phone')" autocomplete="tel" placeholder="+91 98765 43210" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <p class="mt-1 text-xs text-gray-500">At least 8 characters with uppercase, lowercase, and numbers</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" value="Confirm Password" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Terms & Conditions -->
        <div class="flex items-start">
            <div class="flex items-center h-5">
                <input id="terms" name="terms" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-pink-600 focus:ring-pink-500" required />
            </div>
            <div class="ml-3 text-sm">
                <label for="terms" class="text-gray-600">
                    I agree to the <a href="/terms" class="text-pink-600 hover:text-pink-500">Terms of Service</a> and <a href="/privacy" class="text-pink-600 hover:text-pink-500">Privacy Policy</a>
                </label>
            </div>
        </div>
        <x-input-error :messages="$errors->get('terms')" class="mt-2" />

        <div>
            <x-primary-button class="w-full justify-center">
                Create Account
            </x-primary-button>
        </div>

        <div class="text-center text-sm">
            <span class="text-gray-600">Already have an account?</span>
            <a href="{{ route('login') }}" class="font-medium text-pink-600 hover:text-pink-500 ml-1">
                Sign in
            </a>
        </div>
    </form>

    <!-- Social Login Buttons -->
    <x-social-login-buttons />

    <script>
        // Account type selection visual feedback
        document.querySelectorAll('input[name="account_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('input[name="account_type"]').forEach(r => {
                    const label = r.closest('label');
                    const icon = label.querySelector('.check-icon');
                    if (r.checked) {
                        label.classList.add('border-pink-500', 'ring-2', 'ring-pink-500');
                        icon.classList.remove('hidden');
                    } else {
                        label.classList.remove('border-pink-500', 'ring-2', 'ring-pink-500');
                        icon.classList.add('hidden');
                    }
                });
            });
        });
        
        // Trigger initial state
        document.querySelector('input[name="account_type"]:checked')?.dispatchEvent(new Event('change'));
    </script>
</x-guest-layout>
