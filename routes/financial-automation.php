<?php

use App\Http\Controllers\Admin\AppealReviewController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Business\CreditController;
use App\Http\Controllers\Worker\AppealController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Financial Automation Routes
|--------------------------------------------------------------------------
|
| Routes for the three financial automation features:
| - FIN-006: Worker Penalty Processing with Appeals
| - FIN-007: Business Credit System
| - FIN-010: Automated Refund Processing
|
*/

// ============================================================================
// WORKER ROUTES - Penalty Appeals (FIN-006)
// ============================================================================
Route::middleware(['auth', 'verified', 'role:worker'])->prefix('worker')->name('worker.')->group(function () {

    // Penalties
    Route::get('/penalties', [AppealController::class, 'index'])->name('penalties.index');

    // Appeals
    Route::get('/penalties/{penalty}/appeal', [AppealController::class, 'create'])->name('appeals.create');
    Route::post('/penalties/{penalty}/appeal', [AppealController::class, 'store'])->name('appeals.store');
    Route::get('/appeals/{appeal}', [AppealController::class, 'show'])->name('appeals.show');
    Route::get('/appeals/{appeal}/edit', [AppealController::class, 'edit'])->name('appeals.edit');
    Route::put('/appeals/{appeal}', [AppealController::class, 'update'])->name('appeals.update');
    Route::post('/appeals/{appeal}/evidence', [AppealController::class, 'addEvidence'])->name('appeals.add-evidence');
    Route::delete('/appeals/{appeal}/evidence', [AppealController::class, 'removeEvidence'])->name('appeals.remove-evidence');
});

// ============================================================================
// BUSINESS ROUTES - Credit System (FIN-007)
// ============================================================================
Route::middleware(['auth', 'verified', 'role:business'])->prefix('business')->name('business.')->group(function () {

    // Credit Dashboard
    Route::get('/credit', [CreditController::class, 'index'])->name('credit.index');
    Route::get('/credit/transactions', [CreditController::class, 'transactions'])->name('credit.transactions');

    // Credit Application (for businesses without credit)
    Route::get('/credit/apply', [CreditController::class, 'apply'])->name('credit.apply');
    Route::post('/credit/apply', [CreditController::class, 'submitApplication'])->name('credit.apply.submit');

    // Credit Limit Increase
    Route::get('/credit/increase-request', [CreditController::class, 'requestIncrease'])->name('credit.increase.request');
    Route::post('/credit/increase-request', [CreditController::class, 'submitIncreaseRequest'])->name('credit.increase.submit');

    // Invoices
    Route::get('/credit/invoices', [CreditController::class, 'invoices'])->name('credit.invoices');
    Route::get('/credit/invoices/{invoice}', [CreditController::class, 'invoiceShow'])->name('credit.invoice.show');
    Route::get('/credit/invoices/{invoice}/download', [CreditController::class, 'invoiceDownload'])->name('credit.invoice.download');

    // Invoice Payment
    Route::get('/credit/invoices/{invoice}/pay', [CreditController::class, 'invoicePaymentForm'])->name('credit.invoice.payment');
    Route::post('/credit/invoices/{invoice}/pay', [CreditController::class, 'invoicePayment'])->name('credit.invoice.payment.process');
});

// ============================================================================
// ADMIN ROUTES - All Financial Features
// ============================================================================
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    // ========================================================================
    // Penalty & Appeal Management (FIN-006)
    // ========================================================================

    // Penalty Management
    Route::get('/penalties', [AppealReviewController::class, 'penaltyIndex'])->name('penalties.index');
    Route::get('/penalties/create', [AppealReviewController::class, 'penaltyCreate'])->name('penalties.create');
    Route::post('/penalties', [AppealReviewController::class, 'penaltyStore'])->name('penalties.store');
    Route::get('/penalties/{penalty}', [AppealReviewController::class, 'penaltyShow'])->name('penalties.show');
    Route::post('/penalties/{penalty}/waive', [AppealReviewController::class, 'penaltyWaive'])->name('penalties.waive');

    // Appeal Review
    Route::get('/appeals', [AppealReviewController::class, 'index'])->name('appeals.index');
    Route::get('/appeals/{appeal}', [AppealReviewController::class, 'show'])->name('appeals.show');
    Route::post('/appeals/{appeal}/assign', [AppealReviewController::class, 'assignToMe'])->name('appeals.assign');

    // Appeal Approval
    Route::get('/appeals/{appeal}/approve', [AppealReviewController::class, 'approveForm'])->name('appeals.approve.form');
    Route::post('/appeals/{appeal}/approve', [AppealReviewController::class, 'approve'])->name('appeals.approve');

    // Appeal Rejection
    Route::get('/appeals/{appeal}/reject', [AppealReviewController::class, 'rejectForm'])->name('appeals.reject.form');
    Route::post('/appeals/{appeal}/reject', [AppealReviewController::class, 'reject'])->name('appeals.reject');

    // Appeal Notes
    Route::post('/appeals/{appeal}/notes', [AppealReviewController::class, 'addNotes'])->name('appeals.add-notes');

    // ========================================================================
    // Credit System Management (FIN-007)
    // ========================================================================

    // Credit approval, limits, etc. would be added here
    // Route::get('/credit-requests', [...]);
    // Route::post('/credit-requests/{request}/approve', [...]);

    // ========================================================================
    // Refund Management (FIN-010)
    // ========================================================================

    Route::get('/refunds', [RefundController::class, 'index'])->name('refunds.index');
    Route::get('/refunds/create', [RefundController::class, 'create'])->name('refunds.create');
    Route::post('/refunds', [RefundController::class, 'store'])->name('refunds.store');
    Route::get('/refunds/{refund}', [RefundController::class, 'show'])->name('refunds.show');

    // Refund Processing
    Route::post('/refunds/{refund}/process', [RefundController::class, 'process'])->name('refunds.process');
    Route::post('/refunds/{refund}/retry', [RefundController::class, 'retry'])->name('refunds.retry');
    Route::post('/refunds/{refund}/cancel', [RefundController::class, 'cancel'])->name('refunds.cancel');

    // Credit Note
    Route::get('/refunds/{refund}/credit-note', [RefundController::class, 'downloadCreditNote'])->name('refunds.credit-note');

    // Refund Notes
    Route::post('/refunds/{refund}/notes', [RefundController::class, 'addNotes'])->name('refunds.add-notes');
});

// ============================================================================
// API ROUTES (for automation and background jobs)
// ============================================================================
Route::middleware(['api', 'auth:sanctum'])->prefix('api/v1')->name('api.')->group(function () {

    // These endpoints can be called by scheduled jobs or webhooks

    // Automatic refund creation for cancellations
    Route::post('/refunds/auto-create/{shift}', function ($shiftId) {
        $shift = \App\Models\Shift::findOrFail($shiftId);
        $refundService = app(\App\Services\RefundService::class);
        return $refundService->createAutoCancellationRefund($shift);
    })->name('refunds.auto-create');

    // Process pending refunds (called by cron)
    Route::post('/refunds/process-pending', function () {
        \App\Jobs\ProcessPendingRefunds::dispatch();
        return response()->json(['message' => 'Refund processing job dispatched']);
    })->name('refunds.process-pending');

    // Generate weekly credit invoices (called by cron)
    Route::post('/credit/generate-invoices', function () {
        \App\Jobs\GenerateWeeklyCreditInvoices::dispatch();
        return response()->json(['message' => 'Invoice generation job dispatched']);
    })->name('credit.generate-invoices');

    // Monitor credit limits (called by cron)
    Route::post('/credit/monitor-limits', function () {
        \App\Jobs\MonitorCreditLimits::dispatch();
        return response()->json(['message' => 'Credit monitoring job dispatched']);
    })->name('credit.monitor-limits');
});
