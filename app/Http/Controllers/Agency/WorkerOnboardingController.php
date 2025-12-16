<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\BulkImportRequest;
use App\Http\Requests\Agency\InviteWorkerRequest;
use App\Http\Requests\Agency\SyncWorkersRequest;
use App\Models\AgencyInvitation;
use App\Models\AgencyWorker;
use App\Models\User;
use App\Notifications\AgencyWorkerInvitationNotification;
use App\Services\AgencyWorkerImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AGY-REG-004: Worker Pool Onboarding Controller
 *
 * Handles bulk CSV import, individual invitations, external system sync,
 * and invitation management for agency workers.
 */
class WorkerOnboardingController extends Controller
{
    protected AgencyWorkerImportService $importService;

    public function __construct(AgencyWorkerImportService $importService)
    {
        $this->middleware(['auth', 'agency']);
        $this->importService = $importService;
    }

    /**
     * Show the worker import page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $agency = Auth::user();

        $stats = $this->importService->getImportStatistics($agency->id);

        $activeWorkers = AgencyWorker::with(['worker.workerProfile'])
            ->where('agency_id', $agency->id)
            ->where('status', 'active')
            ->count();

        return view('agency.workers.import', [
            'stats' => $stats,
            'activeWorkers' => $activeWorkers,
        ]);
    }

    /**
     * Upload CSV of workers for bulk import.
     *
     * @param BulkImportRequest $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function bulkImport(BulkImportRequest $request)
    {
        $agency = Auth::user();

        // Validate CSV file
        $validation = $this->importService->validateCsvFile($request->file('csv_file'));

        if (!$validation['valid']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validation['errors'],
                ], 422);
            }

            return back()->withErrors($validation['errors'])->withInput();
        }

        // Prepare import options
        $options = [
            'send_invitations' => $request->boolean('send_invitations', true),
            'skip_existing_workers' => $request->boolean('skip_existing', true),
            'skip_existing_invitations' => $request->boolean('skip_existing', true),
            'default_commission_rate' => $request->input('default_commission_rate'),
        ];

        // Perform import
        $results = $this->importService->importFromCsv(
            $request->file('csv_file'),
            $agency->id,
            $options
        );

        Log::info('AgencyWorkerOnboarding: Bulk import completed', [
            'agency_id' => $agency->id,
            'results' => array_diff_key($results, ['created_workers' => [], 'invited_workers' => []]),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'results' => $results,
                'message' => $this->formatImportMessage($results),
            ]);
        }

        return redirect()->route('agency.workers.invitations')
            ->with('success', $this->formatImportMessage($results))
            ->with('import_results', $results);
    }

    /**
     * Send individual worker invitation.
     *
     * @param InviteWorkerRequest $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function inviteIndividual(InviteWorkerRequest $request)
    {
        $agency = Auth::user();
        $email = strtolower(trim($request->input('email')));

        // Check for existing worker in agency
        $existingWorker = User::where('email', $email)
            ->where('user_type', 'worker')
            ->first();

        if ($existingWorker) {
            $inAgency = AgencyWorker::where('agency_id', $agency->id)
                ->where('worker_id', $existingWorker->id)
                ->where('status', 'active')
                ->exists();

            if ($inAgency) {
                $error = 'This worker is already in your agency.';
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $error], 422);
                }
                return back()->withErrors(['email' => $error])->withInput();
            }

            // Link existing worker
            $agencyWorker = AgencyWorker::create([
                'agency_id' => $agency->id,
                'worker_id' => $existingWorker->id,
                'commission_rate' => $request->input('commission_rate', 0),
                'status' => 'active',
                'notes' => $request->input('notes'),
                'added_at' => now(),
            ]);

            $message = 'Worker has been added to your agency.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'worker' => $agencyWorker->load('worker'),
                ]);
            }

            return redirect()->route('agency.workers.index')
                ->with('success', $message);
        }

        // Check for pending invitation
        $existingInvitation = AgencyInvitation::where('agency_id', $agency->id)
            ->where('email', $email)
            ->whereIn('status', ['pending', 'sent', 'viewed'])
            ->where('expires_at', '>', now())
            ->first();

        if ($existingInvitation) {
            $error = 'An invitation has already been sent to this email address.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $error], 422);
            }
            return back()->withErrors(['email' => $error])->withInput();
        }

        // Parse skills and certifications
        $skills = $request->input('skills');
        $certifications = $request->input('certifications');

        // Create invitation
        $invitation = AgencyInvitation::create([
            'agency_id' => $agency->id,
            'email' => $email,
            'phone' => $request->input('phone'),
            'name' => $request->input('name'),
            'type' => 'email',
            'status' => 'pending',
            'preset_commission_rate' => $request->input('commission_rate'),
            'preset_skills' => $skills,
            'preset_certifications' => $certifications,
            'personal_message' => $request->input('personal_message'),
            'invitation_ip' => $request->ip(),
        ]);

        // Send invitation email
        if ($request->boolean('send_email', true)) {
            try {
                $notifiable = new \App\Mail\NotifiableEmail($invitation->email, $invitation->name);
                $notifiable->notify(new AgencyWorkerInvitationNotification($invitation));
                $invitation->markAsSent();
            } catch (\Exception $e) {
                Log::error('AgencyWorkerOnboarding: Failed to send invitation email', [
                    'invitation_id' => $invitation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $message = 'Invitation has been sent to ' . $email;

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'invitation' => $invitation,
            ]);
        }

        return redirect()->route('agency.workers.invitations')
            ->with('success', $message);
    }

    /**
     * Sync workers from external system via API.
     *
     * @param SyncWorkersRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncExternalWorkers(SyncWorkersRequest $request)
    {
        $agency = Auth::user();
        $workers = $request->input('workers', []);
        $batchId = Str::uuid()->toString();

        $results = [
            'total' => count($workers),
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($workers as $index => $workerData) {
            // Validate worker data
            $validation = $this->importService->validateWorkerData($workerData);

            if (!$validation['valid']) {
                $results['failed']++;
                $results['errors'][] = [
                    'index' => $index,
                    'email' => $workerData['email'] ?? 'unknown',
                    'errors' => $validation['errors'],
                ];
                continue;
            }

            // Process worker
            $options = [
                'default_commission_rate' => $request->input('default_commission_rate'),
            ];

            $result = $this->importService->createOrUpdateWorker(
                $workerData,
                $agency->id,
                $batchId,
                $options
            );

            if ($result['success']) {
                $results['successful']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'index' => $index,
                    'email' => $workerData['email'],
                    'error' => $result['error'],
                ];
            }
        }

        // Send invitations if requested
        if ($request->boolean('send_invitations', true)) {
            $invitations = AgencyInvitation::where('batch_id', $batchId)
                ->where('status', 'pending')
                ->get();

            foreach ($invitations as $invitation) {
                try {
                    $notifiable = new \App\Mail\NotifiableEmail($invitation->email, $invitation->name);
                    $notifiable->notify(new AgencyWorkerInvitationNotification($invitation));
                    $invitation->markAsSent();
                } catch (\Exception $e) {
                    Log::error('AgencyWorkerOnboarding: Failed to send sync invitation', [
                        'invitation_id' => $invitation->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        Log::info('AgencyWorkerOnboarding: External sync completed', [
            'agency_id' => $agency->id,
            'batch_id' => $batchId,
            'results' => array_diff_key($results, ['errors' => []]),
        ]);

        return response()->json([
            'success' => $results['failed'] === 0,
            'batch_id' => $batchId,
            'results' => $results,
        ]);
    }

    /**
     * List sent invitations with status.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function showInvitations(Request $request)
    {
        $agency = Auth::user();

        $query = AgencyInvitation::where('agency_id', $agency->id);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by batch
        if ($request->has('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        // Search by email or name
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $invitations = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        // Get statistics
        $stats = [
            'total' => AgencyInvitation::where('agency_id', $agency->id)->count(),
            'pending' => AgencyInvitation::where('agency_id', $agency->id)
                ->whereIn('status', ['pending', 'sent'])
                ->count(),
            'viewed' => AgencyInvitation::where('agency_id', $agency->id)
                ->where('status', 'viewed')
                ->count(),
            'accepted' => AgencyInvitation::where('agency_id', $agency->id)
                ->where('status', 'accepted')
                ->count(),
            'expired' => AgencyInvitation::where('agency_id', $agency->id)
                ->where('status', 'expired')
                ->count(),
        ];

        // Get recent batches
        $batches = AgencyInvitation::where('agency_id', $agency->id)
            ->whereNotNull('batch_id')
            ->selectRaw('batch_id, COUNT(*) as count, MIN(created_at) as created_at')
            ->groupBy('batch_id')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('agency.workers.invitations', [
            'invitations' => $invitations,
            'stats' => $stats,
            'batches' => $batches,
            'currentStatus' => $request->status ?? 'all',
            'currentSearch' => $request->search ?? '',
        ]);
    }

    /**
     * Resend invitation email.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function resendInvitation(Request $request, $id)
    {
        $agency = Auth::user();

        $invitation = AgencyInvitation::where('agency_id', $agency->id)
            ->where('id', $id)
            ->first();

        if (!$invitation) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Invitation not found.'], 404);
            }
            return back()->with('error', 'Invitation not found.');
        }

        if ($invitation->status === 'accepted') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This invitation has already been accepted.',
                ], 422);
            }
            return back()->with('error', 'This invitation has already been accepted.');
        }

        if ($invitation->status === 'cancelled') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This invitation has been cancelled.',
                ], 422);
            }
            return back()->with('error', 'This invitation has been cancelled.');
        }

        // Resend invitation
        try {
            $notifiable = new \App\Mail\NotifiableEmail($invitation->email, $invitation->name);
            $notifiable->notify(new AgencyWorkerInvitationNotification($invitation));
            $invitation->resend();

            $message = 'Invitation has been resent to ' . $invitation->email;

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'invitation' => $invitation->fresh(),
                ]);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('AgencyWorkerOnboarding: Failed to resend invitation', [
                'invitation_id' => $invitation->id,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send invitation email.',
                ], 500);
            }

            return back()->with('error', 'Failed to send invitation email.');
        }
    }

    /**
     * Cancel pending invitation.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function cancelInvitation(Request $request, $id)
    {
        $agency = Auth::user();

        $invitation = AgencyInvitation::where('agency_id', $agency->id)
            ->where('id', $id)
            ->first();

        if (!$invitation) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Invitation not found.'], 404);
            }
            return back()->with('error', 'Invitation not found.');
        }

        if ($invitation->status === 'accepted') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel an accepted invitation.',
                ], 422);
            }
            return back()->with('error', 'Cannot cancel an accepted invitation.');
        }

        $invitation->cancel();

        $message = 'Invitation has been cancelled.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Download CSV template.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadTemplate()
    {
        $content = AgencyWorkerImportService::getCsvTemplate();

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, 'worker_import_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Get invitation link for sharing.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInvitationLink(Request $request, $id)
    {
        $agency = Auth::user();

        $invitation = AgencyInvitation::where('agency_id', $agency->id)
            ->where('id', $id)
            ->first();

        if (!$invitation) {
            return response()->json(['success' => false, 'message' => 'Invitation not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'url' => $invitation->getInvitationUrl(),
            'token' => $invitation->token,
            'expires_at' => $invitation->expires_at->toIso8601String(),
        ]);
    }

    /**
     * Generate a shareable invitation link (no specific email required).
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function generateShareableLink(Request $request)
    {
        $agency = Auth::user();

        $invitation = AgencyInvitation::create([
            'agency_id' => $agency->id,
            'type' => 'link',
            'status' => 'pending',
            'preset_commission_rate' => $request->input('commission_rate'),
            'personal_message' => $request->input('personal_message'),
            'invitation_ip' => $request->ip(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'url' => $invitation->getInvitationUrl(),
                'token' => $invitation->token,
                'expires_at' => $invitation->expires_at->toIso8601String(),
            ]);
        }

        return back()->with('success', 'Shareable link created: ' . $invitation->getInvitationUrl());
    }

    /**
     * Bulk cancel invitations.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkCancel(Request $request)
    {
        $agency = Auth::user();

        $request->validate([
            'invitation_ids' => 'required|array',
            'invitation_ids.*' => 'integer',
        ]);

        $cancelled = AgencyInvitation::where('agency_id', $agency->id)
            ->whereIn('id', $request->invitation_ids)
            ->whereNotIn('status', ['accepted', 'cancelled'])
            ->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'cancelled_count' => $cancelled,
            'message' => "{$cancelled} invitation(s) cancelled.",
        ]);
    }

    /**
     * Bulk resend invitations.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkResend(Request $request)
    {
        $agency = Auth::user();

        $request->validate([
            'invitation_ids' => 'required|array',
            'invitation_ids.*' => 'integer',
        ]);

        $invitations = AgencyInvitation::where('agency_id', $agency->id)
            ->whereIn('id', $request->invitation_ids)
            ->whereNotIn('status', ['accepted', 'cancelled'])
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($invitations as $invitation) {
            try {
                $notifiable = new \App\Mail\NotifiableEmail($invitation->email, $invitation->name);
                $notifiable->notify(new AgencyWorkerInvitationNotification($invitation));
                $invitation->resend();
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                Log::error('AgencyWorkerOnboarding: Bulk resend failed', [
                    'invitation_id' => $invitation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => $failed === 0,
            'sent_count' => $sent,
            'failed_count' => $failed,
            'message' => "{$sent} invitation(s) resent" . ($failed > 0 ? ", {$failed} failed." : '.'),
        ]);
    }

    /**
     * Format import result message.
     *
     * @param array $results
     * @return string
     */
    protected function formatImportMessage(array $results): string
    {
        $parts = [];

        if ($results['successful'] > 0) {
            $parts[] = "{$results['successful']} processed successfully";
        }

        if ($results['invited'] > 0) {
            $parts[] = "{$results['invited']} invitations sent";
        }

        if ($results['existing'] > 0) {
            $parts[] = "{$results['existing']} existing workers linked";
        }

        if ($results['skipped'] > 0) {
            $parts[] = "{$results['skipped']} skipped";
        }

        if ($results['failed'] > 0) {
            $parts[] = "{$results['failed']} failed";
        }

        return "Import complete: " . implode(', ', $parts) . ".";
    }
}
