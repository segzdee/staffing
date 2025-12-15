@extends('layouts.dashboard')

@section('title', 'Upload Portfolio Item')
@section('page-title', 'Add Portfolio Item')
@section('page-subtitle', 'Upload photos, videos, documents, or certifications')

@section('content')
<div class="max-w-2xl">
    <form action="{{ route('worker.portfolio.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6" x-data="uploadForm()">
        @csrf

        <!-- File Type Selection -->
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <label class="block text-sm font-medium text-gray-700 mb-4">Item Type</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach($types as $type)
                    <label class="relative cursor-pointer">
                        <input type="radio" name="type" value="{{ $type }}" x-model="selectedType" class="sr-only peer" {{ $loop->first ? 'checked' : '' }}>
                        <div class="flex flex-col items-center p-4 border-2 border-gray-200 rounded-lg peer-checked:border-gray-900 peer-checked:bg-gray-50 hover:bg-gray-50 transition-colors">
                            @if($type === 'photo')
                                <svg class="w-8 h-8 text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            @elseif($type === 'video')
                                <svg class="w-8 h-8 text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            @elseif($type === 'document')
                                <svg class="w-8 h-8 text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            @else
                                <svg class="w-8 h-8 text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                            @endif
                            <span class="text-sm font-medium text-gray-900 capitalize">{{ $type }}</span>
                        </div>
                    </label>
                @endforeach
            </div>
            @error('type')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- File Upload -->
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <label class="block text-sm font-medium text-gray-700 mb-4">Upload File</label>

            <div class="relative">
                <input type="file" name="file" id="file" @change="handleFileSelect" accept="" x-bind:accept="allowedExtensions" class="sr-only">
                <label for="file" class="flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors">
                    <template x-if="!previewUrl">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <svg class="w-10 h-10 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <p class="mb-2 text-sm text-gray-600">
                                <span class="font-semibold">Click to upload</span> or drag and drop
                            </p>
                            <p class="text-xs text-gray-500" x-text="fileHint"></p>
                        </div>
                    </template>
                    <template x-if="previewUrl">
                        <div class="relative w-full h-full">
                            <template x-if="selectedType === 'photo' || selectedType === 'certification'">
                                <img :src="previewUrl" class="w-full h-full object-contain rounded-lg">
                            </template>
                            <template x-if="selectedType === 'video'">
                                <video :src="previewUrl" class="w-full h-full object-contain rounded-lg" controls></video>
                            </template>
                            <template x-if="selectedType === 'document'">
                                <div class="flex flex-col items-center justify-center h-full">
                                    <svg class="w-16 h-16 text-red-500 mb-2" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                    </svg>
                                    <span class="text-sm text-gray-600" x-text="fileName"></span>
                                </div>
                            </template>
                            <button type="button" @click.prevent="clearFile" class="absolute top-2 right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </label>
            </div>

            <p class="mt-2 text-xs text-gray-500">
                Max file size:
                <span x-show="selectedType === 'photo' || selectedType === 'certification'">{{ $maxImageSize }}MB</span>
                <span x-show="selectedType === 'video'">{{ $maxVideoSize }}MB</span>
                <span x-show="selectedType === 'document'">{{ $maxDocumentSize }}MB</span>
            </p>

            @error('file')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Title & Description -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 space-y-4">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" required maxlength="100" placeholder="e.g., Food Safety Certificate, Event Setup Work" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                @error('title')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description (optional)</label>
                <textarea name="description" id="description" rows="3" maxlength="500" placeholder="Add context about this item..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <a href="{{ route('worker.portfolio.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-lg transition-colors">
                Upload Item
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function uploadForm() {
    return {
        selectedType: 'photo',
        previewUrl: null,
        fileName: null,

        get allowedExtensions() {
            const extensions = {
                'photo': '.jpg,.jpeg,.png,.webp',
                'video': '.mp4,.mov,.webm',
                'document': '.pdf',
                'certification': '.pdf,.jpg,.jpeg,.png'
            };
            return extensions[this.selectedType] || '';
        },

        get fileHint() {
            const hints = {
                'photo': 'JPG, PNG, WebP (max {{ $maxImageSize }}MB)',
                'video': 'MP4, MOV, WebM (max {{ $maxVideoSize }}MB)',
                'document': 'PDF only (max {{ $maxDocumentSize }}MB)',
                'certification': 'PDF, JPG, PNG (max {{ $maxImageSize }}MB)'
            };
            return hints[this.selectedType] || '';
        },

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.fileName = file.name;

            // Preview for images
            if (file.type.startsWith('image/')) {
                this.previewUrl = URL.createObjectURL(file);
            } else if (file.type.startsWith('video/')) {
                this.previewUrl = URL.createObjectURL(file);
            } else {
                this.previewUrl = '#';
            }
        },

        clearFile() {
            const input = document.getElementById('file');
            input.value = '';
            this.previewUrl = null;
            this.fileName = null;
        }
    };
}
</script>
@endpush
