{{-- WKR-004: Rating Category Breakdown Component --}}
@props([
    'summary' => [],
    'showWeights' => true,
    'compact' => false,
])

@php
    $overallRating = $summary['overall_rating'] ?? 0;
    $weightedRating = $summary['weighted_rating'] ?? 0;
    $totalRatings = $summary['total_ratings'] ?? 0;
    $showBreakdown = $summary['show_breakdown'] ?? false;
    $categories = $summary['categories'] ?? [];
@endphp

<div {{ $attributes->merge(['class' => 'rating-category-breakdown']) }}>
    {{-- Overall Rating Summary --}}
    <div class="flex items-center gap-4 mb-4 {{ $compact ? 'pb-2' : 'pb-4' }} border-b border-gray-200">
        <div class="text-center">
            <div class="text-4xl font-bold text-gray-900">{{ number_format($weightedRating, 1) }}</div>
            <div class="text-sm text-gray-500">Weighted Score</div>
        </div>
        <div class="flex-1">
            <div class="flex items-center gap-1 mb-1">
                @for ($i = 1; $i <= 5; $i++)
                    <svg class="w-5 h-5 {{ $i <= round($weightedRating) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                @endfor
            </div>
            <div class="text-sm text-gray-600">Based on {{ $totalRatings }} {{ Str::plural('rating', $totalRatings) }}</div>
        </div>
    </div>

    {{-- Category Breakdown --}}
    @if ($showBreakdown && !empty($categories))
        <div class="space-y-3">
            @foreach ($categories as $key => $category)
                @php
                    $average = $category['average'] ?? 0;
                    $percentage = ($average / 5) * 100;
                @endphp
                <div class="category-item">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-medium text-gray-700">{{ $category['label'] }}</span>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-900">{{ number_format($average, 1) }}</span>
                            @if ($showWeights)
                                <span class="text-xs text-gray-400">({{ $category['weight'] * 100 }}%)</span>
                            @endif
                        </div>
                    </div>
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-300 {{ $average >= 4 ? 'bg-green-500' : ($average >= 3 ? 'bg-yellow-500' : ($average >= 2 ? 'bg-orange-500' : 'bg-red-500')) }}"
                            style="width: {{ $percentage }}%"
                        ></div>
                    </div>
                    @if (!$compact)
                        <p class="text-xs text-gray-500 mt-1">{{ $category['description'] ?? '' }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    @elseif ($totalRatings > 0)
        <div class="text-center py-4 text-gray-500 text-sm">
            <p>Category breakdown will be available after {{ config('ratings.min_ratings_for_breakdown', 3) }} ratings.</p>
            <p class="text-xs mt-1">Currently {{ $totalRatings }} of {{ config('ratings.min_ratings_for_breakdown', 3) }} required.</p>
        </div>
    @else
        <div class="text-center py-4 text-gray-500 text-sm">
            <p>No ratings yet.</p>
        </div>
    @endif
</div>
