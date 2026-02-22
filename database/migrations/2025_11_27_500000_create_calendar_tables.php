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
        // User Calendar Connections - Stores OAuth tokens for each provider
        if (!Schema::hasTable('calendar_connections')) {
            Schema::create('calendar_connections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->enum('provider', ['google', 'outlook', 'apple']); // Calendar provider
                $table->string('provider_email')->nullable(); // Email used with provider
                $table->string('calendar_id')->nullable(); // Primary calendar ID
                $table->text('access_token'); // Encrypted OAuth token
                $table->text('refresh_token')->nullable(); // Encrypted refresh token
                $table->timestamp('token_expires_at')->nullable();
                $table->json('calendars')->nullable(); // List of available calendars
                $table->json('sync_settings')->nullable(); // Sync preferences
                $table->boolean('is_primary')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'provider', 'provider_email']);
                $table->index(['user_id', 'is_active']);
            });
        }

        // User Availability - Defines when users are available
        if (!Schema::hasTable('user_availabilities')) {
            Schema::create('user_availabilities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->tinyInteger('day_of_week'); // 0=Sunday, 6=Saturday
                $table->time('start_time');
                $table->time('end_time');
                $table->string('timezone')->default('UTC');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['user_id', 'day_of_week', 'is_active']);
            });
        }

        // Availability Overrides - Specific date exceptions
        if (!Schema::hasTable('availability_overrides')) {
            Schema::create('availability_overrides', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->date('date');
                $table->boolean('is_available')->default(false); // false = blocked, true = available
                $table->time('start_time')->nullable(); // null if whole day blocked
                $table->time('end_time')->nullable();
                $table->string('reason')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'date', 'start_time']);
            });
        }

        // Scheduled Events - Interviews, meetings, etc.
        if (!Schema::hasTable('scheduled_events')) {
            Schema::create('scheduled_events', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->enum('event_type', ['interview', 'meeting', 'call', 'other'])->default('interview');
                $table->dateTime('starts_at');
                $table->dateTime('ends_at');
                $table->string('timezone')->default('UTC');
                $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed', 'rescheduled'])->default('pending');
                $table->string('location')->nullable(); // Physical location if any
                $table->enum('meeting_type', ['in_person', 'video', 'phone'])->default('video');
                $table->string('meeting_link')->nullable(); // Zoom/Meet/Teams link
                $table->string('meeting_password')->nullable();
                $table->enum('meeting_provider', ['zoom', 'google_meet', 'teams', 'custom'])->nullable();
                $table->json('meeting_details')->nullable(); // Provider-specific data
                $table->json('metadata')->nullable(); // Additional event data
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['organizer_id', 'starts_at']);
                $table->index(['status', 'starts_at']);
            });
        }

        // Event Participants - Attendees of scheduled events
        if (!Schema::hasTable('event_participants')) {
            Schema::create('event_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained('scheduled_events')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('email'); // For external participants
                $table->string('name')->nullable();
                $table->enum('role', ['organizer', 'attendee', 'optional'])->default('attendee');
                $table->enum('status', ['pending', 'accepted', 'declined', 'tentative'])->default('pending');
                $table->timestamp('responded_at')->nullable();
                $table->text('response_note')->nullable();
                $table->timestamps();

                $table->unique(['event_id', 'email']);
                $table->index(['user_id', 'status']);
            });
        }

        // Calendar Sync Log - Track synced events
        if (!Schema::hasTable('calendar_sync_events')) {
            Schema::create('calendar_sync_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('connection_id')->constrained('calendar_connections')->cascadeOnDelete();
                $table->foreignId('event_id')->nullable()->constrained('scheduled_events')->nullOnDelete();
                $table->string('external_event_id'); // ID in external calendar
                $table->string('calendar_id'); // Which calendar it's synced to
                $table->enum('sync_direction', ['push', 'pull']); // Our event pushed vs pulled from calendar
                $table->enum('sync_status', ['synced', 'pending', 'failed'])->default('synced');
                $table->timestamp('last_synced_at');
                $table->json('sync_data')->nullable();
                $table->timestamps();

                $table->unique(['connection_id', 'external_event_id']);
            });
        }

        // Scheduling Links - Public booking links (like Calendly)
        if (!Schema::hasTable('scheduling_links')) {
            Schema::create('scheduling_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('slug')->unique(); // URL slug for booking
                $table->string('title');
                $table->text('description')->nullable();
                $table->integer('duration_minutes')->default(30);
                $table->integer('buffer_before')->default(0); // Minutes before meeting
                $table->integer('buffer_after')->default(0); // Minutes after meeting
                $table->integer('min_notice_hours')->default(24); // Minimum booking notice
                $table->integer('max_days_ahead')->default(60); // How far ahead can book
                $table->json('available_days')->nullable(); // Override weekly availability
                $table->json('questions')->nullable(); // Questions for booker
                $table->boolean('require_confirmation')->default(false);
                $table->enum('meeting_type', ['in_person', 'video', 'phone'])->default('video');
                $table->enum('meeting_provider', ['zoom', 'google_meet', 'teams', 'custom'])->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('bookings_count')->default(0);
                $table->timestamps();

                $table->index(['user_id', 'is_active']);
            });
        }

        // Event Reminders
        if (!Schema::hasTable('event_reminders')) {
            Schema::create('event_reminders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained('scheduled_events')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->integer('minutes_before');
                $table->enum('channel', ['email', 'push', 'sms'])->default('email');
                $table->boolean('is_sent')->default(false);
                $table->timestamp('scheduled_at');
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->index(['scheduled_at', 'is_sent']);
            });
        }

        // Interview Scheduling Requests - For job applications
        if (!Schema::hasTable('interview_requests')) {
            Schema::create('interview_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('application_id')->nullable(); // Optional - may not have job_applications table
                $table->foreignId('requested_by')->constrained('users'); // Employer/HR
                $table->foreignId('candidate_id')->constrained('users');
                $table->foreignId('event_id')->nullable()->constrained('scheduled_events')->nullOnDelete();
                $table->string('interview_type'); // phone, video, onsite, technical, etc.
                $table->integer('duration_minutes')->default(30);
                $table->json('proposed_times')->nullable(); // Multiple time slots offered
                $table->text('message')->nullable();
                $table->enum('status', ['pending', 'times_proposed', 'scheduled', 'cancelled'])->default('pending');
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(['candidate_id', 'status']);
                $table->index(['application_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_requests');
        Schema::dropIfExists('event_reminders');
        Schema::dropIfExists('scheduling_links');
        Schema::dropIfExists('calendar_sync_events');
        Schema::dropIfExists('event_participants');
        Schema::dropIfExists('scheduled_events');
        Schema::dropIfExists('availability_overrides');
        Schema::dropIfExists('user_availabilities');
        Schema::dropIfExists('calendar_connections');
    }
};
