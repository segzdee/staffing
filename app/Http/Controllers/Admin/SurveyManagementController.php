<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Services\FeedbackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * QUA-003: Admin Survey Management Controller
 *
 * Handles survey CRUD and analytics for administrators.
 */
class SurveyManagementController extends Controller
{
    public function __construct(protected FeedbackService $feedbackService) {}

    /**
     * Display a listing of surveys.
     */
    public function index(Request $request)
    {
        $query = Survey::query()->withCount('responses');

        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->ofType($request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $surveys = $query->latest()->paginate(15);

        // Get overall stats
        $stats = [
            'total_surveys' => Survey::count(),
            'active_surveys' => Survey::active()->count(),
            'total_responses' => SurveyResponse::count(),
            'responses_this_month' => SurveyResponse::whereMonth('created_at', now()->month)->count(),
        ];

        return view('admin.surveys.index', compact('surveys', 'stats'));
    }

    /**
     * Show the form for creating a new survey.
     */
    public function create()
    {
        $types = [
            Survey::TYPE_NPS => 'Net Promoter Score (NPS)',
            Survey::TYPE_CSAT => 'Customer Satisfaction (CSAT)',
            Survey::TYPE_POST_SHIFT => 'Post-Shift Feedback',
            Survey::TYPE_ONBOARDING => 'Onboarding Survey',
            Survey::TYPE_GENERAL => 'General Feedback',
        ];

        $audiences = [
            Survey::AUDIENCE_WORKERS => 'Workers Only',
            Survey::AUDIENCE_BUSINESSES => 'Businesses Only',
            Survey::AUDIENCE_ALL => 'All Users',
        ];

        return view('admin.surveys.create', compact('types', 'audiences'));
    }

    /**
     * Store a newly created survey.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:nps,csat,post_shift,onboarding,general',
            'target_audience' => 'required|in:workers,businesses,all',
            'questions' => 'required|array|min:1',
            'questions.*.type' => 'required|in:nps,rating,text,textarea,boolean,select,radio,checkbox',
            'questions.*.text' => 'required|string|max:500',
            'questions.*.required' => 'boolean',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $request->only(['name', 'description', 'type', 'target_audience', 'questions', 'is_active', 'starts_at', 'ends_at']);
        $data['slug'] = Str::slug($request->name).'-'.Str::random(6);

        $survey = $this->feedbackService->createSurvey($data);

        return redirect()
            ->route('admin.surveys.show', $survey->id)
            ->with('success', 'Survey created successfully.');
    }

    /**
     * Display the specified survey with analytics.
     */
    public function show($id)
    {
        $survey = Survey::withCount('responses')->findOrFail($id);

        // Get NPS data if applicable
        $npsData = null;
        if ($survey->isNps()) {
            $npsData = $this->feedbackService->calculateNPS($survey);
        }

        // Get response distribution over time
        $responsesByDay = SurveyResponse::where('survey_id', $id)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        // Get recent responses
        $recentResponses = $survey->responses()
            ->with('user:id,name,email')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.surveys.show', compact('survey', 'npsData', 'responsesByDay', 'recentResponses'));
    }

    /**
     * Show the form for editing the specified survey.
     */
    public function edit($id)
    {
        $survey = Survey::findOrFail($id);

        $types = [
            Survey::TYPE_NPS => 'Net Promoter Score (NPS)',
            Survey::TYPE_CSAT => 'Customer Satisfaction (CSAT)',
            Survey::TYPE_POST_SHIFT => 'Post-Shift Feedback',
            Survey::TYPE_ONBOARDING => 'Onboarding Survey',
            Survey::TYPE_GENERAL => 'General Feedback',
        ];

        $audiences = [
            Survey::AUDIENCE_WORKERS => 'Workers Only',
            Survey::AUDIENCE_BUSINESSES => 'Businesses Only',
            Survey::AUDIENCE_ALL => 'All Users',
        ];

        return view('admin.surveys.edit', compact('survey', 'types', 'audiences'));
    }

    /**
     * Update the specified survey.
     */
    public function update(Request $request, $id)
    {
        $survey = Survey::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:nps,csat,post_shift,onboarding,general',
            'target_audience' => 'required|in:workers,businesses,all',
            'questions' => 'required|array|min:1',
            'questions.*.type' => 'required|in:nps,rating,text,textarea,boolean,select,radio,checkbox',
            'questions.*.text' => 'required|string|max:500',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $survey->update($request->only([
            'name',
            'description',
            'type',
            'target_audience',
            'questions',
            'is_active',
            'starts_at',
            'ends_at',
        ]));

        return redirect()
            ->route('admin.surveys.show', $survey->id)
            ->with('success', 'Survey updated successfully.');
    }

    /**
     * Toggle survey active status.
     */
    public function toggleActive($id)
    {
        $survey = Survey::findOrFail($id);
        $survey->update(['is_active' => ! $survey->is_active]);

        $status = $survey->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Survey {$status} successfully.");
    }

    /**
     * Remove the specified survey.
     */
    public function destroy($id)
    {
        $survey = Survey::findOrFail($id);

        // Check if survey has responses
        if ($survey->responses()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete survey with existing responses. Deactivate it instead.');
        }

        $survey->delete();

        return redirect()
            ->route('admin.surveys.index')
            ->with('success', 'Survey deleted successfully.');
    }

    /**
     * View all responses for a survey.
     */
    public function responses($id)
    {
        $survey = Survey::findOrFail($id);

        $responses = $survey->responses()
            ->with(['user:id,name,email', 'shift:id,title'])
            ->latest()
            ->paginate(25);

        return view('admin.surveys.responses', compact('survey', 'responses'));
    }

    /**
     * Export survey responses as CSV.
     */
    public function exportResponses($id)
    {
        $survey = Survey::findOrFail($id);
        $responses = $survey->responses()->with(['user:id,name,email', 'shift:id,title'])->get();

        $filename = Str::slug($survey->name).'-responses-'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($survey, $responses) {
            $file = fopen('php://output', 'w');

            // Header row
            $header = ['ID', 'User', 'Email', 'Shift', 'NPS Score', 'Feedback', 'Submitted At'];

            // Add question headers
            foreach ($survey->questions as $question) {
                $header[] = $question['text'];
            }

            fputcsv($file, $header);

            // Data rows
            foreach ($responses as $response) {
                $row = [
                    $response->id,
                    $response->user->name ?? 'N/A',
                    $response->user->email ?? 'N/A',
                    $response->shift->title ?? 'N/A',
                    $response->nps_score ?? 'N/A',
                    $response->feedback_text ?? '',
                    $response->created_at->format('Y-m-d H:i:s'),
                ];

                // Add answers for each question
                foreach ($survey->questions as $question) {
                    $answer = $response->getAnswer($question['id']);
                    $row[] = is_array($answer) ? implode(', ', $answer) : ($answer ?? '');
                }

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Create default surveys.
     */
    public function createDefaults()
    {
        // Create NPS survey if not exists
        if (! Survey::where('type', Survey::TYPE_NPS)->exists()) {
            $this->feedbackService->createDefaultNPSSurvey();
        }

        // Create post-shift survey if not exists
        if (! Survey::where('type', Survey::TYPE_POST_SHIFT)->exists()) {
            $this->feedbackService->createDefaultPostShiftSurvey();
        }

        return redirect()
            ->route('admin.surveys.index')
            ->with('success', 'Default surveys created successfully.');
    }
}
