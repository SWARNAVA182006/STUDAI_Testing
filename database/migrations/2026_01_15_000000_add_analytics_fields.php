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
        // Add scope to PaymentTransaction model
        Schema::table('payment_transactions', function (Blueprint $table) {
            // Check if columns exist before adding
            if (!Schema::hasColumn('payment_transactions', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('status');
            }
        });
        
        // Add last_login_at to users table for activity tracking
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
            }
            
            if (!Schema::hasColumn('users', 'profile_completed_at')) {
                $table->timestamp('profile_completed_at')->nullable()->after('last_login_at');
            }
        });
        
        // Add viewed_at to applications for response time tracking
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'viewed_at')) {
                $table->timestamp('viewed_at')->nullable()->after('status');
            }
        });
        
        // Add indexes for analytics queries (wrapped in try-catch to handle duplicates)
        try {
            Schema::table('user_subscriptions', function (Blueprint $table) {
                $table->index(['status', 'created_at']);
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }
        
        try {
            Schema::table('user_subscriptions', function (Blueprint $table) {
                $table->index(['status', 'current_period_end']);
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }
        
        try {
            Schema::table('user_subscriptions', function (Blueprint $table) {
                $table->index('canceled_at');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }
        
        try {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->index(['status', 'paid_at']);
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }
        
        try {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->index('payment_gateway');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }
        
        try {
            Schema::table('applications', function (Blueprint $table) {
                $table->index('status');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }
        
        try {
            Schema::table('applications', function (Blueprint $table) {
                $table->index('viewed_at');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }
        
        try {
            Schema::table('job_listings', function (Blueprint $table) {
                $table->index(['status', 'created_at']);
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('payment_transactions', 'paid_at')) {
                $table->dropColumn('paid_at');
            }
            $table->dropIndex(['status', 'paid_at']);
            $table->dropIndex(['payment_gateway']);
        });
        
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->dropColumn('last_login_at');
            }
            if (Schema::hasColumn('users', 'profile_completed_at')) {
                $table->dropColumn('profile_completed_at');
            }
        });
        
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'viewed_at')) {
                $table->dropColumn('viewed_at');
            }
            $table->dropIndex(['status']);
            $table->dropIndex(['viewed_at']);
        });
        
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['status', 'current_period_end']);
            $table->dropIndex(['canceled_at']);
        });
        
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });
    }
};
