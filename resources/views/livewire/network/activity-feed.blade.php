<div class="space-y-6">
    {{-- Create Post Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-start space-x-3">
            <img src="{{ auth()->user()->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) }}"
                 alt="{{ auth()->user()->name }}"
                 class="w-12 h-12 rounded-full">

            <div class="flex-1">
                <form wire:submit="createPost">
                    <textarea wire:model="newPostContent"
                              rows="3"
                              class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
                              placeholder="What's on your mind? Share an update, achievement, or insight..."></textarea>

                    @error('newPostContent')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror

                    {{-- Media Preview --}}
                    @if(!empty($mediaFiles))
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($mediaFiles as $index => $file)
                                <div class="relative">
                                    @if(str_starts_with($file->getMimeType(), 'image'))
                                        <img src="{{ $file->temporaryUrl() }}" class="h-20 w-20 object-cover rounded-lg">
                                    @else
                                        <div class="h-20 w-20 bg-gray-100 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                                            <x-heroicon-o-video-camera class="h-8 w-8 text-gray-400" />
                                        </div>
                                    @endif
                                    <button type="button"
                                            wire:click="$set('mediaFiles.{{ $index }}', null)"
                                            class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-0.5">
                                        <x-heroicon-s-x-mark class="h-3 w-3" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-3 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            {{-- Add Media --}}
                            <label class="cursor-pointer p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                                <x-heroicon-o-photo class="h-5 w-5 text-gray-500" />
                                <input type="file"
                                       wire:model="mediaFiles"
                                       multiple
                                       accept="image/*,video/*"
                                       class="hidden">
                            </label>

                            {{-- Visibility --}}
                            <select wire:model="postVisibility"
                                    class="text-sm border-0 bg-transparent text-gray-600 dark:text-gray-400 focus:ring-0">
                                <option value="public">🌐 Public</option>
                                <option value="connections">👥 Connections</option>
                                <option value="only_me">🔒 Only Me</option>
                            </select>
                        </div>

                        <button type="submit"
                                class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition"
                                wire:loading.attr="disabled"
                                wire:target="createPost">
                            <span wire:loading.remove wire:target="createPost">Post</span>
                            <span wire:loading wire:target="createPost">Posting...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Trending Hashtags --}}
    @if($this->trendingHashtags->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Trending Topics</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($this->trendingHashtags as $hashtag)
                    <a href="#"
                       class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition">
                        #{{ $hashtag->name }}
                        <span class="ml-1.5 text-xs text-gray-500">{{ $hashtag->posts_count }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Activity Feed --}}
    <div class="space-y-4">
        @forelse($this->posts as $post)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                {{-- Post Header --}}
                <div class="p-4 flex items-start justify-between">
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('network.profile.show', $post->author) }}">
                            <img src="{{ $post->author->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($post->author->name) }}"
                                 alt="{{ $post->author->name }}"
                                 class="w-12 h-12 rounded-full">
                        </a>
                        <div>
                            <a href="{{ route('network.profile.show', $post->author) }}"
                               class="font-semibold text-gray-900 dark:text-white hover:underline">
                                {{ $post->author->name }}
                            </a>
                            <p class="text-sm text-gray-500">
                                {{ $post->author->candidateProfile?->current_title ?? 'Professional' }}
                                • {{ $post->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>

                    @if($post->user_id === auth()->id())
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                                <x-heroicon-o-ellipsis-horizontal class="h-5 w-5 text-gray-500" />
                            </button>
                            <div x-show="open"
                                 @click.away="open = false"
                                 class="absolute right-0 mt-1 w-48 bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 py-1 z-10">
                                <button wire:click="deletePost({{ $post->id }})"
                                        wire:confirm="Are you sure you want to delete this post?"
                                        class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600">
                                    Delete Post
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Shared Post Indicator --}}
                @if($post->sharedPost)
                    <div class="px-4 pb-2">
                        <p class="text-gray-600 dark:text-gray-400">{{ $post->content }}</p>
                    </div>
                    <div class="mx-4 mb-4 border border-gray-200 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/50">
                        <div class="flex items-center space-x-2 mb-2">
                            <img src="{{ $post->sharedPost->author->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($post->sharedPost->author->name) }}"
                                 class="w-8 h-8 rounded-full">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $post->sharedPost->author->name }}</p>
                                <p class="text-xs text-gray-500">{{ $post->sharedPost->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <p class="text-gray-700 dark:text-gray-300">{{ $post->sharedPost->content }}</p>
                    </div>
                @else
                    {{-- Post Content --}}
                    <div class="px-4 pb-3">
                        <p class="text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ $post->content }}</p>
                    </div>

                    {{-- Media --}}
                    @if($post->media)
                        <div class="px-4 pb-3">
                            <div class="grid gap-2 {{ count($post->media) > 1 ? 'grid-cols-2' : 'grid-cols-1' }}">
                                @foreach($post->media as $media)
                                    @if($media['type'] === 'image')
                                        <img src="{{ asset('storage/' . $media['path']) }}"
                                             alt=""
                                             class="w-full rounded-lg object-cover max-h-96">
                                    @else
                                        <video controls class="w-full rounded-lg">
                                            <source src="{{ asset('storage/' . $media['path']) }}" type="video/mp4">
                                        </video>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif

                {{-- Engagement Stats --}}
                <div class="px-4 py-2 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-sm text-gray-500">
                    <div class="flex items-center space-x-4">
                        @if($post->likes_count > 0)
                            <span>{{ $post->likes_count }} {{ Str::plural('reaction', $post->likes_count) }}</span>
                        @endif
                    </div>
                    <div class="flex items-center space-x-4">
                        @if($post->comments_count > 0)
                            <span>{{ $post->comments_count }} {{ Str::plural('comment', $post->comments_count) }}</span>
                        @endif
                        @if($post->shares_count > 0)
                            <span>{{ $post->shares_count }} {{ Str::plural('share', $post->shares_count) }}</span>
                        @endif
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="px-4 py-2 border-t border-gray-100 dark:border-gray-700 flex items-center justify-around">
                    {{-- Like Button with Reactions --}}
                    <div class="relative" x-data="{ showReactions: false }">
                        <button @mouseenter="showReactions = true"
                                @click="showReactions = !showReactions"
                                class="flex items-center space-x-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition {{ $post->likes->where('user_id', auth()->id())->count() ? 'text-indigo-600' : 'text-gray-600 dark:text-gray-400' }}">
                            <x-heroicon-o-hand-thumb-up class="h-5 w-5" />
                            <span>Like</span>
                        </button>

                        {{-- Reaction Picker --}}
                        <div x-show="showReactions"
                             @mouseleave="showReactions = false"
                             x-transition
                             class="absolute bottom-full left-0 mb-2 bg-white dark:bg-gray-700 rounded-full shadow-lg px-2 py-1 flex items-center space-x-1 z-20">
                            @foreach(['👍' => 'like', '🎉' => 'celebrate', '💡' => 'insightful', '❤️' => 'love', '🤔' => 'curious'] as $emoji => $type)
                                <button wire:click="likePost({{ $post->id }}, '{{ $type }}')"
                                        class="text-2xl hover:scale-125 transition-transform p-1"
                                        title="{{ ucfirst($type) }}">
                                    {{ $emoji }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Comment Button --}}
                    <button wire:click="startCommenting({{ $post->id }})"
                            class="flex items-center space-x-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition text-gray-600 dark:text-gray-400">
                        <x-heroicon-o-chat-bubble-left class="h-5 w-5" />
                        <span>Comment</span>
                    </button>

                    {{-- Share Button --}}
                    <button wire:click="sharePost({{ $post->id }})"
                            class="flex items-center space-x-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition text-gray-600 dark:text-gray-400">
                        <x-heroicon-o-arrow-path-rounded-square class="h-5 w-5" />
                        <span>Share</span>
                    </button>
                </div>

                {{-- Comments Section --}}
                @if($commentingOnPost === $post->id || $post->comments_count > 0)
                    <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        {{-- Existing Comments --}}
                        @foreach($post->comments->take(3) as $comment)
                            <div class="flex items-start space-x-2 mb-3">
                                <img src="{{ $comment->author->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($comment->author->name) }}"
                                     class="w-8 h-8 rounded-full">
                                <div class="flex-1">
                                    <div class="bg-white dark:bg-gray-600 rounded-lg px-3 py-2">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $comment->author->name }}</p>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $comment->content }}</p>
                                    </div>
                                    <div class="flex items-center space-x-3 mt-1 text-xs text-gray-500">
                                        <span>{{ $comment->created_at->diffForHumans() }}</span>
                                        <button wire:click="replyToComment({{ $comment->id }})" class="hover:underline">Reply</button>
                                        @if($comment->user_id === auth()->id())
                                            <button wire:click="deleteComment({{ $comment->id }})"
                                                    wire:confirm="Delete this comment?"
                                                    class="text-red-500 hover:underline">Delete</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- New Comment Form --}}
                        @if($commentingOnPost === $post->id)
                            <div class="flex items-start space-x-2 mt-3">
                                <img src="{{ auth()->user()->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) }}"
                                     class="w-8 h-8 rounded-full">
                                <div class="flex-1">
                                    <input type="text"
                                           wire:model="newComment"
                                           wire:keydown.enter="submitComment"
                                           placeholder="Write a comment..."
                                           class="w-full px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-full bg-white dark:bg-gray-600 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <div class="flex items-center justify-end mt-2 space-x-2">
                                        <button wire:click="cancelComment"
                                                class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
                                        <button wire:click="submitComment"
                                                class="text-sm text-indigo-600 font-medium hover:text-indigo-700">Post</button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                <x-heroicon-o-newspaper class="h-16 w-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Your feed is empty</h3>
                <p class="text-gray-500 mb-4">Connect with professionals and follow people to see their updates here.</p>
                <a href="{{ route('candidate.network.connections') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                    Find Connections
                </a>
            </div>
        @endforelse

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $this->posts->links() }}
        </div>
    </div>

    {{-- Share Modal --}}
    @if($sharingPost)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="cancelShare"></div>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Share Post</h3>

                        <textarea wire:model="shareComment"
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                  placeholder="Add a comment (optional)..."></textarea>

                        <div class="mt-4 flex items-center justify-end space-x-3">
                            <button wire:click="cancelShare"
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                                Cancel
                            </button>
                            <button wire:click="confirmShare"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                                Share
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
