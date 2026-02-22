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
        // Background check packages/products
        Schema::create('background_check_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('provider'); // checkr, sterling, goodhire
            $table->string('provider_package_id')->nullable();
            $table->json('checks_included'); // ['criminal', 'employment', 'education', 'credit', 'drug', 'mvr']
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('estimated_days')->default(3);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            $table->index(['company_id', 'is_active']);
        });

        // Main background check records
        Schema::create('background_checks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('candidate_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('application_id')->nullable()->constrained('applications')->onDelete('set null');
            $table->foreignId('package_id')->nullable()->constrained('background_check_packages')->onDelete('set null');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            
            // Provider details
            $table->string('provider'); // checkr, sterling, goodhire
            $table->string('provider_check_id')->nullable();
            $table->string('provider_report_id')->nullable();
            $table->string('provider_candidate_id')->nullable();
            
            // Status tracking
            $table->string('status')->default('pending'); // pending, consent_pending, consent_received, in_progress, completed, failed, cancelled, expired
            $table->string('result')->nullable(); // clear, consider, adverse_action, suspended
            $table->string('adjudication')->nullable(); // engaged, pre_adverse, adverse, approved
            
            // Consent workflow
            $table->timestamp('consent_requested_at')->nullable();
            $table->timestamp('consent_received_at')->nullable();
            $table->timestamp('consent_expires_at')->nullable();
            $table->string('consent_token')->nullable()->unique();
            $table->string('consent_ip_address')->nullable();
            $table->text('consent_user_agent')->nullable();
            $table->boolean('consent_given')->default(false);
            
            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('estimated_completion_days')->nullable();
            
            // Report data
            $table->json('checks_requested')->nullable(); // ['criminal', 'employment', etc.]
            $table->json('checks_completed')->nullable();
            $table->json('report_summary')->nullable();
            $table->text('report_url')->nullable();
            $table->string('report_pdf_path')->nullable();
            
            // Cost tracking
            $table->decimal('cost', 10, 2)->nullable();
            
            // Notes & flags
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->boolean('has_flags')->default(false);
            $table->json('flags')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['company_id', 'status']);
            $table->index(['candidate_id', 'status']);
            $table->index(['provider', 'provider_check_id']);
            $table->index('consent_token');
        });

        // Individual check items within a background check
        Schema::create('background_check_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('background_check_id')->constrained()->onDelete('cascade');
            $table->string('check_type'); // criminal, employment_verification, education_verification, credit, drug_screening, mvr, identity, ssn_trace, sex_offender, global_watchlist
            $table->string('status')->default('pending'); // pending, in_progress, completed, failed, cancelled
            $table->string('result')->nullable(); // clear, consider, adverse
            $table->json('result_data')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['background_check_id', 'check_type']);
        });

        // Activity log for background checks
        Schema::create('background_check_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('background_check_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action'); // created, consent_sent, consent_received, started, check_completed, completed, reviewed, adverse_action_initiated, etc.
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
            
            $table->index(['background_check_id', 'created_at']);
        });

        // Adverse action workflow
        Schema::create('background_check_adverse_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('background_check_id')->constrained()->onDelete('cascade');
            $table->foreignId('initiated_by')->constrained('users')->onDelete('cascade');
            
            // Pre-adverse action (required first step)
            $table->timestamp('pre_adverse_sent_at')->nullable();
            $table->text('pre_adverse_reason')->nullable();
            $table->string('pre_adverse_email_path')->nullable();
            
            // Waiting period
            $table->integer('waiting_period_days')->default(5);
            $table->timestamp('waiting_period_ends_at')->nullable();
            
            // Candidate response
            $table->boolean('candidate_disputed')->default(false);
            $table->text('dispute_reason')->nullable();
            $table->timestamp('dispute_received_at')->nullable();
            
            // Final adverse action
            $table->boolean('final_action_taken')->default(false);
            $table->timestamp('final_adverse_sent_at')->nullable();
            $table->text('final_adverse_reason')->nullable();
            $table->string('final_adverse_email_path')->nullable();
            
            // Outcome
            $table->string('outcome')->nullable(); // withdrawn, upheld
            $table->text('outcome_notes')->nullable();
            
            $table->timestamps();
        });

        // Provider webhook logs
        Schema::create('background_check_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('event_type');
            $table->string('provider_check_id')->nullable();
            $table->json('payload');
            $table->boolean('processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->text('processing_notes')->nullable();
            $table->timestamps();
            
            $table->index(['provider', 'event_type', 'processed']);
            $table->index('provider_check_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('background_check_webhooks');
        Schema::dropIfExists('background_check_adverse_actions');
        Schema::dropIfExists('background_check_activities');
        Schema::dropIfExists('background_check_items');
        Schema::dropIfExists('background_checks');
        Schema::dropIfExists('background_check_packages');
    }
};
