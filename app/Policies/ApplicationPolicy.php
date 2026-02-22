<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    /**
     * Determine if the user can view the application
     */
    public function view(User $user, Application $application): bool
    {
        // User can view their own applications
        // OR employers can view applications to their jobs
        return $user->id === $application->user_id 
            || ($user->isEmployer() && $application->job->user_id === $user->id);
    }
    
    /**
     * Determine if the user can update the application
     */
    public function update(User $user, Application $application): bool
    {
        // Only the applicant can update their application
        // AND only if status allows it (not rejected/withdrawn)
        return $user->id === $application->user_id
            && !in_array($application->status, ['rejected', 'withdrawn']);
    }
    
    /**
     * Determine if the user can delete the application
     */
    public function delete(User $user, Application $application): bool
    {
        // Only the applicant can delete their application
        // AND only if it's in draft or withdrawn status
        return $user->id === $application->user_id
            && in_array($application->status, ['draft', 'withdrawn']);
    }
}
