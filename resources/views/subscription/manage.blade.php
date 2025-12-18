@extends('layouts.app')

@section('title', 'Manage Subscription - OvertimeStaff')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Subscription</h1>

        {{-- Flash Messages --}}
        @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-800">{{ session('success') }}</p>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-800">{{ session('error') }}</p>
        </div>
        @endif

        @if(session('info'))
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-sm text-blue-800">{{ session('info') }}</p>
        </div>
        @endif

        @if($subscription)
            {{-- Current Plan --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-8">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">{{ $subscription->plan->name }}</h2>
                            <p class="text-sm text-gray-500">{{ $subscription->plan->description }}</p>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                     bg-{{ $subscription->status_color }}-100 text-{{ $subscription->status_color }}-800">
                            {{ $subscription->status_label }}
                        </span>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid md:grid-cols-3 gap-6">
                        {{-- Billing Amount --}}
                        <div>
                            <p class="text-sm text-gray-500">Billing Amount</p>
                            <p class="text-lg font-semibold text-gray-900">{{ $subscription->plan->formatted_price }}/{{ $subscription->plan->interval_label }}</p>
                        </div>

                        {{-- Next Billing Date --}}
                        <div>
                            <p class="text-sm text-gray-500">
                                @if($subscription->willCancelAtPeriodEnd())
                                    Access Until
                                @else
                                    Next Billing Date
                                @endif
                            </p>
                            <p class="text-lg font-semibold text-gray-900">
                                {{ $subscription->current_period_end?->format('M j, Y') ?? 'N/A' }}
                            </p>
                        </div>

                        {{-- Member Since --}}
                        <div>
                            <p class="text-sm text-gray-500">Member Since</p>
                            <p class="text-lg font-semibold text-gray-900">{{ $subscription->created_at->format('M j, Y') }}</p>
                        </div>
                    </div>

                    {{-- Trial Notice --}}
                    @if($subscription->onTrial())
                    <div class="mt-6 p-4 bg-indigo-50 border border-indigo-200 rounded-lg">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-indigo-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm text-indigo-800">
                                Your trial ends in <strong>{{ $subscription->trialDaysRemaining() }} days</strong>
                                ({{ $subscription->trial_ends_at->format('M j, Y') }}).
                            </p>
                        </div>
                    </div>
                    @endif

                    {{-- Cancellation Notice --}}
                    @if($subscription->willCancelAtPeriodEnd())
                    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 text-yellow-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-sm text-yellow-800">
                                    Your subscription will cancel on <strong>{{ $subscription->current_period_end->format('M j, Y') }}</strong>.
                                </p>
                            </div>
                            <form action="{{ route('subscription.resume') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-sm font-medium text-yellow-800 underline hover:text-yellow-900">
                                    Resume subscription
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-wrap gap-3">
                    <a href="{{ route('subscription.plans') }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Change Plan
                    </a>
                    <a href="{{ route('subscription.payment-method') }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Update Payment Method
                    </a>
                    @if(!$subscription->willCancelAtPeriodEnd() && !$subscription->isCanceled())
                    <button type="button"
                            onclick="document.getElementById('cancel-modal').classList.remove('hidden')"
                            class="px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-300 rounded-lg hover:bg-red-50 transition-colors">
                        Cancel Subscription
                    </button>
                    @endif
                </div>
            </div>

            {{-- Features --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-8">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Your Features</h2>
                </div>
                <div class="p-6">
                    <div class="grid sm:grid-cols-2 gap-4">
                        @foreach($subscription->plan->getFeatureDescriptions() as $feature)
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700">{{ $feature }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

        @else
            {{-- No Subscription --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">No Active Subscription</h2>
                <p class="text-gray-600 mb-6">Upgrade to a premium plan to unlock additional features.</p>
                <a href="{{ route('subscription.plans') }}"
                   class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                    View Plans
                </a>
            </div>
        @endif

        {{-- Invoices --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Billing History</h2>
                @if($invoices->isNotEmpty())
                <a href="{{ route('subscription.invoices') }}" class="text-sm text-indigo-600 hover:text-indigo-700">
                    View all
                </a>
                @endif
            </div>

            @if($invoices->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($invoices as $invoice)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $invoice->created_at->format('M j, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $invoice->subscription?->plan?->name ?? 'Subscription' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $invoice->formatted_total }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                             bg-{{ $invoice->status_color }}-100 text-{{ $invoice->status_color }}-800">
                                    {{ $invoice->status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                @if($invoice->pdf_url)
                                <a href="{{ route('subscription.download-invoice', $invoice) }}"
                                   class="text-indigo-600 hover:text-indigo-700">
                                    Download
                                </a>
                                @else
                                <span class="text-gray-400">--</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="p-8 text-center">
                <p class="text-gray-500">No invoices yet.</p>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Cancel Modal --}}
@if($subscription && !$subscription->willCancelAtPeriodEnd() && !$subscription->isCanceled())
<div id="cancel-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="document.getElementById('cancel-modal').classList.add('hidden')"></div>

        <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full mx-auto overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>

                <h3 class="text-lg font-semibold text-gray-900 mb-2">Cancel Subscription?</h3>
                <p class="text-sm text-gray-600 mb-6">
                    Your subscription will remain active until <strong>{{ $subscription->current_period_end?->format('M j, Y') }}</strong>.
                    After that, you'll lose access to premium features.
                </p>

                <form action="{{ route('subscription.cancel') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1 text-left">
                            Reason for canceling (optional)
                        </label>
                        <textarea name="reason" rows="3"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                  placeholder="Tell us why you're leaving..."></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button type="button"
                                onclick="document.getElementById('cancel-modal').classList.add('hidden')"
                                class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Keep Subscription
                        </button>
                        <button type="submit"
                                class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                            Cancel Subscription
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
