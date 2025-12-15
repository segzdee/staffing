<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\BusinessCreditTransaction;
use App\Models\CreditInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CreditController extends Controller
{
    /**
     * Display the credit dashboard.
     */
    public function index()
    {
        $business = Auth::user();
        $profile = $business->businessProfile;

        // Check if credit is enabled
        if (!$profile->credit_enabled) {
            return view('business.credit.not-enabled', compact('profile'));
        }

        // Get recent transactions
        $recentTransactions = BusinessCreditTransaction::where('business_id', $business->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get unpaid invoices
        $unpaidInvoices = CreditInvoice::where('business_id', $business->id)
            ->unpaid()
            ->orderBy('due_date', 'asc')
            ->get();

        // Get overdue invoices
        $overdueInvoices = CreditInvoice::where('business_id', $business->id)
            ->overdue()
            ->orderBy('due_date', 'asc')
            ->get();

        // Calculate statistics
        $stats = [
            'credit_limit' => $profile->credit_limit,
            'credit_used' => $profile->credit_used,
            'credit_available' => $profile->credit_available,
            'credit_utilization' => $profile->credit_utilization,
            'total_unpaid' => $unpaidInvoices->sum('amount_due'),
            'total_overdue' => $overdueInvoices->sum('amount_due'),
            'unpaid_count' => $unpaidInvoices->count(),
            'overdue_count' => $overdueInvoices->count(),
        ];

        return view('business.credit.dashboard', compact(
            'profile',
            'recentTransactions',
            'unpaidInvoices',
            'overdueInvoices',
            'stats'
        ));
    }

    /**
     * Display credit transactions history.
     */
    public function transactions(Request $request)
    {
        $business = Auth::user();
        $type = $request->input('type');

        $transactions = BusinessCreditTransaction::where('business_id', $business->id)
            ->when($type, function ($query, $type) {
                return $query->where('transaction_type', $type);
            })
            ->with(['shift', 'invoice'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('business.credit.transactions', compact('transactions', 'type'));
    }

    /**
     * Display all invoices.
     */
    public function invoices(Request $request)
    {
        $business = Auth::user();
        $status = $request->input('status');

        $invoices = CreditInvoice::where('business_id', $business->id)
            ->when($status, function ($query, $status) {
                if ($status === 'unpaid') {
                    return $query->unpaid();
                } elseif ($status === 'overdue') {
                    return $query->overdue();
                } elseif ($status === 'paid') {
                    return $query->paid();
                } else {
                    return $query->where('status', $status);
                }
            })
            ->orderBy('invoice_date', 'desc')
            ->paginate(15);

        return view('business.credit.invoices', compact('invoices', 'status'));
    }

    /**
     * Display a specific invoice.
     */
    public function invoiceShow($invoiceId)
    {
        $business = Auth::user();

        $invoice = CreditInvoice::where('id', $invoiceId)
            ->where('business_id', $business->id)
            ->with(['items.shift', 'items.worker', 'transactions'])
            ->firstOrFail();

        return view('business.credit.invoice-show', compact('invoice'));
    }

    /**
     * Download invoice PDF.
     */
    public function invoiceDownload($invoiceId)
    {
        $business = Auth::user();

        $invoice = CreditInvoice::where('id', $invoiceId)
            ->where('business_id', $business->id)
            ->with(['items.shift', 'items.worker', 'business.businessProfile'])
            ->firstOrFail();

        // Generate PDF if not already generated
        if (!$invoice->pdf_path || !file_exists(storage_path('app/' . $invoice->pdf_path))) {
            // Implement PDF generation service
            // $pdfPath = app(InvoicePdfService::class)->generate($invoice);
            // $invoice->update(['pdf_path' => $pdfPath, 'pdf_generated_at' => now()]);
        }

        // Return PDF download
        // return response()->download(storage_path('app/' . $invoice->pdf_path));

        // For now, return view
        return view('business.credit.invoice-pdf', compact('invoice'));
    }

    /**
     * Show payment form for an invoice.
     */
    public function invoicePaymentForm($invoiceId)
    {
        $business = Auth::user();

        $invoice = CreditInvoice::where('id', $invoiceId)
            ->where('business_id', $business->id)
            ->where('status', '!=', 'paid')
            ->firstOrFail();

        return view('business.credit.invoice-payment', compact('invoice'));
    }

    /**
     * Process payment for an invoice.
     */
    public function invoicePayment(Request $request, $invoiceId)
    {
        $business = Auth::user();

        $invoice = CreditInvoice::where('id', $invoiceId)
            ->where('business_id', $business->id)
            ->where('status', '!=', 'paid')
            ->firstOrFail();

        // Validate request
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:card,bank_transfer,other',
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->amount_due,
            'payment_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Process payment based on method
            if ($request->payment_method === 'card') {
                // Process credit card payment via Stripe
                // Implement Stripe payment processing
                $paymentIntent = null; // app(StripePaymentService::class)->processPayment(...)
                $referenceId = $paymentIntent->id ?? null;
            } else {
                $referenceId = $request->payment_reference;
            }

            // Record payment on invoice
            $invoice->recordPayment(
                $request->amount,
                $referenceId,
                $request->payment_method
            );

            // Update business credit balance
            $profile = $business->businessProfile;
            $profile->credit_used -= $request->amount;
            $profile->credit_available = $profile->credit_limit - $profile->credit_used;
            $profile->credit_utilization = $profile->credit_limit > 0
                ? ($profile->credit_used / $profile->credit_limit) * 100
                : 0;

            // Unpause credit if it was paused and now below 95%
            if ($profile->credit_paused && $profile->credit_utilization < 95) {
                $profile->credit_paused = false;
                $profile->credit_paused_at = null;
                $profile->credit_pause_reason = null;
            }

            $profile->save();

            return redirect()
                ->route('business.credit.invoice.show', $invoice->id)
                ->with('success', 'Payment processed successfully.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Payment failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Request credit limit increase.
     */
    public function requestIncrease()
    {
        $business = Auth::user();
        $profile = $business->businessProfile;

        return view('business.credit.request-increase', compact('profile'));
    }

    /**
     * Submit credit limit increase request.
     */
    public function submitIncreaseRequest(Request $request)
    {
        $business = Auth::user();
        $profile = $business->businessProfile;

        // Validate request
        $validator = Validator::make($request->all(), [
            'requested_limit' => 'required|numeric|min:' . $profile->credit_limit . '|max:1000000',
            'reason' => 'required|string|min:50|max:1000',
            'monthly_volume' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Create request record (implement credit request model)
        // CreditLimitRequest::create([...]);

        // Send notification to admin
        // NotificationService::notifyAdminOfCreditRequest($business, $request->all());

        return redirect()
            ->route('business.credit.index')
            ->with('success', 'Your credit limit increase request has been submitted for review.');
    }

    /**
     * Apply for credit account (for businesses without credit).
     */
    public function apply()
    {
        $business = Auth::user();
        $profile = $business->businessProfile;

        if ($profile->credit_enabled) {
            return redirect()->route('business.credit.index');
        }

        return view('business.credit.apply', compact('profile'));
    }

    /**
     * Submit credit application.
     */
    public function submitApplication(Request $request)
    {
        $business = Auth::user();
        $profile = $business->businessProfile;

        if ($profile->credit_enabled) {
            return redirect()->route('business.credit.index');
        }

        // Validate application
        $validator = Validator::make($request->all(), [
            'requested_limit' => 'required|numeric|min:1000|max:100000',
            'payment_terms' => 'required|in:net_7,net_14,net_30',
            'monthly_volume' => 'required|numeric|min:0',
            'business_references' => 'nullable|string|max:1000',
            'bank_name' => 'required|string|max:255',
            'bank_account_verified' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Create application record (implement credit application model)
        // CreditApplication::create([...]);

        // Send notification to admin
        // NotificationService::notifyAdminOfCreditApplication($business, $request->all());

        return redirect()
            ->route('business.dashboard')
            ->with('success', 'Your credit application has been submitted. We will review it and get back to you within 2-3 business days.');
    }
}
