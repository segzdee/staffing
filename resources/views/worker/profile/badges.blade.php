@extends('layouts.dashboard')

@section('title', 'My Badges')
@section('page-title', 'My Badges & Achievements')

@section('sidebar-nav')
<x-dashboard.sidebar-nav />
@endsection

@section('content')
<div class="p-4 sm:p-6 space-y-6">
    <!-- Header -->
    <div class="mb-6 sm:mb-8">
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-1">My Badges & Achievements</h2>
        <p class="text-sm sm:text-base text-gray-500">Track your progress and earned achievements</p>
    </div>

    <!-- Badges Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
        @php
            $badgeService = app(\App\Services\BadgeService::class);
            $badgeDefinitions = \App\Models\WorkerBadge::getBadgeDefinitions();
            $earnedBadges = auth()->user()->badges()->with('badge')->get()->keyBy('badge_type');
        @endphp

        @foreach($badgeDefinitions as $badgeType => $badge)
            @php
                $earned = $earnedBadges->get($badgeType);
                $progress = $badgeService->getBadgeProgress(auth()->user(), $badgeType);
            @endphp

            <div class="bg-white rounded-xl border-2 {{ $earned ? 'border-yellow-400 shadow-lg' : 'border-gray-200' }} p-4 sm:p-6 transition-all hover:shadow-xl">
                <div class="flex flex-col sm:flex-row sm:items-start gap-4 mb-4">
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div class="w-12 h-12 sm:w-16 sm:h-16 {{ $earned ? 'bg-yellow-100' : 'bg-gray-100' }} rounded-full flex items-center justify-center flex-shrink-0">
                            @if($earned)
                                <svg class="w-6 h-6 sm:w-8 sm:h-8 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @else
                                <svg class="w-6 h-6 sm:w-8 sm:h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-base sm:text-lg font-bold text-gray-900">{{ $badge['name'] }}</h3>
                                @if($earned)
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">
                                        Earned
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs sm:text-sm text-gray-500 mt-1">{{ $badge['description'] }}</p>
                        </div>
                    </div>
                </div>

                @if($earned)
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <p class="text-sm text-gray-600">
                            <strong>Earned:</strong> {{ $earned->earned_at->format('M d, Y') }}
                        </p>
                    </div>
                @else
                    <div class="mt-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Progress</span>
                            <span class="text-sm text-gray-500">{{ $progress['current'] }}/{{ $progress['target'] }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-primary-600 h-2 rounded-full transition-all" style="width: {{ min(100, ($progress['current'] / max(1, $progress['target'])) * 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">{{ $progress['description'] }}</p>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Recent Badges -->
    @php
        $recentBadges = auth()->user()->badges()->with('badge')->latest('earned_at')->limit(5)->get();
    @endphp

    @if($recentBadges->count() > 0)
    <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-6">
        <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-4">Recently Earned</h3>
        <div class="space-y-3">
            @foreach($recentBadges as $badge)
                <div class="flex items-center gap-3 sm:gap-4 p-3 bg-gray-50 rounded-lg">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-gray-900 text-sm sm:text-base truncate">{{ $badgeDefinitions[$badge->badge_type]['name'] ?? $badge->badge_type }}</h4>
                        <p class="text-xs sm:text-sm text-gray-500">Earned {{ $badge->earned_at->diffForHumans() }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
