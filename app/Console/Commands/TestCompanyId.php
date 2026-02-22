<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class TestCompanyId extends Command
{
    protected $signature = 'test:company-id';
    protected $description = 'Verify employer company ID linkage';

    public function handle()
    {
        $this->info('Testing Company ID Linkage...');

        $user = User::where('email', 'company@user.com')->first();

        if (!$user) {
            $this->error('User company@user.com not found!');
            return 1;
        }

        $this->info("User Found: {$user->name} ({$user->email})");
        $this->info("Account Type: {$user->account_type}");
        $this->info("Company ID Column: " . ($user->company_id ?? 'NULL'));

        if ($user->company) {
            $this->info("Linked Company: {$user->company->name} (ID: {$user->company->id})");
            $this->info("SUCCESS: User is correctly linked to a company.");
        } else {
            $this->error("FAILURE: User has company_id={$user->company_id} but relationship returned null.");
        }
    }
}
