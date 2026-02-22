<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the job_embeddings table for storing vector embeddings
     * used in semantic search. For MySQL, embeddings are stored as JSON.
     * For PostgreSQL with pgvector, this can be migrated to native vector type.
     */
    public function up(): void
    {
        Schema::create('job_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_listing_id')->constrained('job_listings')->onDelete('cascade');
            $table->json('embedding');
            $table->string('model_version')->default('text-embedding-3-large');
            $table->integer('dimensions')->default(1536);
            $table->string('content_hash', 64)->nullable();
            $table->timestamps();

            $table->unique('job_listing_id');
            $table->index('model_version');
            $table->index('content_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_embeddings');
    }
};
