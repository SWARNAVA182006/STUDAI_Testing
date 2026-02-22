<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AtsConnection;
use App\Models\AtsProvider;
use App\Services\Ats\AtsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AtsController extends Controller
{
    public function __construct(
        protected AtsService $atsService
    ) {}

    /**
     * Display ATS integration dashboard.
     */
    public function index(): View
    {
        $user = auth()->user();
        $company = $user->company;

        $connections = $company
            ? $company->atsConnections()->with('provider')->get()
            : collect();

        $providers = AtsProvider::active()->get();
        $availableProviders = $this->atsService->getAvailableProviders();

        return view('ats.index', compact('connections', 'providers', 'availableProviders'));
    }

    /**
     * Show connection creation form.
     */
    public function create(Request $request): View
    {
        $providerId = $request->query('provider');
        $provider = $providerId ? AtsProvider::find($providerId) : null;
        $providers = AtsProvider::active()->get();

        return view('ats.create', compact('provider', 'providers'));
    }

    /**
     * Store a new ATS connection.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ats_provider_id' => 'required|exists:ats_providers,id',
            'name' => 'required|string|max:255',
            'credentials' => 'required|array',
        ]);

        $user = auth()->user();
        $company = $user->company;

        if (!$company) {
            return redirect()->route('ats.index')
                ->with('error', 'You must belong to a company to create ATS connections.');
        }

        $provider = AtsProvider::findOrFail($validated['ats_provider_id']);

        $connection = AtsConnection::create([
            'company_id' => $company->id,
            'ats_provider_id' => $provider->id,
            'name' => $validated['name'],
            'is_active' => false,
            'sync_status' => 'pending',
        ]);

        $connection->setEncryptedCredentials($validated['credentials']);

        // Check if OAuth is required
        if ($provider->auth_type === 'oauth2') {
            $authUrl = $this->atsService->getAuthorizationUrl($connection);
            if ($authUrl) {
                return redirect($authUrl);
            }
        }

        // For API key auth, test connection immediately
        if ($this->atsService->testConnection($connection)) {
            $connection->update(['is_active' => true]);
            return redirect()->route('ats.show', $connection)
                ->with('success', 'ATS connection created and verified successfully.');
        }

        return redirect()->route('ats.show', $connection)
            ->with('warning', 'Connection created but could not verify credentials. Please check your API key.');
    }

    /**
     * Show connection details.
     */
    public function show(AtsConnection $connection): View
    {
        $this->authorizeConnection($connection);

        $connection->load(['provider', 'candidateMappings', 'jobMappings']);
        $stats = $this->atsService->getSyncStats($connection);
        $recentLogs = $connection->syncLogs()->latest()->take(10)->get();

        return view('ats.show', compact('connection', 'stats', 'recentLogs'));
    }

    /**
     * Update connection settings.
     */
    public function update(Request $request, AtsConnection $connection): RedirectResponse
    {
        $this->authorizeConnection($connection);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'sync_settings' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['name'])) {
            $connection->update(['name' => $validated['name']]);
        }

        if (isset($validated['sync_settings'])) {
            $connection->update(['sync_settings' => $validated['sync_settings']]);
        }

        if (isset($validated['is_active'])) {
            $connection->update(['is_active' => $validated['is_active']]);
        }

        return redirect()->route('ats.show', $connection)
            ->with('success', 'Connection settings updated.');
    }

    /**
     * Delete connection.
     */
    public function destroy(AtsConnection $connection): RedirectResponse
    {
        $this->authorizeConnection($connection);

        $connection->delete();

        return redirect()->route('ats.index')
            ->with('success', 'ATS connection deleted.');
    }

    /**
     * Handle OAuth callback.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        $code = $request->query('code');
        $state = $request->query('state');

        if (!$code) {
            return redirect()->route('ats.index')
                ->with('error', 'OAuth authorization failed. No code received.');
        }

        // Find the pending connection
        $connection = AtsConnection::whereHas('provider', fn($q) => $q->where('slug', $provider))
            ->where('is_active', false)
            ->where('sync_status', 'pending')
            ->latest()
            ->first();

        if (!$connection) {
            return redirect()->route('ats.index')
                ->with('error', 'No pending connection found for this provider.');
        }

        try {
            $this->atsService->handleOAuthCallback($connection, $code);

            return redirect()->route('ats.show', $connection)
                ->with('success', 'Successfully connected to ' . $connection->provider->name);
        } catch (\Exception $e) {
            return redirect()->route('ats.show', $connection)
                ->with('error', 'Failed to complete OAuth: ' . $e->getMessage());
        }
    }

    /**
     * Trigger manual sync.
     */
    public function sync(Request $request, AtsConnection $connection): RedirectResponse
    {
        $this->authorizeConnection($connection);

        $syncType = $request->input('type', 'all');

        if ($syncType === 'candidates' || $syncType === 'all') {
            $this->atsService->syncCandidates($connection);
        }

        if ($syncType === 'jobs' || $syncType === 'all') {
            $this->atsService->syncJobs($connection);
        }

        return redirect()->route('ats.show', $connection)
            ->with('success', 'Sync initiated. Check sync logs for results.');
    }

    /**
     * Test connection.
     */
    public function testConnection(AtsConnection $connection): JsonResponse
    {
        $this->authorizeConnection($connection);

        $success = $this->atsService->testConnection($connection);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Connection successful!' : 'Connection failed. Please check your credentials.',
        ]);
    }

    /**
     * Get sync logs.
     */
    public function syncLogs(AtsConnection $connection): View
    {
        $this->authorizeConnection($connection);

        $logs = $connection->syncLogs()->latest()->paginate(20);

        return view('ats.sync-logs', compact('connection', 'logs'));
    }

    /**
     * View candidate mappings.
     */
    public function candidates(AtsConnection $connection): View
    {
        $this->authorizeConnection($connection);

        $mappings = $connection->candidateMappings()
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('ats.candidates', compact('connection', 'mappings'));
    }

    /**
     * View job mappings.
     */
    public function jobs(AtsConnection $connection): View
    {
        $this->authorizeConnection($connection);

        $mappings = $connection->jobMappings()
            ->with('jobPosting')
            ->latest()
            ->paginate(20);

        return view('ats.jobs', compact('connection', 'mappings'));
    }

    /**
     * Handle incoming webhook.
     */
    public function webhook(Request $request, string $provider, string $connectionId): JsonResponse
    {
        $connection = AtsConnection::find($connectionId);

        if (!$connection || $connection->provider->slug !== $provider) {
            return response()->json(['error' => 'Invalid connection'], 404);
        }

        $providerService = $this->atsService->getProviderForConnection($connection);

        if (!$providerService) {
            return response()->json(['error' => 'Provider not found'], 404);
        }

        $payload = $providerService->parseWebhookPayload(
            $request->all(),
            $request->headers->all()
        );

        // Log webhook
        $webhook = $connection->webhooks()
            ->where('event_type', $payload['event_type'])
            ->first();

        if ($webhook) {
            $webhook->recordSuccess();
        }

        // Process webhook based on event type
        // This would dispatch jobs to handle specific webhook events
        // For now, just log and acknowledge
        \Log::info('ATS Webhook received', [
            'connection_id' => $connection->id,
            'event_type' => $payload['event_type'],
        ]);

        return response()->json(['status' => 'received']);
    }

    /**
     * Authorize that the user can access this connection.
     */
    protected function authorizeConnection(AtsConnection $connection): void
    {
        $user = auth()->user();
        $company = $user->company;

        if (!$company || $connection->company_id !== $company->id) {
            abort(403, 'Unauthorized access to this connection.');
        }
    }
}
