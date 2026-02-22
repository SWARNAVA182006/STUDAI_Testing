<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Social Providers Configuration - Admin configurable
        Schema::create('social_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Display name: "Google", "LinkedIn"
            $table->string('slug')->unique(); // google, linkedin, apple, microsoft, facebook, twitter, github
            $table->string('client_id')->nullable();
            $table->text('client_secret')->nullable(); // Encrypted
            $table->text('redirect_url')->nullable();
            $table->json('scopes')->nullable(); // OAuth scopes
            $table->json('additional_config')->nullable(); // Any extra provider-specific config
            $table->string('icon')->nullable(); // Icon class or SVG path
            $table->string('color')->nullable(); // Brand color for button
            $table->boolean('is_enabled')->default(false);
            $table->boolean('allow_login')->default(true); // Allow existing users to login
            $table->boolean('allow_register')->default(true); // Allow new user registration
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['is_enabled', 'sort_order']);
        });

        // Social Accounts - Links social accounts to users
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // google, linkedin, etc.
            $table->string('provider_user_id'); // ID from the provider
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->string('nickname')->nullable();
            $table->string('avatar')->nullable();
            $table->text('access_token')->nullable(); // Encrypted
            $table->text('refresh_token')->nullable(); // Encrypted
            $table->timestamp('token_expires_at')->nullable();
            $table->json('profile_data')->nullable(); // Raw profile data from provider
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            
            $table->unique(['provider', 'provider_user_id']);
            $table->index(['user_id', 'provider']);
            $table->index('email');
        });

        // Social Auth Logs - For debugging and security
        Schema::create('social_auth_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider');
            $table->string('provider_user_id')->nullable();
            $table->string('email')->nullable();
            $table->enum('action', ['login', 'register', 'link', 'unlink', 'refresh', 'error']);
            $table->enum('status', ['success', 'failed', 'pending']);
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['provider', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });

        // Seed default providers (disabled by default, admin enables them)
        $this->seedDefaultProviders();
    }

    /**
     * Seed default social providers.
     */
    protected function seedDefaultProviders(): void
    {
        $providers = [
            [
                'name' => 'Google',
                'slug' => 'google',
                'icon' => 'google',
                'color' => '#EA4335',
                'scopes' => json_encode(['openid', 'profile', 'email']),
                'sort_order' => 1,
            ],
            [
                'name' => 'LinkedIn',
                'slug' => 'linkedin',
                'icon' => 'linkedin',
                'color' => '#0A66C2',
                'scopes' => json_encode(['openid', 'profile', 'email']),
                'sort_order' => 2,
            ],
            [
                'name' => 'Apple',
                'slug' => 'apple',
                'icon' => 'apple',
                'color' => '#000000',
                'scopes' => json_encode(['name', 'email']),
                'sort_order' => 3,
            ],
            [
                'name' => 'Microsoft',
                'slug' => 'microsoft',
                'icon' => 'microsoft',
                'color' => '#00A4EF',
                'scopes' => json_encode(['openid', 'profile', 'email', 'User.Read']),
                'sort_order' => 4,
            ],
            [
                'name' => 'Facebook',
                'slug' => 'facebook',
                'icon' => 'facebook',
                'color' => '#1877F2',
                'scopes' => json_encode(['email', 'public_profile']),
                'sort_order' => 5,
            ],
            [
                'name' => 'Twitter',
                'slug' => 'twitter',
                'icon' => 'twitter',
                'color' => '#1DA1F2',
                'scopes' => json_encode([]),
                'sort_order' => 6,
            ],
            [
                'name' => 'GitHub',
                'slug' => 'github',
                'icon' => 'github',
                'color' => '#333333',
                'scopes' => json_encode(['user:email', 'read:user']),
                'sort_order' => 7,
            ],
        ];

        foreach ($providers as $provider) {
            \DB::table('social_providers')->insert(array_merge($provider, [
                'is_enabled' => false,
                'allow_login' => true,
                'allow_register' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_auth_logs');
        Schema::dropIfExists('social_accounts');
        Schema::dropIfExists('social_providers');
    }
};
