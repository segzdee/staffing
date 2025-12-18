@extends('agency.registration.layout')

@section('form-content')
    <form action="{{ route('agency.register.saveStep', $step) }}" method="POST" class="space-y-6">
        @csrf

        <!-- Business Name -->
        <div>
            <label for="business_name" class="block text-sm font-medium text-gray-700">Business Name</label>
            <div class="mt-1">
                <input type="text" name="business_name" id="business_name"
                    value="{{ old('business_name', $data['business_name'] ?? '') }}"
                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                    placeholder="e.g. Apex Staffing Solutions">
            </div>
            @error('business_name')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Registration Number -->
        <div>
            <label for="registration_number" class="block text-sm font-medium text-gray-700">Registration Number
                (EIN/ABN)</label>
            <div class="mt-1">
                <input type="text" name="registration_number" id="registration_number"
                    value="{{ old('registration_number', $data['registration_number'] ?? '') }}"
                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
            </div>
            @error('registration_number')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Agency Type -->
        <div>
            <label for="agency_type" class="block text-sm font-medium text-gray-700">Agency Type</label>
            <div class="mt-1">
                <select id="agency_type" name="agency_type"
                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                    <option value="">Select a type</option>
                    @foreach($stepData['agency_types'] ?? [] as $value => $label)
                        <option value="{{ $value }}" {{ old('agency_type', $data['agency_type'] ?? '') == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            @error('agency_type')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Website -->
        <div>
            <label for="website" class="block text-sm font-medium text-gray-700">Website (Optional)</label>
            <div class="mt-1">
                <input type="url" name="website" id="website" value="{{ old('website', $data['website'] ?? '') }}"
                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                    placeholder="https://">
            </div>
            @error('website')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Description -->
        <div>
            <label for="business_description" class="block text-sm font-medium text-gray-700">Business Description</label>
            <div class="mt-1">
                <textarea id="business_description" name="business_description" rows="3"
                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">{{ old('business_description', $data['business_description'] ?? '') }}</textarea>
            </div>
            <p class="mt-2 text-sm text-gray-500">Briefly describe your agency's focus and expertise.</p>
            @error('business_description')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-5 flex justify-end">
            <button type="submit"
                class="bg-indigo-600 border border-transparent rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Save & Continue
            </button>
        </div>
    </form>
@endsection