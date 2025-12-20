@extends('layouts.authenticated')

@section('css')
<style>
.worker-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s;
}

.worker-card:hover {
    border-color: #667eea;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.worker-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
}

.skill-badge {
    display: inline-block;
    background: #e7f3ff;
    padding: 5px 12px;
    border-radius: 15px;
    margin: 3px;
    font-size: 12px;
}

.stat-inline {
    display: inline-block;
    margin-right: 20px;
    color: #666;
}

.filter-tabs {
    margin-bottom: 20px;
}

.add-worker-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}
</style>
@endsection

@section('content')
<div class="container" style="margin-top: 30px;">
    <div class="page-header">
        <div class="row">
            <div class="col-md-8">
                <h1><i class="fa fa-users"></i> Manage Workers</h1>
                <p class="lead">Your agency's worker pool</p>
            </div>
            <div class="col-md-4 text-right">
                <button type="button" class="btn btn-primary btn-lg" data-toggle="modal" data-target="#addWorkerModal">
                    <i class="fa fa-plus"></i> Add Worker
                </button>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            <i class="fa fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            <i class="fa fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    <!-- Filter Tabs -->
    <ul class="nav nav-tabs filter-tabs">
        <li class="{{ $status == 'active' ? 'active' : '' }}">
            <a href="{{ url('agency/workers?status=active') }}">
                Active Workers
            </a>
        </li>
        <li class="{{ $status == 'inactive' ? 'active' : '' }}">
            <a href="{{ url('agency/workers?status=inactive') }}">
                Inactive
            </a>
        </li>
        <li class="{{ $status == 'removed' ? 'active' : '' }}">
            <a href="{{ url('agency/workers?status=removed') }}">
                Removed
            </a>
        </li>
        <li class="{{ $status == 'all' ? 'active' : '' }}">
            <a href="{{ url('agency/workers?status=all') }}">
                All Workers
            </a>
        </li>
    </ul>

    <!-- Workers List -->
    @if($workers->count() > 0)
        @foreach($workers as $agencyWorker)
            @php
                $worker = $agencyWorker->worker;
            @endphp
            <div class="worker-card">
                <div class="row">
                    <div class="col-md-1">
                        <img src="{{ Helper::getFile(config('path.avatar').$worker->avatar) }}"
                             alt="{{ $worker->name }}"
                             class="worker-avatar">
                    </div>
                    <div class="col-md-7 overflow-hidden">
                        <h4 style="margin-top: 0;" class="flex flex-wrap items-center gap-1">
                            <a href="{{ url($worker->username) }}" class="truncate max-w-[200px]" title="{{ $worker->name }}">{{ $worker->name }}</a>
                            @if($worker->is_verified_worker)
                                <span class="label label-success flex-shrink-0"><i class="fa fa-check-circle"></i> Verified</span>
                            @endif
                            @if($agencyWorker->status !== 'active')
                                <span class="label label-default flex-shrink-0">{{ ucfirst($agencyWorker->status) }}</span>
                            @endif
                        </h4>

                        <p style="margin: 5px 0;">
                            <span class="stat-inline">
                                <i class="fa fa-calendar-check"></i>
                                <strong>{{ $agencyWorker->shifts_completed }}</strong> shifts completed
                            </span>
                            <span class="stat-inline">
                                <i class="fa fa-clock"></i>
                                <strong>{{ $agencyWorker->current_assignments }}</strong> active
                            </span>
                            @if($worker->rating_as_worker)
                                <span class="stat-inline">
                                    <i class="fa fa-star" style="color: #ffc107;"></i>
                                    <strong>{{ number_format($worker->rating_as_worker, 1) }}</strong>
                                </span>
                            @endif
                        </p>

                        @if($worker->skills && $worker->skills->count() > 0)
                            <p style="margin: 10px 0 0 0;">
                                @foreach($worker->skills->take(5) as $skill)
                                    <span class="skill-badge">{{ $skill->name }}</span>
                                @endforeach
                                @if($worker->skills->count() > 5)
                                    <span class="skill-badge">+{{ $worker->skills->count() - 5 }} more</span>
                                @endif
                            </p>
                        @endif

                        @if($agencyWorker->notes)
                            <p style="margin: 10px 0 0 0; color: #666;" class="truncate max-w-full" title="{{ $agencyWorker->notes }}">
                                <small><i class="fa fa-sticky-note"></i> {{ $agencyWorker->notes }}</small>
                            </p>
                        @endif
                    </div>
                    <div class="col-md-4 text-right">
                        <p style="margin: 0 0 10px 0;">
                            <strong>Commission Rate:</strong> {{ $agencyWorker->commission_rate }}%
                        </p>
                        <p style="margin: 0 0 15px 0; color: #999;">
                            <small>Added {{ \Carbon\Carbon::parse($agencyWorker->added_at)->diffForHumans() }}</small>
                        </p>

                        <div class="btn-group">
                            <a href="{{ url($worker->username) }}" class="btn btn-default btn-sm">
                                <i class="fa fa-eye"></i> View Profile
                            </a>
                            @if($agencyWorker->status === 'active')
                                <a href="{{ url('messages/new?to='.$worker->id) }}" class="btn btn-default btn-sm">
                                    <i class="fa fa-envelope"></i> Message
                                </a>
                                <button type="button" class="btn btn-default btn-sm" onclick="editWorker({{ $agencyWorker->id }}, {{ $agencyWorker->commission_rate }}, '{{ $agencyWorker->notes }}')">
                                    <i class="fa fa-edit"></i> Edit
                                </button>
                                <form action="{{ url('agency/workers/'.$worker->id.'/remove') }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this worker from your agency?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fa fa-trash"></i> Remove
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Pagination -->
        <div class="text-center">
            {{ $workers->appends(['status' => $status])->links() }}
        </div>
    @else
        <div class="panel panel-default">
            <div class="panel-body text-center" style="padding: 60px;">
                <i class="fa fa-users fa-4x text-muted"></i>
                <h3 style="margin-top: 20px;">No Workers Found</h3>
                <p class="text-muted">
                    @if($status == 'active')
                        You don't have any active workers yet.
                    @elseif($status == 'inactive')
                        No inactive workers.
                    @elseif($status == 'removed')
                        No removed workers.
                    @else
                        You don't have any workers yet.
                    @endif
                </p>
                <button type="button" class="btn btn-primary btn-lg" data-toggle="modal" data-target="#addWorkerModal">
                    <i class="fa fa-plus"></i> Add Your First Worker
                </button>
            </div>
        </div>
    @endif
</div>

<!-- Add Worker Modal - Mobile Optimized -->
<div class="modal fade" id="addWorkerModal" tabindex="-1" role="dialog" aria-labelledby="addWorkerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content max-h-[100vh] sm:max-h-[90vh] flex flex-col rounded-none sm:rounded-lg overflow-hidden">
            <form action="{{ url('agency/workers/add') }}" method="POST">
                @csrf
                <div class="modal-header flex-shrink-0 px-4 py-3 sm:px-5 sm:py-4 border-b border-gray-200 bg-white flex items-center justify-between">
                    <h4 class="modal-title text-lg font-semibold text-gray-900 m-0 flex items-center gap-2" id="addWorkerModalLabel">
                        <i class="fa fa-plus"></i> Add Worker
                    </h4>
                    <button
                        type="button"
                        class="close min-h-[44px] min-w-[44px] sm:min-h-[36px] sm:min-w-[36px] flex items-center justify-center text-gray-400 hover:text-gray-500 active:text-gray-600 touch-manipulation rounded-lg hover:bg-gray-100 -mr-2 transition-colors"
                        data-dismiss="modal"
                        aria-label="Close"
                    >
                        <span aria-hidden="true" class="text-2xl leading-none">&times;</span>
                    </button>
                </div>
                <div class="modal-body flex-1 overflow-y-auto overscroll-contain px-4 py-4 sm:px-5 bg-white">
                    <div class="form-group mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Worker Email or Username <span class="text-danger">*</span></label>
                        <input type="text" name="worker_identifier" class="form-control min-h-[44px] sm:min-h-[40px] text-base sm:text-sm touch-manipulation" required placeholder="Enter worker's email or username" autocomplete="off">
                        <small class="help-block text-gray-500 mt-1 block">The worker must already have an account on OvertimeStaff</small>
                    </div>

                    <div class="form-group mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Commission Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" name="commission_rate" class="form-control min-h-[44px] sm:min-h-[40px] text-base sm:text-sm touch-manipulation" required min="0" max="100" step="0.1" value="15" inputmode="decimal">
                        <small class="help-block text-gray-500 mt-1 block">Percentage of earnings you'll receive as commission</small>
                    </div>

                    <div class="form-group mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" class="form-control min-h-[88px] text-base sm:text-sm touch-manipulation resize-none" rows="3" placeholder="Any notes about this worker..."></textarea>
                    </div>

                    <div class="alert alert-info flex items-start gap-2">
                        <i class="fa fa-info-circle mt-0.5 flex-shrink-0"></i>
                        <span><strong>Note:</strong> The worker will be notified and must accept your invitation before being added to your agency.</span>
                    </div>
                </div>
                <div class="modal-footer flex-shrink-0 px-4 py-3 sm:px-5 sm:py-4 border-t border-gray-200 bg-gray-50 flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 justify-end pb-[calc(0.75rem+env(safe-area-inset-bottom))] sm:pb-4">
                    <button type="button" class="btn btn-default w-full sm:w-auto min-h-[44px] sm:min-h-[40px] touch-manipulation" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary w-full sm:w-auto min-h-[44px] sm:min-h-[40px] touch-manipulation flex items-center justify-center gap-2">
                        <i class="fa fa-plus"></i> Add Worker
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Worker Modal - Mobile Optimized -->
<div class="modal fade" id="editWorkerModal" tabindex="-1" role="dialog" aria-labelledby="editWorkerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content max-h-[100vh] sm:max-h-[90vh] flex flex-col rounded-none sm:rounded-lg overflow-hidden">
            <form action="" method="POST" id="editWorkerForm">
                @csrf
                @method('PUT')
                <div class="modal-header flex-shrink-0 px-4 py-3 sm:px-5 sm:py-4 border-b border-gray-200 bg-white flex items-center justify-between">
                    <h4 class="modal-title text-lg font-semibold text-gray-900 m-0 flex items-center gap-2" id="editWorkerModalLabel">
                        <i class="fa fa-edit"></i> Edit Worker
                    </h4>
                    <button
                        type="button"
                        class="close min-h-[44px] min-w-[44px] sm:min-h-[36px] sm:min-w-[36px] flex items-center justify-center text-gray-400 hover:text-gray-500 active:text-gray-600 touch-manipulation rounded-lg hover:bg-gray-100 -mr-2 transition-colors"
                        data-dismiss="modal"
                        aria-label="Close"
                    >
                        <span aria-hidden="true" class="text-2xl leading-none">&times;</span>
                    </button>
                </div>
                <div class="modal-body flex-1 overflow-y-auto overscroll-contain px-4 py-4 sm:px-5 bg-white">
                    <div class="form-group mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Commission Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" name="commission_rate" id="editCommissionRate" class="form-control min-h-[44px] sm:min-h-[40px] text-base sm:text-sm touch-manipulation" required min="0" max="100" step="0.1" inputmode="decimal">
                    </div>

                    <div class="form-group mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" id="editNotes" class="form-control min-h-[88px] text-base sm:text-sm touch-manipulation resize-none" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer flex-shrink-0 px-4 py-3 sm:px-5 sm:py-4 border-t border-gray-200 bg-gray-50 flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 justify-end pb-[calc(0.75rem+env(safe-area-inset-bottom))] sm:pb-4">
                    <button type="button" class="btn btn-default w-full sm:w-auto min-h-[44px] sm:min-h-[40px] touch-manipulation" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary w-full sm:w-auto min-h-[44px] sm:min-h-[40px] touch-manipulation flex items-center justify-center gap-2">
                        <i class="fa fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
function editWorker(workerId, commissionRate, notes) {
    document.getElementById('editWorkerForm').action = '{{ url("agency/workers") }}/' + workerId + '/edit';
    document.getElementById('editCommissionRate').value = commissionRate;
    document.getElementById('editNotes').value = notes || '';
    $('#editWorkerModal').modal('show');
}
</script>
@endsection
