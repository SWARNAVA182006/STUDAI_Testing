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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('razorpay_plan_id')->nullable();
            $table->string('payu_plan_id')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('INR');
            $table->enum('billing_period', ['monthly', 'yearly']);
            $table->json('features')->nullable(); // Array of features
            $table->integer('ai_credits')->default(0);
            $table->integer('applications_limit')->nullable(); // null = unlimited
            $table->integer('job_alerts_limit')->nullable();
            $table->boolean('priority_support')->default(false);
            $table->boolean('api_access')->default(false);
            $table->integer('api_calls_limit')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('slug');
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
