<?php

namespace Database\Seeders;

use App\Models\DataRegion;
use Illuminate\Database\Seeder;

/**
 * GLO-010: Data Residency System - Data Region Seeder
 *
 * Seeds the default data regions with country mappings and compliance frameworks.
 */
class DataRegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = [
            [
                'code' => 'eu',
                'name' => 'European Union',
                'countries' => [
                    'AT', // Austria
                    'BE', // Belgium
                    'BG', // Bulgaria
                    'HR', // Croatia
                    'CY', // Cyprus
                    'CZ', // Czech Republic
                    'DK', // Denmark
                    'EE', // Estonia
                    'FI', // Finland
                    'FR', // France
                    'DE', // Germany
                    'GR', // Greece
                    'HU', // Hungary
                    'IE', // Ireland
                    'IT', // Italy
                    'LV', // Latvia
                    'LT', // Lithuania
                    'LU', // Luxembourg
                    'MT', // Malta
                    'NL', // Netherlands
                    'PL', // Poland
                    'PT', // Portugal
                    'RO', // Romania
                    'SK', // Slovakia
                    'SI', // Slovenia
                    'ES', // Spain
                    'SE', // Sweden
                    // EEA countries
                    'IS', // Iceland
                    'LI', // Liechtenstein
                    'NO', // Norway
                    // EU candidate countries (often included for simplicity)
                    'CH', // Switzerland (GDPR-adequate)
                ],
                'primary_storage' => 's3-eu',
                'backup_storage' => 's3-eu-backup',
                'compliance_frameworks' => ['GDPR'],
                'is_active' => true,
            ],
            [
                'code' => 'uk',
                'name' => 'United Kingdom',
                'countries' => [
                    'GB', // United Kingdom
                    'GG', // Guernsey
                    'JE', // Jersey
                    'IM', // Isle of Man
                    'GI', // Gibraltar
                ],
                'primary_storage' => 's3-uk',
                'backup_storage' => 's3-uk-backup',
                'compliance_frameworks' => ['UK-GDPR'],
                'is_active' => true,
            ],
            [
                'code' => 'us',
                'name' => 'United States',
                'countries' => [
                    'US', // United States
                    'PR', // Puerto Rico
                    'VI', // US Virgin Islands
                    'GU', // Guam
                    'AS', // American Samoa
                    // North American partners
                    'CA', // Canada (PIPEDA compliant)
                    'MX', // Mexico
                ],
                'primary_storage' => 's3-us',
                'backup_storage' => 's3-us-backup',
                'compliance_frameworks' => ['CCPA'],
                'is_active' => true,
            ],
            [
                'code' => 'apac',
                'name' => 'Asia Pacific',
                'countries' => [
                    'AU', // Australia
                    'NZ', // New Zealand
                    'SG', // Singapore
                    'JP', // Japan
                    'KR', // South Korea
                    'HK', // Hong Kong
                    'TW', // Taiwan
                    'MY', // Malaysia
                    'TH', // Thailand
                    'PH', // Philippines
                    'ID', // Indonesia
                    'VN', // Vietnam
                    'IN', // India
                    'BD', // Bangladesh
                    'PK', // Pakistan
                    'LK', // Sri Lanka
                    'NP', // Nepal
                ],
                'primary_storage' => 's3-apac',
                'backup_storage' => 's3-apac-backup',
                'compliance_frameworks' => ['APP', 'PDPA'],
                'is_active' => true,
            ],
            [
                'code' => 'latam',
                'name' => 'Latin America',
                'countries' => [
                    'BR', // Brazil
                    'AR', // Argentina
                    'CL', // Chile
                    'CO', // Colombia
                    'PE', // Peru
                    'VE', // Venezuela
                    'EC', // Ecuador
                    'BO', // Bolivia
                    'PY', // Paraguay
                    'UY', // Uruguay
                    'CR', // Costa Rica
                    'PA', // Panama
                    'DO', // Dominican Republic
                    'GT', // Guatemala
                    'HN', // Honduras
                    'SV', // El Salvador
                    'NI', // Nicaragua
                    'CU', // Cuba
                    'JM', // Jamaica
                    'TT', // Trinidad and Tobago
                ],
                'primary_storage' => 's3-latam',
                'backup_storage' => 's3-latam-backup',
                'compliance_frameworks' => ['LGPD'],
                'is_active' => true,
            ],
            [
                'code' => 'mea',
                'name' => 'Middle East & Africa',
                'countries' => [
                    // Middle East
                    'AE', // United Arab Emirates
                    'SA', // Saudi Arabia
                    'QA', // Qatar
                    'KW', // Kuwait
                    'BH', // Bahrain
                    'OM', // Oman
                    'IL', // Israel
                    'JO', // Jordan
                    'LB', // Lebanon
                    'EG', // Egypt
                    'TR', // Turkey
                    // Africa
                    'ZA', // South Africa
                    'NG', // Nigeria
                    'KE', // Kenya
                    'GH', // Ghana
                    'TZ', // Tanzania
                    'UG', // Uganda
                    'RW', // Rwanda
                    'ET', // Ethiopia
                    'MA', // Morocco
                    'TN', // Tunisia
                    'DZ', // Algeria
                    'SN', // Senegal
                    'CI', // Ivory Coast
                    'CM', // Cameroon
                    'MU', // Mauritius
                ],
                'primary_storage' => 's3-mea',
                'backup_storage' => 's3-mea-backup',
                'compliance_frameworks' => ['POPIA'],
                'is_active' => true,
            ],
        ];

        foreach ($regions as $regionData) {
            DataRegion::updateOrCreate(
                ['code' => $regionData['code']],
                $regionData
            );
        }

        $this->command->info('Data regions seeded successfully.');
        $this->command->table(
            ['Code', 'Name', 'Countries', 'Storage', 'Frameworks'],
            collect($regions)->map(function ($region) {
                return [
                    $region['code'],
                    $region['name'],
                    count($region['countries']),
                    $region['primary_storage'],
                    implode(', ', $region['compliance_frameworks']),
                ];
            })
        );
    }
}
