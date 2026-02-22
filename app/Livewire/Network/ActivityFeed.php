<?php

declare(strict_types=1);

namespace App\Livewire\Network;

use App\Models\PostComment;
use App\Models\UserPost;
use App\Services\NetworkingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ActivityFeed extends Component
{
    use WithFileUploads;
    use WithPagination;

    // Post creation
    public string $newPostContent = '';
    public string $postVisibility = 'connections';
    public $mediaFiles = [];

    // Comment
    public ?int $commentingOnPost = null;
    public string $newComment = '';
    public ?int $replyingToComment = null;

    // Share
    public ?int $sharingPost = null;
    public string $shareComment = '';

    // Filter
    public string $filter = 'all';

    protected NetworkingService $networkingService;

    public function boot(NetworkingService $networkingService): void
    {
        $this->networkingService = $networkingService;
    }

    protected function rules(): array
    {
        return [
            'newPostContent' => 'required|string|min:1|max:5000',
            'postVisibility' => 'required|in:public,connections,only_me',
            'mediaFiles.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4|max:10240',
        ];
    }

    #[Computed]
    public function posts()
    {
        return $this->networkingService->getActivityFeed(
            auth()->user(),
            15
        );
    }

    #[Computed]
    public function trendingHashtags()
    {
        return $this->networkingService->getTrendingHashtags(5);
    }

    public function createPost(): void
    {
        $this->validate([
            'newPostContent' => 'required|string|min:1|max:5000',
            'postVisibility' => 'required|in:public,connections,only_me',
        ]);

        $media = null;
        if (! empty($this->mediaFiles)) {
            $media = [];
            foreach ($this->mediaFiles as $file) {
                $path = $file->store('posts/media', 'public');
                $media[] = [
                    'type' => str_starts_with($file->getMimeType(), 'video') ? 'video' : 'image',
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                ];
            }
        }

        $this->networkingService->createPost(
            auth()->user(),
            $this->newPostContent,
            $this->postVisibility,
            $media
        );

        $this->reset(['newPostContent', 'postVisibility', 'mediaFiles']);
        $this->postVisibility = 'connections';

        $this->dispatch('post-created');
        $this->dispatch('notify', type: 'success', message: 'Post published successfully!');
    }

    public function likePost(int $postId, string $type = 'like'): void
    {
        $post = UserPost::findOrFail($postId);

        // Check if already liked
        $existingLike = $post->likes()->where('user_id', auth()->id())->first();

        if ($existingLike) {
            if ($existingLike->type === $type) {
                // Unlike
                $this->networkingService->unlikePost(auth()->user(), $post);
            } else {
                // Change reaction type
                $this->networkingService->likePost(auth()->user(), $post, $type);
            }
        } else {
            $this->networkingService->likePost(auth()->user(), $post, $type);
        }
    }

    public function startCommenting(int $postId): void
    {
        $this->commentingOnPost = $postId;
        $this->newComment = '';
        $this->replyingToComment = null;
    }

    public function replyToComment(int $commentId): void
    {
        $comment = PostComment::findOrFail($commentId);
        $this->commentingOnPost = $comment->post_id;
        $this->replyingToComment = $commentId;
        $this->newComment = '@' . $comment->author->name . ' ';
    }

    public function submitComment(): void
    {
        $this->validate([
            'newComment' => 'required|string|min:1|max:1000',
        ]);

        $post = UserPost::findOrFail($this->commentingOnPost);

        $this->networkingService->commentOnPost(
            auth()->user(),
            $post,
            $this->newComment,
            $this->replyingToComment
        );

        $this->reset(['commentingOnPost', 'newComment', 'replyingToComment']);
        $this->dispatch('notify', type: 'success', message: 'Comment added!');
    }

    public function cancelComment(): void
    {
        $this->reset(['commentingOnPost', 'newComment', 'replyingToComment']);
    }

    public function sharePost(int $postId): void
    {
        $this->sharingPost = $postId;
        $this->shareComment = '';
    }

    public function confirmShare(): void
    {
        $post = UserPost::findOrFail($this->sharingPost);

        $this->networkingService->createPost(
            auth()->user(),
            $this->shareComment ?: 'Shared a post',
            $this->postVisibility,
            null,
            null,
            $post->id
        );

        $this->reset(['sharingPost', 'shareComment']);
        $this->dispatch('notify', type: 'success', message: 'Post shared!');
    }

    public function cancelShare(): void
    {
        $this->reset(['sharingPost', 'shareComment']);
    }

    public function deletePost(int $postId): void
    {
        $post = UserPost::where('id', $postId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $post->delete();

        $this->dispatch('notify', type: 'success', message: 'Post deleted.');
    }

    public function deleteComment(int $commentId): void
    {
        $comment = PostComment::findOrFail($commentId);

        $this->networkingService->deleteComment($comment, auth()->user());

        $this->dispatch('notify', type: 'success', message: 'Comment deleted.');
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    #[On('post-created')]
    public function refreshFeed(): void
    {
        unset($this->posts);
    }

    public function render(): View
    {
        return view('livewire.network.activity-feed');
    }
}
