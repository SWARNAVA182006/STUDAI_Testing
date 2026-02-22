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
        // Freelance/Contract Projects
        Schema::create('marketplace_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->text('deliverables')->nullable();
            $table->enum('project_type', ['fixed_price', 'hourly', 'milestone'])->default('fixed_price');
            $table->enum('category', [
                'web_development', 'mobile_development', 'design', 'writing',
                'marketing', 'data_science', 'ai_ml', 'devops', 'consulting',
                'video_production', 'audio_production', 'translation', 'legal',
                'finance', 'admin_support', 'customer_service', 'other'
            ])->default('other');
            $table->json('skills_required')->nullable();
            $table->decimal('budget_min', 12, 2)->nullable();
            $table->decimal('budget_max', 12, 2)->nullable();
            $table->decimal('hourly_rate_min', 10, 2)->nullable();
            $table->decimal('hourly_rate_max', 10, 2)->nullable();
            $table->string('currency', 10)->default('INR');
            $table->enum('experience_level', ['entry', 'intermediate', 'expert'])->default('intermediate');
            $table->integer('estimated_duration_days')->nullable();
            $table->enum('duration_type', ['days', 'weeks', 'months'])->default('weeks');
            $table->enum('status', ['draft', 'open', 'in_progress', 'completed', 'cancelled', 'disputed'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_urgent')->default(false);
            $table->boolean('allows_remote')->default(true);
            $table->string('location')->nullable();
            $table->integer('proposals_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->timestamp('deadline')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'category']);
            $table->index(['employer_id', 'status']);
            $table->index('published_at');
        });

        // Freelancer Profiles (extends user profiles for marketplace)
        Schema::create('freelancer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('professional_title');
            $table->text('bio');
            $table->text('overview')->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->string('currency', 10)->default('INR');
            $table->json('skills')->nullable();
            $table->json('languages')->nullable();
            $table->enum('experience_level', ['entry', 'intermediate', 'expert'])->default('intermediate');
            $table->enum('availability', ['full_time', 'part_time', 'hourly', 'not_available'])->default('full_time');
            $table->integer('hours_per_week')->nullable();
            $table->boolean('available_for_remote')->default(true);
            $table->boolean('available_for_onsite')->default(false);
            $table->string('preferred_project_size')->nullable(); // small, medium, large
            $table->decimal('total_earnings', 14, 2)->default(0);
            $table->integer('completed_projects')->default(0);
            $table->integer('ongoing_projects')->default(0);
            $table->decimal('success_rate', 5, 2)->default(100);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->json('portfolio')->nullable();
            $table->json('certifications')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['is_verified', 'average_rating']);
        });

        // Project Proposals/Bids
        Schema::create('marketplace_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('marketplace_projects')->cascadeOnDelete();
            $table->foreignId('freelancer_id')->constrained('users')->cascadeOnDelete();
            $table->text('cover_letter');
            $table->decimal('proposed_amount', 12, 2);
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->string('currency', 10)->default('INR');
            $table->integer('estimated_duration_days')->nullable();
            $table->json('milestones')->nullable(); // [{title, amount, duration, deliverables}]
            $table->text('relevant_experience')->nullable();
            $table->json('attachments')->nullable();
            $table->enum('status', ['pending', 'shortlisted', 'accepted', 'rejected', 'withdrawn'])->default('pending');
            $table->boolean('is_boosted')->default(false);
            $table->timestamp('boosted_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'freelancer_id']);
            $table->index(['project_id', 'status']);
            $table->index(['freelancer_id', 'status']);
        });

        // Project Contracts (when proposal is accepted)
        Schema::create('marketplace_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->unique();
            $table->foreignId('project_id')->constrained('marketplace_projects')->cascadeOnDelete();
            $table->foreignId('proposal_id')->constrained('marketplace_proposals')->cascadeOnDelete();
            $table->foreignId('employer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('freelancer_id')->constrained('users')->cascadeOnDelete();
            $table->text('terms');
            $table->decimal('total_amount', 14, 2);
            $table->decimal('platform_fee_percent', 5, 2)->default(10);
            $table->decimal('platform_fee_amount', 12, 2)->default(0);
            $table->decimal('freelancer_amount', 14, 2);
            $table->string('currency', 10)->default('INR');
            $table->enum('payment_type', ['fixed', 'hourly', 'milestone'])->default('fixed');
            $table->enum('status', ['pending', 'active', 'paused', 'completed', 'cancelled', 'disputed'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['employer_id', 'status']);
            $table->index(['freelancer_id', 'status']);
        });

        // Project Milestones
        Schema::create('marketplace_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('marketplace_contracts')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('deliverables')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('INR');
            $table->integer('order')->default(0);
            $table->enum('status', ['pending', 'funded', 'in_progress', 'submitted', 'revision_requested', 'approved', 'released', 'disputed'])->default('pending');
            $table->timestamp('due_date')->nullable();
            $table->timestamp('funded_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->text('submission_note')->nullable();
            $table->json('submission_files')->nullable();
            $table->text('revision_note')->nullable();
            $table->integer('revision_count')->default(0);
            $table->timestamps();

            $table->index(['contract_id', 'status']);
        });

        // Escrow Transactions
        Schema::create('marketplace_escrow', function (Blueprint $table) {
            $table->id();
            $table->string('escrow_id')->unique();
            $table->foreignId('contract_id')->constrained('marketplace_contracts')->cascadeOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained('marketplace_milestones')->nullOnDelete();
            $table->foreignId('payer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('payee_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->decimal('platform_fee', 12, 2)->default(0);
            $table->decimal('net_amount', 14, 2);
            $table->string('currency', 10)->default('INR');
            $table->enum('status', ['pending', 'funded', 'held', 'released', 'refunded', 'disputed'])->default('pending');
            $table->string('payment_gateway')->nullable();
            $table->string('payment_transaction_id')->nullable();
            $table->string('payout_transaction_id')->nullable();
            $table->timestamp('funded_at')->nullable();
            $table->timestamp('held_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('release_note')->nullable();
            $table->text('refund_reason')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'status']);
            $table->index(['payer_id', 'status']);
            $table->index(['payee_id', 'status']);
        });

        // Skill Verification Badges
        Schema::create('skill_badges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color', 20)->default('#4F46E5');
            $table->enum('level', ['beginner', 'intermediate', 'advanced', 'expert'])->default('intermediate');
            $table->enum('category', ['technical', 'soft_skill', 'certification', 'platform', 'achievement'])->default('technical');
            $table->json('requirements')->nullable(); // what's needed to earn this badge
            $table->boolean('requires_assessment')->default(false);
            $table->boolean('requires_verification')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // User Skill Badges (earned badges)
        Schema::create('user_skill_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained('skill_badges')->cascadeOnDelete();
            $table->enum('status', ['pending', 'verified', 'expired', 'revoked'])->default('pending');
            $table->text('verification_evidence')->nullable();
            $table->string('verified_by')->nullable(); // 'system', 'admin', 'assessment'
            $table->integer('assessment_score')->nullable();
            $table->timestamp('earned_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'badge_id']);
            $table->index(['user_id', 'status']);
        });

        // Marketplace Reviews (for both employers and freelancers)
        Schema::create('marketplace_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('marketplace_contracts')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewee_id')->constrained('users')->cascadeOnDelete();
            $table->enum('reviewer_type', ['employer', 'freelancer']);
            $table->tinyInteger('overall_rating'); // 1-5
            $table->tinyInteger('communication_rating')->nullable();
            $table->tinyInteger('quality_rating')->nullable();
            $table->tinyInteger('timeliness_rating')->nullable();
            $table->tinyInteger('professionalism_rating')->nullable();
            $table->tinyInteger('value_rating')->nullable(); // for employer reviews
            $table->tinyInteger('cooperation_rating')->nullable(); // for freelancer reviews
            $table->text('review_text');
            $table->text('private_feedback')->nullable();
            $table->boolean('would_recommend')->default(true);
            $table->boolean('would_hire_again')->nullable(); // for employer reviews
            $table->json('skills_endorsed')->nullable();
            $table->enum('status', ['pending', 'published', 'hidden', 'disputed'])->default('pending');
            $table->text('employer_response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['contract_id', 'reviewer_id']);
            $table->index(['reviewee_id', 'status']);
        });

        // Employer Direct Outreach Messages
        Schema::create('marketplace_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable()->constrained('marketplace_contracts')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('marketplace_projects')->nullOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->enum('message_type', ['inquiry', 'proposal_discussion', 'contract', 'general'])->default('general');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sender_id', 'recipient_id']);
            $table->index(['contract_id', 'created_at']);
        });

        // Project Invitations (employer inviting freelancers)
        Schema::create('marketplace_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('marketplace_projects')->cascadeOnDelete();
            $table->foreignId('employer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('freelancer_id')->constrained('users')->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'viewed', 'accepted', 'declined', 'expired'])->default('pending');
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'freelancer_id']);
            $table->index(['freelancer_id', 'status']);
        });

        // Time Tracking (for hourly contracts)
        Schema::create('marketplace_time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('marketplace_contracts')->cascadeOnDelete();
            $table->foreignId('freelancer_id')->constrained('users')->cascadeOnDelete();
            $table->date('work_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('minutes_worked');
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('amount_earned', 12, 2);
            $table->text('description');
            $table->json('screenshots')->nullable(); // optional work verification
            $table->enum('status', ['pending', 'approved', 'disputed', 'paid'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'work_date']);
            $table->index(['freelancer_id', 'status']);
        });

        // Disputes
        Schema::create('marketplace_disputes', function (Blueprint $table) {
            $table->id();
            $table->string('dispute_number')->unique();
            $table->foreignId('contract_id')->constrained('marketplace_contracts')->cascadeOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained('marketplace_milestones')->nullOnDelete();
            $table->foreignId('raised_by_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('against_id')->constrained('users')->cascadeOnDelete();
            $table->enum('dispute_type', ['payment', 'quality', 'deadline', 'scope', 'communication', 'other'])->default('other');
            $table->text('description');
            $table->json('evidence')->nullable();
            $table->decimal('disputed_amount', 14, 2)->nullable();
            $table->enum('status', ['open', 'under_review', 'mediation', 'resolved', 'escalated', 'closed'])->default('open');
            $table->enum('resolution', ['refund_full', 'refund_partial', 'release_full', 'release_partial', 'split', 'dismissed'])->nullable();
            $table->decimal('resolution_amount', 14, 2)->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'status']);
        });

        // Saved Freelancers / Favorites
        Schema::create('saved_freelancers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('freelancer_id')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employer_id', 'freelancer_id']);
        });

        // Saved Projects
        Schema::create('saved_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freelancer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('marketplace_projects')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['freelancer_id', 'project_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_projects');
        Schema::dropIfExists('saved_freelancers');
        Schema::dropIfExists('marketplace_disputes');
        Schema::dropIfExists('marketplace_time_logs');
        Schema::dropIfExists('marketplace_invitations');
        Schema::dropIfExists('marketplace_messages');
        Schema::dropIfExists('marketplace_reviews');
        Schema::dropIfExists('user_skill_badges');
        Schema::dropIfExists('skill_badges');
        Schema::dropIfExists('marketplace_escrow');
        Schema::dropIfExists('marketplace_milestones');
        Schema::dropIfExists('marketplace_contracts');
        Schema::dropIfExists('marketplace_proposals');
        Schema::dropIfExists('freelancer_profiles');
        Schema::dropIfExists('marketplace_projects');
    }
};
