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
        // Connections table - for professional network connections
        if (!Schema::hasTable('connections')) {
            Schema::create('connections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
                $table->enum('status', ['pending', 'accepted', 'declined', 'blocked'])->default('pending');
                $table->text('message')->nullable(); // Optional connection request message
                $table->timestamp('connected_at')->nullable();
                $table->timestamps();
                
                $table->unique(['requester_id', 'recipient_id']);
                $table->index(['recipient_id', 'status']);
                $table->index(['requester_id', 'status']);
            });
        }

        // User posts table - for activity feed content
        if (!Schema::hasTable('user_posts')) {
            Schema::create('user_posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->text('content');
                $table->json('media')->nullable(); // Array of media URLs
                $table->enum('type', ['text', 'image', 'video', 'article', 'job_update', 'milestone', 'skill_achievement'])->default('text');
                $table->enum('visibility', ['public', 'connections', 'private'])->default('connections');
                $table->unsignedInteger('likes_count')->default(0);
                $table->unsignedInteger('comments_count')->default(0);
                $table->unsignedInteger('shares_count')->default(0);
                $table->unsignedInteger('views_count')->default(0);
                $table->foreignId('original_post_id')->nullable()->constrained('user_posts')->nullOnDelete(); // For shares/reposts
                $table->json('metadata')->nullable(); // For job updates, milestones, etc.
                $table->boolean('is_pinned')->default(false);
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['user_id', 'created_at']);
                $table->index(['visibility', 'created_at']);
                $table->index('type');
            });
        }

        // Post likes table
        if (!Schema::hasTable('post_likes')) {
            Schema::create('post_likes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('post_id')->constrained('user_posts')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->enum('reaction_type', ['like', 'celebrate', 'support', 'love', 'insightful', 'curious'])->default('like');
                $table->timestamps();
                
                $table->unique(['post_id', 'user_id']);
                $table->index('user_id');
            });
        }

        // Post comments table
        if (!Schema::hasTable('post_comments')) {
            Schema::create('post_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('post_id')->constrained('user_posts')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->text('content');
                $table->foreignId('parent_id')->nullable()->constrained('post_comments')->cascadeOnDelete();
                $table->unsignedInteger('likes_count')->default(0);
                $table->unsignedInteger('replies_count')->default(0);
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['post_id', 'created_at']);
                $table->index('parent_id');
            });
        }

        // Comment likes table
        if (!Schema::hasTable('comment_likes')) {
            Schema::create('comment_likes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('comment_id')->constrained('post_comments')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                
                $table->unique(['comment_id', 'user_id']);
            });
        }

        // Groups table - for industry communities
        if (!Schema::hasTable('groups')) {
            Schema::create('groups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('cover_image')->nullable();
                $table->string('icon')->nullable();
                $table->string('industry')->nullable();
                $table->json('topics')->nullable(); // Array of topic tags
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->unsignedInteger('member_count')->default(0);
                $table->unsignedInteger('post_count')->default(0);
                $table->boolean('is_private')->default(false);
                $table->boolean('requires_approval')->default(false);
                $table->boolean('is_featured')->default(false);
                $table->json('rules')->nullable(); // Group rules
                $table->json('settings')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->index('industry');
                $table->index('is_private');
                $table->index('is_featured');
                $table->fullText(['name', 'description']);
            });
        }

        // Group members table
        if (!Schema::hasTable('group_members')) {
            Schema::create('group_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->enum('role', ['member', 'moderator', 'admin', 'owner'])->default('member');
                $table->enum('status', ['pending', 'approved', 'rejected', 'banned'])->default('approved');
                $table->timestamp('joined_at')->nullable();
                $table->boolean('notifications_enabled')->default(true);
                $table->timestamps();
                
                $table->unique(['group_id', 'user_id']);
                $table->index(['user_id', 'status']);
            });
        }

        // Group posts table
        if (!Schema::hasTable('group_posts')) {
            Schema::create('group_posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->text('content');
                $table->json('media')->nullable();
                $table->enum('type', ['discussion', 'question', 'poll', 'announcement', 'job', 'event'])->default('discussion');
                $table->unsignedInteger('likes_count')->default(0);
                $table->unsignedInteger('comments_count')->default(0);
                $table->boolean('is_pinned')->default(false);
                $table->boolean('is_approved')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['group_id', 'created_at']);
                $table->index(['group_id', 'is_pinned']);
            });
        }

        // Network Conversations table - for direct messaging (separate from employer conversations)
        if (!Schema::hasTable('network_conversations')) {
            Schema::create('network_conversations', function (Blueprint $table) {
                $table->id();
                $table->enum('type', ['direct', 'group'])->default('direct');
                $table->string('name')->nullable(); // For group conversations
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();
                
                $table->index('last_message_at');
            });
        }

        // Conversation participants table
        if (!Schema::hasTable('network_conversation_participants')) {
            Schema::create('network_conversation_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('network_conversations')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamp('last_read_at')->nullable();
                $table->boolean('is_muted')->default(false);
                $table->boolean('is_archived')->default(false);
                $table->boolean('is_pinned')->default(false);
                $table->timestamps();
                
                $table->unique(['conversation_id', 'user_id']);
                $table->index('user_id');
            });
        }

        // Network Messages table
        if (!Schema::hasTable('network_messages')) {
            Schema::create('network_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('network_conversations')->cascadeOnDelete();
                $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
                $table->text('content')->nullable();
                $table->enum('type', ['text', 'image', 'file', 'voice', 'job_share', 'profile_share', 'system'])->default('text');
                $table->json('attachments')->nullable();
                $table->json('metadata')->nullable(); // For job shares, etc.
                $table->foreignId('reply_to_id')->nullable();
                $table->json('reactions')->nullable();
                $table->boolean('is_edited')->default(false);
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['conversation_id', 'created_at']);
                $table->index('sender_id');
            });
        }

        // Message read receipts
        if (!Schema::hasTable('network_message_reads')) {
            Schema::create('network_message_reads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('message_id')->constrained('network_messages')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamp('read_at');
                
                $table->unique(['message_id', 'user_id']);
            });
        }

        // Events table - for networking events
        if (!Schema::hasTable('network_events')) {
            Schema::create('network_events', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('cover_image')->nullable();
                $table->enum('type', ['virtual', 'in_person', 'hybrid'])->default('virtual');
                $table->string('location')->nullable();
                $table->string('virtual_link')->nullable();
                $table->timestamp('starts_at');
                $table->timestamp('ends_at')->nullable();
                $table->string('timezone')->default('UTC');
                $table->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('group_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedInteger('capacity')->nullable();
                $table->unsignedInteger('attendee_count')->default(0);
                $table->boolean('requires_approval')->default(false);
                $table->boolean('is_featured')->default(false);
                $table->json('tags')->nullable();
                $table->json('settings')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->index('starts_at');
                $table->index(['organizer_id', 'starts_at']);
            });
        }

        // Event RSVPs
        if (!Schema::hasTable('event_rsvps')) {
            Schema::create('event_rsvps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained('network_events')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->enum('status', ['going', 'interested', 'not_going'])->default('going');
                $table->text('note')->nullable();
                $table->timestamps();
                
                $table->unique(['event_id', 'user_id']);
                $table->index(['user_id', 'status']);
            });
        }

        // Mentorship table
        if (!Schema::hasTable('mentorships')) {
            Schema::create('mentorships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mentor_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('mentee_id')->constrained('users')->cascadeOnDelete();
                $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])->default('pending');
                $table->json('focus_areas')->nullable(); // Skills/topics to focus on
                $table->text('goals')->nullable();
                $table->unsignedInteger('sessions_completed')->default(0);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->decimal('match_score', 5, 2)->nullable(); // AI-calculated match score
                $table->json('metadata')->nullable();
                $table->timestamps();
                
                $table->unique(['mentor_id', 'mentee_id']);
                $table->index(['mentor_id', 'status']);
                $table->index(['mentee_id', 'status']);
            });
        }

        // Mentor profiles table - for users who want to be mentors
        if (!Schema::hasTable('mentor_profiles')) {
            Schema::create('mentor_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->text('bio')->nullable();
                $table->json('expertise_areas')->nullable();
                $table->json('industries')->nullable();
                $table->unsignedInteger('years_experience')->nullable();
                $table->unsignedInteger('max_mentees')->default(3);
                $table->unsignedInteger('current_mentees')->default(0);
                $table->boolean('is_accepting')->default(true);
                $table->enum('availability', ['low', 'medium', 'high'])->default('medium');
                $table->decimal('rating', 3, 2)->nullable();
                $table->unsignedInteger('reviews_count')->default(0);
                $table->boolean('is_verified')->default(false);
                $table->boolean('is_featured')->default(false);
                $table->timestamps();
                
                $table->index('is_accepting');
                $table->index('is_featured');
            });
        }

        // User followers (for following without connecting)
        if (!Schema::hasTable('user_follows')) {
            Schema::create('user_follows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('following_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                
                $table->unique(['follower_id', 'following_id']);
                $table->index('following_id');
            });
        }

        // Hashtags table
        if (!Schema::hasTable('hashtags')) {
            Schema::create('hashtags', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->unsignedInteger('posts_count')->default(0);
                $table->boolean('is_trending')->default(false);
                $table->timestamps();
                
                $table->index('is_trending');
            });
        }

        // Post hashtags pivot table
        if (!Schema::hasTable('post_hashtag')) {
            Schema::create('post_hashtag', function (Blueprint $table) {
                $table->foreignId('post_id')->constrained('user_posts')->cascadeOnDelete();
                $table->foreignId('hashtag_id')->constrained()->cascadeOnDelete();
                
                $table->primary(['post_id', 'hashtag_id']);
            });
        }

        // Notifications preferences for network
        if (!Schema::hasTable('network_notification_settings')) {
            Schema::create('network_notification_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->boolean('connection_requests')->default(true);
                $table->boolean('connection_accepted')->default(true);
                $table->boolean('messages')->default(true);
                $table->boolean('mentions')->default(true);
                $table->boolean('post_likes')->default(true);
                $table->boolean('post_comments')->default(true);
                $table->boolean('group_invites')->default(true);
                $table->boolean('event_invites')->default(true);
                $table->boolean('event_reminders')->default(true);
                $table->boolean('mentorship_requests')->default(true);
                $table->boolean('weekly_digest')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_notification_settings');
        Schema::dropIfExists('post_hashtag');
        Schema::dropIfExists('hashtags');
        Schema::dropIfExists('user_follows');
        Schema::dropIfExists('mentor_profiles');
        Schema::dropIfExists('mentorships');
        Schema::dropIfExists('event_rsvps');
        Schema::dropIfExists('network_events');
        Schema::dropIfExists('network_message_reads');
        Schema::dropIfExists('network_messages');
        Schema::dropIfExists('network_conversation_participants');
        Schema::dropIfExists('network_conversations');
        Schema::dropIfExists('group_posts');
        Schema::dropIfExists('group_members');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('comment_likes');
        Schema::dropIfExists('post_comments');
        Schema::dropIfExists('post_likes');
        Schema::dropIfExists('user_posts');
        Schema::dropIfExists('connections');
    }
};
