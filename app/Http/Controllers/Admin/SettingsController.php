<?php

namespace App\Http\Controllers\Admin;

use App\Helper;
use App\Http\Controllers\Controller;
use App\Models\AdminSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    protected $settings;

    public function __construct()
    {
        try {
            $this->settings = \App\Models\AdminSettings::first();
        } catch (\Exception $e) {
            $this->settings = null;
        }
    }

    /**
     * Show general settings
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $genders = $this->settings?->genders ? explode(',', $this->settings->genders) : [];

        return view('admin.settings', ['genders' => $genders]);
    }

    /**
     * Save general settings
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(Request $request)
    {
        // The referral system cannot be activated if your commission fee equals 0
        if ($this->settings?->fee_commission == 0 && $request->referral_system == 'on') {
            return back()->withErrors([
                'errors' => trans('general.error_active_system_referrals'),
            ]);
        }

        $messages = [
            'genders.required' => trans('general.genders_required'),
        ];

        $request->validate([
            'title' => 'required',
            'email_admin' => 'required',
            'link_terms' => 'required|url',
            'link_privacy' => 'required|url',
            'link_cookies' => 'required|url',
            'genders' => 'required',
        ], $messages);

        if (isset($request->genders)) {
            $genders = implode(',', $request->genders);
        }

        $sql = AdminSettings::first();
        $sql->title = $request->title;
        $sql->email_admin = $request->email_admin;
        $sql->link_terms = $request->link_terms;
        $sql->link_privacy = $request->link_privacy;
        $sql->link_cookies = $request->link_cookies;
        $sql->date_format = $request->date_format;
        $sql->captcha = $request->captcha;
        $sql->email_verification = $request->email_verification;
        $sql->registration_active = $request->registration_active;
        $sql->account_verification = $request->account_verification;
        $sql->show_counter = $request->show_counter;
        $sql->widget_creators_featured = $request->widget_creators_featured;
        $sql->requests_verify_account = $request->requests_verify_account;
        $sql->hide_admin_profile = $request->hide_admin_profile;
        $sql->earnings_simulator = $request->earnings_simulator;
        $sql->watermark = $request->watermark;
        $sql->alert_adult = $request->alert_adult;
        $sql->genders = $genders;
        $sql->who_can_see_content = $request->who_can_see_content;
        $sql->users_can_edit_post = $request->users_can_edit_post;
        $sql->disable_banner_cookies = $request->disable_banner_cookies;
        $sql->captcha_contact = $request->captcha_contact;
        $sql->disable_tips = $request->disable_tips;
        $sql->watermark_on_videos = $request->watermark_on_videos;
        $sql->referral_system = $request->referral_system;
        $sql->video_encoding = $request->video_encoding;
        $sql->save();

        // Default locale
        Helper::envUpdate('DEFAULT_LOCALE', $request->default_language);

        // App Name
        Helper::envUpdate('APP_NAME', ' "'.$request->title.'" ', true);

        // APP Debug
        $path = base_path('.env');

        if (config('app.debug') == true) {
            $APP_DEBUG = 'APP_DEBUG=true';
        } else {
            $APP_DEBUG = 'APP_DEBUG=false';
        }

        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                $APP_DEBUG, 'APP_DEBUG='.$request->app_debug, file_get_contents($path)
            ));
        }

        \Session::flash('success', trans('admin.success_update'));

        return redirect('panel/admin/settings');
    }

    /**
     * Show limits settings
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function limits()
    {
        return view('admin.limits')->withSettings($this->settings);
    }

    /**
     * Save limits settings
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveLimits(Request $request)
    {
        $sql = AdminSettings::first();
        $sql->auto_approve_post = $request->auto_approve_post;
        $sql->file_size_allowed = $request->file_size_allowed;
        $sql->file_size_allowed_verify_account = $request->file_size_allowed_verify_account;
        $sql->update_length = $request->update_length;
        $sql->story_length = $request->story_length;
        $sql->comment_length = $request->comment_length;
        $sql->number_posts_show = $request->number_posts_show;
        $sql->number_comments_show = $request->number_comments_show;
        $sql->maximum_files_post = $request->maximum_files_post;
        $sql->maximum_files_msg = $request->maximum_files_msg;
        $sql->limit_categories = $request->limit_categories;
        $sql->min_width_height_image = $request->min_width_height_image;
        $sql->save();

        \Session::flash('success', trans('admin.success_update'));

        return redirect('panel/admin/settings/limits');
    }

    /**
     * Save market settings.
     *
     * Note: These settings are stored in config/market.php and can be overridden
     * via environment variables. For runtime updates, consider using a database-backed
     * settings store or SystemSettings model.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveMarket(Request $request)
    {
        $validated = $request->validate([
            'demo_enabled' => 'nullable|in:on,off',
            'demo_disable_threshold' => 'nullable|integer|min:1|max:100',
            'demo_shift_count' => 'nullable|integer|min:1|max:50',
            'stats_cache_ttl' => 'nullable|integer|min:60|max:3600',
            'max_pending_applications' => 'nullable|integer|min:1|max:20',
            'instant_claim_min_rating' => 'nullable|numeric|min:3.0|max:5.0',
            'min_hourly_rate' => 'nullable|numeric|min:10|max:100',
            'max_surge_multiplier' => 'nullable|numeric|min:1.0|max:5.0',
        ]);

        // Store settings in SystemSettings table for runtime persistence
        $settingsToSave = [
            'market.demo_enabled' => $request->demo_enabled === 'on',
            'market.demo_disable_threshold' => $validated['demo_disable_threshold'] ?? 15,
            'market.demo_shift_count' => $validated['demo_shift_count'] ?? 20,
            'market.stats_cache_ttl' => $validated['stats_cache_ttl'] ?? 300,
            'market.max_pending_applications' => $validated['max_pending_applications'] ?? 5,
            'market.instant_claim_min_rating' => $validated['instant_claim_min_rating'] ?? 4.5,
            'market.min_hourly_rate' => $validated['min_hourly_rate'] ?? 15,
            'market.max_surge_multiplier' => $validated['max_surge_multiplier'] ?? 2.5,
        ];

        foreach ($settingsToSave as $key => $value) {
            \App\Models\SystemSettings::set($key, $value);
        }

        // Clear config cache
        \Artisan::call('config:clear');

        \Session::flash('success', 'Market settings updated successfully.');

        return redirect()->route('admin.settings.market');
    }

    /**
     * Toggle maintenance mode
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function maintenanceMode(Request $request)
    {
        $strRandom = Str::random(50);

        if (auth()->user()->isSuperAdmin() && $request->maintenance_mode == 'on') {
            \Artisan::call('down', [
                '--secret' => $strRandom,
            ]);
        } elseif (auth()->user()->isSuperAdmin() && $request->maintenance_mode == 'off') {
            \Artisan::call('up');
        }

        if ($this->settings) {
            $this->settings->maintenance_mode = $request->maintenance_mode;
            $this->settings->save();
        }

        if ($request->maintenance_mode == 'on') {
            return redirect($strRandom)
                ->withSuccessMessage(trans('admin.maintenance_mode_on'));
        } else {
            return redirect('panel/admin/maintenance/mode')
                ->withSuccessMessage(trans('admin.maintenance_mode_off'));
        }
    }

    /**
     * Show social profiles settings
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function profilesSocial()
    {
        return view('admin.profiles-social')->withSettings($this->settings);
    }

    /**
     * Update social profiles settings
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfilesSocial(Request $request)
    {
        $sql = AdminSettings::find(1);

        $rules = [
            'twitter' => 'url',
            'facebook' => 'url',
            'googleplus' => 'url',
            'youtube' => 'url',
        ];

        $this->validate($request, $rules);

        $sql->twitter = $request->twitter;
        $sql->facebook = $request->facebook;
        $sql->pinterest = $request->pinterest;
        $sql->instagram = $request->instagram;
        $sql->youtube = $request->youtube;
        $sql->github = $request->github;
        $sql->tiktok = $request->tiktok;
        $sql->snapchat = $request->snapchat;

        $sql->save();

        \Session::flash('success', trans('admin.success_update'));

        return redirect('panel/admin/profiles-social');
    }

    /**
     * Show Google settings
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function google()
    {
        return view('admin.google');
    }

    /**
     * Update Google settings
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateGoogle(Request $request)
    {
        $sql = $this->settings;
        $sql->google_analytics = $request->google_analytics;
        $sql->save();

        // SECURITY: Use whitelist-based environment update service
        $envService = app(\App\Services\EnvironmentUpdateService::class);
        $result = $envService->updateFromRequest($request);

        if (! empty($result['errors'])) {
            return back()->withErrors([
                'errors' => 'Some settings could not be updated: '.implode(', ', array_column($result['errors'], 'key')),
            ]);
        }

        \Session::flash('success', trans('admin.success_update'));

        return redirect('panel/admin/google');
    }

    /**
     * Save email settings
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function emailSettings(Request $request)
    {
        $request->validate([
            'MAIL_FROM_ADDRESS' => 'required',
        ]);

        $request->MAIL_ENCRYPTION = strtolower($request->MAIL_ENCRYPTION);

        if ($this->settings) {
            $this->settings->email_no_reply = $request->MAIL_FROM_ADDRESS;
            $this->settings->save();
        }

        // SECURITY: Use whitelist-based environment update service
        $envService = app(\App\Services\EnvironmentUpdateService::class);
        $result = $envService->updateFromRequest($request);

        if (! empty($result['errors'])) {
            return back()->withErrors([
                'errors' => 'Some settings could not be updated: '.implode(', ', array_column($result['errors'], 'key')),
            ]);
        }

        \Session::flash('success', trans('admin.success_update'));

        return back();
    }

    /**
     * Update social login settings
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSocialLogin(Request $request)
    {
        if ($this->settings) {
            $this->settings->facebook_login = $request->facebook_login;
            $this->settings->google_login = $request->google_login;
            $this->settings->twitter_login = $request->twitter_login;
            $this->settings->save();
        }

        // SECURITY: Use whitelist-based environment update service
        $envService = app(\App\Services\EnvironmentUpdateService::class);
        $result = $envService->updateFromRequest($request);

        if (! empty($result['errors'])) {
            return back()->withErrors([
                'errors' => 'Some settings could not be updated: '.implode(', ', array_column($result['errors'], 'key')),
            ]);
        }

        \Session::flash('success', trans('admin.success_update'));

        return back();
    }

    /**
     * Save storage settings
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storage(Request $request)
    {
        $messages = [
            'APP_URL.required' => trans('validation.required', ['attribute' => 'App URL']),
            'APP_URL.url' => trans('validation.url', ['attribute' => 'App URL']),
        ];

        $request->validate([
            'APP_URL' => 'required|url',
            'AWS_ACCESS_KEY_ID' => 'required_if:FILESYSTEM_DRIVER,s3',
            'AWS_SECRET_ACCESS_KEY' => 'required_if:FILESYSTEM_DRIVER,s3',
            'AWS_DEFAULT_REGION' => 'required_if:FILESYSTEM_DRIVER,s3',
            'AWS_BUCKET' => 'required_if:FILESYSTEM_DRIVER,s3',

            'DOS_ACCESS_KEY_ID' => 'required_if:FILESYSTEM_DRIVER,dospace',
            'DOS_SECRET_ACCESS_KEY' => 'required_if:FILESYSTEM_DRIVER,dospace',
            'DOS_DEFAULT_REGION' => 'required_if:FILESYSTEM_DRIVER,dospace',
            'DOS_BUCKET' => 'required_if:FILESYSTEM_DRIVER,dospace',

            'WAS_ACCESS_KEY_ID' => 'required_if:FILESYSTEM_DRIVER,wasabi',
            'WAS_SECRET_ACCESS_KEY' => 'required_if:FILESYSTEM_DRIVER,wasabi',
            'WAS_DEFAULT_REGION' => 'required_if:FILESYSTEM_DRIVER,wasabi',
            'WAS_BUCKET' => 'required_if:FILESYSTEM_DRIVER,wasabi',

            'BACKBLAZE_ACCOUNT_ID' => 'required_if:FILESYSTEM_DRIVER,backblaze',
            'BACKBLAZE_APP_KEY' => 'required_if:FILESYSTEM_DRIVER,backblaze',
            'BACKBLAZE_BUCKET' => 'required_if:FILESYSTEM_DRIVER,backblaze',
            'BACKBLAZE_BUCKET_ID' => 'required_if:FILESYSTEM_DRIVER,backblaze',
            'BACKBLAZE_BUCKET_REGION' => 'required_if:FILESYSTEM_DRIVER,backblaze',

            'VULTR_ACCESS_KEY' => 'required_if:FILESYSTEM_DRIVER,vultr',
            'VULTR_SECRET_KEY' => 'required_if:FILESYSTEM_DRIVER,vultr',
            'VULTR_REGION' => 'required_if:FILESYSTEM_DRIVER,vultr',
            'VULTR_BUCKET' => 'required_if:FILESYSTEM_DRIVER,vultr',
        ], $messages);

        // SECURITY: Use whitelist-based environment update service
        $envService = app(\App\Services\EnvironmentUpdateService::class);

        // Handle APP_URL trimming
        if ($request->has('APP_URL')) {
            $request->merge(['APP_URL' => trim($request->APP_URL, '/')]);
        }

        // Handle DOS_CDN checkbox
        if (! $request->has('DOS_CDN') || ! $request->DOS_CDN) {
            $request->merge(['DOS_CDN' => null]);
        }

        $result = $envService->updateFromRequest($request);

        // Log any rejected keys
        if (! empty($result['rejected'])) {
            Log::channel('admin')->warning('Storage settings: Some keys were rejected', [
                'admin_id' => auth()->id(),
                'rejected_keys' => $result['rejected'],
                'ip' => $request->ip(),
            ]);
        }

        // Show errors if any
        if (! empty($result['errors'])) {
            return back()->withErrors([
                'errors' => 'Some settings could not be updated: '.implode(', ', array_column($result['errors'], 'key')),
            ]);
        }

        \Session::flash('success', trans('admin.success_update'));

        return back();
    }

    /**
     * Store billing settings
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function billingStore(Request $request)
    {
        if ($this->settings) {
            $this->settings->company = $request->company;
            $this->settings->country = $request->country;
            $this->settings->address = $request->address;
            $this->settings->city = $request->city;
            $this->settings->zip = $request->zip;
            $this->settings->vat = $request->vat;
            $this->settings->save();
        }

        \Session::flash('success', trans('admin.success_update'));

        return back();
    }

    /**
     * Save custom CSS/JS
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function customCssJs(Request $request)
    {
        if ($this->settings) {
            $this->settings->custom_css = $request->custom_css;
            $this->settings->custom_js = $request->custom_js;
            $this->settings->save();
        }

        return back()->withSuccessMessage(trans('admin.success_update'));
    }

    /**
     * Store announcements
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeAnnouncements(Request $request)
    {
        if ($this->settings) {
            $this->settings->announcement = $request->announcement_content;
            $this->settings->announcement_show = $request->announcement_show;
            $this->settings->type_announcement = $request->type_announcement;
            $this->settings->announcement_cookie = Str::random(20);
            $this->settings->save();
        }

        return back()->withSuccessMessage(trans('admin.success_update'));
    }

    /**
     * Save live streaming settings
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveLiveStreaming(Request $request)
    {
        if ($this->settings) {
            $this->settings->live_streaming_status = $request->live_streaming_status;
            $this->settings->agora_app_id = $request->agora_app_id;
            $this->settings->live_streaming_minimum_price = $request->live_streaming_minimum_price;
            $this->settings->live_streaming_max_price = $request->live_streaming_max_price;
            $this->settings->live_streaming_free = $request->live_streaming_free;
            $this->settings->limit_live_streaming_paid = $request->limit_live_streaming_paid;
            $this->settings->limit_live_streaming_free = $request->limit_live_streaming_free;
            $this->settings->save();
        }

        return back()->withSuccessMessage(trans('admin.success_update'));
    }
}
