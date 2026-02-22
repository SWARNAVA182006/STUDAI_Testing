<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SocialAccount;
use App\Models\SocialAuthLog;
use App\Models\SocialProvider;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Two\AbstractProvider;

class SocialAuthService
{
    /**
     * Get all enabled providers for display.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEnabledProviders()
    {
        return SocialProvider::enabled()
            ->configured()
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get provider configuration from database.
     */
    public function getProvider(string $slug): ?SocialProvider
    {
        return SocialProvider::where('slug', $slug)
            ->enabled()
            ->configured()
            ->first();
    }

    /**
     * Configure Socialite with database credentials.
     */
    public function configureProvider(string $slug): ?AbstractProvider
    {
        $provider = $this->getProvider($slug);

        if (!$provider) {
            return null;
        }

        $config = $provider->getSocialiteConfig();

        // Build Socialite driver dynamically
        $driver = Socialite::driver($slug);

        // Apply configuration
        $driver->clientId = $config['client_id'];
        $driver->clientSecret = $config['client_secret'];
        $driver->redirectUrl = $config['redirect'];

        // Apply scopes if specified
        $scopes = $provider->getScopesArray();
        if (!empty($scopes)) {
            $driver->scopes($scopes);
        }

        return $driver;
    }

    /**
     * Redirect to OAuth provider.
     */
    public function redirect(string $provider)
    {
        $driver = $this->configureProvider($provider);

        if (!$driver) {
            throw new \Exception("Provider '{$provider}' is not configured or enabled.");
        }

        // Log the redirect attempt
        SocialAuthLog::logSuccess($provider, 'redirect');

        return $driver->redirect();
    }

    /**
     * Handle OAuth callback.
     */
    public function handleCallback(string $provider): array
    {
        $driver = $this->configureProvider($provider);

        if (!$driver) {
            throw new \Exception("Provider '{$provider}' is not configured or enabled.");
        }

        try {
            $socialUser = $driver->user();

            return $this->findOrCreateUser($provider, $socialUser);
        } catch (\Exception $e) {
            Log::error('Social auth callback failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            SocialAuthLog::logFailure(
                $provider,
                'callback',
                $e->getMessage()
            );

            throw $e;
        }
    }

    /**
     * Find or create user from social login.
     */
    protected function findOrCreateUser(string $provider, SocialiteUser $socialUser): array
    {
        return DB::transaction(function () use ($provider, $socialUser) {
            // Check if we already have this social account linked
            $socialAccount = SocialAccount::forProviderUser($provider, $socialUser->getId())->first();

            if ($socialAccount) {
                // Existing social account - update and log in
                $user = $socialAccount->user;
                $isNewUser = false;

                // Update tokens and profile
                $this->updateSocialAccount($socialAccount, $socialUser);

                SocialAuthLog::logSuccess($provider, 'login', $user->id, [
                    'email' => $socialUser->getEmail(),
                ]);
            } else {
                // Check if user exists with same email
                $email = $socialUser->getEmail();
                $user = $email ? User::where('email', $email)->first() : null;

                if ($user) {
                    // Link to existing account
                    $socialAccount = $this->createSocialAccount($user, $provider, $socialUser);
                    $isNewUser = false;

                    SocialAuthLog::logSuccess($provider, 'link', $user->id, [
                        'email' => $email,
                    ]);
                } else {
                    // Create new user
                    $user = $this->createUser($socialUser);
                    $socialAccount = $this->createSocialAccount($user, $provider, $socialUser);
                    $isNewUser = true;

                    SocialAuthLog::logSuccess($provider, 'register', $user->id, [
                        'email' => $socialUser->getEmail(),
                    ]);
                }
            }

            return [
                'user' => $user,
                'social_account' => $socialAccount,
                'is_new_user' => $isNewUser,
            ];
        });
    }

    /**
     * Create a new user from social data.
     */
    protected function createUser(SocialiteUser $socialUser): User
    {
        $name = $socialUser->getName() ?? $socialUser->getNickname() ?? 'User';
        $email = $socialUser->getEmail();

        // Generate unique email if none provided
        if (!$email) {
            $email = Str::slug($name) . '-' . Str::random(8) . '@social.local';
        }

        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(32)),
            'email_verified_at' => now(), // Social login = verified
        ]);
    }

    /**
     * Create a social account link.
     */
    protected function createSocialAccount(User $user, string $provider, SocialiteUser $socialUser): SocialAccount
    {
        $expiresIn = null;
        $refreshToken = null;

        // Handle different Socialite user types
        if (method_exists($socialUser, 'expiresIn')) {
            $expiresIn = $socialUser->expiresIn;
        }
        if (method_exists($socialUser, 'refreshToken')) {
            $refreshToken = $socialUser->refreshToken;
        }

        return SocialAccount::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_user_id' => $socialUser->getId(),
            'email' => $socialUser->getEmail(),
            'name' => $socialUser->getName(),
            'nickname' => $socialUser->getNickname(),
            'avatar' => $socialUser->getAvatar(),
            'access_token' => $socialUser->token,
            'refresh_token' => $refreshToken,
            'token_expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : null,
            'profile_data' => $socialUser->getRaw(),
            'last_login_at' => now(),
        ]);
    }

    /**
     * Update existing social account.
     */
    protected function updateSocialAccount(SocialAccount $account, SocialiteUser $socialUser): void
    {
        $expiresIn = null;
        $refreshToken = $account->refresh_token;

        if (method_exists($socialUser, 'expiresIn')) {
            $expiresIn = $socialUser->expiresIn;
        }
        if (method_exists($socialUser, 'refreshToken') && $socialUser->refreshToken) {
            $refreshToken = $socialUser->refreshToken;
        }

        $account->update([
            'email' => $socialUser->getEmail() ?? $account->email,
            'name' => $socialUser->getName() ?? $account->name,
            'nickname' => $socialUser->getNickname() ?? $account->nickname,
            'avatar' => $socialUser->getAvatar() ?? $account->avatar,
            'access_token' => $socialUser->token,
            'refresh_token' => $refreshToken,
            'token_expires_at' => $expiresIn ? now()->addSeconds($expiresIn) : $account->token_expires_at,
            'profile_data' => $socialUser->getRaw(),
            'last_login_at' => now(),
        ]);
    }

    /**
     * Disconnect a social account from user.
     */
    public function disconnect(User $user, string $provider): bool
    {
        // Check if user has other login methods
        $socialAccountsCount = $user->socialAccounts()->count();
        $hasPassword = $user->password !== null && $user->password !== '';

        if ($socialAccountsCount <= 1 && !$hasPassword) {
            throw new \Exception('Cannot disconnect the only login method. Please add a password first.');
        }

        $account = $user->socialAccounts()->forProvider($provider)->first();

        if (!$account) {
            return false;
        }

        SocialAuthLog::logSuccess($provider, 'disconnect', $user->id);

        return (bool) $account->delete();
    }

    /**
     * Get user's connected social accounts.
     */
    public function getConnectedAccounts(User $user)
    {
        return $user->socialAccounts()
            ->get()
            ->keyBy('provider');
    }

    /**
     * Get available providers for a user to connect.
     */
    public function getAvailableProviders(User $user)
    {
        $connected = $this->getConnectedAccounts($user)->keys()->toArray();
        
        return $this->getEnabledProviders()
            ->map(function ($provider) use ($connected) {
                $provider->is_connected = in_array($provider->slug, $connected);
                return $provider;
            });
    }

    /**
     * Get provider-specific scopes.
     */
    public function getProviderScopes(string $provider): array
    {
        $defaults = [
            'google' => ['openid', 'email', 'profile'],
            'linkedin' => ['r_liteprofile', 'r_emailaddress'],
            'apple' => ['name', 'email'],
            'microsoft' => ['User.Read', 'email', 'profile', 'openid'],
            'facebook' => ['email', 'public_profile'],
            'github' => ['user:email', 'read:user'],
            'twitter' => [],
        ];

        return $defaults[$provider] ?? [];
    }
}
