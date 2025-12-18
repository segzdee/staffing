<?php

namespace App\Services;

use App\Models\Locale;
use App\Models\Translation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

/**
 * GLO-006: Localization Engine - Core Service
 * Handles all i18n/l10n operations including locale management,
 * date/time/number/currency formatting, and translations.
 */
class LocalizationService
{
    /**
     * The current locale instance.
     */
    protected ?Locale $currentLocale = null;

    /**
     * Cache for locale instances.
     *
     * @var array<string, Locale|null>
     */
    protected array $localeCache = [];

    /**
     * Set the application locale.
     */
    public function setLocale(string $locale): void
    {
        // Validate locale exists and is active
        if (! $this->isValidLocale($locale)) {
            $locale = $this->getDefaultLocale();
        }

        // Set Laravel app locale
        App::setLocale($locale);

        // Store in session
        Session::put('locale', $locale);

        // Cache the current locale instance
        $this->currentLocale = $this->getLocaleInstance($locale);

        // Set Carbon locale for date formatting
        Carbon::setLocale($locale);

        // Set PHP locale for number/currency formatting
        $this->setPhpLocale($locale);
    }

    /**
     * Get the current locale code.
     */
    public function getLocale(): string
    {
        return App::getLocale();
    }

    /**
     * Get the current Locale model instance.
     */
    public function getCurrentLocale(): ?Locale
    {
        if ($this->currentLocale === null) {
            $this->currentLocale = $this->getLocaleInstance($this->getLocale());
        }

        return $this->currentLocale;
    }

    /**
     * Get a Locale instance by code.
     */
    public function getLocaleInstance(string $code): ?Locale
    {
        if (! isset($this->localeCache[$code])) {
            $this->localeCache[$code] = Locale::findByCode($code);
        }

        return $this->localeCache[$code];
    }

    /**
     * Get all supported locales.
     */
    public function getSupportedLocales(): Collection
    {
        return Locale::getActive();
    }

    /**
     * Get supported locale codes.
     *
     * @return array<int, string>
     */
    public function getSupportedLocaleCodes(): array
    {
        return $this->getSupportedLocales()->pluck('code')->toArray();
    }

    /**
     * Format a date according to the locale settings.
     */
    public function formatDate(Carbon $date, ?string $locale = null): string
    {
        $localeInstance = $locale
            ? $this->getLocaleInstance($locale)
            : $this->getCurrentLocale();

        if (! $localeInstance) {
            return $date->format('Y-m-d');
        }

        return $date->format($localeInstance->date_format);
    }

    /**
     * Format a time according to the locale settings.
     */
    public function formatTime(Carbon $time, ?string $locale = null): string
    {
        $localeInstance = $locale
            ? $this->getLocaleInstance($locale)
            : $this->getCurrentLocale();

        if (! $localeInstance) {
            return $time->format('H:i');
        }

        return $time->format($localeInstance->time_format);
    }

    /**
     * Format a datetime according to the locale settings.
     */
    public function formatDateTime(Carbon $datetime, ?string $locale = null): string
    {
        $localeInstance = $locale
            ? $this->getLocaleInstance($locale)
            : $this->getCurrentLocale();

        if (! $localeInstance) {
            return $datetime->format('Y-m-d H:i');
        }

        return $datetime->format($localeInstance->datetime_format);
    }

    /**
     * Format a number according to the locale settings.
     */
    public function formatNumber(float $number, ?string $locale = null, int $decimals = 2): string
    {
        $localeInstance = $locale
            ? $this->getLocaleInstance($locale)
            : $this->getCurrentLocale();

        if (! $localeInstance) {
            return number_format($number, $decimals, '.', ',');
        }

        return number_format(
            $number,
            $decimals,
            $localeInstance->number_decimal_separator,
            $localeInstance->number_thousands_separator
        );
    }

    /**
     * Format a currency amount according to locale and currency settings.
     */
    public function formatCurrency(float $amount, string $currency, ?string $locale = null): string
    {
        $localeInstance = $locale
            ? $this->getLocaleInstance($locale)
            : $this->getCurrentLocale();

        // Get currency symbol
        $symbol = $this->getCurrencySymbol($currency);
        $formattedAmount = $this->formatNumber($amount, $locale);

        // Determine position
        $position = $localeInstance?->currency_position ?? 'before';

        if ($position === 'after') {
            return "{$formattedAmount} {$symbol}";
        }

        return "{$symbol}{$formattedAmount}";
    }

    /**
     * Get the currency symbol for a currency code.
     */
    public function getCurrencySymbol(string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => "\u{20AC}",
            'GBP' => "\u{00A3}",
            'JPY' => "\u{00A5}",
            'CNY' => "\u{00A5}",
            'INR' => "\u{20B9}",
            'BRL' => 'R$',
            'AUD' => 'A$',
            'CAD' => 'C$',
            'CHF' => 'CHF',
            'MXN' => 'MX$',
            'AED' => 'AED',
            'SAR' => 'SAR',
            'NGN' => "\u{20A6}",
            'ZAR' => 'R',
            'KES' => 'KSh',
            'GHS' => 'GH\u{20B5}',
            'EGP' => 'E\u{00A3}',
            'MAD' => 'MAD',
            'KRW' => "\u{20A9}",
            'SGD' => 'S$',
            'HKD' => 'HK$',
            'SEK' => 'kr',
            'NOK' => 'kr',
            'DKK' => 'kr',
            'PLN' => "z\u{0142}",
            'CZK' => "K\u{010D}",
            'HUF' => 'Ft',
            'RUB' => "\u{20BD}",
            'TRY' => "\u{20BA}",
            'THB' => "\u{0E3F}",
            'IDR' => 'Rp',
            'MYR' => 'RM',
            'PHP' => "\u{20B1}",
            'VND' => "\u{20AB}",
            'ILS' => "\u{20AA}",
            'ARS' => 'AR$',
            'CLP' => 'CLP$',
            'COP' => 'COL$',
            'PEN' => 'S/',
        ];

        return $symbols[strtoupper($currency)] ?? $currency;
    }

    /**
     * Check if the current or specified locale is RTL.
     */
    public function isRTL(?string $locale = null): bool
    {
        $localeInstance = $locale
            ? $this->getLocaleInstance($locale)
            : $this->getCurrentLocale();

        return $localeInstance?->is_rtl ?? false;
    }

    /**
     * Get the text direction for the current or specified locale.
     */
    public function getDirection(?string $locale = null): string
    {
        return $this->isRTL($locale) ? 'rtl' : 'ltr';
    }

    /**
     * Get the date format for the current or specified locale.
     */
    public function getDateFormat(?string $locale = null): string
    {
        $localeInstance = $locale
            ? $this->getLocaleInstance($locale)
            : $this->getCurrentLocale();

        return $localeInstance?->date_format ?? 'Y-m-d';
    }

    /**
     * Get the time format for the current or specified locale.
     */
    public function getTimeFormat(?string $locale = null): string
    {
        $localeInstance = $locale
            ? $this->getLocaleInstance($locale)
            : $this->getCurrentLocale();

        return $localeInstance?->time_format ?? 'H:i';
    }

    /**
     * Get the datetime format for the current or specified locale.
     */
    public function getDateTimeFormat(?string $locale = null): string
    {
        $localeInstance = $locale
            ? $this->getLocaleInstance($locale)
            : $this->getCurrentLocale();

        return $localeInstance?->datetime_format ?? 'Y-m-d H:i';
    }

    /**
     * Translate a key with optional replacements.
     * First checks database translations, then falls back to Laravel's trans().
     */
    public function translate(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->getLocale();

        // Parse the key (group.key format)
        if (str_contains($key, '.')) {
            [$group, $itemKey] = explode('.', $key, 2);

            // Try database translation first
            $dbTranslation = Translation::get($locale, $group, $itemKey);

            if ($dbTranslation !== null) {
                return $this->replaceParameters($dbTranslation, $replace);
            }
        }

        // Fall back to Laravel's translation system
        $translation = trans($key, $replace, $locale);

        // If trans() returns the key itself, the translation doesn't exist
        if ($translation === $key) {
            // Try default locale as fallback
            $defaultLocale = $this->getDefaultLocale();
            if ($locale !== $defaultLocale) {
                $fallbackTranslation = trans($key, $replace, $defaultLocale);
                if ($fallbackTranslation !== $key) {
                    return $fallbackTranslation;
                }
            }
        }

        return $translation;
    }

    /**
     * Alias for translate() to match Laravel's __() helper.
     */
    public function __(string $key, array $replace = [], ?string $locale = null): string
    {
        return $this->translate($key, $replace, $locale);
    }

    /**
     * Check if a locale is valid (exists and is active).
     */
    public function isValidLocale(string $locale): bool
    {
        return Locale::isValid($locale);
    }

    /**
     * Get the default locale code.
     */
    public function getDefaultLocale(): string
    {
        return config('app.locale', 'en');
    }

    /**
     * Get the fallback locale code.
     */
    public function getFallbackLocale(): string
    {
        return config('app.fallback_locale', 'en');
    }

    /**
     * Detect the best locale from various sources.
     * Priority: URL param > User preference > Browser header > Default
     */
    public function detectLocale(): string
    {
        // 1. Check URL parameter
        $urlLocale = request()->query('lang');
        if ($urlLocale && $this->isValidLocale($urlLocale)) {
            return $urlLocale;
        }

        // 2. Check session
        $sessionLocale = Session::get('locale');
        if ($sessionLocale && $this->isValidLocale($sessionLocale)) {
            return $sessionLocale;
        }

        // 3. Check authenticated user preference
        if (auth()->check()) {
            $userLocale = auth()->user()->locale ?? auth()->user()->language;
            if ($userLocale && $this->isValidLocale($userLocale)) {
                return $userLocale;
            }
        }

        // 4. Check browser Accept-Language header
        $browserLocale = $this->parseAcceptLanguageHeader();
        if ($browserLocale && $this->isValidLocale($browserLocale)) {
            return $browserLocale;
        }

        // 5. Return default
        return $this->getDefaultLocale();
    }

    /**
     * Parse the Accept-Language header to find the best match.
     */
    protected function parseAcceptLanguageHeader(): ?string
    {
        $acceptLanguage = request()->header('Accept-Language');

        if (! $acceptLanguage) {
            return null;
        }

        $supportedLocales = $this->getSupportedLocaleCodes();

        // Parse header value
        $languages = [];
        foreach (explode(',', $acceptLanguage) as $part) {
            $part = trim($part);
            $quality = 1.0;

            if (str_contains($part, ';q=')) {
                [$lang, $q] = explode(';q=', $part);
                $quality = (float) $q;
            } else {
                $lang = $part;
            }

            // Normalize: get primary language code (e.g., 'en-US' -> 'en')
            $primaryLang = strtolower(explode('-', $lang)[0]);
            $languages[$primaryLang] = max($languages[$primaryLang] ?? 0, $quality);
        }

        // Sort by quality (descending)
        arsort($languages);

        // Find first match in supported locales
        foreach (array_keys($languages) as $lang) {
            if (in_array($lang, $supportedLocales)) {
                return $lang;
            }
        }

        return null;
    }

    /**
     * Replace parameters in a translation string.
     */
    protected function replaceParameters(string $translation, array $replace): string
    {
        if (empty($replace)) {
            return $translation;
        }

        foreach ($replace as $key => $value) {
            $translation = str_replace(
                [':'.$key, ':'.strtoupper($key), ':'.ucfirst($key)],
                [$value, strtoupper($value), ucfirst($value)],
                $translation
            );
        }

        return $translation;
    }

    /**
     * Set PHP locale for native functions.
     */
    protected function setPhpLocale(string $locale): void
    {
        // Map short locale codes to full locale strings
        $localeMap = [
            'en' => 'en_US.UTF-8',
            'es' => 'es_ES.UTF-8',
            'fr' => 'fr_FR.UTF-8',
            'de' => 'de_DE.UTF-8',
            'it' => 'it_IT.UTF-8',
            'pt' => 'pt_BR.UTF-8',
            'nl' => 'nl_NL.UTF-8',
            'ar' => 'ar_SA.UTF-8',
            'zh' => 'zh_CN.UTF-8',
            'ja' => 'ja_JP.UTF-8',
            'ko' => 'ko_KR.UTF-8',
            'ru' => 'ru_RU.UTF-8',
            'tr' => 'tr_TR.UTF-8',
            'pl' => 'pl_PL.UTF-8',
            'sv' => 'sv_SE.UTF-8',
            'da' => 'da_DK.UTF-8',
            'fi' => 'fi_FI.UTF-8',
            'no' => 'no_NO.UTF-8',
            'cs' => 'cs_CZ.UTF-8',
            'hu' => 'hu_HU.UTF-8',
            'el' => 'el_GR.UTF-8',
            'he' => 'he_IL.UTF-8',
            'th' => 'th_TH.UTF-8',
            'vi' => 'vi_VN.UTF-8',
            'id' => 'id_ID.UTF-8',
            'ms' => 'ms_MY.UTF-8',
            'hi' => 'hi_IN.UTF-8',
        ];

        $fullLocale = $localeMap[$locale] ?? "{$locale}.UTF-8";

        // Set locale for various categories
        setlocale(LC_TIME, $fullLocale);
        setlocale(LC_MONETARY, $fullLocale);
        setlocale(LC_NUMERIC, $fullLocale);
        setlocale(LC_CTYPE, $fullLocale);
    }

    /**
     * Get formatted relative time (e.g., "2 hours ago").
     */
    public function formatRelativeTime(Carbon $date, ?string $locale = null): string
    {
        $locale = $locale ?? $this->getLocale();

        return $date->locale($locale)->diffForHumans();
    }

    /**
     * Get localized day names.
     *
     * @return array<int, string>
     */
    public function getDayNames(?string $locale = null): array
    {
        $locale = $locale ?? $this->getLocale();
        Carbon::setLocale($locale);

        return [
            0 => Carbon::now()->startOfWeek()->locale($locale)->dayName,
            1 => Carbon::now()->startOfWeek()->addDay()->locale($locale)->dayName,
            2 => Carbon::now()->startOfWeek()->addDays(2)->locale($locale)->dayName,
            3 => Carbon::now()->startOfWeek()->addDays(3)->locale($locale)->dayName,
            4 => Carbon::now()->startOfWeek()->addDays(4)->locale($locale)->dayName,
            5 => Carbon::now()->startOfWeek()->addDays(5)->locale($locale)->dayName,
            6 => Carbon::now()->startOfWeek()->addDays(6)->locale($locale)->dayName,
        ];
    }

    /**
     * Get localized month names.
     *
     * @return array<int, string>
     */
    public function getMonthNames(?string $locale = null): array
    {
        $locale = $locale ?? $this->getLocale();
        Carbon::setLocale($locale);

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = Carbon::create(null, $i, 1)->locale($locale)->monthName;
        }

        return $months;
    }

    /**
     * Get options for locale selector dropdown.
     *
     * @return array<string, string>
     */
    public function getLocaleOptions(): array
    {
        return Locale::getOptionsWithFlags();
    }

    /**
     * Update translation progress for a locale.
     */
    public function updateTranslationProgress(string $localeCode): int
    {
        $defaultLocale = $this->getDefaultLocale();

        // Count translations in default locale
        $totalKeys = Translation::where('locale', $defaultLocale)->count();

        if ($totalKeys === 0) {
            return 0;
        }

        // Count translations in target locale
        $translatedKeys = Translation::where('locale', $localeCode)->count();

        // Calculate percentage
        $progress = (int) round(($translatedKeys / $totalKeys) * 100);

        // Update locale record
        $locale = Locale::findByCode($localeCode);
        if ($locale) {
            $locale->update(['translation_progress' => $progress]);
        }

        return $progress;
    }
}
