<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSettingAudit;
use App\Models\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * ADM-003: Platform Configuration Management Controller
 *
 * Handles all admin operations for managing platform-wide configuration settings.
 */
class ConfigurationController extends Controller
{
    /**
     * Display the configuration dashboard with all settings grouped by category.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Check permission (skip for dev accounts)
        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return view('admin.unauthorized');
        }

        // Get filter parameters
        $category = $request->get('category');
        $search = $request->get('search');

        // Build query
        $query = SystemSettings::query()->orderBy('category')->orderBy('key');

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        if ($search) {
            $query->search($search);
        }

        $settings = $query->get();

        // Group by category
        $groupedSettings = $settings->groupBy('category');

        // Get categories for tabs
        $categories = SystemSettings::getCategories();

        // Get recent audit entries
        $recentChanges = SystemSettingAudit::with('changedBy')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get audit statistics
        $auditStats = SystemSettingAudit::getStatistics();

        return view('admin.configuration.index', [
            'groupedSettings' => $groupedSettings,
            'categories' => $categories,
            'currentCategory' => $category ?? 'all',
            'search' => $search,
            'recentChanges' => $recentChanges,
            'auditStats' => $auditStats,
            'dataTypes' => SystemSettings::getDataTypes(),
        ]);
    }

    /**
     * Update settings (batch update).
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // Check permission (skip for dev accounts)
        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return redirect()->back()->with('error', 'Access denied. You do not have permission to manage settings.');
        }

        // Get all settings for validation
        $existingSettings = SystemSettings::all()->keyBy('key');
        $settingsToUpdate = $request->input('settings', []);

        // Validate each setting based on its data type
        $errors = [];
        $validatedSettings = [];

        foreach ($settingsToUpdate as $key => $value) {
            if (! $existingSettings->has($key)) {
                continue;
            }

            $setting = $existingSettings->get($key);

            // Validate based on data type
            $validationResult = $this->validateSettingValue($key, $value, $setting);

            if ($validationResult !== true) {
                $errors[$key] = $validationResult;
            } else {
                $validatedSettings[$key] = $value;
            }
        }

        if (! empty($errors)) {
            return redirect()->back()
                ->withErrors($errors)
                ->withInput();
        }

        // Perform batch update
        try {
            $updated = SystemSettings::batchUpdate($validatedSettings, $user->id);

            Log::channel('admin')->info('System settings updated', [
                'admin_id' => $user->id,
                'admin_email' => $user->email,
                'settings_updated' => array_keys($validatedSettings),
                'ip' => $request->ip(),
            ]);

            return redirect()->route('admin.configuration.index')
                ->with('success', count($updated).' setting(s) updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update system settings', [
                'error' => $e->getMessage(),
                'admin_id' => $user->id,
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update settings: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update a single setting via AJAX.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSingle(Request $request, string $key)
    {
        $user = Auth::user();

        // Check permission (skip for dev accounts)
        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $setting = SystemSettings::where('key', $key)->first();

        if (! $setting) {
            return response()->json(['error' => 'Setting not found'], 404);
        }

        $value = $request->input('value');

        // Validate the value
        $validationResult = $this->validateSettingValue($key, $value, $setting);

        if ($validationResult !== true) {
            return response()->json(['error' => $validationResult], 422);
        }

        try {
            SystemSettings::set($key, $value, $user->id);

            Log::channel('admin')->info('System setting updated', [
                'admin_id' => $user->id,
                'key' => $key,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Setting updated successfully',
                'setting' => SystemSettings::where('key', $key)->first(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display audit history for all settings or a specific setting.
     *
     * @return \Illuminate\View\View
     */
    public function history(Request $request)
    {
        $user = Auth::user();

        // Check permission (skip for dev accounts)
        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return view('admin.unauthorized');
        }

        // Build filters
        $filters = [
            'key' => $request->get('key'),
            'user_id' => $request->get('user_id'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
        ];

        // Get audit log with pagination
        $audits = SystemSettingAudit::getAuditLog($filters, 25);

        // Get all settings keys for filter dropdown
        $settingsKeys = SystemSettings::orderBy('key')->pluck('key');

        // Get admin users for filter dropdown
        $adminUsers = \App\Models\User::where('role', 'admin')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Get statistics
        $stats = SystemSettingAudit::getStatistics();

        return view('admin.configuration.history', [
            'audits' => $audits,
            'filters' => $filters,
            'settingsKeys' => $settingsKeys,
            'adminUsers' => $adminUsers,
            'stats' => $stats,
        ]);
    }

    /**
     * Get history for a specific setting via AJAX.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function settingHistory(string $key)
    {
        $user = Auth::user();

        // Check permission (skip for dev accounts)
        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $history = SystemSettingAudit::getHistoryForKey($key, 20);

        return response()->json([
            'success' => true,
            'history' => $history->map(function ($audit) {
                return [
                    'id' => $audit->id,
                    'old_value' => $audit->old_value,
                    'new_value' => $audit->new_value,
                    'changed_by' => $audit->changedBy ? $audit->changedBy->name : 'System',
                    'created_at' => $audit->created_at->format('M j, Y g:i A'),
                    'relative_time' => $audit->created_at->diffForHumans(),
                    'ip_address' => $audit->ip_address,
                ];
            }),
        ]);
    }

    /**
     * Reset a setting to its default value.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function reset(Request $request, string $key)
    {
        $user = Auth::user();

        // Check permission (skip for dev accounts)
        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Access denied'], 403);
            }

            return redirect()->back()->with('error', 'Access denied.');
        }

        $result = SystemSettings::resetToDefault($key, $user->id);

        if (! $result) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Setting not found or has no default'], 404);
            }

            return redirect()->back()->with('error', 'Setting not found or has no default value.');
        }

        Log::channel('admin')->info('System setting reset to default', [
            'admin_id' => $user->id,
            'key' => $key,
            'ip' => $request->ip(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Setting reset to default successfully',
                'setting' => SystemSettings::where('key', $key)->first(),
            ]);
        }

        return redirect()->back()->with('success', "Setting '{$key}' reset to default value.");
    }

    /**
     * Reset all settings to defaults.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetAll(Request $request)
    {
        $user = Auth::user();

        // Check permission (skip for dev accounts)
        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        // Require confirmation
        if (! $request->has('confirm') || $request->input('confirm') !== 'RESET') {
            return redirect()->back()
                ->with('error', 'Please type RESET to confirm resetting all settings.');
        }

        $count = SystemSettings::resetAllToDefaults($user->id);

        Log::channel('admin')->warning('All system settings reset to defaults', [
            'admin_id' => $user->id,
            'admin_email' => $user->email,
            'settings_reset' => $count,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('admin.configuration.index')
            ->with('success', "{$count} setting(s) reset to default values.");
    }

    /**
     * Export settings to JSON.
     *
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        // Check permission (skip for dev accounts)
        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        $settings = SystemSettings::all()->map(function ($setting) {
            return [
                'key' => $setting->key,
                'value' => $setting->value,
                'category' => $setting->category,
                'data_type' => $setting->data_type,
                'is_public' => $setting->is_public,
            ];
        });

        $filename = 'system_settings_'.now()->format('Y-m-d_His').'.json';

        Log::channel('admin')->info('System settings exported', [
            'admin_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return response()->json($settings)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Import settings from JSON.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function import(Request $request)
    {
        $user = Auth::user();

        // Check permission (skip for dev accounts)
        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        $request->validate([
            'import_file' => 'required|file|mimes:json|max:1024',
        ]);

        try {
            $content = file_get_contents($request->file('import_file')->path());
            $settings = json_decode($content, true);

            if (! is_array($settings)) {
                throw new \InvalidArgumentException('Invalid JSON format');
            }

            $imported = 0;
            foreach ($settings as $data) {
                if (! isset($data['key'], $data['value'])) {
                    continue;
                }

                $existing = SystemSettings::where('key', $data['key'])->first();
                if ($existing) {
                    SystemSettings::set($data['key'], $data['value'], $user->id);
                    $imported++;
                }
            }

            Log::channel('admin')->info('System settings imported', [
                'admin_id' => $user->id,
                'settings_imported' => $imported,
                'ip' => $request->ip(),
            ]);

            return redirect()->route('admin.configuration.index')
                ->with('success', "{$imported} setting(s) imported successfully.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to import settings: '.$e->getMessage());
        }
    }

    /**
     * Clear the settings cache.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clearCache(Request $request)
    {
        $user = Auth::user();

        // Check permission (skip for dev accounts)
        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            return redirect()->back()->with('error', 'Access denied.');
        }

        SystemSettings::clearAllCache();

        Log::channel('admin')->info('System settings cache cleared', [
            'admin_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return redirect()->back()->with('success', 'Settings cache cleared successfully.');
    }

    /**
     * Validate a setting value based on its data type.
     *
     * @param  mixed  $value
     * @return bool|string True if valid, error message if invalid
     */
    protected function validateSettingValue(string $key, $value, SystemSettings $setting)
    {
        switch ($setting->data_type) {
            case SystemSettings::DATA_TYPE_INTEGER:
                if (! is_numeric($value) || (int) $value != $value) {
                    return "The {$key} must be an integer.";
                }
                break;

            case SystemSettings::DATA_TYPE_DECIMAL:
                if (! is_numeric($value)) {
                    return "The {$key} must be a number.";
                }
                break;

            case SystemSettings::DATA_TYPE_BOOLEAN:
                // Accept 1, 0, true, false, "true", "false", "1", "0"
                if (! in_array($value, [1, 0, true, false, '1', '0', 'true', 'false'], true)) {
                    return "The {$key} must be a boolean value.";
                }
                break;

            case SystemSettings::DATA_TYPE_JSON:
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return "The {$key} must be valid JSON.";
                    }
                } elseif (! is_array($value)) {
                    return "The {$key} must be valid JSON.";
                }
                break;

            case SystemSettings::DATA_TYPE_STRING:
            default:
                // Strings are always valid
                break;
        }

        return true;
    }

    /**
     * Get all settings grouped by category.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function allGrouped(Request $request)
    {
        $user = Auth::user();

        // Check permission (skip for dev accounts)
        if (! $user->is_dev_account && ! $user->hasPermission('view_settings')) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Access denied.'], 403);
            }

            return redirect()->back()->with('error', 'Access denied.');
        }

        $settings = SystemSettings::allGrouped();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $settings,
            ]);
        }

        return view('admin.configuration.grouped', compact('settings'));
    }

    /**
     * Batch update multiple settings at once.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function batchUpdate(Request $request)
    {
        $user = Auth::user();

        // Check permission (skip for dev accounts)
        if (! $user->is_dev_account && ! $user->hasPermission('manage_settings')) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Access denied.'], 403);
            }

            return redirect()->back()->with('error', 'Access denied.');
        }

        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'present',
        ]);

        $settings = $request->input('settings');
        $errors = [];
        $updated = [];

        foreach ($settings as $settingData) {
            $key = $settingData['key'];
            $value = $settingData['value'];

            $setting = SystemSettings::where('key', $key)->first();

            if (! $setting) {
                $errors[] = "Setting '{$key}' not found.";

                continue;
            }

            // Validate the value
            $validation = $this->validateSettingValue($key, $value, $setting);
            if ($validation !== true) {
                $errors[] = $validation;

                continue;
            }

            // Update using the static method
            SystemSettings::set($key, $value, $user->id);
            $updated[] = $key;
        }

        Log::channel('admin')->info('Batch settings update', [
            'admin_id' => $user->id,
            'updated' => $updated,
            'errors' => $errors,
            'ip' => $request->ip(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => count($errors) === 0,
                'updated' => $updated,
                'errors' => $errors,
            ]);
        }

        if (count($errors) > 0) {
            return redirect()->back()
                ->with('warning', 'Some settings were not updated: '.implode(', ', $errors))
                ->with('success', count($updated).' settings updated successfully.');
        }

        return redirect()->back()->with('success', count($updated).' settings updated successfully.');
    }
}
