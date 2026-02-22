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
        // Talent pool - saved candidates for future opportunities
        Schema::create('talent_pool', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Candidate
            $table->foreignId('added_by')->constrained('users')->onDelete('cascade'); // Recruiter
            $table->string('source')->nullable(); // application, search, referral, event
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->integer('rating')->nullable(); // 1-5 stars
            $table->timestamp('last_contacted_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['company_id', 'user_id']);
            $table->index('company_id');
            $table->index(['company_id', 'is_active']);
        });
        
        // Message conversations
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('candidate_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('job_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('application_id')->nullable()->constrained()->onDelete('set null');
            $table->string('subject')->nullable();
            $table->enum('status', ['active', 'archived', 'spam'])->default('active');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'status']);
            $table->index('candidate_id');
        });
        
        // Messages within conversations
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index('conversation_id');
            $table->index(['conversation_id', 'is_read']);
        });
        
        // Message templates
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->json('variables')->nullable(); // {{candidate_name}}, {{job_title}}, etc.
            $table->string('category')->nullable(); // rejection, interview_invite, offer, follow_up
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('company_id');
        });
        
        // Job templates for wizard
        Schema::create('job_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('category'); // engineering, marketing, sales, etc.
            $table->text('title_template');
            $table->text('description_template');
            $table->text('requirements_template')->nullable();
            $table->text('responsibilities_template')->nullable();
            $table->json('default_skills')->nullable();
            $table->boolean('is_public')->default(false); // Public templates available to all
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            
            $table->index('category');
            $table->index(['company_id', 'is_public']);
        });
        
        // Employee referrals
        Schema::create('employee_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade'); // Employee
            $table->foreignId('candidate_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('job_id')->constrained()->onDelete('cascade');
            $table->foreignId('application_id')->nullable()->constrained()->onDelete('set null');
            $table->string('candidate_name');
            $table->string('candidate_email');
            $table->string('candidate_phone')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'contacted', 'interviewing', 'hired', 'rejected'])->default('pending');
            $table->decimal('bonus_amount', 10, 2)->nullable();
            $table->enum('bonus_status', ['pending', 'approved', 'paid'])->nullable();
            $table->timestamp('hired_at')->nullable();
            $table->timestamp('bonus_paid_at')->nullable();
            $table->timestamps();
            
            $table->index('company_id');
            $table->index('referrer_id');
            $table->index('status');
        });
        
        // Referral program settings
        Schema::create('referral_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->decimal('default_bonus_amount', 10, 2)->default(0);
            $table->json('bonus_by_level')->nullable(); // Different bonuses for junior, senior, etc.
            $table->integer('probation_days')->default(90); // Days before bonus is paid
            $table->text('terms_and_conditions')->nullable();
            $table->integer('max_referrals_per_employee')->nullable();
            $table->boolean('auto_approve_bonus')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_settings');
        Schema::dropIfExists('employee_referrals');
        Schema::dropIfExists('job_templates');
        Schema::dropIfExists('message_templates');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('talent_pool');
    }
};
