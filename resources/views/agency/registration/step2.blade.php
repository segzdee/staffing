@extends('agency.registration.layout')

@section('form-content')
    <form action="{{ route('agency.register.saveStep', $step) }}" method="POST" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
            <!-- Contact Name -->
            <div class="sm:col-span-6">
                <label for="contact_name" class="block text-sm font-medium text-gray-700">Primary Contact Name</label>
                <div class="mt-1">
                    <input type="text" name="contact_name" id="contact_name"
                        value="{{ old('contact_name', $data['contact_name'] ?? '') }}"
                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                </div>
                @error('contact_name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div class="sm:col-span-3">
                <label for="contact_email" class="block text-sm font-medium text-gray-700">Email Address</label>
                <div class="mt-1">
                    <input type="email" name="contact_email" id="contact_email"
                        value="{{ old('contact_email', $data['contact_email'] ?? '') }}"
                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                </div>
                @error('contact_email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Phone -->
            <div class="sm:col-span-3">
                <label for="contact_phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                <div class="mt-1">
                    <input type="tel" name="contact_phone" id="contact_phone"
                        value="{{ old('contact_phone', $data['contact_phone'] ?? '') }}"
                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                </div>
                @error('contact_phone')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div class="sm:col-span-3">
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <div class="mt-1">
                    <input type="password" name="password" id="password"
                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                </div>
                <p class="mt-1 text-xs text-gray-500">Min. 8 characters</p>
                @error('password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div class="sm:col-span-3">
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                <div class="mt-1">
                    <input type="password" name="password_confirmation" id="password_confirmation"
                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                </div>
            </div>

            <!-- Address -->
            <div class="sm:col-span-6">
                <label for="address" class="block text-sm font-medium text-gray-700">Street Address</label>
                <div class="mt-1">
                    <input type="text" name="address" id="address" value="{{ old('address', $data['address'] ?? '') }}"
                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                </div>
                @error('address')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- City -->
            <div class="sm:col-span-2">
                <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                <div class="mt-1">
                    <input type="text" name="city" id="city" value="{{ old('city', $data['city'] ?? '') }}"
                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                </div>
                @error('city')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- State -->
            <div class="sm:col-span-2">
                <label for="state" class="block text-sm font-medium text-gray-700">State / Province</label>
                <div class="mt-1">
                    <input type="text" name="state" id="state" value="{{ old('state', $data['state'] ?? '') }}"
                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                </div>
                @error('state')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Postal Code -->
            <div class="sm:col-span-2">
                <label for="postal_code" class="block text-sm font-medium text-gray-700">ZIP / Postal Code</label>
                <div class="mt-1">
                    <input type="text" name="postal_code" id="postal_code"
                        value="{{ old('postal_code', $data['postal_code'] ?? '') }}"
                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                </div>
                @error('postal_code')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Country -->
            <div class="sm:col-span-6">
                <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
                <div class="mt-1">
                    <select id="country" name="country"
                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                        @foreach($stepData['countries'] ?? [] as $code => $name)
                            <option value="{{ $code }}" {{ old('country', $data['country'] ?? 'US') == $code ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @error('country')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="pt-5 flex justify-between">
            <a href="{{ route('agency.register.previous', $step) }}"
                class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Back
            </a>
            <button type="submit"
                class="bg-indigo-600 border border-transparent rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Save & Continue
            </button>
        </div>
    </form>
@endsection