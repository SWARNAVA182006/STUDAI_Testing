<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OfferComparison;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OfferComparisonPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OfferComparison $comparison): bool
    {
        return $user->id === $comparison->user_id || $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, OfferComparison $comparison): bool
    {
        return $user->id === $comparison->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OfferComparison $comparison): bool
    {
        return $user->id === $comparison->user_id;
    }
}
