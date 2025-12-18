@extends('layouts.app')

@section('title', 'Edit Suggestion')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-3xl">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('suggestions.show', $suggestion) }}" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Suggestion
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Edit Suggestion</h1>
        <p class="mt-1 text-gray-600">Update your suggestion details</p>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <form action="{{ route('suggestions.update', $suggestion) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                    Title <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" id="title" value="{{ old('title', $suggestion->title) }}"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary @error('title') border-red-500 @enderror"
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
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" {{ old('category', $suggestion->category) === $key ? 'selected' : '' }}>{{ $label }}</option>
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
                            <option value="{{ $key }}" {{ old('priority', $suggestion->priority) === $key ? 'selected' : '' }}>{{ $label }}</option>
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
                          required>{{ old('description', $suggestion->description) }}</textarea>
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
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary @error('expected_impact') border-red-500 @enderror">{{ old('expected_impact', $suggestion->expected_impact) }}</textarea>
                @error('expected_impact')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200">
                <a href="{{ route('suggestions.show', $suggestion) }}"
                   class="px-4 py-2 text-gray-700 hover:text-gray-900">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                    Update Suggestion
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
