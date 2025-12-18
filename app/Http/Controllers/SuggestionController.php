<?php

namespace App\Http\Controllers;

use App\Models\ImprovementSuggestion;
use App\Services\ContinuousImprovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * QUA-005: Continuous Improvement System
 * Controller for user-facing suggestion functionality.
 */
class SuggestionController extends BaseController
{
    public function __construct(
        protected ContinuousImprovementService $improvementService
    ) {}

    /**
     * Display the suggestions list (public view).
     */
    public function index(Request $request)
    {
        $query = ImprovementSuggestion::with(['submitter'])
            ->public();

        // Filter by category
        if ($request->filled('category')) {
            $query->withCategory($request->category);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->withStatus($request->status);
        }

        // Sort options
        $sort = $request->get('sort', 'votes');
        switch ($sort) {
            case 'recent':
                $query->recent();
                break;
            case 'votes':
            default:
                $query->topVoted();
                break;
        }

        $suggestions = $query->paginate(15);

        // Get user's votes if authenticated
        $userVotes = [];
        if (Auth::check()) {
            $userVotes = Auth::user()
                ->hasMany(\App\Models\SuggestionVote::class, 'user_id')
                ->pluck('vote_type', 'suggestion_id')
                ->toArray();
        }

        return view('suggestions.index', [
            'suggestions' => $suggestions,
            'categories' => ImprovementSuggestion::getCategories(),
            'statuses' => ImprovementSuggestion::getStatuses(),
            'currentCategory' => $request->category,
            'currentStatus' => $request->status,
            'currentSort' => $sort,
            'userVotes' => $userVotes,
        ]);
    }

    /**
     * Show the form for creating a new suggestion.
     */
    public function create()
    {
        return view('suggestions.create', [
            'categories' => ImprovementSuggestion::getCategories(),
            'priorities' => ImprovementSuggestion::getPriorities(),
        ]);
    }

    /**
     * Store a newly created suggestion.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|in:'.implode(',', array_keys(ImprovementSuggestion::getCategories())),
            'priority' => 'required|in:'.implode(',', array_keys(ImprovementSuggestion::getPriorities())),
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:20|max:5000',
            'expected_impact' => 'nullable|string|max:2000',
        ]);

        $suggestion = $this->improvementService->submitSuggestion(
            Auth::user(),
            $validated
        );

        if ($request->wantsJson()) {
            return $this->successResponse($suggestion, 'Suggestion submitted successfully.', 201);
        }

        return redirect()
            ->route('suggestions.show', $suggestion)
            ->with('success', 'Your suggestion has been submitted. Thank you for helping us improve!');
    }

    /**
     * Display the specified suggestion.
     */
    public function show(ImprovementSuggestion $suggestion)
    {
        // Don't show rejected suggestions to non-admin users
        if ($suggestion->status === ImprovementSuggestion::STATUS_REJECTED) {
            if (! Auth::check() || (Auth::user()->id !== $suggestion->submitted_by && ! Auth::user()->isAdmin())) {
                abort(404);
            }
        }

        $suggestion->load(['submitter', 'assignee', 'suggestionVotes']);

        $userVote = null;
        if (Auth::check()) {
            $userVote = $suggestion->getUserVote(Auth::user());
        }

        return view('suggestions.show', [
            'suggestion' => $suggestion,
            'userVote' => $userVote,
        ]);
    }

    /**
     * Show the form for editing a suggestion (own suggestions only).
     */
    public function edit(ImprovementSuggestion $suggestion)
    {
        // Only the submitter can edit, and only if still in editable status
        if (Auth::id() !== $suggestion->submitted_by) {
            abort(403, 'You can only edit your own suggestions.');
        }

        if (! $suggestion->canBeEdited()) {
            return redirect()
                ->route('suggestions.show', $suggestion)
                ->with('error', 'This suggestion can no longer be edited.');
        }

        return view('suggestions.edit', [
            'suggestion' => $suggestion,
            'categories' => ImprovementSuggestion::getCategories(),
            'priorities' => ImprovementSuggestion::getPriorities(),
        ]);
    }

    /**
     * Update the specified suggestion.
     */
    public function update(Request $request, ImprovementSuggestion $suggestion)
    {
        // Only the submitter can update
        if (Auth::id() !== $suggestion->submitted_by) {
            abort(403, 'You can only edit your own suggestions.');
        }

        if (! $suggestion->canBeEdited()) {
            if ($request->wantsJson()) {
                return $this->errorResponse('This suggestion can no longer be edited.', 403);
            }

            return redirect()
                ->route('suggestions.show', $suggestion)
                ->with('error', 'This suggestion can no longer be edited.');
        }

        $validated = $request->validate([
            'category' => 'required|in:'.implode(',', array_keys(ImprovementSuggestion::getCategories())),
            'priority' => 'required|in:'.implode(',', array_keys(ImprovementSuggestion::getPriorities())),
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:20|max:5000',
            'expected_impact' => 'nullable|string|max:2000',
        ]);

        $suggestion->update($validated);

        if ($request->wantsJson()) {
            return $this->successResponse($suggestion->fresh(), 'Suggestion updated successfully.');
        }

        return redirect()
            ->route('suggestions.show', $suggestion)
            ->with('success', 'Your suggestion has been updated.');
    }

    /**
     * Remove the specified suggestion (own suggestions only, if editable).
     */
    public function destroy(Request $request, ImprovementSuggestion $suggestion)
    {
        // Only the submitter can delete
        if (Auth::id() !== $suggestion->submitted_by) {
            abort(403, 'You can only delete your own suggestions.');
        }

        if (! $suggestion->canBeEdited()) {
            if ($request->wantsJson()) {
                return $this->errorResponse('This suggestion can no longer be deleted.', 403);
            }

            return redirect()
                ->route('suggestions.show', $suggestion)
                ->with('error', 'This suggestion can no longer be deleted.');
        }

        $suggestion->delete();

        if ($request->wantsJson()) {
            return $this->successResponse(null, 'Suggestion deleted successfully.');
        }

        return redirect()
            ->route('suggestions.index')
            ->with('success', 'Your suggestion has been deleted.');
    }

    /**
     * Display the current user's suggestions.
     */
    public function mySuggestions()
    {
        $suggestions = $this->improvementService->getUserSuggestions(Auth::user());

        return view('suggestions.my-suggestions', [
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Vote on a suggestion.
     */
    public function vote(Request $request, ImprovementSuggestion $suggestion)
    {
        $validated = $request->validate([
            'vote_type' => 'required|in:up,down',
        ]);

        $result = $this->improvementService->voteSuggestion(
            $suggestion,
            Auth::user(),
            $validated['vote_type']
        );

        if ($request->wantsJson()) {
            if ($result['success']) {
                return $this->successResponse([
                    'votes' => $result['votes'],
                    'user_vote' => $result['user_vote'],
                ], $result['message']);
            }

            return $this->errorResponse($result['message'], 400);
        }

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Remove vote from a suggestion.
     */
    public function removeVote(Request $request, ImprovementSuggestion $suggestion)
    {
        $vote = $suggestion->suggestionVotes()
            ->where('user_id', Auth::id())
            ->first();

        if (! $vote) {
            if ($request->wantsJson()) {
                return $this->errorResponse('You have not voted on this suggestion.', 400);
            }

            return back()->with('error', 'You have not voted on this suggestion.');
        }

        $vote->delete();

        if ($request->wantsJson()) {
            return $this->successResponse([
                'votes' => $suggestion->fresh()->votes,
                'user_vote' => null,
            ], 'Vote removed.');
        }

        return back()->with('success', 'Your vote has been removed.');
    }
}
