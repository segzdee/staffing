<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\PenaltyAppeal;
use App\Models\WorkerPenalty;
use App\Notifications\AppealUnderReviewNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AppealController extends Controller
{
    /**
     * Display a listing of the worker's penalties.
     */
    public function index()
    {
        $worker = Auth::user();

        $penalties = WorkerPenalty::where('worker_id', $worker->id)
            ->with(['shift', 'business', 'appeals'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('worker.penalties.index', compact('penalties'));
    }

    /**
     * Show the form for creating a new appeal.
     */
    public function create($penaltyId)
    {
        $worker = Auth::user();

        $penalty = WorkerPenalty::where('id', $penaltyId)
            ->where('worker_id', $worker->id)
            ->with(['shift', 'business'])
            ->firstOrFail();

        // Check if penalty can be appealed
        if (!$penalty->canBeAppealed()) {
            return redirect()
                ->route('worker.penalties.index')
                ->with('error', 'This penalty cannot be appealed at this time.');
        }

        // Check if already appealed
        if ($penalty->activeAppeal) {
            return redirect()
                ->route('worker.appeals.show', $penalty->activeAppeal->id)
                ->with('info', 'You have already submitted an appeal for this penalty.');
        }

        return view('worker.appeals.create', compact('penalty'));
    }

    /**
     * Store a newly created appeal in storage.
     */
    public function store(Request $request, $penaltyId)
    {
        $worker = Auth::user();

        $penalty = WorkerPenalty::where('id', $penaltyId)
            ->where('worker_id', $worker->id)
            ->firstOrFail();

        // Validate penalty can be appealed
        if (!$penalty->canBeAppealed()) {
            return redirect()
                ->route('worker.penalties.index')
                ->with('error', 'This penalty cannot be appealed at this time.');
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'appeal_reason' => 'required|string|min:50|max:5000',
            'additional_notes' => 'nullable|string|max:2000',
            'evidence_files.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Handle evidence file uploads
        $evidenceUrls = [];
        if ($request->hasFile('evidence_files')) {
            foreach ($request->file('evidence_files') as $file) {
                // Store in cloud storage (configure Cloudinary or S3)
                $path = $file->store('penalty-appeals/' . $worker->id, 'public');
                $evidenceUrls[] = Storage::url($path);
            }
        }

        // Create appeal
        $appeal = PenaltyAppeal::create([
            'penalty_id' => $penalty->id,
            'worker_id' => $worker->id,
            'appeal_reason' => $request->appeal_reason,
            'additional_notes' => $request->additional_notes,
            'evidence_urls' => $evidenceUrls,
            'status' => 'pending',
            'submitted_at' => now(),
            'deadline_at' => $penalty->issued_at->addDays(14),
        ]);

        // Send confirmation notification to worker that appeal is under review
        $worker->notify(new AppealUnderReviewNotification($appeal));

        return redirect()
            ->route('worker.appeals.show', $appeal->id)
            ->with('success', 'Your appeal has been submitted successfully. An admin will review it within 3-5 business days.');
    }

    /**
     * Display the specified appeal.
     */
    public function show($appealId)
    {
        $worker = Auth::user();

        $appeal = PenaltyAppeal::where('id', $appealId)
            ->where('worker_id', $worker->id)
            ->with(['penalty.shift', 'penalty.business', 'reviewedByAdmin'])
            ->firstOrFail();

        return view('worker.appeals.show', compact('appeal'));
    }

    /**
     * Show the form for editing the specified appeal (only if pending).
     */
    public function edit($appealId)
    {
        $worker = Auth::user();

        $appeal = PenaltyAppeal::where('id', $appealId)
            ->where('worker_id', $worker->id)
            ->where('status', 'pending')
            ->with(['penalty.shift', 'penalty.business'])
            ->firstOrFail();

        // Check if still within deadline
        if (!$appeal->isWithinDeadline()) {
            return redirect()
                ->route('worker.appeals.show', $appeal->id)
                ->with('error', 'The deadline for editing this appeal has passed.');
        }

        return view('worker.appeals.edit', compact('appeal'));
    }

    /**
     * Update the specified appeal in storage (only if pending).
     */
    public function update(Request $request, $appealId)
    {
        $worker = Auth::user();

        $appeal = PenaltyAppeal::where('id', $appealId)
            ->where('worker_id', $worker->id)
            ->where('status', 'pending')
            ->firstOrFail();

        // Check if still within deadline
        if (!$appeal->isWithinDeadline()) {
            return redirect()
                ->route('worker.appeals.show', $appeal->id)
                ->with('error', 'The deadline for editing this appeal has passed.');
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'appeal_reason' => 'required|string|min:50|max:5000',
            'additional_notes' => 'nullable|string|max:2000',
            'evidence_files.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Handle new evidence file uploads
        $evidenceUrls = $appeal->evidence_urls ?? [];
        if ($request->hasFile('evidence_files')) {
            foreach ($request->file('evidence_files') as $file) {
                $path = $file->store('penalty-appeals/' . $worker->id, 'public');
                $evidenceUrls[] = Storage::url($path);
            }
        }

        // Update appeal
        $appeal->update([
            'appeal_reason' => $request->appeal_reason,
            'additional_notes' => $request->additional_notes,
            'evidence_urls' => $evidenceUrls,
        ]);

        return redirect()
            ->route('worker.appeals.show', $appeal->id)
            ->with('success', 'Your appeal has been updated successfully.');
    }

    /**
     * Add additional evidence to an existing appeal.
     */
    public function addEvidence(Request $request, $appealId)
    {
        $worker = Auth::user();

        $appeal = PenaltyAppeal::where('id', $appealId)
            ->where('worker_id', $worker->id)
            ->whereIn('status', ['pending', 'under_review'])
            ->firstOrFail();

        // Validate request
        $validator = Validator::make($request->all(), [
            'evidence_file' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator);
        }

        // Upload evidence
        $path = $request->file('evidence_file')->store('penalty-appeals/' . $worker->id, 'public');
        $appeal->addEvidence(Storage::url($path));

        return redirect()
            ->route('worker.appeals.show', $appeal->id)
            ->with('success', 'Additional evidence has been added to your appeal.');
    }

    /**
     * Remove evidence from an appeal (only if pending).
     */
    public function removeEvidence(Request $request, $appealId)
    {
        $worker = Auth::user();

        $appeal = PenaltyAppeal::where('id', $appealId)
            ->where('worker_id', $worker->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $evidenceIndex = $request->input('evidence_index');
        $evidence = $appeal->evidence_urls ?? [];

        if (isset($evidence[$evidenceIndex])) {
            // Remove from storage
            $path = str_replace('/storage/', '', $evidence[$evidenceIndex]);
            Storage::disk('public')->delete($path);

            // Remove from array
            unset($evidence[$evidenceIndex]);
            $appeal->update(['evidence_urls' => array_values($evidence)]);
        }

        return redirect()
            ->route('worker.appeals.show', $appeal->id)
            ->with('success', 'Evidence removed successfully.');
    }
}
