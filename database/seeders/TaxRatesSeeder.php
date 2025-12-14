<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TaxRates;
use App\Models\Countries;

class TaxRatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds US state sales tax rates (2024 data).
     *
     * Note: Tax rates are state-level base rates. Local jurisdictions may add additional taxes.
     * These should be updated periodically as rates change.
     *
     * @return void
     */
    public function run()
    {
        $us = Countries::where('country_code', 'US')->first();

        if (!$us) {
            $this->command->error('Countries must be seeded first! Run: php artisan db:seed --class=CountriesSeeder');
            return;
        }

        // US State Sales Tax Rates (2024)
        // Source: Tax Foundation and state government websites
        $taxRates = [
            // States with no state sales tax
            ['name' => 'Alaska Sales Tax', 'country' => 'US', 'iso_state' => 'AK', 'percentage' => 0.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Delaware Sales Tax', 'country' => 'US', 'iso_state' => 'DE', 'percentage' => 0.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Montana Sales Tax', 'country' => 'US', 'iso_state' => 'MT', 'percentage' => 0.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'New Hampshire Sales Tax', 'country' => 'US', 'iso_state' => 'NH', 'percentage' => 0.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Oregon Sales Tax', 'country' => 'US', 'iso_state' => 'OR', 'percentage' => 0.0000, 'type' => 'sales_tax', 'status' => '1'],

            // States with sales tax (alphabetical order)
            ['name' => 'Alabama Sales Tax', 'country' => 'US', 'iso_state' => 'AL', 'percentage' => 4.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Arizona Sales Tax', 'country' => 'US', 'iso_state' => 'AZ', 'percentage' => 5.6000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Arkansas Sales Tax', 'country' => 'US', 'iso_state' => 'AR', 'percentage' => 6.5000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'California Sales Tax', 'country' => 'US', 'iso_state' => 'CA', 'percentage' => 7.2500, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Colorado Sales Tax', 'country' => 'US', 'iso_state' => 'CO', 'percentage' => 2.9000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Connecticut Sales Tax', 'country' => 'US', 'iso_state' => 'CT', 'percentage' => 6.3500, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'DC Sales Tax', 'country' => 'US', 'iso_state' => 'DC', 'percentage' => 6.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Florida Sales Tax', 'country' => 'US', 'iso_state' => 'FL', 'percentage' => 6.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Georgia Sales Tax', 'country' => 'US', 'iso_state' => 'GA', 'percentage' => 4.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Hawaii Sales Tax', 'country' => 'US', 'iso_state' => 'HI', 'percentage' => 4.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Idaho Sales Tax', 'country' => 'US', 'iso_state' => 'ID', 'percentage' => 6.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Illinois Sales Tax', 'country' => 'US', 'iso_state' => 'IL', 'percentage' => 6.2500, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Indiana Sales Tax', 'country' => 'US', 'iso_state' => 'IN', 'percentage' => 7.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Iowa Sales Tax', 'country' => 'US', 'iso_state' => 'IA', 'percentage' => 6.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Kansas Sales Tax', 'country' => 'US', 'iso_state' => 'KS', 'percentage' => 6.5000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Kentucky Sales Tax', 'country' => 'US', 'iso_state' => 'KY', 'percentage' => 6.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Louisiana Sales Tax', 'country' => 'US', 'iso_state' => 'LA', 'percentage' => 4.4500, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Maine Sales Tax', 'country' => 'US', 'iso_state' => 'ME', 'percentage' => 5.5000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Maryland Sales Tax', 'country' => 'US', 'iso_state' => 'MD', 'percentage' => 6.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Massachusetts Sales Tax', 'country' => 'US', 'iso_state' => 'MA', 'percentage' => 6.2500, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Michigan Sales Tax', 'country' => 'US', 'iso_state' => 'MI', 'percentage' => 6.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Minnesota Sales Tax', 'country' => 'US', 'iso_state' => 'MN', 'percentage' => 6.8750, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Mississippi Sales Tax', 'country' => 'US', 'iso_state' => 'MS', 'percentage' => 7.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Missouri Sales Tax', 'country' => 'US', 'iso_state' => 'MO', 'percentage' => 4.2250, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Nebraska Sales Tax', 'country' => 'US', 'iso_state' => 'NE', 'percentage' => 5.5000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Nevada Sales Tax', 'country' => 'US', 'iso_state' => 'NV', 'percentage' => 6.8500, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'New Jersey Sales Tax', 'country' => 'US', 'iso_state' => 'NJ', 'percentage' => 6.6250, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'New Mexico Sales Tax', 'country' => 'US', 'iso_state' => 'NM', 'percentage' => 4.8750, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'New York Sales Tax', 'country' => 'US', 'iso_state' => 'NY', 'percentage' => 4.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'North Carolina Sales Tax', 'country' => 'US', 'iso_state' => 'NC', 'percentage' => 4.7500, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'North Dakota Sales Tax', 'country' => 'US', 'iso_state' => 'ND', 'percentage' => 5.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Ohio Sales Tax', 'country' => 'US', 'iso_state' => 'OH', 'percentage' => 5.7500, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Oklahoma Sales Tax', 'country' => 'US', 'iso_state' => 'OK', 'percentage' => 4.5000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Pennsylvania Sales Tax', 'country' => 'US', 'iso_state' => 'PA', 'percentage' => 6.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Rhode Island Sales Tax', 'country' => 'US', 'iso_state' => 'RI', 'percentage' => 7.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'South Carolina Sales Tax', 'country' => 'US', 'iso_state' => 'SC', 'percentage' => 6.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'South Dakota Sales Tax', 'country' => 'US', 'iso_state' => 'SD', 'percentage' => 4.2000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Tennessee Sales Tax', 'country' => 'US', 'iso_state' => 'TN', 'percentage' => 7.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Texas Sales Tax', 'country' => 'US', 'iso_state' => 'TX', 'percentage' => 6.2500, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Utah Sales Tax', 'country' => 'US', 'iso_state' => 'UT', 'percentage' => 6.1000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Vermont Sales Tax', 'country' => 'US', 'iso_state' => 'VT', 'percentage' => 6.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Virginia Sales Tax', 'country' => 'US', 'iso_state' => 'VA', 'percentage' => 5.3000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Washington Sales Tax', 'country' => 'US', 'iso_state' => 'WA', 'percentage' => 6.5000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'West Virginia Sales Tax', 'country' => 'US', 'iso_state' => 'WV', 'percentage' => 6.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Wisconsin Sales Tax', 'country' => 'US', 'iso_state' => 'WI', 'percentage' => 5.0000, 'type' => 'sales_tax', 'status' => '1'],
            ['name' => 'Wyoming Sales Tax', 'country' => 'US', 'iso_state' => 'WY', 'percentage' => 4.0000, 'type' => 'sales_tax', 'status' => '1'],
        ];

        // Use updateOrCreate to avoid duplicates
        foreach ($taxRates as $rate) {
            TaxRates::updateOrCreate(
                ['country' => $rate['country'], 'iso_state' => $rate['iso_state'], 'type' => $rate['type']],
                $rate
            );
        }

        $this->command->info('Tax rates seeded: ' . count($taxRates));
        $this->command->info('  - US States with no sales tax: 5 (AK, DE, MT, NH, OR)');
        $this->command->info('  - US States with sales tax: ' . (count($taxRates) - 5));
    }
}
