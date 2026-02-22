<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Connection;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Hashtag;
use App\Models\NetworkConversation;
use App\Models\NetworkMessage;
use App\Models\PostComment;
use App\Models\PostLike;
use App\Models\User;
use App\Models\UserFollow;
use App\Models\UserPost;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NetworkingService
{
    // ============================================
    // CONNECTION MANAGEMENT
    // ============================================

    /**
     * Send a connection request to another user
     */
    public function sendConnectionRequest(
        User $sender,
        User $recipient,
        ?string $message = null
    ): Connection {
        // Check if connection already exists
        $existing = Connection::where(function ($query) use ($sender, $recipient) {
            $query->where('sender_id', $sender->id)
                ->where('recipient_id', $recipient->id);
        })->orWhere(function ($query) use ($sender, $recipient) {
            $query->where('sender_id', $recipient->id)
                ->where('recipient_id', $sender->id);
        })->first();

        if ($existing) {
            if ($existing->status === Connection::STATUS_BLOCKED) {
                throw new \Exception('Unable to send connection request');
            }

            if ($existing->status === Connection::STATUS_PENDING) {
                throw new \Exception('Connection request already pending');
            }

            if ($existing->status === Connection::STATUS_ACCEPTED) {
                throw new \Exception('Already connected');
            }
        }

        return Connection::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'message' => $message,
            'status' => Connection::STATUS_PENDING,
        ]);
    }

    /**
     * Accept a connection request
     */
    public function acceptConnection(Connection $connection, User $user): Connection
    {
        if ($connection->recipient_id !== $user->id) {
            throw new \Exception('Only the recipient can accept this request');
        }

        $connection->accept();

        // Auto-follow each other
        $this->followUser($connection->sender, $connection->recipient);
        $this->followUser($connection->recipient, $connection->sender);

        // Clear cache
        $this->clearConnectionCache($connection->sender_id);
        $this->clearConnectionCache($connection->recipient_id);

        return $connection;
    }

    /**
     * Reject a connection request
     */
    public function rejectConnection(Connection $connection, User $user): Connection
    {
        if ($connection->recipient_id !== $user->id) {
            throw new \Exception('Only the recipient can reject this request');
        }

        $connection->reject();

        return $connection;
    }

    /**
     * Remove an existing connection
     */
    public function removeConnection(Connection $connection, User $user): void
    {
        if ($connection->sender_id !== $user->id && $connection->recipient_id !== $user->id) {
            throw new \Exception('You are not part of this connection');
        }

        // Unfollow each other
        UserFollow::where('follower_id', $connection->sender_id)
            ->where('following_id', $connection->recipient_id)
            ->delete();
        UserFollow::where('follower_id', $connection->recipient_id)
            ->where('following_id', $connection->sender_id)
            ->delete();

        $connection->delete();

        $this->clearConnectionCache($connection->sender_id);
        $this->clearConnectionCache($connection->recipient_id);
    }

    /**
     * Block a user
     */
    public function blockUser(User $blocker, User $blocked): void
    {
        // Find or create connection and set to blocked
        $connection = Connection::where(function ($query) use ($blocker, $blocked) {
            $query->where('sender_id', $blocker->id)
                ->where('recipient_id', $blocked->id);
        })->orWhere(function ($query) use ($blocker, $blocked) {
            $query->where('sender_id', $blocked->id)
                ->where('recipient_id', $blocker->id);
        })->first();

        if ($connection) {
            $connection->block();
        } else {
            Connection::create([
                'sender_id' => $blocker->id,
                'recipient_id' => $blocked->id,
                'status' => Connection::STATUS_BLOCKED,
            ]);
        }

        // Remove follows
        UserFollow::where('follower_id', $blocker->id)
            ->where('following_id', $blocked->id)
            ->delete();
        UserFollow::where('follower_id', $blocked->id)
            ->where('following_id', $blocker->id)
            ->delete();
    }

    /**
     * Get user's connections
     */
    public function getConnections(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return Connection::forUser($user->id)
            ->accepted()
            ->with(['sender', 'recipient'])
            ->orderByDesc('connected_at')
            ->paginate($perPage);
    }

    /**
     * Get pending connection requests
     */
    public function getPendingRequests(User $user): Collection
    {
        return Connection::where('recipient_id', $user->id)
            ->pending()
            ->with('sender')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get sent connection requests
     */
    public function getSentRequests(User $user): Collection
    {
        return Connection::where('sender_id', $user->id)
            ->pending()
            ->with('recipient')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get mutual connections between two users
     */
    public function getMutualConnections(User $user1, User $user2): Collection
    {
        $user1ConnectionIds = $this->getConnectionIds($user1);
        $user2ConnectionIds = $this->getConnectionIds($user2);

        $mutualIds = array_intersect($user1ConnectionIds, $user2ConnectionIds);

        return User::whereIn('id', $mutualIds)->get();
    }

    /**
     * Get connection degree between two users (1st, 2nd, 3rd, or none)
     */
    public function getConnectionDegree(User $user1, User $user2): int
    {
        if ($user1->id === $user2->id) {
            return 0;
        }

        // Check 1st degree (direct connection)
        $directConnection = Connection::where(function ($query) use ($user1, $user2) {
            $query->where('sender_id', $user1->id)
                ->where('recipient_id', $user2->id);
        })->orWhere(function ($query) use ($user1, $user2) {
            $query->where('sender_id', $user2->id)
                ->where('recipient_id', $user1->id);
        })->where('status', Connection::STATUS_ACCEPTED)->exists();

        if ($directConnection) {
            return 1;
        }

        // Check 2nd degree (have mutual connections)
        $mutualConnections = $this->getMutualConnections($user1, $user2);
        if ($mutualConnections->count() > 0) {
            return 2;
        }

        // Check 3rd degree (connection of connection's connection)
        $user1Connections = $this->getConnectionIds($user1);
        foreach ($user1Connections as $connectionId) {
            $connectionUser = User::find($connectionId);
            if ($connectionUser) {
                $secondDegreeConnections = $this->getConnectionIds($connectionUser);
                foreach ($secondDegreeConnections as $secondConnectionId) {
                    if ($this->areConnected($secondConnectionId, $user2->id)) {
                        return 3;
                    }
                }
            }
        }

        return 0; // No connection within 3 degrees
    }

    /**
     * Check if two users are directly connected
     */
    public function areConnected(int $userId1, int $userId2): bool
    {
        return Connection::where(function ($query) use ($userId1, $userId2) {
            $query->where('sender_id', $userId1)
                ->where('recipient_id', $userId2);
        })->orWhere(function ($query) use ($userId1, $userId2) {
            $query->where('sender_id', $userId2)
                ->where('recipient_id', $userId1);
        })->where('status', Connection::STATUS_ACCEPTED)->exists();
    }

    /**
     * Get IDs of user's connections
     */
    public function getConnectionIds(User $user): array
    {
        $cacheKey = "user_connections_{$user->id}";

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $sentConnections = Connection::where('sender_id', $user->id)
                ->where('status', Connection::STATUS_ACCEPTED)
                ->pluck('recipient_id')
                ->toArray();

            $receivedConnections = Connection::where('recipient_id', $user->id)
                ->where('status', Connection::STATUS_ACCEPTED)
                ->pluck('sender_id')
                ->toArray();

            return array_unique(array_merge($sentConnections, $receivedConnections));
        });
    }

    private function clearConnectionCache(int $userId): void
    {
        Cache::forget("user_connections_{$userId}");
    }

    // ============================================
    // FOLLOW SYSTEM
    // ============================================

    /**
     * Follow a user
     */
    public function followUser(User $follower, User $following): UserFollow
    {
        if ($follower->id === $following->id) {
            throw new \Exception('Cannot follow yourself');
        }

        return UserFollow::firstOrCreate([
            'follower_id' => $follower->id,
            'following_id' => $following->id,
        ]);
    }

    /**
     * Unfollow a user
     */
    public function unfollowUser(User $follower, User $following): void
    {
        UserFollow::where('follower_id', $follower->id)
            ->where('following_id', $following->id)
            ->delete();
    }

    /**
     * Check if user is following another
     */
    public function isFollowing(User $follower, User $following): bool
    {
        return UserFollow::where('follower_id', $follower->id)
            ->where('following_id', $following->id)
            ->exists();
    }

    // ============================================
    // POSTS & ACTIVITY FEED
    // ============================================

    /**
     * Create a new post
     */
    public function createPost(
        User $author,
        string $content,
        string $visibility = 'connections',
        ?array $media = null,
        ?int $groupId = null,
        ?int $sharedPostId = null
    ): UserPost {
        // Extract and sync hashtags
        $hashtags = $this->extractHashtags($content);

        $post = UserPost::create([
            'user_id' => $author->id,
            'content' => $content,
            'visibility' => $visibility,
            'media' => $media,
            'group_id' => $groupId,
            'shared_post_id' => $sharedPostId,
        ]);

        // Sync hashtags
        $this->syncHashtags($post, $hashtags);

        // Update shares count if this is a share
        if ($sharedPostId) {
            UserPost::where('id', $sharedPostId)->increment('shares_count');
        }

        return $post;
    }

    /**
     * Get activity feed for a user
     */
    public function getActivityFeed(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $connectionIds = $this->getConnectionIds($user);
        $followingIds = $user->following()->pluck('following_id')->toArray();
        $groupIds = $user->groupMemberships()
            ->where('status', 'active')
            ->pluck('group_id')
            ->toArray();

        // Combine all relevant user IDs
        $relevantUserIds = array_unique(array_merge(
            $connectionIds,
            $followingIds,
            [$user->id]
        ));

        return UserPost::with(['author', 'sharedPost.author', 'group'])
            ->withCount(['likes', 'comments'])
            ->where(function ($query) use ($user, $relevantUserIds, $groupIds) {
                // Posts from connections/following with public/connections visibility
                $query->whereIn('user_id', $relevantUserIds)
                    ->whereIn('visibility', ['public', 'connections'])
                    ->whereNull('group_id');
            })
            ->orWhere(function ($query) use ($groupIds) {
                // Posts from user's groups
                $query->whereIn('group_id', $groupIds);
            })
            ->orWhere(function ($query) {
                // Public posts from anyone
                $query->where('visibility', 'public')
                    ->whereNull('group_id');
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Get user's posts
     */
    public function getUserPosts(User $user, ?User $viewer = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = UserPost::where('user_id', $user->id)
            ->with(['sharedPost.author'])
            ->withCount(['likes', 'comments'])
            ->whereNull('group_id');

        // Filter by visibility based on viewer relationship
        if (! $viewer || $viewer->id !== $user->id) {
            if ($viewer && $this->areConnected($user->id, $viewer->id)) {
                $query->whereIn('visibility', ['public', 'connections']);
            } else {
                $query->where('visibility', 'public');
            }
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Like a post
     */
    public function likePost(User $user, UserPost $post, string $type = 'like'): PostLike
    {
        $like = PostLike::updateOrCreate(
            [
                'user_id' => $user->id,
                'post_id' => $post->id,
            ],
            [
                'type' => $type,
            ]
        );

        // Update post likes count
        $post->update(['likes_count' => $post->likes()->count()]);

        return $like;
    }

    /**
     * Unlike a post
     */
    public function unlikePost(User $user, UserPost $post): void
    {
        PostLike::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->delete();

        $post->update(['likes_count' => $post->likes()->count()]);
    }

    /**
     * Comment on a post
     */
    public function commentOnPost(
        User $user,
        UserPost $post,
        string $content,
        ?int $parentId = null
    ): PostComment {
        $comment = PostComment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'parent_id' => $parentId,
            'content' => $content,
        ]);

        // Update post comments count
        $post->update(['comments_count' => $post->comments()->count()]);

        return $comment;
    }

    /**
     * Delete a comment
     */
    public function deleteComment(PostComment $comment, User $user): void
    {
        if ($comment->user_id !== $user->id && $comment->post->user_id !== $user->id) {
            throw new \Exception('Not authorized to delete this comment');
        }

        $post = $comment->post;
        $comment->delete();

        $post->update(['comments_count' => $post->comments()->count()]);
    }

    /**
     * Extract hashtags from content
     */
    private function extractHashtags(string $content): array
    {
        preg_match_all('/#(\w+)/', $content, $matches);

        return array_unique($matches[1] ?? []);
    }

    /**
     * Sync hashtags for a post
     */
    private function syncHashtags(UserPost $post, array $hashtags): void
    {
        $hashtagIds = [];

        foreach ($hashtags as $tag) {
            $hashtag = Hashtag::firstOrCreate(
                ['name' => strtolower($tag)],
                ['slug' => Str::slug($tag)]
            );
            $hashtagIds[] = $hashtag->id;
            $hashtag->increment('posts_count');
        }

        $post->hashtags()->sync($hashtagIds);
    }

    /**
     * Get trending hashtags
     */
    public function getTrendingHashtags(int $limit = 10): Collection
    {
        return Hashtag::orderByDesc('posts_count')
            ->limit($limit)
            ->get();
    }

    // ============================================
    // GROUPS
    // ============================================

    /**
     * Create a new group
     */
    public function createGroup(
        User $owner,
        string $name,
        ?string $description = null,
        string $privacy = 'public',
        ?string $industry = null,
        ?string $coverImage = null
    ): Group {
        $group = Group::create([
            'owner_id' => $owner->id,
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(6),
            'description' => $description,
            'privacy' => $privacy,
            'industry' => $industry,
            'cover_image' => $coverImage,
        ]);

        // Add owner as a member with owner role
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMember::ROLE_OWNER,
            'status' => GroupMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        return $group;
    }

    /**
     * Join a group
     */
    public function joinGroup(User $user, Group $group): GroupMember
    {
        $existingMembership = GroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingMembership) {
            if ($existingMembership->status === GroupMember::STATUS_BANNED) {
                throw new \Exception('You are banned from this group');
            }
            throw new \Exception('Already a member of this group');
        }

        $status = $group->privacy === 'private'
            ? GroupMember::STATUS_PENDING
            : GroupMember::STATUS_ACTIVE;

        $member = GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => GroupMember::ROLE_MEMBER,
            'status' => $status,
            'joined_at' => $status === GroupMember::STATUS_ACTIVE ? now() : null,
        ]);

        if ($status === GroupMember::STATUS_ACTIVE) {
            $group->increment('members_count');
        }

        return $member;
    }

    /**
     * Leave a group
     */
    public function leaveGroup(User $user, Group $group): void
    {
        $membership = GroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $membership) {
            throw new \Exception('Not a member of this group');
        }

        if ($membership->role === GroupMember::ROLE_OWNER) {
            throw new \Exception('Owner cannot leave the group. Transfer ownership first.');
        }

        $wasActive = $membership->status === GroupMember::STATUS_ACTIVE;
        $membership->delete();

        if ($wasActive) {
            $group->decrement('members_count');
        }
    }

    /**
     * Get groups for a user
     */
    public function getUserGroups(User $user): Collection
    {
        return Group::whereHas('members', function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->where('status', GroupMember::STATUS_ACTIVE);
        })->withCount('members')->get();
    }

    /**
     * Discover groups
     */
    public function discoverGroups(
        ?string $search = null,
        ?string $industry = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Group::where('privacy', '!=', 'secret')
            ->withCount('members');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($industry) {
            $query->where('industry', $industry);
        }

        return $query->orderByDesc('members_count')->paginate($perPage);
    }

    // ============================================
    // MESSAGING
    // ============================================

    /**
     * Start or get a conversation between users
     */
    public function getOrCreateConversation(User $user1, User $user2): NetworkConversation
    {
        // Check if conversation exists
        $conversation = NetworkConversation::where('type', 'direct')
            ->whereHas('participants', function ($query) use ($user1) {
                $query->where('user_id', $user1->id);
            })
            ->whereHas('participants', function ($query) use ($user2) {
                $query->where('user_id', $user2->id);
            })
            ->first();

        if ($conversation) {
            return $conversation;
        }

        // Create new conversation
        $conversation = NetworkConversation::create(['type' => 'direct']);

        $conversation->participants()->createMany([
            ['user_id' => $user1->id],
            ['user_id' => $user2->id],
        ]);

        return $conversation;
    }

    /**
     * Send a message
     */
    public function sendMessage(
        User $sender,
        NetworkConversation $conversation,
        string $content,
        ?array $attachments = null,
        ?int $replyToId = null
    ): NetworkMessage {
        // Verify user is a participant
        if (! $conversation->participants()->where('user_id', $sender->id)->exists()) {
            throw new \Exception('You are not a participant in this conversation');
        }

        $message = NetworkMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'content' => $content,
            'attachments' => $attachments,
            'reply_to_id' => $replyToId,
        ]);

        // Update conversation
        $conversation->update([
            'last_message_id' => $message->id,
            'last_message_at' => now(),
        ]);

        // Update participant's last read
        $conversation->participants()
            ->where('user_id', $sender->id)
            ->update(['last_read_at' => now()]);

        return $message;
    }

    /**
     * Get user's conversations
     */
    public function getConversations(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return NetworkConversation::forUser($user->id)
            ->with(['participants.user', 'lastMessage.sender'])
            ->orderByDesc('last_message_at')
            ->paginate($perPage);
    }

    /**
     * Get messages in a conversation
     */
    public function getMessages(
        NetworkConversation $conversation,
        User $user,
        int $perPage = 50
    ): LengthAwarePaginator {
        // Verify participation
        if (! $conversation->participants()->where('user_id', $user->id)->exists()) {
            throw new \Exception('Not authorized to view this conversation');
        }

        // Mark as read
        $conversation->participants()
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);

        return $conversation->messages()
            ->with(['sender', 'replyTo.sender'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Get unread message count for user
     */
    public function getUnreadCount(User $user): int
    {
        return DB::table('conversation_participants as cp')
            ->join('network_conversations as nc', 'cp.conversation_id', '=', 'nc.id')
            ->join('network_messages as nm', 'nc.id', '=', 'nm.conversation_id')
            ->where('cp.user_id', $user->id)
            ->where('nm.sender_id', '!=', $user->id)
            ->where(function ($query) {
                $query->whereColumn('nm.created_at', '>', 'cp.last_read_at')
                    ->orWhereNull('cp.last_read_at');
            })
            ->count();
    }

    // ============================================
    // SUGGESTIONS
    // ============================================

    /**
     * Get connection suggestions for a user
     */
    public function getConnectionSuggestions(User $user, int $limit = 10): Collection
    {
        $connectionIds = $this->getConnectionIds($user);
        $blockedIds = Connection::where(function ($query) use ($user) {
            $query->where('sender_id', $user->id)
                ->orWhere('recipient_id', $user->id);
        })->where('status', Connection::STATUS_BLOCKED)
            ->get()
            ->map(fn ($c) => $c->sender_id === $user->id ? $c->recipient_id : $c->sender_id)
            ->toArray();

        $excludeIds = array_merge($connectionIds, $blockedIds, [$user->id]);

        // Get 2nd degree connections (connections of connections)
        $secondDegreeIds = [];
        foreach ($connectionIds as $connectionId) {
            $theirConnections = $this->getConnectionIds(User::find($connectionId));
            $secondDegreeIds = array_merge($secondDegreeIds, $theirConnections);
        }

        $secondDegreeIds = array_diff(array_unique($secondDegreeIds), $excludeIds);

        // Score users based on mutual connections
        $suggestions = [];
        foreach ($secondDegreeIds as $userId) {
            $mutualCount = count(array_intersect(
                $connectionIds,
                $this->getConnectionIds(User::find($userId))
            ));
            $suggestions[$userId] = $mutualCount;
        }

        arsort($suggestions);
        $topUserIds = array_slice(array_keys($suggestions), 0, $limit);

        return User::whereIn('id', $topUserIds)
            ->with(['candidateProfile'])
            ->get()
            ->sortByDesc(fn ($u) => $suggestions[$u->id] ?? 0);
    }
}
