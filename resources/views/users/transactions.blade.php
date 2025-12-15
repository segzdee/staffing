@extends('layouts.authenticated')

@section('css')
<style>
.transaction-item {
    padding: 15px;
    border-bottom: 1px solid #e0e0e0;
    transition: background-color 0.3s;
}

.transaction-item:hover {
    background-color: #f9f9f9;
}

.amount-positive {
    color: #28a745;
    font-weight: bold;
}

.amount-negative {
    color: #dc3545;
    font-weight: bold;
}

.stat-card {
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}
</style>
@endsection

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h2><i class="fa fa-file-invoice-dollar"></i> My Transactions</h2>
            <hr>

            <!-- Statistics Cards -->
            <div class="row">
                @if(auth()->user()->user_type === 'worker')
                <div class="col-md-3">
                    <div class="stat-card bg-success text-white">
                        <h4>{{ Helper::amountFormatDecimal($stats['total_earned']) }}</h4>
                        <p>Total Earned</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-warning text-white">
                        <h4>{{ Helper::amountFormatDecimal($stats['pending_payments']) }}</h4>
                        <p>Pending Payments</p>
                    </div>
                </div>
                @endif

                @if(auth()->user()->user_type === 'business')
                <div class="col-md-3">
                    <div class="stat-card bg-danger text-white">
                        <h4>{{ Helper::amountFormatDecimal($stats['total_spent']) }}</h4>
                        <p>Total Spent</p>
                    </div>
                </div>
                @endif

                <div class="col-md-3">
                    <div class="stat-card bg-info text-white">
                        <h4>{{ Helper::amountFormatDecimal($stats['total_deposits']) }}</h4>
                        <p>Total Deposits</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card bg-primary text-white">
                        <h4>{{ Helper::amountFormatDecimal($stats['total_withdrawals']) }}</h4>
                        <p>Total Withdrawals</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4><i class="fa fa-filter"></i> Filters</h4>
                </div>
                <div class="panel-body">
                    <form method="GET" action="{{ url('my/transactions') }}" class="form-inline">
                        <div class="form-group">
                            <label>Type:</label>
                            <select name="type" class="form-control">
                                <option value="all" {{ $type == 'all' ? 'selected' : '' }}>All Types</option>
                                <option value="shift_payments" {{ $type == 'shift_payments' ? 'selected' : '' }}>Shift Payments</option>
                                <option value="deposits" {{ $type == 'deposits' ? 'selected' : '' }}>Deposits</option>
                                <option value="withdrawals" {{ $type == 'withdrawals' ? 'selected' : '' }}>Withdrawals</option>
                                <option value="legacy" {{ $type == 'legacy' ? 'selected' : '' }}>Legacy</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>From:</label>
                            <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                        </div>

                        <div class="form-group">
                            <label>To:</label>
                            <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-search"></i> Filter
                        </button>
                        <a href="{{ url('my/transactions') }}" class="btn btn-default">
                            <i class="fa fa-refresh"></i> Clear
                        </a>
                        <a href="{{ url('my/transactions/export') }}" class="btn btn-success pull-right">
                            <i class="fa fa-download"></i> Export CSV
                        </a>
                    </form>
                </div>
            </div>

            <!-- Transactions List -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4>Transaction History ({{ $allTransactions->count() }} total)</h4>
                </div>
                <div class="panel-body" style="padding: 0;">
                    @if($paginatedTransactions->count() > 0)
                        @foreach($paginatedTransactions as $transaction)
                            <div class="transaction-item">
                                <div class="row">
                                    <div class="col-md-2">
                                        <span class="badge badge-{{
                                            $transaction['type'] == 'shift_payment' ? 'primary' :
                                            ($transaction['type'] == 'deposit' ? 'success' :
                                            ($transaction['type'] == 'withdrawal' ? 'warning' : 'default'))
                                        }}">
                                            {{ ucfirst(str_replace('_', ' ', $transaction['type'])) }}
                                        </span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>{{ $transaction['description'] }}</strong>
                                        @if(isset($transaction['shift_title']))
                                            <br>
                                            <small class="text-muted">{{ $transaction['shift_title'] }}</small>
                                        @endif
                                    </div>
                                    <div class="col-md-2">
                                        <span class="{{ $transaction['amount'] >= 0 ? 'amount-positive' : 'amount-negative' }}">
                                            {{ $transaction['amount'] >= 0 ? '+' : '' }}{{ Helper::amountFormatDecimal($transaction['amount']) }}
                                        </span>
                                    </div>
                                    <div class="col-md-2">
                                        <span class="label label-{{
                                            $transaction['status'] == 'paid_out' || $transaction['status'] == 'active' || $transaction['status'] == 'completed' ? 'success' :
                                            ($transaction['status'] == 'pending' || $transaction['status'] == 'in_escrow' ? 'warning' : 'default')
                                        }}">
                                            {{ ucfirst(str_replace('_', ' ', $transaction['status'])) }}
                                        </span>
                                    </div>
                                    <div class="col-md-2 text-right">
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($transaction['created_at'])->format('M d, Y g:i A') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <!-- Pagination -->
                        <div class="text-center" style="padding: 20px;">
                            @php
                                $currentPage = request()->get('page', 1);
                                $perPage = 20;
                                $total = $allTransactions->count();
                                $lastPage = ceil($total / $perPage);
                            @endphp

                            @if($lastPage > 1)
                                <nav>
                                    <ul class="pagination">
                                        @if($currentPage > 1)
                                            <li><a href="{{ url('my/transactions?page=' . ($currentPage - 1) . '&type=' . $type . '&date_from=' . $dateFrom . '&date_to=' . $dateTo) }}">&laquo; Previous</a></li>
                                        @endif

                                        @for($i = 1; $i <= $lastPage; $i++)
                                            <li class="{{ $i == $currentPage ? 'active' : '' }}">
                                                <a href="{{ url('my/transactions?page=' . $i . '&type=' . $type . '&date_from=' . $dateFrom . '&date_to=' . $dateTo) }}">{{ $i }}</a>
                                            </li>
                                        @endfor

                                        @if($currentPage < $lastPage)
                                            <li><a href="{{ url('my/transactions?page=' . ($currentPage + 1) . '&type=' . $type . '&date_from=' . $dateFrom . '&date_to=' . $dateTo) }}">Next &raquo;</a></li>
                                        @endif
                                    </ul>
                                </nav>
                            @endif
                        </div>
                    @else
                        <div class="text-center" style="padding: 40px;">
                            <i class="fa fa-file-invoice fa-3x text-muted"></i>
                            <p style="margin-top: 20px; font-size: 16px;">No transactions found</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
