<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Offer letter templates
        Schema::create('offer_letter_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->longText('content_html');
            $table->json('variables')->nullable();
            $table->json('default_values')->nullable();
            $table->enum('type', ['system', 'custom'])->default('custom');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Benefits packages
        Schema::create('benefits_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('benefits');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Offer letters
        Schema::create('offer_letters', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_id')->nullable()->constrained('job_listings')->nullOnDelete();
            $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('offer_letter_templates')->nullOnDelete();
            $table->foreignId('benefits_package_id')->nullable()->constrained('benefits_packages')->nullOnDelete();
            
            // Position details
            $table->string('job_title');
            $table->string('department')->nullable();
            $table->string('employment_type')->default('full-time');
            $table->string('work_location')->nullable();
            $table->enum('work_arrangement', ['on-site', 'remote', 'hybrid'])->default('on-site');
            $table->string('reporting_to')->nullable();
            
            // Compensation
            $table->decimal('base_salary', 12, 2);
            $table->enum('salary_period', ['hourly', 'weekly', 'bi-weekly', 'monthly', 'annually'])->default('annually');
            $table->string('currency', 3)->default('USD');
            $table->decimal('signing_bonus', 12, 2)->nullable();
            $table->decimal('annual_bonus_target', 5, 2)->nullable();
            $table->text('bonus_structure')->nullable();
            
            // Equity
            $table->integer('equity_shares')->nullable();
            $table->string('equity_type')->nullable();
            $table->text('vesting_schedule')->nullable();
            
            // Dates
            $table->date('start_date');
            $table->date('offer_expiry_date');
            $table->date('response_deadline')->nullable();
            
            // Content
            $table->longText('letter_content');
            $table->json('custom_terms')->nullable();
            $table->text('special_conditions')->nullable();
            
            // Status tracking
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'sent',
                'viewed',
                'under_review',
                'accepted',
                'declined',
                'counter_offered',
                'withdrawn',
                'expired'
            ])->default('draft');
            
            // Signature tracking
            $table->string('signature_provider')->nullable();
            $table->string('signature_document_id')->nullable();
            $table->string('signature_status')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            
            // Candidate response
            $table->text('decline_reason')->nullable();
            $table->text('candidate_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['candidate_id', 'status']);
            $table->index('status');
        });

        // Counter offers
        Schema::create('counter_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_letter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('initiated_by')->constrained('users')->cascadeOnDelete();
            $table->integer('round_number')->default(1);
            
            // Requested changes
            $table->decimal('requested_salary', 12, 2)->nullable();
            $table->decimal('requested_signing_bonus', 12, 2)->nullable();
            $table->date('requested_start_date')->nullable();
            $table->integer('requested_equity_shares')->nullable();
            $table->text('requested_benefits')->nullable();
            $table->text('other_requests')->nullable();
            $table->text('justification')->nullable();
            
            // Employer response
            $table->enum('status', ['pending', 'accepted', 'partially_accepted', 'rejected', 'withdrawn'])->default('pending');
            $table->decimal('counter_salary', 12, 2)->nullable();
            $table->decimal('counter_signing_bonus', 12, 2)->nullable();
            $table->date('counter_start_date')->nullable();
            $table->integer('counter_equity_shares')->nullable();
            $table->text('counter_benefits')->nullable();
            $table->text('employer_response')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            
            $table->timestamps();

            $table->index(['offer_letter_id', 'round_number']);
        });

        // Offer comparisons (for candidates)
        Schema::create('offer_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->json('offer_ids');
            $table->json('comparison_criteria')->nullable();
            $table->json('notes')->nullable();
            $table->timestamps();
        });

        // Offer letter activity log
        Schema::create('offer_letter_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_letter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['offer_letter_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_letter_activities');
        Schema::dropIfExists('offer_comparisons');
        Schema::dropIfExists('counter_offers');
        Schema::dropIfExists('offer_letters');
        Schema::dropIfExists('benefits_packages');
        Schema::dropIfExists('offer_letter_templates');
    }
};
