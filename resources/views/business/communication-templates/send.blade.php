@extends('layouts.dashboard')

@section('title', 'Send Template')
@section('page-title', 'Send Message')
@section('page-subtitle', 'Send "{{ $template->name }}" to workers')

@section('content')
<div class="max-w-5xl mx-auto">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="{{ route('business.communication-templates.show', $template) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Template
        </a>
    </div>

    <form action="{{ route('business.communication-templates.send', $template) }}" method="POST" x-data="sendForm()">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Recipients Selection -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Select Recipients</h2>
                        <p class="mt-1 text-sm text-gray-500">Choose workers to receive this message</p>
                    </div>
                    <div class="p-6">
                        @if($workers->count() > 0)
                            <!-- Search and Select All -->
                            <div class="flex items-center justify-between mb-4">
                                <div class="relative flex-1 max-w-xs">
                                    <input type="text" x-model="searchQuery" placeholder="Search workers..."
                                           class="w-full pl-10 pr-4 py-2 rounded-lg border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500">
                                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                <div class="flex items-center gap-4">
                                    <button type="button" @click="selectAll()" class="text-sm text-blue-600 hover:text-blue-800">
                                        Select All
                                    </button>
                                    <button type="button" @click="deselectAll()" class="text-sm text-gray-500 hover:text-gray-700">
                                        Clear
                                    </button>
                                </div>
                            </div>

                            <!-- Workers List -->
                            <div class="border border-gray-200 rounded-lg divide-y divide-gray-200 max-h-96 overflow-y-auto">
                                @foreach($workers as $worker)
                                    <label class="flex items-center p-4 hover:bg-gray-50 cursor-pointer"
                                           x-show="'{{ strtolower($worker->name) }}'.includes(searchQuery.toLowerCase()) || searchQuery === ''">
                                        <input type="checkbox" name="recipient_ids[]" value="{{ $worker->id }}"
                                               x-model="selectedRecipients"
                                               class="rounded border-gray-300 text-gray-900 shadow-sm focus:border-gray-500 focus:ring-gray-500">
                                        <div class="ml-4 flex-1">
                                            <p class="text-sm font-medium text-gray-900">{{ $worker->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $worker->email }}</p>
                                        </div>
                                        @if($worker->workerProfile)
                                            <div class="text-right">
                                                <p class="text-xs text-gray-500">{{ $worker->workerProfile->city ?? 'No location' }}</p>
                                            </div>
                                        @endif
                                    </label>
                                @endforeach
                            </div>

                            <p class="mt-2 text-sm text-gray-500">
                                <span x-text="selectedRecipients.length"></span> worker(s) selected
                            </p>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No workers available</h3>
                                <p class="mt-1 text-sm text-gray-500">Workers who have worked with you will appear here.</p>
                            </div>
                        @endif

                        @error('recipient_ids')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Shift Context (Optional) -->
                @if($shifts->count() > 0)
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Related Shift (Optional)</h2>
                            <p class="mt-1 text-sm text-gray-500">Select a shift to include its details in the message</p>
                        </div>
                        <div class="p-6">
                            <select name="shift_id" x-model="selectedShift" @change="updatePreview()"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-gray-500 focus:ring-gray-500">
                                <option value="">No shift selected</option>
                                @foreach($shifts as $shift)
                                    <option value="{{ $shift->id }}">
                                        {{ $shift->title }} - {{ $shift->shift_date?->format('M j, Y') }} @ {{ $shift->start_time?->format('g:i A') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Template Preview -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Message Preview</h3>
                    </div>
                    <div class="p-6 bg-gray-50">
                        <div class="bg-white rounded-lg border border-gray-200 p-4 text-sm">
                            @if($preview['subject'])
                                <div class="mb-3 pb-3 border-b border-gray-200">
                                    <span class="text-xs text-gray-500 uppercase">Subject:</span>
                                    <p class="text-gray-900 font-medium">{{ $preview['subject'] }}</p>
                                </div>
                            @endif
                            <div class="prose prose-sm max-w-none text-gray-700">
                                {!! nl2br(e(Str::limit($preview['body'], 300))) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Template Info -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Template Info</h3>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Type</dt>
                            <dd class="text-gray-900">{{ $template->type_label }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Channel</dt>
                            <dd class="text-gray-900">{{ $template->channel_label }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Times Used</dt>
                            <dd class="text-gray-900">{{ $template->usage_count }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Send Button -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <button type="submit"
                            :disabled="selectedRecipients.length === 0"
                            class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Send to <span x-text="selectedRecipients.length" class="mx-1"></span> Worker(s)
                    </button>
                    <p class="mt-3 text-xs text-gray-500 text-center">
                        Messages will be sent via {{ strtolower($template->channel_label) }}
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function sendForm() {
    return {
        searchQuery: '',
        selectedRecipients: [],
        selectedShift: '',

        selectAll() {
            const checkboxes = document.querySelectorAll('input[name="recipient_ids[]"]');
            this.selectedRecipients = [];
            checkboxes.forEach(cb => {
                const label = cb.closest('label');
                if (label.style.display !== 'none') {
                    this.selectedRecipients.push(cb.value);
                }
            });
        },

        deselectAll() {
            this.selectedRecipients = [];
        },

        updatePreview() {
            // Could refresh preview with shift data via AJAX if needed
        }
    }
}
</script>
@endpush
@endsection
