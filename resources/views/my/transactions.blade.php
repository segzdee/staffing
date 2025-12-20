@extends('layouts.authenticated')

@section('title') {{ trans('general.transactions') }} -@endsection

@section('css')
<style>
.transactions-container {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.transaction-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s;
}

.transaction-card:hover {
    border-color: #667eea;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.transaction-card.payment {
    border-left: 4px solid #dc3545;
}

.transaction-card.payout {
    border-left: 4px solid #28a745;
}

.transaction-card.escrow {
    border-left: 4px solid #ffc107;
}

.status-badge {
    font-size: 14px;
    padding: 8px 15px;
}

.summary-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.summary-item {
    text-align: center;
}

.summary-item h3 {
    color: white;
    margin: 10px 0 5px 0;
    font-size: 32px;
}

.summary-item p {
    color: rgba(255,255,255,0.9);
    margin: 0;
}
</style>
@endsection

@section('content')
<div class="container" style="margin-top: 30px;">
    <div class="transactions-container">
        <h1 style="margin-top: 0;">
            <i class="bi bi-receipt"></i> {{ trans('general.transactions') }}
        </h1>
        <p class="lead">Your complete payment history</p>

        <!-- Summary Cards -->
        <div class="summary-card">
            <div class="row">
                <div class="col-md-4">
                    <div class="summary-item">
                        @if (auth()->user()->user_type == 'worker')
                            <i class="bi bi-cash-stack fa-2x"></i>
                            <h3>{{ Helper::amountFormatDecimal($totalEarned ?? 0) }}</h3>
                            <p>Total Earned</p>
                        @else
                            <i class="bi bi-credit-card fa-2x"></i>
                            <h3>{{ Helper::amountFormatDecimal($totalSpent ?? 0) }}</h3>
                            <p>Total Spent</p>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="summary-item">
                        <i class="bi bi-hourglass-split fa-2x"></i>
                        <h3>{{ Helper::amountFormatDecimal($pendingAmount ?? 0) }}</h3>
                        <p>In Escrow/Pending</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="summary-item">
                        <i class="bi bi-calendar-check fa-2x"></i>
                        <h3>{{ $completedCount ?? 0 }}</h3>
                        <p>Completed Transactions</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <ul class="nav nav-tabs" style="margin-bottom: 20px;">
            <li class="{{ $filter == 'all' ? 'active' : '' }}">
                <a href="{{ url('my/transactions?filter=all') }}">All Transactions</a>
            </li>
            @if (auth()->user()->user_type == 'worker')
                <li class="{{ $filter == 'payouts' ? 'active' : '' }}">
                    <a href="{{ url('my/transactions?filter=payouts') }}">Payouts</a>
                </li>
                <li class="{{ $filter == 'pending' ? 'active' : '' }}">
                    <a href="{{ url('my/transactions?filter=pending') }}">Pending</a>
                </li>
            @else
                <li class="{{ $filter == 'payments' ? 'active' : '' }}">
                    <a href="{{ url('my/transactions?filter=payments') }}">Payments</a>
                </li>
                <li class="{{ $filter == 'escrow' ? 'active' : '' }}">
                    <a href="{{ url('my/transactions?filter=escrow') }}">In Escrow</a>
                </li>
            @endif
            <li class="{{ $filter == 'disputes' ? 'active' : '' }}">
                <a href="{{ url('my/transactions?filter=disputes') }}">Disputes</a>
            </li>
        </ul>

        <!-- Transactions List -->
        @if($transactions->count() > 0)
            @foreach($transactions as $transaction)
                @php
                    $shift = $transaction->assignment->shift ?? null;
                    $isPayment = $transaction->business_id == auth()->id();
                    $isPayout = $transaction->worker_id == auth()->id();
                @endphp

                <div class="transaction-card {{ $isPayment ? 'payment' : ($isPayout ? 'payout' : 'escrow') }}">
                    <div class="row">
                        <div class="col-md-8">
                            <h4 style="margin-top: 0;">
                                @if ($shift)
                                    <a href="{{ url('shifts/'.$shift->id) }}">{{ $shift->title }}</a>
                                @else
                                    Transaction #{{ $transaction->id }}
                                @endif
                            </h4>

                            <p style="margin: 5px 0;">
                                <!-- Status Badge -->
                                @if ($transaction->status == 'in_escrow')
                                    <span class="label label-warning">In Escrow</span>
                                @elseif ($transaction->status == 'released')
                                    <span class="label label-info">Released</span>
                                @elseif ($transaction->status == 'paid_out')
                                    <span class="label label-success">Paid Out</span>
                                @elseif ($transaction->status == 'disputed')
                                    <span class="label label-danger">Disputed</span>
                                @elseif ($transaction->status == 'failed')
                                    <span class="label label-danger">Failed</span>
                                @else
                                    <span class="label label-default">{{ ucfirst($transaction->status) }}</span>
                                @endif

                                <!-- Disputed Badge -->
                                @if ($transaction->disputed)
                                    <span class="label label-danger"><i class="bi bi-exclamation-triangle"></i> Disputed</span>
                                @endif
                            </p>

                            <!-- Transaction Details -->
                            <div style="margin: 15px 0;">
                                <p style="margin: 5px 0; color: #666;">
                                    <i class="bi bi-calendar"></i>
                                    <strong>Date:</strong> {{ \Carbon\Carbon::parse($transaction->created_at)->format('M d, Y g:i A') }}
                                    ({{ \Carbon\Carbon::parse($transaction->created_at)->diffForHumans() }})
                                </p>

                                @if ($shift)
                                    <p style="margin: 5px 0; color: #666;">
                                        <i class="bi bi-briefcase"></i>
                                        <strong>Shift:</strong> {{ \Carbon\Carbon::parse($shift->shift_date)->format('M d, Y') }}
                                        {{ $shift->start_time }} - {{ $shift->end_time }}
                                    </p>
                                @endif

                                @if ($transaction->assignment && $transaction->assignment->hours_worked)
                                    <p style="margin: 5px 0; color: #666;">
                                        <i class="bi bi-clock"></i>
                                        <strong>Hours Worked:</strong> {{ $transaction->assignment->hours_worked }} hours
                                    </p>
                                @endif

                                @if ($isPayout)
                                    <p style="margin: 5px 0; color: #666;">
                                        <i class="bi bi-building"></i>
                                        <strong>From:</strong> {{ $transaction->business->name ?? 'Unknown' }}
                                    </p>
                                @else
                                    <p style="margin: 5px 0; color: #666;">
                                        <i class="bi bi-person"></i>
                                        <strong>To:</strong> {{ $transaction->worker->name ?? 'Unknown' }}
                                    </p>
                                @endif

                                @if ($transaction->stripe_payment_intent_id)
                                    <p style="margin: 5px 0; color: #999; font-size: 12px;">
                                        <strong>Payment ID:</strong> {{ $transaction->stripe_payment_intent_id }}
                                    </p>
                                @endif
                            </div>

                            <!-- Timeline -->
                            @if ($transaction->escrow_held_at || $transaction->released_at || $transaction->payout_completed_at)
                                <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                                    <strong>Timeline:</strong>
                                    <div style="margin-top: 10px;">
                                        @if ($transaction->escrow_held_at)
                                            <p style="margin: 3px 0; font-size: 13px;">
                                                <i class="bi bi-check-circle text-success"></i>
                                                <strong>Escrow:</strong> {{ \Carbon\Carbon::parse($transaction->escrow_held_at)->format('M d, Y g:i A') }}
                                            </p>
                                        @endif
                                        @if ($transaction->released_at)
                                            <p style="margin: 3px 0; font-size: 13px;">
                                                <i class="bi bi-check-circle text-success"></i>
                                                <strong>Released:</strong> {{ \Carbon\Carbon::parse($transaction->released_at)->format('M d, Y g:i A') }}
                                            </p>
                                        @endif
                                        @if ($transaction->payout_initiated_at)
                                            <p style="margin: 3px 0; font-size: 13px;">
                                                <i class="bi bi-check-circle text-success"></i>
                                                <strong>Payout Initiated:</strong> {{ \Carbon\Carbon::parse($transaction->payout_initiated_at)->format('M d, Y g:i A') }}
                                            </p>
                                        @endif
                                        @if ($transaction->payout_completed_at)
                                            <p style="margin: 3px 0; font-size: 13px;">
                                                <i class="bi bi-check-circle text-success"></i>
                                                <strong>Completed:</strong> {{ \Carbon\Carbon::parse($transaction->payout_completed_at)->format('M d, Y g:i A') }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Dispute Information -->
                            @if ($transaction->disputed && $transaction->dispute_reason)
                                <div style="margin-top: 15px; padding: 10px; background: #f8d7da; border-radius: 4px;">
                                    <strong><i class="bi bi-exclamation-triangle"></i> Dispute Reason:</strong>
                                    <p style="margin: 5px 0;">{{ $transaction->dispute_reason }}</p>
                                    @if ($transaction->disputed_at)
                                        <p style="margin: 5px 0; font-size: 12px;">
                                            Disputed: {{ \Carbon\Carbon::parse($transaction->disputed_at)->format('M d, Y g:i A') }}
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <!-- Amount Column -->
                        <div class="col-md-4 text-right">
                            <div style="padding: 15px; background: #f8f9fa; border-radius: 8px;">
                                <!-- Amount -->
                                @if ($isPayment)
                                    <h3 style="color: #dc3545; margin: 0;">
                                        -{{ Helper::amountFormatDecimal($transaction->amount_gross) }}
                                    </h3>
                                    <p style="margin: 5px 0; color: #666; font-size: 14px;">Payment Made</p>
                                @else
                                    <h3 style="color: #28a745; margin: 0;">
                                        +{{ Helper::amountFormatDecimal($transaction->amount_net) }}
                                    </h3>
                                    <p style="margin: 5px 0; color: #666; font-size: 14px;">Earned</p>
                                @endif

                                <!-- Fee Breakdown -->
                                @if ($transaction->platform_fee)
                                    <p style="margin: 10px 0; font-size: 13px; color: #999;">
                                        Gross: {{ Helper::amountFormatDecimal($transaction->amount_gross) }}<br>
                                        Fee: {{ Helper::amountFormatDecimal($transaction->platform_fee) }}<br>
                                        Net: {{ Helper::amountFormatDecimal($transaction->amount_net) }}
                                    </p>
                                @endif
                            </div>

                            <!-- Actions -->
                            <div style="margin-top: 15px;">
                                @if ($shift)
                                    <a href="{{ url('shifts/'.$shift->id) }}" class="btn btn-default btn-block min-h-[40px] py-2">
                                        <i class="bi bi-eye"></i> View Shift
                                    </a>
                                @endif

                                @if ($transaction->assignment)
                                    <a href="{{ url('worker/assignments') }}" class="btn btn-default btn-block min-h-[40px] py-2">
                                        <i class="bi bi-calendar-check"></i> View Assignment
                                    </a>
                                @endif

                                @if (!$transaction->disputed && in_array($transaction->status, ['in_escrow', 'released']))
                                    <button type="button" class="btn btn-warning btn-block min-h-[40px] py-2"
                                            onclick="openDisputeModal({{ $transaction->id }})">
                                        <i class="bi bi-flag"></i> File Dispute
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Pagination -->
            <div class="text-center">
                {{ $transactions->appends(['filter' => $filter])->links() }}
            </div>
        @else
            <div class="panel panel-default">
                <div class="panel-body text-center" style="padding: 60px;">
                    <i class="bi bi-receipt fa-4x text-muted"></i>
                    <h3 style="margin-top: 20px;">No Transactions Found</h3>
                    <p class="text-muted">
                        @if ($filter == 'all')
                            You don't have any transactions yet.
                        @else
                            No {{ $filter }} transactions found.
                        @endif
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Dispute Modal -->
<div class="modal fade" id="disputeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="" method="POST" id="disputeForm">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close min-h-[40px] min-w-[40px] p-2 flex items-center justify-center" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="bi bi-flag"></i> File Dispute</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>Important:</strong> Filing a dispute will hold the payment until the issue is resolved by our team.
                    </div>

                    <div class="form-group">
                        <label>Reason for Dispute <span class="text-danger">*</span></label>
                        <select name="reason" class="form-control" required>
                            <option value="">Select a reason...</option>
                            <option value="hours_incorrect">Hours worked incorrectly calculated</option>
                            <option value="work_not_completed">Work not completed as agreed</option>
                            <option value="quality_issues">Quality of work issues</option>
                            <option value="no_show">Worker/Business no-show</option>
                            <option value="other">Other (please explain below)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Detailed Explanation <span class="text-danger">*</span></label>
                        <textarea name="explanation" class="form-control" rows="6" required
                                  placeholder="Please provide details about the issue..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default min-h-[40px] py-2 px-4" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning min-h-[40px] py-2 px-4">
                        <i class="bi bi-flag"></i> Submit Dispute
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
function openDisputeModal(transactionId) {
    document.getElementById('disputeForm').action = '{{ url("my/transactions") }}/' + transactionId + '/dispute';
    $('#disputeModal').modal('show');
}
</script>
@endsection
