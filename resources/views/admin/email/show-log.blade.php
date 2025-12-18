@extends('layouts.dashboard')

@section('title', 'Email Log Details')
@section('page-title', 'Email Log Details')
@section('page-subtitle', 'View email delivery and tracking information')

@section('content')
<div class="max-w-4xl space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.email.logs') }}" class="text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <h1 class="text-xl font-semibold text-gray-900">Email Log #{{ $log->id }}</h1>
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

    <!-- Status Banner -->
    @php
        $statusColors = [
            'queued' => 'bg-gray-50 border-gray-200 text-gray-800',
            'sent' => 'bg-blue-50 border-blue-200 text-blue-800',
            'delivered' => 'bg-green-50 border-green-200 text-green-800',
            'opened' => 'bg-purple-50 border-purple-200 text-purple-800',
            'clicked' => 'bg-indigo-50 border-indigo-200 text-indigo-800',
            'bounced' => 'bg-red-50 border-red-200 text-red-800',
            'failed' => 'bg-red-50 border-red-200 text-red-800',
        ];
    @endphp
    <div class="rounded-lg border p-4 {{ $statusColors[$log->status] ?? 'bg-gray-50 border-gray-200' }}">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-2xl font-bold">{{ ucfirst($log->status) }}</span>
                @if($log->error_message)
                <span class="text-sm">{{ $log->error_message }}</span>
                @endif
            </div>
            @if(in_array($log->status, ['failed', 'bounced']))
            <form action="{{ route('admin.email.retry', $log) }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Retry Send
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- Email Details -->
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Email Details</h2>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500">To</label>
                    <div class="mt-1 text-sm text-gray-900">{{ $log->to_email }}</div>
                </div>
                @if($log->user)
                <div>
                    <label class="block text-sm font-medium text-gray-500">User</label>
                    <div class="mt-1 text-sm text-gray-900">{{ $log->user->name }} (ID: {{ $log->user->id }})</div>
                </div>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-500">Subject</label>
                <div class="mt-1 text-sm text-gray-900">{{ $log->subject }}</div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500">Template</label>
                    <div class="mt-1 text-sm text-gray-900 font-mono">{{ $log->template_slug ?? '-' }}</div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Message ID</label>
                    <div class="mt-1 text-sm text-gray-900 font-mono">{{ $log->message_id ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline -->
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Timeline</h2>
        </div>
        <div class="p-6">
            <div class="relative">
                <div class="absolute left-4 top-0 bottom-0 w-px bg-gray-200"></div>
                <div class="space-y-6">
                    <div class="relative flex items-start gap-4 pl-10">
                        <div class="absolute left-2 w-4 h-4 bg-gray-500 rounded-full border-2 border-white"></div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">Queued</div>
                            <div class="text-xs text-gray-500">{{ $log->created_at->format('M d, Y H:i:s') }}</div>
                        </div>
                    </div>

                    @if($log->sent_at)
                    <div class="relative flex items-start gap-4 pl-10">
                        <div class="absolute left-2 w-4 h-4 bg-blue-500 rounded-full border-2 border-white"></div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">Sent</div>
                            <div class="text-xs text-gray-500">{{ $log->sent_at->format('M d, Y H:i:s') }}</div>
                        </div>
                    </div>
                    @endif

                    @if($log->status === 'delivered' || $log->opened_at || $log->clicked_at)
                    <div class="relative flex items-start gap-4 pl-10">
                        <div class="absolute left-2 w-4 h-4 bg-green-500 rounded-full border-2 border-white"></div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">Delivered</div>
                            <div class="text-xs text-gray-500">Email was successfully delivered</div>
                        </div>
                    </div>
                    @endif

                    @if($log->opened_at)
                    <div class="relative flex items-start gap-4 pl-10">
                        <div class="absolute left-2 w-4 h-4 bg-purple-500 rounded-full border-2 border-white"></div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">Opened</div>
                            <div class="text-xs text-gray-500">{{ $log->opened_at->format('M d, Y H:i:s') }}</div>
                        </div>
                    </div>
                    @endif

                    @if($log->clicked_at)
                    <div class="relative flex items-start gap-4 pl-10">
                        <div class="absolute left-2 w-4 h-4 bg-indigo-500 rounded-full border-2 border-white"></div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">Clicked</div>
                            <div class="text-xs text-gray-500">{{ $log->clicked_at->format('M d, Y H:i:s') }}</div>
                        </div>
                    </div>
                    @endif

                    @if($log->status === 'bounced')
                    <div class="relative flex items-start gap-4 pl-10">
                        <div class="absolute left-2 w-4 h-4 bg-red-500 rounded-full border-2 border-white"></div>
                        <div>
                            <div class="text-sm font-medium text-red-600">Bounced</div>
                            <div class="text-xs text-gray-500">{{ $log->error_message ?? 'Email could not be delivered' }}</div>
                        </div>
                    </div>
                    @endif

                    @if($log->status === 'failed')
                    <div class="relative flex items-start gap-4 pl-10">
                        <div class="absolute left-2 w-4 h-4 bg-red-500 rounded-full border-2 border-white"></div>
                        <div>
                            <div class="text-sm font-medium text-red-600">Failed</div>
                            <div class="text-xs text-gray-500">{{ $log->error_message ?? 'Unknown error' }}</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Metadata -->
    @if($log->metadata)
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Metadata</h2>
        </div>
        <div class="p-6">
            <pre class="text-sm text-gray-700 font-mono bg-gray-50 p-4 rounded-lg overflow-x-auto">{{ json_encode($log->metadata, JSON_PRETTY_PRINT) }}</pre>
        </div>
    </div>
    @endif
</div>
@endsection
