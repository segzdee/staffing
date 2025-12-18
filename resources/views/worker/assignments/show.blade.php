@extends('layouts.authenticated')

@section('title', 'Assignment Details')

@section('content')
<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('worker.assignments.index') }}">Assignments</a></li>
            <li class="breadcrumb-item active" aria-current="page">Assignment #{{ $assignment->id }}</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Assignment Details</h5>
                    <span class="badge bg-{{ $assignment->status === 'completed' ? 'success' : ($assignment->status === 'assigned' ? 'primary' : ($assignment->status === 'checked_in' ? 'warning' : 'secondary')) }} fs-6">
                        {{ ucfirst(str_replace('_', ' ', $assignment->status)) }}
                    </span>
                </div>
                <div class="card-body">
                    @if($assignment->shift)
                        <h4>{{ $assignment->shift->title }}</h4>
                        <p class="text-muted mb-4">
                            @if($assignment->shift->business)
                                <i class="fas fa-building me-1"></i>{{ $assignment->shift->business->name }}
                            @endif
                        </p>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6><i class="far fa-calendar me-2"></i>Date & Time</h6>
                                <p class="mb-1">{{ \Carbon\Carbon::parse($assignment->shift->shift_date)->format('l, F j, Y') }}</p>
                                <p>{{ \Carbon\Carbon::parse($assignment->shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($assignment->shift->end_time)->format('g:i A') }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-map-marker-alt me-2"></i>Location</h6>
                                <p class="mb-1">{{ $assignment->shift->location_name ?? 'N/A' }}</p>
                                <p class="text-muted">
                                    {{ $assignment->shift->location_address ?? '' }}
                                    @if($assignment->shift->location_city)
                                        <br>{{ $assignment->shift->location_city }}, {{ $assignment->shift->location_state }} {{ $assignment->shift->location_zip ?? '' }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6><i class="fas fa-dollar-sign me-2"></i>Pay Rate</h6>
                                <p class="h5 text-success">${{ number_format($assignment->shift->hourly_rate, 2) }}/hr</p>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-clock me-2"></i>Estimated Hours</h6>
                                <p>
                                    @php
                                        $start = \Carbon\Carbon::parse($assignment->shift->start_time);
                                        $end = \Carbon\Carbon::parse($assignment->shift->end_time);
                                        $hours = $end->diffInHours($start);
                                    @endphp
                                    {{ $hours }} hours (Est. ${{ number_format($hours * $assignment->shift->hourly_rate, 2) }})
                                </p>
                            </div>
                        </div>

                        @if($assignment->shift->description)
                            <h6><i class="fas fa-info-circle me-2"></i>Description</h6>
                            <p>{{ $assignment->shift->description }}</p>
                        @endif
                    @endif
                </div>
            </div>

            @if($assignment->shift && $assignment->shift->attachments && $assignment->shift->attachments->count() > 0)
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="fas fa-paperclip me-2"></i>Attachments</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @foreach($assignment->shift->attachments as $attachment)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="fas fa-file me-2"></i>{{ $attachment->filename ?? 'Attachment' }}
                                    </span>
                                    @if($attachment->url)
                                        <a href="{{ $attachment->url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($assignment->status === 'assigned')
                            <form action="{{ route('worker.assignments.checkIn', $assignment->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>Check In
                                </button>
                            </form>
                        @elseif($assignment->status === 'checked_in')
                            <form action="{{ route('worker.assignments.checkOut', $assignment->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="fas fa-sign-out-alt me-2"></i>Check Out
                                </button>
                            </form>
                        @elseif($assignment->status === 'completed' && !$assignment->worker_rated)
                            <a href="{{ route('worker.shifts.rate', $assignment->id) }}" class="btn btn-primary">
                                <i class="fas fa-star me-2"></i>Rate Business
                            </a>
                        @endif

                        @if($assignment->shift && $assignment->shift->business)
                            <a href="{{ route('messages.business', ['business_id' => $assignment->shift->business->id, 'shift_id' => $assignment->shift->id]) }}"
                               class="btn btn-outline-secondary">
                                <i class="fas fa-envelope me-2"></i>Message Business
                            </a>
                        @endif

                        <a href="{{ route('worker.assignments.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Assignments
                        </a>
                    </div>
                </div>
            </div>

            @if($assignment->payment)
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Info</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Status:</strong>
                            <span class="badge bg-{{ $assignment->payment->status === 'released' ? 'success' : ($assignment->payment->status === 'in_escrow' ? 'warning' : 'secondary') }}">
                                {{ ucfirst(str_replace('_', ' ', $assignment->payment->status)) }}
                            </span>
                        </p>
                        <p><strong>Amount:</strong> ${{ number_format($assignment->payment->worker_amount ?? $assignment->payment->amount_net ?? 0, 2) }}</p>
                        @if($assignment->payment->payout_completed_at)
                            <p><strong>Paid On:</strong> {{ \Carbon\Carbon::parse($assignment->payment->payout_completed_at)->format('M d, Y') }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
