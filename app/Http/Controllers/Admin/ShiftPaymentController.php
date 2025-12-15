<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShiftPayment;
use App\Models\Shift;
use App\Models\User;
use App\Services\ShiftPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShiftPaymentController extends Controller
{
    protected $paymentService;

    public function __construct(ShiftPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Display all shift payments with filters
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = ShiftPayment::with(['assignment.shift.business', 'worker']);

        // Status filter
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Payout status filter
        if ($request->has('payout_status') && $request->payout_status != '') {
            $query->where('payout_status', $request->payout_status);
        }

        // Date range filter
        if ($request->has('date_from') && $request->date_from != '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to != '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by transaction ID or user
        if ($request->has('q') && strlen($request->q) > 2) {
            $query->where(function($q) use ($request) {
                $q->where('transaction_id', 'LIKE', '%' . $request->q . '%')
                  ->orWhere('stripe_payment_intent_id', 'LIKE', '%' . $request->q . '%')
                  ->orWhereHas('worker', function($query) use ($request) {
                      $query->where('name', 'LIKE', '%' . $request->q . '%');
                  });
            });
        }

        $payments = $query->orderBy('id', 'desc')->paginate(50);

        // Statistics
        $stats = [
            'total_payments' => ShiftPayment::count(),
            'total_amount' => ShiftPayment::sum('amount'),
            'total_platform_fees' => ShiftPayment::sum('platform_fee'),
            'in_escrow' => ShiftPayment::where('status', 'in_escrow')->sum('amount'),
            'disputed' => ShiftPayment::where('status', 'disputed')->count(),
            'failed_payouts' => ShiftPayment::where('payout_status', 'failed')->count(),
        ];

        return view('admin.payments.index', compact('payments', 'stats'));
    }

    /**
     * Display payment details
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $payment = ShiftPayment::with([
            'assignment.shift.business',
            'worker'
        ])->findOrFail($id);

        // Get payment timeline
        $timeline = [
            ['event' => 'Payment Created', 'timestamp' => $payment->created_at],
            ['event' => 'Escrow Held', 'timestamp' => $payment->escrowed_at],
            ['event' => 'Released from Escrow', 'timestamp' => $payment->released_at],
            ['event' => 'Payout Initiated', 'timestamp' => $payment->payout_initiated_at],
            ['event' => 'Payout Completed', 'timestamp' => $payment->payout_completed_at],
        ];

        return view('admin.payments.show', compact('payment', 'timeline'));
    }

    /**
     * Manually release payment from escrow
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function releaseEscrow($id)
    {
        $payment = ShiftPayment::findOrFail($id);

        if ($payment->status !== 'in_escrow') {
            return back()->withErrors(['error' => 'Payment is not in escrow status.']);
        }

        try {
            $this->paymentService->releaseFromEscrow($payment);

            \Session::flash('success', 'Payment has been released from escrow successfully.');
        } catch (\Exception $e) {
            \Session::flash('error', 'Error releasing payment: ' . $e->getMessage());
        }

        return redirect()->route('admin.payments.show', $id);
    }

    /**
     * Process refund for a shift payment
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refund($id, Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'refund_amount' => 'required|numeric|min:0'
        ]);

        $payment = ShiftPayment::findOrFail($id);

        if ($payment->status === 'refunded') {
            return back()->withErrors(['error' => 'Payment has already been refunded.']);
        }

        try {
            // Call Stripe refund API
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            $refund = $stripe->refunds->create([
                'payment_intent' => $payment->stripe_payment_intent_id,
                'amount' => $request->refund_amount * 100, // Convert to cents
                'reason' => 'requested_by_customer',
                'metadata' => [
                    'admin_reason' => $request->reason,
                    'admin_id' => auth()->id(),
                ]
            ]);

            // Update payment record
            $payment->status = 'refunded';
            $payment->refund_amount = $request->refund_amount;
            $payment->refund_reason = $request->reason;
            $payment->refunded_at = Carbon::now();
            $payment->refunded_by_admin_id = auth()->id();
            $payment->stripe_refund_id = $refund->id;
            $payment->save();

            // Adjust platform revenue
            // Decrement admin earnings

            \Session::flash('success', 'Refund processed successfully.');
        } catch (\Exception $e) {
            \Session::flash('error', 'Error processing refund: ' . $e->getMessage());
        }

        return redirect()->route('admin.payments.show', $id);
    }

    /**
     * Hold payment due to dispute
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function holdPayment($id, Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $payment = ShiftPayment::findOrFail($id);

        $payment->status = 'disputed';
        $payment->dispute_reason = $request->reason;
        $payment->disputed_at = Carbon::now();
        $payment->disputed_by_admin_id = auth()->id();
        $payment->save();

        // Notify both parties
        // $payment->worker->notify(new PaymentDisputedNotification($payment));
        // $payment->shift->business->notify(new PaymentDisputedNotification($payment));

        \Session::flash('success', 'Payment has been placed on hold due to dispute.');

        return redirect()->route('admin.payments.show', $id);
    }

    /**
     * Retry failed instant payout
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function retryInstantPayout($id)
    {
        $payment = ShiftPayment::findOrFail($id);

        if ($payment->payout_status !== 'failed') {
            return back()->withErrors(['error' => 'Payout has not failed. Cannot retry.']);
        }

        try {
            $this->paymentService->instantPayout($payment);

            \Session::flash('success', 'Payout retry initiated successfully.');
        } catch (\Exception $e) {
            \Session::flash('error', 'Error retrying payout: ' . $e->getMessage());
        }

        return redirect()->route('admin.payments.show', $id);
    }

    /**
     * View all disputed payments
     *
     * @return \Illuminate\View\View
     */
    public function disputes()
    {
        $payments = ShiftPayment::with(['assignment.shift.business', 'worker'])
            ->where('status', 'disputed')
            ->orderBy('disputed_at', 'desc')
            ->paginate(30);

        return view('admin.payments.disputes', compact('payments'));
    }

    /**
     * Resolve payment dispute
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resolveDispute($id, Request $request)
    {
        $request->validate([
            'resolution' => 'required|in:release,refund',
            'resolution_notes' => 'required|string|max:1000'
        ]);

        $payment = ShiftPayment::findOrFail($id);

        if ($payment->status !== 'disputed') {
            return back()->withErrors(['error' => 'Payment is not under dispute.']);
        }

        try {
            if ($request->resolution === 'release') {
                // Release payment to worker
                $this->paymentService->releaseFromEscrow($payment);
                $message = 'Dispute resolved. Payment released to worker.';
            } else {
                // Refund to business
                $this->refund($id, new Request([
                    'reason' => 'Dispute resolved in favor of business: ' . $request->resolution_notes,
                    'refund_amount' => $payment->amount
                ]));
                $message = 'Dispute resolved. Payment refunded to business.';
            }

            $payment->dispute_resolved_at = Carbon::now();
            $payment->dispute_resolution = $request->resolution;
            $payment->dispute_resolution_notes = $request->resolution_notes;
            $payment->resolved_by_admin_id = auth()->id();
            $payment->save();

            \Session::flash('success', $message);
        } catch (\Exception $e) {
            \Session::flash('error', 'Error resolving dispute: ' . $e->getMessage());
        }

        return redirect()->route('admin.payments.disputes');
    }

    /**
     * View payment statistics
     *
     * @return \Illuminate\View\View
     */
    public function statistics()
    {
        $stats = [
            // Total metrics
            'total_payments' => ShiftPayment::count(),
            'total_volume' => ShiftPayment::sum('amount'),
            'total_platform_revenue' => ShiftPayment::sum('platform_fee'),
            'total_worker_earnings' => ShiftPayment::sum('worker_amount'),

            // Status breakdown
            'in_escrow' => ShiftPayment::where('status', 'in_escrow')->sum('amount'),
            'released' => ShiftPayment::where('status', 'released')->sum('amount'),
            'refunded' => ShiftPayment::where('status', 'refunded')->sum('refund_amount'),
            'disputed_count' => ShiftPayment::where('status', 'disputed')->count(),

            // Payout metrics
            'instant_payout_success_rate' => $this->calculatePayoutSuccessRate(),
            'avg_payout_time' => $this->calculateAveragePayoutTime(),
            'failed_payouts' => ShiftPayment::where('payout_status', 'failed')->count(),

            // Today
            'payments_today' => ShiftPayment::whereDate('created_at', today())->count(),
            'revenue_today' => ShiftPayment::whereDate('created_at', today())->sum('platform_fee'),

            // This week
            'payments_week' => ShiftPayment::whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count(),
            'revenue_week' => ShiftPayment::whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->sum('platform_fee'),

            // This month
            'payments_month' => ShiftPayment::whereMonth('created_at', Carbon::now()->month)->count(),
            'revenue_month' => ShiftPayment::whereMonth('created_at', Carbon::now()->month)->sum('platform_fee'),
        ];

        // Daily revenue chart data (last 30 days)
        $dailyRevenue = DB::table('shift_payments')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(platform_fee) as revenue'))
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return view('admin.payments.statistics', compact('stats', 'dailyRevenue'));
    }

    /**
     * Calculate instant payout success rate
     *
     * @return float
     */
    private function calculatePayoutSuccessRate()
    {
        $totalPayouts = ShiftPayment::whereNotNull('payout_initiated_at')->count();

        if ($totalPayouts == 0) {
            return 0;
        }

        $successfulPayouts = ShiftPayment::where('payout_status', 'completed')->count();

        return round(($successfulPayouts / $totalPayouts) * 100, 1);
    }

    /**
     * Calculate average payout time in minutes
     *
     * @return float
     */
    private function calculateAveragePayoutTime()
    {
        $completedPayouts = ShiftPayment::whereNotNull('payout_initiated_at')
            ->whereNotNull('payout_completed_at')
            ->get();

        if ($completedPayouts->isEmpty()) {
            return 0;
        }

        $totalMinutes = $completedPayouts->sum(function($payment) {
            return Carbon::parse($payment->payout_initiated_at)->diffInMinutes($payment->payout_completed_at);
        });

        $count = $completedPayouts->count();
        return $count > 0 ? round($totalMinutes / $count, 1) : 0;
    }
}
