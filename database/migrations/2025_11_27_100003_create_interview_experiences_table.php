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
        if (!Schema::hasTable('interview_experiences')) {
            Schema::create('interview_experiences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();

                // Job Information
                $table->string('job_title');
                $table->string('department')->nullable();
                $table->string('location')->nullable();

                // Interview Details
                $table->enum('application_method', ['online', 'recruiter', 'referral', 'career_fair', 'campus', 'other'])->nullable();
                $table->date('interview_date')->nullable();
                $table->enum('interview_duration', ['less_than_1_hour', '1_2_hours', '2_4_hours', 'half_day', 'full_day', 'multiple_days'])->nullable();
                $table->json('interview_stages')->nullable();
                $table->unsignedTinyInteger('num_interviews')->nullable();

                // Experience Rating
                $table->enum('experience', ['positive', 'neutral', 'negative']);
                $table->enum('difficulty', ['easy', 'average', 'difficult', 'very_difficult'])->nullable();

                // Outcome
                $table->enum('outcome', ['got_offer', 'declined_offer', 'no_offer', 'pending', 'withdrew'])->nullable();
                $table->boolean('accepted_offer')->nullable();
                $table->decimal('offered_salary', 12, 2)->nullable();
                $table->string('currency', 3)->default('USD');

                // Content
                $table->text('interview_process')->nullable();
                $table->json('interview_questions')->nullable();
                $table->text('preparation_tips')->nullable();
                $table->text('advice_for_candidates')->nullable();

                // Verification & Status
                $table->boolean('is_verified')->default(false);
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->boolean('is_anonymous')->default(true);

                // Engagement
                $table->unsignedInteger('helpful_count')->default(0);
                $table->unsignedInteger('view_count')->default(0);

                $table->timestamps();
                $table->softDeletes();

                // Indexes
                $table->index(['company_id', 'status']);
                $table->index(['company_id', 'job_title']);
                $table->index(['experience']);
                $table->index(['outcome']);
            });
        }

        // Create interview experience votes table
        if (!Schema::hasTable('interview_experience_votes')) {
            Schema::create('interview_experience_votes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('interview_experience_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_helpful')->default(true);
                $table->timestamps();

                $table->unique(['interview_experience_id', 'user_id'], 'interview_exp_votes_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_experience_votes');
        Schema::dropIfExists('interview_experiences');
    }
};
