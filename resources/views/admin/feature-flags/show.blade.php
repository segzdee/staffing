@extends('layouts.dashboard')

@section('title', $featureFlag->name)
@section('page-title', $featureFlag->name)
@section('page-subtitle', 'Feature flag details and history')

@section('content')
<div class="space-y-6">
    {{-- Header Actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.feature-flags.index') }}" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ $featureFlag->name }}</h2>
                <p class="text-sm font-mono text-gray-500">{{ $featureFlag->key }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            {{-- Toggle Button --}}
            <form method="POST" action="{{ route('admin.feature-flags.toggle', $featureFlag) }}" class="inline">
                @csrf
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium rounded-lg {{ $featureFlag->is_enabled ? 'text-green-700 bg-green-50 border border-green-200 hover:bg-green-100' : 'text-gray-700 bg-gray-50 border border-gray-200 hover:bg-gray-100' }}">
                    @if($featureFlag->is_enabled)
                        <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 7H7a5 5 0 000 10h10a5 5 0 000-10zm0 8a3 3 0 110-6 3 3 0 010 6z"/>
                        </svg>
                        Enabled
                    @else
                        <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M7 7h10a5 5 0 010 10H7A5 5 0 017 7zm0 8a3 3 0 100-6 3 3 0 000 6z"/>
                        </svg>
                        Disabled
                    @endif
                </button>
            </form>

            {{-- Edit Button --}}
            <a href="{{ route('admin.feature-flags.edit', $featureFlag) }}" class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Details Card --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Details</h3>
                </div>
                <div class="p-6 space-y-4">
                    @if($featureFlag->description)
                        <div>
                            <label class="text-xs font-medium text-gray-500 uppercase">Description</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $featureFlag->description }}</p>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-medium text-gray-500 uppercase">Status</label>
                            @php
                                $statusLabel = $featureFlag->getStatusLabel();
                                $statusColor = $featureFlag->getStatusColor();
                                $colorClasses = match($statusColor) {
                                    'green' => 'bg-green-100 text-green-700',
                                    'red' => 'bg-red-100 text-red-700',
                                    'yellow' => 'bg-yellow-100 text-yellow-700',
                                    'blue' => 'bg-blue-100 text-blue-700',
                                    'purple' => 'bg-purple-100 text-purple-700',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <p class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClasses }}">
                                    {{ $statusLabel }}
                                </span>
                            </p>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-gray-500 uppercase">Rollout</label>
                            <div class="mt-1 flex items-center">
                                <div class="w-20 bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="bg-gray-900 h-2 rounded-full" style="width: {{ $featureFlag->rollout_percentage }}%"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-900">{{ $featureFlag->rollout_percentage }}%</span>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-gray-500 uppercase">Start Date</label>
                            <p class="mt-1 text-sm text-gray-900">
                                {{ $featureFlag->starts_at ? $featureFlag->starts_at->format('M j, Y g:i A') : 'Not set' }}
                            </p>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-gray-500 uppercase">End Date</label>
                            <p class="mt-1 text-sm text-gray-900">
                                {{ $featureFlag->ends_at ? $featureFlag->ends_at->format('M j, Y g:i A') : 'Not set' }}
                            </p>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-gray-500 uppercase">Created</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $featureFlag->created_at->format('M j, Y g:i A') }}</p>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-gray-500 uppercase">Last Updated</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $featureFlag->updated_at->format('M j, Y g:i A') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Targeting Card --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Targeting</h3>
                </div>
                <div class="p-6 space-y-4">
                    {{-- Users --}}
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Specific Users</label>
                        @if(!empty($featureFlag->enabled_for_users))
                            <div class="mt-1 flex flex-wrap gap-1">
                                @foreach($featureFlag->enabled_for_users as $userId)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700">
                                        User #{{ $userId }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-1 text-sm text-gray-500">No specific users targeted</p>
                        @endif
                    </div>

                    {{-- Roles --}}
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Roles</label>
                        @if(!empty($featureFlag->enabled_for_roles))
                            <div class="mt-1 flex flex-wrap gap-1">
                                @foreach($featureFlag->enabled_for_roles as $role)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-50 text-purple-700 capitalize">
                                        {{ $role }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-1 text-sm text-gray-500">No specific roles targeted</p>
                        @endif
                    </div>

                    {{-- Tiers --}}
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Subscription Tiers</label>
                        @if(!empty($featureFlag->enabled_for_tiers))
                            <div class="mt-1 flex flex-wrap gap-1">
                                @foreach($featureFlag->enabled_for_tiers as $tier)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700 capitalize">
                                        {{ $tier }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-1 text-sm text-gray-500">No specific tiers targeted</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Usage Code Snippet --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Usage</h3>
                </div>
                <div class="p-6 space-y-4">
                    {{-- PHP --}}
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">PHP / Controller</label>
                        <pre class="mt-1 p-3 bg-gray-900 text-gray-100 rounded-lg text-sm overflow-x-auto"><code>if (feature('{{ $featureFlag->key }}')) {
    // Feature is enabled
}</code></pre>
                    </div>

                    {{-- Blade --}}
                    <div>
                        <label class="text-xs font-medium text-gray-500 uppercase">Blade Template</label>
                        <pre class="mt-1 p-3 bg-gray-900 text-gray-100 rounded-lg text-sm overflow-x-auto"><code>@&#64;feature('{{ $featureFlag->key }}')
    {{-- Feature content --}}
@&#64;endfeature</code></pre>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Quick Stats --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Quick Stats</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Is Active</span>
                        <span class="text-sm font-medium {{ $featureFlag->isWithinDateRange() && $featureFlag->is_enabled ? 'text-green-600' : 'text-gray-600' }}">
                            {{ $featureFlag->isWithinDateRange() && $featureFlag->is_enabled ? 'Yes' : 'No' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Created By</span>
                        <span class="text-sm font-medium text-gray-900">
                            {{ $featureFlag->creator->name ?? 'System' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Log Entries</span>
                        <span class="text-sm font-medium text-gray-900">{{ $history->total() }}</span>
                    </div>
                </div>
            </div>

            {{-- Activity Log --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Activity Log</h3>
                </div>
                @if($history->isEmpty())
                    <div class="p-6 text-center text-gray-500">
                        <p class="text-sm">No activity recorded yet.</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                        @foreach($history as $log)
                            <div class="px-6 py-3 hover:bg-gray-50">
                                <div class="flex items-start gap-3">
                                    @php
                                        $actionColor = $log->action_color;
                                        $dotClass = match($actionColor) {
                                            'green' => 'bg-green-500',
                                            'red' => 'bg-red-500',
                                            'blue' => 'bg-blue-500',
                                            default => 'bg-gray-400',
                                        };
                                    @endphp
                                    <div class="mt-1.5 w-2 h-2 rounded-full {{ $dotClass }}"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900">{{ $log->action_description }}</p>
                                        <p class="text-xs text-gray-500">{{ $log->getChangeSummary() }}</p>
                                        <p class="mt-1 text-xs text-gray-400">
                                            {{ $log->user->name ?? 'System' }} - {{ $log->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($history->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200">
                            {{ $history->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
