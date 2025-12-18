@extends('layouts.app')

@section('title', 'Subscription Plans - OvertimeStaff')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Choose Your Plan</h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                @if($userType === 'worker')
                    Get priority matching, early payout access, and boost your profile visibility.
                @elseif($userType === 'business')
                    Streamline your hiring with unlimited posts, roster management, and powerful analytics.
                @else
                    Scale your agency with white-label features, reduced commissions, and advanced tools.
                @endif
            </p>
        </div>

        {{-- Billing Interval Selector --}}
        @if($availableIntervals->count() > 1)
        <div class="flex justify-center mb-8">
            <div class="inline-flex rounded-lg bg-gray-100 p-1" x-data="{ interval: '{{ $selectedInterval }}' }">
                @foreach($availableIntervals as $interval)
                <a href="{{ route('subscription.plans', ['interval' => $interval]) }}"
                   class="px-4 py-2 text-sm font-medium rounded-md transition-colors
                          {{ $selectedInterval === $interval ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                    {{ ucfirst($interval) }}
                    @if($interval === 'yearly')
                        <span class="ml-1 text-xs text-green-600">(Save up to 20%)</span>
                    @endif
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Current Subscription Notice --}}
        @if($currentSubscription)
        <div class="mb-8 p-4 bg-blue-50 border border-blue-200 rounded-lg max-w-2xl mx-auto">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-blue-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="text-sm text-blue-800">
                        You're currently on the <strong>{{ $currentSubscription->plan->name }}</strong> plan.
                        <a href="{{ route('subscription.manage') }}" class="underline">Manage your subscription</a>
                    </p>
                </div>
            </div>
        </div>
        @endif

        {{-- Plans Grid --}}
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
            @forelse($plans as $plan)
            <div class="relative bg-white rounded-2xl shadow-lg overflow-hidden
                        {{ $plan->is_popular ? 'ring-2 ring-indigo-600' : 'border border-gray-200' }}">
                {{-- Popular Badge --}}
                @if($plan->is_popular)
                <div class="absolute top-0 right-0 bg-indigo-600 text-white text-xs font-semibold px-3 py-1 rounded-bl-lg">
                    Most Popular
                </div>
                @endif

                <div class="p-8">
                    {{-- Plan Name --}}
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ $plan->name }}</h3>

                    {{-- Price --}}
                    <div class="mb-4">
                        <span class="text-4xl font-bold text-gray-900">{{ $plan->formatted_price }}</span>
                        <span class="text-gray-500">/{{ $plan->interval_label }}</span>
                    </div>

                    {{-- Savings Badge --}}
                    @if($plan->savings_percentage > 0)
                    <div class="mb-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Save {{ $plan->savings_percentage }}% annually
                        </span>
                    </div>
                    @endif

                    {{-- Description --}}
                    <p class="text-gray-600 text-sm mb-6">{{ $plan->description }}</p>

                    {{-- Trial Info --}}
                    @if($plan->trial_days > 0)
                    <p class="text-sm text-indigo-600 mb-6">
                        <svg class="inline-block h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                        {{ $plan->trial_days }}-day free trial
                    </p>
                    @endif

                    {{-- CTA Button --}}
                    @if($currentSubscription && $currentSubscription->plan->id === $plan->id)
                        <button disabled class="w-full py-3 px-4 bg-gray-100 text-gray-500 rounded-lg font-medium cursor-not-allowed">
                            Current Plan
                        </button>
                    @elseif($currentSubscription)
                        <a href="{{ route('subscription.change-plan', $plan) }}"
                           class="block w-full py-3 px-4 text-center rounded-lg font-medium transition-colors
                                  {{ $plan->is_popular ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-gray-900 text-white hover:bg-gray-800' }}">
                            Switch to {{ $plan->name }}
                        </a>
                    @else
                        <a href="{{ route('subscription.checkout', $plan) }}"
                           class="block w-full py-3 px-4 text-center rounded-lg font-medium transition-colors
                                  {{ $plan->is_popular ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-gray-900 text-white hover:bg-gray-800' }}">
                            Get Started
                        </a>
                    @endif

                    {{-- Features List --}}
                    <ul class="mt-8 space-y-3">
                        @foreach($plan->getFeatureDescriptions() as $feature)
                        <li class="flex items-start">
                            <svg class="h-5 w-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-gray-600">{{ $feature }}</span>
                        </li>
                        @endforeach
                    </ul>

                    {{-- Additional Info --}}
                    @if($plan->max_users)
                    <p class="mt-4 text-xs text-gray-500">Up to {{ $plan->max_users }} team members</p>
                    @endif

                    @if($plan->commission_rate !== null)
                    <p class="mt-2 text-xs text-gray-500">{{ $plan->commission_rate }}% platform commission</p>
                    @endif
                </div>
            </div>
            @empty
            <div class="col-span-3 text-center py-12">
                <p class="text-gray-500">No subscription plans available at this time.</p>
            </div>
            @endforelse
        </div>

        {{-- FAQ Section --}}
        <div class="mt-16 max-w-3xl mx-auto">
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-8">Frequently Asked Questions</h2>
            <div class="space-y-4" x-data="{ openFaq: null }">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <button @click="openFaq = openFaq === 1 ? null : 1"
                            class="w-full px-6 py-4 text-left flex justify-between items-center">
                        <span class="font-medium text-gray-900">Can I cancel my subscription anytime?</span>
                        <svg class="h-5 w-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': openFaq === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="openFaq === 1" x-collapse class="px-6 pb-4 text-gray-600 text-sm">
                        Yes! You can cancel your subscription at any time. You'll continue to have access to premium features until the end of your current billing period.
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <button @click="openFaq = openFaq === 2 ? null : 2"
                            class="w-full px-6 py-4 text-left flex justify-between items-center">
                        <span class="font-medium text-gray-900">What happens after the free trial?</span>
                        <svg class="h-5 w-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': openFaq === 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="openFaq === 2" x-collapse class="px-6 pb-4 text-gray-600 text-sm">
                        After your free trial ends, you'll be automatically charged for your selected plan. You can cancel before the trial ends to avoid being charged.
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <button @click="openFaq = openFaq === 3 ? null : 3"
                            class="w-full px-6 py-4 text-left flex justify-between items-center">
                        <span class="font-medium text-gray-900">Can I change my plan later?</span>
                        <svg class="h-5 w-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': openFaq === 3 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="openFaq === 3" x-collapse class="px-6 pb-4 text-gray-600 text-sm">
                        Absolutely! You can upgrade or downgrade your plan at any time. When upgrading, you'll be charged the prorated difference. When downgrading, the change takes effect at the end of your current billing period.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
