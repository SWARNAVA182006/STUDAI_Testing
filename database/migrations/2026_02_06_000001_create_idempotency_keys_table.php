<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the idempotency_keys table for storing request idempotency data.
     * This prevents duplicate processing of the same request, especially critical
     * for payment and application submission endpoints.
     */
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->index();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('endpoint', 255);
            $table->string('method', 10);
            $table->integer('response_status');
            $table->longText('response_body');
            $table->json('response_headers')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            // Composite unique constraint: key + user + endpoint
            $table->unique(['key', 'user_id', 'endpoint'], 'idempotency_unique');

            // Index for cleanup of expired records
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
