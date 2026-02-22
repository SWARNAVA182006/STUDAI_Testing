<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Auth\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:3,1'); // Max 3 registration attempts per minute (brute force protection)

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1'); // Max 5 login attempts per minute (brute force protection)

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:5,60') // Max 5 password reset requests per hour
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,60') // Max 5 password reset submissions per hour
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

// Two-Factor Authentication Routes
Route::middleware(['auth', 'password.confirm'])->group(function () {
    Route::get('/two-factor-authentication', [TwoFactorController::class, 'show'])
        ->name('two-factor.show');
    
    Route::post('/two-factor-authentication/enable', [TwoFactorController::class, 'enable'])
        ->name('two-factor.enable');
    
    Route::get('/two-factor-authentication/confirm', [TwoFactorController::class, 'confirm'])
        ->name('two-factor.confirm');
    
    Route::post('/two-factor-authentication/verify', [TwoFactorController::class, 'verify'])
        ->name('two-factor.verify');
    
    Route::delete('/two-factor-authentication', [TwoFactorController::class, 'disable'])
        ->name('two-factor.disable');
    
    Route::get('/two-factor-recovery-codes', [TwoFactorController::class, 'recoveryCodes'])
        ->name('two-factor.recovery-codes');
    
    Route::post('/two-factor-recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])
        ->name('two-factor.recovery-codes.regenerate');
});

// Social Authentication Routes
Route::middleware('guest')->group(function () {
    // Get available providers (API)
    Route::get('/auth/social/providers', [SocialAuthController::class, 'providers'])
        ->name('social.providers');
    
    // OAuth redirect
    Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])
        ->name('social.redirect')
        ->whereIn('provider', ['google', 'linkedin', 'apple', 'microsoft', 'facebook', 'github', 'twitter']);
    
    // OAuth callback
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->name('social.callback')
        ->whereIn('provider', ['google', 'linkedin', 'apple', 'microsoft', 'facebook', 'github', 'twitter']);
});

// Connected accounts management (authenticated users)
Route::middleware('auth')->group(function () {
    // View connected accounts
    Route::get('/profile/connections', [SocialAuthController::class, 'connections'])
        ->name('profile.connections');
    
    // Connect new social account
    Route::get('/profile/connect/{provider}', [SocialAuthController::class, 'connect'])
        ->name('social.connect')
        ->whereIn('provider', ['google', 'linkedin', 'apple', 'microsoft', 'facebook', 'github', 'twitter']);
    
    // Connect callback
    Route::get('/profile/connect/{provider}/callback', [SocialAuthController::class, 'connectCallback'])
        ->name('social.connect.callback')
        ->whereIn('provider', ['google', 'linkedin', 'apple', 'microsoft', 'facebook', 'github', 'twitter']);
    
    // Disconnect social account
    Route::delete('/profile/disconnect/{provider}', [SocialAuthController::class, 'disconnect'])
        ->name('social.disconnect')
        ->whereIn('provider', ['google', 'linkedin', 'apple', 'microsoft', 'facebook', 'github', 'twitter']);
});
