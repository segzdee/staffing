@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Dispute #{{ $dispute->id }}
            @if($dispute->isEscalated())
            <span class="label label-purple">Escalation Level {{ $dispute->escalation_level }}</span>
            @endif
            <small>{{ ucfirst(str_replace('_', ' ', $dispute->status)) }}</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="{{ url('panel/admin/disputes') }}">Disputes</a></li>
            <li class="active">#{{ $dispute->id }}</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            {{-- Left Column: Dispute Details --}}
            <div class="col-md-4">
                {{-- SLA Timer --}}
                @if($dispute->isActive())
                <div class="box {{ $slaData['percentage'] >= 100 ? 'box-danger' : ($slaData['percentage'] >= 80 ? 'box-warning' : 'box-success') }}">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-clock-o"></i> SLA Timer</h3>
                    </div>
                    <div class="box-body">
                        <div class="text-center">
                            <h2 id="slaCountdown" class="text-{{ $slaData['percentage'] >= 100 ? 'danger' : ($slaData['percentage'] >= 80 ? 'warning' : 'success') }}">
                                @if($slaData['remaining_hours'] > 0)
                                    {{ floor($slaData['remaining_hours']) }}h {{ round(($slaData['remaining_hours'] - floor($slaData['remaining_hours'])) * 60) }}m
                                @else
                                    SLA BREACHED
                                @endif
                            </h2>
                            <p class="text-muted">
                                Deadline: {{ $slaData['deadline']->format('M d, Y g:i A') }}
                            </p>
                        </div>
                        <div class="progress">
                            <div class="progress-bar progress-bar-{{ $slaData['percentage'] >= 100 ? 'danger' : ($slaData['percentage'] >= 80 ? 'warning' : 'success') }}"
                                 role="progressbar"
                                 style="width: {{ min(100, $slaData['percentage']) }}%">
                                {{ round($slaData['percentage']) }}%
                            </div>
                        </div>
                        <div class="row text-center" style="margin-top: 10px;">
                            <div class="col-xs-6">
                                <small class="text-muted">SLA Threshold</small><br>
                                <strong>{{ $slaData['threshold'] }} hours</strong>
                            </div>
                            <div class="col-xs-6">
                                <small class="text-muted">Priority</small><br>
                                <span class="label {{ $dispute->getPriorityBadgeClass() }}">{{ ucfirst($dispute->priority) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Dispute Info --}}
                <div class="box box-danger">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-gavel"></i> Dispute Details</h3>
                    </div>
                    <div class="box-body">
                        <dl class="dl-horizontal">
                            <dt>Status</dt>
                            <dd>
                                <span class="label {{ $dispute->getStatusBadgeClass() }}">
                                    {{ ucfirst(str_replace('_', ' ', $dispute->status)) }}
                                </span>
                            </dd>

                            <dt>Priority</dt>
                            <dd>
                                <span class="label {{ $dispute->getPriorityBadgeClass() }}">
                                    {{ ucfirst($dispute->priority) }}
                                </span>
                            </dd>

                            <dt>Filed By</dt>
                            <dd>
                                <span class="label label-{{ $dispute->filed_by == 'worker' ? 'success' : 'info' }}">
                                    {{ ucfirst($dispute->filed_by) }}
                                </span>
                            </dd>

                            <dt>Filed At</dt>
                            <dd>{{ $dispute->filed_at->format('M d, Y g:i A') }}</dd>

                            @if($dispute->assigned_at)
                            <dt>Assigned At</dt>
                            <dd>{{ $dispute->assigned_at->format('M d, Y g:i A') }}</dd>
                            @endif

                            @if($dispute->resolved_at)
                            <dt>Resolved At</dt>
                            <dd>{{ $dispute->resolved_at->format('M d, Y g:i A') }}</dd>
                            @endif

                            @if($dispute->escalated_at)
                            <dt>Escalated At</dt>
                            <dd>{{ $dispute->escalated_at->format('M d, Y g:i A') }}</dd>
                            @endif
                        </dl>

                        <hr>

                        <h4>Dispute Reason</h4>
                        <div class="well well-sm">
                            {{ $dispute->dispute_reason }}
                        </div>

                        @if($dispute->evidence_urls && count($dispute->evidence_urls) > 0)
                        <h4>Initial Evidence</h4>
                        <ul class="list-unstyled">
                            @foreach($dispute->evidence_urls as $url)
                            <li><a href="{{ $url }}" target="_blank" rel="noopener noreferrer"><i class="fa fa-file"></i> View Evidence</a></li>
                            @endforeach
                        </ul>
                        @endif

                        @if($dispute->resolution_outcome)
                        <hr>
                        <h4>Resolution</h4>
                        <div class="alert alert-{{ $dispute->resolution_outcome == 'worker_favor' ? 'success' : ($dispute->resolution_outcome == 'business_favor' ? 'info' : 'warning') }}">
                            <strong>{{ $dispute->getOutcomeLabel() }}</strong>
                            @if($dispute->adjustment_amount)
                            <br>Adjustment: ${{ number_format($dispute->adjustment_amount, 2) }}
                            @endif
                        </div>
                        @if($dispute->resolution_notes)
                        <p><strong>Notes:</strong> {{ $dispute->resolution_notes }}</p>
                        @endif
                        @endif
                    </div>
                </div>

                {{-- Parties Involved --}}
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-users"></i> Parties Involved</h3>
                    </div>
                    <div class="box-body">
                        <h4><i class="fa fa-user"></i> Worker</h4>
                        @if($dispute->worker)
                        <p>
                            <a href="{{ url('panel/admin/workers/' . $dispute->worker_id) }}">
                                {{ $dispute->worker->name }}
                            </a><br>
                            <small class="text-muted">{{ $dispute->worker->email }}</small>
                        </p>
                        @else
                        <p class="text-muted">N/A</p>
                        @endif

                        <hr>

                        <h4><i class="fa fa-building"></i> Business</h4>
                        @if($dispute->business)
                        <p>
                            <a href="{{ url('panel/admin/businesses/' . $dispute->business_id) }}">
                                {{ $dispute->business->name }}
                            </a><br>
                            <small class="text-muted">{{ $dispute->business->email }}</small>
                        </p>
                        @else
                        <p class="text-muted">N/A</p>
                        @endif

                        @if($dispute->shiftPayment && $dispute->shiftPayment->assignment && $dispute->shiftPayment->assignment->shift)
                        <hr>
                        <h4><i class="fa fa-calendar"></i> Related Shift</h4>
                        <p>
                            <a href="{{ url('panel/admin/shifts/' . $dispute->shiftPayment->assignment->shift->id) }}">
                                {{ $dispute->shiftPayment->assignment->shift->title }}
                            </a><br>
                            <small class="text-muted">
                                Payment: ${{ number_format($dispute->shiftPayment->amount ?? 0, 2) }}
                            </small>
                        </p>
                        @endif
                    </div>
                </div>

                {{-- Assignment --}}
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-user-circle"></i> Assignment</h3>
                    </div>
                    <div class="box-body">
                        @if($dispute->assignedAdmin)
                        <p>
                            <strong>Assigned To:</strong> {{ $dispute->assignedAdmin->name }}<br>
                            <small class="text-muted">Since {{ $dispute->assigned_at->format('M d, Y g:i A') }}</small>
                        </p>
                        @if($dispute->previousAdmin)
                        <p class="text-muted">
                            <small>Previously: {{ $dispute->previousAdmin->name }}</small>
                        </p>
                        @endif
                        @else
                        <p class="text-danger"><i class="fa fa-exclamation-triangle"></i> Unassigned</p>
                        @endif

                        @if($dispute->isActive())
                        <form method="POST" action="{{ url('panel/admin/disputes/' . $dispute->id . '/assign') }}">
                            @csrf
                            <div class="form-group">
                                <label>{{ $dispute->assignedAdmin ? 'Reassign To' : 'Assign To' }}</label>
                                <select name="admin_id" class="form-control" required>
                                    <option value="">Select Admin...</option>
                                    @foreach($admins as $admin)
                                    <option value="{{ $admin->id }}" {{ $dispute->assigned_to_admin == $admin->id ? 'selected' : '' }}>
                                        {{ $admin->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fa fa-user-plus"></i> {{ $dispute->assignedAdmin ? 'Reassign' : 'Assign' }}
                            </button>
                        </form>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                @if($dispute->isActive())
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-cogs"></i> Actions</h3>
                    </div>
                    <div class="box-body">
                        <div class="btn-group-vertical" style="width: 100%;">
                            <button type="button" class="btn btn-success" onclick="showResolveModal()">
                                <i class="fa fa-check"></i> Resolve Dispute
                            </button>

                            @if(($dispute->escalation_level ?? 0) < 3)
                            <button type="button" class="btn btn-warning" onclick="showEscalateModal()">
                                <i class="fa fa-arrow-up"></i> Escalate
                            </button>
                            @endif

                            <button type="button" class="btn btn-default" onclick="showStatusModal()">
                                <i class="fa fa-refresh"></i> Change Status
                            </button>

                            <button type="button" class="btn btn-danger" onclick="showCloseModal()">
                                <i class="fa fa-times"></i> Close Without Resolution
                            </button>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Escalation History --}}
                @if($dispute->escalations->count() > 0)
                <div class="box box-purple">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-history"></i> Escalation History</h3>
                    </div>
                    <div class="box-body">
                        <ul class="timeline timeline-inverse">
                            @foreach($dispute->escalations as $escalation)
                            <li>
                                <i class="fa fa-arrow-up bg-purple"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fa fa-clock-o"></i> {{ $escalation->escalated_at->diffForHumans() }}</span>
                                    <h3 class="timeline-header">Level {{ $escalation->escalation_level }}</h3>
                                    <div class="timeline-body">
                                        <p><strong>Reason:</strong> {{ $escalation->escalation_reason }}</p>
                                        @if($escalation->escalatedFromAdmin)
                                        <p><small>From: {{ $escalation->escalatedFromAdmin->name }}</small></p>
                                        @endif
                                        @if($escalation->escalatedToAdmin)
                                        <p><small>To: {{ $escalation->escalatedToAdmin->name }}</small></p>
                                        @endif
                                    </div>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif

                {{-- Payment Adjustments --}}
                @if($dispute->adjustments->count() > 0)
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-money"></i> Payment Adjustments</h3>
                    </div>
                    <div class="box-body no-padding">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dispute->adjustments as $adjustment)
                                <tr>
                                    <td>{{ $adjustment->getTypeLabel() }}</td>
                                    <td>{{ $adjustment->getFormattedAmount() }}</td>
                                    <td>
                                        <span class="label label-{{ $adjustment->status == 'applied' ? 'success' : ($adjustment->status == 'pending' ? 'warning' : 'default') }}">
                                            {{ ucfirst($adjustment->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>

            {{-- Right Column: Communication Thread --}}
            <div class="col-md-8">
                <div class="box box-primary direct-chat direct-chat-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-comments"></i> Communication Thread</h3>
                        <div class="box-tools pull-right">
                            <span class="badge bg-blue" id="messageCount">{{ $dispute->messages->count() }}</span>
                            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                                <i class="fa fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="direct-chat-messages" id="messageContainer" style="height: 500px; overflow-y: auto;">
                            @forelse($dispute->messages as $message)
                            <div class="direct-chat-msg {{ $message->isFromAdmin() ? 'right' : '' }} {{ $message->is_internal ? 'internal-message' : '' }}">
                                <div class="direct-chat-info clearfix">
                                    <span class="direct-chat-name {{ $message->isFromAdmin() ? 'pull-right' : 'pull-left' }}">
                                        @if($message->message_type == 'system')
                                        <i class="fa fa-cog"></i> System
                                        @else
                                        {{ $message->sender ? $message->sender->name : 'Unknown' }}
                                        @endif
                                        @if($message->is_internal)
                                        <span class="label label-warning" style="font-size: 10px;">Internal</span>
                                        @endif
                                    </span>
                                    <span class="direct-chat-timestamp {{ $message->isFromAdmin() ? 'pull-left' : 'pull-right' }}">
                                        {{ $message->created_at->format('M d, g:i A') }}
                                    </span>
                                </div>
                                <img class="direct-chat-img"
                                     src="https://ui-avatars.com/api/?name={{ urlencode($message->sender ? $message->sender->name : 'S') }}&background={{ $message->isFromAdmin() ? '3c8dbc' : ($message->isFromWorker() ? '00a65a' : 'f39c12') }}&color=fff"
                                     alt="">
                                <div class="direct-chat-text {{ $message->message_type == 'system' ? 'bg-gray' : ($message->message_type == 'evidence' ? 'bg-yellow' : ($message->message_type == 'resolution' ? 'bg-green' : '')) }}">
                                    @if($message->message_type == 'evidence')
                                    <strong><i class="fa fa-file"></i> Evidence Submitted</strong><br>
                                    @elseif($message->message_type == 'resolution')
                                    <strong><i class="fa fa-check-circle"></i> Resolution</strong><br>
                                    @endif
                                    {{ $message->message }}

                                    @if($message->hasAttachments())
                                    <div class="attachments" style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed rgba(0,0,0,0.1);">
                                        <strong><i class="fa fa-paperclip"></i> Attachments ({{ $message->getAttachmentCount() }}):</strong>
                                        <ul class="list-unstyled" style="margin-top: 5px;">
                                            @foreach($message->attachments as $attachment)
                                            <li>
                                                <a href="{{ $attachment['url'] ?? '#' }}" target="_blank" rel="noopener noreferrer">
                                                    <i class="fa fa-file-o"></i> {{ $attachment['name'] ?? 'File' }}
                                                </a>
                                                <small class="text-muted">({{ number_format(($attachment['size'] ?? 0) / 1024, 1) }}KB)</small>
                                            </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @empty
                            <div class="text-center text-muted" style="padding: 50px;">
                                <i class="fa fa-comments-o fa-3x"></i>
                                <p style="margin-top: 15px;">No messages yet. Start the conversation below.</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                    <div class="box-footer">
                        <form id="messageForm" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <div class="input-group">
                                    <input type="text" name="message" id="messageInput" placeholder="Type Message ..." class="form-control" required>
                                    <span class="input-group-btn">
                                        <button type="button" class="btn btn-default" onclick="document.getElementById('fileInput').click()">
                                            <i class="fa fa-paperclip"></i>
                                        </button>
                                        <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane"></i></button>
                                    </span>
                                </div>
                                <input type="file" id="fileInput" name="files[]" multiple style="display: none;" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="is_internal" id="isInternal"> Internal Note (not visible to parties)
                                    </label>
                                </div>
                                <div class="col-md-6 text-right">
                                    <span id="selectedFiles" class="text-muted"></span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Quick Evidence Upload --}}
                <div class="box box-default collapsed-box">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-upload"></i> Upload Evidence</h3>
                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="box-body">
                        <form id="evidenceForm" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label>Evidence Files</label>
                                <input type="file" name="files[]" multiple class="form-control" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt" required>
                                <p class="help-block">Max 5 files, 10MB each. Supported: Images, PDF, Word, Excel, Text</p>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Describe what this evidence shows..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-upload"></i> Upload Evidence
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

{{-- Resolve Modal --}}
<div class="modal fade" id="resolveModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="resolveForm" method="POST" action="{{ url('panel/admin/disputes/' . $dispute->id . '/resolve') }}">
                @csrf
                <div class="modal-header bg-success">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-check"></i> Resolve Dispute</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Resolution Outcome *</label>
                                <select name="outcome" class="form-control" required onchange="updateAdjustmentVisibility(this)">
                                    <option value="">Select Outcome...</option>
                                    <option value="worker_favor">In Worker's Favor</option>
                                    <option value="business_favor">In Business's Favor</option>
                                    <option value="split">Split Resolution</option>
                                    <option value="no_fault">No Fault Found</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group" id="adjustmentGroup">
                                <label>Adjustment Amount ($)</label>
                                <input type="number" name="adjustment_amount" class="form-control" step="0.01" min="0" placeholder="0.00">
                                <p class="help-block">Leave empty if no financial adjustment needed</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Resolution Notes * (visible to all parties)</label>
                        <textarea name="resolution_notes" class="form-control" rows="4" required
                                  placeholder="Explain the resolution decision and reasoning..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Internal Notes (admin only)</label>
                        <textarea name="internal_notes" class="form-control" rows="3"
                                  placeholder="Optional internal notes for admin records..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Resolve Dispute</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Escalate Modal --}}
<div class="modal fade" id="escalateModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ url('panel/admin/disputes/' . $dispute->id . '/escalate') }}">
                @csrf
                <div class="modal-header bg-warning">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-arrow-up"></i> Escalate Dispute</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        This will escalate the dispute to Level {{ ($dispute->escalation_level ?? 0) + 1 }} and upgrade priority.
                    </div>
                    <div class="form-group">
                        <label>Escalation Reason *</label>
                        <textarea name="reason" class="form-control" rows="3" required
                                  placeholder="Explain why this dispute needs escalation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Escalate</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Status Modal --}}
<div class="modal fade" id="statusModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ url('panel/admin/disputes/' . $dispute->id . '/status') }}">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-refresh"></i> Change Status</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>New Status *</label>
                        <select name="status" class="form-control" required>
                            <option value="pending" {{ $dispute->status == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="investigating" {{ $dispute->status == 'investigating' ? 'selected' : '' }}>Investigating</option>
                            <option value="evidence_review" {{ $dispute->status == 'evidence_review' ? 'selected' : '' }}>Evidence Review</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes about this status change..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Close Modal --}}
<div class="modal fade" id="closeModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ url('panel/admin/disputes/' . $dispute->id . '/close') }}">
                @csrf
                <div class="modal-header bg-danger">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-times"></i> Close Dispute</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i>
                        This will close the dispute without a formal resolution. No adjustments will be made.
                    </div>
                    <div class="form-group">
                        <label>Closure Notes *</label>
                        <textarea name="notes" class="form-control" rows="3" required
                                  placeholder="Explain why this dispute is being closed without resolution..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Close Dispute</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
// Modal functions
function showResolveModal() { $('#resolveModal').modal('show'); }
function showEscalateModal() { $('#escalateModal').modal('show'); }
function showStatusModal() { $('#statusModal').modal('show'); }
function showCloseModal() { $('#closeModal').modal('show'); }

function updateAdjustmentVisibility(select) {
    var group = document.getElementById('adjustmentGroup');
    if (select.value === 'no_fault') {
        group.style.display = 'none';
    } else {
        group.style.display = 'block';
    }
}

// Message form handling
document.getElementById('messageForm').addEventListener('submit', function(e) {
    e.preventDefault();

    var message = document.getElementById('messageInput').value;
    var isInternal = document.getElementById('isInternal').checked;
    var files = document.getElementById('fileInput').files;

    if (!message.trim()) {
        return;
    }

    var formData = new FormData();
    formData.append('message', message);
    formData.append('is_internal', isInternal ? '1' : '0');
    formData.append('_token', '{{ csrf_token() }}');

    // Add files if any
    for (var i = 0; i < files.length; i++) {
        formData.append('files[]', files[i]);
    }

    fetch('{{ url("panel/admin/disputes/" . $dispute->id . "/message") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error sending message: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error sending message: ' + error);
    });
});

// Evidence form handling
document.getElementById('evidenceForm').addEventListener('submit', function(e) {
    e.preventDefault();

    var formData = new FormData(this);
    formData.append('_token', '{{ csrf_token() }}');

    fetch('{{ url("panel/admin/disputes/" . $dispute->id . "/evidence") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error uploading evidence: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Error uploading evidence: ' + error);
    });
});

// File selection display
document.getElementById('fileInput').addEventListener('change', function() {
    var count = this.files.length;
    document.getElementById('selectedFiles').textContent = count > 0 ? count + ' file(s) selected' : '';
});

// Scroll to bottom of messages
var container = document.getElementById('messageContainer');
if (container) {
    container.scrollTop = container.scrollHeight;
}

// SLA countdown timer
@if($dispute->isActive() && $slaData['remaining_hours'] > 0)
var deadline = new Date('{{ $slaData["deadline"]->toISOString() }}');

function updateCountdown() {
    var now = new Date();
    var diff = deadline - now;

    if (diff <= 0) {
        document.getElementById('slaCountdown').textContent = 'SLA BREACHED';
        document.getElementById('slaCountdown').className = 'text-danger';
        return;
    }

    var hours = Math.floor(diff / (1000 * 60 * 60));
    var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    var seconds = Math.floor((diff % (1000 * 60)) / 1000);

    document.getElementById('slaCountdown').textContent = hours + 'h ' + minutes + 'm ' + seconds + 's';
}

updateCountdown();
setInterval(updateCountdown, 1000);
@endif
</script>

<style>
.label-purple { background-color: #605ca8; }
.bg-purple { background-color: #605ca8 !important; }
.box-purple { border-top-color: #605ca8; }

.direct-chat-messages {
    transform: none;
}

.direct-chat-msg.right .direct-chat-text {
    background: #3c8dbc;
    border-color: #3c8dbc;
    color: #fff;
}

.direct-chat-msg.right .direct-chat-text:before,
.direct-chat-msg.right .direct-chat-text:after {
    border-left-color: #3c8dbc;
}

.internal-message .direct-chat-text {
    background: #fff3cd !important;
    border-color: #ffc107 !important;
    color: #856404 !important;
}

.direct-chat-text.bg-gray {
    background: #d2d6de !important;
    border-color: #d2d6de !important;
    color: #444 !important;
}

.direct-chat-text.bg-yellow {
    background: #f39c12 !important;
    border-color: #f39c12 !important;
    color: #fff !important;
}

.direct-chat-text.bg-green {
    background: #00a65a !important;
    border-color: #00a65a !important;
    color: #fff !important;
}

.timeline-inverse > li > .timeline-item {
    background: #f9f9f9;
    border-color: #ddd;
}

dl.dl-horizontal dt {
    width: 100px;
}

dl.dl-horizontal dd {
    margin-left: 120px;
}
</style>
@endsection
