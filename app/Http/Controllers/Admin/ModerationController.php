<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedPhrase;
use App\Models\CommunicationReport;
use App\Models\MessageModerationLog;
use App\Models\User;
use App\Services\ContentModerationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * ModerationController
 *
 * COM-005: Communication Compliance
 * Admin controller for content moderation, blocked phrases management,
 * and communication report review.
 */
class ModerationController extends Controller
{
    protected ContentModerationService $moderationService;

    /**
     * Create a new controller instance.
     */
    public function __construct(ContentModerationService $moderationService)
    {
        $this->moderationService = $moderationService;
    }

    /**
     * Display moderation dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $stats = [
            'pending_reviews' => MessageModerationLog::requiringReview()->count(),
            'open_reports' => CommunicationReport::open()->count(),
            'blocked_today' => MessageModerationLog::blocked()
                ->whereDate('created_at', today())
                ->count(),
            'flagged_today' => MessageModerationLog::flagged()
                ->whereDate('created_at', today())
                ->count(),
            'critical_issues' => MessageModerationLog::critical()
                ->requiringReview()
                ->count(),
            'total_blocked_phrases' => BlockedPhrase::active()->count(),
        ];

        // Recent flagged content
        $recentFlagged = MessageModerationLog::with(['user', 'reviewer'])
            ->requiringReview()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Recent reports
        $recentReports = CommunicationReport::with(['reporter', 'reportedUser'])
            ->pending()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.moderation.index', compact('stats', 'recentFlagged', 'recentReports'));
    }

    // =========================================================================
    // MODERATION LOGS
    // =========================================================================

    /**
     * Display moderation logs requiring review.
     *
     * @return \Illuminate\View\View
     */
    public function logs(Request $request)
    {
        $query = MessageModerationLog::with(['user', 'reviewer'])
            ->orderBy('created_at', 'desc');

        // Filter by review status
        if ($request->filled('review_status')) {
            if ($request->review_status === 'pending') {
                $query->requiringReview();
            } elseif ($request->review_status === 'reviewed') {
                $query->reviewed();
            }
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->byAction($request->action);
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->bySeverity($request->severity);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->forUser($request->user_id);
        }

        // Search in content
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('original_content', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $logs = $query->paginate(25)->withQueryString();

        return view('admin.moderation.logs', compact('logs'));
    }

    /**
     * Display a single moderation log.
     *
     * @return \Illuminate\View\View
     */
    public function showLog(MessageModerationLog $log)
    {
        $log->load(['user', 'reviewer', 'moderatable']);

        // Get user's moderation history
        $userHistory = MessageModerationLog::forUser($log->user_id)
            ->where('id', '!=', $log->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $userStats = $this->moderationService->getUserModerationStats($log->user_id);

        return view('admin.moderation.show-log', compact('log', 'userHistory', 'userStats'));
    }

    /**
     * Review a moderation log.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reviewLog(Request $request, MessageModerationLog $log)
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in([
                MessageModerationLog::ACTION_ALLOWED,
                MessageModerationLog::ACTION_FLAGGED,
                MessageModerationLog::ACTION_BLOCKED,
                MessageModerationLog::ACTION_REDACTED,
            ])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->moderationService->reviewModeration(
            $log,
            auth()->user(),
            $validated['action'],
            $validated['notes']
        );

        return redirect()->route('admin.moderation.logs')
            ->with('success', 'Moderation log reviewed successfully.');
    }

    /**
     * Bulk review moderation logs.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function bulkReview(Request $request)
    {
        $validated = $request->validate([
            'log_ids' => ['required', 'array', 'min:1'],
            'log_ids.*' => ['exists:message_moderation_logs,id'],
            'action' => ['required', Rule::in([
                MessageModerationLog::ACTION_ALLOWED,
                MessageModerationLog::ACTION_FLAGGED,
                MessageModerationLog::ACTION_BLOCKED,
                MessageModerationLog::ACTION_REDACTED,
            ])],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $logs = MessageModerationLog::whereIn('id', $validated['log_ids'])
            ->requiringReview()
            ->get();

        $reviewed = 0;
        foreach ($logs as $log) {
            $this->moderationService->reviewModeration(
                $log,
                auth()->user(),
                $validated['action'],
                $validated['notes']
            );
            $reviewed++;
        }

        $message = "{$reviewed} log(s) reviewed successfully.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'count' => $reviewed]);
        }

        return redirect()->route('admin.moderation.logs')
            ->with('success', $message);
    }

    // =========================================================================
    // COMMUNICATION REPORTS
    // =========================================================================

    /**
     * Display communication reports.
     *
     * @return \Illuminate\View\View
     */
    public function reports(Request $request)
    {
        $query = CommunicationReport::with(['reporter', 'reportedUser', 'resolver'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by reason
        if ($request->filled('reason')) {
            $query->byReason($request->reason);
        }

        // Filter by reported user
        if ($request->filled('reported_user_id')) {
            $query->againstUser($request->reported_user_id);
        }

        // Search
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('reporter', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('reportedUser', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $reports = $query->paginate(25)->withQueryString();

        return view('admin.moderation.reports', compact('reports'));
    }

    /**
     * Display a single report.
     *
     * @return \Illuminate\View\View
     */
    public function showReport(CommunicationReport $report)
    {
        $report->load(['reporter', 'reportedUser', 'resolver', 'reportable']);

        // Get other reports against this user
        $otherReports = CommunicationReport::againstUser($report->reported_user_id)
            ->where('id', '!=', $report->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get reported user's moderation stats
        $userStats = $this->moderationService->getUserModerationStats($report->reported_user_id);

        return view('admin.moderation.show-report', compact('report', 'otherReports', 'userStats'));
    }

    /**
     * Start investigating a report.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function investigateReport(CommunicationReport $report)
    {
        if (! $report->isPending()) {
            return redirect()->back()
                ->with('error', 'This report is already being processed.');
        }

        $report->startInvestigation();

        return redirect()->route('admin.moderation.reports.show', $report)
            ->with('success', 'Investigation started.');
    }

    /**
     * Resolve a report.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resolveReport(Request $request, CommunicationReport $report)
    {
        $validated = $request->validate([
            'resolution_notes' => ['required', 'string', 'max:2000'],
            'take_action' => ['nullable', 'boolean'],
            'action_type' => ['required_if:take_action,1', 'nullable', 'string'],
        ]);

        $report->resolve(auth()->user(), $validated['resolution_notes']);

        // Optionally take action against the reported user
        if ($request->boolean('take_action') && $request->filled('action_type')) {
            $this->takeActionAgainstUser($report->reportedUser, $request->action_type);
        }

        return redirect()->route('admin.moderation.reports')
            ->with('success', 'Report resolved successfully.');
    }

    /**
     * Dismiss a report.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function dismissReport(Request $request, CommunicationReport $report)
    {
        $validated = $request->validate([
            'resolution_notes' => ['required', 'string', 'max:2000'],
        ]);

        $report->dismiss(auth()->user(), $validated['resolution_notes']);

        return redirect()->route('admin.moderation.reports')
            ->with('success', 'Report dismissed.');
    }

    // =========================================================================
    // BLOCKED PHRASES
    // =========================================================================

    /**
     * Display blocked phrases.
     *
     * @return \Illuminate\View\View
     */
    public function blockedPhrases(Request $request)
    {
        $query = BlockedPhrase::orderBy('created_at', 'desc');

        // Filter by type
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->byAction($request->action);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        // Search
        if ($request->filled('q')) {
            $query->where('phrase', 'like', '%'.$request->q.'%');
        }

        $phrases = $query->paginate(50)->withQueryString();

        return view('admin.moderation.blocked-phrases', compact('phrases'));
    }

    /**
     * Show form to create a blocked phrase.
     *
     * @return \Illuminate\View\View
     */
    public function createBlockedPhrase()
    {
        return view('admin.moderation.create-blocked-phrase');
    }

    /**
     * Store a new blocked phrase.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeBlockedPhrase(Request $request)
    {
        $validated = $request->validate([
            'phrase' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(BlockedPhrase::getTypes())],
            'action' => ['required', Rule::in(BlockedPhrase::getActions())],
            'is_regex' => ['boolean'],
            'case_sensitive' => ['boolean'],
        ]);

        // Validate regex if provided
        if ($request->boolean('is_regex')) {
            $pattern = '/'.$validated['phrase'].'/';
            if (@preg_match($pattern, '') === false) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['phrase' => 'Invalid regular expression pattern.']);
            }
        }

        $this->moderationService->addBlockedPhrase(
            $validated['phrase'],
            $validated['type'],
            $validated['action'],
            $request->boolean('is_regex'),
            $request->boolean('case_sensitive')
        );

        return redirect()->route('admin.moderation.blocked-phrases')
            ->with('success', 'Blocked phrase added successfully.');
    }

    /**
     * Show form to edit a blocked phrase.
     *
     * @return \Illuminate\View\View
     */
    public function editBlockedPhrase(BlockedPhrase $phrase)
    {
        return view('admin.moderation.edit-blocked-phrase', compact('phrase'));
    }

    /**
     * Update a blocked phrase.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateBlockedPhrase(Request $request, BlockedPhrase $phrase)
    {
        $validated = $request->validate([
            'phrase' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(BlockedPhrase::getTypes())],
            'action' => ['required', Rule::in(BlockedPhrase::getActions())],
            'is_regex' => ['boolean'],
            'case_sensitive' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        // Validate regex if provided
        if ($request->boolean('is_regex')) {
            $pattern = '/'.$validated['phrase'].'/';
            if (@preg_match($pattern, '') === false) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['phrase' => 'Invalid regular expression pattern.']);
            }
        }

        $phrase->update([
            'phrase' => $validated['phrase'],
            'type' => $validated['type'],
            'action' => $validated['action'],
            'is_regex' => $request->boolean('is_regex'),
            'case_sensitive' => $request->boolean('case_sensitive'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->moderationService->clearBlockedPhrasesCache();

        return redirect()->route('admin.moderation.blocked-phrases')
            ->with('success', 'Blocked phrase updated successfully.');
    }

    /**
     * Delete a blocked phrase.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteBlockedPhrase(BlockedPhrase $phrase)
    {
        $phrase->delete();
        $this->moderationService->clearBlockedPhrasesCache();

        return redirect()->route('admin.moderation.blocked-phrases')
            ->with('success', 'Blocked phrase deleted successfully.');
    }

    /**
     * Toggle blocked phrase status.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleBlockedPhrase(BlockedPhrase $phrase)
    {
        $phrase->update(['is_active' => ! $phrase->is_active]);
        $this->moderationService->clearBlockedPhrasesCache();

        return response()->json([
            'success' => true,
            'is_active' => $phrase->is_active,
        ]);
    }

    /**
     * Import blocked phrases from a file.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importBlockedPhrases(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:1024'],
            'type' => ['required', Rule::in(BlockedPhrase::getTypes())],
            'action' => ['required', Rule::in(BlockedPhrase::getActions())],
        ]);

        $file = $request->file('file');
        $content = file_get_contents($file->getPathname());
        $lines = array_filter(array_map('trim', explode("\n", $content)));

        $imported = 0;
        foreach ($lines as $line) {
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Check if phrase already exists
            $exists = BlockedPhrase::where('phrase', $line)
                ->where('type', $request->type)
                ->exists();

            if (! $exists) {
                BlockedPhrase::create([
                    'phrase' => $line,
                    'type' => $request->type,
                    'action' => $request->action,
                    'is_regex' => false,
                    'case_sensitive' => false,
                    'is_active' => true,
                ]);
                $imported++;
            }
        }

        $this->moderationService->clearBlockedPhrasesCache();

        return redirect()->route('admin.moderation.blocked-phrases')
            ->with('success', "{$imported} phrase(s) imported successfully.");
    }

    // =========================================================================
    // USER MODERATION HISTORY
    // =========================================================================

    /**
     * Display moderation history for a user.
     *
     * @return \Illuminate\View\View
     */
    public function userHistory(User $user)
    {
        $logs = MessageModerationLog::forUser($user->id)
            ->with('reviewer')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        $reports = CommunicationReport::againstUser($user->id)
            ->with(['reporter', 'resolver'])
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        $stats = $this->moderationService->getUserModerationStats($user->id);

        return view('admin.moderation.user-history', compact('user', 'logs', 'reports', 'stats'));
    }

    /**
     * Take action against a user based on report.
     */
    protected function takeActionAgainstUser(User $user, string $actionType): void
    {
        switch ($actionType) {
            case 'warn':
                // Send warning notification
                // $user->notify(new ModerationWarningNotification());
                break;

            case 'mute_24h':
                // Mute messaging for 24 hours
                // This could set a flag on the user profile
                break;

            case 'mute_7d':
                // Mute messaging for 7 days
                break;

            case 'suspend':
                // Suspend the user
                $user->suspend(7, 'Communication policy violation');
                break;
        }
    }

    /**
     * Test content against moderation rules.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testContent(Request $request)
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $result = $this->moderationService->moderateContent(
            $validated['content'],
            auth()->user()
        );

        return response()->json([
            'success' => true,
            'result' => $result,
        ]);
    }
}
