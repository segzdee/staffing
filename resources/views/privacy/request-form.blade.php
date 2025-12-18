@extends('layouts.marketing')

@section('title', 'Data Privacy Request - ' . config('app.name'))

@section('content')
<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Data Privacy Request</h1>
            <p class="mt-2 text-gray-600">Exercise your data privacy rights under GDPR and CCPA</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sm:p-8">
            <form action="{{ route('privacy.submit-request') }}" method="POST" class="space-y-6">
                @csrf

                {{-- Email Address --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        value="{{ old('email', auth()->user()?->email) }}"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('email') border-red-500 @enderror"
                        placeholder="your@email.com"
                    >
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">Enter the email address associated with your account.</p>
                </div>

                {{-- Request Type --}}
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700">Request Type</label>
                    <select
                        name="type"
                        id="type"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('type') border-red-500 @enderror"
                    >
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}" {{ $selectedType === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Request Type Descriptions --}}
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Request Type Information:</h4>
                    <dl class="space-y-3 text-sm">
                        <div id="desc-access" class="request-description">
                            <dt class="font-medium text-gray-700">Data Access (Article 15)</dt>
                            <dd class="text-gray-500">Request a copy of all personal data we hold about you.</dd>
                        </div>
                        <div id="desc-portability" class="request-description hidden">
                            <dt class="font-medium text-gray-700">Data Portability (Article 20)</dt>
                            <dd class="text-gray-500">Receive your data in a machine-readable format for transfer to another service.</dd>
                        </div>
                        <div id="desc-erasure" class="request-description hidden">
                            <dt class="font-medium text-gray-700">Data Erasure (Article 17)</dt>
                            <dd class="text-gray-500">Request deletion of all your personal data (Right to be Forgotten).</dd>
                        </div>
                        <div id="desc-rectification" class="request-description hidden">
                            <dt class="font-medium text-gray-700">Data Rectification (Article 16)</dt>
                            <dd class="text-gray-500">Request correction of inaccurate personal data.</dd>
                        </div>
                        <div id="desc-restriction" class="request-description hidden">
                            <dt class="font-medium text-gray-700">Processing Restriction (Article 18)</dt>
                            <dd class="text-gray-500">Request restriction of processing of your personal data.</dd>
                        </div>
                        <div id="desc-objection" class="request-description hidden">
                            <dt class="font-medium text-gray-700">Processing Objection (Article 21)</dt>
                            <dd class="text-gray-500">Object to processing of your personal data for specific purposes.</dd>
                        </div>
                    </dl>
                </div>

                {{-- Additional Description --}}
                <div id="description-field" class="hidden">
                    <label for="description" class="block text-sm font-medium text-gray-700">Additional Information</label>
                    <textarea
                        name="description"
                        id="description"
                        rows="4"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        placeholder="Please provide details about your request..."
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Important Notice --}}
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Identity Verification Required</h3>
                            <p class="mt-1 text-sm text-yellow-700">
                                After submitting this request, you will receive an email to verify your identity. Your request will only be processed after verification.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="flex items-center justify-between">
                    <a href="{{ url('/') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                    <button
                        type="submit"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Submit Request
                    </button>
                </div>
            </form>
        </div>

        {{-- Legal Information --}}
        <div class="mt-8 text-center text-sm text-gray-500">
            <p>
                Under GDPR, we are required to respond to your request within 30 days.
                For more information, see our <a href="{{ route('privacy.settings') }}" class="text-indigo-600 hover:text-indigo-500">Privacy Policy</a>.
            </p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const descriptionField = document.getElementById('description-field');
    const descriptions = document.querySelectorAll('.request-description');

    function updateForm() {
        const selectedType = typeSelect.value;

        // Show/hide descriptions
        descriptions.forEach(desc => {
            desc.classList.add('hidden');
        });
        const activeDesc = document.getElementById('desc-' + selectedType);
        if (activeDesc) {
            activeDesc.classList.remove('hidden');
        }

        // Show description field for certain types
        const requiresDescription = ['rectification', 'restriction', 'objection'];
        if (requiresDescription.includes(selectedType)) {
            descriptionField.classList.remove('hidden');
        } else {
            descriptionField.classList.add('hidden');
        }
    }

    typeSelect.addEventListener('change', updateForm);
    updateForm(); // Initial state
});
</script>
@endsection
