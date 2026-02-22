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
        // ATS Providers - Available ATS systems
        if (!Schema::hasTable('ats_providers')) {
            Schema::create('ats_providers', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // Lever, Greenhouse, Workday, etc.
                $table->string('slug')->unique();
                $table->string('display_name');
                $table->text('description')->nullable();
                $table->string('logo')->nullable();
                $table->string('website_url')->nullable();
                $table->string('documentation_url')->nullable();
                $table->enum('auth_type', ['oauth2', 'api_key', 'basic', 'custom'])->default('api_key');
                $table->json('required_credentials')->nullable(); // List of required credential fields
                $table->json('supported_features')->nullable(); // What this ATS supports
                $table->json('webhook_events')->nullable(); // Available webhook events
                $table->json('rate_limits')->nullable(); // API rate limit info
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // ATS Connections - Employer connections to their ATS
        if (!Schema::hasTable('ats_connections')) {
            Schema::create('ats_connections', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('ats_provider_id')->constrained()->cascadeOnDelete();
                $table->foreignId('connected_by')->constrained('users'); // User who set up connection
                $table->string('connection_name')->nullable(); // Custom name for this connection
                $table->text('api_key')->nullable(); // Encrypted API key
                $table->text('api_secret')->nullable(); // Encrypted API secret
                $table->text('access_token')->nullable(); // Encrypted OAuth token
                $table->text('refresh_token')->nullable(); // Encrypted refresh token
                $table->timestamp('token_expires_at')->nullable();
                $table->string('webhook_secret')->nullable(); // For validating incoming webhooks
                $table->string('webhook_url')->nullable(); // Our webhook endpoint for this connection
                $table->json('credentials')->nullable(); // Additional encrypted credentials
                $table->json('settings')->nullable(); // Connection-specific settings
                $table->json('field_mappings')->nullable(); // Custom field mappings
                $table->enum('sync_direction', ['push', 'pull', 'bidirectional'])->default('bidirectional');
                $table->boolean('auto_sync_jobs')->default(true);
                $table->boolean('auto_sync_candidates')->default(true);
                $table->boolean('auto_sync_applications')->default(true);
                $table->integer('sync_interval_minutes')->default(15);
                $table->timestamp('last_synced_at')->nullable();
                $table->enum('status', ['active', 'paused', 'error', 'disconnected'])->default('active');
                $table->text('last_error')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'ats_provider_id']);
                $table->index(['status', 'last_synced_at']);
            });
        }

        // ATS Synced Jobs - Jobs synced from/to ATS
        if (!Schema::hasTable('ats_synced_jobs')) {
            Schema::create('ats_synced_jobs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ats_connection_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('job_posting_id')->nullable(); // May link to job_postings if exists
                $table->string('external_job_id'); // Job ID in external ATS
                $table->string('external_requisition_id')->nullable();
                $table->enum('sync_direction', ['imported', 'exported']); // Where job originated
                $table->enum('sync_status', ['synced', 'pending', 'failed', 'conflict'])->default('synced');
                $table->json('external_data')->nullable(); // Raw data from ATS
                $table->json('field_mappings')->nullable(); // How fields were mapped
                $table->string('external_url')->nullable(); // Link to job in ATS
                $table->timestamp('external_created_at')->nullable();
                $table->timestamp('external_updated_at')->nullable();
                $table->timestamp('last_synced_at');
                $table->text('sync_error')->nullable();
                $table->timestamps();

                $table->unique(['ats_connection_id', 'external_job_id']);
                $table->index(['job_posting_id', 'sync_status']);
            });
        }

        // ATS Synced Candidates - Candidates synced to ATS
        if (!Schema::hasTable('ats_synced_candidates')) {
            Schema::create('ats_synced_candidates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ats_connection_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Our candidate
                $table->string('external_candidate_id'); // Candidate ID in external ATS
                $table->enum('sync_direction', ['imported', 'exported'])->default('exported');
                $table->enum('sync_status', ['synced', 'pending', 'failed'])->default('synced');
                $table->json('external_data')->nullable(); // Raw candidate data from ATS
                $table->json('synced_fields')->nullable(); // Which fields were synced
                $table->string('external_url')->nullable(); // Link to candidate in ATS
                $table->timestamp('last_synced_at');
                $table->text('sync_error')->nullable();
                $table->timestamps();

                $table->unique(['ats_connection_id', 'user_id'], 'ats_cand_conn_user_unique');
                $table->unique(['ats_connection_id', 'external_candidate_id'], 'ats_cand_conn_ext_unique');
            });
        }

        // ATS Synced Applications - Application status sync
        if (!Schema::hasTable('ats_synced_applications')) {
            Schema::create('ats_synced_applications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ats_connection_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('application_id')->nullable(); // Our application ID
                $table->foreignId('ats_synced_job_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('ats_synced_candidate_id')->nullable()->constrained()->nullOnDelete();
                $table->string('external_application_id'); // Application ID in external ATS
                $table->enum('sync_direction', ['imported', 'exported'])->default('exported');
                $table->enum('sync_status', ['synced', 'pending', 'failed'])->default('synced');
                $table->string('external_stage')->nullable(); // Current stage in ATS pipeline
                $table->string('external_status')->nullable(); // Status in ATS
                $table->json('stage_history')->nullable(); // History of stage changes
                $table->json('external_data')->nullable(); // Raw application data
                $table->string('external_url')->nullable(); // Link to application in ATS
                $table->timestamp('external_applied_at')->nullable();
                $table->timestamp('last_synced_at');
                $table->text('sync_error')->nullable();
                $table->timestamps();

                $table->unique(['ats_connection_id', 'external_application_id'], 'ats_app_conn_ext_unique');
                $table->index(['application_id', 'sync_status']);
            });
        }

        // ATS Sync Logs - Detailed sync history
        if (!Schema::hasTable('ats_sync_logs')) {
            Schema::create('ats_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ats_connection_id')->constrained()->cascadeOnDelete();
                $table->enum('sync_type', ['full', 'incremental', 'webhook', 'manual']);
                $table->enum('entity_type', ['jobs', 'candidates', 'applications', 'all']);
                $table->enum('direction', ['push', 'pull']);
                $table->enum('status', ['started', 'completed', 'failed', 'partial']);
                $table->integer('records_processed')->default(0);
                $table->integer('records_created')->default(0);
                $table->integer('records_updated')->default(0);
                $table->integer('records_failed')->default(0);
                $table->json('errors')->nullable(); // List of errors
                $table->json('summary')->nullable(); // Sync summary data
                $table->timestamp('started_at');
                $table->timestamp('completed_at')->nullable();
                $table->integer('duration_seconds')->nullable();
                $table->timestamps();

                $table->index(['ats_connection_id', 'created_at']);
            });
        }

        // ATS Webhooks - Incoming webhook events
        if (!Schema::hasTable('ats_webhooks')) {
            Schema::create('ats_webhooks', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('ats_connection_id')->nullable()->constrained()->nullOnDelete();
                $table->string('provider_slug'); // In case connection is deleted
                $table->string('event_type'); // candidate.created, application.stage_changed, etc.
                $table->json('payload'); // Raw webhook payload
                $table->json('headers')->nullable(); // Request headers
                $table->enum('status', ['pending', 'processing', 'processed', 'failed'])->default('pending');
                $table->text('processing_error')->nullable();
                $table->timestamp('received_at');
                $table->timestamp('processed_at')->nullable();
                $table->integer('retry_count')->default(0);
                $table->timestamps();

                $table->index(['ats_connection_id', 'status']);
                $table->index(['provider_slug', 'event_type']);
            });
        }

        // ATS Field Mappings - Custom field mapping templates
        if (!Schema::hasTable('ats_field_mappings')) {
            Schema::create('ats_field_mappings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ats_provider_id')->constrained()->cascadeOnDelete();
                $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete(); // null = default mapping
                $table->enum('entity_type', ['job', 'candidate', 'application']);
                $table->string('our_field'); // Our field name
                $table->string('ats_field'); // ATS field name
                $table->enum('direction', ['push', 'pull', 'bidirectional'])->default('bidirectional');
                $table->string('transform')->nullable(); // Transformation to apply
                $table->json('value_mappings')->nullable(); // Value translation map
                $table->boolean('is_required')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['ats_provider_id', 'company_id', 'entity_type', 'our_field'], 'ats_field_map_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ats_field_mappings');
        Schema::dropIfExists('ats_webhooks');
        Schema::dropIfExists('ats_sync_logs');
        Schema::dropIfExists('ats_synced_applications');
        Schema::dropIfExists('ats_synced_candidates');
        Schema::dropIfExists('ats_synced_jobs');
        Schema::dropIfExists('ats_connections');
        Schema::dropIfExists('ats_providers');
    }
};
