@extends('layouts.authenticated')

@section('title', 'Worker Invitations')
@section('page-title', 'Worker Invitations')

@section('content')
<div class="p-6">
    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Invitation Management</h1>
                <p class="text-gray-600 mt-1">Track and manage your worker invitations.</p>
            </div>
            <a href="{{ route('agency.workers.import') }}" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition font-medium">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Import Workers
            </a>
        </div>

        @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
        @endif

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <a href="{{ route('agency.workers.invitations', ['status' => 'all']) }}"
               class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition {{ $currentStatus === 'all' ? 'ring-2 ring-brand-500' : '' }}">
                <p class="text-gray-500 text-sm">Total</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
            </a>

            <a href="{{ route('agency.workers.invitations', ['status' => 'pending']) }}"
               class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition {{ $currentStatus === 'pending' ? 'ring-2 ring-brand-500' : '' }}">
                <p class="text-gray-500 text-sm">Pending</p>
                <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
            </a>

            <a href="{{ route('agency.workers.invitations', ['status' => 'viewed']) }}"
               class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition {{ $currentStatus === 'viewed' ? 'ring-2 ring-brand-500' : '' }}">
                <p class="text-gray-500 text-sm">Viewed</p>
                <p class="text-2xl font-bold text-blue-600">{{ $stats['viewed'] }}</p>
            </a>

            <a href="{{ route('agency.workers.invitations', ['status' => 'accepted']) }}"
               class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition {{ $currentStatus === 'accepted' ? 'ring-2 ring-brand-500' : '' }}">
                <p class="text-gray-500 text-sm">Accepted</p>
                <p class="text-2xl font-bold text-green-600">{{ $stats['accepted'] }}</p>
            </a>

            <a href="{{ route('agency.workers.invitations', ['status' => 'expired']) }}"
               class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition {{ $currentStatus === 'expired' ? 'ring-2 ring-brand-500' : '' }}">
                <p class="text-gray-500 text-sm">Expired</p>
                <p class="text-2xl font-bold text-gray-400">{{ $stats['expired'] }}</p>
            </a>
        </div>

        <!-- Filters and Search -->
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <form method="GET" action="{{ route('agency.workers.invitations') }}" class="flex flex-wrap items-center gap-4">
<div class="flex-1 min-w-64">
                    <label for="search-invitations" class="sr-only">Search by email or name</label>
                    <input id="search-invitations" type="text" name="search" value="{{ $currentSearch }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                           placeholder="Search by email or name..." aria-label="Search workers by email or name">
                </div>

                <input type="hidden" name="status" value="{{ $currentStatus }}">

                @if($batches->isNotEmpty())
                <div>
                    <select name="batch_id" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                        <option value="">All Batches</option>
                        @foreach($batches as $batch)
                        <option value="{{ $batch->batch_id }}" {{ request('batch_id') === $batch->batch_id ? 'selected' : '' }}>
                            Batch {{ $loop->iteration }} ({{ $batch->count }} workers) - {{ $batch->created_at->format('M d') }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    Search
                </button>

                @if($currentSearch || request('batch_id'))
                <a href="{{ route('agency.workers.invitations', ['status' => $currentStatus]) }}" class="text-gray-500 hover:text-gray-700">
                    Clear Filters
                </a>
                @endif
            </form>
        </div>

        <!-- Bulk Actions -->
        <div id="bulk-actions" class="bg-gray-100 rounded-xl p-4 hidden">
            <div class="flex items-center justify-between">
                <span class="text-gray-700"><span id="selected-count">0</span> invitation(s) selected</span>
                <div class="flex gap-2">
                    <button onclick="bulkResend()" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition text-sm">
                        Resend Selected
                    </button>
                    <button onclick="bulkCancel()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm">
                        Cancel Selected
                    </button>
                </div>
            </div>
        </div>

        <!-- Invitations Table -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                <input type="checkbox" id="select-all" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Worker</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commission</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($invitations as $invitation)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4">
                                @if(!in_array($invitation->status, ['accepted', 'cancelled']))
                                <input type="checkbox" name="invitation_ids[]" value="{{ $invitation->id }}" class="invitation-checkbox rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $invitation->name ?: 'Not provided' }}</p>
                                    <p class="text-sm text-gray-500">{{ $invitation->email ?: $invitation->phone ?: 'Link invitation' }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $invitation->type === 'bulk' ? 'bg-purple-100 text-purple-800' : '' }}
                                    {{ $invitation->type === 'email' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $invitation->type === 'link' ? 'bg-gray-100 text-gray-800' : '' }}">
                                    {{ ucfirst($invitation->type) }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $invitation->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $invitation->status === 'sent' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $invitation->status === 'viewed' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                    {{ $invitation->status === 'accepted' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $invitation->status === 'expired' ? 'bg-gray-100 text-gray-800' : '' }}
                                    {{ $invitation->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                                    {{ ucfirst($invitation->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-700">
                                {{ $invitation->preset_commission_rate ? $invitation->preset_commission_rate . '%' : 'Default' }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500">
                                {{ $invitation->sent_at ? $invitation->sent_at->format('M d, Y') : '-' }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500">
                                @if($invitation->expires_at->isPast())
                                    <span class="text-red-500">Expired</span>
                                @else
                                    {{ $invitation->expires_at->format('M d, Y') }}
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if(in_array($invitation->status, ['pending', 'sent', 'viewed']))
                                        <button onclick="copyInvitationLink({{ $invitation->id }})"
                                                class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg" title="Copy Link">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                            </svg>
                                        </button>

                                        <form action="{{ route('agency.workers.invitations.resend', $invitation->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="p-2 text-blue-500 hover:text-blue-700 hover:bg-blue-50 rounded-lg" title="Resend">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                            </button>
                                        </form>

                                        <form action="{{ route('agency.workers.invitations.cancel', $invitation->id) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Are you sure you want to cancel this invitation?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg" title="Cancel">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                    @if($invitation->status === 'accepted' && $invitation->acceptedBy)
                                        <a href="{{ route('agency.workers.index') }}" class="text-green-600 hover:text-green-700 text-sm">
                                            View Worker
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-gray-500">No invitations found.</p>
                                <a href="{{ route('agency.workers.import') }}" class="text-brand-600 hover:text-brand-700 mt-2 inline-block">
                                    Import workers to get started
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($invitations->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $invitations->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.invitation-checkbox');
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');

    selectAll?.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateBulkActions();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkActions);
    });

    function updateBulkActions() {
        const checked = document.querySelectorAll('.invitation-checkbox:checked');
        if (checked.length > 0) {
            bulkActions.classList.remove('hidden');
            selectedCount.textContent = checked.length;
        } else {
            bulkActions.classList.add('hidden');
        }
    }
});

function copyInvitationLink(id) {
    fetch(`{{ url('agency/workers/invitations') }}/${id}/link`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                navigator.clipboard.writeText(data.url).then(() => {
                    alert('Invitation link copied to clipboard!');
                });
            }
        });
}

function bulkResend() {
    const ids = Array.from(document.querySelectorAll('.invitation-checkbox:checked')).map(cb => cb.value);
    if (ids.length === 0) return;

    if (!confirm(`Resend ${ids.length} invitation(s)?`)) return;

    fetch('{{ route("agency.workers.invitations.bulk-resend") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ invitation_ids: ids })
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        location.reload();
    });
}

function bulkCancel() {
    const ids = Array.from(document.querySelectorAll('.invitation-checkbox:checked')).map(cb => cb.value);
    if (ids.length === 0) return;

    if (!confirm(`Cancel ${ids.length} invitation(s)? This cannot be undone.`)) return;

    fetch('{{ route("agency.workers.invitations.bulk-cancel") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ invitation_ids: ids })
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        location.reload();
    });
}
</script>
@endpush
