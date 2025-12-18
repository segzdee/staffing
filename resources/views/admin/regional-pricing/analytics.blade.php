@extends('admin.layout')

@section('title', 'Regional Pricing Analytics')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.regional-pricing.index') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Pricing Analytics</h1>
                <p class="mt-1 text-sm text-gray-600">Regional pricing performance and distribution analysis.</p>
            </div>
        </div>
    </div>

    <!-- Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Regions</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $analytics['total_regions'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Countries</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $analytics['countries'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Currencies</p>
                    <p class="text-2xl font-bold text-gray-900">{{ count($analytics['by_currency']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Active Adjustments</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $analytics['adjustments']['active'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- PPP Distribution -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">PPP Factor Distribution</h2>

            <div class="space-y-4">
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div class="p-4 bg-green-50 rounded-lg">
                        <p class="text-sm text-green-600 font-medium">Minimum</p>
                        <p class="text-2xl font-bold text-green-700">{{ number_format($analytics['ppp_distribution']['min'], 3) }}</p>
                    </div>
                    <div class="p-4 bg-blue-50 rounded-lg">
                        <p class="text-sm text-blue-600 font-medium">Average</p>
                        <p class="text-2xl font-bold text-blue-700">{{ number_format($analytics['ppp_distribution']['avg'], 3) }}</p>
                    </div>
                    <div class="p-4 bg-red-50 rounded-lg">
                        <p class="text-sm text-red-600 font-medium">Maximum</p>
                        <p class="text-2xl font-bold text-red-700">{{ number_format($analytics['ppp_distribution']['max'], 3) }}</p>
                    </div>
                </div>

                @if(!empty($analytics['top_regions_by_ppp']))
                    <div class="mt-4">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Highest PPP Regions</h3>
                        <div class="space-y-2">
                            @foreach($analytics['top_regions_by_ppp'] as $region)
                                <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                    <span class="text-sm text-gray-700">{{ $region['country'] }}</span>
                                    <span class="text-sm font-mono font-medium text-gray-900">{{ number_format($region['ppp_factor'], 3) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Currency Distribution -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Currency Distribution</h2>

            <div class="space-y-3">
                @foreach($analytics['by_currency'] as $currency => $count)
                    @php
                        $percentage = ($count / $analytics['total_regions']) * 100;
                    @endphp
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700">{{ $currency }}</span>
                            <span class="text-gray-500">{{ $count }} regions</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Fee Rates -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Average Fee Rates</h2>

            <div class="grid grid-cols-2 gap-6">
                <div class="text-center">
                    <div class="mx-auto w-24 h-24 rounded-full bg-blue-100 flex items-center justify-center">
                        <span class="text-2xl font-bold text-blue-600">{{ number_format($analytics['fee_rates']['platform_fee_avg'], 1) }}%</span>
                    </div>
                    <p class="mt-2 text-sm font-medium text-gray-700">Platform Fee</p>
                    <p class="text-xs text-gray-500">Average across all regions</p>
                </div>

                <div class="text-center">
                    <div class="mx-auto w-24 h-24 rounded-full bg-green-100 flex items-center justify-center">
                        <span class="text-2xl font-bold text-green-600">{{ number_format($analytics['fee_rates']['worker_fee_avg'], 1) }}%</span>
                    </div>
                    <p class="mt-2 text-sm font-medium text-gray-700">Worker Fee</p>
                    <p class="text-xs text-gray-500">Average across all regions</p>
                </div>
            </div>
        </div>

        <!-- Adjustments by Type -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Adjustments by Type</h2>

            @if(!empty($analytics['adjustments']['by_type']))
                <div class="space-y-3">
                    @php
                        $typeLabels = [
                            'subscription' => 'Subscription',
                            'service_fee' => 'Service Fee',
                            'surge' => 'Surge Pricing',
                            'promotional' => 'Promotional',
                            'seasonal' => 'Seasonal',
                            'holiday' => 'Holiday',
                        ];
                        $colors = ['blue', 'green', 'yellow', 'purple', 'pink', 'red'];
                    @endphp
                    @foreach($analytics['adjustments']['by_type'] as $type => $count)
                        @php
                            $colorIndex = array_search($type, array_keys($analytics['adjustments']['by_type'])) % count($colors);
                            $color = $colors[$colorIndex];
                        @endphp
                        <div class="flex items-center justify-between p-3 bg-{{ $color }}-50 rounded-lg">
                            <span class="text-sm font-medium text-{{ $color }}-700">{{ $typeLabels[$type] ?? ucfirst($type) }}</span>
                            <span class="text-lg font-bold text-{{ $color }}-800">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 text-center py-4">No adjustments configured yet.</p>
            @endif
        </div>

        <!-- Rate Ranges -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Rate Ranges (Averages)</h2>

            <div class="space-y-4">
                <div class="p-4 bg-gray-50 rounded-lg">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-700">Minimum Hourly Rate</span>
                        <span class="text-lg font-bold text-gray-900">${{ number_format($analytics['rate_ranges']['min_rate_avg'], 2) }}</span>
                    </div>
                    <p class="text-xs text-gray-500">Average minimum rate across all regions (USD equivalent)</p>
                </div>

                <div class="p-4 bg-gray-50 rounded-lg">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-700">Maximum Hourly Rate</span>
                        <span class="text-lg font-bold text-gray-900">${{ number_format($analytics['rate_ranges']['max_rate_avg'], 2) }}</span>
                    </div>
                    <p class="text-xs text-gray-500">Average maximum rate across all regions (USD equivalent)</p>
                </div>
            </div>
        </div>

        <!-- Regions by Continent -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Coverage by Continent</h2>

            @if($regionsByContinent->isNotEmpty())
                <div class="space-y-3">
                    @php
                        $continentColors = [
                            'North America' => 'blue',
                            'Europe' => 'green',
                            'Asia' => 'yellow',
                            'Oceania' => 'purple',
                            'Africa' => 'orange',
                            'South America' => 'pink',
                            'Middle East' => 'red',
                            'Other' => 'gray',
                        ];
                    @endphp
                    @foreach($regionsByContinent as $continent => $count)
                        @php
                            $color = $continentColors[$continent] ?? 'gray';
                        @endphp
                        <div class="flex items-center justify-between p-3 bg-{{ $color }}-50 rounded-lg">
                            <span class="text-sm font-medium text-{{ $color }}-700">{{ $continent }}</span>
                            <span class="text-lg font-bold text-{{ $color }}-800">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 text-center py-4">No regional data available.</p>
            @endif
        </div>
    </div>
</div>
@endsection
