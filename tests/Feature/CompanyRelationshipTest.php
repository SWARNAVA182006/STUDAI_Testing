<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use Tests\TestCase;

class CompanyRelationshipTest extends TestCase
{
    public function test_employer_has_company_id_assigned()
    {
        // Fetch the seeded employer user
        $user = User::where('email', 'company@user.com')->first();

        if (!$user) {
            $this->fail('Employer user (company@user.com) not found. Did you run the seeder?');
        }

        // Check if company_id is set
        $this->assertNotNull($user->company_id, 'User company_id is null');

        // Check if the relationship works
        $this->assertInstanceOf(Company::class, $user->company, 'User->company relationship did not return a Company instance');
        
        // Check if it matches the expected company
        $this->assertEquals('Test Company', $user->company->name, 'Company name does not match expected "Test Company"');
        
        echo "\nVerified: User {$user->email} is linked to Company ID: {$user->company_id} ({$user->company->name})\n";
    }
}
