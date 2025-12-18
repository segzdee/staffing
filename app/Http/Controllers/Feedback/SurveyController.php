<?php

namespace App\Http\Controllers\Feedback;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\Survey;
use App\Services\FeedbackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * QUA-003: SurveyController
 *
 * Handles user-facing survey display and response submission.
 */
class SurveyController extends Controller
{
    public function __construct(protected FeedbackService $feedbackService) {}

    /**
     * Display available surveys for the current user.
     */
    public function index()
    {
        $user = Auth::user();
        $surveys = $this->feedbackService->getAvailableSurveys($user);
        $pendingShiftSurveys = $this->feedbackService->getPendingPostShiftSurveys($user);

        return view('feedback.surveys.index', compact('surveys', 'pendingShiftSurveys'));
    }

    /**
     * Display a specific survey.
     */
    public function show($slug)
    {
        $survey = Survey::where('slug', $slug)->firstOrFail();
        $user = Auth::user();

        // Check if survey is available
        if (! $survey->isAvailable()) {
            return redirect()
                ->route('feedback.surveys.index')
                ->with('error', 'This survey is no longer available.');
        }

        // Check if user already responded (for non-shift surveys)
        if (! $survey->isPostShift() && $survey->hasUserResponded($user->id)) {
            return redirect()
                ->route('feedback.surveys.index')
                ->with('info', 'You have already completed this survey. Thank you for your feedback!');
        }

        return view('feedback.surveys.show', compact('survey'));
    }

    /**
     * Display a post-shift survey for a specific shift.
     */
    public function showPostShift($slug, $shiftId)
    {
        $survey = Survey::where('slug', $slug)->firstOrFail();
        $user = Auth::user();
        $shift = Shift::findOrFail($shiftId);

        // Verify user worked this shift
        $assignment = $shift->assignments()
            ->where('worker_id', $user->id)
            ->where('status', 'completed')
            ->first();

        if (! $assignment) {
            return redirect()
                ->route('feedback.surveys.index')
                ->with('error', 'You cannot submit feedback for this shift.');
        }

        // Check if already responded for this shift
        if ($survey->hasUserRespondedForShift($user->id, $shiftId)) {
            return redirect()
                ->route('feedback.surveys.index')
                ->with('info', 'You have already provided feedback for this shift.');
        }

        return view('feedback.surveys.post-shift', compact('survey', 'shift', 'assignment'));
    }

    /**
     * Submit a survey response.
     */
    public function submit(Request $request, $slug)
    {
        $survey = Survey::where('slug', $slug)->firstOrFail();
        $user = Auth::user();

        // Validate survey is available
        if (! $survey->isAvailable()) {
            return redirect()
                ->route('feedback.surveys.index')
                ->with('error', 'This survey is no longer available.');
        }

        // Build validation rules from survey questions
        $rules = $this->buildValidationRules($survey);
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Get shift if this is a post-shift survey
        $shift = null;
        if ($request->has('shift_id')) {
            $shift = Shift::find($request->shift_id);

            // Check if already responded for this shift
            if ($shift && $survey->hasUserRespondedForShift($user->id, $shift->id)) {
                return redirect()
                    ->route('feedback.surveys.index')
                    ->with('info', 'You have already provided feedback for this shift.');
            }
        }

        // Check if user already responded (for non-shift surveys)
        if (! $shift && $survey->hasUserResponded($user->id)) {
            return redirect()
                ->route('feedback.surveys.index')
                ->with('info', 'You have already completed this survey.');
        }

        // Collect answers
        $answers = [];
        foreach ($survey->questions as $question) {
            $questionId = $question['id'];
            if ($request->has($questionId)) {
                $answers[$questionId] = $request->input($questionId);
            }
        }

        // Submit response
        $this->feedbackService->submitResponse($survey, $user, $answers, $shift);

        return redirect()
            ->route('feedback.surveys.thanks')
            ->with('success', 'Thank you for your feedback!');
    }

    /**
     * Display thank you page after survey submission.
     */
    public function thanks()
    {
        return view('feedback.surveys.thanks');
    }

    /**
     * Build validation rules from survey questions.
     */
    protected function buildValidationRules(Survey $survey): array
    {
        $rules = [];

        foreach ($survey->questions as $question) {
            $questionId = $question['id'];
            $questionRules = [];

            if ($question['required'] ?? false) {
                $questionRules[] = 'required';
            } else {
                $questionRules[] = 'nullable';
            }

            switch ($question['type'] ?? 'text') {
                case 'nps':
                    $questionRules[] = 'integer';
                    $questionRules[] = 'between:0,10';
                    break;
                case 'rating':
                    $min = $question['options']['min'] ?? 1;
                    $max = $question['options']['max'] ?? 5;
                    $questionRules[] = 'integer';
                    $questionRules[] = "between:{$min},{$max}";
                    break;
                case 'boolean':
                    $questionRules[] = 'boolean';
                    break;
                case 'select':
                case 'radio':
                    if (! empty($question['options'])) {
                        $options = is_array($question['options']) ? implode(',', array_keys($question['options'])) : '';
                        if ($options) {
                            $questionRules[] = "in:{$options}";
                        }
                    }
                    break;
                case 'textarea':
                    $questionRules[] = 'string';
                    $questionRules[] = 'max:5000';
                    break;
                default:
                    $questionRules[] = 'string';
                    $questionRules[] = 'max:1000';
            }

            $rules[$questionId] = $questionRules;
        }

        return $rules;
    }
}
