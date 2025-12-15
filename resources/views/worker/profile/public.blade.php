@extends('layouts.guest')

@section('title', $meta['title'] ?? $profile['worker']['name'] . ' - Profile')

@push('meta')
<meta name="description" content="{{ $meta['description'] ?? '' }}">
<meta name="keywords" content="{{ $meta['keywords'] ?? '' }}">
<meta property="og:title" content="{{ $meta['title'] ?? '' }}">
<meta property="og:description" content="{{ $meta['description'] ?? '' }}">
<meta property="og:type" content="profile">
@if($profile['worker']['avatar'])
<meta property="og:image" content="{{ $profile['worker']['avatar'] }}">
@endif
<meta name="twitter:card" content="summary">
@endpush

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-gray-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <rect x="3" y="3" width="7" height="7" rx="1"/>
                            <rect x="14" y="3" width="7" height="7" rx="1"/>
                            <rect x="3" y="14" width="7" height="7" rx="1"/>
                            <rect x="14" y="14" width="7" height="7" rx="1"/>
                        </svg>
                    </div>
                    <span class="text-lg font-bold tracking-tight text-gray-900">
                        OVERTIME<span class="text-gray-600">STAFF</span>
                    </span>
                </a>

                @auth
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900">
                        Dashboard
                    </a>
                @else
                    <div class="flex items-center gap-4">
                        <a href="{{ route('login') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900">Sign In</a>
                        <a href="{{ route('register') }}" class="px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-lg">
                            Get Started
                        </a>
                    </div>
                @endauth
            </div>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Profile Header -->
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden mb-6">
            <!-- Cover/Banner -->
            @if($profile['featured_item'])
                <div class="h-48 bg-gray-200">
                    @if(str_starts_with($profile['featured_item']['type'] ?? '', 'photo'))
                        <img src="{{ $profile['featured_item']['file_url'] }}" alt="Featured work" class="w-full h-full object-cover">
                    @else
                        <img src="{{ $profile['featured_item']['thumbnail_url'] }}" alt="Featured work" class="w-full h-full object-cover">
                    @endif
                </div>
            @else
                <div class="h-32 bg-gradient-to-r from-gray-700 to-gray-900"></div>
            @endif

            <div class="px-6 pb-6 {{ $profile['featured_item'] ? '-mt-16' : 'pt-6' }}">
                <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                    <!-- Avatar -->
                    <div class="w-24 h-24 sm:w-32 sm:h-32 rounded-full border-4 border-white bg-gray-200 overflow-hidden shadow-lg flex-shrink-0">
                        @if($profile['worker']['avatar'])
                            <img src="{{ $profile['worker']['avatar'] }}" alt="{{ $profile['worker']['name'] }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-3xl sm:text-4xl font-bold text-gray-500 bg-gray-100">
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
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Verified
                                </span>
                            @endif

                            @if($profile['worker']['background_check_approved'])
                                <span class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                    Background Checked
                                </span>
                            @endif
                        </div>

                        @if($profile['worker']['location'])
                            <p class="text-sm text-gray-600 flex items-center gap-1 mb-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ $profile['worker']['location'] }}
                            </p>
                        @endif

                        <div class="flex flex-wrap items-center gap-4 text-sm">
                            @if($profile['worker']['rating_average'] > 0)
                                <div class="flex items-center gap-1">
                                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                    <span class="font-semibold text-gray-900">{{ number_format($profile['worker']['rating_average'], 1) }}</span>
                                    <span class="text-gray-500">({{ $profile['worker']['rating_count'] }} reviews)</span>
                                </div>
                            @endif

                            @if($profile['worker']['total_shifts_completed'] > 0)
                                <div class="text-gray-600">
                                    <span class="font-semibold text-gray-900">{{ $profile['worker']['total_shifts_completed'] }}</span> shifts completed
                                </div>
                            @endif

                            @if($profile['worker']['years_experience'] > 0)
                                <div class="text-gray-600">
                                    <span class="font-semibold text-gray-900">{{ $profile['worker']['years_experience'] }}+</span> years experience
                                </div>
                            @endif
                        </div>
                    </div>

                    @auth
                        @if(auth()->user()->isBusiness() || auth()->user()->isAgency())
                            <a href="{{ route('messages.worker', $profile['worker']['id']) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                </svg>
                                Contact
                            </a>
                        @endif
                    @endauth
                </div>

                @if($profile['worker']['bio'])
                    <p class="mt-4 text-gray-700 leading-relaxed">{{ $profile['worker']['bio'] }}</p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Portfolio -->
                @if(count($profile['portfolio']) > 0)
                    <div class="bg-white border border-gray-200 rounded-xl p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Portfolio</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            @foreach($profile['portfolio'] as $item)
                                <a href="{{ route('profile.portfolio', [$profile['slug'], $item['id']]) }}" class="group aspect-square bg-gray-100 rounded-lg overflow-hidden relative">
                                    <img src="{{ $item['thumbnail_url'] }}" alt="{{ $item['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                                        <div class="absolute bottom-2 left-2 right-2">
                                            <p class="text-white text-sm font-medium truncate">{{ $item['title'] }}</p>
                                        </div>
                                    </div>
                                    @if($item['type'] === 'video')
                                        <div class="absolute top-2 right-2">
                                            <div class="w-6 h-6 bg-black/50 rounded-full flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M8 5v14l11-7z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Endorsements -->
                @if(count($profile['endorsements']) > 0)
                    <div class="bg-white border border-gray-200 rounded-xl p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Endorsements</h2>
                        <div class="space-y-4">
                            @foreach($profile['endorsements'] as $endorsement)
                                <div class="border-b border-gray-100 pb-4 last:border-0 last:pb-0">
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-600 font-semibold flex-shrink-0">
                                            {{ strtoupper(substr($endorsement['business_name'], 0, 1)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium text-gray-900">{{ $endorsement['business_name'] }}</span>
                                                @if($endorsement['skill_name'])
                                                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ $endorsement['skill_name'] }}</span>
                                                @endif
                                            </div>
                                            <p class="text-sm text-gray-600 mt-1">{{ $endorsement['text'] }}</p>
                                            <p class="text-xs text-gray-400 mt-1">{{ $endorsement['created_at'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Skills -->
                @if(count($profile['skills']) > 0)
                    <div class="bg-white border border-gray-200 rounded-xl p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Skills</h2>
                        <div class="flex flex-wrap gap-2">
                            @foreach($profile['skills'] as $skill)
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

                <!-- Certifications -->
                @if(count($profile['certifications']) > 0)
                    <div class="bg-white border border-gray-200 rounded-xl p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Certifications</h2>
                        <ul class="space-y-3">
                            @foreach($profile['certifications'] as $cert)
                                <li class="flex items-start gap-2">
                                    <svg class="w-5 h-5 {{ $cert['verified'] ? 'text-green-500' : 'text-gray-400' }} flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $cert['name'] }}</p>
                                        @if($cert['verified'])
                                            <p class="text-xs text-green-600">Verified</p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Reliability -->
                @if($profile['worker']['reliability_score'] > 0)
                    <div class="bg-white border border-gray-200 rounded-xl p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Reliability</h2>
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full {{ $profile['worker']['reliability_score'] >= 90 ? 'bg-green-100' : ($profile['worker']['reliability_score'] >= 70 ? 'bg-yellow-100' : 'bg-red-100') }} mb-2">
                                <span class="text-2xl font-bold {{ $profile['worker']['reliability_score'] >= 90 ? 'text-green-600' : ($profile['worker']['reliability_score'] >= 70 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ round($profile['worker']['reliability_score']) }}%
                                </span>
                            </div>
                            <p class="text-sm text-gray-600">Reliability Score</p>
                        </div>
                    </div>
                @endif

                <!-- CTA for guests -->
                @guest
                    <div class="bg-gradient-to-r from-gray-800 to-gray-900 rounded-xl p-6 text-white">
                        <h3 class="font-semibold mb-2">Looking to hire?</h3>
                        <p class="text-sm text-gray-300 mb-4">Create an account to contact this worker and post shifts.</p>
                        <a href="{{ route('register') }}" class="block w-full py-2 text-center text-sm font-medium text-gray-900 bg-white hover:bg-gray-100 rounded-lg transition-colors">
                            Get Started Free
                        </a>
                    </div>
                @endguest
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="border-t border-gray-200 mt-12 py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-gray-500">
            <p>&copy; {{ date('Y') }} OvertimeStaff. All rights reserved.</p>
        </div>
    </footer>
</div>
@endsection
