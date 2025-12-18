<?php

namespace App\Http\Controllers;

use App\Models\CommunicationReport;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * CommunicationReportController
 *
 * COM-005: Communication Compliance
 * Handles user-submitted reports of inappropriate communications.
 */
class CommunicationReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the report form for a message.
     *
     * @return \Illuminate\View\View
     */
    public function createForMessage(Message $message)
    {
        // Verify user is part of the conversation
        $conversation = $message->conversation;
        if (! $conversation->hasParticipant(Auth::id())) {
            abort(403, 'You do not have permission to report this message.');
        }

        // Can't report your own messages
        if ($message->from_user_id === Auth::id()) {
            return redirect()->back()
                ->with('error', 'You cannot report your own message.');
        }

        $reportable = $message;
        $reportableType = 'message';
        $reportedUser = $message->sender;

        return view('reports.communication.create', compact('reportable', 'reportableType', 'reportedUser'));
    }

    /**
     * Show the report form for a conversation.
     *
     * @return \Illuminate\View\View
     */
    public function createForConversation(Conversation $conversation)
    {
        // Verify user is part of the conversation
        if (! $conversation->hasParticipant(Auth::id())) {
            abort(403, 'You do not have permission to report this conversation.');
        }

        // Determine the other party
        $reportedUser = Auth::id() === $conversation->worker_id
            ? $conversation->business
            : $conversation->worker;

        $reportable = $conversation;
        $reportableType = 'conversation';

        return view('reports.communication.create', compact('reportable', 'reportableType', 'reportedUser'));
    }

    /**
     * Store a new communication report.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reportable_type' => ['required', Rule::in(['message', 'conversation'])],
            'reportable_id' => ['required', 'integer'],
            'reported_user_id' => ['required', 'exists:users,id'],
            'reason' => ['required', Rule::in(CommunicationReport::getReasons())],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        // Verify the reportable exists and user has access
        if ($validated['reportable_type'] === 'message') {
            $reportable = Message::findOrFail($validated['reportable_id']);
            $conversation = $reportable->conversation;

            if (! $conversation->hasParticipant(Auth::id())) {
                abort(403, 'You do not have permission to report this message.');
            }

            if ($reportable->from_user_id === Auth::id()) {
                return redirect()->back()
                    ->with('error', 'You cannot report your own message.');
            }

            $reportableType = Message::class;
        } else {
            $reportable = Conversation::findOrFail($validated['reportable_id']);

            if (! $reportable->hasParticipant(Auth::id())) {
                abort(403, 'You do not have permission to report this conversation.');
            }

            // Can't report a conversation with yourself
            if ($validated['reported_user_id'] == Auth::id()) {
                return redirect()->back()
                    ->with('error', 'Invalid report submission.');
            }

            $reportableType = Conversation::class;
        }

        // Check for duplicate report
        $existingReport = CommunicationReport::where('reportable_type', $reportableType)
            ->where('reportable_id', $validated['reportable_id'])
            ->where('reporter_id', Auth::id())
            ->where('status', CommunicationReport::STATUS_PENDING)
            ->first();

        if ($existingReport) {
            return redirect()->route('messages.index')
                ->with('info', 'You have already reported this. Our team is reviewing it.');
        }

        // Create the report
        CommunicationReport::create([
            'reportable_type' => $reportableType,
            'reportable_id' => $validated['reportable_id'],
            'reporter_id' => Auth::id(),
            'reported_user_id' => $validated['reported_user_id'],
            'reason' => $validated['reason'],
            'description' => $validated['description'],
            'status' => CommunicationReport::STATUS_PENDING,
        ]);

        return redirect()->route('messages.index')
            ->with('success', 'Thank you for your report. Our team will review it shortly.');
    }

    /**
     * View user's submitted reports.
     *
     * @return \Illuminate\View\View
     */
    public function myReports()
    {
        $reports = CommunicationReport::where('reporter_id', Auth::id())
            ->with(['reportedUser', 'resolver'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('reports.communication.my-reports', compact('reports'));
    }

    /**
     * Cancel a pending report.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel(CommunicationReport $report)
    {
        // Verify ownership
        if ($report->reporter_id !== Auth::id()) {
            abort(403, 'You do not have permission to cancel this report.');
        }

        // Can only cancel pending reports
        if ($report->status !== CommunicationReport::STATUS_PENDING) {
            return redirect()->back()
                ->with('error', 'This report cannot be cancelled as it is already being processed.');
        }

        $report->delete();

        return redirect()->route('reports.communication.my-reports')
            ->with('success', 'Report cancelled successfully.');
    }
}
