<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OfferLetter;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OfferLetterPolicy
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
    public function view(User $user, OfferLetter $offerLetter): bool
    {
        // Candidate can view their own offers
        if ($user->id === $offerLetter->candidate_id) {
            return true;
        }

        // Company members can view their company's offers
        if ($user->company_id === $offerLetter->company_id) {
            return $user->hasAnyRole(['employer', 'recruiter', 'admin']);
        }

        return $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['employer', 'recruiter', 'admin', 'super-admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, OfferLetter $offerLetter): bool
    {
        // Only company members can update offers (not candidates)
        if ($user->company_id !== $offerLetter->company_id) {
            return $user->hasRole('super-admin');
        }

        return $user->hasAnyRole(['employer', 'recruiter', 'admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OfferLetter $offerLetter): bool
    {
        // Only allow deletion of draft offers
        if ($offerLetter->status !== 'draft') {
            return false;
        }

        if ($user->company_id !== $offerLetter->company_id) {
            return $user->hasRole('super-admin');
        }

        return $user->hasAnyRole(['employer', 'admin']);
    }

    /**
     * Determine whether the user can respond to the offer (accept/decline/counter).
     */
    public function respond(User $user, OfferLetter $offerLetter): bool
    {
        return $user->id === $offerLetter->candidate_id && $offerLetter->can_respond;
    }

    /**
     * Determine whether the user can send the offer.
     */
    public function send(User $user, OfferLetter $offerLetter): bool
    {
        if ($user->company_id !== $offerLetter->company_id) {
            return $user->hasRole('super-admin');
        }

        return $user->hasAnyRole(['employer', 'recruiter', 'admin']) && $offerLetter->isDraft();
    }

    /**
     * Determine whether the user can withdraw the offer.
     */
    public function withdraw(User $user, OfferLetter $offerLetter): bool
    {
        if ($offerLetter->isAccepted() || $offerLetter->isWithdrawn()) {
            return false;
        }

        if ($user->company_id !== $offerLetter->company_id) {
            return $user->hasRole('super-admin');
        }

        return $user->hasAnyRole(['employer', 'admin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, OfferLetter $offerLetter): bool
    {
        return $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, OfferLetter $offerLetter): bool
    {
        return $user->hasRole('super-admin');
    }
}
