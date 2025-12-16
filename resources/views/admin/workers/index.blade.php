@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Worker Management
            <small>All Workers</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Workers</li>
        </ol>
    </section>

    <section class="content">
        <!-- Filters Box -->
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-filter"></i> Filters</h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                </div>
            </div>
            <div class="box-body">
                <form method="GET" action="{{ url('panel/admin/workers') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">All Statuses</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Verification</label>
                                <select name="verified" class="form-control">
                                    <option value="">All Workers</option>
                                    <option value="1" {{ request('verified') == '1' ? 'selected' : '' }}>Verified Only</option>
                                    <option value="0" {{ request('verified') == '0' ? 'selected' : '' }}>Unverified Only</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Skills</label>
                                <select name="skill" class="form-control">
                                    <option value="">All Skills</option>
                                    @foreach($skills as $skill)
                                        <option value="{{ $skill->id }}" {{ request('skill') == $skill->id ? 'selected' : '' }}>
                                            {{ $skill->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Search</label>
                                <input type="text" name="q" class="form-control" placeholder="Name or email..." value="{{ request('q') }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Location</label>
                                <input type="text" name="location" class="form-control" placeholder="City or state..." value="{{ request('location') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Minimum Rating</label>
                                <select name="min_rating" class="form-control">
                                    <option value="">Any Rating</option>
                                    <option value="4.5" {{ request('min_rating') == '4.5' ? 'selected' : '' }}>4.5+ Stars</option>
                                    <option value="4.0" {{ request('min_rating') == '4.0' ? 'selected' : '' }}>4.0+ Stars</option>
                                    <option value="3.5" {{ request('min_rating') == '3.5' ? 'selected' : '' }}>3.5+ Stars</option>
                                    <option value="3.0" {{ request('min_rating') == '3.0' ? 'selected' : '' }}>3.0+ Stars</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="has_certifications" value="1" {{ request('has_certifications') ? 'checked' : '' }}>
                                            Has certifications
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="pending_verification" value="1" {{ request('pending_verification') ? 'checked' : '' }}>
                                            Pending verification
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i> Apply Filters
                            </button>
                            <a href="{{ url('panel/admin/workers') }}" class="btn btn-default">
                                <i class="fa fa-refresh"></i> Clear
                            </a>
                            <a href="{{ url('panel/admin/workers/skills') }}" class="btn btn-info pull-right">
                                <i class="fa fa-wrench"></i> Manage Skills
                            </a>
                            <a href="{{ url('panel/admin/workers/certifications') }}" class="btn btn-warning pull-right mr-2" style="margin-right: 10px;">
                                <i class="fa fa-certificate"></i> Review Certifications
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Workers Table -->
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">All Workers ({{ $workers->total() }})</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <div class="overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shifts Completed</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Earned</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verified</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($workers as $worker)
                        <tr class="{{ $worker->status == 'suspended' ? 'bg-red-50' : 'hover:bg-gray-50' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $worker->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <a href="{{ url('panel/admin/workers/'.$worker->id) }}">
                                    {{ $worker->name }}
                                </a>
                                @if($worker->badges->count() > 0)
                                    @foreach($worker->badges as $badge)
                                        <i class="fa fa-star text-yellow" title="{{ $badge->badge_type }}"></i>
                                    @endforeach
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $worker->email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $worker->city }}, {{ $worker->state }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($worker->average_rating)
                                    <span class="badge bg-{{ $worker->average_rating >= 4.5 ? 'green' : ($worker->average_rating >= 4.0 ? 'blue' : 'yellow') }}">
                                        {{ number_format($worker->average_rating, 1) }} <i class="fa fa-star"></i>
                                    </span>
                                @else
                                    <span class="text-muted">No ratings</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($worker->shifts_completed_count ?? 0) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">{{ Helper::amountFormatDecimal($worker->total_earned ?? 0) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($worker->status == 'active')
                                    <span class="label label-success">Active</span>
                                @elseif($worker->status == 'inactive')
                                    <span class="label label-default">Inactive</span>
                                @elseif($worker->status == 'suspended')
                                    <span class="label label-danger">Suspended</span>
                                @else
                                    <span class="label label-default">{{ ucfirst($worker->status) }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($worker->is_verified_worker)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        Verified
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Unverified</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="btn-group">
                                    <a href="{{ url('panel/admin/workers/'.$worker->id) }}" class="btn btn-xs btn-info" title="View Details">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    @if(!$worker->is_verified_worker)
                                        <button type="button" class="btn btn-xs btn-success" onclick="verifyWorker({{ $worker->id }})" title="Verify Worker">
                                            <i class="fa fa-check"></i>
                                        </button>
                                    @endif
                                    @if($worker->status != 'suspended')
                                        <button type="button" class="btn btn-xs btn-danger" onclick="suspendWorker({{ $worker->id }})" title="Suspend">
                                            <i class="fa fa-ban"></i>
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-xs btn-warning" onclick="unsuspendWorker({{ $worker->id }})" title="Unsuspend">
                                            <i class="fa fa-refresh"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="px-6 py-4 text-center text-sm text-gray-500">
                                <p class="py-8">No workers found.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
            @if($workers->total() > 0)
            <div class="box-footer clearfix">
                {{ $workers->appends(request()->query())->links() }}
            </div>
            @endif
        </div>

    </section>
</div>

<!-- Verify Worker Modal -->
<div class="modal fade" id="verifyWorkerModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="verifyWorkerForm">
                @csrf
                <div class="modal-header bg-success">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Verify Worker</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> Verify this worker's identity and credentials.
                    </div>
                    <div class="form-group">
                        <label>Verification Notes (optional)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Add any notes about the verification process..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Verify Worker</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Suspend Worker Modal -->
<div class="modal fade" id="suspendWorkerModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="suspendWorkerForm">
                @csrf
                <div class="modal-header bg-danger">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Suspend Worker</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fa fa-warning"></i> This will prevent the worker from accessing their account and applying to shifts.
                    </div>
                    <div class="form-group">
                        <label>Reason for suspension *</label>
                        <textarea name="reason" class="form-control" rows="4" required placeholder="Describe why this worker is being suspended..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Suspend Worker</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
function verifyWorker(workerId) {
    var form = document.getElementById('verifyWorkerForm');
    form.action = '/panel/admin/workers/' + workerId + '/verify';
    $('#verifyWorkerModal').modal('show');
}

function suspendWorker(workerId) {
    var form = document.getElementById('suspendWorkerForm');
    form.action = '/panel/admin/workers/' + workerId + '/suspend';
    $('#suspendWorkerModal').modal('show');
}

function unsuspendWorker(workerId) {
    if (confirm('Are you sure you want to unsuspend this worker?')) {
        $.post('/panel/admin/workers/' + workerId + '/unsuspend', {
            _token: '{{ csrf_token() }}'
        }, function(response) {
            location.reload();
        }).fail(function(xhr) {
            alert('Error unsuspending worker: ' + xhr.responseJSON.message);
        });
    }
}

$(document).ready(function() {
    $('#verifyWorkerForm, #suspendWorkerForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var data = form.serialize();

        $.post(url, data, function(response) {
            location.reload();
        }).fail(function(xhr) {
            alert('Error: ' + xhr.responseJSON.message);
        });
    });
});
</script>

<style>
.bg-danger-light {
    background-color: #f8d7da !important;
}
.mr-2 {
    margin-right: 10px;
}
</style>
@endsection
