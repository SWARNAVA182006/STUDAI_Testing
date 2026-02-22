<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PushNotificationController extends Controller
{
    /**
     * Get VAPID public key for push subscription.
     */
    public function getVapidPublicKey(): JsonResponse
    {
        return response()->json([
            'publicKey' => config('webpush.vapid.public_key'),
        ]);
    }

    /**
     * Subscribe to push notifications.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|max:500',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        try {
            $subscription = PushSubscription::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'endpoint' => $validated['endpoint'],
                ],
                [
                    'public_key' => $validated['keys']['p256dh'],
                    'auth_token' => $validated['keys']['auth'],
                    'content_encoding' => 'aes128gcm',
                    'user_agent' => $request->userAgent(),
                    'device_type' => $this->detectDeviceType($request),
                    'last_used_at' => now(),
                ]
            );

            Log::info('Push subscription created', [
                'user_id' => auth()->id(),
                'subscription_id' => $subscription->id,
            ]);

            return response()->json([
                'message' => 'Successfully subscribed to push notifications',
                'subscription_id' => $subscription->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create push subscription', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to subscribe to push notifications',
            ], 500);
        }
    }

    /**
     * Unsubscribe from push notifications.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        $deleted = PushSubscription::where('user_id', auth()->id())
            ->where('endpoint', $validated['endpoint'])
            ->delete();

        if ($deleted) {
            Log::info('Push subscription deleted', [
                'user_id' => auth()->id(),
                'endpoint' => $validated['endpoint'],
            ]);

            return response()->json([
                'message' => 'Successfully unsubscribed from push notifications',
            ]);
        }

        return response()->json([
            'error' => 'Subscription not found',
        ], 404);
    }

    /**
     * Get user's push subscriptions.
     */
    public function getSubscriptions(): JsonResponse
    {
        $subscriptions = PushSubscription::where('user_id', auth()->id())
            ->orderBy('last_used_at', 'desc')
            ->get()
            ->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'device_type' => $subscription->device_type,
                    'user_agent' => Str::limit($subscription->user_agent, 50),
                    'last_used_at' => $subscription->last_used_at?->diffForHumans(),
                    'created_at' => $subscription->created_at->format('M d, Y'),
                ];
            });

        return response()->json([
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Delete a specific subscription.
     */
    public function deleteSubscription(PushSubscription $subscription): JsonResponse
    {
        // Ensure user owns this subscription
        if ($subscription->user_id !== auth()->id()) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        $subscription->delete();

        return response()->json([
            'message' => 'Subscription deleted successfully',
        ]);
    }

    /**
     * Get notification preferences.
     */
    public function getPreferences(): JsonResponse
    {
        $preferences = NotificationPreference::getUserPreferences(auth()->id());

        return response()->json([
            'preferences' => $preferences,
            'channels' => NotificationPreference::CHANNELS,
            'types' => NotificationPreference::TYPES,
        ]);
    }

    /**
     * Update notification preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel' => 'required|string|in:push,email,sms',
            'notification_type' => 'required|string',
            'enabled' => 'required|boolean',
            'settings' => 'nullable|array',
        ]);

        try {
            if ($validated['enabled']) {
                NotificationPreference::enable(
                    auth()->id(),
                    $validated['channel'],
                    $validated['notification_type'],
                    $validated['settings'] ?? []
                );
            } else {
                NotificationPreference::disable(
                    auth()->id(),
                    $validated['channel'],
                    $validated['notification_type']
                );
            }

            Log::info('Notification preference updated', [
                'user_id' => auth()->id(),
                'channel' => $validated['channel'],
                'type' => $validated['notification_type'],
                'enabled' => $validated['enabled'],
            ]);

            return response()->json([
                'message' => 'Preference updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update notification preference', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to update preference',
            ], 500);
        }
    }

    /**
     * Test push notification.
     */
    public function testNotification(Request $request): JsonResponse
    {
        $user = auth()->user();

        $subscriptions = PushSubscription::where('user_id', $user->id)->get();

        if ($subscriptions->isEmpty()) {
            return response()->json([
                'error' => 'No push subscriptions found',
            ], 404);
        }

        try {
            // Intentionally inline — this is a diagnostic/test endpoint, not production flow.
            $user->notify(new \App\Notifications\TestPushNotification());

            return response()->json([
                'message' => 'Test notification sent successfully',
                'count' => $subscriptions->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send test notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to send test notification',
            ], 500);
        }
    }

    /**
     * Detect device type from user agent.
     */
    private function detectDeviceType(Request $request): string
    {
        $userAgent = $request->userAgent();

        if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile/i', $userAgent)) {
            return 'mobile';
        }

        if (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        }

        return 'desktop';
    }
}
