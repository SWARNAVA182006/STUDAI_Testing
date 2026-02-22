<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_accessible(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_about_page_is_accessible(): void
    {
        $response = $this->get('/about');

        $response->assertStatus(200);
    }

    public function test_features_page_is_accessible(): void
    {
        $response = $this->get('/features');

        $response->assertStatus(200);
    }

    public function test_pricing_page_is_accessible(): void
    {
        $response = $this->get('/pricing');

        $response->assertStatus(200);
    }

    public function test_contact_page_is_accessible(): void
    {
        $response = $this->get('/contact');

        $response->assertStatus(200);
    }

    public function test_how_it_works_page_is_accessible(): void
    {
        $response = $this->get('/how-it-works');

        $response->assertStatus(200);
    }

    public function test_privacy_policy_page_is_accessible(): void
    {
        $response = $this->get('/privacy-policy');

        $response->assertStatus(200);
    }

    public function test_terms_page_is_accessible(): void
    {
        $response = $this->get('/terms-and-conditions');

        $response->assertStatus(200);
    }

    public function test_blog_page_is_accessible(): void
    {
        $response = $this->get('/blog');

        $response->assertStatus(200);
    }
}
