@extends('layouts.dashboard')

@section('title', 'Payout Status')
@section('page-title', 'Payout Status')
@section('page-subtitle', 'Manage your Stripe Connect account and view payout history')

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- Alert Messages --}}
    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    @if(session('warning'))
    <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <span>{{ session('warning') }}</span>
    </div>
    @endif

    {{-- Status Overview --}}
    <div class="bg-white rounded-xl border border-gray-200 mb-6">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-gray-900">Account Status</h2>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $agency->stripe_status_class }}">
                    {{ $agency->stripe_status_label }}
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Pending Commission --}}
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Pending Commission</p>
                            <p class="text-2xl font-bold text-gray-900">${{ number_format($pendingCommission, 2) }}</p>
                        </div>
                    </div>
                </div>

                {{-- Total Payouts --}}
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Paid Out</p>
                            <p class="text-2xl font-bold text-gray-900">${{ number_format($totalPayouts, 2) }}</p>
                        </div>
                    </div>
                </div>

                {{-- Payout Count --}}
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Payouts</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $payoutCount }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Account Details --}}
    <div class="bg-white rounded-xl border border-gray-200 mb-6">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Stripe Connect Details</h3>
        </div>
        <div class="p-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <dt class="text-sm text-gray-500">Account ID</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900">
                        {{ $agency->stripe_connect_account_id ?? 'Not connected' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Account Type</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900">
                        {{ ucfirst($agency->stripe_account_type ?? 'N/A') }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Charges Enabled</dt>
                    <dd class="mt-1 flex items-center gap-2">
                        @if($agency->stripe_charges_enabled)
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm font-medium text-green-700">Yes</span>
                        @else
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-500">No</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Payouts Enabled</dt>
                    <dd class="mt-1 flex items-center gap-2">
                        @if($agency->stripe_payout_enabled)
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm font-medium text-green-700">Yes</span>
                        @else
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-500">No</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Onboarded At</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900">
                        {{ $agency->stripe_onboarded_at ? $agency->stripe_onboarded_at->format('M j, Y g:i A') : 'Not completed' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Last Payout</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900">
                        @if($lastPayout)
                        {{ $lastPayout->format('M j, Y') }} - ${{ number_format($agency->last_payout_amount, 2) }}
                        @else
                        No payouts yet
                        @endif
                    </dd>
                </div>
            </dl>

            {{-- Pending Requirements --}}
            @if(!empty($statusDetails['requirements']))
            <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="font-medium text-yellow-800 mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Pending Requirements
                </h4>
                <ul class="text-sm text-yellow-700 space-y-1">
                    @foreach($statusDetails['requirements'] as $requirement)
                    <li class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></span>
                        {{ ucwords(str_replace(['_', '.'], ' ', $requirement)) }}
                    </li>
                    @endforeach
                </ul>
                <div class="mt-4">
                    <a href="{{ route('agency.stripe.connect') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transition-colors">
                        Complete Requirements
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Stripe Connect Balance --}}
    @if($balance && $agency->canReceivePayouts())
    <div class="bg-white rounded-xl border border-gray-200 mb-6">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Stripe Account Balance</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($balance as $currency => $amounts)
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">{{ strtoupper($currency) }} Balance</p>
                    <div class="flex items-baseline gap-4">
                        <div>
                            <span class="text-2xl font-bold text-gray-900">${{ number_format($amounts['available'], 2) }}</span>
                            <span class="text-sm text-gray-500 ml-1">available</span>
                        </div>
                        @if($amounts['pending'] > 0)
                        <div class="text-sm text-gray-500">
                            ${{ number_format($amounts['pending'], 2) }} pending
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Actions --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Actions</h3>
        </div>
        <div class="p-6">
            <div class="flex flex-wrap gap-4">
                @if($agency->hasStripeConnectAccount())
                <a href="{{ route('agency.stripe.dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    Open Stripe Dashboard
                </a>
                @endif

                @if(!$agency->canReceivePayouts())
                <a href="{{ route('agency.stripe.connect') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Complete Setup
                </a>
                @endif

                <a href="{{ route('agency.commissions') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    View Commission Report
                </a>

                <button type="button" onclick="refreshStatus()" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5" id="refresh-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh Status
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function refreshStatus() {
    const icon = document.getElementById('refresh-icon');
    icon.classList.add('animate-spin');

    fetch('{{ route("agency.stripe.refresh-status") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Failed to refresh status: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to refresh status');
    })
    .finally(() => {
        icon.classList.remove('animate-spin');
    });
}
</script>
@endpush
@endsection
