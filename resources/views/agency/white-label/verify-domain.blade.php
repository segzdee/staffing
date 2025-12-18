@extends('layouts.agency')

@section('title', 'Verify Domain')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="mb-8">
        <a href="{{ route('agency.white-label.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to White-Label Settings
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Verify Domain</h1>
        <p class="text-gray-600 mt-1">Complete verification for <strong>{{ $domain->domain }}</strong></p>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="max-w-3xl mx-auto">
        {{-- Status --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Verification Status</h2>
                    <p class="text-gray-600 mt-1">{{ $domain->domain }}</p>
                </div>
                <span class="px-3 py-1 text-sm font-medium rounded-full {{ $domain->status_class }}">
                    {{ $domain->status_label }}
                </span>
            </div>
        </div>

        {{-- Instructions --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ $instructions['type'] }}</h2>
            <p class="text-gray-600 mb-6">{{ $instructions['instructions'] }}</p>

            @if($domain->verification_method === 'dns_txt')
                <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Record Name (Host)</label>
                        <div class="flex items-center gap-2">
                            <code class="flex-1 px-3 py-2 bg-white border border-gray-200 rounded text-sm font-mono">{{ $instructions['record_name'] }}</code>
                            <button type="button" onclick="copyToClipboard('{{ $instructions['record_name'] }}')"
                                class="px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded hover:bg-gray-100 transition-colors">
                                Copy
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Record Type</label>
                        <code class="block px-3 py-2 bg-white border border-gray-200 rounded text-sm font-mono">{{ $instructions['record_type'] }}</code>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Record Value</label>
                        <div class="flex items-center gap-2">
                            <code class="flex-1 px-3 py-2 bg-white border border-gray-200 rounded text-sm font-mono break-all">{{ $instructions['record_value'] }}</code>
                            <button type="button" onclick="copyToClipboard('{{ $instructions['record_value'] }}')"
                                class="px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded hover:bg-gray-100 transition-colors">
                                Copy
                            </button>
                        </div>
                    </div>
                </div>
            @elseif($domain->verification_method === 'dns_cname')
                <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Record Name (Host)</label>
                        <div class="flex items-center gap-2">
                            <code class="flex-1 px-3 py-2 bg-white border border-gray-200 rounded text-sm font-mono">{{ $instructions['record_name'] }}</code>
                            <button type="button" onclick="copyToClipboard('{{ $instructions['record_name'] }}')"
                                class="px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded hover:bg-gray-100 transition-colors">
                                Copy
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Record Type</label>
                        <code class="block px-3 py-2 bg-white border border-gray-200 rounded text-sm font-mono">{{ $instructions['record_type'] }}</code>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Points To (Value)</label>
                        <div class="flex items-center gap-2">
                            <code class="flex-1 px-3 py-2 bg-white border border-gray-200 rounded text-sm font-mono">{{ $instructions['record_value'] }}</code>
                            <button type="button" onclick="copyToClipboard('{{ $instructions['record_value'] }}')"
                                class="px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded hover:bg-gray-100 transition-colors">
                                Copy
                            </button>
                        </div>
                    </div>
                </div>
            @elseif($domain->verification_method === 'file')
                <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">File Path</label>
                        <div class="flex items-center gap-2">
                            <code class="flex-1 px-3 py-2 bg-white border border-gray-200 rounded text-sm font-mono">{{ $instructions['file_path'] }}</code>
                            <button type="button" onclick="copyToClipboard('{{ $instructions['file_path'] }}')"
                                class="px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded hover:bg-gray-100 transition-colors">
                                Copy
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">File Content</label>
                        <div class="flex items-center gap-2">
                            <code class="flex-1 px-3 py-2 bg-white border border-gray-200 rounded text-sm font-mono">{{ $instructions['file_content'] }}</code>
                            <button type="button" onclick="copyToClipboard('{{ $instructions['file_content'] }}')"
                                class="px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded hover:bg-gray-100 transition-colors">
                                Copy
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                <h3 class="font-medium text-blue-900 mb-2">Important Notes</h3>
                <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                    <li>DNS changes can take up to 48 hours to propagate worldwide</li>
                    <li>Make sure to add the record exactly as shown above</li>
                    <li>After adding the record, click "Verify Now" below to check</li>
                </ul>
            </div>
        </div>

        {{-- Verify Button --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <form action="{{ route('agency.white-label.domain.verify.check', ['domain' => $domain->id]) }}" method="POST"
                x-data="{ verifying: false }"
                @submit="verifying = true">
                @csrf

                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-medium text-gray-900">Ready to verify?</h3>
                        <p class="text-sm text-gray-600">Click the button to check if your DNS records are configured correctly.</p>
                        @if($domain->last_check_at)
                            <p class="text-sm text-gray-500 mt-1">Last checked: {{ $domain->last_check_at->diffForHumans() }}</p>
                        @endif
                    </div>
                    <button type="submit"
                        class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                        :disabled="verifying"
                        {{ !$domain->canRetryVerification() ? 'disabled' : '' }}>
                        <span x-show="!verifying">Verify Now</span>
                        <span x-show="verifying" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Verifying...
                        </span>
                    </button>
                </div>

                @if(!$domain->canRetryVerification())
                    <p class="text-sm text-yellow-600 mt-4">
                        Please wait {{ $domain->getSecondsUntilRetry() }} seconds before trying again.
                    </p>
                @endif
            </form>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Could add a toast notification here
        alert('Copied to clipboard!');
    });
}
</script>
@endsection
