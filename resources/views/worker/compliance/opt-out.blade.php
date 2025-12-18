@extends('layouts.worker')

@section('title', 'Request Opt-Out - ' . $rule->name)

@section('content')
<div class="container mx-auto px-4 py-6 max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('worker.compliance.index') }}" class="text-blue-600 hover:underline text-sm">
            &larr; Back to Compliance Dashboard
        </a>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-2">Request Opt-Out</h1>
    <h2 class="text-lg text-gray-600 mb-6">{{ $rule->name }}</h2>

    @if($existingExemption)
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <h3 class="font-medium text-yellow-800">You already have an exemption for this rule</h3>
        <p class="text-sm text-yellow-700 mt-1">
            Status: <strong>{{ ucfirst($existingExemption->status) }}</strong>
            @if($existingExemption->status === 'approved')
                - Valid until {{ $existingExemption->valid_until?->format('M d, Y') ?? 'indefinitely' }}
            @endif
        </p>
        @if($existingExemption->status === 'pending')
        <p class="text-sm text-yellow-700 mt-2">
            Your request is pending review. You will be notified when it is processed.
        </p>
        @endif
    </div>
    @else

    <!-- Rule Information -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="font-semibold text-gray-700 mb-2">About This Rule</h3>
        <p class="text-gray-600 mb-4">{{ $rule->description }}</p>

        @if($rule->legal_reference)
        <p class="text-sm text-gray-500">
            <strong>Legal Reference:</strong> {{ $rule->legal_reference }}
        </p>
        @endif

        <div class="mt-4 p-4 bg-gray-50 rounded-lg">
            <h4 class="font-medium text-gray-700 mb-2">Current Limits</h4>
            @if($rule->getMaxHours())
            <p class="text-sm text-gray-600">Maximum Hours: {{ $rule->getMaxHours() }}h per {{ $rule->getPeriod() ?? 'week' }}</p>
            @endif
            @if($rule->getMinHours())
            <p class="text-sm text-gray-600">Minimum Rest: {{ $rule->getMinHours() }}h</p>
            @endif
        </div>
    </div>

    <!-- Warning Box -->
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <h3 class="font-medium text-red-800 mb-2">Important: Read Before Proceeding</h3>
        <ul class="text-sm text-red-700 space-y-2">
            <li>By opting out, you agree to work hours or conditions that exceed the standard legal protections.</li>
            <li>This opt-out is voluntary - you cannot be required to opt-out as a condition of employment.</li>
            <li>You may withdraw your opt-out at any time with reasonable notice.</li>
            <li>Opting out does not affect your other employment rights.</li>
            @if($rule->opt_out_requirements)
            <li><strong>Specific Requirements:</strong> {{ $rule->opt_out_requirements }}</li>
            @endif
        </ul>
    </div>

    <!-- Opt-Out Form -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="font-semibold text-gray-700 mb-4">Submit Your Opt-Out Request</h3>

        <form action="{{ route('worker.compliance.submit-opt-out', $rule) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">
                    Reason for Opting Out <span class="text-red-500">*</span>
                </label>
                <textarea
                    id="reason"
                    name="reason"
                    rows="4"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('reason') border-red-500 @enderror"
                    placeholder="Please explain why you want to opt-out of this regulation..."
                    required
                >{{ old('reason') }}</textarea>
                @error('reason')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="valid_until" class="block text-sm font-medium text-gray-700 mb-1">
                    Opt-Out Valid Until (Optional)
                </label>
                <input
                    type="date"
                    id="valid_until"
                    name="valid_until"
                    value="{{ old('valid_until') }}"
                    min="{{ now()->addDay()->format('Y-m-d') }}"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('valid_until') border-red-500 @enderror"
                >
                <p class="text-sm text-gray-500 mt-1">Leave blank for indefinite opt-out. You can always withdraw later.</p>
                @error('valid_until')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="document" class="block text-sm font-medium text-gray-700 mb-1">
                    Supporting Document (Optional)
                </label>
                <input
                    type="file"
                    id="document"
                    name="document"
                    accept=".pdf,.jpg,.jpeg,.png"
                    class="w-full border border-gray-300 rounded-md p-2 @error('document') border-red-500 @enderror"
                >
                <p class="text-sm text-gray-500 mt-1">Accepted formats: PDF, JPG, PNG. Max size: 5MB</p>
                @error('document')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label class="flex items-start">
                    <input
                        type="checkbox"
                        name="acknowledge_consequences"
                        value="1"
                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 mt-1 @error('acknowledge_consequences') border-red-500 @enderror"
                        required
                    >
                    <span class="ml-2 text-sm text-gray-700">
                        I understand and acknowledge that by opting out:
                        <ul class="list-disc ml-4 mt-1 text-gray-600">
                            <li>I may be asked to work hours that exceed normal legal limits</li>
                            <li>I am making this decision voluntarily</li>
                            <li>I can withdraw this opt-out at any time</li>
                            <li>This does not affect my other employment rights</li>
                        </ul>
                    </span>
                </label>
                @error('acknowledge_consequences')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <a href="{{ route('worker.compliance.index') }}" class="text-gray-600 hover:text-gray-800">
                    Cancel
                </a>
                <button
                    type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    Submit Opt-Out Request
                </button>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection
