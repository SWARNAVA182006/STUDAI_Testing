<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use App\Models\AIPrompt;
use App\Services\AI\PromptRegistryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PromptRegistryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PromptRegistryService $promptRegistry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->promptRegistry = app(PromptRegistryService::class);
    }

    public function test_get_returns_prompt_by_name(): void
    {
        AIPrompt::create([
            'name' => 'test_prompt',
            'version' => 1,
            'template' => 'Hello, {{name}}!',
            'system_prompt' => 'You are a helpful assistant.',
            'is_active' => true,
        ]);

        $prompt = $this->promptRegistry->get('test_prompt');

        $this->assertNotNull($prompt);
        $this->assertEquals('test_prompt', $prompt->name);
        $this->assertEquals('Hello, {{name}}!', $prompt->template);
    }

    public function test_get_returns_latest_active_version(): void
    {
        AIPrompt::create([
            'name' => 'versioned_prompt',
            'version' => 1,
            'template' => 'Version 1',
            'is_active' => true,
        ]);

        AIPrompt::create([
            'name' => 'versioned_prompt',
            'version' => 2,
            'template' => 'Version 2',
            'is_active' => true,
        ]);

        AIPrompt::create([
            'name' => 'versioned_prompt',
            'version' => 3,
            'template' => 'Version 3 (inactive)',
            'is_active' => false,
        ]);

        $prompt = $this->promptRegistry->get('versioned_prompt');

        $this->assertEquals(2, $prompt->version);
        $this->assertEquals('Version 2', $prompt->template);
    }

    public function test_get_returns_null_for_nonexistent_prompt(): void
    {
        $prompt = $this->promptRegistry->get('nonexistent');

        $this->assertNull($prompt);
    }

    public function test_render_replaces_variables(): void
    {
        AIPrompt::create([
            'name' => 'greeting',
            'version' => 1,
            'template' => 'Hello, {{name}}! Your role is {{role}}.',
            'is_active' => true,
        ]);

        $rendered = $this->promptRegistry->render('greeting', [
            'name' => 'John',
            'role' => 'Developer',
        ]);

        $this->assertEquals('Hello, John! Your role is Developer.', $rendered);
    }

    public function test_render_handles_missing_variables(): void
    {
        AIPrompt::create([
            'name' => 'partial',
            'version' => 1,
            'template' => 'Hello, {{name}}! Your role is {{role}}.',
            'is_active' => true,
        ]);

        $rendered = $this->promptRegistry->render('partial', [
            'name' => 'John',
        ]);

        $this->assertEquals('Hello, John! Your role is {{role}}.', $rendered);
    }

    public function test_render_returns_empty_for_nonexistent_prompt(): void
    {
        $rendered = $this->promptRegistry->render('nonexistent', []);

        $this->assertEquals('', $rendered);
    }

    public function test_get_system_prompt_returns_system_prompt(): void
    {
        AIPrompt::create([
            'name' => 'with_system',
            'version' => 1,
            'template' => 'User template',
            'system_prompt' => 'You are an expert career advisor.',
            'is_active' => true,
        ]);

        $systemPrompt = $this->promptRegistry->getSystemPrompt('with_system');

        $this->assertEquals('You are an expert career advisor.', $systemPrompt);
    }

    public function test_get_system_prompt_returns_default_when_null(): void
    {
        AIPrompt::create([
            'name' => 'no_system',
            'version' => 1,
            'template' => 'User template',
            'system_prompt' => null,
            'is_active' => true,
        ]);

        $systemPrompt = $this->promptRegistry->getSystemPrompt('no_system');

        $this->assertNotNull($systemPrompt);
        $this->assertStringContainsString('helpful', strtolower($systemPrompt));
    }

    public function test_get_config_returns_metadata(): void
    {
        AIPrompt::create([
            'name' => 'configured',
            'version' => 1,
            'template' => 'Template',
            'is_active' => true,
            'metadata' => [
                'model' => 'gpt-5.1',
                'max_tokens' => 2000,
                'temperature' => 0.7,
            ],
        ]);

        $config = $this->promptRegistry->getConfig('configured');

        $this->assertIsArray($config);
        $this->assertEquals('gpt-5.1', $config['model']);
        $this->assertEquals(2000, $config['max_tokens']);
        $this->assertEquals(0.7, $config['temperature']);
    }

    public function test_get_config_returns_defaults_when_no_metadata(): void
    {
        AIPrompt::create([
            'name' => 'no_config',
            'version' => 1,
            'template' => 'Template',
            'is_active' => true,
            'metadata' => null,
        ]);

        $config = $this->promptRegistry->getConfig('no_config');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('model', $config);
        $this->assertArrayHasKey('max_tokens', $config);
        $this->assertArrayHasKey('temperature', $config);
    }

    public function test_record_usage_creates_usage_record(): void
    {
        $prompt = AIPrompt::create([
            'name' => 'usage_tracking',
            'version' => 1,
            'template' => 'Template',
            'is_active' => true,
        ]);

        $this->promptRegistry->recordUsage($prompt->id, [
            'tokens_used' => 150,
            'response_time_ms' => 500,
            'success' => true,
        ]);

        $this->assertDatabaseHas('ai_prompt_usages', [
            'ai_prompt_id' => $prompt->id,
            'tokens_used' => 150,
        ]);
    }

    public function test_caches_prompts(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(new AIPrompt([
                'name' => 'cached',
                'template' => 'Cached template',
            ]));

        $prompt = $this->promptRegistry->get('cached');

        $this->assertEquals('Cached template', $prompt->template);
    }

    public function test_clear_cache_invalidates_cached_prompts(): void
    {
        AIPrompt::create([
            'name' => 'to_clear',
            'version' => 1,
            'template' => 'Original',
            'is_active' => true,
        ]);

        // First call caches
        $this->promptRegistry->get('to_clear');

        // Clear cache
        $this->promptRegistry->clearCache('to_clear');

        // Verify cache was cleared (implementation specific)
        Cache::shouldReceive('forget')
            ->with('prompt:to_clear')
            ->once();

        $this->promptRegistry->clearCache('to_clear');
    }

    public function test_list_all_returns_all_active_prompts(): void
    {
        AIPrompt::create([
            'name' => 'prompt_1',
            'version' => 1,
            'template' => 'Template 1',
            'is_active' => true,
        ]);

        AIPrompt::create([
            'name' => 'prompt_2',
            'version' => 1,
            'template' => 'Template 2',
            'is_active' => true,
        ]);

        AIPrompt::create([
            'name' => 'prompt_3',
            'version' => 1,
            'template' => 'Template 3 (inactive)',
            'is_active' => false,
        ]);

        $prompts = $this->promptRegistry->listAll();

        $this->assertCount(2, $prompts);
        $this->assertTrue($prompts->contains('name', 'prompt_1'));
        $this->assertTrue($prompts->contains('name', 'prompt_2'));
        $this->assertFalse($prompts->contains('name', 'prompt_3'));
    }

    public function test_create_new_version_increments_version(): void
    {
        AIPrompt::create([
            'name' => 'evolving',
            'version' => 1,
            'template' => 'Original template',
            'is_active' => true,
        ]);

        $newVersion = $this->promptRegistry->createNewVersion('evolving', [
            'template' => 'Updated template',
        ]);

        $this->assertEquals(2, $newVersion->version);
        $this->assertEquals('Updated template', $newVersion->template);
        $this->assertTrue($newVersion->is_active);
    }

    public function test_create_new_version_deactivates_old_version(): void
    {
        $original = AIPrompt::create([
            'name' => 'superseded',
            'version' => 1,
            'template' => 'Original',
            'is_active' => true,
        ]);

        $this->promptRegistry->createNewVersion('superseded', [
            'template' => 'New version',
        ]);

        $original->refresh();
        $this->assertFalse($original->is_active);
    }

    public function test_render_supports_conditional_blocks(): void
    {
        AIPrompt::create([
            'name' => 'conditional',
            'version' => 1,
            'template' => 'Hello{{#if premium}}, Premium{{/if}} User!',
            'is_active' => true,
        ]);

        $withPremium = $this->promptRegistry->render('conditional', ['premium' => true]);
        $withoutPremium = $this->promptRegistry->render('conditional', ['premium' => false]);

        $this->assertEquals('Hello, Premium User!', $withPremium);
        $this->assertEquals('Hello User!', $withoutPremium);
    }

    public function test_render_supports_loops(): void
    {
        AIPrompt::create([
            'name' => 'loop',
            'version' => 1,
            'template' => 'Skills: {{#each skills}}{{this}}, {{/each}}',
            'is_active' => true,
        ]);

        $rendered = $this->promptRegistry->render('loop', [
            'skills' => ['PHP', 'Laravel', 'JavaScript'],
        ]);

        $this->assertStringContainsString('PHP', $rendered);
        $this->assertStringContainsString('Laravel', $rendered);
        $this->assertStringContainsString('JavaScript', $rendered);
    }
}
