@extends('layouts.authenticated')

@section('title', 'Import Workers')
@section('page-title', 'Import Workers')

@section('content')
<div class="p-6">
    <div class="max-w-6xl mx-auto space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Worker Pool Onboarding</h1>
                <p class="text-gray-600 mt-1">Import workers via CSV, invite individually, or sync from external systems.</p>
            </div>
            <a href="{{ route('agency.workers.invitations') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                View Invitations
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <span class="text-gray-500 text-sm">Active Workers</span>
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <p class="text-2xl font-bold text-gray-900 mt-2">{{ $activeWorkers ?? 0 }}</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <span class="text-gray-500 text-sm">Pending Invitations</span>
                    <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <p class="text-2xl font-bold text-gray-900 mt-2">{{ $stats['invitations']['pending'] ?? 0 }}</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <span class="text-gray-500 text-sm">Accepted</span>
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-2xl font-bold text-gray-900 mt-2">{{ $stats['invitations']['accepted'] ?? 0 }}</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <span class="text-gray-500 text-sm">Expired</span>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-2xl font-bold text-gray-900 mt-2">{{ $stats['invitations']['expired'] ?? 0 }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Bulk CSV Import -->
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Bulk CSV Import</h2>
                    <p class="text-gray-600 text-sm mt-1">Upload a CSV file to import multiple workers at once.</p>
                </div>

                <form id="bulk-import-form" action="{{ route('agency.workers.bulk-import') }}" method="POST" enctype="multipart/form-data" class="p-6">
                    @csrf

                    <!-- File Upload -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">CSV File</label>
                        <div id="file-dropzone" class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-brand-500 transition cursor-pointer">
                            <svg class="w-10 h-10 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <p class="text-gray-600 mb-2">Drag and drop your CSV file here, or</p>
                            <label for="csv_file" class="text-brand-600 hover:text-brand-700 cursor-pointer font-medium">browse files</label>
                            <input type="file" id="csv_file" name="csv_file" accept=".csv,.txt" class="hidden" required>
                            <p id="selected-file" class="text-sm text-gray-500 mt-2 hidden"></p>
                        </div>
                        @error('csv_file')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Import Options -->
                    <div class="space-y-4 mb-6">
                        <label class="flex items-center space-x-3">
                            <input type="checkbox" name="send_invitations" value="1" checked class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            <span class="text-sm text-gray-700">Send invitation emails to new workers</span>
                        </label>

                        <label class="flex items-center space-x-3">
                            <input type="checkbox" name="skip_existing" value="1" checked class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            <span class="text-sm text-gray-700">Skip existing workers and pending invitations</span>
                        </label>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Default Commission Rate (%)</label>
                            <input type="number" name="default_commission_rate" step="0.01" min="0" max="100" placeholder="Leave blank to use agency default"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Override if not specified in CSV</p>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-between">
                        <a href="{{ route('agency.workers.download-template') }}" class="text-brand-600 hover:text-brand-700 text-sm font-medium">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download CSV Template
                        </a>
                        <button type="submit" class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition font-medium">
                            Import Workers
                        </button>
                    </div>
                </form>
            </div>

            <!-- Individual Invitation -->
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Invite Individual Worker</h2>
                    <p class="text-gray-600 text-sm mt-1">Send a personal invitation to a single worker.</p>
                </div>

                <form id="individual-invite-form" action="{{ route('agency.workers.invite') }}" method="POST" class="p-6">
                    @csrf

                    <div class="space-y-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                            <input type="email" id="email" name="email" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                                   placeholder="worker@example.com">
                            @error('email')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                                <input type="text" id="name" name="name"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                                       placeholder="John Smith">
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                                <input type="tel" id="phone" name="phone"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                                       placeholder="+1234567890">
                            </div>
                        </div>

                        <div>
                            <label for="commission_rate" class="block text-sm font-medium text-gray-700 mb-2">Commission Rate (%)</label>
                            <input type="number" id="commission_rate" name="commission_rate" step="0.01" min="0" max="100"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                                   placeholder="Leave blank for agency default">
                        </div>

                        <div>
                            <label for="personal_message" class="block text-sm font-medium text-gray-700 mb-2">Personal Message</label>
                            <textarea id="personal_message" name="personal_message" rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent resize-none"
                                      placeholder="Add a personal message to the invitation..."></textarea>
                        </div>

                        <label class="flex items-center space-x-3">
                            <input type="checkbox" name="send_email" value="1" checked class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            <span class="text-sm text-gray-700">Send invitation email immediately</span>
                        </label>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition font-medium">
                            Send Invitation
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Shareable Link Generator -->
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Generate Shareable Link</h2>
                <p class="text-gray-600 text-sm mt-1">Create a link that can be shared with anyone to join your agency.</p>
            </div>

            <div class="p-6">
                <form id="shareable-link-form" action="{{ route('agency.workers.generate-link') }}" method="POST" class="flex items-end gap-4">
                    @csrf
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Commission Rate (%) - Optional</label>
                        <input type="number" name="commission_rate" step="0.01" min="0" max="100"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                               placeholder="Default rate for workers using this link">
                    </div>
                    <button type="submit" class="px-6 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition font-medium">
                        Generate Link
                    </button>
                </form>

                @if(session('success') && str_contains(session('success'), 'Shareable link'))
                <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <p class="text-green-800 text-sm">{{ session('success') }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- CSV Format Reference -->
        <div class="bg-gray-50 rounded-xl border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">CSV Format Reference</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="text-left py-2 pr-4 font-medium text-gray-700">Column</th>
                            <th class="text-left py-2 pr-4 font-medium text-gray-700">Required</th>
                            <th class="text-left py-2 font-medium text-gray-700">Description</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600">
                        <tr class="border-b border-gray-200">
                            <td class="py-2 pr-4 font-mono text-xs">email</td>
                            <td class="py-2 pr-4"><span class="text-green-600">Yes</span></td>
                            <td class="py-2">Worker's email address</td>
                        </tr>
                        <tr class="border-b border-gray-200">
                            <td class="py-2 pr-4 font-mono text-xs">name</td>
                            <td class="py-2 pr-4"><span class="text-gray-400">No</span></td>
                            <td class="py-2">Worker's full name</td>
                        </tr>
                        <tr class="border-b border-gray-200">
                            <td class="py-2 pr-4 font-mono text-xs">phone</td>
                            <td class="py-2 pr-4"><span class="text-gray-400">No</span></td>
                            <td class="py-2">Phone number (with country code)</td>
                        </tr>
                        <tr class="border-b border-gray-200">
                            <td class="py-2 pr-4 font-mono text-xs">commission_rate</td>
                            <td class="py-2 pr-4"><span class="text-gray-400">No</span></td>
                            <td class="py-2">Commission percentage (0-100)</td>
                        </tr>
                        <tr class="border-b border-gray-200">
                            <td class="py-2 pr-4 font-mono text-xs">skills</td>
                            <td class="py-2 pr-4"><span class="text-gray-400">No</span></td>
                            <td class="py-2">Comma-separated skill names</td>
                        </tr>
                        <tr class="border-b border-gray-200">
                            <td class="py-2 pr-4 font-mono text-xs">certifications</td>
                            <td class="py-2 pr-4"><span class="text-gray-400">No</span></td>
                            <td class="py-2">Comma-separated certification names</td>
                        </tr>
                        <tr class="border-b border-gray-200">
                            <td class="py-2 pr-4 font-mono text-xs">notes</td>
                            <td class="py-2 pr-4"><span class="text-gray-400">No</span></td>
                            <td class="py-2">Internal notes about the worker</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 font-mono text-xs">personal_message</td>
                            <td class="py-2 pr-4"><span class="text-gray-400">No</span></td>
                            <td class="py-2">Custom message in invitation email</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropzone = document.getElementById('file-dropzone');
    const fileInput = document.getElementById('csv_file');
    const selectedFile = document.getElementById('selected-file');

    // Click to browse
    dropzone.addEventListener('click', () => fileInput.click());

    // Drag and drop
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('border-brand-500', 'bg-brand-50');
    });

    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('border-brand-500', 'bg-brand-50');
    });

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('border-brand-500', 'bg-brand-50');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            updateSelectedFile(files[0]);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            updateSelectedFile(e.target.files[0]);
        }
    });

    function updateSelectedFile(file) {
        selectedFile.textContent = `Selected: ${file.name} (${formatBytes(file.size)})`;
        selectedFile.classList.remove('hidden');
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});
</script>
@endpush
