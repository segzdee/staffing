@extends('layouts.dashboard')

@section('title', 'Preview: ' . $template->name)
@section('page-title', 'Template Preview')
@section('page-subtitle', $template->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.email.templates.edit', $template) }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h1 class="text-xl font-semibold text-gray-900">Preview: {{ $template->name }}</h1>
        </div>
        <form action="{{ route('admin.email.templates.test', $template) }}" method="POST" class="flex items-center gap-3">
            @csrf
            <input type="email" name="to_email" value="{{ auth()->user()->email }}" required
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm w-64"
                placeholder="test@example.com">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                Send Test
            </button>
        </form>
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

    <!-- Subject Preview -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="text-sm text-gray-500 mb-1">Subject</div>
        <div class="text-lg font-medium text-gray-900">{{ $rendered['subject'] }}</div>
    </div>

    <!-- HTML Preview -->
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <span class="text-sm font-medium text-gray-700">HTML Preview</span>
            <span class="text-xs text-gray-500">With sample data</span>
        </div>
        <div class="p-4">
            <div class="border border-gray-200 rounded-lg overflow-hidden bg-gray-100">
                <iframe srcdoc="{{ e(view('emails.templated', ['bodyHtml' => $rendered['body_html'], 'bodyText' => $rendered['body_text'], 'logId' => null, 'trackOpens' => false, 'trackClicks' => false])->render()) }}"
                    class="w-full" style="height: 600px; background: white;"></iframe>
            </div>
        </div>
    </div>

    <!-- Plain Text Preview -->
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="px-4 py-3 border-b border-gray-200">
            <span class="text-sm font-medium text-gray-700">Plain Text Preview</span>
        </div>
        <div class="p-4">
            <pre class="whitespace-pre-wrap text-sm text-gray-700 font-mono bg-gray-50 p-4 rounded-lg">{{ $rendered['body_text'] }}</pre>
        </div>
    </div>

    <!-- Template Variables -->
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="px-4 py-3 border-b border-gray-200">
            <span class="text-sm font-medium text-gray-700">Template Variables</span>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach($template->variables ?? [] as $variable)
                <div class="bg-gray-50 px-3 py-2 rounded text-sm font-mono text-gray-700">
                    {{ '{{' }} {{ $variable }} {{ '}}' }}
                </div>
                @endforeach
            </div>
            @if(empty($template->variables))
            <p class="text-sm text-gray-500">No custom variables defined</p>
            @endif
        </div>
    </div>
</div>
@endsection
