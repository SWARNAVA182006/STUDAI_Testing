<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BackgroundCheck;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BackgroundCheckPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Super admins can do everything
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any background checks.
     */
    public function viewAny(User $user): bool
    {
        // Employers and HR can view background checks
        return $user->hasAnyRole(['employer', 'hr_manager', 'recruiter', 'admin'])
            || $user->hasPermissionTo('view background checks');
    }

    /**
     * Determine whether the user can view the background check.
     */
    public function view(User $user, BackgroundCheck $backgroundCheck): bool
    {
        // Candidate can view their own background check
        if ($user->id === $backgroundCheck->candidate_id) {
            return true;
        }

        // Company staff can view their company's checks
        if ($user->company_id === $backgroundCheck->company_id) {
            return $user->hasAnyRole(['employer', 'hr_manager', 'recruiter', 'admin'])
                || $user->hasPermissionTo('view background checks');
        }

        return false;
    }

    /**
     * Determine whether the user can create background checks.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['employer', 'hr_manager', 'recruiter', 'admin'])
            || $user->hasPermissionTo('create background checks');
    }

    /**
     * Determine whether the user can update the background check.
     */
    public function update(User $user, BackgroundCheck $backgroundCheck): bool
    {
        // Only company staff can update checks
        if ($user->company_id !== $backgroundCheck->company_id) {
            return false;
        }

        // Cannot update completed or cancelled checks
        if ($backgroundCheck->isCompleted() || $backgroundCheck->isCancelled()) {
            return false;
        }

        return $user->hasAnyRole(['employer', 'hr_manager', 'admin'])
            || $user->hasPermissionTo('edit background checks');
    }

    /**
     * Determine whether the user can delete the background check.
     */
    public function delete(User $user, BackgroundCheck $backgroundCheck): bool
    {
        // Only admins can delete checks
        if ($user->company_id !== $backgroundCheck->company_id) {
            return false;
        }

        // Cannot delete completed checks
        if ($backgroundCheck->isCompleted()) {
            return false;
        }

        return $user->hasAnyRole(['employer', 'admin'])
            || $user->hasPermissionTo('delete background checks');
    }

    /**
     * Determine whether the user can restore the background check.
     */
    public function restore(User $user, BackgroundCheck $backgroundCheck): bool
    {
        return $user->hasRole('admin')
            || $user->hasPermissionTo('restore background checks');
    }

    /**
     * Determine whether the user can permanently delete the background check.
     */
    public function forceDelete(User $user, BackgroundCheck $backgroundCheck): bool
    {
        return $user->hasRole('admin')
            || $user->hasPermissionTo('force delete background checks');
    }

    /**
     * Determine whether the user can cancel the background check.
     */
    public function cancel(User $user, BackgroundCheck $backgroundCheck): bool
    {
        if ($user->company_id !== $backgroundCheck->company_id) {
            return false;
        }

        // Cannot cancel completed checks
        if ($backgroundCheck->isCompleted() || $backgroundCheck->isCancelled()) {
            return false;
        }

        return $user->hasAnyRole(['employer', 'hr_manager', 'admin'])
            || $user->hasPermissionTo('cancel background checks');
    }

    /**
     * Determine whether the user can initiate adverse action.
     */
    public function initiateAdverseAction(User $user, BackgroundCheck $backgroundCheck): bool
    {
        if ($user->company_id !== $backgroundCheck->company_id) {
            return false;
        }

        // Only completed checks with issues can have adverse action
        if (!$backgroundCheck->isCompleted() || !$backgroundCheck->has_flags) {
            return false;
        }

        // Cannot start if adverse action already exists
        if ($backgroundCheck->adverseAction()->exists()) {
            return false;
        }

        return $user->hasAnyRole(['employer', 'hr_manager', 'admin'])
            || $user->hasPermissionTo('initiate adverse action');
    }

    /**
     * Determine whether the user can send consent request.
     */
    public function sendConsent(User $user, BackgroundCheck $backgroundCheck): bool
    {
        if ($user->company_id !== $backgroundCheck->company_id) {
            return false;
        }

        // Only pending checks can have consent sent
        if (!$backgroundCheck->isPending()) {
            return false;
        }

        return $user->hasAnyRole(['employer', 'hr_manager', 'recruiter', 'admin'])
            || $user->hasPermissionTo('send consent requests');
    }

    /**
     * Determine whether the user can submit consent (candidate).
     */
    public function submitConsent(User $user, BackgroundCheck $backgroundCheck): bool
    {
        // Only the candidate can submit consent
        return $user->id === $backgroundCheck->candidate_id
            && $backgroundCheck->isAwaitingConsent();
    }

    /**
     * Determine whether the user can download the report.
     */
    public function downloadReport(User $user, BackgroundCheck $backgroundCheck): bool
    {
        // Only completed checks with reports can be downloaded
        if (!$backgroundCheck->isCompleted() || !$backgroundCheck->report_pdf_path) {
            return false;
        }

        // Candidate can download their own report
        if ($user->id === $backgroundCheck->candidate_id) {
            return true;
        }

        // Company staff can download
        if ($user->company_id === $backgroundCheck->company_id) {
            return $user->hasAnyRole(['employer', 'hr_manager', 'recruiter', 'admin'])
                || $user->hasPermissionTo('download background reports');
        }

        return false;
    }

    /**
     * Determine whether the user can request a recheck.
     */
    public function recheck(User $user, BackgroundCheck $backgroundCheck): bool
    {
        if ($user->company_id !== $backgroundCheck->company_id) {
            return false;
        }

        // Only completed checks can be rechecked
        if (!$backgroundCheck->isCompleted()) {
            return false;
        }

        return $user->hasAnyRole(['employer', 'hr_manager', 'admin'])
            || $user->hasPermissionTo('recheck background');
    }

    /**
     * Determine whether the user can view the decrypted report data.
     */
    public function viewDecryptedReport(User $user, BackgroundCheck $backgroundCheck): bool
    {
        // Only company admins can view decrypted reports
        if ($user->company_id !== $backgroundCheck->company_id) {
            return false;
        }

        return $user->hasAnyRole(['employer', 'admin'])
            || $user->hasPermissionTo('view decrypted background reports');
    }
}
