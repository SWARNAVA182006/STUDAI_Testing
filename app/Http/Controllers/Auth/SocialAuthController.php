<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SocialAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SocialAuthController extends Controller
{
    public function __construct(
        protected SocialAuthService $socialAuthService
    ) {}

    /**
     * Show available social login options.
     */
    public function providers()
    {
        $providers = $this->socialAuthService->getEnabledProviders();

        return response()->json([
            'providers' => $providers->map(fn($p) => [
                'name' => $p->name,
                'slug' => $p->slug,
                'icon' => $p->icon,
                'color' => $p->color,
                'url' => route('social.redirect', $p->slug),
            ]),
        ]);
    }

    /**
     * Redirect to OAuth provider.
     */
    public function redirect(string $provider)
    {
        try {
            return $this->socialAuthService->redirect($provider);
        } catch (\Exception $e) {
            Log::error('Social auth redirect failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Unable to connect to ' . ucfirst($provider) . '. Please try again.');
        }
    }

    /**
     * Handle OAuth callback.
     */
    public function callback(Request $request, string $provider)
    {
        // Check for OAuth errors
        if ($request->has('error')) {
            $error = $request->get('error_description', $request->get('error', 'Unknown error'));

            Log::warning('Social auth callback error', [
                'provider' => $provider,
                'error' => $error,
            ]);

            return redirect()->route('login')
                ->with('error', 'Authentication failed: ' . $error);
        }

        try {
            $result = $this->socialAuthService->handleCallback($provider);

            $user = $result['user'];
            $isNewUser = $result['is_new_user'];

            // Log the user in
            Auth::login($user, true);

            // Determine redirect destination
            if ($isNewUser) {
                return redirect()->route('onboarding')
                    ->with('success', 'Welcome to StudAI Career! Let\'s set up your profile.');
            }

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Welcome back, ' . $user->name . '!');

        } catch (\Exception $e) {
            Log::error('Social auth callback failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Authentication failed. Please try again or use another method.');
        }
    }

    /**
     * Connect a social account to existing user.
     */
    public function connect(string $provider)
    {
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Please log in first to connect a social account.');
        }

        try {
            // Store state to know we're connecting, not logging in
            session(['social_auth_action' => 'connect']);

            return $this->socialAuthService->redirect($provider);
        } catch (\Exception $e) {
            return redirect()->route('profile.connections')
                ->with('error', 'Unable to connect to ' . ucfirst($provider) . '.');
        }
    }

    /**
     * Handle connect callback.
     */
    public function connectCallback(Request $request, string $provider)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Check for OAuth errors
        if ($request->has('error')) {
            return redirect()->route('profile.connections')
                ->with('error', 'Failed to connect ' . ucfirst($provider) . '.');
        }

        try {
            $result = $this->socialAuthService->handleCallback($provider);

            // Check if the social account was linked to the current user
            if ($result['user']->id === Auth::id()) {
                return redirect()->route('profile.connections')
                    ->with('success', ucfirst($provider) . ' connected successfully!');
            }

            // The social account is already linked to another user
            return redirect()->route('profile.connections')
                ->with('error', 'This ' . ucfirst($provider) . ' account is already linked to another user.');

        } catch (\Exception $e) {
            Log::error('Social connect failed', [
                'provider' => $provider,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('profile.connections')
                ->with('error', 'Failed to connect ' . ucfirst($provider) . '. ' . $e->getMessage());
        }
    }

    /**
     * Disconnect a social account.
     */
    public function disconnect(Request $request, string $provider)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        try {
            $this->socialAuthService->disconnect(Auth::user(), $provider);

            return redirect()->route('profile.connections')
                ->with('success', ucfirst($provider) . ' disconnected successfully.');

        } catch (\Exception $e) {
            return redirect()->route('profile.connections')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Get user's connected accounts (for profile page).
     */
    public function connections()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $providers = $this->socialAuthService->getAvailableProviders(Auth::user());

        return view('auth.connections', [
            'providers' => $providers,
        ]);
    }
}
