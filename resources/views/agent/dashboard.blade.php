@extends('layouts.authenticated')

@section('title', 'AI Agent Dashboard')
@section('page-title', 'AI Agent Dashboard')

@section('sidebar-nav')
<a href="{{ route('agent.dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
    </svg>
    <span>Dashboard</span>
</a>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl p-6 text-white">
        <h2 class="text-2xl font-bold mb-2">🤖 Welcome, {{ auth()->user()->name }}!</h2>
        <p class="text-purple-100">AI Agent API Dashboard - Manage your API access and monitor usage</p>
    </div>

    <!-- API Key Section -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
            API Key
        </h3>
        <div class="bg-gray-50 rounded-lg p-4 mb-4">
            <code class="text-sm font-mono break-all">{{ $agentProfile->api_key ?? 'Not set' }}</code>
        </div>
        @if($agentProfile->api_key_expires_at)
            <p class="text-sm text-gray-600">
                <strong>Expires:</strong> {{ $agentProfile->api_key_expires_at->format('Y-m-d H:i:s') }}
                @if($agentProfile->api_key_expires_at->isPast())
                    <span class="text-red-600 font-semibold">(Expired)</span>
                @else
                    <span class="text-green-600">({{ $agentProfile->api_key_expires_at->diffForHumans() }})</span>
                @endif
            </p>
        @endif
        <p class="text-sm text-gray-600 mt-2">
            <strong>Status:</strong> 
            @if($agentProfile->is_active)
                <span class="text-green-600 font-semibold">Active</span>
            @else
                <span class="text-red-600 font-semibold">Inactive</span>
            @endif
        </p>
    </div>

    <!-- API Usage Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total API Calls</p>
                    <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_api_calls']) }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Last API Call</p>
                    <p class="text-lg font-semibold text-gray-900">
                        @if($stats['last_api_call_at'])
                            {{ \Carbon\Carbon::parse($stats['last_api_call_at'])->diffForHumans() }}
                        @else
                            Never
                        @endif
                    </p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Capabilities</p>
                    <p class="text-lg font-semibold text-gray-900">
                        {{ count($stats['capabilities']) }} enabled
                    </p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- API Documentation -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            API Endpoints
        </h3>
        <div class="space-y-3">
            <div class="border-l-4 border-blue-500 pl-4">
                <p class="font-semibold text-gray-900">Base URL</p>
                <code class="text-sm text-gray-600">{{ url('/api/agent') }}</code>
            </div>
            <div class="border-l-4 border-green-500 pl-4">
                <p class="font-semibold text-gray-900">Authentication</p>
                <code class="text-sm text-gray-600">X-Agent-API-Key: {{ $agentProfile->api_key ?? 'YOUR_API_KEY' }}</code>
            </div>
            <div class="border-l-4 border-purple-500 pl-4">
                <p class="font-semibold text-gray-900">Available Endpoints</p>
                <ul class="list-disc list-inside text-sm text-gray-600 space-y-1 mt-2">
                    <li><code>POST /api/agent/shifts</code> - Create shift</li>
                    <li><code>GET /api/agent/shifts/{id}</code> - Get shift details</li>
                    <li><code>PUT /api/agent/shifts/{id}</code> - Update shift</li>
                    <li><code>DELETE /api/agent/shifts/{id}</code> - Cancel shift</li>
                    <li><code>GET /api/agent/workers/search</code> - Search workers</li>
                    <li><code>POST /api/agent/workers/invite</code> - Invite worker</li>
                    <li><code>POST /api/agent/match/workers</code> - Match workers to shift</li>
                    <li><code>GET /api/agent/applications</code> - Get applications</li>
                    <li><code>POST /api/agent/applications/{id}/accept</code> - Accept application</li>
                    <li><code>GET /api/agent/stats</code> - Get statistics</li>
                </ul>
            </div>
            <div class="border-l-4 border-yellow-500 pl-4">
                <p class="font-semibold text-gray-900">Rate Limits</p>
                <p class="text-sm text-gray-600">60 requests per minute, 1000 requests per hour</p>
            </div>
        </div>
    </div>

    <!-- Capabilities -->
    @if(!empty($stats['capabilities']))
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4">Enabled Capabilities</h3>
        <div class="flex flex-wrap gap-2">
            @foreach($stats['capabilities'] as $capability)
                <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">
                    {{ ucfirst(str_replace('_', ' ', $capability)) }}
                </span>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection

