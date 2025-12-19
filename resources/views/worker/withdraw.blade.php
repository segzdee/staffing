@extends('layouts.dashboard')

@section('title', 'Withdraw Funds')

@section('content')
<div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Withdraw Funds</h1>
            <p class="mt-1 text-sm text-gray-500">Transfer your available balance to your bank account.</p>
        </div>
        <a href="{{ route('worker.payment-setup') }}"
           class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Payment Settings
        </a>
    </div>

    {{-- Status Messages --}}
    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex">
            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <p class="ml-3 text-sm text-green-700">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex">
            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <p class="ml-3 text-sm text-red-700">{{ session('error') }}</p>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex">
            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <div class="ml-3">
                @foreach($errors->all() as $error)
                <p class="text-sm text-red-700">{{ $error }}</p>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Left Column: Balance Cards & Withdrawal Form --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Balance Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                {{-- Available Balance --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-500">Available</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                            Ready
                        </span>
                    </div>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($availableBalance ?? 0, 2) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Can withdraw now</p>
                </div>

                {{-- Pending Balance --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-500">Pending</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                            In Escrow
                        </span>
                    </div>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($pendingBalance ?? 0, 2) }}</p>
                    <p class="mt-1 text-xs text-gray-500">From active shifts</p>
                </div>

                {{-- Processing Balance --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-500">Processing</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                            In Transit
                        </span>
                    </div>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($processingBalance ?? 0, 2) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Being transferred</p>
                </div>
            </div>

            {{-- Withdrawal Form --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" x-data="withdrawalForm()">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Request Withdrawal</h2>
                </div>

                @if(($payoutMethods ?? collect())->isEmpty())
                {{-- No Payout Methods --}}
                <div class="p-8 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 mb-4">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-medium text-gray-900 mb-1">No payout method configured</h3>
                    <p class="text-sm text-gray-500 mb-4">Set up a payout method to withdraw your earnings.</p>
                    <a href="{{ route('worker.payment-setup') }}"
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                        Set Up Payout Method
                    </a>
                </div>
                @elseif(($availableBalance ?? 0) < ($minWithdrawal ?? 25))
                {{-- Insufficient Balance --}}
                <div class="p-8 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-yellow-100 mb-4">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-medium text-gray-900 mb-1">Minimum balance not met</h3>
                    <p class="text-sm text-gray-500">You need at least ${{ number_format($minWithdrawal ?? 25, 2) }} to make a withdrawal.</p>
                    <p class="mt-2 text-sm text-gray-500">Current available: <span class="font-medium text-gray-900">${{ number_format($availableBalance ?? 0, 2) }}</span></p>
                </div>
                @else
                {{-- Withdrawal Form --}}
                <form action="{{ route('worker.withdraw.process') }}" method="POST" class="p-6 space-y-6">
                    @csrf

                    {{-- Amount Input --}}
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                            Withdrawal Amount
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-lg">$</span>
                            </div>
                            <input type="number"
                                   name="amount"
                                   id="amount"
                                   x-model="amount"
                                   step="0.01"
                                   min="{{ $minWithdrawal ?? 25 }}"
                                   max="{{ $availableBalance ?? 0 }}"
                                   class="block w-full pl-8 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-lg"
                                   placeholder="0.00"
                                   required>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <button type="button"
                                        @click="amount = {{ $availableBalance ?? 0 }}"
                                        class="text-xs font-medium text-indigo-600 hover:text-indigo-500">
                                    MAX
                                </button>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Min: ${{ number_format($minWithdrawal ?? 25, 2) }} | Available: ${{ number_format($availableBalance ?? 0, 2) }}
                        </p>
                    </div>

                    {{-- Payout Method Selection --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Payout Method
                        </label>
                        <div class="space-y-3">
                            @foreach($payoutMethods ?? [] as $method)
                            <label class="flex items-center p-4 border rounded-lg cursor-pointer transition-colors"
                                   :class="selectedMethod == '{{ $method->id }}' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:bg-gray-50'">
                                <input type="radio"
                                       name="payout_method_id"
                                       value="{{ $method->id }}"
                                       x-model="selectedMethod"
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500"
                                       {{ $method->is_default ? 'checked' : '' }}>
                                <div class="ml-3 flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-900">
                                            @if($method->type === 'bank')
                                                {{ $method->bank_name ?? 'Bank Account' }} ****{{ $method->last4 ?? '****' }}
                                            @elseif($method->type === 'card')
                                                Debit Card ****{{ $method->last4 ?? '****' }}
                                            @else
                                                {{ ucfirst($method->type) }}
                                            @endif
                                        </span>
                                        @if($method->is_default)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                            Default
                                        </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        @if($method->type === 'bank')
                                            Standard: 1-2 business days
                                        @else
                                            {{ ucfirst($method->type) }}
                                        @endif
                                    </p>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Instant Payout Option --}}
                    @if($instantPayoutAvailable ?? false)
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg p-4 border border-indigo-100">
                        <label class="flex items-start cursor-pointer">
                            <input type="checkbox"
                                   name="instant"
                                   value="1"
                                   x-model="useInstant"
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 rounded mt-0.5">
                            <div class="ml-3">
                                <div class="flex items-center">
                                    <span class="text-sm font-medium text-gray-900">Instant Payout</span>
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                        <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                                        </svg>
                                        Fast
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    Get your money in minutes instead of days. <span class="font-medium">1.5% fee</span> (min $0.50, max $10.00)
                                </p>
                            </div>
                        </label>
                    </div>
                    @endif

                    {{-- Fee Summary --}}
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Summary</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Withdrawal amount</span>
                                <span class="text-gray-900" x-text="'$' + parseFloat(amount || 0).toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between" x-show="useInstant">
                                <span class="text-gray-500">Instant fee (1.5%)</span>
                                <span class="text-gray-900" x-text="'-$' + calculateFee().toFixed(2)"></span>
                            </div>
                            <div class="border-t border-gray-200 pt-2 flex justify-between font-medium">
                                <span class="text-gray-900">You'll receive</span>
                                <span class="text-green-600" x-text="'$' + calculateNet().toFixed(2)"></span>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-gray-500" x-show="!useInstant">
                            Standard payouts arrive in 1-2 business days.
                        </p>
                        <p class="mt-3 text-xs text-gray-500" x-show="useInstant">
                            Instant payouts typically arrive within 30 minutes.
                        </p>
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit"
                            :disabled="!canSubmit"
                            class="w-full py-3 px-4 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <span x-text="useInstant ? 'Request Instant Withdrawal' : 'Request Withdrawal'"></span>
                    </button>
                </form>
                @endif
            </div>
        </div>

        {{-- Right Column: Stats & History --}}
        <div class="space-y-6">
            {{-- Withdrawal Stats --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-medium text-gray-900 mb-4">Withdrawal Stats</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Total Withdrawn</p>
                        <p class="text-xl font-bold text-gray-900">${{ number_format($withdrawalStats['total_withdrawn'] ?? 0, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider">This Month</p>
                        <p class="text-xl font-bold text-gray-900">${{ number_format($withdrawalStats['this_month'] ?? 0, 2) }}</p>
                    </div>
                    @if(($withdrawalStats['pending_count'] ?? 0) > 0)
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Pending Requests</p>
                        <p class="text-xl font-bold text-yellow-600">{{ $withdrawalStats['pending_count'] }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Recent Withdrawals --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-medium text-gray-900">Recent Withdrawals</h3>
                </div>
                @if(($recentWithdrawals ?? collect())->isEmpty())
                <div class="p-6 text-center">
                    <p class="text-sm text-gray-500">No withdrawals yet.</p>
                </div>
                @else
                <div class="divide-y divide-gray-200">
                    @foreach($recentWithdrawals ?? [] as $withdrawal)
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">${{ number_format($withdrawal->amount, 2) }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ \Carbon\Carbon::parse($withdrawal->created_at)->format('M j, Y') }}
                                </p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($withdrawal->status === 'completed') bg-green-100 text-green-800
                                @elseif($withdrawal->status === 'processing') bg-blue-100 text-blue-800
                                @elseif($withdrawal->status === 'failed') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst($withdrawal->status) }}
                            </span>
                        </div>
                        @if($withdrawal->is_instant ?? false)
                        <p class="mt-1 text-xs text-indigo-600">
                            <svg class="inline h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                            </svg>
                            Instant
                        </p>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Help Section --}}
            <div class="bg-gray-50 rounded-xl p-6">
                <h3 class="text-sm font-medium text-gray-900 mb-3">Need Help?</h3>
                <div class="space-y-3 text-sm text-gray-500">
                    <p>
                        <span class="font-medium text-gray-700">Processing times:</span>
                        Standard withdrawals take 1-2 business days. Instant payouts arrive within minutes.
                    </p>
                    <p>
                        <span class="font-medium text-gray-700">Minimum withdrawal:</span>
                        ${{ number_format($minWithdrawal ?? 25, 2) }}
                    </p>
                    <p>
                        <span class="font-medium text-gray-700">Issues?</span>
                        Contact <a href="mailto:support@overtimestaff.com" class="text-indigo-600 hover:text-indigo-500">support@overtimestaff.com</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function withdrawalForm() {
    return {
        amount: '',
        selectedMethod: '{{ ($payoutMethods ?? collect())->where('is_default', true)->first()?->id ?? ($payoutMethods ?? collect())->first()?->id ?? '' }}',
        useInstant: false,
        minWithdrawal: {{ $minWithdrawal ?? 25 }},
        maxWithdrawal: {{ $availableBalance ?? 0 }},

        get canSubmit() {
            const amt = parseFloat(this.amount) || 0;
            return amt >= this.minWithdrawal && amt <= this.maxWithdrawal && this.selectedMethod;
        },

        calculateFee() {
            if (!this.useInstant) return 0;
            const amt = parseFloat(this.amount) || 0;
            let fee = amt * 0.015; // 1.5%
            fee = Math.max(fee, 0.50); // Min $0.50
            fee = Math.min(fee, 10.00); // Max $10.00
            return fee;
        },

        calculateNet() {
            const amt = parseFloat(this.amount) || 0;
            return amt - this.calculateFee();
        }
    };
}
</script>
@endpush
@endsection
