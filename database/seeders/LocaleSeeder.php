<?php

namespace Database\Seeders;

use App\Models\Locale;
use Illuminate\Database\Seeder;

/**
 * GLO-006: Localization Engine - Seed supported locales
 */
class LocaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locales = [
            // English (Default)
            [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'flag_emoji' => "\u{1F1EC}\u{1F1E7}", // GB flag
                'is_rtl' => false,
                'date_format' => 'M d, Y',
                'time_format' => 'g:i A',
                'datetime_format' => 'M d, Y g:i A',
                'number_decimal_separator' => '.',
                'number_thousands_separator' => ',',
                'currency_position' => 'before',
                'translation_progress' => 100,
                'is_active' => true,
            ],
            // Spanish
            [
                'code' => 'es',
                'name' => 'Spanish',
                'native_name' => 'Espanol',
                'flag_emoji' => "\u{1F1EA}\u{1F1F8}", // ES flag
                'is_rtl' => false,
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'datetime_format' => 'd/m/Y H:i',
                'number_decimal_separator' => ',',
                'number_thousands_separator' => '.',
                'currency_position' => 'after',
                'translation_progress' => 95,
                'is_active' => true,
            ],
            // French
            [
                'code' => 'fr',
                'name' => 'French',
                'native_name' => 'Francais',
                'flag_emoji' => "\u{1F1EB}\u{1F1F7}", // FR flag
                'is_rtl' => false,
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'datetime_format' => 'd/m/Y H:i',
                'number_decimal_separator' => ',',
                'number_thousands_separator' => ' ',
                'currency_position' => 'after',
                'translation_progress' => 85,
                'is_active' => true,
            ],
            // German
            [
                'code' => 'de',
                'name' => 'German',
                'native_name' => 'Deutsch',
                'flag_emoji' => "\u{1F1E9}\u{1F1EA}", // DE flag
                'is_rtl' => false,
                'date_format' => 'd.m.Y',
                'time_format' => 'H:i',
                'datetime_format' => 'd.m.Y H:i',
                'number_decimal_separator' => ',',
                'number_thousands_separator' => '.',
                'currency_position' => 'after',
                'translation_progress' => 0,
                'is_active' => true,
            ],
            // Portuguese
            [
                'code' => 'pt',
                'name' => 'Portuguese',
                'native_name' => 'Portugues',
                'flag_emoji' => "\u{1F1E7}\u{1F1F7}", // BR flag (Brazilian Portuguese)
                'is_rtl' => false,
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'datetime_format' => 'd/m/Y H:i',
                'number_decimal_separator' => ',',
                'number_thousands_separator' => '.',
                'currency_position' => 'before',
                'translation_progress' => 0,
                'is_active' => true,
            ],
            // Italian
            [
                'code' => 'it',
                'name' => 'Italian',
                'native_name' => 'Italiano',
                'flag_emoji' => "\u{1F1EE}\u{1F1F9}", // IT flag
                'is_rtl' => false,
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'datetime_format' => 'd/m/Y H:i',
                'number_decimal_separator' => ',',
                'number_thousands_separator' => '.',
                'currency_position' => 'after',
                'translation_progress' => 0,
                'is_active' => true,
            ],
            // Dutch
            [
                'code' => 'nl',
                'name' => 'Dutch',
                'native_name' => 'Nederlands',
                'flag_emoji' => "\u{1F1F3}\u{1F1F1}", // NL flag
                'is_rtl' => false,
                'date_format' => 'd-m-Y',
                'time_format' => 'H:i',
                'datetime_format' => 'd-m-Y H:i',
                'number_decimal_separator' => ',',
                'number_thousands_separator' => '.',
                'currency_position' => 'before',
                'translation_progress' => 0,
                'is_active' => true,
            ],
            // Arabic (RTL)
            [
                'code' => 'ar',
                'name' => 'Arabic',
                'native_name' => "\u{0627}\u{0644}\u{0639}\u{0631}\u{0628}\u{064A}\u{0629}",
                'flag_emoji' => "\u{1F1F8}\u{1F1E6}", // SA flag
                'is_rtl' => true,
                'date_format' => 'Y/m/d',
                'time_format' => 'H:i',
                'datetime_format' => 'Y/m/d H:i',
                'number_decimal_separator' => "\u{066B}",
                'number_thousands_separator' => "\u{066C}",
                'currency_position' => 'after',
                'translation_progress' => 0,
                'is_active' => true,
            ],
            // Chinese (Simplified)
            [
                'code' => 'zh',
                'name' => 'Chinese',
                'native_name' => "\u{4E2D}\u{6587}",
                'flag_emoji' => "\u{1F1E8}\u{1F1F3}", // CN flag
                'is_rtl' => false,
                'date_format' => 'Y/m/d',
                'time_format' => 'H:i',
                'datetime_format' => 'Y/m/d H:i',
                'number_decimal_separator' => '.',
                'number_thousands_separator' => ',',
                'currency_position' => 'before',
                'translation_progress' => 0,
                'is_active' => true,
            ],
            // Japanese
            [
                'code' => 'ja',
                'name' => 'Japanese',
                'native_name' => "\u{65E5}\u{672C}\u{8A9E}",
                'flag_emoji' => "\u{1F1EF}\u{1F1F5}", // JP flag
                'is_rtl' => false,
                'date_format' => 'Y/m/d',
                'time_format' => 'H:i',
                'datetime_format' => 'Y/m/d H:i',
                'number_decimal_separator' => '.',
                'number_thousands_separator' => ',',
                'currency_position' => 'before',
                'translation_progress' => 0,
                'is_active' => true,
            ],
            // Korean
            [
                'code' => 'ko',
                'name' => 'Korean',
                'native_name' => "\u{D55C}\u{AD6D}\u{C5B4}",
                'flag_emoji' => "\u{1F1F0}\u{1F1F7}", // KR flag
                'is_rtl' => false,
                'date_format' => 'Y. m. d.',
                'time_format' => 'H:i',
                'datetime_format' => 'Y. m. d. H:i',
                'number_decimal_separator' => '.',
                'number_thousands_separator' => ',',
                'currency_position' => 'before',
                'translation_progress' => 0,
                'is_active' => false, // Disabled by default, enable when translations ready
            ],
            // Russian
            [
                'code' => 'ru',
                'name' => 'Russian',
                'native_name' => "\u{0420}\u{0443}\u{0441}\u{0441}\u{043A}\u{0438}\u{0439}",
                'flag_emoji' => "\u{1F1F7}\u{1F1FA}", // RU flag
                'is_rtl' => false,
                'date_format' => 'd.m.Y',
                'time_format' => 'H:i',
                'datetime_format' => 'd.m.Y H:i',
                'number_decimal_separator' => ',',
                'number_thousands_separator' => ' ',
                'currency_position' => 'after',
                'translation_progress' => 0,
                'is_active' => false,
            ],
            // Turkish
            [
                'code' => 'tr',
                'name' => 'Turkish',
                'native_name' => "T\u{00FC}rk\u{00E7}e",
                'flag_emoji' => "\u{1F1F9}\u{1F1F7}", // TR flag
                'is_rtl' => false,
                'date_format' => 'd.m.Y',
                'time_format' => 'H:i',
                'datetime_format' => 'd.m.Y H:i',
                'number_decimal_separator' => ',',
                'number_thousands_separator' => '.',
                'currency_position' => 'after',
                'translation_progress' => 0,
                'is_active' => false,
            ],
            // Hindi
            [
                'code' => 'hi',
                'name' => 'Hindi',
                'native_name' => "\u{0939}\u{093F}\u{0928}\u{094D}\u{0926}\u{0940}",
                'flag_emoji' => "\u{1F1EE}\u{1F1F3}", // IN flag
                'is_rtl' => false,
                'date_format' => 'd/m/Y',
                'time_format' => 'h:i A',
                'datetime_format' => 'd/m/Y h:i A',
                'number_decimal_separator' => '.',
                'number_thousands_separator' => ',',
                'currency_position' => 'before',
                'translation_progress' => 0,
                'is_active' => false,
            ],
            // Polish
            [
                'code' => 'pl',
                'name' => 'Polish',
                'native_name' => 'Polski',
                'flag_emoji' => "\u{1F1F5}\u{1F1F1}", // PL flag
                'is_rtl' => false,
                'date_format' => 'd.m.Y',
                'time_format' => 'H:i',
                'datetime_format' => 'd.m.Y H:i',
                'number_decimal_separator' => ',',
                'number_thousands_separator' => ' ',
                'currency_position' => 'after',
                'translation_progress' => 0,
                'is_active' => false,
            ],
            // Hebrew (RTL)
            [
                'code' => 'he',
                'name' => 'Hebrew',
                'native_name' => "\u{05E2}\u{05D1}\u{05E8}\u{05D9}\u{05EA}",
                'flag_emoji' => "\u{1F1EE}\u{1F1F1}", // IL flag
                'is_rtl' => true,
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'datetime_format' => 'd/m/Y H:i',
                'number_decimal_separator' => '.',
                'number_thousands_separator' => ',',
                'currency_position' => 'before',
                'translation_progress' => 0,
                'is_active' => false,
            ],
            // Thai
            [
                'code' => 'th',
                'name' => 'Thai',
                'native_name' => "\u{0E44}\u{0E17}\u{0E22}",
                'flag_emoji' => "\u{1F1F9}\u{1F1ED}", // TH flag
                'is_rtl' => false,
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'datetime_format' => 'd/m/Y H:i',
                'number_decimal_separator' => '.',
                'number_thousands_separator' => ',',
                'currency_position' => 'before',
                'translation_progress' => 0,
                'is_active' => false,
            ],
            // Vietnamese
            [
                'code' => 'vi',
                'name' => 'Vietnamese',
                'native_name' => "Ti\u{1EBF}ng Vi\u{1EC7}t",
                'flag_emoji' => "\u{1F1FB}\u{1F1F3}", // VN flag
                'is_rtl' => false,
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'datetime_format' => 'd/m/Y H:i',
                'number_decimal_separator' => ',',
                'number_thousands_separator' => '.',
                'currency_position' => 'after',
                'translation_progress' => 0,
                'is_active' => false,
            ],
            // Indonesian
            [
                'code' => 'id',
                'name' => 'Indonesian',
                'native_name' => 'Bahasa Indonesia',
                'flag_emoji' => "\u{1F1EE}\u{1F1E9}", // ID flag
                'is_rtl' => false,
                'date_format' => 'd/m/Y',
                'time_format' => 'H.i',
                'datetime_format' => 'd/m/Y H.i',
                'number_decimal_separator' => ',',
                'number_thousands_separator' => '.',
                'currency_position' => 'before',
                'translation_progress' => 0,
                'is_active' => false,
            ],
            // Swahili
            [
                'code' => 'sw',
                'name' => 'Swahili',
                'native_name' => 'Kiswahili',
                'flag_emoji' => "\u{1F1F0}\u{1F1EA}", // KE flag
                'is_rtl' => false,
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'datetime_format' => 'd/m/Y H:i',
                'number_decimal_separator' => '.',
                'number_thousands_separator' => ',',
                'currency_position' => 'before',
                'translation_progress' => 0,
                'is_active' => false,
            ],
        ];

        foreach ($locales as $locale) {
            Locale::updateOrCreate(
                ['code' => $locale['code']],
                $locale
            );
        }

        $this->command->info('Seeded '.count($locales).' locales.');
    }
}
