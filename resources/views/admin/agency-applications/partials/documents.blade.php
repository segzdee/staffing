{{-- Document Verification UI - AGY-REG-003 --}}
<div class="bg-white rounded-lg border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-medium text-gray-900">Documents</h3>
            <p class="mt-1 text-sm text-gray-500">{{ $application->documents->count() }} {{ Str::plural('document', $application->documents->count()) }} uploaded</p>
        </div>
        <div class="flex items-center gap-2">
            <!-- Document Progress -->
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500">{{ $application->getDocumentCompletionPercentage() }}% verified</span>
                <div class="w-24 bg-gray-200 rounded-full h-2">
                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ $application->getDocumentCompletionPercentage() }}%"></div>
                </div>
            </div>
        </div>
    </div>

    @if($application->documents->count() > 0)
        <form method="POST" action="{{ route('admin.agency-applications.review-documents', $application->id) }}" id="documentsForm">
            @csrf
            <div class="divide-y divide-gray-200">
                @foreach($application->documents as $index => $document)
                    <div class="p-6 {{ $document->status === 'rejected' ? 'bg-red-50' : ($document->status === 'verified' ? 'bg-green-50' : '') }}">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-4 flex-1">
                                <!-- Document Icon -->
                                <div class="flex-shrink-0">
                                    @php
                                        $extension = pathinfo($document->file_name ?? '', PATHINFO_EXTENSION);
                                        $iconClass = match(strtolower($extension)) {
                                            'pdf' => 'text-red-500',
                                            'doc', 'docx' => 'text-blue-500',
                                            'jpg', 'jpeg', 'png', 'gif' => 'text-green-500',
                                            default => 'text-gray-500'
                                        };
                                    @endphp
                                    <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center">
                                        <svg class="w-6 h-6 {{ $iconClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Document Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-sm font-medium text-gray-900">
                                            {{ $document->getDocumentTypeLabel() }}
                                        </h4>
                                        @php
                                            $statusBadges = [
                                                'pending' => 'bg-yellow-100 text-yellow-700',
                                                'verified' => 'bg-green-100 text-green-700',
                                                'rejected' => 'bg-red-100 text-red-700',
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusBadges[$document->status] ?? 'bg-gray-100 text-gray-700' }}">
                                            {{ ucfirst($document->status) }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ $document->file_name ?? 'Document' }}
                                        @if($document->file_size)
                                            <span class="mx-1">|</span>
                                            {{ number_format($document->file_size / 1024, 1) }} KB
                                        @endif
                                    </p>
                                    @if($document->uploaded_at)
                                        <p class="mt-1 text-xs text-gray-400">
                                            Uploaded {{ $document->uploaded_at->diffForHumans() }}
                                        </p>
                                    @endif

                                    @if($document->reviewer_notes)
                                        <div class="mt-2 p-2 bg-gray-100 rounded text-sm text-gray-700">
                                            <span class="font-medium">Reviewer notes:</span> {{ $document->reviewer_notes }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Document Actions -->
                            <div class="flex-shrink-0 flex items-start gap-2">
                                <!-- View Document -->
                                @if($document->file_url)
                                    <a href="{{ $document->file_url }}" target="_blank"
                                       class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View
                                    </a>
                                @endif

                                @if($document->file_url)
                                    <a href="{{ $document->file_url }}" download
                                       class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Download
                                    </a>
                                @endif
                            </div>
                        </div>

                        <!-- Review Controls -->
                        @if(!$application->isTerminal())
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <input type="hidden" name="documents[{{ $index }}][id]" value="{{ $document->id }}">

                                <div class="flex flex-wrap items-end gap-4">
                                    <!-- Status Select -->
                                    <div class="flex-1 min-w-[200px]">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <select name="documents[{{ $index }}][status]"
                                                class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm document-status-select"
                                                data-index="{{ $index }}">
                                            <option value="pending" {{ $document->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="verified" {{ $document->status === 'verified' ? 'selected' : '' }}>Verified</option>
                                            <option value="rejected" {{ $document->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                        </select>
                                    </div>

                                    <!-- Notes Input (shown when rejected) -->
                                    <div class="flex-1 min-w-[300px] document-notes-container" id="notes-container-{{ $index }}" style="{{ $document->status === 'rejected' ? '' : 'display: none;' }}">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Notes</label>
                                        <input type="text" name="documents[{{ $index }}][notes]"
                                               value="{{ $document->reviewer_notes }}"
                                               class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                                               placeholder="Reason for rejection...">
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            @if(!$application->isTerminal())
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Document Review
                    </button>
                </div>
            @endif
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Show/hide notes field based on status
                document.querySelectorAll('.document-status-select').forEach(function(select) {
                    select.addEventListener('change', function() {
                        const index = this.dataset.index;
                        const notesContainer = document.getElementById('notes-container-' + index);
                        if (this.value === 'rejected') {
                            notesContainer.style.display = '';
                        } else {
                            notesContainer.style.display = 'none';
                        }
                    });
                });
            });
        </script>
    @else
        <div class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No documents uploaded</h3>
            <p class="mt-1 text-sm text-gray-500">The applicant has not yet uploaded any documents.</p>
        </div>
    @endif
</div>
