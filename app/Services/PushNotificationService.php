<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    protected ?WebPush $webPush = null;
    
    public function __construct()
    {
        $this->initializeWebPush();
    }
    
    protected function initializeWebPush(): void
    {
        $vapidPublicKey = config('webpush.vapid.public_key');
        $vapidPrivateKey = config('webpush.vapid.private_key');
        $vapidSubject = config('webpush.vapid.subject');
        
        if (!$vapidPublicKey || !$vapidPrivateKey) {
            Log::warning('VAPID keys not configured for push notifications');
            return;
        }
        
        try {
            $this->webPush = new WebPush([
                'VAPID' => [
                    'subject' => $vapidSubject,
                    'publicKey' => $vapidPublicKey,
                    'privateKey' => $vapidPrivateKey,
                ],
            ]);
            
            $this->webPush->setReuseVAPIDHeaders(true);
        } catch (\Exception $e) {
            Log::error('Failed to initialize WebPush', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Subscribe a user to push notifications
     */
    public function subscribe(User $user, array $subscriptionData): PushSubscription
    {
        // Remove existing subscription for this endpoint
        PushSubscription::where('user_id', $user->id)
            ->where('endpoint', $subscriptionData['endpoint'])
            ->delete();
        
        return PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => $subscriptionData['endpoint'],
            'public_key' => $subscriptionData['keys']['p256dh'] ?? null,
            'auth_token' => $subscriptionData['keys']['auth'] ?? null,
            'content_encoding' => $subscriptionData['contentEncoding'] ?? 'aesgcm',
            'expiration_time' => $subscriptionData['expirationTime'] ?? null,
        ]);
    }
    
    /**
     * Unsubscribe a user from push notifications
     */
    public function unsubscribe(User $user, string $endpoint): bool
    {
        return PushSubscription::where('user_id', $user->id)
            ->where('endpoint', $endpoint)
            ->delete() > 0;
    }
    
    /**
     * Send push notification to a user
     */
    public function sendToUser(User $user, array $payload): array
    {
        $subscriptions = PushSubscription::where('user_id', $user->id)->get();
        
        $results = [];
        foreach ($subscriptions as $subscription) {
            $result = $this->sendNotification($subscription, $payload);
            $results[] = $result;
            
            if ($result['success'] === false && $result['expired']) {
                $subscription->delete();
            }
        }
        
        return $results;
    }
    
    /**
     * Send notification to a specific subscription
     */
    public function sendNotification(PushSubscription $pushSubscription, array $payload): array
    {
        if (!$this->webPush) {
            return ['success' => false, 'error' => 'WebPush not initialized', 'expired' => false];
        }
        
        try {
            $subscription = Subscription::create([
                'endpoint' => $pushSubscription->endpoint,
                'publicKey' => $pushSubscription->public_key,
                'authToken' => $pushSubscription->auth_token,
                'contentEncoding' => $pushSubscription->content_encoding ?? 'aesgcm',
            ]);
            
            $this->webPush->queueNotification(
                $subscription,
                json_encode($payload)
            );
            
            foreach ($this->webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    return ['success' => true, 'expired' => false];
                }
                
                $expired = in_array($report->getResponse()?->getStatusCode(), [404, 410]);
                
                return [
                    'success' => false,
                    'error' => $report->getReason(),
                    'expired' => $expired,
                ];
            }
            
            return ['success' => true, 'expired' => false];
            
        } catch (\Exception $e) {
            Log::error('Push notification failed', [
                'error' => $e->getMessage(),
                'subscription_id' => $pushSubscription->id,
            ]);
            
            return ['success' => false, 'error' => $e->getMessage(), 'expired' => false];
        }
    }
    
    /**
     * Send notification to multiple users
     */
    public function sendToUsers(array $userIds, array $payload): array
    {
        $results = [];
        
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $results[$userId] = $this->sendToUser($user, $payload);
            }
        }
        
        return $results;
    }
    
    /**
     * Send job alert notification
     */
    public function sendJobAlert(User $user, array $job): array
    {
        $payload = [
            'title' => 'New Job Match!',
            'body' => "{$job['title']} at {$job['company']}",
            'icon' => '/images/icons/icon-192x192.png',
            'badge' => '/images/icons/badge-72x72.png',
            'tag' => 'job-alert-' . $job['id'],
            'data' => [
                'type' => 'job_alert',
                'job_id' => $job['id'],
                'url' => route('jobs.show', $job['id']),
            ],
            'actions' => [
                ['action' => 'view', 'title' => 'View Job'],
                ['action' => 'save', 'title' => 'Save'],
            ],
        ];
        
        return $this->sendToUser($user, $payload);
    }
    
    /**
     * Send application update notification
     */
    public function sendApplicationUpdate(User $user, array $application): array
    {
        $statusMessages = [
            'under_review' => 'Your application is being reviewed',
            'interview_scheduled' => 'Interview scheduled!',
            'offer_received' => 'Congratulations! You received an offer',
            'rejected' => 'Application update',
        ];
        
        $payload = [
            'title' => $statusMessages[$application['status']] ?? 'Application Update',
            'body' => "{$application['job_title']} at {$application['company']}",
            'icon' => '/images/icons/icon-192x192.png',
            'badge' => '/images/icons/badge-72x72.png',
            'tag' => 'application-' . $application['id'],
            'data' => [
                'type' => 'application_update',
                'application_id' => $application['id'],
                'status' => $application['status'],
                'url' => route('applications.show', $application['id']),
            ],
        ];
        
        return $this->sendToUser($user, $payload);
    }
    
    /**
     * Send interview reminder notification
     */
    public function sendInterviewReminder(User $user, array $interview): array
    {
        $payload = [
            'title' => '📅 Interview Reminder',
            'body' => "Interview with {$interview['company']} in {$interview['time_until']}",
            'icon' => '/images/icons/icon-192x192.png',
            'badge' => '/images/icons/badge-72x72.png',
            'tag' => 'interview-reminder-' . $interview['id'],
            'requireInteraction' => true,
            'data' => [
                'type' => 'interview_reminder',
                'interview_id' => $interview['id'],
                'url' => route('applications.show', $interview['application_id']),
            ],
            'actions' => [
                ['action' => 'prepare', 'title' => 'Prepare'],
                ['action' => 'reschedule', 'title' => 'Reschedule'],
            ],
        ];
        
        return $this->sendToUser($user, $payload);
    }
    
    /**
     * Send message notification
     */
    public function sendMessageNotification(User $user, array $message): array
    {
        $payload = [
            'title' => "New message from {$message['sender_name']}",
            'body' => Str::limit($message['content'], 100),
            'icon' => $message['sender_avatar'] ?? '/images/icons/icon-192x192.png',
            'badge' => '/images/icons/badge-72x72.png',
            'tag' => 'message-' . $message['conversation_id'],
            'data' => [
                'type' => 'message',
                'conversation_id' => $message['conversation_id'],
                'url' => route('messages.show', $message['conversation_id']),
            ],
            'actions' => [
                ['action' => 'reply', 'title' => 'Reply'],
                ['action' => 'view', 'title' => 'View'],
            ],
        ];
        
        return $this->sendToUser($user, $payload);
    }
    
    /**
     * Get VAPID public key for client-side subscription
     */
    public function getPublicKey(): ?string
    {
        return config('webpush.vapid.public_key');
    }
}
