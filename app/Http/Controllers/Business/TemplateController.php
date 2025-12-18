<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\CommunicationTemplateRequest;
use App\Http\Requests\Business\SendTemplateRequest;
use App\Models\CommunicationTemplate;
use App\Models\Shift;
use App\Models\User;
use App\Services\CommunicationTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * BIZ-010: Communication Templates Controller
 *
 * Handles CRUD operations for business communication templates.
 */
class TemplateController extends Controller
{
    protected CommunicationTemplateService $templateService;

    public function __construct(CommunicationTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Display list of templates.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Ensure default templates exist
        $this->templateService->ensureDefaultTemplates($user);

        $query = CommunicationTemplate::forBusiness($user->id)
            ->withCount('sends');

        // Apply filters
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $templates = $query->orderBy('type')->orderBy('name')->paginate(20);

        $types = CommunicationTemplate::getTypes();
        $channels = CommunicationTemplate::getChannels();
        $analytics = $this->templateService->getTemplateAnalytics($user);

        return view('business.communication-templates.index', compact(
            'templates',
            'types',
            'channels',
            'analytics'
        ));
    }

    /**
     * Show create template form.
     */
    public function create(Request $request)
    {
        $types = CommunicationTemplate::getTypes();
        $channels = CommunicationTemplate::getChannels();

        $selectedType = $request->get('type', CommunicationTemplate::TYPE_CUSTOM);
        $variables = $this->templateService->getAvailableVariables($selectedType);
        $allVariables = CommunicationTemplate::getAvailableVariables();

        return view('business.communication-templates.create', compact(
            'types',
            'channels',
            'variables',
            'allVariables',
            'selectedType'
        ));
    }

    /**
     * Store a new template.
     */
    public function store(CommunicationTemplateRequest $request)
    {
        $user = Auth::user();

        try {
            $template = $this->templateService->createTemplate($user, $request->validated());

            return redirect()->route('business.communication-templates.show', $template)
                ->with('success', 'Template created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create template: '.$e->getMessage());
        }
    }

    /**
     * Display a template.
     */
    public function show(CommunicationTemplate $template)
    {
        $this->authorizeTemplate($template);

        $template->loadCount('sends');
        $usageStats = $template->getUsageStats();
        $preview = $this->templateService->previewTemplate($template);

        $recentSends = $template->sends()
            ->with(['recipient', 'shift'])
            ->latest()
            ->take(10)
            ->get();

        return view('business.communication-templates.show', compact(
            'template',
            'usageStats',
            'preview',
            'recentSends'
        ));
    }

    /**
     * Show edit template form.
     */
    public function edit(CommunicationTemplate $template)
    {
        $this->authorizeTemplate($template);

        if (! $template->isEditable()) {
            return redirect()->route('business.communication-templates.show', $template)
                ->with('error', 'System templates cannot be edited.');
        }

        $types = CommunicationTemplate::getTypes();
        $channels = CommunicationTemplate::getChannels();
        $variables = $this->templateService->getAvailableVariables($template->type);
        $allVariables = CommunicationTemplate::getAvailableVariables();

        return view('business.communication-templates.edit', compact(
            'template',
            'types',
            'channels',
            'variables',
            'allVariables'
        ));
    }

    /**
     * Update a template.
     */
    public function update(CommunicationTemplateRequest $request, CommunicationTemplate $template)
    {
        $this->authorizeTemplate($template);

        if (! $template->isEditable()) {
            return redirect()->route('business.communication-templates.show', $template)
                ->with('error', 'System templates cannot be edited.');
        }

        try {
            $this->templateService->updateTemplate($template, $request->validated());

            return redirect()->route('business.communication-templates.show', $template)
                ->with('success', 'Template updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update template: '.$e->getMessage());
        }
    }

    /**
     * Delete a template.
     */
    public function destroy(CommunicationTemplate $template)
    {
        $this->authorizeTemplate($template);

        if (! $template->isEditable()) {
            return redirect()->route('business.communication-templates.index')
                ->with('error', 'System templates cannot be deleted.');
        }

        $template->delete();

        return redirect()->route('business.communication-templates.index')
            ->with('success', 'Template deleted successfully.');
    }

    /**
     * Preview a template with sample data.
     */
    public function preview(Request $request, CommunicationTemplate $template)
    {
        $this->authorizeTemplate($template);

        $preview = $this->templateService->previewTemplate($template);

        if ($request->wantsJson()) {
            return response()->json([
                'subject' => $preview['subject'],
                'body' => $preview['body'],
            ]);
        }

        return view('business.communication-templates.preview', compact('template', 'preview'));
    }

    /**
     * Duplicate a template.
     */
    public function duplicate(CommunicationTemplate $template)
    {
        $this->authorizeTemplate($template);

        try {
            $copy = $this->templateService->duplicateTemplate($template);

            return redirect()->route('business.communication-templates.edit', $copy)
                ->with('success', 'Template duplicated. You can now customize it.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to duplicate template: '.$e->getMessage());
        }
    }

    /**
     * Set template as default.
     */
    public function setDefault(CommunicationTemplate $template)
    {
        $this->authorizeTemplate($template);

        $template->setAsDefault();

        return redirect()->back()
            ->with('success', 'Template set as default for '.$template->type_label.'.');
    }

    /**
     * Toggle template active status.
     */
    public function toggleActive(CommunicationTemplate $template)
    {
        $this->authorizeTemplate($template);

        $template->update(['is_active' => ! $template->is_active]);

        $status = $template->is_active ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "Template {$status} successfully.");
    }

    /**
     * Show send template form.
     */
    public function showSendForm(Request $request, CommunicationTemplate $template)
    {
        $this->authorizeTemplate($template);

        $user = Auth::user();

        // Get available recipients (workers who have worked with this business)
        $workers = User::where('user_type', 'worker')
            ->whereHas('shiftAssignments', function ($q) use ($user) {
                $q->whereHas('shift', function ($sq) use ($user) {
                    $sq->where('business_id', $user->id);
                });
            })
            ->orderBy('name')
            ->get();

        // Get shifts for context
        $shifts = Shift::where('business_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->orderBy('shift_date', 'desc')
            ->take(50)
            ->get();

        $preview = $this->templateService->previewTemplate($template);

        return view('business.communication-templates.send', compact(
            'template',
            'workers',
            'shifts',
            'preview'
        ));
    }

    /**
     * Send template to workers.
     */
    public function send(SendTemplateRequest $request, CommunicationTemplate $template)
    {
        $this->authorizeTemplate($template);

        $user = Auth::user();

        // Get recipients
        $recipientIds = $request->input('recipient_ids', []);
        $recipients = User::whereIn('id', $recipientIds)->get();

        if ($recipients->isEmpty()) {
            return redirect()->back()
                ->with('error', 'Please select at least one recipient.');
        }

        // Get shift if specified
        $shift = null;
        if ($request->filled('shift_id')) {
            $shift = Shift::where('business_id', $user->id)
                ->find($request->shift_id);
        }

        // Build variables
        $baseVariables = $this->templateService->buildBusinessVariables($user);
        if ($shift) {
            $baseVariables = array_merge($baseVariables, $this->templateService->buildShiftVariables($shift));
        }

        // Add any custom variables from the form
        if ($request->filled('custom_variables')) {
            $customVars = $request->input('custom_variables', []);
            $baseVariables = array_merge($baseVariables, $customVars);
        }

        // Send to all recipients
        $results = $this->templateService->sendBulkTemplate(
            $template,
            $user,
            $recipients,
            $baseVariables,
            $shift
        );

        $message = "Message sent to {$results['sent']} recipient(s).";
        if ($results['failed'] > 0) {
            $message .= " {$results['failed']} failed.";
        }

        return redirect()->route('business.communication-templates.show', $template)
            ->with('success', $message);
    }

    /**
     * View send history for all templates.
     */
    public function history(Request $request)
    {
        $user = Auth::user();

        $filters = [
            'template_id' => $request->get('template_id'),
            'status' => $request->get('status'),
            'channel' => $request->get('channel'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'per_page' => $request->get('per_page', 20),
        ];

        $sends = $this->templateService->getSendHistory($user, $filters);

        $templates = CommunicationTemplate::forBusiness($user->id)
            ->orderBy('name')
            ->get();

        return view('business.communication-templates.history', compact('sends', 'templates', 'filters'));
    }

    /**
     * Get variables for a template type (AJAX).
     */
    public function getVariables(Request $request)
    {
        $type = $request->get('type', CommunicationTemplate::TYPE_CUSTOM);
        $variables = $this->templateService->getAvailableVariables($type);

        return response()->json(['variables' => $variables]);
    }

    /**
     * Render template preview (AJAX).
     */
    public function renderPreview(Request $request)
    {
        $body = $request->input('body', '');
        $subject = $request->input('subject', '');

        // Create a temporary template for preview
        $template = new CommunicationTemplate([
            'body' => $body,
            'subject' => $subject,
            'type' => $request->input('type', CommunicationTemplate::TYPE_CUSTOM),
        ]);

        $preview = $this->templateService->previewTemplate($template);

        return response()->json([
            'subject' => $preview['subject'],
            'body' => $preview['body'],
        ]);
    }

    /**
     * Authorize that user owns the template.
     */
    protected function authorizeTemplate(CommunicationTemplate $template): void
    {
        if ($template->business_id !== Auth::id()) {
            abort(403, 'You do not have permission to access this template.');
        }
    }
}
