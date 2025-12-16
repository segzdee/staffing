@extends('layouts.app')

@section('title', 'Activate Your Account - OvertimeStaff')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Activate Your Account</h1>
            <p class="text-gray-600 mt-2">Complete the required steps below to start receiving shift opportunities.</p>
        </div>

        @if($eligibility['eligible'])
        <!-- Ready to Activate -->
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-8 mb-8 text-white text-center">
            <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold mb-2">You're Ready!</h2>
            <p class="text-green-100 mb-6">All required steps are complete. Click below to activate your account.</p>
            <form action="{{ route('api.worker.activation.activate') }}" method="POST" id="activateForm">
                @csrf
                <button type="submit" class="inline-flex items-center px-8 py-3 bg-white text-green-600 rounded-lg font-bold text-lg hover:bg-green-50 transition-colors">
                    Activate My Account
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </button>
            </form>
        </div>
        @else
        <!-- Not Ready Yet -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-lg p-8 mb-8 text-white">
            <div class="flex items-center">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-6">
                    <h2 class="text-2xl font-bold">Almost There!</h2>
                    <p class="text-blue-100 mt-1">Complete {{ $eligibility['summary']['required_total'] - $eligibility['summary']['required_complete'] }} more required step(s) to activate your account.</p>
                </div>
            </div>
            <div class="mt-6">
                <div class="flex justify-between text-sm mb-2">
                    <span>Progress</span>
                    <span>{{ $eligibility['summary']['required_complete'] }}/{{ $eligibility['summary']['required_total'] }} Required</span>
                </div>
                <div class="w-full bg-white bg-opacity-20 rounded-full h-3">
                    <div class="bg-white h-3 rounded-full transition-all duration-500" style="width: {{ ($eligibility['summary']['required_complete'] / max(1, $eligibility['summary']['required_total'])) * 100 }}%"></div>
                </div>
            </div>
        </div>
        @endif

        <!-- Eligibility Checklist -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold text-gray-900">Activation Checklist</h2>
                <p class="text-gray-600 text-sm mt-1">Complete all required items to activate your account.</p>
            </div>

            <!-- Required Items -->
            <div class="p-6">
                <h3 class="text-sm font-semibold text-red-600 uppercase tracking-wide mb-4">Required</h3>
                <div class="space-y-4">
                    @foreach($eligibility['checks'] as $key => $check)
                        @if($check['required'])
                        <div class="flex items-start p-4 rounded-lg {{ $check['passed'] ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200' }}">
                            <div class="flex-shrink-0">
                                @if($check['passed'])
                                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                @else
                                <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </div>
                                @endif
                            </div>
                            <div class="ml-4 flex-1">
                                <h4 class="text-base font-medium text-gray-900">{{ $check['name'] }}</h4>
                                <p class="text-sm {{ $check['passed'] ? 'text-green-600' : 'text-gray-500' }} mt-1">{{ $check['message'] }}</p>
                                @if(isset($check['value']) && isset($check['threshold']))
                                <div class="mt-2">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="{{ $check['passed'] ? 'bg-green-500' : 'bg-blue-500' }} h-2 rounded-full" style="width: {{ min(100, ($check['value'] / $check['threshold']) * 100) }}%"></div>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">{{ $check['value'] }}% / {{ $check['threshold'] }}% required</p>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Recommended Items -->
            <div class="p-6 bg-gray-50 border-t">
                <h3 class="text-sm font-semibold text-yellow-600 uppercase tracking-wide mb-4">Recommended (Optional)</h3>
                <div class="space-y-3">
                    @foreach($eligibility['checks'] as $key => $check)
                        @if(!$check['required'])
                        <div class="flex items-center p-3 rounded-lg bg-white border border-gray-200">
                            <div class="flex-shrink-0">
                                @if($check['passed'])
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                @else
                                <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </div>
                                @endif
                            </div>
                            <div class="ml-3 flex-1">
                                <h4 class="text-sm font-medium text-gray-900">{{ $check['name'] }}</h4>
                                <p class="text-xs text-gray-500">{{ $check['message'] }}</p>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Stats Summary -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
            <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                <p class="text-2xl font-bold text-gray-900">{{ $status['days_since_registration'] }}</p>
                <p class="text-xs text-gray-500">Days Since Joining</p>
            </div>
            <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                <p class="text-2xl font-bold text-gray-900">{{ round($eligibility['summary']['profile_completeness']) }}%</p>
                <p class="text-xs text-gray-500">Profile Complete</p>
            </div>
            <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                <p class="text-2xl font-bold text-gray-900">{{ $eligibility['summary']['required_complete'] }}/{{ $eligibility['summary']['required_total'] }}</p>
                <p class="text-xs text-gray-500">Required Steps</p>
            </div>
            <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                <p class="text-2xl font-bold text-gray-900">{{ $eligibility['summary']['recommended_complete'] }}/{{ $eligibility['summary']['recommended_total'] }}</p>
                <p class="text-xs text-gray-500">Recommended Steps</p>
            </div>
        </div>

        <!-- Help Section -->
        <div class="mt-8 text-center">
            <p class="text-gray-500">Need help? <a href="{{ route('contact') }}" class="text-blue-600 hover:underline">Contact support</a></p>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('activateForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    const button = this.querySelector('button[type="submit"]');
    const originalText = button.innerHTML;
    button.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Activating...';
    button.disabled = true;

    try {
        const response = await fetch('{{ route("api.worker.activation.activate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        });

        const data = await response.json();

        if (data.success) {
            window.location.href = '{{ route("worker.activation.welcome") }}';
        } else {
            alert(data.error || 'Activation failed. Please try again.');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        button.innerHTML = originalText;
        button.disabled = false;
    }
});
</script>
@endpush
@endsection
