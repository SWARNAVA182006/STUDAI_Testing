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
        // Push subscriptions for web push notifications
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('endpoint', 500)->unique();
            $table->text('public_key');
            $table->text('auth_token');
            $table->string('content_encoding')->default('aes128gcm');
            $table->string('user_agent')->nullable();
            $table->string('device_type', 50)->nullable(); // desktop, mobile, tablet
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('last_used_at');
        });

        // Notification preferences
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('channel', 50); // push, email, sms
            $table->string('notification_type', 100); // job_alert, application_status, interview_reminder, etc.
            $table->boolean('enabled')->default(true);
            $table->json('settings')->nullable(); // Additional settings like frequency, quiet hours
            $table->timestamps();

            $table->unique(['user_id', 'channel', 'notification_type'], 'notif_prefs_user_chan_type_unique');
            $table->index(['user_id', 'enabled']);
        });

        // Sent notifications log
        Schema::create('sent_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('channel', 50); // push, email, sms
            $table->string('notification_type', 100);
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->enum('status', ['sent', 'failed', 'clicked', 'dismissed'])->default('sent');
            $table->timestamp('sent_at');
            $table->timestamp('clicked_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'sent_at']);
            $table->index(['notification_type', 'sent_at']);
            $table->index(['status', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sent_notifications');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('push_subscriptions');
    }
};
