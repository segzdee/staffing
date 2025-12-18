<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * WhatsApp Template Controller
 *
 * COM-004: SMS/WhatsApp Alerts
 * Admin interface for managing WhatsApp message templates.
 */
class WhatsAppTemplateController extends Controller
{
    public function __construct(
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Display all WhatsApp templates.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Permission check
        if (! $user->is_dev_account && ! $user->hasPermission('messaging')) {
            return view('admin.unauthorized');
        }

        $query = WhatsAppTemplate::query();

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('template_id', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Category filter
        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        // Active filter
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $templates = $query->orderBy('name')->paginate(20)->withQueryString();

        // Get statistics
        $stats = [
            'total' => WhatsAppTemplate::count(),
            'approved' => WhatsAppTemplate::where('status', 'approved')->count(),
            'pending' => WhatsAppTemplate::where('status', 'pending')->count(),
            'rejected' => WhatsAppTemplate::where('status', 'rejected')->count(),
            'active' => WhatsAppTemplate::where('is_active', true)->where('status', 'approved')->count(),
        ];

        // Get message stats (last 30 days)
        $messageStats = SmsLog::whatsapp()
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status IN ("delivered", "read") THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(cost) as total_cost
            ')
            ->first();

        return view('admin.whatsapp.index', compact('templates', 'stats', 'messageStats'));
    }

    /**
     * Show form to create a new template.
     */
    public function create()
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('messaging')) {
            return view('admin.unauthorized');
        }

        return view('admin.whatsapp.create');
    }

    /**
     * Store a new template.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('messaging')) {
            return redirect()->route('admin.whatsapp.index')
                ->with('error', 'You do not have permission to create templates.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:whatsapp_templates,name'],
            'template_id' => ['required', 'string', 'max:255', 'unique:whatsapp_templates,template_id'],
            'language' => ['required', 'string', 'max:10'],
            'category' => ['required', Rule::in(['utility', 'marketing', 'authentication'])],
            'content' => ['required', 'string'],
            'header_type' => ['nullable', Rule::in(['text', 'image', 'document', 'video'])],
            'header_content' => ['nullable', 'string'],
            'buttons' => ['nullable', 'array'],
            'footer' => ['nullable', 'string'],
        ]);

        $header = null;
        if ($validated['header_type']) {
            $header = [
                'type' => $validated['header_type'],
                'content' => $validated['header_content'] ?? '',
            ];
        }

        $template = WhatsAppTemplate::create([
            'name' => $validated['name'],
            'template_id' => $validated['template_id'],
            'language' => $validated['language'],
            'category' => $validated['category'],
            'content' => $validated['content'],
            'header' => $header,
            'buttons' => $validated['buttons'] ?? null,
            'footer' => $validated['footer'] ? ['text' => $validated['footer']] : null,
            'status' => 'pending',
            'is_active' => false,
        ]);

        return redirect()->route('admin.whatsapp.index')
            ->with('success', "Template '{$template->name}' created successfully. It will become active once approved by Meta.");
    }

    /**
     * Show template details.
     */
    public function show(WhatsAppTemplate $template)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('messaging')) {
            return view('admin.unauthorized');
        }

        // Get usage statistics
        $usageStats = SmsLog::whatsapp()
            ->where('template_id', $template->template_id)
            ->selectRaw('
                COUNT(*) as total_sent,
                SUM(CASE WHEN status IN ("delivered", "read") THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = "read" THEN 1 ELSE 0 END) as read_count,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(cost) as total_cost,
                MIN(created_at) as first_used,
                MAX(created_at) as last_used
            ')
            ->first();

        // Get recent messages using this template
        $recentMessages = SmsLog::whatsapp()
            ->where('template_id', $template->template_id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.whatsapp.show', compact('template', 'usageStats', 'recentMessages'));
    }

    /**
     * Show form to edit a template.
     */
    public function edit(WhatsAppTemplate $template)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('messaging')) {
            return view('admin.unauthorized');
        }

        return view('admin.whatsapp.edit', compact('template'));
    }

    /**
     * Update a template.
     */
    public function update(Request $request, WhatsAppTemplate $template)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('messaging')) {
            return redirect()->route('admin.whatsapp.index')
                ->with('error', 'You do not have permission to update templates.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('whatsapp_templates')->ignore($template->id)],
            'template_id' => ['required', 'string', 'max:255', Rule::unique('whatsapp_templates')->ignore($template->id)],
            'language' => ['required', 'string', 'max:10'],
            'category' => ['required', Rule::in(['utility', 'marketing', 'authentication'])],
            'content' => ['required', 'string'],
            'header_type' => ['nullable', Rule::in(['text', 'image', 'document', 'video'])],
            'header_content' => ['nullable', 'string'],
            'buttons' => ['nullable', 'array'],
            'footer' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $header = null;
        if ($validated['header_type']) {
            $header = [
                'type' => $validated['header_type'],
                'content' => $validated['header_content'] ?? '',
            ];
        }

        $template->update([
            'name' => $validated['name'],
            'template_id' => $validated['template_id'],
            'language' => $validated['language'],
            'category' => $validated['category'],
            'content' => $validated['content'],
            'header' => $header,
            'buttons' => $validated['buttons'] ?? null,
            'footer' => $validated['footer'] ? ['text' => $validated['footer']] : null,
            'is_active' => $validated['is_active'] ?? $template->is_active,
        ]);

        return redirect()->route('admin.whatsapp.show', $template)
            ->with('success', 'Template updated successfully.');
    }

    /**
     * Delete a template.
     */
    public function destroy(WhatsAppTemplate $template)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('messaging')) {
            return redirect()->route('admin.whatsapp.index')
                ->with('error', 'You do not have permission to delete templates.');
        }

        $name = $template->name;
        $template->delete();

        return redirect()->route('admin.whatsapp.index')
            ->with('success', "Template '{$name}' deleted successfully.");
    }

    /**
     * Toggle template active status.
     */
    public function toggleActive(WhatsAppTemplate $template)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('messaging')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($template->status !== 'approved' && ! $template->is_active) {
            return response()->json([
                'error' => 'Only approved templates can be activated',
            ], 422);
        }

        $template->update(['is_active' => ! $template->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $template->is_active,
            'message' => $template->is_active
                ? 'Template activated'
                : 'Template deactivated',
        ]);
    }

    /**
     * Sync templates from Meta Business API.
     */
    public function sync()
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('messaging')) {
            return redirect()->route('admin.whatsapp.index')
                ->with('error', 'You do not have permission to sync templates.');
        }

        $count = $this->whatsAppService->syncTemplatesFromMeta();

        if ($count > 0) {
            return redirect()->route('admin.whatsapp.index')
                ->with('success', "Successfully synced {$count} templates from Meta.");
        }

        return redirect()->route('admin.whatsapp.index')
            ->with('warning', 'No templates were synced. Check your WhatsApp API configuration.');
    }

    /**
     * Mark template as approved (manual override).
     */
    public function approve(WhatsAppTemplate $template)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('messaging')) {
            return redirect()->route('admin.whatsapp.index')
                ->with('error', 'You do not have permission to approve templates.');
        }

        $template->markApproved();

        return redirect()->route('admin.whatsapp.show', $template)
            ->with('success', 'Template marked as approved.');
    }

    /**
     * Mark template as rejected (manual override).
     */
    public function reject(Request $request, WhatsAppTemplate $template)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('messaging')) {
            return redirect()->route('admin.whatsapp.index')
                ->with('error', 'You do not have permission to reject templates.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $template->markRejected($validated['reason']);

        return redirect()->route('admin.whatsapp.show', $template)
            ->with('success', 'Template marked as rejected.');
    }

    /**
     * Display messaging dashboard with SMS and WhatsApp stats.
     */
    public function dashboard(Request $request)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('messaging')) {
            return view('admin.unauthorized');
        }

        $days = $request->input('days', 30);

        // Overall message statistics
        $overallStats = SmsLog::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                channel,
                COUNT(*) as total,
                SUM(CASE WHEN status IN ("delivered", "read") THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(cost) as total_cost,
                SUM(segments) as total_segments
            ')
            ->groupBy('channel')
            ->get()
            ->keyBy('channel');

        // Message type breakdown
        $typeStats = SmsLog::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');

        // Daily message volume
        $dailyVolume = SmsLog::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, channel, COUNT(*) as count')
            ->groupBy('date', 'channel')
            ->orderBy('date')
            ->get();

        // Top templates by usage
        $topTemplates = SmsLog::whatsapp()
            ->whereNotNull('template_id')
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('template_id, COUNT(*) as usage_count')
            ->groupBy('template_id')
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get();

        // WhatsApp enabled
        $whatsappEnabled = config('whatsapp.enabled', false);

        return view('admin.whatsapp.dashboard', compact(
            'overallStats',
            'typeStats',
            'dailyVolume',
            'topTemplates',
            'whatsappEnabled',
            'days'
        ));
    }
}
