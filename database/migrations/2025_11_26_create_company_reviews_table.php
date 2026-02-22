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
        Schema::create('company_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->integer('rating'); // 1-5 stars
            $table->text('review_text');
            $table->string('position'); // Job title when worked there
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'internship']);
            $table->text('pros')->nullable();
            $table->text('cons')->nullable();
            $table->text('advice_to_management')->nullable();
            $table->boolean('is_verified')->default(false); // Verified employee
            $table->integer('helpful_count')->default(0);
            $table->timestamps();
            
            $table->unique(['user_id', 'company_id']); // One review per user per company
            $table->index(['company_id', 'rating']);
            $table->index(['company_id', 'created_at']);
        });
        
        // Table to track who marked reviews as helpful
        Schema::create('review_helpful', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('company_reviews')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('created_at')->nullable();
            
            $table->unique(['review_id', 'user_id']);
        });
        
        // Table for company followers
        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('created_at')->nullable();
            
            $table->unique(['company_id', 'user_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_helpful');
        Schema::dropIfExists('company_reviews');
        Schema::dropIfExists('company_user');
    }
};
