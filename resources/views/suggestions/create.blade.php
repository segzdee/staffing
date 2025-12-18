@extends('layouts.app')

@section('title', 'Submit a Suggestion')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-3xl">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('suggestions.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Suggestions
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Submit a Suggestion</h1>
        <p class="mt-1 text-gray-600">Share your idea for improving OvertimeStaff</p>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <form action="{{ route('suggestions.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                    Title <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" id="title" value="{{ old('title') }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary @error('title') border-red-500 @enderror"
                       placeholder="Brief, descriptive title for your suggestion"
                       required>
                @error('title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Category & Priority -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
                        Category <span class="text-red-500">*</span>
                    </label>
                    <select name="category" id="category"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary @error('category') border-red-500 @enderror"
                            required>
                        <option value="">Select a category</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">
                        Priority <span class="text-red-500">*</span>
                    </label>
                    <select name="priority" id="priority"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary @error('priority') border-red-500 @enderror"
                            required>
                        @foreach($priorities as $key => $label)
                            <option value="{{ $key }}" {{ old('priority', 'medium') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('priority')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                    Description <span class="text-red-500">*</span>
                </label>
                <textarea name="description" id="description" rows="6"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary @error('description') border-red-500 @enderror"
                          placeholder="Describe your suggestion in detail. What problem does it solve? How should it work?"
                          required>{{ old('description') }}</textarea>
                <p class="mt-1 text-sm text-gray-500">Minimum 20 characters</p>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Expected Impact -->
            <div>
                <label for="expected_impact" class="block text-sm font-medium text-gray-700 mb-1">
                    Expected Impact (Optional)
                </label>
                <textarea name="expected_impact" id="expected_impact" rows="3"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary @error('expected_impact') border-red-500 @enderror"
                          placeholder="How would this improvement benefit users or the platform?">{{ old('expected_impact') }}</textarea>
                @error('expected_impact')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Guidelines -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-900 mb-2">Suggestion Guidelines</h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li class="flex items-start">
                        <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Be specific and provide as much detail as possible
                    </li>
                    <li class="flex items-start">
                        <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Check existing suggestions to avoid duplicates
                    </li>
                    <li class="flex items-start">
                        <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Focus on how the improvement would benefit users
                    </li>
                    <li class="flex items-start">
                        <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Use the appropriate priority level
                    </li>
                </ul>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200">
                <a href="{{ route('suggestions.index') }}"
                   class="px-4 py-2 text-gray-700 hover:text-gray-900">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                    Submit Suggestion
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
