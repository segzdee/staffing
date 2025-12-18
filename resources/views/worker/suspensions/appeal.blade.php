@extends('layouts.worker')

@section('title', 'Submit Appeal')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="{{ route('worker.suspensions.show', $suspension) }}" class="text-blue-600 hover:underline flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Suspension Details
        </a>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-6">Submit Appeal</h1>

    @if(session('error'))
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
            <p class="text-red-700">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Suspension Summary -->
    <div class="bg-gray-50 rounded-lg p-4 mb-6">
        <h2 class="font-medium text-gray-700 mb-2">Appealing Suspension</h2>
        <p class="text-gray-600">
            <strong>Type:</strong> {{ $suspension->getTypeLabel() }}<br>
            <strong>Reason:</strong> {{ $suspension->getReasonCategoryLabel() }}<br>
            <strong>Issued:</strong> {{ $suspension->created_at->format('F j, Y') }}
        </p>
    </div>

    <!-- Appeal Window Notice -->
    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    You have <strong>{{ $appealDaysRemaining }} day(s)</strong> remaining to submit your appeal.
                    Appeals must be submitted within {{ config('suspensions.appeal_window_days', 7) }} days of the suspension.
                </p>
            </div>
        </div>
    </div>

    <!-- Appeal Form -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <form action="{{ route('worker.suspensions.appeal.submit', $suspension) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-6">
                <label for="appeal_reason" class="block text-sm font-medium text-gray-700 mb-2">
                    Why do you believe this suspension should be overturned? *
                </label>
                <textarea name="appeal_reason" id="appeal_reason" rows="6"
                          class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('appeal_reason') border-red-500 @enderror"
                          placeholder="Please provide a detailed explanation of why you believe this suspension was issued in error. Include any relevant context or circumstances that may not have been considered."
                          required>{{ old('appeal_reason') }}</textarea>
                @error('appeal_reason')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">Minimum 50 characters required</p>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Supporting Evidence (Optional)
                </label>
                <p class="text-sm text-gray-500 mb-3">
                    Upload any documents, screenshots, or other evidence that supports your appeal.
                    Accepted formats: JPG, PNG, PDF, DOC, DOCX. Maximum 5 files, 10MB each.
                </p>
                <input type="file" name="supporting_evidence[]" multiple
                       accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @error('supporting_evidence')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @error('supporting_evidence.*')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 class="font-medium text-gray-700 mb-2">Appeal Guidelines</h3>
                <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
                    <li>Be respectful and professional in your appeal</li>
                    <li>Provide specific facts and evidence to support your case</li>
                    <li>Include any relevant context that may not have been considered</li>
                    <li>Appeals are typically reviewed within 48 hours</li>
                    <li>You will be notified via email when a decision is made</li>
                </ul>
            </div>

            <div class="flex items-center justify-between">
                <a href="{{ route('worker.suspensions.show', $suspension) }}"
                   class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Submit Appeal
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
