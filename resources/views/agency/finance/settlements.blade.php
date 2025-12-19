@extends('layouts.dashboard')

@section('title', 'Settlements')
@section('page-title', 'Payout Settlements')
@section('page-subtitle', 'Track your payout history and upcoming settlements')

@section('content')
<div class="space-y-6">
    {{-- Stripe Status Banner --}}
    @if(!$stripeConnected)
    <div class="bg-gradient-to-r from-amber-500 to-orange-500 rounded-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Payout Account Not Set Up</h3>
                    <p class="text-white/80">Connect Stripe to receive your commission payouts</p>
                </div>
            </div>
            <a href="{{ route('agency.stripe.onboarding') }}" class="px-6 py-2 bg-white text-orange-600 font-medium rounded-lg hover:bg-white/90 transition-colors">
                Set Up Now
            </a>
        </div>
    </div>
    @endif

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <span class="text-sm text-gray-500">Total Settled</span>
            </div>
            <p class="text-xl font-bold text-gray-900">${{ number_format($totalSettled, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-sm text-gray-500">Pending Payout</span>
            </div>
            <p class="text-xl font-bold text-gray-900">${{ number_format($pendingPayout, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-sm text-gray-500">Next Payout</span>
            </div>
            <p class="text-xl font-bold text-gray-900">{{ $nextPayoutDate ?? 'N/A' }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <span class="text-sm text-gray-500">Total Payouts</span>
            </div>
            <p class="text-xl font-bold text-gray-900">{{ $totalPayoutsCount }}</p>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Settlement History --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Settlement History</h3>
                    <div class="flex gap-2">
                        <select name="period" class="text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All Time</option>
                            <option value="month">This Month</option>
                            <option value="quarter">This Quarter</option>
                            <option value="year">This Year</option>
                        </select>
                    </div>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse($settlements as $settlement)
                    <div class="p-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 {{ $settlement['status'] === 'completed' ? 'bg-green-100' : ($settlement['status'] === 'pending' ? 'bg-amber-100' : 'bg-red-100') }} rounded-lg flex items-center justify-center">
                                @if($settlement['status'] === 'completed')
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                @elseif($settlement['status'] === 'pending')
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                @else
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                @endif
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Settlement #{{ $settlement['id'] }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ $settlement['date']->format('M d, Y') }} &middot;
                                    {{ $settlement['shifts_count'] }} shift{{ $settlement['shifts_count'] !== 1 ? 's' : '' }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-900">${{ number_format($settlement['amount'], 2) }}</p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $settlement['status'] === 'completed' ? 'bg-green-100 text-green-700' : ($settlement['status'] === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                                {{ ucfirst($settlement['status']) }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-1">No settlements yet</h3>
                        <p class="text-gray-500">Your payout settlements will appear here once processed.</p>
                    </div>
                    @endforelse
                </div>

                @if(count($settlements) > 0)
                <div class="p-4 border-t border-gray-200 text-center">
                    <button type="button" class="text-indigo-600 hover:text-indigo-700 font-medium text-sm">
                        Load More
                    </button>
                </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Payout Schedule --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Payout Schedule</h3>
                @if($stripeConnected)
                <div class="space-y-4">
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-600">Schedule</span>
                        <span class="text-sm font-medium text-gray-900">{{ ucfirst($payoutSchedule ?? 'Weekly') }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-600">Payout Day</span>
                        <span class="text-sm font-medium text-gray-900">{{ $payoutDay ?? 'Friday' }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="text-sm text-gray-600">Currency</span>
                        <span class="text-sm font-medium text-gray-900">{{ $payoutCurrency ?? 'USD' }}</span>
                    </div>
                </div>
                @else
                <div class="text-center py-4">
                    <p class="text-sm text-gray-500 mb-4">Connect Stripe to set up your payout schedule</p>
                    <a href="{{ route('agency.stripe.onboarding') }}" class="block w-full px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                        Set Up Payouts
                    </a>
                </div>
                @endif
            </div>

            {{-- Bank Account Info --}}
            @if($stripeConnected && $bankAccount)
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Payout Method</h3>
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <div class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $bankAccount['bank_name'] ?? 'Bank Account' }}</p>
                        <p class="text-sm text-gray-500">****{{ $bankAccount['last4'] ?? '****' }}</p>
                    </div>
                </div>
                <a href="{{ route('agency.stripe.status') }}" class="mt-4 block w-full text-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    Update Payout Method
                </a>
            </div>
            @endif

            {{-- Last Payout --}}
            @if($lastPayout)
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Last Payout</h3>
                <div class="text-center">
                    <p class="text-3xl font-bold text-green-600 mb-2">${{ number_format($lastPayout['amount'], 2) }}</p>
                    <p class="text-sm text-gray-500">{{ $lastPayout['date']->format('F d, Y') }}</p>
                    <span class="inline-flex items-center mt-2 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                        Completed
                    </span>
                </div>
            </div>
            @endif

            {{-- Help Section --}}
            <div class="bg-gray-50 rounded-xl p-6">
                <h3 class="font-semibold text-gray-900 mb-2">Need Help?</h3>
                <p class="text-sm text-gray-600 mb-4">If you have questions about settlements or payouts, our support team is here to help.</p>
                <a href="{{ route('contact') ?? '#' }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
