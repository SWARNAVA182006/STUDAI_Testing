<?php

declare(strict_types=1);

namespace Tests\Feature\API;

use App\Models\NegotiationSession;
use App\Models\NegotiationStrategy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\Traits\MocksAIService;
use Tests\TestCase;

class NegotiationAPITest extends TestCase
{
    use RefreshDatabase, MocksAIService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_generate_strategy(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockAIWithJSON([
            'strategy' => 'value_based',
            'key_points' => [
                'Emphasize your unique skills',
                'Research market rates',
                'Prepare counter-offers',
            ],
            'opening_position' => 1500000,
            'target_position' => 1800000,
            'walkaway_point' => 1300000,
        ]);

        $response = $this->postJson('/api/negotiation/strategy', [
            'current_salary' => 1200000,
            'target_salary' => 1800000,
            'role' => 'Senior Developer',
            'company_size' => 'large',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'strategy',
                'key_points',
                'opening_position',
                'target_position',
            ]);
    }

    public function test_strategy_requires_current_salary(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/negotiation/strategy', [
            'target_salary' => 1800000,
            'role' => 'Developer',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_salary']);
    }

    public function test_can_get_strategy(): void
    {
        Sanctum::actingAs($this->user);

        $strategy = NegotiationStrategy::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/negotiation/strategy/{$strategy->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'strategy',
                'key_points',
            ]);
    }

    public function test_can_get_scenarios(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockAIWithJSON([
            'scenarios' => [
                [
                    'name' => 'Initial Offer Response',
                    'description' => 'They offer below your target',
                    'suggested_response' => 'Thank you for the offer...',
                ],
                [
                    'name' => 'Counter-Offer',
                    'description' => 'They push back on your counter',
                    'suggested_response' => 'I understand your constraints...',
                ],
            ],
        ]);

        $strategy = NegotiationStrategy::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/negotiation/scenarios/{$strategy->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'scenarios' => [
                    '*' => ['name', 'description', 'suggested_response'],
                ],
            ]);
    }

    public function test_can_get_scripts(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockAIWithJSON([
            'scripts' => [
                [
                    'situation' => 'Opening',
                    'script' => 'Thank you for the opportunity...',
                    'tips' => ['Maintain confident tone', 'Use pauses effectively'],
                ],
            ],
        ]);

        $strategy = NegotiationStrategy::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/negotiation/scripts/{$strategy->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'scripts' => [
                    '*' => ['situation', 'script', 'tips'],
                ],
            ]);
    }

    public function test_can_start_coaching_session(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockAI();

        $strategy = NegotiationStrategy::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->postJson('/api/negotiation/session', [
            'strategy_id' => $strategy->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'session_id',
                'status',
                'initial_message',
            ]);
    }

    public function test_can_send_message_in_session(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockAIWithResponse('That\'s a great approach! Here\'s what I suggest...');

        $session = NegotiationSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $response = $this->postJson("/api/negotiation/session/{$session->id}/message", [
            'message' => 'They offered 1.5L but I want 1.8L',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'response',
                'suggestions',
            ]);
    }

    public function test_can_update_session_stage(): void
    {
        Sanctum::actingAs($this->user);

        $session = NegotiationSession::factory()->create([
            'user_id' => $this->user->id,
            'current_stage' => 'preparation',
        ]);

        $response = $this->putJson("/api/negotiation/session/{$session->id}/stage", [
            'stage' => 'negotiation',
        ]);

        $response->assertStatus(200);

        $session->refresh();
        $this->assertEquals('negotiation', $session->current_stage);
    }

    public function test_can_record_outcome(): void
    {
        Sanctum::actingAs($this->user);

        $session = NegotiationSession::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->putJson("/api/negotiation/session/{$session->id}/outcome", [
            'outcome' => 'accepted',
            'final_salary' => 1750000,
            'other_benefits' => ['signing_bonus', 'stock_options'],
            'notes' => 'Very positive experience',
        ]);

        $response->assertStatus(200);

        $session->refresh();
        $this->assertEquals('accepted', $session->outcome);
        $this->assertEquals(1750000, $session->final_salary);
    }

    public function test_can_get_tactics_library(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/negotiation/tactics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'tactics' => [
                    '*' => ['name', 'description', 'when_to_use', 'example'],
                ],
            ]);
    }

    public function test_cannot_access_other_users_strategy(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $strategy = NegotiationStrategy::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->getJson("/api/negotiation/strategy/{$strategy->id}");

        $response->assertStatus(404);
    }

    public function test_cannot_send_message_to_other_users_session(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $session = NegotiationSession::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->postJson("/api/negotiation/session/{$session->id}/message", [
            'message' => 'Hello',
        ]);

        $response->assertStatus(404);
    }

    public function test_can_list_user_strategies(): void
    {
        Sanctum::actingAs($this->user);

        NegotiationStrategy::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/negotiation/strategies');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_list_user_sessions(): void
    {
        Sanctum::actingAs($this->user);

        NegotiationSession::factory()->count(2)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/negotiation/sessions');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_session_messages_are_stored(): void
    {
        Sanctum::actingAs($this->user);
        $this->mockAIWithResponse('AI response');

        $session = NegotiationSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $this->postJson("/api/negotiation/session/{$session->id}/message", [
            'message' => 'User message',
        ]);

        $this->assertDatabaseHas('negotiation_messages', [
            'negotiation_session_id' => $session->id,
            'role' => 'user',
        ]);

        $this->assertDatabaseHas('negotiation_messages', [
            'negotiation_session_id' => $session->id,
            'role' => 'assistant',
        ]);
    }

    public function test_negotiation_requires_authentication(): void
    {
        $response = $this->postJson('/api/negotiation/strategy', [
            'current_salary' => 1200000,
        ]);

        $response->assertStatus(401);
    }
}
