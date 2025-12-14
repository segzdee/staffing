<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transactions;
use App\Models\ShiftPayment;
use App\Models\Deposits;
use App\Models\Withdrawals;

class TransactionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display user's transaction history
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $type = $request->get('type', 'all');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Base query for legacy transactions
        $transactionsQuery = Transactions::where('user_id', $user->id)
            ->orWhere('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Get shift payments (new shift marketplace)
        $shiftPaymentsQuery = ShiftPayment::where(function($query) use ($user) {
            $query->where('worker_id', $user->id)
                  ->orWhere('business_id', $user->id);
        })->orderBy('created_at', 'desc');

        // Get deposits
        $depositsQuery = Deposits::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Get withdrawals
        $withdrawalsQuery = Withdrawals::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Apply date filters
        if ($dateFrom) {
            $transactionsQuery->whereDate('created_at', '>=', $dateFrom);
            $shiftPaymentsQuery->whereDate('created_at', '>=', $dateFrom);
            $depositsQuery->whereDate('created_at', '>=', $dateFrom);
            $withdrawalsQuery->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $transactionsQuery->whereDate('created_at', '<=', $dateTo);
            $shiftPaymentsQuery->whereDate('created_at', '<=', $dateTo);
            $depositsQuery->whereDate('created_at', '<=', $dateTo);
            $withdrawalsQuery->whereDate('created_at', '<=', $dateTo);
        }

        // Get all transactions based on type filter
        $allTransactions = collect();

        if ($type === 'all' || $type === 'shift_payments') {
            $shiftPayments = $shiftPaymentsQuery->get()->map(function($payment) use ($user) {
                return [
                    'id' => $payment->id,
                    'type' => 'shift_payment',
                    'description' => 'Shift Payment',
                    'shift_title' => $payment->shift->title ?? 'N/A',
                    'amount' => $user->id == $payment->worker_id ? $payment->worker_amount : -$payment->total_amount,
                    'status' => $payment->status,
                    'created_at' => $payment->created_at,
                    'payment_method' => $payment->payment_method ?? 'Stripe',
                ];
            });
            $allTransactions = $allTransactions->merge($shiftPayments);
        }

        if ($type === 'all' || $type === 'deposits') {
            $deposits = $depositsQuery->get()->map(function($deposit) {
                return [
                    'id' => $deposit->id,
                    'type' => 'deposit',
                    'description' => 'Account Deposit',
                    'amount' => $deposit->amount,
                    'status' => $deposit->status,
                    'created_at' => $deposit->created_at,
                    'payment_method' => $deposit->payment_gateway ?? 'N/A',
                ];
            });
            $allTransactions = $allTransactions->merge($deposits);
        }

        if ($type === 'all' || $type === 'withdrawals') {
            $withdrawals = $withdrawalsQuery->get()->map(function($withdrawal) {
                return [
                    'id' => $withdrawal->id,
                    'type' => 'withdrawal',
                    'description' => 'Withdrawal',
                    'amount' => -$withdrawal->amount,
                    'status' => $withdrawal->status,
                    'created_at' => $withdrawal->created_at,
                    'payment_method' => $withdrawal->gateway ?? 'N/A',
                ];
            });
            $allTransactions = $allTransactions->merge($withdrawals);
        }

        if ($type === 'all' || $type === 'legacy') {
            $transactions = $transactionsQuery->get()->map(function($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => 'legacy',
                    'description' => $transaction->type ?? 'Transaction',
                    'amount' => $transaction->amount,
                    'status' => $transaction->status ?? 'completed',
                    'created_at' => $transaction->created_at,
                    'payment_method' => $transaction->payment_gateway ?? 'N/A',
                ];
            });
            $allTransactions = $allTransactions->merge($transactions);
        }

        // Sort by created_at descending
        $allTransactions = $allTransactions->sortByDesc('created_at');

        // Paginate manually
        $perPage = 20;
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $paginatedTransactions = $allTransactions->slice($offset, $perPage);

        // Calculate statistics
        $stats = [
            'total_earned' => ShiftPayment::where('worker_id', $user->id)
                ->where('status', 'paid_out')
                ->sum('worker_amount'),
            'total_spent' => ShiftPayment::where('business_id', $user->id)
                ->whereIn('status', ['in_escrow', 'released', 'paid_out'])
                ->sum('total_amount'),
            'pending_payments' => ShiftPayment::where('worker_id', $user->id)
                ->whereIn('status', ['in_escrow', 'released'])
                ->sum('worker_amount'),
            'total_deposits' => Deposits::where('user_id', $user->id)
                ->where('status', 'active')
                ->sum('amount'),
            'total_withdrawals' => Withdrawals::where('user_id', $user->id)
                ->where('status', 'paid')
                ->sum('amount'),
        ];

        return view('users.transactions', compact(
            'paginatedTransactions',
            'allTransactions',
            'stats',
            'type',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * Export transactions to CSV
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        // Get all transactions (simplified for export)
        $shiftPayments = ShiftPayment::where(function($query) use ($user) {
            $query->where('worker_id', $user->id)
                  ->orWhere('business_id', $user->id);
        })->orderBy('created_at', 'desc')->get();

        $deposits = Deposits::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
        $withdrawals = Withdrawals::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();

        // Generate CSV
        $filename = 'transactions_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($shiftPayments, $deposits, $withdrawals, $user) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, ['Date', 'Type', 'Description', 'Amount', 'Status', 'Payment Method']);

            // Shift payments
            foreach ($shiftPayments as $payment) {
                $amount = $user->id == $payment->worker_id ? $payment->worker_amount : -$payment->total_amount;
                fputcsv($file, [
                    $payment->created_at->format('Y-m-d H:i:s'),
                    'Shift Payment',
                    $payment->shift->title ?? 'N/A',
                    $amount,
                    $payment->status,
                    $payment->payment_method ?? 'Stripe'
                ]);
            }

            // Deposits
            foreach ($deposits as $deposit) {
                fputcsv($file, [
                    $deposit->created_at->format('Y-m-d H:i:s'),
                    'Deposit',
                    'Account Deposit',
                    $deposit->amount,
                    $deposit->status,
                    $deposit->payment_gateway ?? 'N/A'
                ]);
            }

            // Withdrawals
            foreach ($withdrawals as $withdrawal) {
                fputcsv($file, [
                    $withdrawal->created_at->format('Y-m-d H:i:s'),
                    'Withdrawal',
                    'Withdrawal',
                    -$withdrawal->amount,
                    $withdrawal->status,
                    $withdrawal->gateway ?? 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
