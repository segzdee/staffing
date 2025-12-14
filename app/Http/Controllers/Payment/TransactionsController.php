<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShiftPayment;
use App\Services\ShiftPaymentService;
use Auth;

class TransactionsController extends Controller
{
    protected $paymentService;

    public function __construct(ShiftPaymentService $paymentService)
    {
        $this->middleware('auth');
        $this->paymentService = $paymentService;
    }

    /**
     * Show transaction history for user
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $filter = $request->get('filter', 'all');

        // Base query
        $query = ShiftPayment::with(['assignment.shift', 'worker', 'business']);

        // Filter by user type
        if ($user->user_type === 'worker') {
            $query->where('worker_id', $user->id);

            // Apply worker-specific filters
            switch ($filter) {
                case 'payouts':
                    $query->where('status', 'paid_out');
                    break;
                case 'pending':
                    $query->whereIn('status', ['in_escrow', 'released']);
                    break;
                case 'disputes':
                    $query->where('disputed', true);
                    break;
            }
        } else {
            // Business view
            $query->where('business_id', $user->id);

            // Apply business-specific filters
            switch ($filter) {
                case 'payments':
                    $query->whereNotNull('stripe_payment_intent_id');
                    break;
                case 'escrow':
                    $query->where('status', 'in_escrow');
                    break;
                case 'disputes':
                    $query->where('disputed', true);
                    break;
            }
        }

        // Get paginated transactions
        $transactions = $query->orderBy('created_at', 'DESC')->paginate(20);

        // Calculate summary statistics
        if ($user->user_type === 'worker') {
            $totalEarned = ShiftPayment::where('worker_id', $user->id)
                ->where('status', 'paid_out')
                ->sum('amount_net');

            $pendingAmount = ShiftPayment::where('worker_id', $user->id)
                ->whereIn('status', ['in_escrow', 'released'])
                ->sum('amount_net');
        } else {
            $totalSpent = ShiftPayment::where('business_id', $user->id)
                ->whereIn('status', ['in_escrow', 'released', 'paid_out'])
                ->sum('amount_gross');

            $pendingAmount = ShiftPayment::where('business_id', $user->id)
                ->where('status', 'in_escrow')
                ->sum('amount_gross');
        }

        $completedCount = ShiftPayment::where($user->user_type === 'worker' ? 'worker_id' : 'business_id', $user->id)
            ->where('status', 'paid_out')
            ->count();

        return view('my.transactions', compact(
            'transactions',
            'filter',
            'totalEarned',
            'totalSpent',
            'pendingAmount',
            'completedCount'
        ));
    }

    /**
     * File a dispute for a transaction
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function fileDispute(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string',
            'explanation' => 'required|string|max:2000'
        ]);

        $payment = ShiftPayment::findOrFail($id);

        // Verify user has access to this payment
        $user = Auth::user();
        if ($payment->worker_id !== $user->id && $payment->business_id !== $user->id) {
            abort(403, 'Unauthorized access to this transaction');
        }

        // Can't dispute if already paid out
        if ($payment->status === 'paid_out') {
            return redirect()->back()->with('error', 'Cannot dispute a completed payout');
        }

        // File the dispute
        $disputeReason = $request->reason . ': ' . $request->explanation;
        $result = $this->paymentService->handleDispute(
            $payment->assignment,
            $disputeReason
        );

        if ($result) {
            return redirect()->route('transactions.index')
                ->with('success', 'Dispute filed successfully. Our team will review and contact you within 24 hours.');
        } else {
            return redirect()->back()
                ->with('error', 'Failed to file dispute. Please try again or contact support.');
        }
    }

    /**
     * Download transaction history as CSV (Admin or User)
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        $query = ShiftPayment::with(['assignment.shift', 'worker', 'business']);

        if ($user->user_type === 'worker') {
            $query->where('worker_id', $user->id);
        } elseif ($user->user_type === 'business') {
            $query->where('business_id', $user->id);
        }

        $transactions = $query->orderBy('created_at', 'DESC')->get();

        $filename = 'transactions_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($transactions, $user) {
            $file = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($file, [
                'Date',
                'Shift',
                'Status',
                'Amount Gross',
                'Platform Fee',
                'Amount Net',
                'Hours',
                'Disputed',
                'Payment ID'
            ]);

            // CSV Data
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->created_at->format('Y-m-d H:i:s'),
                    $transaction->assignment->shift->title ?? 'N/A',
                    $transaction->status,
                    $transaction->amount_gross,
                    $transaction->platform_fee,
                    $transaction->amount_net,
                    $transaction->assignment->hours_worked ?? 'N/A',
                    $transaction->disputed ? 'Yes' : 'No',
                    $transaction->stripe_payment_intent_id ?? 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
