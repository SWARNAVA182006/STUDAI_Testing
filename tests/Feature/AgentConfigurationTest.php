<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AgentConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected AgentConfiguration $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'account_type' => 'job_seeker',
        ]);

        $this->config = AgentConfiguration::create([
            'user_id' => $this->user->id,
            'is_active' => true,
            'daily_application_limit' => 5,
            'applications_this_month' => 0,
            'match_threshold_percentage' => 70,
            'application_aggressiveness' => 'moderate',
            'active_hours' => ['start' => '09:00', 'end' => '18:00'],
            'active_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        ]);
    }

    /** @test */
    public function it_can_check_if_agent_can_apply_today()
    {
        // Within limits
        $this->assertTrue($this->config->canApplyToday());

        // At limit
        $this->config->update(['daily_application_limit' => 3]);
        $this->config->applications()->createMany([
            ['user_id' => $this->user->id, 'discovered_job_id' => 1, 'status' => 'pending'],
            ['user_id' => $this->user->id, 'discovered_job_id' => 2, 'status' => 'pending'],
            ['user_id' => $this->user->id, 'discovered_job_id' => 3, 'status' => 'submitted'],
        ]);

        $this->config->refresh();
        $this->assertFalse($this->config->canApplyToday());
    }

    /** @test */
    public function it_can_check_monthly_application_limit()
    {
        $this->config->update([
            'applications_this_month' => 99,
        ]);

        $this->assertFalse($this->config->hasReachedMonthlyLimit());

        $this->config->update(['applications_this_month' => 100]);
        $this->assertTrue($this->config->hasReachedMonthlyLimit());
    }

    /** @test */
    public function it_can_check_if_within_active_hours()
    {
        // Mock current time to be within active hours (e.g., 10:00)
        $this->travelTo(now()->setTime(10, 0));
        $this->assertTrue($this->config->isInActiveHours());

        // Mock current time to be outside active hours (e.g., 20:00)
        $this->travelTo(now()->setTime(20, 0));
        $this->assertFalse($this->config->isInActiveHours());

        // No active hours restriction
        $this->config->update(['active_hours' => null]);
        $this->assertTrue($this->config->isInActiveHours());
    }

    /** @test */
    public function it_can_handle_active_hours_in_different_formats()
    {
        // Object format
        $this->config->update(['active_hours' => ['start' => '09:00', 'end' => '17:00']]);
        $this->travelTo(now()->setTime(10, 0));
        $this->assertTrue($this->config->isInActiveHours());

        // Array of ranges format
        $this->config->update(['active_hours' => ['09:00-12:00', '14:00-18:00']]);
        $this->travelTo(now()->setTime(10, 30));
        $this->assertTrue($this->config->isInActiveHours());

        $this->travelTo(now()->setTime(13, 0));
        $this->assertFalse($this->config->isInActiveHours());
    }

    /** @test */
    public function it_can_check_if_day_is_active()
    {
        $this->config->update([
            'active_days' => ['monday', 'tuesday', 'wednesday'],
        ]);

        // Mock Monday
        $monday = now()->parse('next monday');
        $this->travelTo($monday);
        $this->assertTrue($this->config->isActiveDay());

        // Mock Saturday
        $saturday = now()->parse('next saturday');
        $this->travelTo($saturday);
        $this->assertFalse($this->config->isActiveDay());

        // No day restrictions
        $this->config->update(['active_days' => null]);
        $this->assertTrue($this->config->isActiveDay());
    }

    /** @test */
    public function it_calculates_aggressiveness_multiplier_correctly()
    {
        $this->config->update(['application_aggressiveness' => 'conservative']);
        $this->assertEquals(0.7, $this->config->getAggressivenessMultiplier());

        $this->config->update(['application_aggressiveness' => 'moderate']);
        $this->assertEquals(1.0, $this->config->getAggressivenessMultiplier());

        $this->config->update(['application_aggressiveness' => 'aggressive']);
        $this->assertEquals(1.5, $this->config->getAggressivenessMultiplier());
    }

    /** @test */
    public function it_can_update_run_schedule()
    {
        $this->assertNull($this->config->last_run_at);

        $this->config->updateRunSchedule();
        $this->config->refresh();

        $this->assertNotNull($this->config->last_run_at);
        $this->assertNotNull($this->config->next_run_at);
        $this->assertTrue($this->config->next_run_at->isAfter(now()));
    }

    /** @test */
    public function active_scope_only_returns_active_configurations()
    {
        // Create inactive config
        AgentConfiguration::create([
            'user_id' => User::factory()->create(['account_type' => 'job_seeker'])->id,
            'is_active' => false,
            'daily_application_limit' => 5,
        ]);

        $activeConfigs = AgentConfiguration::active()->get();
        $this->assertCount(1, $activeConfigs);
        $this->assertTrue($activeConfigs->first()->is_active);
    }

    /** @test */
    public function ready_to_run_scope_filters_correctly()
    {
        // Set next run in past
        $this->config->update(['next_run_at' => now()->subHour()]);

        // Create config with future next_run_at
        AgentConfiguration::create([
            'user_id' => User::factory()->create(['account_type' => 'job_seeker'])->id,
            'is_active' => true,
            'daily_application_limit' => 5,
            'next_run_at' => now()->addDay(),
        ]);

        $readyConfigs = AgentConfiguration::readyToRun()->get();
        $this->assertCount(1, $readyConfigs);
        $this->assertEquals($this->config->id, $readyConfigs->first()->id);
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        $this->assertInstanceOf(User::class, $this->config->user);
        $this->assertEquals($this->user->id, $this->config->user->id);
    }
}
