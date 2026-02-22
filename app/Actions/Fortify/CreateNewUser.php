<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
            'account_type' => ['required', 'string', Rule::in(['job_seeker', 'employer'])],
            'phone' => ['nullable', 'string', 'max:20'],
            'terms' => ['required', 'accepted'],
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'account_type' => $input['account_type'],
            'phone' => $input['phone'] ?? null,
            'is_active' => true,
            'timezone' => 'UTC',
        ]);

        // Assign role based on account type
        if ($input['account_type'] === 'employer') {
            $user->assignRole('employer');
        } else {
            $user->assignRole('job_seeker');
        }

        // Create initial profile for job seekers
        if ($user->isJobSeeker()) {
            $user->profile()->create([
                'profile_completeness' => 10, // Just registered
            ]);
        }

        return $user;
    }
}
