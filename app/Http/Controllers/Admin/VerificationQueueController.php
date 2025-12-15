<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VerificationQueue;
use App\Models\User;
use App\Notifications\BulkVerificationCompletedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * VerificationQueueController - ADM-001
 *
 * Handles admin verification queue operations including:
 * - Listing and filtering verification requests
 * - Individual approve/reject actions
 * - Bulk approve/reject operations
 * - SLA tracking and statistics
 */
class VerificationQueueController extends Controller
{
    /**
     * Display the verification queue with filtering and SLA information
     */
    public function index(Request $request)
    {
        $query = VerificationQueue::with(['verifiable', 'reviewer'])
            ->actionable();

        // Filter by SLA status
        if ($request->filled('sla_status')) {
            $query->bySLAStatus($request->sla_status);
        }

        // Filter by verification type
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by verifiable (user name/email)
        if ($request->filled('search')) {
            $search = $request->search;
            // This is a simplified search - in production, you'd want to search across related models
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                  ->orWhereHas('verifiable', function ($vq) use ($search) {
                      // Search varies by verifiable type
                  });
            });
        }

        // Default sort by priority (SLA deadline)
        $data = $query->byPriority()->paginate(20);

        // Get SLA statistics for the widget
        $slaStats = VerificationQueue::getSLAStatistics();
        $avgProcessingTimes = VerificationQueue::getAverageProcessingTimes();

        return view('admin.verification-queue', [
            'data' => $data,
            'slaStats' => $slaStats,
            'avgProcessingTimes' => $avgProcessingTimes,
            'filters' => [
                'sla_status' => $request->sla_status,
                'type' => $request->type,
                'status' => $request->status,
                'search' => $request->search,
            ],
        ]);
    }

    /**
     * Show a single verification request details
     */
    public function show($id)
    {
        $verification = VerificationQueue::with(['verifiable', 'reviewer'])
            ->findOrFail($id);

        return view('admin.verification-show', [
            'verification' => $verification,
        ]);
    }

    /**
     * Approve a single verification request
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $verification = VerificationQueue::findOrFail($id);

        if (!in_array($verification->status, ['pending', 'in_review'])) {
            return redirect()->back()->with('error', 'This verification has already been processed.');
        }

        $verification->approve(auth()->id(), $request->notes);

        Log::info('Verification approved', [
            'verification_id' => $id,
            'admin_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Verification approved successfully.');
    }

    /**
     * Reject a single verification request
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        $verification = VerificationQueue::findOrFail($id);

        if (!in_array($verification->status, ['pending', 'in_review'])) {
            return redirect()->back()->with('error', 'This verification has already been processed.');
        }

        $verification->reject(auth()->id(), $request->notes);

        Log::info('Verification rejected', [
            'verification_id' => $id,
            'admin_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Verification rejected successfully.');
    }

    /**
     * Bulk approve multiple verification requests
     *
     * POST /admin/verification/bulk-approve
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1|max:50',
            'ids.*' => 'required|integer|exists:verification_queue,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $ids = $request->input('ids');
        $notes = $request->input('notes');
        $adminId = auth()->id();

        // Perform bulk approval
        $results = VerificationQueue::bulkApprove($ids, $adminId, $notes);

        // Log the bulk operation
        Log::info('Bulk verification approval', [
            'admin_id' => $adminId,
            'requested_ids' => $ids,
            'success_count' => $results['success'],
            'failed_count' => $results['failed'],
        ]);

        // Notify admin team about bulk operation completion
        $this->notifyBulkOperationComplete('approve', $results);

        // Return appropriate response based on request type
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $this->formatBulkResultMessage('approved', $results),
                'results' => $results,
            ]);
        }

        $message = $this->formatBulkResultMessage('approved', $results);

        if ($results['failed'] > 0) {
            return redirect()->back()
                ->with('warning', $message)
                ->with('errors_list', $results['errors']);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Bulk reject multiple verification requests
     *
     * POST /admin/verification/bulk-reject
     */
    public function bulkReject(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1|max:50',
            'ids.*' => 'required|integer|exists:verification_queue,id',
            'notes' => 'required|string|min:10|max:1000',
        ], [
            'notes.required' => 'A rejection reason is required for bulk rejections.',
            'notes.min' => 'Please provide a more detailed rejection reason (at least 10 characters).',
        ]);

        $ids = $request->input('ids');
        $notes = $request->input('notes');
        $adminId = auth()->id();

        // Perform bulk rejection
        $results = VerificationQueue::bulkReject($ids, $adminId, $notes);

        // Log the bulk operation
        Log::info('Bulk verification rejection', [
            'admin_id' => $adminId,
            'requested_ids' => $ids,
            'success_count' => $results['success'],
            'failed_count' => $results['failed'],
            'reason' => $notes,
        ]);

        // Notify admin team about bulk operation completion
        $this->notifyBulkOperationComplete('reject', $results);

        // Return appropriate response based on request type
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $this->formatBulkResultMessage('rejected', $results),
                'results' => $results,
            ]);
        }

        $message = $this->formatBulkResultMessage('rejected', $results);

        if ($results['failed'] > 0) {
            return redirect()->back()
                ->with('warning', $message)
                ->with('errors_list', $results['errors']);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Get SLA statistics for dashboard widget
     *
     * GET /admin/verification/sla-stats
     */
    public function slaStats()
    {
        $stats = VerificationQueue::getSLAStatistics();
        $avgTimes = VerificationQueue::getAverageProcessingTimes();

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'average_processing_times' => $avgTimes,
            'sla_targets' => VerificationQueue::SLA_TARGETS,
        ]);
    }

    /**
     * Get verification queue data for AJAX refresh
     *
     * GET /admin/verification/queue-data
     */
    public function queueData(Request $request)
    {
        $query = VerificationQueue::with(['verifiable'])
            ->actionable();

        // Apply filters
        if ($request->filled('sla_status')) {
            $query->bySLAStatus($request->sla_status);
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        $data = $query->byPriority()
            ->take(50)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'verification_type' => $item->verification_type,
                    'status' => $item->status,
                    'sla_status' => $item->sla_status,
                    'sla_remaining_time' => $item->sla_remaining_time,
                    'sla_remaining_hours' => $item->sla_remaining_hours,
                    'sla_deadline' => $item->sla_deadline?->toISOString(),
                    'submitted_at' => $item->submitted_at->toISOString(),
                    'priority_score' => $item->priority_score,
                    'verifiable_type' => class_basename($item->verifiable_type),
                    'verifiable_id' => $item->verifiable_id,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $data,
            'total' => $data->count(),
        ]);
    }

    /**
     * Format bulk operation result message
     */
    protected function formatBulkResultMessage(string $action, array $results): string
    {
        $total = $results['success'] + $results['failed'];

        if ($results['failed'] === 0) {
            return "Successfully {$action} {$results['success']} verification(s).";
        }

        if ($results['success'] === 0) {
            return "Failed to {$action} all {$total} verification(s). Check error details.";
        }

        return "{$results['success']} verification(s) {$action}, {$results['failed']} failed.";
    }

    /**
     * Notify admin team about bulk operation completion
     */
    protected function notifyBulkOperationComplete(string $action, array $results): void
    {
        try {
            // Get admin users to notify (super admins and verification admins)
            $adminUsers = User::where('role', 'admin')
                ->where('id', '!=', auth()->id())
                ->where(function ($q) {
                    $q->where('permission', 'full')
                      ->orWhere('permissions', 'LIKE', '%verification%');
                })
                ->get();

            if ($adminUsers->isNotEmpty()) {
                Notification::send($adminUsers, new BulkVerificationCompletedNotification(
                    auth()->user(),
                    $action,
                    $results
                ));
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send bulk operation notification to admins', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
