<?php

namespace App\Http\Controllers\Feedback;

use App\Http\Controllers\Controller;
use App\Models\BugReport;
use App\Services\FeedbackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * QUA-003: BugReportController
 *
 * Handles bug report submission and viewing.
 */
class BugReportController extends Controller
{
    public function __construct(protected FeedbackService $feedbackService) {}

    /**
     * Display user's bug reports.
     */
    public function index()
    {
        $user = Auth::user();

        $bugReports = BugReport::where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        return view('feedback.bug-reports.index', compact('bugReports'));
    }

    /**
     * Show the form for creating a new bug report.
     */
    public function create()
    {
        $severityLevels = [
            BugReport::SEVERITY_LOW => 'Low - Minor inconvenience',
            BugReport::SEVERITY_MEDIUM => 'Medium - Affects functionality',
            BugReport::SEVERITY_HIGH => 'High - Major impact on usage',
            BugReport::SEVERITY_CRITICAL => 'Critical - System unusable',
        ];

        return view('feedback.bug-reports.create', compact('severityLevels'));
    }

    /**
     * Store a newly created bug report.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:10|max:200',
            'description' => 'required|string|min:30|max:5000',
            'steps_to_reproduce' => 'nullable|string|max:5000',
            'expected_behavior' => 'nullable|string|max:1000',
            'actual_behavior' => 'nullable|string|max:1000',
            'severity' => 'required|in:low,medium,high,critical',
            'screenshots.*' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Handle screenshot uploads
        $attachments = [];
        if ($request->hasFile('screenshots')) {
            foreach ($request->file('screenshots') as $file) {
                $path = $file->store('bug-reports/'.$user->id, 'public');
                $attachments[] = Storage::url($path);
            }
        }

        // Auto-detect browser and OS from user agent
        $userAgent = $request->header('User-Agent');
        $browserInfo = $this->parseUserAgent($userAgent);

        $bugReport = $this->feedbackService->submitBugReport($user, [
            'title' => $request->title,
            'description' => $request->description,
            'steps_to_reproduce' => $request->steps_to_reproduce,
            'expected_behavior' => $request->expected_behavior,
            'actual_behavior' => $request->actual_behavior,
            'severity' => $request->severity,
            'attachments' => $attachments,
            'browser' => $browserInfo['browser'],
            'os' => $browserInfo['os'],
        ]);

        return redirect()
            ->route('feedback.bug-reports.show', $bugReport->id)
            ->with('success', 'Your bug report has been submitted. Our team will investigate shortly.');
    }

    /**
     * Display the specified bug report.
     */
    public function show($id)
    {
        $user = Auth::user();

        $bugReport = BugReport::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return view('feedback.bug-reports.show', compact('bugReport'));
    }

    /**
     * Add additional information or attachments to an open bug report.
     */
    public function addDetails(Request $request, $id)
    {
        $user = Auth::user();

        $bugReport = BugReport::where('id', $id)
            ->where('user_id', $user->id)
            ->open()
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'additional_info' => 'nullable|string|max:2000',
            'screenshots.*' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:5120',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator);
        }

        // Handle new screenshot uploads
        if ($request->hasFile('screenshots')) {
            foreach ($request->file('screenshots') as $file) {
                $path = $file->store('bug-reports/'.$user->id, 'public');
                $bugReport->addAttachment(Storage::url($path));
            }
        }

        // Append additional info to description
        if ($request->filled('additional_info')) {
            $bugReport->update([
                'description' => $bugReport->description."\n\n--- Additional Information (Added ".now()->format('Y-m-d H:i').")---\n".$request->additional_info,
            ]);
        }

        return redirect()
            ->route('feedback.bug-reports.show', $bugReport->id)
            ->with('success', 'Additional information has been added to your bug report.');
    }

    /**
     * Parse user agent to extract browser and OS.
     */
    protected function parseUserAgent(?string $userAgent): array
    {
        $result = [
            'browser' => 'Unknown',
            'os' => 'Unknown',
        ];

        if (! $userAgent) {
            return $result;
        }

        // Detect browser
        if (str_contains($userAgent, 'Firefox')) {
            $result['browser'] = 'Firefox';
        } elseif (str_contains($userAgent, 'Edg')) {
            $result['browser'] = 'Microsoft Edge';
        } elseif (str_contains($userAgent, 'Chrome')) {
            $result['browser'] = 'Chrome';
        } elseif (str_contains($userAgent, 'Safari')) {
            $result['browser'] = 'Safari';
        } elseif (str_contains($userAgent, 'MSIE') || str_contains($userAgent, 'Trident')) {
            $result['browser'] = 'Internet Explorer';
        }

        // Detect OS
        if (str_contains($userAgent, 'Windows NT 10')) {
            $result['os'] = 'Windows 10/11';
        } elseif (str_contains($userAgent, 'Windows')) {
            $result['os'] = 'Windows';
        } elseif (str_contains($userAgent, 'Mac OS X')) {
            $result['os'] = 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $result['os'] = 'Linux';
        } elseif (str_contains($userAgent, 'Android')) {
            $result['os'] = 'Android';
        } elseif (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
            $result['os'] = 'iOS';
        }

        return $result;
    }
}
