<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            // Add grace period tracking columns
            $table->timestamp('grace_period_ends_at')->nullable()->after('canceled_at');
            $table->unsignedInteger('failure_count')->default(0)->after('grace_period_ends_at');
            $table->timestamp('last_retry_at')->nullable()->after('failure_count');

            // Add index for grace period queries
            $table->index(['status', 'grace_period_ends_at']);
        });

        // Update the status enum to include 'past_due'
        // Note: In MySQL, we need to alter the enum to add new value
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE user_subscriptions MODIFY status ENUM('pending', 'active', 'canceled', 'expired', 'trialing', 'past_due') DEFAULT 'active'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['status', 'grace_period_ends_at']);
            $table->dropColumn(['grace_period_ends_at', 'failure_count', 'last_retry_at']);
        });

        // Revert enum if MySQL
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE user_subscriptions MODIFY status ENUM('active', 'canceled', 'expired', 'trialing') DEFAULT 'active'");
        }
    }
};
