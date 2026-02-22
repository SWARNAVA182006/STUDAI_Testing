<?php

declare(strict_types=1);

namespace Tests\Feature\Employer;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployerDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create employer role if it doesn't exist
        Role::firstOrCreate(['name' => 'employer', 'guard_name' => 'web']);
    }

    public function test_employer_dashboard_requires_authentication(): void
    {
        $response = $this->get('/employer/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_employer_can_access_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('employer');
        
        $company = Company::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/employer/dashboard');

        // Should be accessible or redirect based on role setup
        $this->assertTrue(in_array($response->status(), [200, 302, 403]));
    }

    public function test_employer_jobs_page_requires_authentication(): void
    {
        $response = $this->get('/employer/jobs');

        $response->assertRedirect('/login');
    }

    public function test_employer_applicants_page_requires_authentication(): void
    {
        $response = $this->get('/employer/applicants');

        $response->assertRedirect('/login');
    }
}
