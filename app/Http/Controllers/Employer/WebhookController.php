<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:employer');
    }
    
    /**
     * List webhooks
     */
    public function index()
    {
        $company = auth('employer')->user()->company;
        
        $webhooks = Webhook::where('company_id', $company->id)
            ->withCount(['deliveries', 'deliveries as successful_deliveries' => function ($q) {
                $q->where('status', 'success');
            }])
            ->orderByDesc('created_at')
            ->get();
        
        $availableEvents = Webhook::EVENTS;
        
        return view('employer.webhooks.index', compact('webhooks', 'availableEvents'));
    }
    
    /**
     * Create webhook
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'events' => 'required|array|min:1',
            'events.*' => 'string|in:' . implode(',', Webhook::EVENTS),
            'retry_attempts' => 'nullable|integer|min:1|max:5',
            'timeout_seconds' => 'nullable|integer|min:5|max:60',
        ]);
        
        $company = auth('employer')->user()->company;
        
        // Generate webhook secret
        $secret = Str::random(32);
        
        $webhook = Webhook::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'url' => $validated['url'],
            'events' => $validated['events'],
            'secret' => $secret,
            'retry_attempts' => $validated['retry_attempts'] ?? 3,
            'timeout_seconds' => $validated['timeout_seconds'] ?? 30,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Webhook created successfully',
            'data' => $webhook,
            'secret' => $secret,
        ]);
    }
    
    /**
     * Update webhook
     */
    public function update(Request $request, Webhook $webhook)
    {
        $company = auth('employer')->user()->company;
        
        if ($webhook->company_id !== $company->id) {
            abort(403);
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|url',
            'events' => 'sometimes|array|min:1',
            'events.*' => 'string|in:' . implode(',', Webhook::EVENTS),
            'is_active' => 'sometimes|boolean',
            'retry_attempts' => 'sometimes|integer|min:1|max:5',
            'timeout_seconds' => 'sometimes|integer|min:5|max:60',
        ]);
        
        $webhook->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Webhook updated successfully',
            'data' => $webhook->fresh(),
        ]);
    }
    
    /**
     * Delete webhook
     */
    public function destroy(Webhook $webhook)
    {
        $company = auth('employer')->user()->company;
        
        if ($webhook->company_id !== $company->id) {
            abort(403);
        }
        
        $webhook->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Webhook deleted successfully',
        ]);
    }
    
    /**
     * View webhook deliveries
     */
    public function deliveries(Webhook $webhook)
    {
        $company = auth('employer')->user()->company;
        
        if ($webhook->company_id !== $company->id) {
            abort(403);
        }
        
        $deliveries = $webhook->deliveries()
            ->orderByDesc('created_at')
            ->paginate(50);
        
        return view('employer.webhooks.deliveries', compact('webhook', 'deliveries'));
    }
    
    /**
     * Test webhook
     */
    public function test(Request $request, Webhook $webhook)
    {
        $company = auth('employer')->user()->company;
        
        if ($webhook->company_id !== $company->id) {
            abort(403);
        }
        
        $testPayload = [
            'test' => true,
            'webhook_id' => $webhook->id,
            'webhook_name' => $webhook->name,
            'message' => 'This is a test webhook delivery',
        ];
        
        app(\App\Services\WebhookService::class)->trigger(
            'webhook.test',
            $testPayload,
            $company->id
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Test webhook sent. Check the deliveries tab for results.',
        ]);
    }
}
