<?php

namespace App\Providers;

use App\Services\LocalizationService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * GLO-006: Localization Engine - Service Provider
 * Registers Blade directives for locale-aware formatting.
 */
class LocalizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register LocalizationService as singleton
        $this->app->singleton(LocalizationService::class, function ($app) {
            return new LocalizationService;
        });

        // Alias for easier access
        $this->app->alias(LocalizationService::class, 'localization');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerBladeDirectives();
    }

    /**
     * Register Blade directives for localization.
     */
    protected function registerBladeDirectives(): void
    {
        /**
         * Format a date according to locale settings.
         * Usage: @localeDate($date) or @localeDate($date, 'es')
         */
        Blade::directive('localeDate', function ($expression) {
            return "<?php
                \$args = [$expression];
                \$date = \$args[0] ?? null;
                \$locale = \$args[1] ?? null;
                if (\$date) {
                    \$date = \$date instanceof \Carbon\Carbon ? \$date : \Carbon\Carbon::parse(\$date);
                    echo app(\App\Services\LocalizationService::class)->formatDate(\$date, \$locale);
                }
            ?>";
        });

        /**
         * Format a time according to locale settings.
         * Usage: @localeTime($time) or @localeTime($time, 'es')
         */
        Blade::directive('localeTime', function ($expression) {
            return "<?php
                \$args = [$expression];
                \$time = \$args[0] ?? null;
                \$locale = \$args[1] ?? null;
                if (\$time) {
                    \$time = \$time instanceof \Carbon\Carbon ? \$time : \Carbon\Carbon::parse(\$time);
                    echo app(\App\Services\LocalizationService::class)->formatTime(\$time, \$locale);
                }
            ?>";
        });

        /**
         * Format a datetime according to locale settings.
         * Usage: @localeDateTime($datetime) or @localeDateTime($datetime, 'es')
         */
        Blade::directive('localeDateTime', function ($expression) {
            return "<?php
                \$args = [$expression];
                \$datetime = \$args[0] ?? null;
                \$locale = \$args[1] ?? null;
                if (\$datetime) {
                    \$datetime = \$datetime instanceof \Carbon\Carbon ? \$datetime : \Carbon\Carbon::parse(\$datetime);
                    echo app(\App\Services\LocalizationService::class)->formatDateTime(\$datetime, \$locale);
                }
            ?>";
        });

        /**
         * Format a currency amount according to locale settings.
         * Usage: @localeCurrency($amount, 'EUR') or @localeCurrency($amount, 'EUR', 'es')
         */
        Blade::directive('localeCurrency', function ($expression) {
            return "<?php
                \$args = [$expression];
                \$amount = \$args[0] ?? 0;
                \$currency = \$args[1] ?? 'EUR';
                \$locale = \$args[2] ?? null;
                echo app(\App\Services\LocalizationService::class)->formatCurrency((float)\$amount, \$currency, \$locale);
            ?>";
        });

        /**
         * Format a number according to locale settings.
         * Usage: @localeNumber($number) or @localeNumber($number, 'es', 2)
         */
        Blade::directive('localeNumber', function ($expression) {
            return "<?php
                \$args = [$expression];
                \$number = \$args[0] ?? 0;
                \$locale = \$args[1] ?? null;
                \$decimals = \$args[2] ?? 2;
                echo app(\App\Services\LocalizationService::class)->formatNumber((float)\$number, \$locale, \$decimals);
            ?>";
        });

        /**
         * Format relative time (e.g., "2 hours ago").
         * Usage: @localeRelative($date) or @localeRelative($date, 'es')
         */
        Blade::directive('localeRelative', function ($expression) {
            return "<?php
                \$args = [$expression];
                \$date = \$args[0] ?? null;
                \$locale = \$args[1] ?? null;
                if (\$date) {
                    \$date = \$date instanceof \Carbon\Carbon ? \$date : \Carbon\Carbon::parse(\$date);
                    echo app(\App\Services\LocalizationService::class)->formatRelativeTime(\$date, \$locale);
                }
            ?>";
        });

        /**
         * Check if current locale is RTL.
         * Usage: @rtl ... @endrtl
         */
        Blade::directive('rtl', function () {
            return "<?php if(app(\App\Services\LocalizationService::class)->isRTL()): ?>";
        });

        Blade::directive('endrtl', function () {
            return '<?php endif; ?>';
        });

        /**
         * Check if current locale is LTR.
         * Usage: @ltr ... @endltr
         */
        Blade::directive('ltr', function () {
            return "<?php if(!app(\App\Services\LocalizationService::class)->isRTL()): ?>";
        });

        Blade::directive('endltr', function () {
            return '<?php endif; ?>';
        });

        /**
         * Get text direction attribute.
         * Usage: <html dir="@localeDirection">
         */
        Blade::directive('localeDirection', function () {
            return "<?php echo app(\App\Services\LocalizationService::class)->getDirection(); ?>";
        });

        /**
         * Get current locale code.
         * Usage: <html lang="@localeCode">
         */
        Blade::directive('localeCode', function () {
            return "<?php echo app(\App\Services\LocalizationService::class)->getLocale(); ?>";
        });

        /**
         * Locale-aware translation with database fallback.
         * Usage: @localeTranslate('general.welcome', ['name' => 'John'])
         */
        Blade::directive('localeTranslate', function ($expression) {
            return "<?php
                \$args = [$expression];
                \$key = \$args[0] ?? '';
                \$replace = \$args[1] ?? [];
                \$locale = \$args[2] ?? null;
                echo app(\App\Services\LocalizationService::class)->translate(\$key, \$replace, \$locale);
            ?>";
        });

        /**
         * Alias for localeTranslate.
         * Usage: @l('general.welcome')
         */
        Blade::directive('l', function ($expression) {
            return "<?php echo app(\App\Services\LocalizationService::class)->translate($expression); ?>";
        });

        /**
         * Locale picker dropdown component.
         * Usage: @localePicker or @localePicker('dropdown')
         */
        Blade::directive('localePicker', function ($expression) {
            $style = $expression ?: "'dropdown'";

            return "<?php
                \$locales = \App\Models\Locale::getActive();
                \$currentLocale = app(\App\Services\LocalizationService::class)->getLocale();
                \$currentLocaleModel = \App\Models\Locale::findByCode(\$currentLocale);
                \$style = $style;
            ?>
            @include('components.locale-picker', [
                'locales' => \$locales,
                'currentLocale' => \$currentLocale,
                'currentLocaleModel' => \$currentLocaleModel,
                'style' => \$style
            ])";
        });
    }
}
