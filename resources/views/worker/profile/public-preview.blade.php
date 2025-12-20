@extends('layouts.dashboard')

@section('title', 'Public Profile Preview')
@section('page-title', 'Public Profile Preview')
@section('page-subtitle', 'This is how your profile appears to businesses and other users')

@section('content')
<div class="space-y-6">
    <!-- Preview Notice -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3">
                <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5 sm:mt-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <div>
                    <p class="font-medium text-blue-900">Preview Mode</p>
                    <p class="text-sm text-blue-700">This is a preview of your public profile. Changes you make will be reflected here.</p>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 flex-shrink-0">
                @if($profile['enabled'])
                    <a href="{{ route('profile.public', $profile['slug']) }}" target="_blank" class="flex items-center justify-center px-4 py-2 text-sm font-medium text-blue-700 bg-blue-100 hover:bg-blue-200 rounded-lg transition-colors min-h-[44px]">
                        View Live Profile
                    </a>
                @endif
                <a href="{{ route('worker.portfolio.index') }}" class="flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg transition-colors min-h-[44px]">
                    Back to Portfolio
                </a>
            </div>
        </div>
    </div>

    @if(!$profile['enabled'])
        <!-- Not Enabled Warning -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <p class="font-medium text-yellow-900">Public Profile Disabled</p>
                    <p class="text-sm text-yellow-700">Your profile is not publicly visible. Enable it from the Portfolio page to share it.</p>
                </div>
            </div>
        </div>
    @else
        <!-- Profile Preview Frame -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <!-- Browser Mock Header -->
            <div class="bg-gray-100 border-b border-gray-200 px-4 py-2 flex items-center gap-2">
                <div class="flex gap-1.5">
                    <div class="w-3 h-3 rounded-full bg-red-400"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                    <div class="w-3 h-3 rounded-full bg-green-400"></div>
                </div>
                <div class="flex-1 ml-4">
                    <div class="bg-white rounded px-3 py-1 text-sm text-gray-500 truncate">
                        {{ route('profile.public', $profile['slug']) }}
                    </div>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="p-6">
                <!-- Profile Header -->
                <div class="flex flex-col sm:flex-row sm:items-start gap-6 mb-6">
                    <div class="w-24 h-24 rounded-full bg-gray-200 overflow-hidden flex-shrink-0">
                        @if($profile['worker']['avatar'])
                            <img src="{{ $profile['worker']['avatar'] }}" alt="{{ $profile['worker']['name'] }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-3xl font-bold text-gray-500 bg-gray-100">
                                {{ strtoupper(substr($profile['worker']['name'], 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <h1 class="text-2xl font-bold text-gray-900">{{ $profile['worker']['name'] }}</h1>

                            @if($profile['featured_status'])
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded" style="background-color: {{ $profile['featured_status']['badge_color'] }}20; color: {{ $profile['featured_status']['badge_color'] }};">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                    {{ $profile['featured_status']['tier_name'] }} Featured
                                </span>
                            @endif

                            @if($profile['worker']['identity_verified'])
                                <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded">
                                    Verified
                                </span>
                            @endif
                        </div>

                        @if($profile['worker']['location'])
                            <p class="text-sm text-gray-600 mb-2">{{ $profile['worker']['location'] }}</p>
                        @endif

                        <div class="flex flex-wrap items-center gap-4 text-sm">
                            @if($profile['worker']['rating_average'] > 0)
                                <div class="flex items-center gap-1">
                                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                    <span class="font-semibold">{{ number_format($profile['worker']['rating_average'], 1) }}</span>
                                    <span class="text-gray-500">({{ $profile['worker']['rating_count'] }})</span>
                                </div>
                            @endif

                            @if($profile['worker']['total_shifts_completed'] > 0)
                                <span class="text-gray-600">{{ $profile['worker']['total_shifts_completed'] }} shifts</span>
                            @endif

                            @if($profile['worker']['years_experience'] > 0)
                                <span class="text-gray-600">{{ $profile['worker']['years_experience'] }}+ years</span>
                            @endif
                        </div>

                        @if($profile['worker']['bio'])
                            <p class="mt-4 text-gray-700">{{ $profile['worker']['bio'] }}</p>
                        @endif
                    </div>
                </div>

                <!-- Skills Preview -->
                @if(count($profile['skills']) > 0)
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Skills</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($profile['skills']->take(8) as $skill)
                                <span class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-700 text-sm rounded-full">
                                    {{ $skill['name'] }}
                                    @if($skill['verified'])
                                        <svg class="w-3 h-3 ml-1 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Portfolio Preview -->
                @if(count($profile['portfolio']) > 0)
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Portfolio</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            @foreach($profile['portfolio']->take(4) as $item)
                                <div class="aspect-square bg-gray-100 rounded overflow-hidden">
                                    <img src="{{ $item['thumbnail_url'] }}" alt="{{ $item['title'] }}" class="w-full h-full object-cover">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
