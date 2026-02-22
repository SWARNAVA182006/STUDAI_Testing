<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_index_is_accessible(): void
    {
        $response = $this->get('/marketplace');

        $response->assertStatus(200);
    }

    public function test_marketplace_projects_is_accessible(): void
    {
        $response = $this->get('/marketplace/projects');

        $response->assertStatus(200);
    }

    public function test_marketplace_freelancers_is_accessible(): void
    {
        $response = $this->get('/marketplace/freelancers');

        $response->assertStatus(200);
    }

    public function test_marketplace_categories_is_accessible(): void
    {
        $response = $this->get('/marketplace/categories');

        $response->assertStatus(200);
    }

    public function test_freelancer_dashboard_requires_authentication(): void
    {
        $response = $this->get('/marketplace/freelancer/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_employer_marketplace_dashboard_requires_authentication(): void
    {
        $response = $this->get('/marketplace/employer/dashboard');

        $response->assertRedirect('/login');
    }
}
