@extends('layouts.authenticated')

@section('title', 'My Earnings')
@section('page-title', 'My Earnings')

@section('sidebar-nav')
<a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('worker.assignments.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>
    <span>My Shifts</span>
</a>
<a href="#" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <span>Earnings</span>
</a>
<a href="{{ route('worker.profile') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
    </svg>
    <span>Profile</span>
</a>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">My Earnings</h2>
            <p class="text-sm text-gray-500 mt-1">Track your income and payment history</p>
        </div>
        <div class="flex space-x-3">
            <select class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500">
                <option value="this_month">This Month</option>
                <option value="last_month">Last Month</option>
                <option value="this_year">This Year</option>
                <option value="all_time">All Time</option>
            </select>
            <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                Export
            </button>
        </div>
    </div>

    <!-- Earnings Summary -->
    <div class="grid md:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm text-gray-500">Total Earnings</p>
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-3xl font-bold text-gray-900">${{ number_format($totalEarnings ?? 0, 2) }}</p>
            <p class="text-sm text-green-600 mt-2">+{{ $earningsGrowth ?? 0 }}% from last period</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm text-gray-500">Pending Payment</p>
                <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-3xl font-bold text-gray-900">${{ number_format($pendingPayment ?? 0, 2) }}</p>
            <p class="text-sm text-gray-500 mt-2">Processing</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm text-gray-500">Hours Worked</p>
                <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ $hoursWorked ?? 0 }}</p>
            <p class="text-sm text-gray-500 mt-2">This period</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm text-gray-500">Shifts Completed</p>
                <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ $shiftsCompleted ?? 0 }}</p>
            <p class="text-sm text-gray-500 mt-2">This period</p>
        </div>
    </div>

    <!-- Earnings Chart -->
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Earnings Over Time</h3>
        <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
            <!-- Placeholder for chart -->
            <div class="text-center text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
                <p>Earnings chart will be displayed here</p>
            </div>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="bg-white border border-gray-200 rounded-xl">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Payment History</h3>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($payments ?? [] as $payment)
            <div class="p-4 flex items-center justify-between hover:bg-gray-50">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $payment->shift->title ?? 'Shift Payment' }}</p>
                        <p class="text-sm text-gray-500">{{ $payment->shift->business->name ?? 'Business' }} | {{ $payment->paid_at ?? $payment->created_at ?? 'Date' }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="font-bold text-green-600">+${{ number_format($payment->amount ?? 0, 2) }}</p>
                    <p class="text-sm text-gray-500">{{ $payment->hours ?? 0 }} hours</p>
                </div>
            </div>
            @empty
            <div class="p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No payments yet</h3>
                <p class="mt-2 text-sm text-gray-500">Complete shifts to start earning!</p>
                <a href="{{ route('shifts.index') }}" class="mt-4 inline-block px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700">
                    Browse Shifts
                </a>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Payout Settings -->
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Payout Settings</h3>
            <a href="{{ route('settings.index') }}" class="text-brand-600 hover:text-brand-700 text-sm font-medium">
                Edit Settings
            </a>
        </div>
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-500">Payment Method</p>
                <p class="font-medium text-gray-900">{{ $payoutMethod ?? 'Bank Transfer (ACH)' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Payout Schedule</p>
                <p class="font-medium text-gray-900">{{ $payoutSchedule ?? 'Weekly (Every Friday)' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Account Ending In</p>
                <p class="font-medium text-gray-900">****{{ $accountLast4 ?? '1234' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Next Payout</p>
                <p class="font-medium text-gray-900">{{ $nextPayoutDate ?? 'Friday, Dec 20' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
