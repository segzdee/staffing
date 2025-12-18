<?php

namespace App\Http\Controllers\Feedback;

use App\Http\Controllers\Controller;
use App\Models\FeatureRequest;
use App\Services\FeedbackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * QUA-003: FeatureRequestController
 *
 * Handles feature request submission and voting.
 */
class FeatureRequestController extends Controller
{
    public function __construct(protected FeedbackService $feedbackService) {}

    /**
     * Display feature requests.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = FeatureRequest::query()
            ->with('user:id,name')
            ->withCount('votes');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->withStatus($request->status);
        } else {
            $query->open();
        }

        // Filter by category
        if ($request->has('category') && $request->category !== 'all') {
            $query->inCategory($request->category);
        }

        // Sort options
        $sortBy = $request->get('sort', 'popular');
        $query->when($sortBy === 'popular', fn ($q) => $q->popular());
        $query->when($sortBy === 'newest', fn ($q) => $q->latest());
        $query->when($sortBy === 'oldest', fn ($q) => $q->oldest());

        $featureRequests = $query->paginate(15)->appends($request->query());

        // Get user's votes for quick lookup
        $userVotes = $user
            ? FeatureRequest::whereHas('votes', fn ($q) => $q->where('user_id', $user->id))
                ->pluck('id')
                ->toArray()
            : [];

        // Get stats
        $stats = [
            'total' => FeatureRequest::count(),
            'open' => FeatureRequest::open()->count(),
            'planned' => FeatureRequest::withStatus(FeatureRequest::STATUS_PLANNED)->count(),
            'completed' => FeatureRequest::withStatus(FeatureRequest::STATUS_COMPLETED)->count(),
        ];

        return view('feedback.feature-requests.index', compact(
            'featureRequests',
            'userVotes',
            'stats'
        ));
    }

    /**
     * Show the form for creating a new feature request.
     */
    public function create()
    {
        $categories = [
            FeatureRequest::CATEGORY_UI => 'User Interface',
            FeatureRequest::CATEGORY_FEATURE => 'New Feature',
            FeatureRequest::CATEGORY_INTEGRATION => 'Integration',
            FeatureRequest::CATEGORY_MOBILE => 'Mobile App',
            FeatureRequest::CATEGORY_OTHER => 'Other',
        ];

        return view('feedback.feature-requests.create', compact('categories'));
    }

    /**
     * Store a newly created feature request.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:10|max:200',
            'description' => 'required|string|min:50|max:5000',
            'category' => 'required|in:ui,feature,integration,mobile,other',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $featureRequest = $this->feedbackService->submitFeatureRequest($user, [
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
        ]);

        // Auto-vote for the creator
        $featureRequest->votes()->create(['user_id' => $user->id]);

        return redirect()
            ->route('feedback.feature-requests.show', $featureRequest->id)
            ->with('success', 'Your feature request has been submitted! Others can now vote for it.');
    }

    /**
     * Display the specified feature request.
     */
    public function show($id)
    {
        $featureRequest = FeatureRequest::with(['user:id,name', 'votes.user:id,name'])
            ->withCount('votes')
            ->findOrFail($id);

        $user = Auth::user();
        $hasVoted = $user ? $featureRequest->hasUserVoted($user->id) : false;

        // Get related requests
        $relatedRequests = FeatureRequest::where('category', $featureRequest->category)
            ->where('id', '!=', $featureRequest->id)
            ->open()
            ->popular()
            ->limit(5)
            ->get();

        return view('feedback.feature-requests.show', compact(
            'featureRequest',
            'hasVoted',
            'relatedRequests'
        ));
    }

    /**
     * Toggle vote for a feature request.
     */
    public function vote(Request $request, $id)
    {
        $user = Auth::user();
        $featureRequest = FeatureRequest::findOrFail($id);

        if (! $featureRequest->isOpen()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Voting is closed for this feature request.',
                ], 400);
            }

            return redirect()
                ->back()
                ->with('error', 'Voting is closed for this feature request.');
        }

        $result = $this->feedbackService->toggleFeatureVote($featureRequest, $user);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'voted' => $result['voted'],
                'vote_count' => $result['vote_count'],
            ]);
        }

        $message = $result['voted']
            ? 'Your vote has been recorded!'
            : 'Your vote has been removed.';

        return redirect()
            ->back()
            ->with('success', $message);
    }

    /**
     * Display user's own feature requests.
     */
    public function myRequests()
    {
        $user = Auth::user();

        $featureRequests = FeatureRequest::where('user_id', $user->id)
            ->withCount('votes')
            ->latest()
            ->paginate(15);

        return view('feedback.feature-requests.my-requests', compact('featureRequests'));
    }
}
