@extends('layouts.dashboard')

@section('title', 'Disputed Payments')
@section('page-title', 'Disputed Payments')
@section('page-subtitle', 'Monitor and resolve payment disputes')

@section('content')
<div class="space-y-6">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Active Disputes --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-red-600 bg-red-50 px-2 py-1 rounded">Active</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $activeDisputes ?? 0 }}</p>
            <p class="text-sm text-gray-500 mt-1">Active Disputes</p>
        </div>

        {{-- Pending Resolution --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-amber-600 bg-amber-50 px-2 py-1 rounded">Pending</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $pendingResolution ?? 0 }}</p>
            <p class="text-sm text-gray-500 mt-1">Pending Resolution</p>
        </div>

        {{-- Resolved This Week --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-green-600 bg-green-50 px-2 py-1 rounded">Resolved</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $resolvedThisWeek ?? 0 }}</p>
            <p class="text-sm text-gray-500 mt-1">Resolved This Week</p>
        </div>

        {{-- Average Resolution Time --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded">Avg Time</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $averageResolutionTime ?? '0' }} hrs</p>
            <p class="text-sm text-gray-500 mt-1">Avg Resolution Time</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            {{-- Search --}}
            <div class="flex-1 min-w-[200px]">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                    placeholder="Dispute ID, payment ID, or user name..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
            </div>

            {{-- Status Filter --}}
            <div class="min-w-[150px]">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
                    <option value="">All Statuses</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="under_review" {{ request('status') === 'under_review' ? 'selected' : '' }}>Under Review</option>
                    <option value="awaiting_response" {{ request('status') === 'awaiting_response' ? 'selected' : '' }}>Awaiting Response</option>
                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="escalated" {{ request('status') === 'escalated' ? 'selected' : '' }}>Escalated</option>
                </select>
            </div>

            {{-- SLA Status --}}
            <div class="min-w-[150px]">
                <label for="sla" class="block text-sm font-medium text-gray-700 mb-1">SLA Status</label>
                <select id="sla" name="sla" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
                    <option value="">All SLA</option>
                    <option value="on_track" {{ request('sla') === 'on_track' ? 'selected' : '' }}>On Track (Green)</option>
                    <option value="at_risk" {{ request('sla') === 'at_risk' ? 'selected' : '' }}>At Risk (Amber)</option>
                    <option value="breached" {{ request('sla') === 'breached' ? 'selected' : '' }}>Breached (Red)</option>
                </select>
            </div>

            {{-- Actions --}}
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 transition-colors">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'sla']))
                <a href="{{ route('admin.finance.disputed') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900 font-medium">
                    Clear
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Disputes Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Payment Disputes</h3>
            <span class="text-sm text-gray-500">{{ $disputes->total() ?? 0 }} disputes</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Dispute ID</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Payment</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Parties</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Amount</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Reason</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Status</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">SLA</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($disputes ?? [] as $dispute)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm text-gray-900">{{ $dispute->dispute_id ?? 'DSP-' . str_pad($dispute->id ?? 0, 6, '0', STR_PAD_LEFT) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div>
                                <span class="font-mono text-sm text-gray-900">{{ $dispute->payment->payment_id ?? 'PAY-' . str_pad($dispute->payment_id ?? 0, 8, '0', STR_PAD_LEFT) }}</span>
                                <p class="text-xs text-gray-500">{{ $dispute->payment->shift->title ?? 'Shift' }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500">Worker:</span>
                                    <span class="text-sm text-gray-900">{{ $dispute->worker->name ?? 'Worker' }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-500">Business:</span>
                                    <span class="text-sm text-gray-900">{{ $dispute->business->name ?? 'Business' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-semibold text-gray-900">${{ number_format(($dispute->amount ?? 0) / 100, 2) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600">{{ $dispute->reason ?? 'Not specified' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusColors = [
                                    'open' => 'bg-red-100 text-red-700',
                                    'under_review' => 'bg-blue-100 text-blue-700',
                                    'awaiting_response' => 'bg-amber-100 text-amber-700',
                                    'resolved' => 'bg-green-100 text-green-700',
                                    'escalated' => 'bg-purple-100 text-purple-700',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$dispute->status ?? 'open'] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst(str_replace('_', ' ', $dispute->status ?? 'Open')) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $slaStatus = $dispute->sla_status ?? 'on_track';
                                $slaColors = [
                                    'on_track' => 'bg-green-500',
                                    'at_risk' => 'bg-amber-500',
                                    'breached' => 'bg-red-500',
                                ];
                                $slaLabels = [
                                    'on_track' => 'On Track',
                                    'at_risk' => 'At Risk',
                                    'breached' => 'Breached',
                                ];
                            @endphp
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full {{ $slaColors[$slaStatus] ?? 'bg-gray-500' }}"></span>
                                <span class="text-sm {{ $slaStatus === 'breached' ? 'text-red-600 font-medium' : ($slaStatus === 'at_risk' ? 'text-amber-600' : 'text-gray-600') }}">
                                    {{ $slaLabels[$slaStatus] ?? 'Unknown' }}
                                </span>
                            </div>
                            @if(isset($dispute->sla_deadline))
                            <p class="text-xs text-gray-500 mt-1">{{ $dispute->sla_deadline->diffForHumans() }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                @if(($dispute->status ?? 'open') !== 'resolved')
                                <a href="{{ route('admin.finance.disputed.resolve', $dispute->id ?? 0) }}"
                                    class="text-green-600 hover:text-green-800 font-medium text-sm">
                                    Resolve
                                </a>
                                <span class="text-gray-300">|</span>
                                @endif
                                <a href="{{ route('admin.finance.disputed.show', $dispute->id ?? 0) }}"
                                    class="text-gray-600 hover:text-gray-900 font-medium text-sm">
                                    Details
                                </a>
                                @if(($dispute->status ?? 'open') !== 'escalated' && ($dispute->status ?? 'open') !== 'resolved')
                                <span class="text-gray-300">|</span>
                                <form action="{{ route('admin.finance.disputed.escalate', $dispute->id ?? 0) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-sm"
                                        onclick="return confirm('Escalate this dispute?')">
                                        Escalate
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-1">No disputes found</h3>
                            <p class="text-gray-500">Payment disputes will appear here when they are raised.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if(isset($disputes) && $disputes->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $disputes->links() }}
        </div>
        @endif
    </div>

    {{-- SLA Warning --}}
    @if(($slaBreachedCount ?? 0) > 0)
    <div class="bg-red-50 border border-red-200 rounded-xl p-6">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-red-800">{{ $slaBreachedCount }} Disputes Have Breached SLA</h3>
                <p class="text-sm text-red-600 mt-1">These disputes require immediate attention to maintain service quality.</p>
                <div class="mt-4">
                    <a href="{{ route('admin.finance.disputed', ['sla' => 'breached']) }}"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors">
                        View Breached Disputes
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
