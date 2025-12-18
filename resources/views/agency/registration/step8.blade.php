@extends('agency.registration.layout')

@section('form-content')
    <form action="{{ route('agency.register.saveStep', $step) }}" method="POST" class="space-y-6">
        @csrf

        <div class="space-y-6">
            <!-- Review Section -->
            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Registration Summary</h3>

                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Business Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ session('agency.registration.step1.business_name') }}</dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Agency Type</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ ucfirst(str_replace('_', ' ', session('agency.registration.step1.agency_type'))) }}</dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Primary Contact</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ session('agency.registration.step2.contact_name') }}</dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ session('agency.registration.step2.contact_email') }}</dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Tier</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ ucfirst(session('agency.registration.step4.partnership_tier')) }}</dd>
                    </div>
                </dl>

                <div class="mt-6 flex justify-end">
                    <a href="{{ route('agency.register.step', 1) }}"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Edit Information</a>
                </div>
            </div>

            <div class="space-y-4">
                <div class="relative flex items-start">
                    <div class="flex items-center h-5">
                        <input id="accuracy_confirmed" name="accuracy_confirmed" type="checkbox" value="1"
                            class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="accuracy_confirmed" class="font-medium text-gray-700">I confirm that all provided
                            information is accurate and truthful.</label>
                    </div>
                </div>
                @error('accuracy_confirmed')
                    <p class="text-sm text-red-600 ml-8">{{ $message }}</p>
                @enderror

                <div class="relative flex items-start">
                    <div class="flex items-center h-5">
                        <input id="final_confirmation" name="final_confirmation" type="checkbox" value="1"
                            class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="final_confirmation" class="font-medium text-gray-700">I am authorized to bind this
                            agency to the OvertimeStaff platform agreement.</label>
                    </div>
                </div>
                @error('final_confirmation')
                    <p class="text-sm text-red-600 ml-8">{{ $message }}</p>
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
                Complete Registration
            </button>
        </div>
    </form>
@endsection