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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('job_listings')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('application_number')->unique();
            $table->text('cover_letter')->nullable();
            $table->string('resume_file')->nullable();
            $table->json('answers')->nullable(); // Screening questions
            $table->enum('status', [
                'draft', 'submitted', 'viewed', 'shortlisted', 
                'interview_scheduled', 'interviewed', 'offered', 
                'accepted', 'rejected', 'withdrawn'
            ])->default('draft');
            $table->integer('match_score')->nullable(); // AI match percentage
            $table->json('match_analysis')->nullable(); // Detailed match breakdown
            $table->json('timeline')->nullable(); // Status change history
            $table->text('notes')->nullable(); // Recruiter notes
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['job_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['job_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
