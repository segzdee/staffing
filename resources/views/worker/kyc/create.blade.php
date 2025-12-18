@extends('layouts.dashboard')

@section('title', 'Submit KYC Verification')
@section('page-title', 'Verify Your Identity')
@section('page-subtitle', 'Upload your documents to complete identity verification')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6">
            <form id="kyc-form" enctype="multipart/form-data" class="space-y-6">
                @csrf

                {{-- Document Type Selection --}}
                <div>
                    <label for="document_type" class="block text-sm font-medium text-gray-700 mb-2">
                        Document Type <span class="text-red-500">*</span>
                    </label>
                    <select name="document_type" id="document_type" required
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select a document type</option>
                        @foreach($requirements['document_types'] as $type)
                            <option value="{{ $type }}">
                                {{ ucwords(str_replace('_', ' ', $type)) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Choose the type of government-issued ID you will upload.</p>
                </div>

                {{-- Document Country --}}
                <div>
                    <label for="document_country" class="block text-sm font-medium text-gray-700 mb-2">
                        Issuing Country <span class="text-red-500">*</span>
                    </label>
                    <select name="document_country" id="document_country" required
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="{{ $country }}" selected>{{ $country }}</option>
                        {{-- Countries will be populated by JS or use a predefined list --}}
                    </select>
                </div>

                {{-- Document Number (Optional) --}}
                <div>
                    <label for="document_number" class="block text-sm font-medium text-gray-700 mb-2">
                        Document Number <span class="text-gray-400">(Optional)</span>
                    </label>
                    <input type="text" name="document_number" id="document_number"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Enter document number">
                    <p class="mt-1 text-xs text-gray-500">The ID number on your document.</p>
                </div>

                {{-- Document Expiry Date --}}
                <div>
                    <label for="document_expiry" class="block text-sm font-medium text-gray-700 mb-2">
                        Expiry Date <span class="text-gray-400">(Optional)</span>
                    </label>
                    <input type="date" name="document_expiry" id="document_expiry"
                        min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-xs text-gray-500">When does your document expire? Must be in the future.</p>
                </div>

                {{-- Document Front Upload --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Document Front <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-indigo-400 transition-colors" id="drop-zone-front">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="document_front" class="relative cursor-pointer rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                    <span>Upload a file</span>
                                    <input id="document_front" name="document_front" type="file" class="sr-only" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">PNG, JPG, PDF up to {{ round($maxFileSize / 1024) }}MB</p>
                        </div>
                    </div>
                    <div id="preview-front" class="mt-2 hidden">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <img id="preview-front-img" src="" alt="Document front preview" class="w-20 h-14 object-cover rounded">
                            <div class="flex-1">
                                <p id="preview-front-name" class="text-sm font-medium text-gray-900"></p>
                                <p id="preview-front-size" class="text-xs text-gray-500"></p>
                            </div>
                            <button type="button" onclick="clearFile('front')" class="text-red-600 hover:text-red-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Document Back Upload --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Document Back <span class="text-gray-400">(If applicable)</span>
                    </label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-indigo-400 transition-colors" id="drop-zone-back">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="document_back" class="relative cursor-pointer rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                    <span>Upload a file</span>
                                    <input id="document_back" name="document_back" type="file" class="sr-only" accept=".jpg,.jpeg,.png,.webp,.pdf">
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">PNG, JPG, PDF up to {{ round($maxFileSize / 1024) }}MB</p>
                        </div>
                    </div>
                    <div id="preview-back" class="mt-2 hidden">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <img id="preview-back-img" src="" alt="Document back preview" class="w-20 h-14 object-cover rounded">
                            <div class="flex-1">
                                <p id="preview-back-name" class="text-sm font-medium text-gray-900"></p>
                                <p id="preview-back-size" class="text-xs text-gray-500"></p>
                            </div>
                            <button type="button" onclick="clearFile('back')" class="text-red-600 hover:text-red-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Selfie Upload --}}
                @if($requirements['selfie_required'])
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Selfie Photo <span class="text-red-500">*</span>
                    </label>
                    <p class="text-sm text-gray-600 mb-3">Take a clear photo of your face. We will compare it with your document photo.</p>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-indigo-400 transition-colors" id="drop-zone-selfie">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="selfie" class="relative cursor-pointer rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                    <span>Upload a selfie</span>
                                    <input id="selfie" name="selfie" type="file" class="sr-only" accept=".jpg,.jpeg,.png,.webp" required>
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">PNG, JPG up to {{ round($maxFileSize / 1024) }}MB</p>
                        </div>
                    </div>
                    <div id="preview-selfie" class="mt-2 hidden">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <img id="preview-selfie-img" src="" alt="Selfie preview" class="w-14 h-14 object-cover rounded-full">
                            <div class="flex-1">
                                <p id="preview-selfie-name" class="text-sm font-medium text-gray-900"></p>
                                <p id="preview-selfie-size" class="text-xs text-gray-500"></p>
                            </div>
                            <button type="button" onclick="clearFile('selfie')" class="text-red-600 hover:text-red-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Error Display --}}
                <div id="error-container" class="hidden p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex">
                        <svg class="h-5 w-5 text-red-400 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
                            <ul id="error-list" class="mt-2 text-sm text-red-700 list-disc list-inside"></ul>
                        </div>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <a href="{{ route('worker.kyc.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                        Cancel
                    </a>
                    <button type="submit" id="submit-btn"
                        class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="submit-text">Submit for Verification</span>
                        <svg id="submit-spinner" class="hidden ml-2 w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Guidelines --}}
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-6">
        <h4 class="font-medium text-blue-900 mb-3">Document Guidelines</h4>
        <ul class="space-y-2 text-sm text-blue-700">
            <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Ensure all four corners of the document are visible
            </li>
            <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Make sure the text and photo are clear and not blurry
            </li>
            <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Avoid glare, shadows, or obstructions
            </li>
            <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Document must not be expired
            </li>
            <li class="flex items-start gap-2">
                <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                For selfie: face the camera directly with good lighting
            </li>
        </ul>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('kyc-form');
    const submitBtn = document.getElementById('submit-btn');
    const submitText = document.getElementById('submit-text');
    const submitSpinner = document.getElementById('submit-spinner');
    const errorContainer = document.getElementById('error-container');
    const errorList = document.getElementById('error-list');

    // File input handlers
    ['front', 'back', 'selfie'].forEach(type => {
        const input = document.getElementById(type === 'front' ? 'document_front' : (type === 'back' ? 'document_back' : 'selfie'));
        if (input) {
            input.addEventListener('change', function(e) {
                handleFileSelect(e.target.files[0], type);
            });
        }

        // Drag and drop
        const dropZone = document.getElementById('drop-zone-' + type);
        if (dropZone) {
            dropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('border-indigo-500', 'bg-indigo-50');
            });
            dropZone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('border-indigo-500', 'bg-indigo-50');
            });
            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('border-indigo-500', 'bg-indigo-50');
                const file = e.dataTransfer.files[0];
                if (file) {
                    const input = document.getElementById(type === 'front' ? 'document_front' : (type === 'back' ? 'document_back' : 'selfie'));
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    input.files = dt.files;
                    handleFileSelect(file, type);
                }
            });
        }
    });

    function handleFileSelect(file, type) {
        if (!file) return;

        const preview = document.getElementById('preview-' + type);
        const previewImg = document.getElementById('preview-' + type + '-img');
        const previewName = document.getElementById('preview-' + type + '-name');
        const previewSize = document.getElementById('preview-' + type + '-size');
        const dropZone = document.getElementById('drop-zone-' + type);

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            previewImg.src = '/images/pdf-icon.png'; // fallback for PDF
        }

        previewName.textContent = file.name;
        previewSize.textContent = formatFileSize(file.size);
        preview.classList.remove('hidden');
        dropZone.classList.add('hidden');
    }

    window.clearFile = function(type) {
        const input = document.getElementById(type === 'front' ? 'document_front' : (type === 'back' ? 'document_back' : 'selfie'));
        const preview = document.getElementById('preview-' + type);
        const dropZone = document.getElementById('drop-zone-' + type);

        input.value = '';
        preview.classList.add('hidden');
        dropZone.classList.remove('hidden');
    };

    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Reset error state
        errorContainer.classList.add('hidden');
        errorList.innerHTML = '';

        // Show loading state
        submitBtn.disabled = true;
        submitText.textContent = 'Submitting...';
        submitSpinner.classList.remove('hidden');

        const formData = new FormData(form);

        try {
            const response = await fetch('{{ route("worker.kyc.store") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = data.data.redirect_url || '{{ route("worker.kyc.index") }}';
            } else {
                // Show errors
                errorContainer.classList.remove('hidden');
                if (data.errors) {
                    Object.values(data.errors).flat().forEach(error => {
                        const li = document.createElement('li');
                        li.textContent = error;
                        errorList.appendChild(li);
                    });
                } else {
                    const li = document.createElement('li');
                    li.textContent = data.message || 'An error occurred. Please try again.';
                    errorList.appendChild(li);
                }
            }
        } catch (error) {
            errorContainer.classList.remove('hidden');
            const li = document.createElement('li');
            li.textContent = 'Network error. Please check your connection and try again.';
            errorList.appendChild(li);
        } finally {
            submitBtn.disabled = false;
            submitText.textContent = 'Submit for Verification';
            submitSpinner.classList.add('hidden');
        }
    });
});
</script>
@endpush
@endsection
