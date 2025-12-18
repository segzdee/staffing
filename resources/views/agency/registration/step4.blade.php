@extends('agency.registration.layout')

@section('form-content')
    <form action="{{ route('agency.register.saveStep', $step) }}" method="POST" class="space-y-6"
        x-data="{ selectedTier: '{{ old('partnership_tier', $data['partnership_tier'] ?? 'professional') }}', billingCycle: '{{ old('billing_cycle', $data['billing_cycle'] ?? 'monthly') }}' }">
        @csrf

        <!-- Billing Cycle Toggle -->
        <div class="flex justify-center">
            <div class="relative bg-gray-100 p-0.5 rounded-lg flex sm:mt-8">
                <button type="button" @click="billingCycle = 'monthly'"
                    class="relative w-1/2 rounded-md py-2 text-sm font-medium whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:w-auto sm:px-8 shadow-sm transition-colors duration-200"
                    :class="billingCycle === 'monthly' ? 'bg-white border-gray-200 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-900'">
                    Monthly billing
                </button>
                <button type="button" @click="billingCycle = 'annual'"
                    class="relative w-1/2 rounded-md py-2 text-sm font-medium whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:w-auto sm:px-8 border-transparent text-gray-500 hover:text-gray-900 transition-colors duration-200"
                    :class="billingCycle === 'annual' ? 'bg-white border-gray-200 text-gray-900 shadow-sm' : ''">
                    Annual billing
                </button>
                <input type="hidden" name="billing_cycle" :value="billingCycle">
            </div>
        </div>

        <!-- Tiers Grid -->
        <div class="mt-8 space-y-4 sm:mt-12 sm:grid sm:grid-cols-3 sm:gap-6 sm:space-y-0">
            @foreach($stepData['tiers'] ?? [] as $key => $tier)
                <div class="relative border rounded-lg shadow-sm p-6 flex flex-col cursor-pointer transition-all duration-200 hover:border-indigo-500"
                    :class="selectedTier === '{{ $key }}' ? 'border-indigo-500 ring-2 ring-indigo-500' : 'border-gray-200'"
                    @click="selectedTier = '{{ $key }}'">

                    <div class="flex-1">
                        <h3 class="text-xl font-semibold text-gray-900">{{ $tier['name'] }}</h3>
                        @if(isset($tier['recommended']) && $tier['recommended'])
                            <p
                                class="absolute top-0 -translate-y-1/2 bg-indigo-500 text-white px-3 py-0.5 text-xs font-semibold rounded-full transform left-1/2 -translate-x-1/2">
                                Most Popular
                            </p>
                        @endif
                        <p class="mt-4 flex items-baseline text-gray-900">
                            <span class="text-3xl font-extrabold tracking-tight"
                                x-text="billingCycle === 'monthly' ? '${{ $tier['price_monthly'] ?? '0' }}' : '${{ $tier['price_annual'] ?? '0' }}'"></span>
                            <span class="ml-1 text-xl font-semibold text-gray-500">/mo</span>
                        </p>
                        @if(($tier['commission'] ?? 0) > 0)
                            <p class="mt-1 text-sm text-gray-500">{{ $tier['commission'] }}% Commission Fee</p>
                        @else
                            <p class="mt-1 text-sm text-green-600 font-medium">No Commission Fee</p>
                        @endif

                        <ul class="mt-6 space-y-4">
                            @foreach($tier['features'] as $feature)
                                <li class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <p class="ml-3 text-sm text-gray-700">{{ $feature }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="mt-8">
                        <div class="w-full flex items-center justify-center p-1 rounded-full border-2"
                            :class="selectedTier === '{{ $key }}' ? 'border-indigo-500' : 'border-gray-300'">
                            <div class="w-3 h-3 rounded-full"
                                :class="selectedTier === '{{ $key }}' ? 'bg-indigo-500' : 'bg-transparent'"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <input type="hidden" name="partnership_tier" :value="selectedTier">
        @error('partnership_tier')
            <p class="mt-2 text-sm text-red-600 text-center">{{ $message }}</p>
        @enderror

        <!-- Promo Code -->
        <div class="max-w-md mx-auto mt-8">
            <label for="promo_code" class="block text-sm font-medium text-gray-700">Promo Code (Optional)</label>
            <div class="mt-1">
                <input type="text" name="promo_code" id="promo_code"
                    value="{{ old('promo_code', $data['promo_code'] ?? '') }}"
                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
            </div>
            @error('promo_code')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-8 flex justify-between border-t border-gray-200">
            <a href="{{ route('agency.register.previous', $step) }}"
                class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Back
            </a>
            <button type="submit"
                class="bg-indigo-600 border border-transparent rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Save & Continue
            </button>
        </div>
    </form>
@endsection