<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\EmailService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * COM-003: Admin Email Controller
 *
 * Manages email templates, logs, and statistics for administrators.
 */
class EmailController extends Controller
{
    public function __construct(protected EmailService $emailService) {}

    /**
     * Display email dashboard with statistics.
     */
    public function index()
    {
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        $stats = $this->emailService->getEmailStats($startDate, $endDate);
        $templates = EmailTemplate::all();
        $recentLogs = EmailLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.email.index', [
            'stats' => $stats,
            'templates' => $templates,
            'recentLogs' => $recentLogs,
        ]);
    }

    /**
     * Display list of email templates.
     */
    public function templates()
    {
        $templates = EmailTemplate::with('creator')
            ->orderBy('category')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.email.templates', [
            'templates' => $templates,
            'categories' => EmailTemplate::getCategories(),
        ]);
    }

    /**
     * Show form to create a new template.
     */
    public function createTemplate()
    {
        return view('admin.email.create-template', [
            'categories' => EmailTemplate::getCategories(),
            'defaultVariables' => config('email_templates.default_variables'),
        ]);
    }

    /**
     * Store a new email template.
     */
    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:255|unique:email_templates,slug|regex:/^[a-z0-9_]+$/',
            'name' => 'required|string|max:255',
            'category' => ['required', Rule::in(array_keys(EmailTemplate::getCategories()))],
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $validated['variables'] = $request->input('variables', []);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = auth()->id();

        $template = EmailTemplate::create($validated);

        return redirect()
            ->route('admin.email.templates')
            ->with('success', "Template '{$template->name}' created successfully.");
    }

    /**
     * Show form to edit an email template.
     */
    public function editTemplate(EmailTemplate $template)
    {
        return view('admin.email.edit-template', [
            'template' => $template,
            'categories' => EmailTemplate::getCategories(),
            'defaultVariables' => config('email_templates.default_variables'),
        ]);
    }

    /**
     * Update an email template.
     */
    public function updateTemplate(Request $request, EmailTemplate $template)
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_]+$/', Rule::unique('email_templates')->ignore($template->id)],
            'name' => 'required|string|max:255',
            'category' => ['required', Rule::in(array_keys(EmailTemplate::getCategories()))],
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $validated['variables'] = $request->input('variables', []);
        $validated['is_active'] = $request->boolean('is_active', true);

        $template->update($validated);

        return redirect()
            ->route('admin.email.templates')
            ->with('success', "Template '{$template->name}' updated successfully.");
    }

    /**
     * Delete an email template.
     */
    public function destroyTemplate(EmailTemplate $template)
    {
        $name = $template->name;
        $template->delete();

        return redirect()
            ->route('admin.email.templates')
            ->with('success', "Template '{$name}' deleted successfully.");
    }

    /**
     * Preview a template with sample data.
     */
    public function previewTemplate(Request $request, EmailTemplate $template)
    {
        $variables = $request->input('variables', []);
        $rendered = $this->emailService->renderTemplate($template, $variables);

        if ($request->wantsJson()) {
            return response()->json($rendered);
        }

        return view('admin.email.preview-template', [
            'template' => $template,
            'rendered' => $rendered,
        ]);
    }

    /**
     * Send a test email.
     */
    public function sendTestEmail(Request $request, EmailTemplate $template)
    {
        $validated = $request->validate([
            'to_email' => 'required|email',
            'variables' => 'nullable|array',
        ]);

        $variables = $validated['variables'] ?? [];
        $log = $this->emailService->sendTestEmail($validated['to_email'], $template, $variables);

        if ($log && $log->status !== EmailLog::STATUS_FAILED) {
            return back()->with('success', "Test email sent to {$validated['to_email']}.");
        }

        return back()->with('error', 'Failed to send test email: '.($log?->error_message ?? 'Unknown error'));
    }

    /**
     * Display email logs.
     */
    public function logs(Request $request)
    {
        $query = EmailLog::with(['user', 'template']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by template
        if ($request->filled('template')) {
            $query->where('template_slug', $request->template);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->dateRange(
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            );
        }

        // Search by email
        if ($request->filled('search')) {
            $query->where('to_email', 'like', '%'.$request->search.'%');
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(25);
        $templates = EmailTemplate::pluck('name', 'slug');

        return view('admin.email.logs', [
            'logs' => $logs,
            'templates' => $templates,
            'statuses' => EmailLog::getStatuses(),
            'filters' => $request->only(['status', 'template', 'start_date', 'end_date', 'search']),
        ]);
    }

    /**
     * View a specific email log.
     */
    public function showLog(EmailLog $log)
    {
        $log->load(['user', 'template']);

        return view('admin.email.show-log', [
            'log' => $log,
        ]);
    }

    /**
     * Display email statistics.
     */
    public function stats(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfDay();

        $stats = $this->emailService->getEmailStats($startDate, $endDate);

        // Get daily breakdown
        $dailyStats = EmailLog::query()
            ->dateRange($startDate, $endDate)
            ->selectRaw('DATE(created_at) as date, status, COUNT(*) as count')
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(function ($group) {
                return $group->pluck('count', 'status');
            });

        return view('admin.email.stats', [
            'stats' => $stats,
            'dailyStats' => $dailyStats,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Display bounced emails for management.
     */
    public function bounces()
    {
        $bounces = $this->emailService->getBouncedEmails(100);

        // Group by email address to show repeat bounces
        $groupedBounces = $bounces->groupBy('to_email');

        return view('admin.email.bounces', [
            'bounces' => $bounces,
            'groupedBounces' => $groupedBounces,
        ]);
    }

    /**
     * Retry sending a failed email.
     */
    public function retryEmail(EmailLog $log)
    {
        $newLog = $this->emailService->retryEmail($log);

        if ($newLog && $newLog->status !== EmailLog::STATUS_FAILED) {
            return back()->with('success', 'Email queued for resend.');
        }

        return back()->with('error', 'Failed to retry email.');
    }

    /**
     * Send bulk email to users.
     */
    public function bulkSend(Request $request)
    {
        $validated = $request->validate([
            'template_slug' => 'required|exists:email_templates,slug',
            'user_type' => 'nullable|in:worker,business,agency,all',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $template = EmailTemplate::findBySlug($validated['template_slug']);

        if (! $template) {
            return back()->with('error', 'Template not found.');
        }

        $query = User::query();

        if (! empty($validated['user_ids'])) {
            $query->whereIn('id', $validated['user_ids']);
        } elseif (isset($validated['user_type']) && $validated['user_type'] !== 'all') {
            $query->where('user_type', $validated['user_type']);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            return back()->with('error', 'No users found matching criteria.');
        }

        $logs = $this->emailService->sendBulkEmail($users, $validated['template_slug']);

        $sentCount = collect($logs)->filter(fn ($log) => $log->status !== EmailLog::STATUS_FAILED)->count();

        return back()->with('success', "{$sentCount} emails queued for sending.");
    }

    /**
     * Export email logs to CSV.
     */
    public function exportLogs(Request $request)
    {
        $query = EmailLog::query();

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->dateRange(
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            );
        }

        $logs = $query->orderBy('created_at', 'desc')->limit(10000)->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="email-logs-'.now()->format('Y-m-d').'.csv"',
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'To Email', 'Template', 'Subject', 'Status', 'Sent At', 'Opened At', 'Clicked At', 'Error']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->to_email,
                    $log->template_slug,
                    $log->subject,
                    $log->status,
                    $log->sent_at?->toDateTimeString(),
                    $log->opened_at?->toDateTimeString(),
                    $log->clicked_at?->toDateTimeString(),
                    $log->error_message,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
