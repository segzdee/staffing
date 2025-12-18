@extends('agency.registration.layout')

@section('form-content')
<form action="{{ route('agency.register.saveStep', $step) }}" method="POST" class="space-y-6">
    @csrf

    <!-- Existing Workers Count -->
    <div>
        <label for="existing_workers_count" class="block text-sm font-medium text-gray-700">How many workers do you currently have?</label>
        <div class="mt-1">
            <select id="existing_workers_count" name="existing_workers_count" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                <option value="">Select a range</option>
                @foreach(['1-10', '11-50', '51-100', '101-500', '500+'] as $range)
                <option value="{{ $range }}" {{ old('existing_workers_count', $data['existing_workers_count'] ?? '') == $range ? 'selected' : '' }}>
                    {{ $range }}
                </option>
                @endforeach
            </select>
        </div>
        @error('existing_workers_count')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Industries -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Industries Served</label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach($stepData['industries'] ?? [] as $key => $label)
            <div class="relative flex items-start">
                <div class="flex items-center h-5">
                    <input id="industry_{{ $key }}" name="industries[]" type="checkbox" value="{{ $key }}" 
                           {{ in_array($key, old('industries', $data['industries'] ?? [])) ? 'checked' : '' }}
                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                </div>
                <div class="ml-3 text-sm">
                    <label for="industry_{{ $key }}" class="font-medium text-gray-700">{{ $label }}</label>
                </div>
            </div>
            @endforeach
        </div>
        @error('industries')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Worker Types -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Worker Types Offered</label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach(['full_time' => 'Full Time', 'part_time' => 'Part Time', 'temporary' => 'Temporary / Shift Based', 'seasonal' => 'Seasonal', 'contract' => 'Contract'] as $key => $label)
            <div class="relative flex items-start">
                <div class="flex items-center h-5">
                    <input id="worker_type_{{ $key }}" name="worker_types[]" type="checkbox" value="{{ $key }}" 
                           {{ in_array($key, old('worker_types', $data['worker_types'] ?? [])) ? 'checked' : '' }}
                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                </div>
                <div class="ml-3 text-sm">
                    <label for="worker_type_{{ $key }}" class="font-medium text-gray-700">{{ $label }}</label>
                </div>
            </div>
            @endforeach
        </div>
        @error('worker_types')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Average Placements -->
    <div>
        <label for="average_placements_monthly" class="block text-sm font-medium text-gray-700">Average Monthly Placements</label>
        <div class="mt-1">
            <input type="number" name="average_placements_monthly" id="average_placements_monthly" 
                   value="{{ old('average_placements_monthly', $data['average_placements_monthly'] ?? '') }}"
                   min="0"
                   class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
        </div>
        @error('average_placements_monthly')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Service Areas -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Service Areas (Cities/Regions)</label>
        <div class="space-y-2" x-data="{ areas: {{ json_encode(old('service_areas', $data['service_areas'] ?? [''])) }} }">
            <template x-for="(area, index) in areas" :key="index">
                <div class="flex gap-2">
                    <input type="text" :name="'service_areas[' + index + ']'" x-model="areas[index]"
                           class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                           placeholder="e.g. New York Metro Area">
                    <button type="button" @click="areas = areas.filter((_, i) => i !== index)"
                            x-show="areas.length > 1"
                            class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <button type="button" @click="areas.push('')"
                            x-show="index === areas.length - 1"
                            class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                </div>
            </template>
        </div>
        @error('service_areas')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="pt-5 flex justify-between">
        <a href="{{ route('agency.register.previous', $step) }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Back
        </a>
        <button type="submit" class="bg-indigo-600 border border-transparent rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Save & Continue
        </button>
    </div>
</form>
@endsection
