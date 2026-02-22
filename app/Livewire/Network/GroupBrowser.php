<?php

declare(strict_types=1);

namespace App\Livewire\Network;

use App\Models\Group;
use App\Models\GroupMember;
use App\Services\NetworkingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class GroupBrowser extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $tab = 'discover';
    public string $search = '';
    public ?string $industryFilter = null;

    // Create group form
    public bool $showCreateForm = false;
    public string $groupName = '';
    public string $groupDescription = '';
    public string $groupPrivacy = 'public';
    public ?string $groupIndustry = null;
    public $groupCover = null;

    protected NetworkingService $networkingService;

    public function boot(NetworkingService $networkingService): void
    {
        $this->networkingService = $networkingService;
    }

    protected function rules(): array
    {
        return [
            'groupName' => 'required|string|min:3|max:100',
            'groupDescription' => 'nullable|string|max:1000',
            'groupPrivacy' => 'required|in:public,private,secret',
            'groupIndustry' => 'nullable|string|max:100',
            'groupCover' => 'nullable|image|max:5120',
        ];
    }

    #[Computed]
    public function myGroups()
    {
        return $this->networkingService->getUserGroups(auth()->user());
    }

    #[Computed]
    public function discoverGroups()
    {
        return $this->networkingService->discoverGroups(
            $this->search ?: null,
            $this->industryFilter,
            15
        );
    }

    #[Computed]
    public function industries(): array
    {
        return Group::whereNotNull('industry')
            ->distinct()
            ->pluck('industry')
            ->toArray();
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    public function openCreateForm(): void
    {
        $this->showCreateForm = true;
        $this->resetCreateForm();
    }

    public function closeCreateForm(): void
    {
        $this->showCreateForm = false;
        $this->resetCreateForm();
    }

    private function resetCreateForm(): void
    {
        $this->groupName = '';
        $this->groupDescription = '';
        $this->groupPrivacy = 'public';
        $this->groupIndustry = null;
        $this->groupCover = null;
    }

    public function createGroup(): void
    {
        $this->validate();

        $coverPath = null;
        if ($this->groupCover) {
            $coverPath = $this->groupCover->store('groups/covers', 'public');
        }

        $this->networkingService->createGroup(
            auth()->user(),
            $this->groupName,
            $this->groupDescription ?: null,
            $this->groupPrivacy,
            $this->groupIndustry,
            $coverPath
        );

        $this->closeCreateForm();
        $this->dispatch('notify', type: 'success', message: 'Group created successfully!');

        unset($this->myGroups, $this->discoverGroups);
    }

    public function joinGroup(int $groupId): void
    {
        try {
            $group = Group::findOrFail($groupId);

            $membership = $this->networkingService->joinGroup(auth()->user(), $group);

            if ($membership->status === GroupMember::STATUS_PENDING) {
                $this->dispatch('notify', type: 'info', message: 'Join request sent! Waiting for approval.');
            } else {
                $this->dispatch('notify', type: 'success', message: 'You have joined the group!');
            }

            unset($this->myGroups, $this->discoverGroups);
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function leaveGroup(int $groupId): void
    {
        try {
            $group = Group::findOrFail($groupId);

            $this->networkingService->leaveGroup(auth()->user(), $group);

            $this->dispatch('notify', type: 'success', message: 'You have left the group.');

            unset($this->myGroups, $this->discoverGroups);
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function isMember(int $groupId): bool
    {
        return GroupMember::where('group_id', $groupId)
            ->where('user_id', auth()->id())
            ->where('status', GroupMember::STATUS_ACTIVE)
            ->exists();
    }

    public function isPending(int $groupId): bool
    {
        return GroupMember::where('group_id', $groupId)
            ->where('user_id', auth()->id())
            ->where('status', GroupMember::STATUS_PENDING)
            ->exists();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedIndustryFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->industryFilter = null;
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.network.group-browser');
    }
}
