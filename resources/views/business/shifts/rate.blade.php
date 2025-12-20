@extends('layouts.authenticated')

@section('title', 'Rate Worker')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-4 py-4 sm:px-6 sm:py-5 border-b border-gray-200 bg-gray-50">
                <h4 class="text-lg sm:text-xl font-semibold text-gray-900">Rate Worker Performance</h4>
                <p class="mt-1 text-sm text-gray-600 truncate">Shift: {{ $assignment->shift->title }}</p>
                <p class="text-sm text-gray-600">Worker: {{ $rated->name }}</p>
            </div>
            <div class="p-4 sm:p-6">
                <form action="{{ route('business.shifts.rate.store', $assignment->id) }}" method="POST">
                    @csrf

                    {{-- WKR-004: 4-Category Rating System for Workers --}}
                    <div class="mb-6">
                        <h5 class="text-base sm:text-lg font-semibold mb-2 sm:mb-3">Rate the Worker in Each Category</h5>
                        <p class="text-sm text-gray-500 mb-4">All categories are required. Your ratings help maintain quality standards on the platform.</p>

                        {{-- Punctuality Rating --}}
                        <div class="mb-4 p-3 sm:p-4 bg-gray-50 rounded-lg">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2 mb-3">
                                <div class="flex-1 min-w-0">
                                    <x-ui.label class="after:content-['*'] after:ml-0.5 after:text-destructive text-sm sm:text-base font-medium"
                                        value="{{ $categories['punctuality']['label'] }}" />
                                    <p class="text-xs sm:text-sm text-gray-500">{{ $categories['punctuality']['description'] }}</p>
                                </div>
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded self-start flex-shrink-0">{{ $categories['punctuality']['weight'] * 100 }}% weight</span>
                            </div>
                            <div class="rating-input" data-category="punctuality">
                                @for($i = 5; $i >= 1; $i--)
                                    <input type="radio" name="punctuality_rating" value="{{ $i }}" id="punctuality_{{ $i }}" required
                                        {{ old('punctuality_rating') == $i ? 'checked' : '' }}>
                                    <label for="punctuality_{{ $i }}" class="star" title="{{ config('ratings.labels')[$i] ?? '' }}">&#9733;</label>
                                @endfor
                            </div>
                            @error('punctuality_rating')
                                <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Quality Rating --}}
                        <div class="mb-4 p-3 sm:p-4 bg-gray-50 rounded-lg">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2 mb-3">
                                <div class="flex-1 min-w-0">
                                    <x-ui.label class="after:content-['*'] after:ml-0.5 after:text-destructive text-sm sm:text-base font-medium"
                                        value="{{ $categories['quality']['label'] }}" />
                                    <p class="text-xs sm:text-sm text-gray-500">{{ $categories['quality']['description'] }}</p>
                                </div>
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded self-start flex-shrink-0">{{ $categories['quality']['weight'] * 100 }}% weight</span>
                            </div>
                            <div class="rating-input" data-category="quality">
                                @for($i = 5; $i >= 1; $i--)
                                    <input type="radio" name="quality_rating" value="{{ $i }}" id="quality_{{ $i }}" required
                                        {{ old('quality_rating') == $i ? 'checked' : '' }}>
                                    <label for="quality_{{ $i }}" class="star" title="{{ config('ratings.labels')[$i] ?? '' }}">&#9733;</label>
                                @endfor
                            </div>
                            @error('quality_rating')
                                <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Professionalism Rating --}}
                        <div class="mb-4 p-3 sm:p-4 bg-gray-50 rounded-lg">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2 mb-3">
                                <div class="flex-1 min-w-0">
                                    <x-ui.label class="after:content-['*'] after:ml-0.5 after:text-destructive text-sm sm:text-base font-medium"
                                        value="{{ $categories['professionalism']['label'] }}" />
                                    <p class="text-xs sm:text-sm text-gray-500">{{ $categories['professionalism']['description'] }}</p>
                                </div>
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded self-start flex-shrink-0">{{ $categories['professionalism']['weight'] * 100 }}% weight</span>
                            </div>
                            <div class="rating-input" data-category="professionalism">
                                @for($i = 5; $i >= 1; $i--)
                                    <input type="radio" name="professionalism_rating" value="{{ $i }}" id="professionalism_{{ $i }}" required
                                        {{ old('professionalism_rating') == $i ? 'checked' : '' }}>
                                    <label for="professionalism_{{ $i }}" class="star" title="{{ config('ratings.labels')[$i] ?? '' }}">&#9733;</label>
                                @endfor
                            </div>
                            @error('professionalism_rating')
                                <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Reliability Rating --}}
                        <div class="mb-4 p-3 sm:p-4 bg-gray-50 rounded-lg">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2 mb-3">
                                <div class="flex-1 min-w-0">
                                    <x-ui.label class="after:content-['*'] after:ml-0.5 after:text-destructive text-sm sm:text-base font-medium"
                                        value="{{ $categories['reliability']['label'] }}" />
                                    <p class="text-xs sm:text-sm text-gray-500">{{ $categories['reliability']['description'] }}</p>
                                </div>
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded self-start flex-shrink-0">{{ $categories['reliability']['weight'] * 100 }}% weight</span>
                            </div>
                            <div class="rating-input" data-category="reliability">
                                @for($i = 5; $i >= 1; $i--)
                                    <input type="radio" name="reliability_rating" value="{{ $i }}" id="reliability_{{ $i }}" required
                                        {{ old('reliability_rating') == $i ? 'checked' : '' }}>
                                    <label for="reliability_{{ $i }}" class="star" title="{{ config('ratings.labels')[$i] ?? '' }}">&#9733;</label>
                                @endfor
                            </div>
                            @error('reliability_rating')
                                <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Would Hire Again --}}
                    <div class="mb-6 p-3 sm:p-4 border border-gray-200 rounded-lg">
                        <x-ui.label class="text-sm sm:text-base font-medium mb-3"
                            value="Would You Hire This Worker Again?" />
                        <div class="flex flex-wrap gap-4">
                            <div class="flex items-center space-x-2">
                                <input type="radio" name="would_hire_again" value="1" id="yes"
                                    class="h-5 w-5 border-gray-300 text-primary focus:ring-primary"
                                    {{ old('would_hire_again') == '1' ? 'checked' : '' }}>
                                <label for="yes"
                                    class="text-sm font-medium leading-none">Yes</label>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input type="radio" name="would_hire_again" value="0" id="no"
                                    class="h-5 w-5 border-gray-300 text-primary focus:ring-primary"
                                    {{ old('would_hire_again') == '0' ? 'checked' : '' }}>
                                <label for="no"
                                    class="text-sm font-medium leading-none">No</label>
                            </div>
                        </div>
                    </div>

                    {{-- Review Text --}}
                    <div class="mb-6 space-y-2">
                        <x-ui.label for="review_text" value="Feedback for the Worker (Optional)" />
                        <x-ui.textarea name="review_text" id="review_text" rows="4" maxlength="1000"
                            class="w-full"
                            placeholder="Share constructive feedback about this worker's performance...">{{ old('review_text') }}</x-ui.textarea>
                        <p class="text-xs text-gray-500">Max 1000 characters</p>
                        @error('review_text')
                            <div class="text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Submit Buttons --}}
                    <div class="flex flex-col-reverse sm:flex-row gap-3">
                        <x-ui.button variant="secondary" tag="a"
                            href="{{ route('business.shifts.show', $assignment->shift->id) }}"
                            class="w-full sm:w-auto justify-center">Cancel</x-ui.button>
                        <x-ui.button type="submit" class="w-full sm:w-auto justify-center">Submit Rating</x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 2px;
        }

        .rating-input input {
            display: none;
        }

        .rating-input label {
            font-size: 1.5rem;
            cursor: pointer;
            color: #d1d5db;
            transition: color 0.2s, transform 0.1s;
            padding: 4px;
            min-height: 40px;
            min-width: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @media (min-width: 640px) {
            .rating-input {
                gap: 4px;
            }
            .rating-input label {
                font-size: 2rem;
                padding: 2px;
            }
        }

        .rating-input label:hover {
            transform: scale(1.1);
        }

        .rating-input input:checked ~ label,
        .rating-input label:hover,
        .rating-input label:hover ~ label {
            color: #fbbf24;
        }

        .rating-input input:checked ~ label {
            color: #f59e0b;
        }
    </style>
@endsection
