@extends('agency.registration.layout')

@section('form-content')
    <div class="space-y-6">
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        Please upload valid documents. We will verify your business license and insurance before activating
                        your account.
                    </p>
                </div>
            </div>
        </div>

        <div class="space-y-8 divide-y divide-gray-200">
            @foreach($stepData['required_documents'] ?? [] as $key => $doc)
                <div class="pt-8 first:pt-0"
                    x-data="documentUploader('{{ $key }}', '{{ route('agency.register.upload') }}', '{{ route('agency.register.remove-document') }}')">
                    <div class="md:grid md:grid-cols-3 md:gap-6">
                        <div class="md:col-span-1">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">{{ $doc['name'] }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ $doc['description'] }}</p>
                            @if($doc['required'])
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 mt-2">
                                    Required
                                </span>
                            @endif
                        </div>
                        <div class="mt-5 md:mt-0 md:col-span-2">
                            <!-- Default State: Upload Box -->
                            <div x-show="!hasDocument"
                                class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-indigo-500 transition-colors cursor-pointer"
                                @click="$refs.fileInput.click()" @dragover.prevent="$el.classList.add('border-indigo-500')"
                                @dragleave.prevent="$el.classList.remove('border-indigo-500')"
                                @drop.prevent="handleDrop($event)">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                        viewBox="0 0 48 48" aria-hidden="true">
                                        <path
                                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600 justify-center">
                                        <label
                                            class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                            <span>Upload a file</span>
                                            <input x-ref="fileInput" type="file" class="sr-only" @change="uploadFile($event)">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PDF, PNG, JPG up to 10MB</p>
                                </div>
                            </div>

                            <!-- Uploading State -->
                            <div x-show="isUploading" x-cloak class="mt-2">
                                <div class="relative pt-1">
                                    <div class="flex mb-2 items-center justify-between">
                                        <div>
                                            <span
                                                class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-indigo-600 bg-indigo-200">
                                                Uploading...
                                            </span>
                                        </div>
                                    </div>
                                    <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-indigo-200">
                                        <div
                                            class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-indigo-500 w-full animate-pulse">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Uploaded State -->
                            <div x-show="hasDocument" x-cloak class="rounded-md bg-green-50 p-4 border border-green-200">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <h3 class="text-sm font-medium text-green-800">Document Uploaded</h3>
                                        <div class="mt-2 text-sm text-green-700">
                                            <p x-text="fileName"></p>
                                        </div>
                                    </div>
                                    <div class="ml-auto pl-3">
                                        <div class="-mx-1.5 -my-1.5">
                                            <button type="button" @click="removeFile()"
                                                class="inline-flex bg-green-50 rounded-md p-1.5 text-green-500 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-green-50 focus:ring-green-600">
                                                <span class="sr-only">Remove</span>
                                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @error('documents.' . $key)
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Final Form Submission (just validation) -->
        <form action="{{ route('agency.register.saveStep', $step) }}" method="POST">
            @csrf
            <div class="pt-5 flex justify-between">
                <a href="{{ route('agency.register.previous', $step) }}"
                    class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Back
                </a>
                <button type="submit"
                    class="bg-indigo-600 border border-transparent rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Continue
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script nonce="{{ $cspNonce ?? '' }}">
            document.addEventListener('alpine:init', () => {
                Alpine.data('documentUploader', (docType, uploadUrl, removeUrl) => ({
                    isUploading: false,
                    hasDocument: {{ isset($stepData['uploaded_documents'][$key]) ? 'true' : 'false' }},
                    fileName: '{{ $stepData['uploaded_documents'][$key]['original_name'] ?? '' }}',

                    uploadFile(event) {
                        const file = event.target.files[0];
                        if (!file) return;

                        this.processUpload(file);
                    },

                    handleDrop(event) {
                        this.$el.classList.remove('border-indigo-500');
                        const file = event.dataTransfer.files[0];
                        if (!file) return;

                        this.processUpload(file);
                    },

                    processUpload(file) {
                        this.isUploading = true;
                        const formData = new FormData();
                        formData.append('document', file);
                        formData.append('document_type', docType);
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                        fetch(uploadUrl, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json'
                            }
                        })
                            .then(response => response.json())
                            .then(data => {
                                this.isUploading = false;
                                if (data.success) {
                                    this.hasDocument = true;
                                    this.fileName = data.document.name;
                                } else {
                                    alert(data.message || 'Upload failed');
                                }
                            })
                            .catch(error => {
                                this.isUploading = false;
                                console.error('Error:', error);
                                alert('Upload failed. Please try again.');
                            });
                    },

                    removeFile() {
                        if (!confirm('Are you sure you want to remove this document?')) return;

                        const formData = new FormData();
                        formData.append('document_type', docType);
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                        fetch(removeUrl, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json'
                            }
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    this.hasDocument = false;
                                    this.fileName = '';
                                    // Reset input
                                    if (this.$refs.fileInput) this.$refs.fileInput.value = '';
                                } else {
                                    alert(data.message);
                                }
                            });
                    }
                }));
            });
        </script>
    @endpush
@endsection