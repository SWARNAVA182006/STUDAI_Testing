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
        // Search history table
        Schema::create('search_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('query');
            $table->integer('results_count')->default(0);
            $table->timestamp('created_at')->nullable();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['query', 'created_at']);
        });
        
        // Saved jobs (bookmarks)
        Schema::create('job_user_saved', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('notes')->nullable(); // User notes about why they saved it
            $table->timestamp('created_at')->nullable();
            
            $table->unique(['job_id', 'user_id']);
            $table->index(['user_id', 'created_at']);
        });
        
        // Saved searches (for quick access to favorite searches)
        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // User-friendly name
            $table->string('query')->nullable();
            $table->json('filters')->nullable(); // Saved filter settings
            $table->boolean('notify_on_new')->default(false); // Alert when new matches
            $table->integer('last_result_count')->default(0);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_searches');
        Schema::dropIfExists('job_user_saved');
        Schema::dropIfExists('search_history');
    }
};
