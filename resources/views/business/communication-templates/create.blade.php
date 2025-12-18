@extends('layouts.dashboard')

@section('title', 'Create Communication Template')
@section('page-title', 'Create Template')
@section('page-subtitle', 'Create a new message template for worker communications')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="{{ route('business.communication-templates.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Templates
        </a>
    </div>

    <form action="{{ route('business.communication-templates.store') }}" method="POST" x-data="templateForm()" @submit="handleSubmit">
        @csrf

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Template Details</h2>
                <p class="mt-1 text-sm text-gray-500">Define the basic information for your template</p>
            </div>

            <div class="p-6 space-y-6">
                <!-- Template Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Template Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500"
                           placeholder="e.g., Welcome to Our Team">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Type and Channel -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700">Template Type</label>
                        <select name="type" id="type" required x-model="selectedType" @change="updateVariables()"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500">
                            @foreach($types as $value => $label)
                                <option value="{{ $value }}" {{ old('type', $selectedType) == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="channel" class="block text-sm font-medium text-gray-700">Communication Channel</label>
                        <select name="channel" id="channel" required
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500">
                            @foreach($channels as $value => $label)
                                <option value="{{ $value }}" {{ old('channel', 'all') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('channel')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Subject Line -->
                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700">
                        Subject Line <span class="text-gray-400">(for email)</span>
                    </label>
                    <input type="text" name="subject" id="subject" value="{{ old('subject') }}"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500"
                           placeholder="e.g., Welcome to {{business_name}}!">
                    <p class="mt-1 text-xs text-gray-500">You can use variables like @{{worker_name}} in the subject</p>
                    @error('subject')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Message Body -->
                <div>
                    <label for="body" class="block text-sm font-medium text-gray-700">Message Body</label>
                    <textarea name="body" id="body" rows="10" required x-model="body"
                              class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500 font-mono text-sm"
                              placeholder="Write your message here...">{{ old('body') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">Use Markdown formatting for rich text. Variables will be replaced with actual values when sent.</p>
                    @error('body')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status Options -->
                <div class="flex items-center space-x-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" checked
                               class="rounded border-gray-300 text-gray-900 shadow-sm focus:border-gray-500 focus:ring-gray-500">
                        <span class="ml-2 text-sm text-gray-700">Active</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_default" value="1"
                               class="rounded border-gray-300 text-gray-900 shadow-sm focus:border-gray-500 focus:ring-gray-500">
                        <span class="ml-2 text-sm text-gray-700">Set as default for this type</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Available Variables -->
        <div class="mt-6 bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Available Variables</h2>
                <p class="mt-1 text-sm text-gray-500">Click a variable to insert it into your message</p>
            </div>
            <div class="p-6">
                @foreach($allVariables as $category => $vars)
                    <div class="mb-4">
                        <h3 class="text-sm font-medium text-gray-700 capitalize mb-2">{{ $category }}</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($vars as $varName => $description)
                                <button type="button" @click="insertVariable('{{ $varName }}')"
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors"
                                        title="{{ $description }}">
                                    @{{ {{ $varName }} }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Preview -->
        <div class="mt-6 bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Preview</h2>
                    <p class="mt-1 text-sm text-gray-500">See how your message will look with sample data</p>
                </div>
                <button type="button" @click="updatePreview()" class="text-sm text-blue-600 hover:text-blue-800">
                    Refresh Preview
                </button>
            </div>
            <div class="p-6 bg-gray-50">
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div x-show="previewSubject" class="mb-4">
                        <span class="text-xs text-gray-500 uppercase tracking-wide">Subject:</span>
                        <p class="text-gray-900 font-medium" x-text="previewSubject"></p>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 uppercase tracking-wide">Message:</span>
                        <div class="mt-2 prose prose-sm max-w-none text-gray-700" x-html="previewBody"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="mt-6 flex items-center justify-end gap-4">
            <a href="{{ route('business.communication-templates.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 font-medium">
                Create Template
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function templateForm() {
    return {
        selectedType: '{{ old('type', $selectedType) }}',
        body: `{{ old('body', '') }}`,
        previewSubject: '',
        previewBody: '',

        init() {
            this.updatePreview();
        },

        insertVariable(varName) {
            const textarea = document.getElementById('body');
            const cursorPos = textarea.selectionStart;
            const textBefore = this.body.substring(0, cursorPos);
            const textAfter = this.body.substring(cursorPos);

            this.body = textBefore + '{{' + varName + '}}' + textAfter;

            // Reset cursor position
            this.$nextTick(() => {
                textarea.focus();
                textarea.setSelectionRange(cursorPos + varName.length + 4, cursorPos + varName.length + 4);
            });

            this.updatePreview();
        },

        updateVariables() {
            fetch(`{{ route('business.communication-templates.variables') }}?type=${this.selectedType}`)
                .then(response => response.json())
                .then(data => {
                    // Variables are updated dynamically if needed
                });
        },

        updatePreview() {
            const subject = document.getElementById('subject').value;

            fetch('{{ route('business.communication-templates.render-preview') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    subject: subject,
                    body: this.body,
                    type: this.selectedType
                })
            })
            .then(response => response.json())
            .then(data => {
                this.previewSubject = data.subject || '';
                this.previewBody = this.markdownToHtml(data.body || '');
            });
        },

        markdownToHtml(text) {
            // Simple markdown conversion
            return text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/\n/g, '<br>')
                .replace(/- (.*?)(?=\n|$)/g, '<li>$1</li>');
        },

        handleSubmit(e) {
            // Allow form submission
            return true;
        }
    }
}
</script>
@endpush
@endsection
