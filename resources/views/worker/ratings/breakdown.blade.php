@extends('layouts.authenticated')

@section('title', 'My Ratings')
@section('page-title', 'Rating Breakdown')

@section('sidebar-nav')
<a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('worker.profile') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
    </svg>
    <span>Profile</span>
</a>
<a href="{{ route('worker.ratings.breakdown') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
    </svg>
    <span>Ratings</span>
</a>
@endsection

@section('content')
<div class="p-6">
    <div class="max-w-6xl mx-auto">
        {{-- Page Header --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">My Rating Breakdown</h1>
            <p class="text-gray-600 mt-1">See how businesses have rated your performance across different categories.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column: Category Breakdown --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Category Breakdown Card --}}
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Performance by Category</h2>
                    <x-ratings.category-breakdown :summary="$summary" :show-weights="true" />
                </div>

                {{-- Rating Trend Chart --}}
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Rating Trend (Last 6 Months)</h2>
                    @if (!empty($trend))
                        <div class="h-64">
                            <canvas id="ratingTrendChart"></canvas>
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <p>Not enough data to show trend.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right Column: Distribution & Recent --}}
            <div class="space-y-6">
                {{-- Rating Distribution --}}
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Rating Distribution</h2>
                    @if (array_sum($distribution) > 0)
                        <div class="space-y-2">
                            @foreach (range(5, 1) as $star)
                                @php
                                    $count = $distribution[$star] ?? 0;
                                    $total = array_sum($distribution);
                                    $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                                @endphp
                                <div class="flex items-center gap-2">
                                    <span class="text-sm w-12 text-gray-600">{{ $star }} star</span>
                                    <div class="flex-1 h-3 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-yellow-400 rounded-full" style="width: {{ $percentage }}%"></div>
                                    </div>
                                    <span class="text-sm w-8 text-gray-600 text-right">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-gray-500 text-sm">
                            <p>No ratings yet.</p>
                        </div>
                    @endif
                </div>

                {{-- Category Weights Info --}}
                <div class="bg-brand-50 rounded-xl border border-brand-200 p-6">
                    <h3 class="text-sm font-semibold text-brand-800 mb-3">How Weighted Scores Work</h3>
                    <p class="text-sm text-brand-700 mb-3">Your weighted score is calculated based on performance in each category:</p>
                    <ul class="text-sm text-brand-600 space-y-1">
                        @foreach ($categories as $key => $category)
                            <li class="flex justify-between">
                                <span>{{ $category['label'] }}</span>
                                <span class="font-medium">{{ $category['weight'] * 100 }}%</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        {{-- Recent Ratings --}}
        <div class="mt-6 bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Ratings</h2>
            @if ($recentRatings->count() > 0)
                <div class="space-y-4">
                    @foreach ($recentRatings as $rating)
                        <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg">
                            <div class="flex-shrink-0">
                                @if ($rating->rater?->avatar)
                                    <img src="{{ $rating->rater->avatar }}" alt="{{ $rating->rater->name }}" class="w-10 h-10 rounded-full object-cover">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-brand-100 flex items-center justify-center text-brand-600 font-semibold">
                                        {{ strtoupper(substr($rating->rater?->name ?? 'B', 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-medium text-gray-900">
                                        {{ $rating->rater?->businessProfile?->business_name ?? $rating->rater?->name ?? 'Business' }}
                                    </span>
                                    <span class="text-sm text-gray-500">{{ $rating->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="flex items-center">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <svg class="w-4 h-4 {{ $i <= $rating->rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        @endfor
                                    </div>
                                    @if ($rating->weighted_score)
                                        <span class="text-sm text-gray-500">({{ number_format($rating->weighted_score, 2) }} weighted)</span>
                                    @endif
                                </div>
                                @if ($rating->review_text)
                                    <p class="text-sm text-gray-600">{{ $rating->review_text }}</p>
                                @endif
                                @if ($rating->assignment?->shift)
                                    <p class="text-xs text-gray-400 mt-1">
                                        Shift: {{ $rating->assignment->shift->title }} - {{ $rating->assignment->shift->shift_date?->format('M j, Y') }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <p>No ratings received yet.</p>
                    <p class="text-sm mt-1">Complete shifts to start receiving ratings from businesses.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@if (!empty($trend))
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('ratingTrendChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode(array_column($trend, 'month')) !!},
            datasets: [{
                label: 'Weighted Score',
                data: {!! json_encode(array_column($trend, 'avg_weighted')) !!},
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true,
                tension: 0.3,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    min: 0,
                    max: 5,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>
@endif
@endsection
