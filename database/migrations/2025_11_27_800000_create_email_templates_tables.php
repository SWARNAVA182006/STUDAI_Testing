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
        // Email template categories
        if (!Schema::hasTable('email_template_categories')) {
            Schema::create('email_template_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Email templates
        if (!Schema::hasTable('email_templates')) {
            Schema::create('email_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained('email_template_categories')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('subject');
                $table->longText('body_html');
                $table->longText('body_text')->nullable();
                $table->json('variables')->nullable(); // Available template variables
                $table->json('default_values')->nullable(); // Default values for variables
                $table->enum('type', ['system', 'custom', 'ai_generated'])->default('custom');
                $table->enum('tone', ['professional', 'friendly', 'formal', 'casual'])->default('professional');
                $table->boolean('is_default')->default(false);
                $table->boolean('is_public')->default(false); // Shared with all users
                $table->boolean('is_active')->default(true);
                $table->integer('usage_count')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['category_id', 'is_active']);
                $table->index(['user_id', 'is_active']);
                $table->index(['company_id', 'is_active']);
                $table->index('type');
            });
        }

        // Template versions for tracking changes
        if (!Schema::hasTable('email_template_versions')) {
            Schema::create('email_template_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('template_id')->constrained('email_templates')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->integer('version_number');
                $table->string('subject');
                $table->longText('body_html');
                $table->longText('body_text')->nullable();
                $table->text('change_notes')->nullable();
                $table->timestamps();

                $table->unique(['template_id', 'version_number'], 'template_version_unique');
            });
        }

        // Sent emails tracking for analytics
        if (!Schema::hasTable('email_sends')) {
            Schema::create('email_sends', function (Blueprint $table) {
                $table->id();
                $table->foreignId('template_id')->nullable()->constrained('email_templates')->nullOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->string('recipient_email');
                $table->string('recipient_name')->nullable();
                $table->string('subject');
                $table->string('message_id')->nullable()->unique();
                $table->enum('status', ['queued', 'sent', 'delivered', 'opened', 'clicked', 'bounced', 'failed'])->default('queued');
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('clicked_at')->nullable();
                $table->integer('open_count')->default(0);
                $table->integer('click_count')->default(0);
                $table->json('metadata')->nullable(); // Additional tracking data
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index(['template_id', 'status']);
                $table->index(['user_id', 'created_at']);
                $table->index('recipient_email');
                $table->index('status');
            });
        }

        // Email template analytics aggregated data
        if (!Schema::hasTable('email_template_analytics')) {
            Schema::create('email_template_analytics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('template_id')->constrained('email_templates')->cascadeOnDelete();
                $table->date('date');
                $table->integer('sends')->default(0);
                $table->integer('deliveries')->default(0);
                $table->integer('opens')->default(0);
                $table->integer('unique_opens')->default(0);
                $table->integer('clicks')->default(0);
                $table->integer('unique_clicks')->default(0);
                $table->integer('bounces')->default(0);
                $table->integer('failures')->default(0);
                $table->decimal('open_rate', 5, 2)->default(0);
                $table->decimal('click_rate', 5, 2)->default(0);
                $table->timestamps();

                $table->unique(['template_id', 'date']);
                $table->index('date');
            });
        }

        // AI customization requests
        if (!Schema::hasTable('email_ai_customizations')) {
            Schema::create('email_ai_customizations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('template_id')->constrained('email_templates')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->text('original_content');
                $table->text('customized_content');
                $table->json('customization_params')->nullable(); // tone, length, focus areas
                $table->text('prompt_used')->nullable();
                $table->integer('tokens_used')->default(0);
                $table->boolean('was_accepted')->default(false);
                $table->timestamps();

                $table->index(['template_id', 'user_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_ai_customizations');
        Schema::dropIfExists('email_template_analytics');
        Schema::dropIfExists('email_sends');
        Schema::dropIfExists('email_template_versions');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('email_template_categories');
    }
};
