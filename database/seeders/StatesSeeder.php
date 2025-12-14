<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\States;
use App\Models\Countries;

class StatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds states/provinces for US (50), Canada (13), UK (4), Australia (8).
     *
     * @return void
     */
    public function run()
    {
        // Get country IDs
        $us = Countries::where('country_code', 'US')->first();
        $ca = Countries::where('country_code', 'CA')->first();
        $gb = Countries::where('country_code', 'GB')->first();
        $au = Countries::where('country_code', 'AU')->first();

        if (!$us || !$ca || !$gb || !$au) {
            $this->command->error('Countries must be seeded first! Run: php artisan db:seed --class=CountriesSeeder');
            return;
        }

        $allStates = [];

        // US States (50 states + DC)
        $usStates = [
            ['countries_id' => $us->id, 'name' => 'Alabama', 'code' => 'AL'],
            ['countries_id' => $us->id, 'name' => 'Alaska', 'code' => 'AK'],
            ['countries_id' => $us->id, 'name' => 'Arizona', 'code' => 'AZ'],
            ['countries_id' => $us->id, 'name' => 'Arkansas', 'code' => 'AR'],
            ['countries_id' => $us->id, 'name' => 'California', 'code' => 'CA'],
            ['countries_id' => $us->id, 'name' => 'Colorado', 'code' => 'CO'],
            ['countries_id' => $us->id, 'name' => 'Connecticut', 'code' => 'CT'],
            ['countries_id' => $us->id, 'name' => 'Delaware', 'code' => 'DE'],
            ['countries_id' => $us->id, 'name' => 'District of Columbia', 'code' => 'DC'],
            ['countries_id' => $us->id, 'name' => 'Florida', 'code' => 'FL'],
            ['countries_id' => $us->id, 'name' => 'Georgia', 'code' => 'GA'],
            ['countries_id' => $us->id, 'name' => 'Hawaii', 'code' => 'HI'],
            ['countries_id' => $us->id, 'name' => 'Idaho', 'code' => 'ID'],
            ['countries_id' => $us->id, 'name' => 'Illinois', 'code' => 'IL'],
            ['countries_id' => $us->id, 'name' => 'Indiana', 'code' => 'IN'],
            ['countries_id' => $us->id, 'name' => 'Iowa', 'code' => 'IA'],
            ['countries_id' => $us->id, 'name' => 'Kansas', 'code' => 'KS'],
            ['countries_id' => $us->id, 'name' => 'Kentucky', 'code' => 'KY'],
            ['countries_id' => $us->id, 'name' => 'Louisiana', 'code' => 'LA'],
            ['countries_id' => $us->id, 'name' => 'Maine', 'code' => 'ME'],
            ['countries_id' => $us->id, 'name' => 'Maryland', 'code' => 'MD'],
            ['countries_id' => $us->id, 'name' => 'Massachusetts', 'code' => 'MA'],
            ['countries_id' => $us->id, 'name' => 'Michigan', 'code' => 'MI'],
            ['countries_id' => $us->id, 'name' => 'Minnesota', 'code' => 'MN'],
            ['countries_id' => $us->id, 'name' => 'Mississippi', 'code' => 'MS'],
            ['countries_id' => $us->id, 'name' => 'Missouri', 'code' => 'MO'],
            ['countries_id' => $us->id, 'name' => 'Montana', 'code' => 'MT'],
            ['countries_id' => $us->id, 'name' => 'Nebraska', 'code' => 'NE'],
            ['countries_id' => $us->id, 'name' => 'Nevada', 'code' => 'NV'],
            ['countries_id' => $us->id, 'name' => 'New Hampshire', 'code' => 'NH'],
            ['countries_id' => $us->id, 'name' => 'New Jersey', 'code' => 'NJ'],
            ['countries_id' => $us->id, 'name' => 'New Mexico', 'code' => 'NM'],
            ['countries_id' => $us->id, 'name' => 'New York', 'code' => 'NY'],
            ['countries_id' => $us->id, 'name' => 'North Carolina', 'code' => 'NC'],
            ['countries_id' => $us->id, 'name' => 'North Dakota', 'code' => 'ND'],
            ['countries_id' => $us->id, 'name' => 'Ohio', 'code' => 'OH'],
            ['countries_id' => $us->id, 'name' => 'Oklahoma', 'code' => 'OK'],
            ['countries_id' => $us->id, 'name' => 'Oregon', 'code' => 'OR'],
            ['countries_id' => $us->id, 'name' => 'Pennsylvania', 'code' => 'PA'],
            ['countries_id' => $us->id, 'name' => 'Rhode Island', 'code' => 'RI'],
            ['countries_id' => $us->id, 'name' => 'South Carolina', 'code' => 'SC'],
            ['countries_id' => $us->id, 'name' => 'South Dakota', 'code' => 'SD'],
            ['countries_id' => $us->id, 'name' => 'Tennessee', 'code' => 'TN'],
            ['countries_id' => $us->id, 'name' => 'Texas', 'code' => 'TX'],
            ['countries_id' => $us->id, 'name' => 'Utah', 'code' => 'UT'],
            ['countries_id' => $us->id, 'name' => 'Vermont', 'code' => 'VT'],
            ['countries_id' => $us->id, 'name' => 'Virginia', 'code' => 'VA'],
            ['countries_id' => $us->id, 'name' => 'Washington', 'code' => 'WA'],
            ['countries_id' => $us->id, 'name' => 'West Virginia', 'code' => 'WV'],
            ['countries_id' => $us->id, 'name' => 'Wisconsin', 'code' => 'WI'],
            ['countries_id' => $us->id, 'name' => 'Wyoming', 'code' => 'WY'],
        ];

        // Canadian Provinces and Territories (13 total)
        $caProvinces = [
            ['countries_id' => $ca->id, 'name' => 'Alberta', 'code' => 'AB'],
            ['countries_id' => $ca->id, 'name' => 'British Columbia', 'code' => 'BC'],
            ['countries_id' => $ca->id, 'name' => 'Manitoba', 'code' => 'MB'],
            ['countries_id' => $ca->id, 'name' => 'New Brunswick', 'code' => 'NB'],
            ['countries_id' => $ca->id, 'name' => 'Newfoundland and Labrador', 'code' => 'NL'],
            ['countries_id' => $ca->id, 'name' => 'Northwest Territories', 'code' => 'NT'],
            ['countries_id' => $ca->id, 'name' => 'Nova Scotia', 'code' => 'NS'],
            ['countries_id' => $ca->id, 'name' => 'Nunavut', 'code' => 'NU'],
            ['countries_id' => $ca->id, 'name' => 'Ontario', 'code' => 'ON'],
            ['countries_id' => $ca->id, 'name' => 'Prince Edward Island', 'code' => 'PE'],
            ['countries_id' => $ca->id, 'name' => 'Quebec', 'code' => 'QC'],
            ['countries_id' => $ca->id, 'name' => 'Saskatchewan', 'code' => 'SK'],
            ['countries_id' => $ca->id, 'name' => 'Yukon', 'code' => 'YT'],
        ];

        // UK Constituent Countries and Crown Dependencies
        $gbRegions = [
            ['countries_id' => $gb->id, 'name' => 'England', 'code' => 'ENG'],
            ['countries_id' => $gb->id, 'name' => 'Scotland', 'code' => 'SCT'],
            ['countries_id' => $gb->id, 'name' => 'Wales', 'code' => 'WLS'],
            ['countries_id' => $gb->id, 'name' => 'Northern Ireland', 'code' => 'NIR'],
        ];

        // Australian States and Territories (8 total)
        $auStates = [
            ['countries_id' => $au->id, 'name' => 'New South Wales', 'code' => 'NSW'],
            ['countries_id' => $au->id, 'name' => 'Victoria', 'code' => 'VIC'],
            ['countries_id' => $au->id, 'name' => 'Queensland', 'code' => 'QLD'],
            ['countries_id' => $au->id, 'name' => 'Western Australia', 'code' => 'WA'],
            ['countries_id' => $au->id, 'name' => 'South Australia', 'code' => 'SA'],
            ['countries_id' => $au->id, 'name' => 'Tasmania', 'code' => 'TAS'],
            ['countries_id' => $au->id, 'name' => 'Australian Capital Territory', 'code' => 'ACT'],
            ['countries_id' => $au->id, 'name' => 'Northern Territory', 'code' => 'NT'],
        ];

        $allStates = array_merge($usStates, $caProvinces, $gbRegions, $auStates);

        // Add is_active flag to all states
        foreach ($allStates as &$state) {
            $state['is_active'] = true;
        }

        // Use updateOrCreate to avoid duplicates
        foreach ($allStates as $state) {
            States::updateOrCreate(
                ['countries_id' => $state['countries_id'], 'code' => $state['code']],
                $state
            );
        }

        $this->command->info('States/Provinces seeded: ' . count($allStates));
        $this->command->info('  - US States: ' . count($usStates));
        $this->command->info('  - Canadian Provinces: ' . count($caProvinces));
        $this->command->info('  - UK Regions: ' . count($gbRegions));
        $this->command->info('  - Australian States: ' . count($auStates));
    }
}
