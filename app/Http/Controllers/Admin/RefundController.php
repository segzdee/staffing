<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Models\User;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RefundController extends Controller
{
    protected $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

    /**
     * Display refund management dashboard.
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        $type = $request->input('type');

        $refunds = Refund::with([
            'business.businessProfile',
            'shift',
            'shiftPayment',
            'processedByAdmin'
        ])
            ->when($status === 'pending', function ($query) {
                return $query->pending();
            })
            ->when($status === 'processing', function ($query) {
                return $query->processing();
            })
            ->when($status === 'completed', function ($query) {
                return $query->completed();
            })
            ->when($status === 'failed', function ($query) {
                return $query->failed();
            })
            ->when($type, function ($query, $type) {
                return $query->ofType($type);
            })
            ->orderBy('initiated_at', 'desc')
            ->paginate(20);

        // Get statistics
        $stats = [
            'pending' => Refund::pending()->count(),
            'processing' => Refund::processing()->count(),
            'completed' => Refund::completed()->count(),
            'failed' => Refund::failed()->count(),
            'total_pending_amount' => Refund::pending()->sum('refund_amount'),
            'total_completed_amount' => Refund::completed()
                ->where('completed_at', '>', now()->subDays(30))
                ->sum('refund_amount'),
        ];

        return view('admin.refunds.index', compact('refunds', 'status', 'type', 'stats'));
    }

    /**
     * Display the specified refund.
     */
    public function show($refundId)
    {
        $refund = Refund::with([
            'business.businessProfile',
            'shift',
            'shiftPayment',
            'processedByAdmin'
        ])->findOrFail($refundId);

        return view('admin.refunds.show', compact('refund'));
    }

    /**
     * Show form to create manual refund.
     */
    public function create(Request $request)
    {
        $businessId = $request->input('business_id');
        $shiftId = $request->input('shift_id');

        return view('admin.refunds.create', compact('businessId', 'shiftId'));
    }

    /**
     * Store a manually created refund.
     */
    public function store(Request $request)
    {
        $admin = Auth::user();

        // Validate request
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|exists:users,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'shift_payment_id' => 'nullable|exists:shift_payments,id',
            'refund_amount' => 'required|numeric|min:0.01|max:100000',
            'refund_reason' => 'required|in:billing_error,overcharge,duplicate_charge,dispute_resolved,goodwill,other',
            'reason_description' => 'required|string|min:20|max:1000',
            'refund_method' => 'required|in:original_payment_method,credit_balance,manual',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $business = User::findOrFail($request->business_id);

            $refund = $this->refundService->createManualRefund(
                $business,
                $request->refund_amount,
                $request->refund_reason,
                $request->reason_description,
                $request->shift_id,
                $request->shift_payment_id,
                $request->refund_method,
                $admin->id
            );

            if ($request->admin_notes) {
                $refund->update(['admin_notes' => $request->admin_notes]);
            }

            return redirect()
                ->route('admin.refunds.show', $refund->id)
                ->with('success', 'Manual refund created successfully.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to create refund: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Process a pending refund manually.
     */
    public function process($refundId)
    {
        $refund = Refund::findOrFail($refundId);

        if (!$refund->isPending()) {
            return redirect()
                ->route('admin.refunds.show', $refund->id)
                ->with('error', 'This refund is not pending.');
        }

        try {
            $success = $this->refundService->processRefund($refund);

            if ($success) {
                return redirect()
                    ->route('admin.refunds.show', $refund->id)
                    ->with('success', 'Refund processed successfully.');
            } else {
                return redirect()
                    ->route('admin.refunds.show', $refund->id)
                    ->with('error', 'Failed to process refund.');
            }

        } catch (\Exception $e) {
            return redirect()
                ->route('admin.refunds.show', $refund->id)
                ->with('error', 'Error processing refund: ' . $e->getMessage());
        }
    }

    /**
     * Retry a failed refund.
     */
    public function retry($refundId)
    {
        $refund = Refund::findOrFail($refundId);

        if (!$refund->isFailed()) {
            return redirect()
                ->route('admin.refunds.show', $refund->id)
                ->with('error', 'This refund has not failed.');
        }

        try {
            $success = $this->refundService->retryRefund($refund);

            if ($success) {
                return redirect()
                    ->route('admin.refunds.show', $refund->id)
                    ->with('success', 'Refund retry successful.');
            } else {
                return redirect()
                    ->route('admin.refunds.show', $refund->id)
                    ->with('error', 'Refund retry failed.');
            }

        } catch (\Exception $e) {
            return redirect()
                ->route('admin.refunds.show', $refund->id)
                ->with('error', 'Error retrying refund: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a pending refund.
     */
    public function cancel(Request $request, $refundId)
    {
        $refund = Refund::findOrFail($refundId);

        if (!$refund->isPending()) {
            return redirect()
                ->route('admin.refunds.show', $refund->id)
                ->with('error', 'Only pending refunds can be cancelled.');
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|min:10|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator);
        }

        $refund->update([
            'status' => 'cancelled',
            'admin_notes' => $request->reason,
        ]);

        return redirect()
            ->route('admin.refunds.show', $refund->id)
            ->with('success', 'Refund has been cancelled.');
    }

    /**
     * Download credit note PDF.
     */
    public function downloadCreditNote($refundId)
    {
        $refund = Refund::findOrFail($refundId);

        if (!$refund->isCompleted()) {
            return redirect()
                ->route('admin.refunds.show', $refund->id)
                ->with('error', 'Credit note is only available for completed refunds.');
        }

        // Generate credit note if not exists
        if (!$refund->hasCreditNote()) {
            $refund->generateCreditNote();
        }

        // Return PDF download (implement PDF service)
        // return response()->download(storage_path('app/' . $refund->credit_note_pdf_path));

        // For now, return view
        return view('admin.refunds.credit-note', compact('refund'));
    }

    /**
     * Add admin notes to a refund.
     */
    public function addNotes(Request $request, $refundId)
    {
        $refund = Refund::findOrFail($refundId);

        $validator = Validator::make($request->all(), [
            'admin_notes' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator);
        }

        $refund->update(['admin_notes' => $request->admin_notes]);

        return redirect()
            ->route('admin.refunds.show', $refund->id)
            ->with('success', 'Notes added successfully.');
    }
}
