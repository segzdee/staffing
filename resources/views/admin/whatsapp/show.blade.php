@extends('layouts.admin-dashboard')

@section('title', 'WhatsApp Template: ' . $template->name)

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $template->name }}</h1>
                    <p class="text-muted mb-0">Template ID: {{ $template->template_id }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.whatsapp.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                    <a href="{{ route('admin.whatsapp.edit', $template) }}" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Edit
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Template Details -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Template Details</h5>
                    <div>
                        @if($template->status === 'approved')
                            <span class="badge bg-success">Approved</span>
                        @elseif($template->status === 'pending')
                            <span class="badge bg-warning">Pending</span>
                        @else
                            <span class="badge bg-danger">Rejected</span>
                        @endif
                        @if($template->is_active)
                            <span class="badge bg-primary ms-1">Active</span>
                        @else
                            <span class="badge bg-secondary ms-1">Inactive</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small">Language</label>
                            <div>{{ $template->language }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small">Category</label>
                            <div>
                                <span class="badge bg-{{ $template->category === 'utility' ? 'info' : ($template->category === 'marketing' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($template->category) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    @if($template->header)
                    <div class="mb-3">
                        <label class="form-label text-muted small">Header</label>
                        <div class="p-2 bg-light rounded">
                            <strong>Type:</strong> {{ ucfirst($template->header['type'] ?? 'None') }}<br>
                            @if(isset($template->header['content']))
                                <strong>Content:</strong> {{ $template->header['content'] }}
                            @endif
                        </div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label text-muted small">Message Body</label>
                        <div class="p-3 bg-light rounded">
                            <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;">{{ $template->content }}</pre>
                        </div>
                        <small class="text-muted">
                            Placeholders: {{ $template->getPlaceholderCount() }} variable(s)
                        </small>
                    </div>

                    @if($template->footer)
                    <div class="mb-3">
                        <label class="form-label text-muted small">Footer</label>
                        <div class="text-muted">{{ $template->footer['text'] ?? '-' }}</div>
                    </div>
                    @endif

                    @if($template->buttons)
                    <div class="mb-3">
                        <label class="form-label text-muted small">Buttons</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($template->buttons as $button)
                                <span class="badge bg-outline-primary border">
                                    {{ $button['text'] ?? 'Button' }}
                                    <small class="text-muted">({{ $button['type'] ?? 'unknown' }})</small>
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($template->rejection_reason)
                    <div class="alert alert-danger">
                        <strong>Rejection Reason:</strong> {{ $template->rejection_reason }}
                    </div>
                    @endif

                    <hr>

                    <div class="row text-muted small">
                        <div class="col-md-4">
                            <strong>Created:</strong><br>
                            {{ $template->created_at->format('M d, Y H:i') }}
                        </div>
                        <div class="col-md-4">
                            <strong>Last Updated:</strong><br>
                            {{ $template->updated_at->format('M d, Y H:i') }}
                        </div>
                        <div class="col-md-4">
                            <strong>Approved At:</strong><br>
                            {{ $template->approved_at?->format('M d, Y H:i') ?? 'Not approved' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usage Statistics -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Usage Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center mb-3">
                            <div class="h3 mb-0">{{ number_format($usageStats->total_sent ?? 0) }}</div>
                            <small class="text-muted">Total Sent</small>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="h3 mb-0 text-success">{{ number_format($usageStats->delivered ?? 0) }}</div>
                            <small class="text-muted">Delivered</small>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="h3 mb-0 text-info">{{ number_format($usageStats->read_count ?? 0) }}</div>
                            <small class="text-muted">Read</small>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="h3 mb-0 text-danger">{{ number_format($usageStats->failed ?? 0) }}</div>
                            <small class="text-muted">Failed</small>
                        </div>
                    </div>

                    @if(($usageStats->total_sent ?? 0) > 0)
                    <div class="progress" style="height: 20px;">
                        @php
                            $total = $usageStats->total_sent;
                            $deliveredPct = ($usageStats->delivered / $total) * 100;
                            $readPct = ($usageStats->read_count / $total) * 100;
                            $failedPct = ($usageStats->failed / $total) * 100;
                        @endphp
                        <div class="progress-bar bg-info" style="width: {{ $readPct }}%" title="Read"></div>
                        <div class="progress-bar bg-success" style="width: {{ $deliveredPct - $readPct }}%" title="Delivered"></div>
                        <div class="progress-bar bg-danger" style="width: {{ $failedPct }}%" title="Failed"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mt-1">
                        <span>Delivery Rate: {{ number_format($deliveredPct, 1) }}%</span>
                        <span>Read Rate: {{ number_format($readPct, 1) }}%</span>
                    </div>
                    @else
                    <p class="text-muted text-center mb-0">No messages sent with this template yet.</p>
                    @endif

                    <hr>

                    <div class="row small text-muted">
                        <div class="col-md-4">
                            <strong>Total Cost:</strong> ${{ number_format(($usageStats->total_cost ?? 0) / 100, 2) }}
                        </div>
                        <div class="col-md-4">
                            <strong>First Used:</strong> {{ $usageStats->first_used ? \Carbon\Carbon::parse($usageStats->first_used)->format('M d, Y') : 'Never' }}
                        </div>
                        <div class="col-md-4">
                            <strong>Last Used:</strong> {{ $usageStats->last_used ? \Carbon\Carbon::parse($usageStats->last_used)->format('M d, Y') : 'Never' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Messages -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Messages</h5>
                </div>
                <div class="card-body p-0">
                    @if($recentMessages->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Recipient</th>
                                    <th>Status</th>
                                    <th>Sent</th>
                                    <th>Delivered</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentMessages as $message)
                                <tr>
                                    <td>
                                        <code>{{ substr($message->phone_number, 0, -4) }}****</code>
                                    </td>
                                    <td>
                                        @switch($message->status)
                                            @case('delivered')
                                            @case('read')
                                                <span class="badge bg-success">{{ ucfirst($message->status) }}</span>
                                                @break
                                            @case('sent')
                                            @case('queued')
                                                <span class="badge bg-info">{{ ucfirst($message->status) }}</span>
                                                @break
                                            @case('failed')
                                                <span class="badge bg-danger">Failed</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ ucfirst($message->status) }}</span>
                                        @endswitch
                                    </td>
                                    <td>{{ $message->created_at->format('M d, H:i') }}</td>
                                    <td>{{ $message->delivered_at?->format('M d, H:i') ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted text-center py-4 mb-0">No messages found for this template.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Preview -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Preview</h5>
                </div>
                <div class="card-body">
                    <div class="whatsapp-preview">
                        <div class="whatsapp-message">
                            @if($template->header)
                                <div class="whatsapp-header">
                                    @if($template->header['type'] === 'text')
                                        {{ $template->header['content'] ?? '' }}
                                    @else
                                        <i class="fas fa-{{ $template->header['type'] === 'image' ? 'image' : ($template->header['type'] === 'video' ? 'video' : 'file') }}"></i>
                                        [{{ strtoupper($template->header['type']) }}]
                                    @endif
                                </div>
                            @endif
                            <div class="whatsapp-body">{{ $template->content }}</div>
                            @if($template->footer)
                                <div class="whatsapp-footer">{{ $template->footer['text'] ?? '' }}</div>
                            @endif
                            @if($template->buttons)
                                <div class="whatsapp-buttons">
                                    @foreach($template->buttons as $button)
                                        <div class="whatsapp-button">{{ $button['text'] ?? 'Button' }}</div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($template->status === 'approved')
                            <button type="button" class="btn btn-{{ $template->is_active ? 'warning' : 'success' }}"
                                    onclick="toggleActive()">
                                <i class="fas fa-{{ $template->is_active ? 'pause' : 'play' }} me-2"></i>
                                {{ $template->is_active ? 'Deactivate' : 'Activate' }} Template
                            </button>
                        @endif

                        @if($template->status === 'pending')
                            <form action="{{ route('admin.whatsapp.approve', $template) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-check me-2"></i>Mark as Approved
                                </button>
                            </form>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="fas fa-times me-2"></i>Mark as Rejected
                            </button>
                        @endif

                        <a href="{{ route('admin.whatsapp.edit', $template) }}" class="btn btn-outline-primary">
                            <i class="fas fa-edit me-2"></i>Edit Template
                        </a>

                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="fas fa-trash me-2"></i>Delete Template
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick Info -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Info</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled small mb-0">
                        <li class="mb-2">
                            <i class="fas fa-info-circle text-muted me-2"></i>
                            Templates must be approved by Meta before use
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-clock text-muted me-2"></i>
                            Approval typically takes 24-48 hours
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-dollar-sign text-muted me-2"></i>
                            WhatsApp messages cost varies by region
                        </li>
                        <li>
                            <i class="fas fa-sync text-muted me-2"></i>
                            Use sync to update status from Meta
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.whatsapp.reject', $template) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reason" class="form-label">Rejection Reason</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required
                                  placeholder="Enter the reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the template <strong>{{ $template->name }}</strong>?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.whatsapp.destroy', $template) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Template</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.whatsapp-preview {
    background-color: #e5ddd5;
    padding: 1rem;
    border-radius: 8px;
}
.whatsapp-message {
    background-color: #dcf8c6;
    border-radius: 8px;
    padding: 8px 12px;
    max-width: 100%;
    box-shadow: 0 1px 0.5px rgba(0,0,0,0.13);
}
.whatsapp-header {
    font-weight: bold;
    margin-bottom: 4px;
    padding-bottom: 4px;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}
.whatsapp-body {
    white-space: pre-wrap;
    word-wrap: break-word;
}
.whatsapp-footer {
    font-size: 0.75rem;
    color: #667781;
    margin-top: 4px;
}
.whatsapp-buttons {
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid rgba(0,0,0,0.1);
}
.whatsapp-button {
    display: block;
    text-align: center;
    padding: 8px;
    color: #00a5f4;
    font-size: 0.875rem;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}
.whatsapp-button:last-child {
    border-bottom: none;
}
</style>

@push('scripts')
<script>
function toggleActive() {
    fetch('{{ route("admin.whatsapp.toggle-active", $template) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.error || 'Failed to toggle template status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}
</script>
@endpush
@endsection
