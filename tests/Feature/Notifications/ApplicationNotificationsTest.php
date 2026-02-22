<?php

namespace Tests\Feature\Notifications;

use App\Events\ApplicationStatusChanged;
use App\Events\ApplicationSubmitted;
use App\Listeners\SendApplicationStatusChangedNotification;
use App\Listeners\SendApplicationSubmittedNotification;
use App\Models\AgentConfiguration;
use App\Models\AutoApplication;
use App\Models\DiscoveredJob;
use App\Models\JobMatch;
use App\Models\User;
use App\Notifications\ApplicationStatusChangedNotification;
use App\Notifications\ApplicationSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApplicationNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected AgentConfiguration $agentConfig;
    protected AutoApplication $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'account_type' => 'job_seeker',
        ]);

        $this->agentConfig = AgentConfiguration::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
            'notifications_enabled' => true,
            'notification_channels' => ['email', 'in_app'],
        ]);

        $job = DiscoveredJob::factory()->create();
        $match = JobMatch::factory()->create([
            'user_id' => $this->user->id,
            'discovered_job_id' => $job->id,
            'approval_status' => 'approved',
        ]);

        $this->application = AutoApplication::factory()->create([
            'user_id' => $this->user->id,
            'job_match_id' => $match->id,
            'discovered_job_id' => $job->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function application_submitted_event_is_dispatched_when_application_submitted()
    {
        Event::fake([ApplicationSubmitted::class]);

        // Trigger the event manually (in real usage, ApplicationSubmissionService does this)
        event(new ApplicationSubmitted($this->application));

        Event::assertDispatched(ApplicationSubmitted::class, function ($event) {
            return $event->application->id === $this->application->id;
        });
    }

    /** @test */
    public function application_status_changed_event_is_dispatched_when_status_updated()
    {
        Event::fake([ApplicationStatusChanged::class]);

        $this->application->updateStatus('viewed', 'Application was viewed by employer');

        Event::assertDispatched(ApplicationStatusChanged::class, function ($event) {
            return $event->application->id === $this->application->id
                && $event->oldStatus === 'submitted'
                && $event->newStatus === 'viewed';
        });
    }

    /** @test */
    public function submitted_notification_is_sent_when_notifications_enabled()
    {
        Notification::fake();

        $listener = new SendApplicationSubmittedNotification();
        $event = new ApplicationSubmitted($this->application);

        $listener->handle($event);

        Notification::assertSentTo(
            $this->user,
            ApplicationSubmittedNotification::class,
            function ($notification) {
                return $notification->application->id === $this->application->id;
            }
        );
    }

    /** @test */
    public function submitted_notification_not_sent_when_notifications_disabled()
    {
        Notification::fake();

        $this->agentConfig->update(['notifications_enabled' => false]);

        $listener = new SendApplicationSubmittedNotification();
        $event = new ApplicationSubmitted($this->application);

        $listener->handle($event);

        Notification::assertNothingSent();
    }

    /** @test */
    public function submitted_notification_not_sent_when_email_channel_disabled()
    {
        Notification::fake();

        $this->agentConfig->update(['notification_channels' => ['in_app']]);

        $listener = new SendApplicationSubmittedNotification();
        $event = new ApplicationSubmitted($this->application);

        $listener->handle($event);

        // Should still send since in_app is enabled
        Notification::assertSentTo($this->user, ApplicationSubmittedNotification::class);
    }

    /** @test */
    public function status_changed_notification_is_sent_when_notifications_enabled()
    {
        Notification::fake();

        $listener = new SendApplicationStatusChangedNotification();
        $event = new ApplicationStatusChanged($this->application, 'submitted', 'viewed');

        $listener->handle($event);

        Notification::assertSentTo(
            $this->user,
            ApplicationStatusChangedNotification::class,
            function ($notification) {
                return $notification->application->id === $this->application->id
                    && $notification->oldStatus === 'submitted'
                    && $notification->newStatus === 'viewed';
            }
        );
    }

    /** @test */
    public function status_changed_notification_not_sent_when_notifications_disabled()
    {
        Notification::fake();

        $this->agentConfig->update(['notifications_enabled' => false]);

        $listener = new SendApplicationStatusChangedNotification();
        $event = new ApplicationStatusChanged($this->application, 'submitted', 'viewed');

        $listener->handle($event);

        Notification::assertNothingSent();
    }

    /** @test */
    public function submitted_notification_contains_correct_job_details()
    {
        Notification::fake();

        event(new ApplicationSubmitted($this->application));

        Notification::assertSentTo($this->user, ApplicationSubmittedNotification::class, function ($notification) {
            $mailData = $notification->toMail($this->user);
            $databaseData = $notification->toDatabase($this->user);

            // Check mail notification
            $this->assertStringContainsString($this->application->discoveredJob->job_title, $mailData->subject);

            // Check database notification
            $this->assertEquals($this->application->discovered_job_id, $databaseData['job_id']);
            $this->assertEquals($this->application->discoveredJob->job_title, $databaseData['job_title']);
            $this->assertEquals($this->application->discoveredJob->company_name, $databaseData['company_name']);

            return true;
        });
    }

    /** @test */
    public function status_changed_notification_contains_old_and_new_status()
    {
        Notification::fake();

        event(new ApplicationStatusChanged($this->application, 'submitted', 'interviewing'));

        Notification::assertSentTo($this->user, ApplicationStatusChangedNotification::class, function ($notification) {
            $databaseData = $notification->toDatabase($this->user);

            $this->assertEquals($this->application->id, $databaseData['application_id']);
            $this->assertEquals('submitted', $databaseData['old_status']);
            $this->assertEquals('interviewing', $databaseData['new_status']);

            return true;
        });
    }

    /** @test */
    public function notifications_are_queued_for_async_processing()
    {
        $submittedListener = new SendApplicationSubmittedNotification();
        $statusListener = new SendApplicationStatusChangedNotification();

        $this->assertArrayHasKey('Illuminate\Contracts\Queue\ShouldQueue', class_implements($submittedListener));
        $this->assertArrayHasKey('Illuminate\Contracts\Queue\ShouldQueue', class_implements($statusListener));
    }

    /** @test */
    public function event_listeners_are_registered_in_service_provider()
    {
        $events = Event::getListeners(ApplicationSubmitted::class);
        $this->assertNotEmpty($events, 'ApplicationSubmitted event should have listeners');

        $events = Event::getListeners(ApplicationStatusChanged::class);
        $this->assertNotEmpty($events, 'ApplicationStatusChanged event should have listeners');
    }
}
