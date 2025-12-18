@extends('layouts.dashboard')

@section('title', 'Pending Certifications')
@section('page-title', 'Pending Verifications')
@section('page-subtitle', 'Review and verify worker certifications')

@section('content')

    <!-- Breadcrumb -->
    <nav class="mb-4 text-sm">
        <ol class="flex items-center space-x-2">
            <li><a href="{{ route('admin.certifications.index') }}" class="text-gray-500 hover:text-gray-700">Certifications</a></li>
            <li class="text-gray-400">/</li>
            <li class="text-gray-900 font-medium">Pending Verifications</li>
        </ol>
    </nav>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-amber-800">{{ $certifications->total() }}</div>
                    <div class="text-sm text-amber-600">Total Pending</div>
                </div>
            </div>
        </div>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-blue-800">{{ $certifications->where('document_url', '!=', null)->count() }}</div>
                    <div class="text-sm text-blue-600">With Documents</div>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-800">{{ $certifications->filter(fn($c) => $c->created_at->diffInDays(now()) > 3)->count() }}</div>
                    <div class="text-sm text-gray-600">Older than 3 days</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Certifications List -->
    <x-dashboard.widget-card title="Certifications Awaiting Review">
        @if($certifications->isEmpty())
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">All caught up!</h3>
                <p class="mt-1 text-sm text-gray-500">No certifications pending verification.</p>
                <div class="mt-6">
                    <a href="{{ route('admin.certifications.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                        Back to Dashboard
                    </a>
                </div>
            </div>
        @else
            <div class="space-y-4">
                @foreach($certifications as $cert)
                    <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition" id="cert-{{ $cert->id }}">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-4">
                                <!-- Worker Avatar -->
                                <div class="flex-shrink-0">
                                    <div class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center">
                                        @if($cert->worker->avatar)
                                            <img src="{{ $cert->worker->avatar }}" alt="{{ $cert->worker->name }}" class="h-12 w-12 rounded-full object-cover">
                                        @else
                                            <span class="text-gray-500 font-medium text-lg">{{ substr($cert->worker->first_name ?? 'W', 0, 1) }}</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Certification Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-2">
                                        <h4 class="text-sm font-medium text-gray-900 truncate">{{ $cert->worker->name ?? $cert->worker->email }}</h4>
                                        <span class="text-xs text-gray-500">submitted {{ $cert->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-sm text-gray-700 mt-1">
                                        <strong>{{ $cert->safetyCertification->name ?? $cert->certificationType->name ?? 'Unknown Certification' }}</strong>
                                    </p>
                                    <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                        @if($cert->certification_number)
                                            <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded">Cert #: {{ $cert->certification_number }}</span>
                                        @endif
                                        @if($cert->issue_date)
                                            <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded">Issued: {{ $cert->issue_date->format('M j, Y') }}</span>
                                        @endif
                                        @if($cert->expiry_date)
                                            <span class="bg-{{ $cert->expiry_date->isPast() ? 'red' : ($cert->expiry_date->diffInDays(now()) < 30 ? 'amber' : 'green') }}-100 text-{{ $cert->expiry_date->isPast() ? 'red' : ($cert->expiry_date->diffInDays(now()) < 30 ? 'amber' : 'green') }}-700 px-2 py-1 rounded">
                                                Expires: {{ $cert->expiry_date->format('M j, Y') }}
                                            </span>
                                        @endif
                                        @if($cert->issuing_authority)
                                            <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded">{{ $cert->issuing_authority }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Document Preview & Actions -->
                            <div class="flex-shrink-0 flex items-center space-x-3">
                                @if($cert->document_url || $cert->currentDocument)
                                    <a href="{{ $cert->document_url ?? $cert->currentDocument->url ?? '#' }}" target="_blank" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        View Document
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400">No document</span>
                                @endif
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                            <div class="text-xs text-gray-500">
                                Worker ID: {{ $cert->worker_id }} | Cert ID: {{ $cert->id }}
                            </div>
                            <div class="flex items-center space-x-2">
                                <button type="button" onclick="rejectCertification({{ $cert->id }})" class="inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Reject
                                </button>
                                <button type="button" onclick="verifyCertification({{ $cert->id }})" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Verify
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $certifications->links() }}
            </div>
        @endif
    </x-dashboard.widget-card>

    <!-- Rejection Modal -->
    <div id="rejection-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Reject Certification</h3>
                <form id="rejection-form">
                    <input type="hidden" id="rejection-cert-id" name="certification_id">
                    <div class="mb-4">
                        <label for="rejection-reason" class="block text-sm font-medium text-gray-700 mb-1">Reason for Rejection</label>
                        <textarea id="rejection-reason" name="reason" rows="4" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" placeholder="Please provide a clear reason so the worker understands what they need to correct..." required></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeRejectionModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                            Reject Certification
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function verifyCertification(id) {
        if (confirm('Are you sure you want to verify this certification?')) {
            fetch('/api/admin/certifications/' + id + '/verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('cert-' + id).remove();
                    showNotification('Certification verified successfully', 'success');
                } else {
                    showNotification(data.message || 'Failed to verify certification', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
            });
        }
    }

    function rejectCertification(id) {
        document.getElementById('rejection-cert-id').value = id;
        document.getElementById('rejection-modal').classList.remove('hidden');
    }

    function closeRejectionModal() {
        document.getElementById('rejection-modal').classList.add('hidden');
        document.getElementById('rejection-form').reset();
    }

    document.getElementById('rejection-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('rejection-cert-id').value;
        const reason = document.getElementById('rejection-reason').value;

        fetch('/api/admin/certifications/' + id + '/reject', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ reason: reason })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeRejectionModal();
                document.getElementById('cert-' + id).remove();
                showNotification('Certification rejected', 'success');
            } else {
                showNotification(data.message || 'Failed to reject certification', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred', 'error');
        });
    });

    function showNotification(message, type) {
        // Simple notification - replace with your notification system
        alert(message);
    }
</script>
@endpush
