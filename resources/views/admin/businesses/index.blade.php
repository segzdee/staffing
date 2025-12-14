@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Business Management
            <small>All Businesses</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Businesses</li>
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
                <form method="GET" action="{{ url('panel/admin/businesses') }}">
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
                                    <option value="">All Businesses</option>
                                    <option value="1" {{ request('verified') == '1' ? 'selected' : '' }}>Verified Only</option>
                                    <option value="0" {{ request('verified') == '0' ? 'selected' : '' }}>Unverified Only</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Industry</label>
                                <select name="industry" class="form-control">
                                    <option value="">All Industries</option>
                                    @foreach($industries as $industry)
                                        <option value="{{ $industry }}" {{ request('industry') == $industry ? 'selected' : '' }}>
                                            {{ ucfirst($industry) }}
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
                                <label>Minimum Shifts Posted</label>
                                <input type="number" name="min_shifts" class="form-control" placeholder="0" value="{{ request('min_shifts') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="has_license" value="1" {{ request('has_license') ? 'checked' : '' }}>
                                            Has business license
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
                            <a href="{{ url('panel/admin/businesses') }}" class="btn btn-default">
                                <i class="fa fa-refresh"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Businesses Table -->
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">All Businesses ({{ $businesses->total() }})</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Business Name</th>
                            <th>Email</th>
                            <th>Location</th>
                            <th>Industry</th>
                            <th>Shifts Posted</th>
                            <th>Total Spent</th>
                            <th>Status</th>
                            <th>Verified</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($businesses as $business)
                        <tr class="{{ $business->status == 'suspended' ? 'bg-danger-light' : '' }}">
                            <td>{{ $business->id }}</td>
                            <td>
                                <a href="{{ url('panel/admin/businesses/'.$business->id) }}">
                                    {{ $business->name }}
                                </a>
                            </td>
                            <td>{{ $business->email }}</td>
                            <td>{{ $business->city }}, {{ $business->state }}</td>
                            <td>
                                @if($business->businessProfile && $business->businessProfile->industry)
                                    <span class="label label-primary">{{ ucfirst($business->businessProfile->industry) }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-blue">{{ $business->shifts_posted_count ?? 0 }}</span>
                            </td>
                            <td class="text-red">{{ Helper::amountFormatDecimal($business->total_spent ?? 0) }}</td>
                            <td>
                                @if($business->status == 'active')
                                    <span class="label label-success">Active</span>
                                @elseif($business->status == 'inactive')
                                    <span class="label label-default">Inactive</span>
                                @elseif($business->status == 'suspended')
                                    <span class="label label-danger">Suspended</span>
                                @else
                                    <span class="label label-default">{{ ucfirst($business->status) }}</span>
                                @endif
                            </td>
                            <td>
                                @if($business->is_verified_business)
                                    <span class="label label-success"><i class="fa fa-check"></i> Verified</span>
                                @else
                                    <span class="label label-warning">Unverified</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ url('panel/admin/businesses/'.$business->id) }}" class="btn btn-xs btn-info" title="View Details">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    @if(!$business->is_verified_business)
                                        <button type="button" class="btn btn-xs btn-success" onclick="verifyBusiness({{ $business->id }})" title="Verify Business">
                                            <i class="fa fa-check"></i>
                                        </button>
                                    @endif
                                    @if($business->status != 'suspended')
                                        <button type="button" class="btn btn-xs btn-danger" onclick="suspendBusiness({{ $business->id }})" title="Suspend">
                                            <i class="fa fa-ban"></i>
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-xs btn-warning" onclick="unsuspendBusiness({{ $business->id }})" title="Unsuspend">
                                            <i class="fa fa-refresh"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted">
                                <p style="padding: 20px;">No businesses found.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($businesses->total() > 0)
            <div class="box-footer clearfix">
                {{ $businesses->appends(request()->query())->links() }}
            </div>
            @endif
        </div>

    </section>
</div>

<!-- Verify Business Modal -->
<div class="modal fade" id="verifyBusinessModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="verifyBusinessForm">
                @csrf
                <div class="modal-header bg-success">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Verify Business</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> Verify this business's identity and credentials.
                    </div>
                    <div class="form-group">
                        <label>Verification Notes (optional)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Add any notes about the verification process..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Verify Business</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Suspend Business Modal -->
<div class="modal fade" id="suspendBusinessModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="suspendBusinessForm">
                @csrf
                <div class="modal-header bg-danger">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Suspend Business</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fa fa-warning"></i> This will prevent the business from posting new shifts and accessing their account.
                    </div>
                    <div class="form-group">
                        <label>Reason for suspension *</label>
                        <textarea name="reason" class="form-control" rows="4" required placeholder="Describe why this business is being suspended..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Suspend Business</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
function verifyBusiness(businessId) {
    var form = document.getElementById('verifyBusinessForm');
    form.action = '/panel/admin/businesses/' + businessId + '/verify';
    $('#verifyBusinessModal').modal('show');
}

function suspendBusiness(businessId) {
    var form = document.getElementById('suspendBusinessForm');
    form.action = '/panel/admin/businesses/' + businessId + '/suspend';
    $('#suspendBusinessModal').modal('show');
}

function unsuspendBusiness(businessId) {
    if (confirm('Are you sure you want to unsuspend this business?')) {
        $.post('/panel/admin/businesses/' + businessId + '/unsuspend', {
            _token: '{{ csrf_token() }}'
        }, function(response) {
            location.reload();
        }).fail(function(xhr) {
            alert('Error unsuspending business: ' + xhr.responseJSON.message);
        });
    }
}

$(document).ready(function() {
    $('#verifyBusinessForm, #suspendBusinessForm').submit(function(e) {
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
</style>
@endsection
