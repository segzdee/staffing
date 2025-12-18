@extends('layouts.dashboard')

@section('title', 'Create Roster')
@section('page-title', 'Create Roster')
@section('page-subtitle', 'Create a new worker roster')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <form action="{{ route('business.rosters.store') }}" method="POST" class="p-6">
            @csrf

            <div class="space-y-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Roster Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
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
                        <option value="{{ $value }}" {{ old('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-sm text-gray-500">
                        <span id="type-description">Select a type to see its description.</span>
                    </p>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description (Optional)</label>
                    <textarea name="description" id="description" rows="3"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-gray-900 focus:border-gray-900 sm:text-sm"
                        placeholder="Describe the purpose of this roster...">{{ old('description') }}</textarea>
                    @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Is Default -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox" name="is_default" id="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}
                            class="h-4 w-4 text-gray-900 focus:ring-gray-900 border-gray-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="is_default" class="font-medium text-gray-700">Set as Default</label>
                        <p class="text-gray-500">Workers will be added to this roster by default when you mark them as this type.</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end space-x-3">
                <a href="{{ route('business.rosters.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800">
                    Create Roster
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const typeDescription = document.getElementById('type-description');

    const descriptions = {
        'preferred': 'Preferred workers get first access to your shifts and are shown at the top of your worker list.',
        'regular': 'Regular workers are your trusted pool who have worked well with your business before.',
        'backup': 'Backup workers are contacted when your preferred and regular workers are not available.',
        'blacklist': 'Blacklisted workers are blocked from seeing or applying to your shifts.'
    };

    function updateDescription() {
        const type = typeSelect.value;
        typeDescription.textContent = descriptions[type] || 'Select a type to see its description.';
    }

    typeSelect.addEventListener('change', updateDescription);
    updateDescription();
});
</script>
@endpush
@endsection
