@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<style>
.assignment-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s;
}

.assignment-card:hover {
    border-color: #667eea;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.status-badge {
    font-size: 14px;
    padding: 8px 15px;
}

.worker-info {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.worker-avatar-small {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 10px;
}

.shift-details {
    color: #666;
    margin: 5px 0;
}

.payment-status {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}

.payment-status.pending {
    background: #fff3cd;
    color: #856404;
}

.payment-status.in_escrow {
    background: #d1ecf1;
    color: #0c5460;
}

.payment-status.released {
    background: #d4edda;
    color: #155724;
}
</style>
@endsection

@section('content')
<div class="container" style="margin-top: 30px;">
    <div class="page-header">
        <h1><i class="fa fa-calendar-check"></i> Shift Assignments</h1>
        <p class="lead">All shift assignments for your workers</p>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="{{ url('agency/assignments?status=all') }}" class="filter-tab {{ $status == 'all' ? 'active' : '' }}">All Assignments</a>
        <a href="{{ url('agency/assignments?status=assigned') }}" class="filter-tab {{ $status == 'assigned' ? 'active' : '' }}">Assigned</a>
        <a href="{{ url('agency/assignments?status=in_progress') }}" class="filter-tab {{ $status == 'in_progress' ? 'active' : '' }}">In Progress</a>
        <a href="{{ url('agency/assignments?status=completed') }}" class="filter-tab {{ $status == 'completed' ? 'active' : '' }}">Completed</a>
        <a href="{{ url('agency/assignments?status=cancelled') }}" class="filter-tab {{ $status == 'cancelled' ? 'active' : '' }}">Cancelled</a>
    </div>

    @if($assignments->count() > 0)
        @foreach($assignments as $assignment)
            @php
                $shift = $assignment->shift;
                $worker = $assignment->worker;
                $payment = $assignment->shiftPayment;
            @endphp
            <div class="assignment-card">
                <div class="row">
                    <div class="col-md-8">
                        <!-- Worker Info -->
                        <div class="worker-info">
                            <img src="{{ Helper::getFile(config('path.avatar').$worker->avatar) }}"
                                 alt="{{ $worker->name }}"
                                 class="worker-avatar-small">
                            <div>
                                <strong>{{ $worker->name }}</strong>
                                @if($worker->is_verified_worker)
                                    <span class="label label-success label-xs"><i class="fa fa-check"></i></span>
                                @endif
                            </div>
                        </div>

                        <!-- Shift Details -->
                        <h4 style="margin: 10px 0;">
                            <a href="{{ url('shifts/'.$shift->id) }}">
                                {{ $shift->title }}
                            </a>
                        </h4>

                        <div class="shift-details">
                            <p style="margin: 5px 0;">
                                <span class="label label-primary">{{ ucfirst($shift->industry) }}</span>
                            </p>
                            <p style="margin: 5px 0;">
                                <i class="fa fa-calendar"></i>
                                {{ \Carbon\Carbon::parse($shift->shift_date)->format('l, F d, Y') }}
                            </p>
                            <p style="margin: 5px 0;">
                                <i class="fa fa-clock"></i>
                                {{ $shift->start_time }} - {{ $shift->end_time }}
                                ({{ $shift->duration_hours }} hours)
                            </p>
                            <p style="margin: 5px 0;">
                                <i class="fa fa-map-marker"></i>
                                {{ $shift->location_address }}, {{ $shift->location_city }}, {{ $shift->location_state }}
                            </p>
                        </div>

                        <!-- Assignment Details -->
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                            <p style="margin: 5px 0; color: #666;">
                                <strong>Assigned:</strong> {{ \Carbon\Carbon::parse($assignment->assigned_at)->format('M d, Y g:i A') }}
                            </p>
                            @if($assignment->checked_in_at)
                                <p style="margin: 5px 0; color: #666;">
                                    <strong>Checked In:</strong> {{ \Carbon\Carbon::parse($assignment->checked_in_at)->format('M d, Y g:i A') }}
                                </p>
                            @endif
                            @if($assignment->checked_out_at)
                                <p style="margin: 5px 0; color: #666;">
                                    <strong>Checked Out:</strong> {{ \Carbon\Carbon::parse($assignment->checked_out_at)->format('M d, Y g:i A') }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-4 text-right">
                        <!-- Status Badge -->
                        <span class="badge status-badge badge-{{ $assignment->status == 'completed' ? 'success' : ($assignment->status == 'in_progress' ? 'warning' : ($assignment->status == 'cancelled' ? 'danger' : 'primary')) }}">
                            {{ ucfirst(str_replace('_', ' ', $assignment->status)) }}
                        </span>

                        <!-- Payment Info -->
                        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <h4 style="color: #667eea; margin-top: 0;">
                                {{ Helper::amountFormatDecimal($shift->final_rate) }}/hr
                            </h4>
                            @if($payment)
                                <p style="margin: 5px 0; font-size: 12px;">
                                    Worker: {{ Helper::amountFormatDecimal($payment->worker_amount) }}
                                </p>
                                <p style="margin: 5px 0; font-size: 12px;">
                                    Commission: {{ Helper::amountFormatDecimal($payment->agency_commission) }}
                                </p>
                                <p style="margin: 10px 0 0 0;">
                                    <span class="payment-status {{ $payment->status }}">
                                        {{ ucfirst(str_replace('_', ' ', $payment->status)) }}
                                    </span>
                                </p>
                            @else
                                <p style="margin: 5px 0; font-size: 12px; color: #999;">
                                    Payment pending
                                </p>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div style="margin-top: 15px;">
                            <a href="{{ url('shifts/'.$shift->id) }}" class="btn btn-default btn-sm btn-block">
                                <i class="fa fa-eye"></i> View Shift
                            </a>
                            <a href="{{ url($worker->username) }}" class="btn btn-default btn-sm btn-block">
                                <i class="fa fa-user"></i> View Worker
                            </a>
                            @if($assignment->status === 'assigned')
                                <a href="{{ url('messages/new?to='.$worker->id.'&shift_id='.$shift->id) }}" class="btn btn-default btn-sm btn-block">
                                    <i class="fa fa-envelope"></i> Message Worker
                                </a>
                            @endif
                        </div>

                        <!-- Time Until Shift -->
                        @if($assignment->status === 'assigned' && \Carbon\Carbon::parse($shift->shift_date.' '.$shift->start_time)->isFuture())
                            <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 4px;">
                                <small>
                                    <i class="fa fa-clock"></i>
                                    Starts {{ \Carbon\Carbon::parse($shift->shift_date.' '.$shift->start_time)->diffForHumans() }}
                                </small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Pagination -->
        <div class="text-center">
            {{ $assignments->appends(['status' => $status])->links() }}
        </div>
    @else
        <div class="panel panel-default">
            <div class="panel-body text-center" style="padding: 60px;">
                <i class="fa fa-calendar-times fa-4x text-muted"></i>
                <h3 style="margin-top: 20px;">No Assignments Found</h3>
                <p class="text-muted">
                    @if($status == 'all')
                        Your workers don't have any assignments yet.
                    @else
                        No {{ $status }} assignments.
                    @endif
                </p>
                <a href="{{ url('agency/shifts/browse') }}" class="btn btn-primary btn-lg">
                    <i class="fa fa-search"></i> Browse Available Shifts
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
