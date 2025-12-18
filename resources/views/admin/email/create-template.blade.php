@extends('layouts.dashboard')

@section('title', 'Create Email Template')
@section('page-title', 'Create Email Template')
@section('page-subtitle', 'Design a new email template')

@section('content')
<div class="max-w-4xl">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.email.templates') }}" class="text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <h1 class="text-xl font-semibold text-gray-900">Create Email Template</h1>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.email.templates.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-6">
            <h2 class="text-lg font-semibold text-gray-900">Template Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Template Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="e.g., Welcome Email">
                </div>

                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                    <input type="text" name="slug" id="slug" value="{{ old('slug') }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                        placeholder="e.g., welcome_email" pattern="[a-z0-9_]+">
                    <p class="mt-1 text-xs text-gray-500">Lowercase letters, numbers, and underscores only</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category" id="category" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @foreach($categories as $value => $label)
                        <option value="{{ $value }}" {{ old('category') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Template is active</span>
                    </label>
                </div>
            </div>

            <div>
                <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Subject Line</label>
                <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="e.g., Welcome to OvertimeStaff, {{ '{{' }} user_name {{ '}}' }}!">
                <p class="mt-1 text-xs text-gray-500">Use {{ '{{ variable_name }}' }} for merge tags</p>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-6">
            <h2 class="text-lg font-semibold text-gray-900">Email Content</h2>

            <div>
                <label for="body_html" class="block text-sm font-medium text-gray-700 mb-1">HTML Body</label>
                <textarea name="body_html" id="body_html" rows="15" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                    placeholder="<h2>Hello {{ '{{' }} user_name {{ '}}' }}!</h2>">{{ old('body_html') }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Use HTML with {{ '{{ variable_name }}' }} for merge tags. The content will be wrapped in a styled email layout.</p>
            </div>

            <div>
                <label for="body_text" class="block text-sm font-medium text-gray-700 mb-1">Plain Text Body (Optional)</label>
                <textarea name="body_text" id="body_text" rows="8"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                    placeholder="Hello {{ '{{' }} user_name {{ '}}' }}!">{{ old('body_text') }}</textarea>
                <p class="mt-1 text-xs text-gray-500">If empty, a plain text version will be generated from the HTML</p>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-6">
            <h2 class="text-lg font-semibold text-gray-900">Available Variables</h2>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach($defaultVariables as $variable)
                <div class="bg-gray-50 px-3 py-2 rounded text-sm font-mono text-gray-700">
                    {{ '{{' }} {{ $variable }} {{ '}}' }}
                </div>
                @endforeach
            </div>

            <div>
                <label for="variables" class="block text-sm font-medium text-gray-700 mb-1">Custom Variables</label>
                <input type="text" name="variables_input" id="variables_input"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="e.g., shift_title, shift_date, business_name">
                <p class="mt-1 text-xs text-gray-500">Comma-separated list of additional variables this template uses</p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('admin.email.templates') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                Create Template
            </button>
        </div>
    </form>
</div>

<script>
    // Auto-generate slug from name
    document.getElementById('name').addEventListener('input', function() {
        const slug = this.value
            .toLowerCase()
            .replace(/[^a-z0-9\s]/g, '')
            .replace(/\s+/g, '_')
            .substring(0, 50);
        document.getElementById('slug').value = slug;
    });

    // Convert comma-separated variables to array before submit
    document.querySelector('form').addEventListener('submit', function() {
        const variablesInput = document.getElementById('variables_input');
        const variables = variablesInput.value
            .split(',')
            .map(v => v.trim())
            .filter(v => v.length > 0);

        // Create hidden inputs for variables array
        variables.forEach(v => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'variables[]';
            input.value = v;
            this.appendChild(input);
        });
    });
</script>
@endsection
