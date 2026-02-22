<?php

declare(strict_types=1);

namespace Tests\Feature\API;

use App\Models\Application;
use App\Models\Resume;
use App\Models\User;
use App\Services\GDPRService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GDPRAPITest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Storage::fake('local');
    }

    public function test_can_get_gdpr_rights_info(): void
    {
        $response = $this->getJson('/api/gdpr/rights');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'rights' => [
                    '*' => ['name', 'description', 'endpoint'],
                ],
                'data_protection_officer',
            ]);
    }

    public function test_can_export_user_data(): void
    {
        Sanctum::actingAs($this->user);

        // Create some data
        Resume::factory()->create(['user_id' => $this->user->id]);
        Application::factory()->create(['user_id' => $this->user->id]);

        $response = $this->postJson('/api/gdpr/export');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'download_url',
                'expires_at',
            ]);
    }

    public function test_can_preview_export(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/gdpr/export/preview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'export_date',
                'categories',
            ]);
    }

    public function test_can_get_consent_status(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/gdpr/consent');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'consents' => [
                    'marketing_emails',
                    'data_processing',
                    'third_party_sharing',
                    'analytics',
                    'ai_processing',
                ],
            ]);
    }

    public function test_can_update_consent(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/gdpr/consent', [
            'marketing_emails' => true,
            'third_party_sharing' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'consents' => [
                    'marketing_emails' => true,
                    'third_party_sharing' => false,
                ],
            ]);

        $this->user->refresh();
        $this->assertTrue($this->user->marketing_consent);
        $this->assertFalse($this->user->third_party_consent);
    }

    public function test_can_schedule_deletion(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/gdpr/delete', [
            'password' => 'password',
            'confirm' => true,
            'immediate' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'scheduled_at',
                'can_cancel_until',
            ]);

        $this->assertDatabaseHas('scheduled_deletions', [
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    public function test_deletion_requires_password(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/gdpr/delete', [
            'password' => 'wrong_password',
            'confirm' => true,
        ]);

        $response->assertStatus(422);
    }

    public function test_can_cancel_scheduled_deletion(): void
    {
        Sanctum::actingAs($this->user);

        // First schedule deletion
        $this->postJson('/api/gdpr/delete', [
            'password' => 'password',
            'confirm' => true,
        ]);

        // Then cancel it
        $response = $this->postJson('/api/gdpr/delete/cancel');

        $response->assertStatus(200);

        $this->assertDatabaseMissing('scheduled_deletions', [
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    public function test_can_request_immediate_deletion(): void
    {
        Sanctum::actingAs($this->user);

        $userId = $this->user->id;

        $response = $this->postJson('/api/gdpr/delete', [
            'password' => 'password',
            'confirm' => true,
            'immediate' => true,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    public function test_can_anonymize_account(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/gdpr/anonymize', [
            'password' => 'password',
            'confirm' => true,
        ]);

        $response->assertStatus(200);

        $this->user->refresh();
        $this->assertEquals('Deleted User', $this->user->name);
        $this->assertStringContainsString('@deleted.local', $this->user->email);
    }

    public function test_can_restrict_processing(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/gdpr/restrict', [
            'reason' => 'Privacy concerns',
            'categories' => ['marketing', 'analytics', 'third_party'],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'restricted_categories' => ['marketing', 'analytics', 'third_party'],
            ]);

        $this->user->refresh();
        $this->assertFalse($this->user->marketing_consent);
        $this->assertFalse($this->user->analytics_consent);
        $this->assertFalse($this->user->third_party_consent);
    }

    public function test_export_requires_authentication(): void
    {
        $response = $this->postJson('/api/gdpr/export');

        $response->assertStatus(401);
    }

    public function test_gdpr_service_export_categories(): void
    {
        $service = app(GDPRService::class);

        // Create test data
        Resume::factory()->create(['user_id' => $this->user->id]);

        $data = $service->exportUserData($this->user->id, [
            GDPRService::CATEGORY_PROFILE,
            GDPRService::CATEGORY_RESUMES,
        ]);

        $this->assertArrayHasKey('categories', $data);
        $this->assertArrayHasKey('profile', $data['categories']);
        $this->assertArrayHasKey('resumes', $data['categories']);
        $this->assertArrayNotHasKey('applications', $data['categories']);
    }

    public function test_gdpr_service_logs_operations(): void
    {
        $service = app(GDPRService::class);

        $service->exportUserData($this->user->id);

        $this->assertDatabaseHas('gdpr_audit_logs', [
            'user_id' => $this->user->id,
            'operation' => 'export',
        ]);
    }
}
