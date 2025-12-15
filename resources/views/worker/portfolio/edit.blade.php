@extends('layouts.dashboard')

@section('title', 'Edit Portfolio Item')
@section('page-title', 'Edit Portfolio Item')
@section('page-subtitle', 'Update the details of your portfolio item')

@section('content')
<div class="max-w-2xl">
    <form action="{{ route('worker.portfolio.update', $item) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Preview -->
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <label class="block text-sm font-medium text-gray-700 mb-4">File Preview</label>
            <div class="aspect-video bg-gray-100 rounded-lg overflow-hidden">
                @if($item->isImage())
                    <img src="{{ $item->file_url }}" alt="{{ $item->title }}" class="w-full h-full object-contain">
                @elseif($item->isVideo())
                    <video src="{{ $item->file_url }}" class="w-full h-full" controls></video>
                @else
                    <div class="w-full h-full flex flex-col items-center justify-center">
                        <svg class="w-16 h-16 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="text-sm text-gray-600">{{ $item->original_filename }}</span>
                        <a href="{{ $item->file_url }}" target="_blank" class="mt-2 text-sm text-blue-600 hover:underline">View Document</a>
                    </div>
                @endif
            </div>
            <div class="mt-3 flex items-center justify-between text-sm text-gray-500">
                <span class="capitalize">{{ $item->type }}</span>
                <span>{{ $item->formatted_file_size }}</span>
            </div>
        </div>

        <!-- Title & Description -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 space-y-4">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                <input type="text" name="title" id="title" value="{{ old('title', $item->title) }}" required maxlength="100" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                @error('title')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description (optional)</label>
                <textarea name="description" id="description" rows="3" maxlength="500" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent">{{ old('description', $item->description) }}</textarea>
                @error('description')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center">
                <input type="hidden" name="is_visible" value="0">
                <input type="checkbox" name="is_visible" id="is_visible" value="1" {{ old('is_visible', $item->is_visible) ? 'checked' : '' }} class="w-4 h-4 text-gray-900 border-gray-300 rounded focus:ring-gray-900">
                <label for="is_visible" class="ml-2 text-sm text-gray-700">
                    Visible on public profile
                </label>
            </div>
        </div>

        <!-- Featured Status -->
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-900">Featured Status</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        @if($item->is_featured)
                            This item is your featured portfolio item.
                        @else
                            Set this as your featured item to display prominently on your profile.
                        @endif
                    </p>
                </div>

                @if(!$item->is_featured)
                    <form action="{{ route('worker.portfolio.featured', $item) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-yellow-700 bg-yellow-100 hover:bg-yellow-200 rounded-lg transition-colors">
                            Set as Featured
                        </button>
                    </form>
                @else
                    <span class="inline-flex items-center px-3 py-1 bg-yellow-100 text-yellow-800 text-sm font-medium rounded-full">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        Featured
                    </span>
                @endif
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <a href="{{ route('worker.portfolio.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900">
                Cancel
            </a>
            <div class="flex items-center gap-3">
                <form action="{{ route('worker.portfolio.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this item? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-red-700 bg-red-100 hover:bg-red-200 rounded-lg transition-colors">
                        Delete
                    </button>
                </form>
                <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-lg transition-colors">
                    Save Changes
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
