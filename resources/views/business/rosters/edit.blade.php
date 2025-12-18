@extends('layouts.dashboard')

@section('title', 'Edit Roster')
@section('page-title', 'Edit Roster')
@section('page-subtitle', 'Update roster settings')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <form action="{{ route('business.rosters.update', $roster) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Roster Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $roster->name) }}" required
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-gray-900 focus:border-gray-900 sm:text-sm"
                        placeholder="e.g., VIP Staff, Weekend Crew">
                    @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Type -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700">Roster Type</label>
                    <select name="type" id="type" required
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-gray-900 focus:border-gray-900 sm:text-sm">
                        @foreach($rosterTypes as $value => $label)
                        <option value="{{ $value }}" {{ old('type', $roster->type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description (Optional)</label>
                    <textarea name="description" id="description" rows="3"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-gray-900 focus:border-gray-900 sm:text-sm"
                        placeholder="Describe the purpose of this roster...">{{ old('description', $roster->description) }}</textarea>
                    @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Is Default -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox" name="is_default" id="is_default" value="1" {{ old('is_default', $roster->is_default) ? 'checked' : '' }}
                            class="h-4 w-4 text-gray-900 focus:ring-gray-900 border-gray-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="is_default" class="font-medium text-gray-700">Set as Default</label>
                        <p class="text-gray-500">Workers will be added to this roster by default when you mark them as this type.</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <form action="{{ route('business.rosters.destroy', $roster) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium" onclick="return confirm('Are you sure you want to delete this roster? All members will be removed.')">
                        Delete Roster
                    </button>
                </form>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('business.rosters.show', $roster) }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800">
                        Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
