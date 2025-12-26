@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            KYC Verification #{{ $verification->id }}
            <small>Review Details</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="{{ route('dashboard.admin.kyc.index') }}">KYC Verifications</a></li>
            <li class="active">Review #{{ $verification->id }}</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            {{-- Left Column: Documents --}}
            <div class="col-md-8">
                {{-- Status Banner --}}
                <div class="callout callout-{{ $verification->status === 'approved' ? 'success' : ($verification->status === 'rejected' ? 'danger' : ($verification->status === 'in_review' ? 'info' : 'warning')) }}">
                    <h4>
                        @if($verification->status === 'approved')
                            <i class="fa fa-check-circle"></i> Approved
                        @elseif($verification->status === 'rejected')
                            <i class="fa fa-times-circle"></i> Rejected
                        @elseif($verification->status === 'in_review')
                            <i class="fa fa-eye"></i> In Review
                        @else
                            <i class="fa fa-clock-o"></i> Pending Review
                        @endif
                    </h4>
                    @if($verification->reviewed_at)
                        <p>Reviewed by {{ $verification->reviewer?->name ?? 'System' }} on {{ $verification->reviewed_at->format('M d, Y \a\t H:i') }}</p>
                    @else
                        <p>Submitted {{ $verification->created_at->diffForHumans() }}</p>
                    @endif
                </div>

                {{-- Document Viewer --}}
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Document Images</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            {{-- Document Front --}}
                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <strong>Document Front</strong>
                                    </div>
                                    <div class="panel-body text-center">
                                        @if($documentUrls['front'])
                                            <a href="{{ $documentUrls['front'] }}" target="_blank" class="document-preview" rel="noopener noreferrer">
                                                <img src="{{ $documentUrls['front'] }}" alt="Document Front" class="img-responsive" style="max-height: 300px; margin: 0 auto;">
                                            </a>
                                            <p class="text-muted small mt-2">Click to view full size</p>
                                        @else
                                            <p class="text-muted">Document not available</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Document Back --}}
                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <strong>Document Back</strong>
                                    </div>
                                    <div class="panel-body text-center">
                                        @if($documentUrls['back'])
                                            <a href="{{ $documentUrls['back'] }}" target="_blank" class="document-preview" rel="noopener noreferrer">
                                                <img src="{{ $documentUrls['back'] }}" alt="Document Back" class="img-responsive" style="max-height: 300px; margin: 0 auto;">
                                            </a>
                                            <p class="text-muted small mt-2">Click to view full size</p>
                                        @else
                                            <p class="text-muted">Not provided (may not be required)</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Selfie --}}
                        @if($documentUrls['selfie'])
                        <div class="row">
                            <div class="col-md-6 col-md-offset-3">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <strong>Selfie Photo</strong>
                                    </div>
                                    <div class="panel-body text-center">
                                        <a href="{{ $documentUrls['selfie'] }}" target="_blank" class="document-preview" rel="noopener noreferrer">
                                            <img src="{{ $documentUrls['selfie'] }}" alt="Selfie" class="img-responsive img-circle" style="max-height: 200px; margin: 0 auto;">
                                        </a>
                                        <p class="text-muted small mt-2">Click to view full size</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Rejection Reason (if rejected) --}}
                @if($verification->rejection_reason)
                <div class="box box-danger">
                    <div class="box-header with-border">
                        <h3 class="box-title">Rejection Reason</h3>
                    </div>
                    <div class="box-body">
                        {{ $verification->rejection_reason }}
                    </div>
                </div>
                @endif

                {{-- Reviewer Notes --}}
                @if($verification->reviewer_notes)
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">Reviewer Notes</h3>
                    </div>
                    <div class="box-body">
                        {{ $verification->reviewer_notes }}
                    </div>
                </div>
                @endif
            </div>

            {{-- Right Column: Info & Actions --}}
            <div class="col-md-4">
                {{-- User Info --}}
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">User Information</h3>
                    </div>
                    <div class="box-body">
                        <dl>
                            <dt>Name</dt>
                            <dd>{{ $verification->user->name ?? 'Unknown' }}</dd>

                            <dt>Email</dt>
                            <dd>{{ $verification->user->email ?? 'Unknown' }}</dd>

                            <dt>Account Created</dt>
                            <dd>{{ $verification->user->created_at?->format('M d, Y') ?? 'Unknown' }}</dd>

                            <dt>Current KYC Level</dt>
                            <dd>
                                <span class="label label-{{ $verification->user->kyc_level === 'full' ? 'success' : ($verification->user->kyc_level === 'enhanced' ? 'info' : ($verification->user->kyc_level === 'basic' ? 'warning' : 'default')) }}">
                                    {{ ucfirst($verification->user->kyc_level ?? 'none') }}
                                </span>
                            </dd>
                        </dl>
                    </div>
                </div>

                {{-- Document Info --}}
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">Document Details</h3>
                    </div>
                    <div class="box-body">
                        <dl>
                            <dt>Document Type</dt>
                            <dd>{{ $verification->document_type_name }}</dd>

                            <dt>Issuing Country</dt>
                            <dd><span class="label label-default">{{ $verification->document_country }}</span></dd>

                            @if($verification->document_number)
                            <dt>Document Number</dt>
                            <dd><code>{{ $verification->document_number }}</code></dd>
                            @endif

                            @if($verification->document_expiry)
                            <dt>Document Expiry</dt>
                            <dd>
                                {{ $verification->document_expiry->format('M d, Y') }}
                                @if($verification->isDocumentExpired())
                                    <span class="label label-danger">Expired</span>
                                @elseif($verification->isDocumentExpiringSoon())
                                    <span class="label label-warning">Expiring Soon</span>
                                @endif
                            </dd>
                            @endif

                            <dt>Provider</dt>
                            <dd>{{ ucfirst($verification->provider) }}</dd>

                            @if($verification->confidence_score)
                            <dt>Confidence Score</dt>
                            <dd>{{ number_format($verification->confidence_score * 100, 1) }}%</dd>
                            @endif

                            <dt>Attempt</dt>
                            <dd>{{ $verification->attempt_count }} of {{ $verification->max_attempts }}</dd>
                        </dl>
                    </div>
                </div>

                {{-- Metadata --}}
                <div class="box box-default collapsed-box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Submission Metadata</h3>
                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="box-body">
                        <dl>
                            <dt>IP Address</dt>
                            <dd><code>{{ $verification->ip_address ?? 'Unknown' }}</code></dd>

                            <dt>Submitted</dt>
                            <dd>{{ $verification->created_at->format('M d, Y H:i:s') }}</dd>

                            @if($verification->provider_reference)
                            <dt>Provider Reference</dt>
                            <dd><code>{{ $verification->provider_reference }}</code></dd>
                            @endif
                        </dl>
                    </div>
                </div>

                {{-- Actions --}}
                @if(in_array($verification->status, ['pending', 'in_review']))
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title">Review Actions</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <label for="reviewer-notes">Notes (Optional)</label>
                            <textarea id="reviewer-notes" class="form-control" rows="2" placeholder="Add review notes..."></textarea>
                        </div>
                        <button type="button" class="btn btn-success btn-block" id="approve-btn">
                            <i class="fa fa-check"></i> Approve Verification
                        </button>
                    </div>
                    <div class="box-footer">
                        <div class="form-group">
                            <label for="rejection-reason">Rejection Reason</label>
                            <textarea id="rejection-reason" class="form-control" rows="2" placeholder="Required if rejecting..."></textarea>
                        </div>
                        <button type="button" class="btn btn-danger btn-block" id="reject-btn">
                            <i class="fa fa-times"></i> Reject Verification
                        </button>
                    </div>
                </div>
                @endif

                {{-- User's Previous Verifications --}}
                @if($history->count() > 0)
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">Verification History</h3>
                    </div>
                    <div class="box-body">
                        <ul class="timeline timeline-inverse">
                            @foreach($history as $pastVerification)
                            <li>
                                <i class="fa fa-{{ $pastVerification->status === 'approved' ? 'check bg-green' : ($pastVerification->status === 'rejected' ? 'times bg-red' : 'clock-o bg-yellow') }}"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fa fa-clock-o"></i> {{ $pastVerification->created_at->diffForHumans() }}</span>
                                    <h3 class="timeline-header">{{ ucfirst($pastVerification->status) }}</h3>
                                    <div class="timeline-body">
                                        {{ $pastVerification->document_type_name }} ({{ $pastVerification->document_country }})
                                        @if($pastVerification->rejection_reason)
                                            <br><small class="text-muted">{{ Str::limit($pastVerification->rejection_reason, 50) }}</small>
                                        @endif
                                    </div>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif

                {{-- Back Button --}}
                <a href="{{ route('dashboard.admin.kyc.index') }}" class="btn btn-default btn-block">
                    <i class="fa fa-arrow-left"></i> Back to Queue
                </a>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#approve-btn').click(function() {
        var notes = $('#reviewer-notes').val();

        if (!confirm('Are you sure you want to approve this verification?')) {
            return;
        }

        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');

        $.ajax({
            url: '{{ route("dashboard.admin.kyc.approve", $verification->id) }}',
            method: 'POST',
            data: {
                notes: notes,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    alert('Verification approved successfully.');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                    $('#approve-btn').prop('disabled', false).html('<i class="fa fa-check"></i> Approve Verification');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $('#approve-btn').prop('disabled', false).html('<i class="fa fa-check"></i> Approve Verification');
            }
        });
    });

    $('#reject-btn').click(function() {
        var reason = $('#rejection-reason').val().trim();

        if (!reason) {
            alert('Please enter a rejection reason.');
            $('#rejection-reason').focus();
            return;
        }

        if (!confirm('Are you sure you want to reject this verification?')) {
            return;
        }

        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');

        $.ajax({
            url: '{{ route("dashboard.admin.kyc.reject", $verification->id) }}',
            method: 'POST',
            data: {
                reason: reason,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    alert('Verification rejected.');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                    $('#reject-btn').prop('disabled', false).html('<i class="fa fa-times"></i> Reject Verification');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $('#reject-btn').prop('disabled', false).html('<i class="fa fa-times"></i> Reject Verification');
            }
        });
    });
});
</script>
@endpush
@endsection
