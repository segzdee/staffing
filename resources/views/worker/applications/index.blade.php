@extends('layouts.authenticated')

@section('css')
<style>
.application-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s;
}

.application-card:hover {
    border-color: #667eea;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.status-badge {
    font-size: 14px;
    padding: 8px 15px;
}
</style>
@endsection

@section('content')
<div class="container" style="margin-top: 30px;">
    <div class="page-header">
        <h1><i class="fa fa-file-alt"></i> My Applications</h1>
        <p class="lead">Track your shift applications</p>
    </div>

    <!-- Filter Tabs -->
    <ul class="nav nav-tabs" style="margin-bottom: 20px;">
        <li class="{{ $status == 'all' ? 'active' : '' }}">
            <a href="{{ url('worker/applications?status=all') }}">All Applications</a>
        </li>
        <li class="{{ $status == 'pending' ? 'active' : '' }}">
            <a href="{{ url('worker/applications?status=pending') }}">Pending</a>
        </li>
        <li class="{{ $status == 'accepted' ? 'active' : '' }}">
            <a href="{{ url('worker/applications?status=accepted') }}">Accepted</a>
        </li>
        <li class="{{ $status == 'rejected' ? 'active' : '' }}">
            <a href="{{ url('worker/applications?status=rejected') }}">Rejected</a>
        </li>
    </ul>

    @if($applications->count() > 0)
        @foreach($applications as $application)
            @php
                $shift = $application->shift;
            @endphp
            <div class="application-card">
                <div class="row">
                    <div class="col-md-8">
                        <h4 style="margin-top: 0;">
                            <a href="{{ url('shifts/'.$shift->id) }}">{{ $shift->title }}</a>
                        </h4>

                        <p style="margin: 5px 0;">
                            <span class="label label-primary">{{ ucfirst($shift->industry) }}</span>
                            @if($shift->urgency_level !== 'normal')
                                <span class="label label-danger">{{ ucfirst($shift->urgency_level) }}</span>
                            @endif
                        </p>

                        <p style="margin: 10px 0; color: #666;">
                            <i class="fa fa-calendar"></i>
                            {{ \Carbon\Carbon::parse($shift->shift_date)->format('l, F d, Y') }}
                            <br>
                            <i class="fa fa-clock"></i>
                            {{ $shift->start_time }} - {{ $shift->end_time }} ({{ $shift->duration_hours }} hours)
                            <br>
                            <i class="fa fa-map-marker"></i>
                            {{ $shift->location_address }}, {{ $shift->location_city }}, {{ $shift->location_state }}
                        </p>

                        <p style="margin: 10px 0; color: #999;">
                            <small>
                                <strong>Applied:</strong> {{ \Carbon\Carbon::parse($application->created_at)->format('M d, Y g:i A') }}
                                ({{ \Carbon\Carbon::parse($application->created_at)->diffForHumans() }})
                            </small>
                        </p>

                        @if($application->cover_letter)
                            <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                                <strong>Your Message:</strong>
                                <p style="margin: 5px 0;">{{ $application->cover_letter }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="col-md-4 text-right">
                        <span class="badge status-badge badge-{{ $application->status == 'accepted' ? 'success' : ($application->status == 'rejected' ? 'danger' : 'warning') }}">
                            {{ ucfirst($application->status) }}
                        </span>

                        <div style="margin-top: 20px;">
                            <h3 style="color: #28a745; margin: 0;">
                                {{ Helper::amountFormatDecimal($shift->final_rate) }}/hr
                            </h3>
                            <p style="margin: 5px 0; color: #666;">
                                Est. {{ Helper::amountFormatDecimal($shift->final_rate * $shift->duration_hours) }}
                            </p>
                        </div>

                        <div style="margin-top: 20px;">
                            <a href="{{ url('shifts/'.$shift->id) }}" class="btn btn-default btn-sm btn-block">
                                <i class="fa fa-eye"></i> View Shift
                            </a>

                            @if($application->status === 'pending')
                                <form action="{{ url('worker/applications/'.$application->id.'/withdraw') }}" method="POST" style="margin-top: 5px;" onsubmit="return confirm('Are you sure you want to withdraw this application?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm btn-block">
                                        <i class="fa fa-times"></i> Withdraw Application
                                    </button>
                                </form>
                            @endif

                            @if($application->status === 'accepted')
                                <a href="{{ url('worker/assignments') }}" class="btn btn-primary btn-sm btn-block" style="margin-top: 5px;">
                                    <i class="fa fa-calendar-check"></i> View Assignment
                                </a>
                            @endif
                        </div>

                        @if($application->status === 'rejected' && $application->rejection_reason)
                            <div style="margin-top: 15px; padding: 10px; background: #f8d7da; border-radius: 4px;">
                                <small><strong>Reason:</strong> {{ $application->rejection_reason }}</small>
                            </div>
                        @endif

                        @if($application->status === 'pending')
                            <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 4px;">
                                <small>
                                    <i class="fa fa-clock"></i>
                                    Awaiting review by business
                                </small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Pagination -->
        <div class="text-center">
            {{ $applications->appends(['status' => $status])->links() }}
        </div>
    @else
        <div class="panel panel-default">
            <div class="panel-body text-center" style="padding: 60px;">
                <i class="fa fa-file-alt fa-4x text-muted"></i>
                <h3 style="margin-top: 20px;">No Applications Found</h3>
                <p class="text-muted">
                    @if($status == 'all')
                        You haven't applied to any shifts yet.
                    @else
                        No {{ $status }} applications.
                    @endif
                </p>
                <a href="{{ url('shifts') }}" class="btn btn-primary btn-lg">
                    <i class="fa fa-search"></i> Browse Available Shifts
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
