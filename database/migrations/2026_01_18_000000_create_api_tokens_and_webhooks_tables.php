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
        // API tokens table (extends Laravel Sanctum)
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Token name/description
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable(); // JSON array of permissions
            $table->integer('rate_limit')->default(60); // Requests per minute
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['company_id', 'is_active']);
            $table->index('token');
        });
        
        // API usage logs
        Schema::create('api_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_token_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('endpoint');
            $table->string('method', 10); // GET, POST, PUT, DELETE
            $table->integer('status_code');
            $table->integer('response_time_ms');
            $table->ipAddress('ip_address');
            $table->text('user_agent')->nullable();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->timestamp('created_at');
            
            $table->index(['api_token_id', 'created_at']);
            $table->index(['company_id', 'created_at']);
            $table->index('created_at');
        });
        
        // Webhooks configuration
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('url');
            $table->json('events'); // Array of event types to subscribe to
            $table->string('secret'); // For signature verification
            $table->boolean('is_active')->default(true);
            $table->integer('retry_attempts')->default(3);
            $table->integer('timeout_seconds')->default(30);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'is_active']);
        });
        
        // Webhook delivery logs
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->json('payload');
            $table->integer('status_code')->nullable();
            $table->text('response_body')->nullable();
            $table->integer('attempt_number')->default(1);
            $table->integer('response_time_ms')->nullable();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            
            $table->index(['webhook_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
        
        // Rate limiting records
        Schema::create('rate_limits', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Token ID or IP address
            $table->integer('hits')->default(0);
            $table->timestamp('reset_at');
            $table->timestamps();
            
            $table->index('key');
            $table->index('reset_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_limits');
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('api_usage_logs');
        Schema::dropIfExists('api_tokens');
    }
};
