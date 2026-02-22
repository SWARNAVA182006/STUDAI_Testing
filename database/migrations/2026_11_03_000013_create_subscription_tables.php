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
        // Subscription plans table
        if (!Schema::hasTable('subscription_plans')) {
            Schema::create('subscription_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // Free, Professional, Premium
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->decimal('price_monthly', 10, 2)->default(0);
                $table->decimal('price_yearly', 10, 2)->default(0);
                $table->integer('discount_yearly_percent')->default(0); // e.g., 20% off yearly
                $table->json('features'); // Array of feature slugs
                $table->integer('applications_limit')->nullable(); // null = unlimited
                $table->integer('ai_credits')->nullable(); // null = unlimited
                $table->integer('job_alerts_limit')->default(5);
                $table->integer('saved_jobs_limit')->nullable();
                $table->integer('assessment_limit')->default(3);
                $table->boolean('priority_support')->default(false);
                $table->boolean('resume_builder')->default(false);
                $table->boolean('ai_resume_review')->default(false);
                $table->boolean('mock_interviews')->default(false);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_featured')->default(false);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }
        
        // User subscriptions table
        if (!Schema::hasTable('user_subscriptions')) {
            Schema::create('user_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();
                $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
                $table->enum('status', ['active', 'trialing', 'past_due', 'canceled', 'expired'])->default('active');
                $table->decimal('amount', 10, 2);
                $table->string('currency', 3)->default('INR');
                $table->timestamp('current_period_start');
                $table->timestamp('current_period_end');
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamp('canceled_at')->nullable();
                $table->integer('applications_used_this_month')->default(0);
                $table->integer('ai_credits_used_this_month')->default(0);
                $table->integer('assessments_taken_this_month')->default(0);
                $table->timestamp('last_reset_at')->nullable(); // Track when usage was reset
                $table->timestamps();
                
                $table->index(['user_id', 'status']);
                $table->index('current_period_end');
            });
        }
        
        // Payment transactions table
        if (!Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_subscription_id')->nullable()->constrained()->nullOnDelete();
                $table->string('transaction_id')->unique();
                $table->enum('payment_gateway', ['razorpay', 'payu']); // India-specific gateways
                $table->string('gateway_order_id')->nullable();
                $table->string('gateway_payment_id')->nullable();
                $table->string('gateway_signature')->nullable();
                $table->decimal('amount', 10, 2);
                $table->string('currency', 3)->default('INR');
                $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('pending');
                $table->enum('type', ['subscription', 'upgrade', 'renewal', 'one_time'])->default('subscription');
                $table->text('description')->nullable();
                $table->json('metadata')->nullable(); // Store additional gateway data
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
                
                $table->index(['user_id', 'status']);
                $table->index('payment_gateway');
                $table->index('gateway_order_id');
            });
        }
        
        // Usage logs table (for detailed analytics)
        if (!Schema::hasTable('usage_logs')) {
            Schema::create('usage_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->enum('type', ['application', 'ai_credit', 'assessment', 'job_alert', 'resume_review']);
                $table->string('resource_type')->nullable(); // Model class name
                $table->unsignedBigInteger('resource_id')->nullable(); // Model ID
                $table->integer('credits_consumed')->default(1);
                $table->json('metadata')->nullable();
                $table->timestamp('created_at');
                
                $table->index(['user_id', 'type', 'created_at']);
                $table->index('created_at'); // For time-based queries
            });
        }
        
        // Promo codes table
        if (!Schema::hasTable('promo_codes')) {
            Schema::create('promo_codes', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->enum('type', ['percentage', 'fixed_amount', 'free_trial']);
                $table->decimal('discount_value', 10, 2)->nullable(); // Percentage or amount
                $table->integer('trial_days')->nullable(); // For free trial codes
                $table->integer('max_uses')->nullable(); // null = unlimited
                $table->integer('used_count')->default(0);
                $table->timestamp('valid_from')->nullable();
                $table->timestamp('valid_until')->nullable();
                $table->boolean('is_active')->default(true);
                $table->json('applicable_plans')->nullable(); // Array of plan IDs
                $table->timestamps();
                
                $table->index('code');
                $table->index(['is_active', 'valid_until']);
            });
        }
        
        // Promo code redemptions table
        if (!Schema::hasTable('promo_code_redemptions')) {
            Schema::create('promo_code_redemptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('promo_code_id')->constrained()->cascadeOnDelete();
                $table->foreignId('payment_transaction_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('discount_amount', 10, 2);
                $table->timestamp('redeemed_at');
                
                $table->index(['user_id', 'promo_code_id']);
            });
        }
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_code_redemptions');
        Schema::dropIfExists('promo_codes');
        Schema::dropIfExists('usage_logs');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('user_subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
