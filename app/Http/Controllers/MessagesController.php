<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MessagesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display inbox/conversations list.
     */
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');

        $query = Conversation::with(['worker', 'business', 'shift', 'lastMessage'])
            ->forUser(Auth::id())
            ->orderBy('last_message_at', 'desc');

        if ($filter === 'unread') {
            $query->withUnreadFor(Auth::id());
        } elseif ($filter === 'archived') {
            $query->where('status', 'archived');
        } else {
            $query->active();
        }

        $conversations = $query->get();

        // Eager load unread message counts to avoid N+1 queries
        $conversationIds = $conversations->pluck('id');
        $unreadCounts = \App\Models\Message::whereIn('conversation_id', $conversationIds)
            ->where('to_user_id', Auth::id())
            ->whereNull('read_at')
            ->selectRaw('conversation_id, COUNT(*) as count')
            ->groupBy('conversation_id')
            ->pluck('count', 'conversation_id');

        // Add other_party_name to each conversation
        foreach ($conversations as $conv) {
            if (Auth::user()->isWorker()) {
                $conv->other_party_name = $conv->business->name ?? 'Business';
                $conv->last_message = $conv->lastMessage->message ?? 'No messages yet';
            } else {
                $conv->other_party_name = $conv->worker->name ?? 'Worker';
                $conv->last_message = $conv->lastMessage->message ?? 'No messages yet';
            }

            // Add unread count from pre-loaded data
            $conv->unread_count = $unreadCounts->get($conv->id, 0);
        }

        // Get unread count
        $unreadCount = Conversation::forUser(Auth::id())
            ->active()
            ->withUnreadFor(Auth::id())
            ->count();

        return view('messages.index', compact('conversations', 'unreadCount', 'filter'));
    }

    /**
     * Show a specific conversation.
     */
    public function show($conversationId)
    {
        $conversation = Conversation::with([
            'worker.workerProfile',
            'business.businessProfile',
            'shift',
            'messages.sender'
        ])->findOrFail($conversationId);

        // Check authorization
        if (!$conversation->hasParticipant(Auth::id())) {
            abort(403, 'You do not have permission to view this conversation.');
        }

        // Mark messages as read
        $conversation->markAsReadFor(Auth::id());

        // Get messages
        $messages = $conversation->messages()->orderBy('created_at', 'asc')->get();

        // Get all conversations for sidebar
        $conversations = Conversation::with(['worker', 'business', 'lastMessage'])
            ->forUser(Auth::id())
            ->active()
            ->orderBy('last_message_at', 'desc')
            ->get();

        // Add other_party_name to each conversation
        foreach ($conversations as $conv) {
            if (Auth::user()->isWorker()) {
                $conv->other_party_name = $conv->business->name ?? 'Business';
            } else {
                $conv->other_party_name = $conv->worker->name ?? 'Worker';
            }
        }

        // Add other_party_name to current conversation
        if (Auth::user()->isWorker()) {
            $conversation->other_party_name = $conversation->business->name ?? 'Business';
        } else {
            $conversation->other_party_name = $conversation->worker->name ?? 'Worker';
        }

        return view('messages.show', compact('conversation', 'messages', 'conversations'));
    }

    /**
     * Start a new conversation with a business about a shift.
     */
    public function createWithBusiness($businessId, Request $request)
    {
        // Check authorization (workers only)
        if (!Auth::user()->isWorker()) {
            abort(403, 'Only workers can initiate conversations with businesses.');
        }

        $business = User::where('id', $businessId)
            ->where('user_type', 'business')
            ->firstOrFail();

        $shiftId = $request->get('shift_id');
        $shift = null;

        if ($shiftId) {
            $shift = Shift::findOrFail($shiftId);
        }

        // Check if conversation already exists
        $conversation = Conversation::where('worker_id', Auth::id())
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId)
            ->first();

        if ($conversation) {
            return redirect()->route('messages.show', $conversation->id);
        }

        return view('messages.create', compact('business', 'shift'));
    }

    /**
     * Start a new conversation with a worker (business initiating).
     */
    public function createWithWorker($workerId, Request $request)
    {
        // Check authorization (businesses only)
        if (!Auth::user()->isBusiness()) {
            abort(403, 'Only businesses can initiate conversations with workers.');
        }

        $worker = User::where('id', $workerId)
            ->where('user_type', 'worker')
            ->firstOrFail();

        $shiftId = $request->get('shift_id');
        $shift = null;

        if ($shiftId) {
            $shift = Shift::findOrFail($shiftId);
        }

        // Check if conversation already exists
        $conversation = Conversation::where('worker_id', $workerId)
            ->where('business_id', Auth::id())
            ->where('shift_id', $shiftId)
            ->first();

        if ($conversation) {
            return redirect()->route('messages.show', $conversation->id);
        }

        return view('messages.create', compact('worker', 'shift'));
    }

    /**
     * Send a new message (creates conversation if needed).
     */
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to_user_id' => 'required|exists:users,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'message' => 'required|string|max:5000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $toUser = User::findOrFail($request->to_user_id);

        // Validate user types
        if (Auth::user()->isWorker() && !$toUser->isBusiness()) {
            return redirect()->back()->with('error', 'Workers can only message businesses.');
        }

        if (Auth::user()->isBusiness() && !$toUser->isWorker()) {
            return redirect()->back()->with('error', 'Businesses can only message workers.');
        }

        // Find or create conversation
        $workerId = Auth::user()->isWorker() ? Auth::id() : $toUser->id;
        $businessId = Auth::user()->isBusiness() ? Auth::id() : $toUser->id;

        $conversation = Conversation::firstOrCreate([
            'worker_id' => $workerId,
            'business_id' => $businessId,
            'shift_id' => $request->shift_id,
        ], [
            'subject' => $request->shift_id ? 'Shift #' . $request->shift_id : 'General Inquiry',
            'status' => 'active',
        ]);

        // Handle attachment upload
        $attachmentUrl = null;
        $attachmentType = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('message-attachments', 'public');
            $attachmentUrl = $path;
            $attachmentType = $file->getClientOriginalExtension();
        }

        // Create message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'from_user_id' => Auth::id(),
            'to_user_id' => $toUser->id,
            'message' => $request->message,
            'attachment_url' => $attachmentUrl,
            'attachment_type' => $attachmentType,
        ]);

        // Update conversation last message timestamp
        $conversation->update(['last_message_at' => now()]);

        // TODO: Send notification to recipient
        // event(new MessageSent($message));

        return redirect()->route('messages.show', $conversation->id)
            ->with('success', 'Message sent successfully!');
    }

    /**
     * Archive a conversation.
     */
    public function archive($conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Check authorization
        if (!$conversation->hasParticipant(Auth::id())) {
            abort(403, 'You do not have permission to archive this conversation.');
        }

        $conversation->update(['status' => 'archived']);

        return redirect()->route('messages.index')
            ->with('success', 'Conversation archived.');
    }

    /**
     * Restore an archived conversation.
     */
    public function restore($conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Check authorization
        if (!$conversation->hasParticipant(Auth::id())) {
            abort(403, 'You do not have permission to restore this conversation.');
        }

        $conversation->update(['status' => 'active']);

        return redirect()->route('messages.show', $conversationId)
            ->with('success', 'Conversation restored.');
    }

    /**
     * Get unread message count (AJAX endpoint).
     */
    public function unreadCount()
    {
        $count = Message::forRecipient(Auth::id())
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }
}
