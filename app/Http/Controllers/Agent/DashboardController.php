<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AiAgentProfile;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Show the AI Agent dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();

        // Verify user is an AI Agent
        if (!$user->isAiAgent()) {
            return redirect()->route('dashboard')
                ->with('error', 'Access denied. AI Agent access required.');
        }

        // Get agent profile
        $agentProfile = $user->aiAgentProfile;

        if (!$agentProfile) {
            return redirect()->route('dashboard')
                ->with('error', 'AI Agent profile not found.');
        }

        // API usage stats
        $stats = [
            'total_api_calls' => $agentProfile->total_api_calls ?? 0,
            'last_api_call_at' => $agentProfile->last_api_call_at,
            'api_key' => $agentProfile->api_key,
            'api_key_expires_at' => $agentProfile->api_key_expires_at,
            'is_active' => $agentProfile->is_active,
            'capabilities' => $agentProfile->capabilities ?? [],
        ];

        return view('agent.dashboard', compact('user', 'agentProfile', 'stats'));
    }
}

