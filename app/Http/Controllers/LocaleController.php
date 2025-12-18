<?php

namespace App\Http\Controllers;

use App\Models\Locale;
use App\Services\LocalizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * GLO-006: Localization Engine - Controller for locale management
 */
class LocaleController extends Controller
{
    /**
     * The localization service instance.
     */
    protected LocalizationService $localization;

    /**
     * Create a new controller instance.
     */
    public function __construct(LocalizationService $localization)
    {
        $this->localization = $localization;
    }

    /**
     * Change the application locale.
     */
    public function change(Request $request): RedirectResponse
    {
        $request->validate([
            'locale' => ['required', 'string', 'max:10'],
        ]);

        $locale = $request->input('locale');

        // Validate locale exists and is active
        if (! $this->localization->isValidLocale($locale)) {
            return redirect()->back()->with('error', __('general.invalid_locale'));
        }

        // Set the locale
        $this->localization->setLocale($locale);

        // Update user preference if authenticated
        if (auth()->check()) {
            $user = auth()->user();
            $user->update([
                'locale' => $locale,
                'language' => $locale, // For backward compatibility
            ]);
        }

        // Redirect back or to referrer
        $redirect = $request->input('redirect', $request->header('referer', route('home')));

        return redirect($redirect)->with('success', __('general.locale_changed'));
    }

    /**
     * Change locale via AJAX.
     */
    public function changeAjax(Request $request): JsonResponse
    {
        $request->validate([
            'locale' => ['required', 'string', 'max:10'],
        ]);

        $locale = $request->input('locale');

        // Validate locale exists and is active
        if (! $this->localization->isValidLocale($locale)) {
            return response()->json([
                'success' => false,
                'message' => __('general.invalid_locale'),
            ], 400);
        }

        // Set the locale
        $this->localization->setLocale($locale);

        // Update user preference if authenticated
        if (auth()->check()) {
            $user = auth()->user();
            $user->update([
                'locale' => $locale,
                'language' => $locale,
            ]);
        }

        // Get locale info for response
        $localeInstance = Locale::findByCode($locale);

        return response()->json([
            'success' => true,
            'message' => __('general.locale_changed'),
            'locale' => [
                'code' => $locale,
                'name' => $localeInstance?->name,
                'native_name' => $localeInstance?->native_name,
                'is_rtl' => $localeInstance?->is_rtl ?? false,
                'direction' => $this->localization->getDirection($locale),
            ],
        ]);
    }

    /**
     * Get all available locales.
     */
    public function index(): JsonResponse
    {
        $locales = Locale::getActive()->map(function ($locale) {
            return [
                'code' => $locale->code,
                'name' => $locale->name,
                'native_name' => $locale->native_name,
                'flag_emoji' => $locale->flag_emoji,
                'is_rtl' => $locale->is_rtl,
                'translation_progress' => $locale->translation_progress,
            ];
        });

        return response()->json([
            'success' => true,
            'current' => $this->localization->getLocale(),
            'locales' => $locales,
        ]);
    }

    /**
     * Get current locale info.
     */
    public function current(): JsonResponse
    {
        $locale = $this->localization->getLocale();
        $localeInstance = $this->localization->getCurrentLocale();

        return response()->json([
            'success' => true,
            'locale' => [
                'code' => $locale,
                'name' => $localeInstance?->name,
                'native_name' => $localeInstance?->native_name,
                'flag_emoji' => $localeInstance?->flag_emoji,
                'is_rtl' => $localeInstance?->is_rtl ?? false,
                'direction' => $this->localization->getDirection($locale),
                'date_format' => $localeInstance?->date_format,
                'time_format' => $localeInstance?->time_format,
                'number_decimal_separator' => $localeInstance?->number_decimal_separator,
                'number_thousands_separator' => $localeInstance?->number_thousands_separator,
                'currency_position' => $localeInstance?->currency_position,
            ],
        ]);
    }

    /**
     * Get translations for a specific group (for JavaScript).
     */
    public function translations(Request $request): JsonResponse
    {
        $request->validate([
            'groups' => ['sometimes', 'array'],
            'groups.*' => ['string'],
        ]);

        $locale = $this->localization->getLocale();
        $groups = $request->input('groups', ['general', 'validation', 'auth']);

        $translations = [];

        foreach ($groups as $group) {
            // Try to load from Laravel lang files
            $langPath = resource_path("lang/{$locale}/{$group}.php");
            if (file_exists($langPath)) {
                $translations[$group] = include $langPath;
            } else {
                // Fall back to default locale
                $defaultLangPath = resource_path("lang/en/{$group}.php");
                if (file_exists($defaultLangPath)) {
                    $translations[$group] = include $defaultLangPath;
                }
            }
        }

        return response()->json([
            'success' => true,
            'locale' => $locale,
            'translations' => $translations,
        ]);
    }

    /**
     * Format a date using current locale settings (API endpoint).
     */
    public function formatDate(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date'],
            'format' => ['sometimes', 'string', 'in:date,time,datetime,relative'],
        ]);

        $date = \Carbon\Carbon::parse($request->input('date'));
        $format = $request->input('format', 'datetime');

        $formatted = match ($format) {
            'date' => $this->localization->formatDate($date),
            'time' => $this->localization->formatTime($date),
            'relative' => $this->localization->formatRelativeTime($date),
            default => $this->localization->formatDateTime($date),
        };

        return response()->json([
            'success' => true,
            'formatted' => $formatted,
        ]);
    }

    /**
     * Format a currency amount using current locale settings (API endpoint).
     */
    public function formatCurrency(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric'],
            'currency' => ['required', 'string', 'size:3'],
        ]);

        $formatted = $this->localization->formatCurrency(
            (float) $request->input('amount'),
            $request->input('currency')
        );

        return response()->json([
            'success' => true,
            'formatted' => $formatted,
            'symbol' => $this->localization->getCurrencySymbol($request->input('currency')),
        ]);
    }
}
