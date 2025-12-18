@extends('layouts.dashboard')

@section('title', $template->name)
@section('page-title', 'Template Details')
@section('page-subtitle', $template->name)

@section('content')
<div class="max-w-5xl mx-auto">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="{{ route('business.communication-templates.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Templates
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Template Info -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-xl font-semibold text-gray-900">{{ $template->name }}</h2>
                            @if($template->is_default)
                                <span class="px-2 py-0.5 text-xs bg-blue-100 text-blue-800 rounded-full">Default</span>
                            @endif
                            <span class="px-2 py-0.5 text-xs {{ $template->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }} rounded-full">
                                {{ $template->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">{{ $template->type_label }} - {{ $template->channel_label }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('business.communication-templates.send.form', $template) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Send
                        </a>
                        @if($template->isEditable())
                            <a href="{{ route('business.communication-templates.edit', $template) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium">
                                Edit
                            </a>
                        @endif
                    </div>
                </div>

                <div class="p-6">
                    @if($template->subject)
                        <div class="mb-4">
                            <span class="text-xs text-gray-500 uppercase tracking-wide">Subject</span>
                            <p class="mt-1 text-gray-900 font-medium">{{ $template->subject }}</p>
                        </div>
                    @endif

                    <div>
                        <span class="text-xs text-gray-500 uppercase tracking-wide">Message Body</span>
                        <div class="mt-2 p-4 bg-gray-50 rounded-lg font-mono text-sm text-gray-700 whitespace-pre-wrap">{{ $template->body }}</div>
                    </div>

                    @if($template->variables && count($template->variables) > 0)
                        <div class="mt-4">
                            <span class="text-xs text-gray-500 uppercase tracking-wide">Variables Used</span>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($template->variables as $var)
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-mono">@{{ {{ $var }} }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Preview -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Preview with Sample Data</h3>
                    <p class="mt-1 text-sm text-gray-500">This is how the message will appear to recipients</p>
                </div>
                <div class="p-6 bg-gray-50">
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        @if($preview['subject'])
                            <div class="mb-4 pb-4 border-b border-gray-200">
                                <span class="text-xs text-gray-500 uppercase tracking-wide">Subject:</span>
                                <p class="text-gray-900 font-medium">{{ $preview['subject'] }}</p>
                            </div>
                        @endif
                        <div class="prose prose-sm max-w-none text-gray-700">
                            {!! nl2br(e($preview['body'])) !!}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Sends -->
            @if($recentSends->count() > 0)
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Sends</h3>
                    </div>
                    <div class="divide-y divide-gray-200">
                        @foreach($recentSends as $send)
                            <div class="p-4 flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                                        <span class="text-sm font-medium text-gray-600">{{ substr($send->recipient->name, 0, 1) }}</span>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-900">{{ $send->recipient->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $send->sent_at?->diffForHumans() ?? 'Pending' }}</p>
                                    </div>
                                </div>
                                <span class="px-2 py-1 text-xs rounded-full
                                    {{ $send->status === 'sent' || $send->status === 'delivered' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $send->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $send->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}">
                                    {{ ucfirst($send->status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Stats -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistics</h3>
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Total Sends</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $usageStats['total_sends'] }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Delivered</dt>
                        <dd class="text-sm font-medium text-green-600">{{ $usageStats['delivered'] }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Failed</dt>
                        <dd class="text-sm font-medium text-red-600">{{ $usageStats['failed'] }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500">Last Used</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ $usageStats['last_used']?->diffForHumans() ?? 'Never' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <a href="{{ route('business.communication-templates.send.form', $template) }}" class="flex items-center w-full px-4 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Send to Workers
                    </a>

                    <form action="{{ route('business.communication-templates.duplicate', $template) }}" method="POST">
                        @csrf
                        <button type="submit" class="flex items-center w-full px-4 py-2 bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            Duplicate Template
                        </button>
                    </form>

                    @if(!$template->is_default)
                        <form action="{{ route('business.communication-templates.set-default', $template) }}" method="POST">
                            @csrf
                            <button type="submit" class="flex items-center w-full px-4 py-2 bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                                Set as Default
                            </button>
                        </form>
                    @endif

                    <form action="{{ route('business.communication-templates.toggle-active', $template) }}" method="POST">
                        @csrf
                        <button type="submit" class="flex items-center w-full px-4 py-2 bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100">
                            @if($template->is_active)
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Deactivate
                            @else
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Activate
                            @endif
                        </button>
                    </form>
                </div>
            </div>

            <!-- Template Info -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Details</h3>
                <dl class="space-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Created</dt>
                        <dd class="text-gray-900">{{ $template->created_at->format('M j, Y g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Last Updated</dt>
                        <dd class="text-gray-900">{{ $template->updated_at->format('M j, Y g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Slug</dt>
                        <dd class="text-gray-900 font-mono">{{ $template->slug }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
