<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Reference data (order matters - countries first, then states that depend on them)
        $this->call([
            CountriesSeeder::class,
            StatesSeeder::class,
            TaxRatesSeeder::class,
            SkillsSeeder::class,
            CertificationsSeeder::class,
        ]);

        // Application settings
        $this->call([
            ChatSettingSeeder::class,
            SystemSettingsSeeder::class,
        ]);

        // Onboarding configuration
        $this->call(OnboardingStepSeeder::class);

        // Business configuration
        $this->call([
            IndustriesSeeder::class,
            BusinessTypesSeeder::class,
        ]);

        // Development accounts - only seed in non-production environments
        if (app()->environment('local', 'development', 'testing')) {
            $this->call([
                DevCredentialsSeeder::class,
            ]);
        }
    }
}
