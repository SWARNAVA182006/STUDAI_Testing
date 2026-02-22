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
        // Interview scheduling table
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->enum('interview_type', ['phone', 'video', 'in_person', 'technical', 'hr', 'final']);
            $table->dateTime('scheduled_at');
            $table->integer('duration_minutes')->default(60);
            $table->string('location')->nullable();
            $table->string('meeting_link')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'canceled', 'no_show'])->default('scheduled');
            $table->json('feedback')->nullable();
            $table->integer('rating')->nullable(); // 1-5 stars
            $table->text('interviewer_notes')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('canceled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();
            
            $table->index('scheduled_at');
            $table->index('status');
        });
        
        // Interview interviewers (many-to-many)
        Schema::create('interview_interviewer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Employer user
            $table->boolean('is_lead')->default(false);
            $table->json('availability')->nullable();
            $table->timestamps();
            
            $table->unique(['interview_id', 'user_id']);
        });
        
        // Application status history
        Schema::create('application_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->string('status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['application_id', 'created_at']);
        });
        
        // Application notes
        Schema::create('application_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->text('content');
            $table->boolean('is_private')->default(true);
            $table->timestamps();
            
            $table->index('application_id');
        });
        
        // Add missing columns to applications table
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('status');
            }
            if (!Schema::hasColumn('applications', 'status_updated_at')) {
                $table->timestamp('status_updated_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('applications', 'source')) {
                $table->string('source')->default('direct')->after('status'); // direct, referral, job_board, social_media
            }
        });
        
        // Team members table (for companies with multiple recruiters)
        Schema::create('company_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['owner', 'admin', 'recruiter', 'hiring_manager', 'viewer'])->default('recruiter');
            $table->json('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            
            $table->unique(['company_id', 'user_id']);
            $table->index('company_id');
        });
        
        // Job views tracking (for analytics)
        Schema::create('job_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->timestamp('viewed_at');
            
            $table->index(['job_id', 'viewed_at']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_views');
        Schema::dropIfExists('company_team_members');
        
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'is_archived')) {
                $table->dropColumn('is_archived');
            }
            if (Schema::hasColumn('applications', 'status_updated_at')) {
                $table->dropColumn('status_updated_at');
            }
            if (Schema::hasColumn('applications', 'source')) {
                $table->dropColumn('source');
            }
        });
        
        Schema::dropIfExists('application_notes');
        Schema::dropIfExists('application_status_history');
        Schema::dropIfExists('interview_interviewer');
        Schema::dropIfExists('interviews');
    }
};
