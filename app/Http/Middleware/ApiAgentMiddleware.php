<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AiAgentProfile;

class ApiAgentMiddleware
{
    /**
     * Handle an incoming request for AI Agent API authentication.
     * Includes rate limiting, API key validation, and request tracking.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get API key from header
        $apiKey = $request->header('X-Agent-API-Key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'API key required',
                'message' => 'Please provide X-Agent-API-Key header.'
            ], 401);
        }

        // Find agent profile by API key
        $agentProfile = AiAgentProfile::where('api_key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (!$agentProfile) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid API key',
                'message' => 'The provided API key is invalid or has been deactivated.'
            ], 401);
        }

        // Check if API key has expired
        if ($agentProfile->api_key_expires_at && $agentProfile->api_key_expires_at < now()) {
            return response()->json([
                'success' => false,
                'error' => 'API key expired',
                'message' => 'Your API key has expired. Please generate a new one.'
            ], 401);
        }

        // Get the agent user
        $agent = $agentProfile->user;

        if (!$agent || $agent->status !== 'active') {
            return response()->json([
                'success' => false,
                'error' => 'Agent account inactive',
                'message' => 'Your agent account has been deactivated.'
            ], 403);
        }

        // Rate limiting - 60 requests per minute, 1000 per hour
        $rateLimitKey = 'api_agent_' . $agent->id;
        $requestsPerMinute = 60;
        $requestsPerHour = 1000;

        $minuteRequests = cache()->get($rateLimitKey . '_minute', 0);
        $hourRequests = cache()->get($rateLimitKey . '_hour', 0);

        if ($minuteRequests >= $requestsPerMinute) {
            return response()->json([
                'success' => false,
                'error' => 'Rate limit exceeded',
                'message' => 'Too many requests. Limit: 60 per minute.',
                'retry_after' => 60
            ], 429);
        }

        if ($hourRequests >= $requestsPerHour) {
            return response()->json([
                'success' => false,
                'error' => 'Rate limit exceeded',
                'message' => 'Too many requests. Limit: 1000 per hour.',
                'retry_after' => 3600
            ], 429);
        }

        // Increment rate limit counters
        cache()->put($rateLimitKey . '_minute', $minuteRequests + 1, 60);
        cache()->put($rateLimitKey . '_hour', $hourRequests + 1, 3600);

        // Update agent profile stats
        $agentProfile->update([
            'last_api_call_at' => now(),
            'total_api_calls' => $agentProfile->total_api_calls + 1
        ]);

        // Attach agent and profile to request
        $request->merge([
            'agent' => $agent,
            'agent_profile' => $agentProfile
        ]);

        // Set auth user for policies
        $request->setUserResolver(function () use ($agent) {
            return $agent;
        });

        return $next($request);
    }
}
