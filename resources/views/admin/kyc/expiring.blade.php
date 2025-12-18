@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Expiring Verifications
            <small>WKR-001: Document Expiry Management</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="{{ route('dashboard.admin.kyc.index') }}">KYC Verifications</a></li>
            <li class="active">Expiring</li>
        </ol>
    </section>

    <section class="content">
        {{-- Filter by days --}}
        <div class="box box-default">
            <div class="box-body">
                <form action="{{ route('dashboard.admin.kyc.expiring') }}" method="GET" class="form-inline">
                    <div class="form-group" style="margin-right: 15px;">
                        <label for="days">Show expiring within:</label>
                        <select name="days" id="days" class="form-control" style="margin-left: 10px;">
                            <option value="7" {{ $days == 7 ? 'selected' : '' }}>7 days</option>
                            <option value="14" {{ $days == 14 ? 'selected' : '' }}>14 days</option>
                            <option value="30" {{ $days == 30 ? 'selected' : '' }}>30 days</option>
                            <option value="60" {{ $days == 60 ? 'selected' : '' }}>60 days</option>
                            <option value="90" {{ $days == 90 ? 'selected' : '' }}>90 days</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-filter"></i> Update
                    </button>
                </form>
            </div>
        </div>

        {{-- Expiring Verifications Table --}}
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-clock-o"></i>
                    Verifications Expiring Within {{ $days }} Days
                </h3>
                <span class="label label-warning pull-right">{{ $verifications->total() }} total</span>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Document Type</th>
                            <th>Country</th>
                            <th>Document Expiry</th>
                            <th>Verification Expiry</th>
                            <th>Days Remaining</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($verifications as $verification)
                        @php
                            $expiryDate = $verification->document_expiry ?? $verification->expires_at;
                            $daysRemaining = $expiryDate ? now()->diffInDays($expiryDate, false) : null;
                        @endphp
                        <tr class="{{ $daysRemaining !== null && $daysRemaining <= 7 ? 'danger' : ($daysRemaining !== null && $daysRemaining <= 14 ? 'warning' : '') }}">
                            <td>{{ $verification->id }}</td>
                            <td>
                                <strong>{{ $verification->user->name ?? 'Unknown' }}</strong>
                                <br>
                                <small class="text-muted">{{ $verification->user->email ?? '' }}</small>
                            </td>
                            <td>{{ $verification->document_type_name }}</td>
                            <td><span class="label label-default">{{ $verification->document_country }}</span></td>
                            <td>
                                @if($verification->document_expiry)
                                    {{ $verification->document_expiry->format('M d, Y') }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($verification->expires_at)
                                    {{ $verification->expires_at->format('M d, Y') }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($daysRemaining !== null)
                                    @if($daysRemaining <= 0)
                                        <span class="label label-danger">Expired</span>
                                    @elseif($daysRemaining <= 7)
                                        <span class="label label-danger">{{ $daysRemaining }} day(s)</span>
                                    @elseif($daysRemaining <= 14)
                                        <span class="label label-warning">{{ $daysRemaining }} day(s)</span>
                                    @else
                                        <span class="label label-info">{{ $daysRemaining }} day(s)</span>
                                    @endif
                                @else
                                    <span class="text-muted">Unknown</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('dashboard.admin.kyc.show', $verification->id) }}" class="btn btn-xs btn-primary">
                                    <i class="fa fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                <i class="fa fa-check-circle fa-2x" style="margin-bottom: 10px;"></i>
                                <br>
                                No verifications expiring within {{ $days }} days.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="box-footer clearfix">
                {{ $verifications->appends(['days' => $days])->links() }}
            </div>
        </div>

        {{-- Info Box --}}
        <div class="callout callout-info">
            <h4><i class="fa fa-info-circle"></i> About Expiring Verifications</h4>
            <p>
                Workers with expiring verifications will receive reminder notifications at {{ config('kyc.expiry_warning_days', 30) }} days before expiry.
                Once expired, workers will need to submit a new verification to continue working.
            </p>
        </div>

        <a href="{{ route('dashboard.admin.kyc.index') }}" class="btn btn-default">
            <i class="fa fa-arrow-left"></i> Back to Review Queue
        </a>
    </section>
</div>
@endsection
