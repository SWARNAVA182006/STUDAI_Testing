<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiTokenController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:employer');
    }
    
    /**
     * List API tokens
     */
    public function index()
    {
        $company = auth('employer')->user()->company;
        
        $tokens = ApiToken::where('company_id', $company->id)
            ->orderByDesc('created_at')
            ->get();
        
        return view('employer.api-tokens.index', compact('tokens'));
    }
    
    /**
     * Create API token
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'required|array',
            'abilities.*' => 'string|in:jobs.read,jobs.write,applications.read,applications.write,company.read,company.write,webhooks.manage,*',
            'rate_limit' => 'required|integer|min:10|max:1000',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);
        
        $company = auth('employer')->user()->company;
        
        // Check subscription limits
        $currentTokens = ApiToken::where('company_id', $company->id)
            ->active()
            ->count();
        
        // Free tier: 1 token, Pro: 5 tokens, Enterprise: unlimited
        $maxTokens = auth('employer')->user()->subscription?->subscriptionPlan?->api_tokens_limit ?? 1;
        
        if ($currentTokens >= $maxTokens) {
            return response()->json([
                'success' => false,
                'message' => 'You have reached the maximum number of API tokens. Please upgrade your plan.',
            ], 403);
        }
        
        // Generate token
        $plainToken = Str::random(60);
        $hashedToken = hash('sha256', $plainToken);
        
        $apiToken = ApiToken::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'token' => $hashedToken,
            'abilities' => $validated['abilities'],
            'rate_limit' => $validated['rate_limit'],
            'expires_at' => $validated['expires_in_days'] 
                ? now()->addDays($validated['expires_in_days'])
                : null,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'API token created successfully. Copy it now - it will not be shown again!',
            'token' => $plainToken,
            'token_id' => $apiToken->id,
        ]);
    }
    
    /**
     * Revoke API token
     */
    public function destroy(ApiToken $apiToken)
    {
        $company = auth('employer')->user()->company;
        
        if ($apiToken->company_id !== $company->id) {
            abort(403);
        }
        
        $apiToken->update(['is_active' => false]);
        
        return response()->json([
            'success' => true,
            'message' => 'API token revoked',
        ]);
    }
    
    /**
     * Get API documentation
     */
    public function documentation()
    {
        return view('employer.api-tokens.documentation');
    }
}
