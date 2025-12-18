<?php

namespace Database\Seeders;

use App\Models\TaxJurisdiction;
use Illuminate\Database\Seeder;

/**
 * GLO-002: Tax Jurisdiction Engine - Seed jurisdictions for US, UK, EU, Australia
 */
class TaxJurisdictionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // US Federal
        $this->seedUSJurisdictions();

        // UK
        $this->seedUKJurisdictions();

        // EU Countries
        $this->seedEUJurisdictions();

        // Australia
        $this->seedAustraliaJurisdictions();

        // Other major markets
        $this->seedOtherJurisdictions();

        $this->command->info('Tax jurisdictions seeded successfully!');
    }

    /**
     * Seed US federal and state tax jurisdictions.
     */
    protected function seedUSJurisdictions(): void
    {
        // US Federal
        TaxJurisdiction::updateOrCreate(
            ['country_code' => 'US', 'state_code' => null, 'city' => null],
            [
                'name' => 'United States (Federal)',
                'income_tax_rate' => 22.00, // Default federal bracket
                'social_security_rate' => 7.65, // FICA (6.2% SS + 1.45% Medicare)
                'vat_rate' => 0,
                'vat_reverse_charge' => false,
                'withholding_rate' => 30.00, // Default for non-residents without W-8BEN
                'tax_brackets' => [
                    ['threshold' => 11600, 'rate' => 10],
                    ['threshold' => 47150, 'rate' => 12],
                    ['threshold' => 100525, 'rate' => 22],
                    ['threshold' => 191950, 'rate' => 24],
                    ['threshold' => 243725, 'rate' => 32],
                    ['threshold' => 609350, 'rate' => 35],
                    ['threshold' => PHP_INT_MAX, 'rate' => 37],
                ],
                'tax_free_threshold' => 14600, // 2024 standard deduction for single
                'requires_w9' => true,
                'requires_w8ben' => false,
                'tax_id_format' => '/^\d{3}-\d{2}-\d{4}$|^\d{2}-\d{7}$/',
                'tax_id_name' => 'SSN/EIN',
                'currency_code' => 'USD',
                'is_active' => true,
            ]
        );

        // US States with income tax
        $usStates = [
            ['code' => 'CA', 'name' => 'California', 'rate' => 9.30, 'threshold' => 0],
            ['code' => 'NY', 'name' => 'New York', 'rate' => 8.82, 'threshold' => 0],
            ['code' => 'TX', 'name' => 'Texas', 'rate' => 0, 'threshold' => 0], // No state income tax
            ['code' => 'FL', 'name' => 'Florida', 'rate' => 0, 'threshold' => 0], // No state income tax
            ['code' => 'IL', 'name' => 'Illinois', 'rate' => 4.95, 'threshold' => 0],
            ['code' => 'PA', 'name' => 'Pennsylvania', 'rate' => 3.07, 'threshold' => 0],
            ['code' => 'OH', 'name' => 'Ohio', 'rate' => 3.99, 'threshold' => 26050],
            ['code' => 'GA', 'name' => 'Georgia', 'rate' => 5.49, 'threshold' => 0],
            ['code' => 'NC', 'name' => 'North Carolina', 'rate' => 4.75, 'threshold' => 0],
            ['code' => 'MI', 'name' => 'Michigan', 'rate' => 4.25, 'threshold' => 0],
            ['code' => 'NJ', 'name' => 'New Jersey', 'rate' => 10.75, 'threshold' => 0],
            ['code' => 'VA', 'name' => 'Virginia', 'rate' => 5.75, 'threshold' => 0],
            ['code' => 'WA', 'name' => 'Washington', 'rate' => 0, 'threshold' => 0], // No state income tax
            ['code' => 'AZ', 'name' => 'Arizona', 'rate' => 2.50, 'threshold' => 0],
            ['code' => 'MA', 'name' => 'Massachusetts', 'rate' => 5.00, 'threshold' => 0],
            ['code' => 'TN', 'name' => 'Tennessee', 'rate' => 0, 'threshold' => 0], // No state income tax
            ['code' => 'IN', 'name' => 'Indiana', 'rate' => 3.15, 'threshold' => 0],
            ['code' => 'MO', 'name' => 'Missouri', 'rate' => 4.95, 'threshold' => 0],
            ['code' => 'MD', 'name' => 'Maryland', 'rate' => 5.75, 'threshold' => 0],
            ['code' => 'WI', 'name' => 'Wisconsin', 'rate' => 7.65, 'threshold' => 0],
            ['code' => 'CO', 'name' => 'Colorado', 'rate' => 4.40, 'threshold' => 0],
            ['code' => 'MN', 'name' => 'Minnesota', 'rate' => 9.85, 'threshold' => 0],
            ['code' => 'SC', 'name' => 'South Carolina', 'rate' => 6.40, 'threshold' => 0],
            ['code' => 'AL', 'name' => 'Alabama', 'rate' => 5.00, 'threshold' => 0],
            ['code' => 'LA', 'name' => 'Louisiana', 'rate' => 4.25, 'threshold' => 0],
            ['code' => 'KY', 'name' => 'Kentucky', 'rate' => 4.00, 'threshold' => 0],
            ['code' => 'OR', 'name' => 'Oregon', 'rate' => 9.90, 'threshold' => 0],
            ['code' => 'OK', 'name' => 'Oklahoma', 'rate' => 4.75, 'threshold' => 0],
            ['code' => 'CT', 'name' => 'Connecticut', 'rate' => 6.99, 'threshold' => 0],
            ['code' => 'UT', 'name' => 'Utah', 'rate' => 4.65, 'threshold' => 0],
            ['code' => 'NV', 'name' => 'Nevada', 'rate' => 0, 'threshold' => 0], // No state income tax
            ['code' => 'IA', 'name' => 'Iowa', 'rate' => 5.70, 'threshold' => 0],
            ['code' => 'AR', 'name' => 'Arkansas', 'rate' => 4.40, 'threshold' => 0],
            ['code' => 'MS', 'name' => 'Mississippi', 'rate' => 5.00, 'threshold' => 0],
            ['code' => 'KS', 'name' => 'Kansas', 'rate' => 5.70, 'threshold' => 0],
            ['code' => 'NM', 'name' => 'New Mexico', 'rate' => 5.90, 'threshold' => 0],
            ['code' => 'NE', 'name' => 'Nebraska', 'rate' => 6.64, 'threshold' => 0],
            ['code' => 'ID', 'name' => 'Idaho', 'rate' => 5.80, 'threshold' => 0],
            ['code' => 'WV', 'name' => 'West Virginia', 'rate' => 5.12, 'threshold' => 0],
            ['code' => 'HI', 'name' => 'Hawaii', 'rate' => 11.00, 'threshold' => 0],
            ['code' => 'NH', 'name' => 'New Hampshire', 'rate' => 0, 'threshold' => 0], // No wage tax
            ['code' => 'ME', 'name' => 'Maine', 'rate' => 7.15, 'threshold' => 0],
            ['code' => 'RI', 'name' => 'Rhode Island', 'rate' => 5.99, 'threshold' => 0],
            ['code' => 'MT', 'name' => 'Montana', 'rate' => 5.90, 'threshold' => 0],
            ['code' => 'DE', 'name' => 'Delaware', 'rate' => 6.60, 'threshold' => 0],
            ['code' => 'SD', 'name' => 'South Dakota', 'rate' => 0, 'threshold' => 0], // No state income tax
            ['code' => 'ND', 'name' => 'North Dakota', 'rate' => 2.50, 'threshold' => 0],
            ['code' => 'AK', 'name' => 'Alaska', 'rate' => 0, 'threshold' => 0], // No state income tax
            ['code' => 'VT', 'name' => 'Vermont', 'rate' => 8.75, 'threshold' => 0],
            ['code' => 'WY', 'name' => 'Wyoming', 'rate' => 0, 'threshold' => 0], // No state income tax
            ['code' => 'DC', 'name' => 'District of Columbia', 'rate' => 10.75, 'threshold' => 0],
        ];

        foreach ($usStates as $state) {
            TaxJurisdiction::updateOrCreate(
                ['country_code' => 'US', 'state_code' => $state['code'], 'city' => null],
                [
                    'name' => $state['name'],
                    'income_tax_rate' => $state['rate'],
                    'social_security_rate' => 0, // State level doesn't have SS
                    'vat_rate' => 0,
                    'vat_reverse_charge' => false,
                    'withholding_rate' => 0,
                    'tax_free_threshold' => $state['threshold'],
                    'requires_w9' => true,
                    'requires_w8ben' => false,
                    'tax_id_format' => '/^\d{3}-\d{2}-\d{4}$|^\d{2}-\d{7}$/',
                    'tax_id_name' => 'SSN/EIN',
                    'currency_code' => 'USD',
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Seed UK tax jurisdictions.
     */
    protected function seedUKJurisdictions(): void
    {
        TaxJurisdiction::updateOrCreate(
            ['country_code' => 'GB', 'state_code' => null, 'city' => null],
            [
                'name' => 'United Kingdom',
                'income_tax_rate' => 20.00, // Basic rate
                'social_security_rate' => 12.00, // National Insurance Class 1
                'vat_rate' => 20.00,
                'vat_reverse_charge' => false,
                'withholding_rate' => 0,
                'tax_brackets' => [
                    ['threshold' => 12570, 'rate' => 0], // Personal allowance
                    ['threshold' => 50270, 'rate' => 20], // Basic rate
                    ['threshold' => 125140, 'rate' => 40], // Higher rate
                    ['threshold' => PHP_INT_MAX, 'rate' => 45], // Additional rate
                ],
                'tax_free_threshold' => 12570, // 2024/25 personal allowance
                'requires_w9' => false,
                'requires_w8ben' => false,
                'tax_id_format' => '/^[A-Z]{2}\d{6}[A-Z]$/',
                'tax_id_name' => 'National Insurance Number',
                'currency_code' => 'GBP',
                'is_active' => true,
            ]
        );

        // Scotland (different rates)
        TaxJurisdiction::updateOrCreate(
            ['country_code' => 'GB', 'state_code' => 'SCT', 'city' => null],
            [
                'name' => 'Scotland',
                'income_tax_rate' => 21.00, // Intermediate rate
                'social_security_rate' => 12.00,
                'vat_rate' => 20.00,
                'vat_reverse_charge' => false,
                'withholding_rate' => 0,
                'tax_brackets' => [
                    ['threshold' => 12570, 'rate' => 0], // Personal allowance
                    ['threshold' => 14876, 'rate' => 19], // Starter rate
                    ['threshold' => 26561, 'rate' => 20], // Basic rate
                    ['threshold' => 43662, 'rate' => 21], // Intermediate rate
                    ['threshold' => 75000, 'rate' => 42], // Higher rate
                    ['threshold' => 125140, 'rate' => 45], // Advanced rate
                    ['threshold' => PHP_INT_MAX, 'rate' => 48], // Top rate
                ],
                'tax_free_threshold' => 12570,
                'requires_w9' => false,
                'requires_w8ben' => false,
                'tax_id_format' => '/^[A-Z]{2}\d{6}[A-Z]$/',
                'tax_id_name' => 'National Insurance Number',
                'currency_code' => 'GBP',
                'is_active' => true,
            ]
        );
    }

    /**
     * Seed EU country tax jurisdictions.
     */
    protected function seedEUJurisdictions(): void
    {
        $euCountries = [
            ['code' => 'DE', 'name' => 'Germany', 'income' => 42.00, 'ss' => 20.43, 'vat' => 19.00, 'threshold' => 11604, 'currency' => 'EUR', 'tax_id_name' => 'Steuer-ID'],
            ['code' => 'FR', 'name' => 'France', 'income' => 30.00, 'ss' => 22.00, 'vat' => 20.00, 'threshold' => 10777, 'currency' => 'EUR', 'tax_id_name' => 'NIF'],
            ['code' => 'IT', 'name' => 'Italy', 'income' => 43.00, 'ss' => 9.19, 'vat' => 22.00, 'threshold' => 8500, 'currency' => 'EUR', 'tax_id_name' => 'Codice Fiscale'],
            ['code' => 'ES', 'name' => 'Spain', 'income' => 45.00, 'ss' => 6.35, 'vat' => 21.00, 'threshold' => 15000, 'currency' => 'EUR', 'tax_id_name' => 'NIF/NIE'],
            ['code' => 'NL', 'name' => 'Netherlands', 'income' => 49.50, 'ss' => 27.65, 'vat' => 21.00, 'threshold' => 0, 'currency' => 'EUR', 'tax_id_name' => 'BSN'],
            ['code' => 'BE', 'name' => 'Belgium', 'income' => 50.00, 'ss' => 13.07, 'vat' => 21.00, 'threshold' => 9270, 'currency' => 'EUR', 'tax_id_name' => 'Rijksregisternummer'],
            ['code' => 'AT', 'name' => 'Austria', 'income' => 50.00, 'ss' => 18.12, 'vat' => 20.00, 'threshold' => 12816, 'currency' => 'EUR', 'tax_id_name' => 'Steuernummer'],
            ['code' => 'PT', 'name' => 'Portugal', 'income' => 48.00, 'ss' => 11.00, 'vat' => 23.00, 'threshold' => 7703, 'currency' => 'EUR', 'tax_id_name' => 'NIF'],
            ['code' => 'IE', 'name' => 'Ireland', 'income' => 40.00, 'ss' => 4.00, 'vat' => 23.00, 'threshold' => 18000, 'currency' => 'EUR', 'tax_id_name' => 'PPS Number'],
            ['code' => 'GR', 'name' => 'Greece', 'income' => 44.00, 'ss' => 14.12, 'vat' => 24.00, 'threshold' => 10000, 'currency' => 'EUR', 'tax_id_name' => 'AFM'],
            ['code' => 'PL', 'name' => 'Poland', 'income' => 32.00, 'ss' => 13.71, 'vat' => 23.00, 'threshold' => 30000, 'currency' => 'PLN', 'tax_id_name' => 'PESEL/NIP'],
            ['code' => 'SE', 'name' => 'Sweden', 'income' => 52.00, 'ss' => 7.00, 'vat' => 25.00, 'threshold' => 22208, 'currency' => 'SEK', 'tax_id_name' => 'Personnummer'],
            ['code' => 'DK', 'name' => 'Denmark', 'income' => 52.07, 'ss' => 8.00, 'vat' => 25.00, 'threshold' => 46700, 'currency' => 'DKK', 'tax_id_name' => 'CPR-nummer'],
            ['code' => 'FI', 'name' => 'Finland', 'income' => 44.00, 'ss' => 7.15, 'vat' => 24.00, 'threshold' => 0, 'currency' => 'EUR', 'tax_id_name' => 'Henkilotunnus'],
            ['code' => 'CZ', 'name' => 'Czech Republic', 'income' => 23.00, 'ss' => 11.00, 'vat' => 21.00, 'threshold' => 0, 'currency' => 'CZK', 'tax_id_name' => 'Rodne cislo'],
            ['code' => 'RO', 'name' => 'Romania', 'income' => 10.00, 'ss' => 25.00, 'vat' => 19.00, 'threshold' => 0, 'currency' => 'RON', 'tax_id_name' => 'CNP'],
            ['code' => 'HU', 'name' => 'Hungary', 'income' => 15.00, 'ss' => 18.50, 'vat' => 27.00, 'threshold' => 0, 'currency' => 'HUF', 'tax_id_name' => 'Adoazonosito jel'],
            ['code' => 'SK', 'name' => 'Slovakia', 'income' => 25.00, 'ss' => 13.40, 'vat' => 20.00, 'threshold' => 0, 'currency' => 'EUR', 'tax_id_name' => 'Rodne cislo'],
            ['code' => 'BG', 'name' => 'Bulgaria', 'income' => 10.00, 'ss' => 13.78, 'vat' => 20.00, 'threshold' => 0, 'currency' => 'BGN', 'tax_id_name' => 'EGN'],
            ['code' => 'HR', 'name' => 'Croatia', 'income' => 30.00, 'ss' => 16.50, 'vat' => 25.00, 'threshold' => 5320, 'currency' => 'EUR', 'tax_id_name' => 'OIB'],
            ['code' => 'LU', 'name' => 'Luxembourg', 'income' => 42.00, 'ss' => 12.45, 'vat' => 17.00, 'threshold' => 11265, 'currency' => 'EUR', 'tax_id_name' => 'ID nationale'],
            ['code' => 'MT', 'name' => 'Malta', 'income' => 35.00, 'ss' => 10.00, 'vat' => 18.00, 'threshold' => 9100, 'currency' => 'EUR', 'tax_id_name' => 'ID Number'],
            ['code' => 'CY', 'name' => 'Cyprus', 'income' => 35.00, 'ss' => 8.30, 'vat' => 19.00, 'threshold' => 19500, 'currency' => 'EUR', 'tax_id_name' => 'TIC'],
            ['code' => 'EE', 'name' => 'Estonia', 'income' => 20.00, 'ss' => 1.60, 'vat' => 22.00, 'threshold' => 0, 'currency' => 'EUR', 'tax_id_name' => 'Isikukood'],
            ['code' => 'LV', 'name' => 'Latvia', 'income' => 31.00, 'ss' => 10.50, 'vat' => 21.00, 'threshold' => 0, 'currency' => 'EUR', 'tax_id_name' => 'Personas kods'],
            ['code' => 'LT', 'name' => 'Lithuania', 'income' => 32.00, 'ss' => 19.50, 'vat' => 21.00, 'threshold' => 0, 'currency' => 'EUR', 'tax_id_name' => 'Asmens kodas'],
            ['code' => 'SI', 'name' => 'Slovenia', 'income' => 50.00, 'ss' => 22.10, 'vat' => 22.00, 'threshold' => 0, 'currency' => 'EUR', 'tax_id_name' => 'EMSO'],
        ];

        foreach ($euCountries as $country) {
            TaxJurisdiction::updateOrCreate(
                ['country_code' => $country['code'], 'state_code' => null, 'city' => null],
                [
                    'name' => $country['name'],
                    'income_tax_rate' => $country['income'],
                    'social_security_rate' => $country['ss'],
                    'vat_rate' => $country['vat'],
                    'vat_reverse_charge' => true, // EU B2B reverse charge
                    'withholding_rate' => 0,
                    'tax_free_threshold' => $country['threshold'],
                    'requires_w9' => false,
                    'requires_w8ben' => false,
                    'tax_id_name' => $country['tax_id_name'],
                    'currency_code' => $country['currency'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Seed Australian tax jurisdictions.
     */
    protected function seedAustraliaJurisdictions(): void
    {
        TaxJurisdiction::updateOrCreate(
            ['country_code' => 'AU', 'state_code' => null, 'city' => null],
            [
                'name' => 'Australia',
                'income_tax_rate' => 32.50, // Default marginal rate
                'social_security_rate' => 0, // Superannuation is employer-paid
                'vat_rate' => 10.00, // GST
                'vat_reverse_charge' => false,
                'withholding_rate' => 0,
                'tax_brackets' => [
                    ['threshold' => 18200, 'rate' => 0],
                    ['threshold' => 45000, 'rate' => 19],
                    ['threshold' => 120000, 'rate' => 32.5],
                    ['threshold' => 180000, 'rate' => 37],
                    ['threshold' => PHP_INT_MAX, 'rate' => 45],
                ],
                'tax_free_threshold' => 18200, // 2024 tax-free threshold
                'requires_w9' => false,
                'requires_w8ben' => false,
                'tax_id_format' => '/^\d{9}$|^\d{11}$/',
                'tax_id_name' => 'TFN/ABN',
                'currency_code' => 'AUD',
                'is_active' => true,
            ]
        );

        // Australian states (for payroll tax purposes)
        $auStates = [
            ['code' => 'NSW', 'name' => 'New South Wales'],
            ['code' => 'VIC', 'name' => 'Victoria'],
            ['code' => 'QLD', 'name' => 'Queensland'],
            ['code' => 'WA', 'name' => 'Western Australia'],
            ['code' => 'SA', 'name' => 'South Australia'],
            ['code' => 'TAS', 'name' => 'Tasmania'],
            ['code' => 'ACT', 'name' => 'Australian Capital Territory'],
            ['code' => 'NT', 'name' => 'Northern Territory'],
        ];

        foreach ($auStates as $state) {
            TaxJurisdiction::updateOrCreate(
                ['country_code' => 'AU', 'state_code' => $state['code'], 'city' => null],
                [
                    'name' => $state['name'],
                    'income_tax_rate' => 0, // No state income tax
                    'social_security_rate' => 0,
                    'vat_rate' => 10.00,
                    'vat_reverse_charge' => false,
                    'withholding_rate' => 0,
                    'tax_free_threshold' => 18200,
                    'requires_w9' => false,
                    'requires_w8ben' => false,
                    'tax_id_format' => '/^\d{9}$|^\d{11}$/',
                    'tax_id_name' => 'TFN/ABN',
                    'currency_code' => 'AUD',
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Seed other major market jurisdictions.
     */
    protected function seedOtherJurisdictions(): void
    {
        $otherCountries = [
            // Canada
            ['code' => 'CA', 'name' => 'Canada', 'income' => 33.00, 'ss' => 5.95, 'vat' => 5.00, 'threshold' => 15705, 'currency' => 'CAD', 'tax_id_name' => 'SIN'],
            // Switzerland
            ['code' => 'CH', 'name' => 'Switzerland', 'income' => 11.50, 'ss' => 6.40, 'vat' => 8.10, 'threshold' => 0, 'currency' => 'CHF', 'tax_id_name' => 'AHV-Nr'],
            // Norway
            ['code' => 'NO', 'name' => 'Norway', 'income' => 22.00, 'ss' => 7.80, 'vat' => 25.00, 'threshold' => 0, 'currency' => 'NOK', 'tax_id_name' => 'Fodselsnummer'],
            // New Zealand
            ['code' => 'NZ', 'name' => 'New Zealand', 'income' => 33.00, 'ss' => 0, 'vat' => 15.00, 'threshold' => 14000, 'currency' => 'NZD', 'tax_id_name' => 'IRD Number'],
            // Singapore
            ['code' => 'SG', 'name' => 'Singapore', 'income' => 22.00, 'ss' => 20.00, 'vat' => 9.00, 'threshold' => 20000, 'currency' => 'SGD', 'tax_id_name' => 'NRIC/FIN'],
            // Hong Kong
            ['code' => 'HK', 'name' => 'Hong Kong', 'income' => 17.00, 'ss' => 5.00, 'vat' => 0, 'threshold' => 132000, 'currency' => 'HKD', 'tax_id_name' => 'HKID'],
            // Japan
            ['code' => 'JP', 'name' => 'Japan', 'income' => 45.00, 'ss' => 14.92, 'vat' => 10.00, 'threshold' => 0, 'currency' => 'JPY', 'tax_id_name' => 'My Number'],
            // South Korea
            ['code' => 'KR', 'name' => 'South Korea', 'income' => 45.00, 'ss' => 9.32, 'vat' => 10.00, 'threshold' => 0, 'currency' => 'KRW', 'tax_id_name' => 'RRN'],
            // India
            ['code' => 'IN', 'name' => 'India', 'income' => 30.00, 'ss' => 12.00, 'vat' => 18.00, 'threshold' => 300000, 'currency' => 'INR', 'tax_id_name' => 'PAN'],
            // Brazil
            ['code' => 'BR', 'name' => 'Brazil', 'income' => 27.50, 'ss' => 11.00, 'vat' => 0, 'threshold' => 0, 'currency' => 'BRL', 'tax_id_name' => 'CPF'],
            // Mexico
            ['code' => 'MX', 'name' => 'Mexico', 'income' => 35.00, 'ss' => 2.375, 'vat' => 16.00, 'threshold' => 0, 'currency' => 'MXN', 'tax_id_name' => 'RFC'],
            // South Africa
            ['code' => 'ZA', 'name' => 'South Africa', 'income' => 45.00, 'ss' => 1.00, 'vat' => 15.00, 'threshold' => 95750, 'currency' => 'ZAR', 'tax_id_name' => 'Tax Number'],
            // United Arab Emirates
            ['code' => 'AE', 'name' => 'United Arab Emirates', 'income' => 0, 'ss' => 0, 'vat' => 5.00, 'threshold' => 0, 'currency' => 'AED', 'tax_id_name' => 'TRN'],
            // Israel
            ['code' => 'IL', 'name' => 'Israel', 'income' => 50.00, 'ss' => 12.00, 'vat' => 17.00, 'threshold' => 0, 'currency' => 'ILS', 'tax_id_name' => 'Teudat Zehut'],
            // Nigeria
            ['code' => 'NG', 'name' => 'Nigeria', 'income' => 24.00, 'ss' => 7.50, 'vat' => 7.50, 'threshold' => 0, 'currency' => 'NGN', 'tax_id_name' => 'TIN'],
            // Kenya
            ['code' => 'KE', 'name' => 'Kenya', 'income' => 30.00, 'ss' => 6.00, 'vat' => 16.00, 'threshold' => 24000, 'currency' => 'KES', 'tax_id_name' => 'KRA PIN'],
            // Ghana
            ['code' => 'GH', 'name' => 'Ghana', 'income' => 30.00, 'ss' => 5.50, 'vat' => 15.00, 'threshold' => 0, 'currency' => 'GHS', 'tax_id_name' => 'TIN'],
            // Philippines
            ['code' => 'PH', 'name' => 'Philippines', 'income' => 35.00, 'ss' => 4.50, 'vat' => 12.00, 'threshold' => 250000, 'currency' => 'PHP', 'tax_id_name' => 'TIN'],
            // Indonesia
            ['code' => 'ID', 'name' => 'Indonesia', 'income' => 35.00, 'ss' => 4.00, 'vat' => 11.00, 'threshold' => 54000000, 'currency' => 'IDR', 'tax_id_name' => 'NPWP'],
            // Thailand
            ['code' => 'TH', 'name' => 'Thailand', 'income' => 35.00, 'ss' => 5.00, 'vat' => 7.00, 'threshold' => 150000, 'currency' => 'THB', 'tax_id_name' => 'Tax ID'],
            // Vietnam
            ['code' => 'VN', 'name' => 'Vietnam', 'income' => 35.00, 'ss' => 10.50, 'vat' => 10.00, 'threshold' => 0, 'currency' => 'VND', 'tax_id_name' => 'MST'],
            // Argentina
            ['code' => 'AR', 'name' => 'Argentina', 'income' => 35.00, 'ss' => 17.00, 'vat' => 21.00, 'threshold' => 0, 'currency' => 'ARS', 'tax_id_name' => 'CUIT'],
            // Chile
            ['code' => 'CL', 'name' => 'Chile', 'income' => 40.00, 'ss' => 20.00, 'vat' => 19.00, 'threshold' => 0, 'currency' => 'CLP', 'tax_id_name' => 'RUT'],
            // Colombia
            ['code' => 'CO', 'name' => 'Colombia', 'income' => 39.00, 'ss' => 4.00, 'vat' => 19.00, 'threshold' => 0, 'currency' => 'COP', 'tax_id_name' => 'NIT'],
        ];

        foreach ($otherCountries as $country) {
            TaxJurisdiction::updateOrCreate(
                ['country_code' => $country['code'], 'state_code' => null, 'city' => null],
                [
                    'name' => $country['name'],
                    'income_tax_rate' => $country['income'],
                    'social_security_rate' => $country['ss'],
                    'vat_rate' => $country['vat'],
                    'vat_reverse_charge' => false,
                    'withholding_rate' => 0,
                    'tax_free_threshold' => $country['threshold'],
                    'requires_w9' => false,
                    'requires_w8ben' => $country['code'] !== 'US',
                    'tax_id_name' => $country['tax_id_name'],
                    'currency_code' => $country['currency'],
                    'is_active' => true,
                ]
            );
        }
    }
}
