<?php

declare(strict_types=1);

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
        // GDPR audit logs for tracking data operations
        Schema::create('gdpr_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('operation'); // export, delete, anonymize, consent_update
            $table->json('data')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');

            $table->index(['user_id', 'operation']);
            $table->index('created_at');
        });

        // Scheduled deletions for delayed right to erasure
        Schema::create('scheduled_deletions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('scheduled_at');
            $table->string('status')->default('pending'); // pending, canceled, completed
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['status', 'scheduled_at']);
        });

        // Add consent columns to users table
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('marketing_consent')->default(false)->after('remember_token');
            $table->boolean('data_processing_consent')->default(true)->after('marketing_consent');
            $table->boolean('third_party_consent')->default(false)->after('data_processing_consent');
            $table->boolean('analytics_consent')->default(true)->after('third_party_consent');
            $table->boolean('ai_processing_consent')->default(true)->after('analytics_consent');
            $table->timestamp('consent_updated_at')->nullable()->after('ai_processing_consent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gdpr_audit_logs');
        Schema::dropIfExists('scheduled_deletions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'marketing_consent',
                'data_processing_consent',
                'third_party_consent',
                'analytics_consent',
                'ai_processing_consent',
                'consent_updated_at',
            ]);
        });
    }
};
