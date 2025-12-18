<?php

namespace App\Support;

/**
 * GLO-008: Cross-Border Payments - IBAN Validator
 *
 * Validates International Bank Account Numbers (IBAN) using checksum validation
 * and country-specific length requirements.
 */
class IBANValidator
{
    /**
     * IBAN length requirements by country code.
     * Source: SWIFT IBAN Registry
     */
    protected const IBAN_LENGTHS = [
        'AL' => 28, // Albania
        'AD' => 24, // Andorra
        'AT' => 20, // Austria
        'AZ' => 28, // Azerbaijan
        'BH' => 22, // Bahrain
        'BY' => 28, // Belarus
        'BE' => 16, // Belgium
        'BA' => 20, // Bosnia and Herzegovina
        'BR' => 29, // Brazil
        'BG' => 22, // Bulgaria
        'CR' => 22, // Costa Rica
        'HR' => 21, // Croatia
        'CY' => 28, // Cyprus
        'CZ' => 24, // Czech Republic
        'DK' => 18, // Denmark
        'DO' => 28, // Dominican Republic
        'TL' => 23, // East Timor
        'EG' => 29, // Egypt
        'EE' => 20, // Estonia
        'FO' => 18, // Faroe Islands
        'FI' => 18, // Finland
        'FR' => 27, // France
        'GE' => 22, // Georgia
        'DE' => 22, // Germany
        'GI' => 23, // Gibraltar
        'GR' => 27, // Greece
        'GL' => 18, // Greenland
        'GT' => 28, // Guatemala
        'HU' => 28, // Hungary
        'IS' => 26, // Iceland
        'IQ' => 23, // Iraq
        'IE' => 22, // Ireland
        'IL' => 23, // Israel
        'IT' => 27, // Italy
        'JO' => 30, // Jordan
        'KZ' => 20, // Kazakhstan
        'XK' => 20, // Kosovo
        'KW' => 30, // Kuwait
        'LV' => 21, // Latvia
        'LB' => 28, // Lebanon
        'LI' => 21, // Liechtenstein
        'LT' => 20, // Lithuania
        'LU' => 20, // Luxembourg
        'MK' => 19, // North Macedonia
        'MT' => 31, // Malta
        'MR' => 27, // Mauritania
        'MU' => 30, // Mauritius
        'MC' => 27, // Monaco
        'MD' => 24, // Moldova
        'ME' => 22, // Montenegro
        'NL' => 18, // Netherlands
        'NO' => 15, // Norway
        'PK' => 24, // Pakistan
        'PS' => 29, // Palestinian territories
        'PL' => 28, // Poland
        'PT' => 25, // Portugal
        'QA' => 29, // Qatar
        'RO' => 24, // Romania
        'LC' => 32, // Saint Lucia
        'SM' => 27, // San Marino
        'ST' => 25, // Sao Tome and Principe
        'SA' => 24, // Saudi Arabia
        'RS' => 22, // Serbia
        'SC' => 31, // Seychelles
        'SK' => 24, // Slovakia
        'SI' => 19, // Slovenia
        'ES' => 24, // Spain
        'SE' => 24, // Sweden
        'CH' => 21, // Switzerland
        'TN' => 24, // Tunisia
        'TR' => 26, // Turkey
        'UA' => 29, // Ukraine
        'AE' => 23, // United Arab Emirates
        'GB' => 22, // United Kingdom
        'VA' => 22, // Vatican City
        'VG' => 24, // Virgin Islands, British
    ];

    /**
     * Validate an IBAN.
     */
    public static function validate(string $iban): bool
    {
        // Remove spaces and convert to uppercase
        $iban = self::normalize($iban);

        // Basic format check
        if (! preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $iban)) {
            return false;
        }

        // Get the country code
        $countryCode = substr($iban, 0, 2);

        // Check if we know this country's IBAN length
        if (isset(self::IBAN_LENGTHS[$countryCode])) {
            if (strlen($iban) !== self::IBAN_LENGTHS[$countryCode]) {
                return false;
            }
        } elseif (strlen($iban) < 15 || strlen($iban) > 34) {
            // General IBAN length bounds
            return false;
        }

        // Validate the checksum
        return self::validateChecksum($iban);
    }

    /**
     * Normalize an IBAN by removing spaces and converting to uppercase.
     */
    public static function normalize(string $iban): string
    {
        return strtoupper(preg_replace('/\s+/', '', $iban));
    }

    /**
     * Validate the IBAN checksum using the MOD-97 algorithm.
     */
    protected static function validateChecksum(string $iban): bool
    {
        // Move the first 4 characters to the end
        $rearranged = substr($iban, 4).substr($iban, 0, 4);

        // Replace letters with numbers (A=10, B=11, ..., Z=35)
        $numericIban = self::lettersToNumbers($rearranged);

        // Perform MOD-97 check
        return self::mod97($numericIban) === 1;
    }

    /**
     * Convert letters in the IBAN to their numeric equivalents.
     * A = 10, B = 11, ..., Z = 35
     */
    protected static function lettersToNumbers(string $iban): string
    {
        $result = '';
        $length = strlen($iban);

        for ($i = 0; $i < $length; $i++) {
            $char = $iban[$i];
            if (ctype_alpha($char)) {
                $result .= (ord($char) - 55);
            } else {
                $result .= $char;
            }
        }

        return $result;
    }

    /**
     * Calculate MOD-97 for a large number represented as a string.
     * Uses piece-wise calculation to handle arbitrarily large numbers.
     */
    protected static function mod97(string $number): int
    {
        $remainder = 0;
        $length = strlen($number);

        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $number[$i];
            $remainder = ($remainder * 10 + $digit) % 97;
        }

        return $remainder;
    }

    /**
     * Format an IBAN for display (groups of 4 characters).
     */
    public static function format(string $iban): string
    {
        $iban = self::normalize($iban);

        return trim(chunk_split($iban, 4, ' '));
    }

    /**
     * Get the country code from an IBAN.
     */
    public static function getCountryCode(string $iban): string
    {
        return substr(self::normalize($iban), 0, 2);
    }

    /**
     * Get the check digits from an IBAN.
     */
    public static function getCheckDigits(string $iban): string
    {
        return substr(self::normalize($iban), 2, 2);
    }

    /**
     * Get the BBAN (Basic Bank Account Number) from an IBAN.
     */
    public static function getBBAN(string $iban): string
    {
        return substr(self::normalize($iban), 4);
    }

    /**
     * Calculate check digits for a given country code and BBAN.
     */
    public static function calculateCheckDigits(string $countryCode, string $bban): string
    {
        $countryCode = strtoupper($countryCode);
        $bban = strtoupper(preg_replace('/\s+/', '', $bban));

        // Create a temporary IBAN with check digits 00
        $tempIban = $bban.$countryCode.'00';

        // Convert to numeric
        $numericIban = self::lettersToNumbers($tempIban);

        // Calculate remainder
        $remainder = self::mod97($numericIban);

        // Calculate check digits
        $checkDigits = 98 - $remainder;

        return str_pad((string) $checkDigits, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Generate an IBAN from country code and BBAN.
     */
    public static function generate(string $countryCode, string $bban): string
    {
        $countryCode = strtoupper($countryCode);
        $checkDigits = self::calculateCheckDigits($countryCode, $bban);

        return $countryCode.$checkDigits.$bban;
    }

    /**
     * Get validation error message.
     */
    public static function getValidationError(string $iban): ?string
    {
        $iban = self::normalize($iban);

        if (empty($iban)) {
            return 'IBAN is required';
        }

        if (! preg_match('/^[A-Z]{2}/', $iban)) {
            return 'IBAN must start with a 2-letter country code';
        }

        if (! preg_match('/^[A-Z]{2}[0-9]{2}/', $iban)) {
            return 'IBAN must have 2 check digits after the country code';
        }

        if (! preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $iban)) {
            return 'IBAN contains invalid characters';
        }

        $countryCode = substr($iban, 0, 2);
        if (isset(self::IBAN_LENGTHS[$countryCode])) {
            $expectedLength = self::IBAN_LENGTHS[$countryCode];
            $actualLength = strlen($iban);
            if ($actualLength !== $expectedLength) {
                return "IBAN for {$countryCode} must be {$expectedLength} characters (got {$actualLength})";
            }
        }

        if (! self::validateChecksum($iban)) {
            return 'IBAN checksum is invalid';
        }

        return null;
    }

    /**
     * Check if a country supports IBAN.
     */
    public static function isIbanCountry(string $countryCode): bool
    {
        return isset(self::IBAN_LENGTHS[strtoupper($countryCode)]);
    }

    /**
     * Get the expected IBAN length for a country.
     */
    public static function getExpectedLength(string $countryCode): ?int
    {
        return self::IBAN_LENGTHS[strtoupper($countryCode)] ?? null;
    }

    /**
     * Get all supported country codes.
     */
    public static function getSupportedCountries(): array
    {
        return array_keys(self::IBAN_LENGTHS);
    }
}
