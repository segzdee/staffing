@extends('layouts.dashboard')

@section('title', 'Featured Status')
@section('page-title', 'Get Featured')
@section('page-subtitle', 'Boost your visibility and get noticed by more employers')

@section('content')
<div class="space-y-6">
    <!-- Current Status -->
    @if($activeFeaturedStatus)
        <div class="bg-gradient-to-r {{ $activeFeaturedStatus->tier === 'gold' ? 'from-yellow-500 to-amber-600' : ($activeFeaturedStatus->tier === 'silver' ? 'from-gray-400 to-gray-500' : 'from-amber-700 to-orange-800') }} rounded-xl p-6 text-white">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <h2 class="text-xl font-bold">{{ ucfirst($activeFeaturedStatus->tier) }} Featured Status Active</h2>
                    </div>
                    <p class="text-white/80">
                        Your profile is boosted by {{ ($activeFeaturedStatus->search_boost - 1) * 100 }}% in search results
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold">{{ $activeFeaturedStatus->days_remaining }}</p>
                    <p class="text-sm text-white/80">days remaining</p>
                    <p class="text-xs text-white/60 mt-1">Expires {{ $activeFeaturedStatus->end_date->format('M d, Y') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Pricing Tiers -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($tiers as $key => $tier)
            <div class="bg-white border-2 {{ $key === 'gold' ? 'border-yellow-400' : ($key === 'silver' ? 'border-gray-400' : 'border-amber-600') }} rounded-xl overflow-hidden {{ $key === 'silver' ? 'md:-mt-4 md:mb-4' : '' }}">
                @if($key === 'silver')
                    <div class="bg-gray-900 text-white text-center py-2 text-sm font-medium">
                        Most Popular
                    </div>
                @endif

                <div class="p-6">
                    <!-- Tier Header -->
                    <div class="text-center mb-6">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full mb-3" style="background-color: {{ $tier['badge_color'] }}20;">
                            <svg class="w-6 h-6" style="color: {{ $tier['badge_color'] }};" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">{{ $tier['name'] }}</h3>
                        <p class="text-sm text-gray-600">{{ $tier['duration_days'] }} days</p>
                    </div>

                    <!-- Price -->
                    <div class="text-center mb-6">
                        <span class="text-4xl font-bold text-gray-900">{{ $tier['cost_formatted'] }}</span>
                    </div>

                    <!-- Features -->
                    <ul class="space-y-3 mb-6">
                        @foreach($tier['features'] as $feature)
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-sm text-gray-700">{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Boost Info -->
                    <div class="bg-gray-50 rounded-lg p-3 mb-6">
                        <p class="text-sm text-center">
                            <span class="font-semibold text-gray-900">{{ ($tier['search_boost'] - 1) * 100 }}%</span>
                            <span class="text-gray-600">search boost</span>
                        </p>
                    </div>

                    <!-- CTA -->
                    @if($activeFeaturedStatus)
                        <button disabled class="w-full py-3 text-sm font-medium text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed">
                            Already Featured
                        </button>
                    @else
                        <form action="{{ route('worker.profile.featured.purchase') }}" method="POST">
                            @csrf
                            <input type="hidden" name="tier" value="{{ $key }}">
                            <input type="hidden" name="payment_method_id" value="demo">
                            <button type="submit" class="w-full py-3 text-sm font-medium text-white {{ $key === 'gold' ? 'bg-yellow-600 hover:bg-yellow-700' : ($key === 'silver' ? 'bg-gray-600 hover:bg-gray-700' : 'bg-amber-700 hover:bg-amber-800') }} rounded-lg transition-colors">
                                Get {{ $tier['name'] }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- How It Works -->
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">How Featured Status Works</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h3 class="font-medium text-gray-900 mb-1">Search Boost</h3>
                <p class="text-sm text-gray-600">Your profile appears higher in search results when businesses look for workers</p>
            </div>
            <div class="text-center">
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                </div>
                <h3 class="font-medium text-gray-900 mb-1">Featured Badge</h3>
                <p class="text-sm text-gray-600">A special badge appears on your profile showing you're a featured worker</p>
            </div>
            <div class="text-center">
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </div>
                <h3 class="font-medium text-gray-900 mb-1">More Visibility</h3>
                <p class="text-sm text-gray-600">Get more profile views and increase your chances of being hired</p>
            </div>
        </div>
    </div>

    <!-- History -->
    @if($featuredHistory->count() > 0)
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Featured Status History</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-sm text-gray-500 border-b border-gray-200">
                            <th class="pb-3 font-medium">Tier</th>
                            <th class="pb-3 font-medium">Duration</th>
                            <th class="pb-3 font-medium">Status</th>
                            <th class="pb-3 font-medium">Cost</th>
                            <th class="pb-3 font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($featuredHistory as $history)
                            <tr>
                                <td class="py-3">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded" style="background-color: {{ $history->badge_color }}20; color: {{ $history->badge_color }};">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                        </svg>
                                        {{ ucfirst($history->tier) }}
                                    </span>
                                </td>
                                <td class="py-3 text-sm text-gray-600">
                                    {{ $history->start_date->format('M d') }} - {{ $history->end_date->format('M d, Y') }}
                                </td>
                                <td class="py-3">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded
                                        {{ $history->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $history->status === 'expired' ? 'bg-gray-100 text-gray-800' : '' }}
                                        {{ $history->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $history->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    ">
                                        {{ ucfirst($history->status) }}
                                    </span>
                                </td>
                                <td class="py-3 text-sm font-medium text-gray-900">{{ $history->formatted_cost }}</td>
                                <td class="py-3 text-sm text-gray-500">{{ $history->created_at->format('M d, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- FAQ -->
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Frequently Asked Questions</h2>
        <div class="space-y-4" x-data="{ open: null }">
            <div class="border-b border-gray-100 pb-4">
                <button @click="open = open === 1 ? null : 1" class="flex items-center justify-between w-full text-left">
                    <span class="font-medium text-gray-900">Can I upgrade my featured tier?</span>
                    <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': open === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <p x-show="open === 1" x-collapse class="mt-2 text-sm text-gray-600">
                    Currently, you'll need to wait for your current featured status to expire before purchasing a new tier. We're working on an upgrade feature.
                </p>
            </div>
            <div class="border-b border-gray-100 pb-4">
                <button @click="open = open === 2 ? null : 2" class="flex items-center justify-between w-full text-left">
                    <span class="font-medium text-gray-900">Is there a refund policy?</span>
                    <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': open === 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <p x-show="open === 2" x-collapse class="mt-2 text-sm text-gray-600">
                    We offer a full refund within 24 hours of purchase if you haven't received any additional profile views. After 24 hours, refunds are prorated based on days remaining.
                </p>
            </div>
            <div class="pb-4">
                <button @click="open = open === 3 ? null : 3" class="flex items-center justify-between w-full text-left">
                    <span class="font-medium text-gray-900">How much visibility increase can I expect?</span>
                    <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': open === 3 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <p x-show="open === 3" x-collapse class="mt-2 text-sm text-gray-600">
                    Featured workers typically see 2-5x more profile views during their featured period. Actual results depend on your profile completeness, skills, and location.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
