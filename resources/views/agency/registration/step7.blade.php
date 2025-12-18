@extends('agency.registration.layout')

@section('form-content')
<form action="{{ route('agency.register.saveStep', $step) }}" method="POST" class="space-y-6">
    @csrf

    <div class="bg-gray-50 p-4 rounded-md border border-gray-200 mb-6 max-h-60 overflow-y-auto">
        <h3 class="font-medium mb-2">Platform Agency Terms</h3>
        <p class="text-sm text-gray-600 mb-2">
            1. <strong>Relationship</strong>: The Agency acts as an independent provider of staffing services...
        </p>
        <p class="text-sm text-gray-600 mb-2">
            2. <strong>Commission</strong>: Platform fees are calculated based on the Tier selected...
        </p>
        <p class="text-sm text-gray-600 mb-2">
            3. <strong>Compliance</strong>: Agent warrants that all supplied workers are legally eligible to work...
        </p>
        <!-- In a real app, this would include the full legal text -->
        <p class="text-sm text-gray-500 mt-4 italic">Scroll to read full terms...</p>
    </div>

    <div class="space-y-4">
        <div class="relative flex items-start">
            <div class="flex items-center h-5">
                <input id="terms_accepted" name="terms_accepted" type="checkbox" value="1" 
                       {{ old('terms_accepted', $data['terms_accepted'] ?? '') ? 'checked' : '' }}
                       class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
            </div>
            <div class="ml-3 text-sm">
                <label for="terms_accepted" class="font-medium text-gray-700">I accept the <a href="{{ route('terms') }}" class="text-indigo-600 hover:text-indigo-500" target="_blank">General Terms of Service</a>.</label>
            </div>
        </div>
        @error('terms_accepted')
        <p class="text-sm text-red-600 ml-8">{{ $message }}</p>
        @enderror

        <div class="relative flex items-start">
            <div class="flex items-center h-5">
                <input id="commercial_terms_accepted" name="commercial_terms_accepted" type="checkbox" value="1" 
                       {{ old('commercial_terms_accepted', $data['commercial_terms_accepted'] ?? '') ? 'checked' : '' }}
                       class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
            </div>
            <div class="ml-3 text-sm">
                <label for="commercial_terms_accepted" class="font-medium text-gray-700">I accept the <a href="#" class="text-indigo-600 hover:text-indigo-500">Commercial Agency Agreement</a>.</label>
            </div>
        </div>
        @error('commercial_terms_accepted')
        <p class="text-sm text-red-600 ml-8">{{ $message }}</p>
        @enderror

        <div class="relative flex items-start">
            <div class="flex items-center h-5">
                <input id="privacy_accepted" name="privacy_accepted" type="checkbox" value="1" 
                       {{ old('privacy_accepted', $data['privacy_accepted'] ?? '') ? 'checked' : '' }}
                       class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
            </div>
            <div class="ml-3 text-sm">
                <label for="privacy_accepted" class="font-medium text-gray-700">I have read the <a href="{{ route('privacy.settings') }}" class="text-indigo-600 hover:text-indigo-500" target="_blank">Privacy Policy</a>.</label>
            </div>
        </div>
        @error('privacy_accepted')
        <p class="text-sm text-red-600 ml-8">{{ $message }}</p>
        @enderror
        
        <div class="relative flex items-start pt-4 border-t border-gray-200">
            <div class="flex items-center h-5">
                <input id="marketing_consent" name="marketing_consent" type="checkbox" value="1" 
                       {{ old('marketing_consent', $data['marketing_consent'] ?? '') ? 'checked' : '' }}
                       class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
            </div>
            <div class="ml-3 text-sm">
                <label for="marketing_consent" class="font-medium text-gray-700">I agree to receive updates and tips to help grow my agency business.</label>
                <p class="text-gray-500">You can unsubscribe at any time.</p>
            </div>
        </div>
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
