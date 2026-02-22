<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // Custom login view
        Fortify::loginView(function () {
            return view('auth.login');
        });

        // Custom registration view
        Fortify::registerView(function () {
            return view('auth.register');
        });

        // Custom password reset views
        Fortify::requestPasswordResetLinkView(function () {
            return view('auth.forgot-password');
        });

        // Custom email verification view
        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });

        Fortify::resetPasswordView(function (Request $request) {
            return view('auth.reset-password', ['request' => $request]);
        });

        // Two factor authentication views
        Fortify::twoFactorChallengeView(function () {
            return view('auth.two-factor-challenge');
        });

        Fortify::confirmPasswordView(function () {
            return view('auth.confirm-password');
        });

        // Custom authentication logic with account type check
        Fortify::authenticateUsing(function (Request $request) {
            $user = \App\Models\User::where('email', $request->email)
                ->where('is_active', true)
                ->first();

            if ($user && \Hash::check($request->password, $user->password)) {
                return $user;
            }
        });

        // Redirect after login based on account type
        Fortify::redirects('login', function () {
            $user = auth()->user();
            
            if ($user->isAdmin()) {
                return route('admin.dashboard');
            } elseif ($user->isEmployer()) {
                return route('employer.dashboard');
            } else {
                return route('dashboard');
            }
        });

        // Redirect after registration
        Fortify::redirects('register', function () {
            return route('profile.complete');
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
