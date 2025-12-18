@extends('layouts.dashboard')

@section('title', 'Send History')
@section('page-title', 'Message History')
@section('page-subtitle', 'View all messages sent using your templates')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="{{ route('business.communication-templates.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Templates
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <form method="GET" action="{{ route('business.communication-templates.history') }}" class="flex flex-wrap items-center gap-4">
            <div class="w-48">
                <select name="template_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500">
                    <option value="">All Templates</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}" {{ request('template_id') == $template->id ? 'selected' : '' }}>
                            {{ $template->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <select name="status" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500">
                    <option value="">All Status</option>
                    <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>
            <div class="w-36">
                <select name="channel" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500">
                    <option value="">All Channels</option>
                    <option value="email" {{ request('channel') == 'email' ? 'selected' : '' }}>Email</option>
                    <option value="sms" {{ request('channel') == 'sms' ? 'selected' : '' }}>SMS</option>
                    <option value="in_app" {{ request('channel') == 'in_app' ? 'selected' : '' }}>In-App</option>
                </select>
            </div>
            <div class="w-40">
                <input type="date" name="date_from" value="{{ request('date_from') }}" placeholder="From date"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500">
            </div>
            <div class="w-40">
                <input type="date" name="date_to" value="{{ request('date_to') }}" placeholder="To date"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500">
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                Filter
            </button>
            @if(request()->hasAny(['template_id', 'status', 'channel', 'date_from', 'date_to']))
                <a href="{{ route('business.communication-templates.history') }}" class="text-sm text-gray-500 hover:text-gray-700">
                    Clear filters
                </a>
            @endif
        </form>
    </div>

    <!-- History Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Recipient
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Template
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Channel
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Sent At
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($sends as $send)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                                    <span class="text-sm font-medium text-gray-600">{{ substr($send->recipient->name ?? '?', 0, 1) }}</span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $send->recipient->name ?? 'Unknown' }}</div>
                                    <div class="text-sm text-gray-500">{{ $send->recipient->email ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $send->template->name ?? 'Deleted' }}</div>
                            @if($send->shift)
                                <div class="text-xs text-gray-500">Shift: {{ $send->shift->title }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center text-sm text-gray-500">
                                @if($send->channel === 'email')
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                @elseif($send->channel === 'sms')
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                    </svg>
                                @endif
                                {{ ucfirst(str_replace('_', ' ', $send->channel)) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $send->status === 'sent' || $send->status === 'delivered' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $send->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $send->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ ucfirst($send->status) }}
                            </span>
                            @if($send->status === 'failed' && $send->error_message)
                                <p class="text-xs text-red-500 mt-1 truncate max-w-xs" title="{{ $send->error_message }}">
                                    {{ Str::limit($send->error_message, 30) }}
                                </p>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $send->sent_at?->format('M j, Y g:i A') ?? $send->created_at->format('M j, Y g:i A') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button type="button" onclick="showMessageDetail({{ $send->id }})" class="text-gray-600 hover:text-gray-900">
                                View
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No messages sent yet</h3>
                            <p class="mt-1 text-sm text-gray-500">Start by sending a template to your workers.</p>
                            <div class="mt-6">
                                <a href="{{ route('business.communication-templates.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-gray-900 hover:bg-gray-800">
                                    View Templates
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($sends->hasPages())
        <div class="mt-6">
            {{ $sends->withQueryString()->links() }}
        </div>
    @endif
</div>

<!-- Message Detail Modal -->
<div id="messageDetailModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeMessageDetail()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg font-medium text-gray-900 mb-4" id="modal-title">Message Details</h3>
                <div id="messageContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeMessageDetail()" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:w-auto sm:text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showMessageDetail(sendId) {
    // For now, show a simple message. Could be enhanced with AJAX to load full details.
    const modal = document.getElementById('messageDetailModal');
    const content = document.getElementById('messageContent');

    // Find the row and extract data
    const row = event.target.closest('tr');
    const template = row.querySelector('td:nth-child(2) .text-gray-900').textContent;
    const recipient = row.querySelector('td:nth-child(1) .text-gray-900').textContent;
    const status = row.querySelector('td:nth-child(4) span').textContent.trim();
    const sentAt = row.querySelector('td:nth-child(5)').textContent.trim();

    content.innerHTML = `
        <dl class="space-y-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">Recipient</dt>
                <dd class="mt-1 text-sm text-gray-900">${recipient}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Template</dt>
                <dd class="mt-1 text-sm text-gray-900">${template}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Status</dt>
                <dd class="mt-1 text-sm text-gray-900">${status}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Sent At</dt>
                <dd class="mt-1 text-sm text-gray-900">${sentAt}</dd>
            </div>
        </dl>
    `;

    modal.classList.remove('hidden');
}

function closeMessageDetail() {
    document.getElementById('messageDetailModal').classList.add('hidden');
}
</script>
@endpush
@endsection
