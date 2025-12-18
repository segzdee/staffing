@extends('layouts.dashboard')

@section('title', 'Transfer #' . $transfer->id)
@section('page-title', 'Transfer Details')
@section('page-subtitle', 'Data transfer audit log entry')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Back Button --}}
    <div>
        <a href="{{ route('admin.data-residency.transfer-logs') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Transfer Logs
        </a>
    </div>

    {{-- Transfer Header --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Transfer #{{ $transfer->id }}</h2>
                    <p class="text-sm text-gray-500 mt-1">Created {{ $transfer->created_at->format('M j, Y \a\t H:i:s') }}</p>
                </div>
                @php
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'in_progress' => 'bg-blue-100 text-blue-800',
                        'completed' => 'bg-green-100 text-green-800',
                        'failed' => 'bg-red-100 text-red-800',
                    ];
                @endphp
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium {{ $statusColors[$transfer->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ ucfirst(str_replace('_', ' ', $transfer->status)) }}
                </span>
            </div>

            {{-- Transfer Flow --}}
            <div class="flex items-center justify-center py-8 bg-gray-50 rounded-lg">
                <div class="text-center">
                    <span class="inline-flex items-center justify-center w-16 h-16 rounded-lg bg-gray-200 text-gray-700 text-xl font-bold uppercase">
                        {{ strtoupper($transfer->from_region) }}
                    </span>
                    <p class="text-sm text-gray-500 mt-2">From</p>
                </div>
                <div class="mx-8">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </div>
                <div class="text-center">
                    <span class="inline-flex items-center justify-center w-16 h-16 rounded-lg bg-blue-100 text-blue-700 text-xl font-bold uppercase">
                        {{ strtoupper($transfer->to_region) }}
                    </span>
                    <p class="text-sm text-gray-500 mt-2">To</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Details Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- User Information --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">User Information</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Name</p>
                    <p class="text-sm text-gray-900 mt-1">{{ $transfer->user->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Email</p>
                    <p class="text-sm text-gray-900 mt-1">{{ $transfer->user->email ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">User ID</p>
                    <p class="text-sm text-gray-900 font-mono mt-1">{{ $transfer->user_id }}</p>
                </div>
                @if($transfer->user)
                    <div class="pt-4">
                        <a href="{{ route('admin.data-residency.user-report', $transfer->user) }}"
                           class="text-sm text-blue-600 hover:text-blue-800">
                            View User Residency Report
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Transfer Details --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Transfer Details</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Transfer Type</p>
                    <p class="text-sm text-gray-900 mt-1">{{ ucfirst($transfer->transfer_type) }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Legal Basis</p>
                    <p class="text-sm text-gray-900 mt-1">{{ $transfer->legal_basis ?? 'Not specified' }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Duration</p>
                    <p class="text-sm text-gray-900 mt-1">
                        @if($transfer->completed_at)
                            {{ $transfer->duration }} seconds
                        @else
                            In progress...
                        @endif
                    </p>
                </div>
                @if($transfer->completed_at)
                    <div>
                        <p class="text-sm font-medium text-gray-500">Completed At</p>
                        <p class="text-sm text-gray-900 mt-1">{{ $transfer->completed_at->format('M j, Y H:i:s') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Data Types --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Data Types Transferred</h3>
        </div>
        <div class="p-6">
            <div class="flex flex-wrap gap-2">
                @foreach($transfer->data_types ?? [] as $type)
                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-gray-100 text-gray-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ ucfirst($type) }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Error Message (if failed) --}}
    @if($transfer->isFailed() && $transfer->error_message)
        <div class="bg-red-50 rounded-xl border border-red-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-red-200">
                <h3 class="text-lg font-semibold text-red-900">Error Details</h3>
            </div>
            <div class="p-6">
                <pre class="text-sm text-red-800 whitespace-pre-wrap font-mono">{{ $transfer->error_message }}</pre>
            </div>
        </div>
    @endif

    {{-- Metadata --}}
    @if($transfer->metadata)
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Metadata</h3>
            </div>
            <div class="p-6">
                <pre class="text-sm text-gray-700 whitespace-pre-wrap font-mono bg-gray-50 p-4 rounded-lg overflow-x-auto">{{ json_encode($transfer->metadata, JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    @endif

    {{-- Timestamps --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Timeline</h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-3 h-3 rounded-full bg-gray-400"></div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-900">Transfer Created</p>
                        <p class="text-xs text-gray-500">{{ $transfer->created_at->format('M j, Y H:i:s') }}</p>
                    </div>
                </div>
                @if($transfer->status !== 'pending')
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-3 h-3 rounded-full bg-blue-400"></div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">Processing Started</p>
                            <p class="text-xs text-gray-500">{{ $transfer->updated_at->format('M j, Y H:i:s') }}</p>
                        </div>
                    </div>
                @endif
                @if($transfer->completed_at)
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-3 h-3 rounded-full {{ $transfer->isFailed() ? 'bg-red-400' : 'bg-green-400' }}"></div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">{{ $transfer->isFailed() ? 'Transfer Failed' : 'Transfer Completed' }}</p>
                            <p class="text-xs text-gray-500">{{ $transfer->completed_at->format('M j, Y H:i:s') }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
