<?php

namespace Database\Seeders;

use App\Models\SystemSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ADM-003: System Settings Seeder
 *
 * Seeds default platform configuration settings into the system_settings table.
 * This seeder uses upsert to safely update existing settings without losing modifications.
 */
class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding system settings...');

        $defaults = SystemSettings::getDefaults();
        $created = 0;
        $updated = 0;

        foreach ($defaults as $key => $data) {
            $existing = SystemSettings::where('key', $key)->first();

            if ($existing) {
                // Update description and meta fields, but preserve the value
                $existing->update([
                    'category' => $data['category'],
                    'description' => $data['description'],
                    'data_type' => $data['data_type'],
                    'is_public' => $data['is_public'],
                ]);
                $updated++;
            } else {
                // Create new setting with default value
                SystemSettings::create([
                    'key' => $key,
                    'value' => $data['value'],
                    'category' => $data['category'],
                    'description' => $data['description'],
                    'data_type' => $data['data_type'],
                    'is_public' => $data['is_public'],
                ]);
                $created++;
            }
        }

        // Clear the settings cache
        SystemSettings::clearAllCache();

        $this->command->info("Created {$created} new settings, updated {$updated} existing settings.");
        $this->command->info('System settings seeded successfully!');
    }

    /**
     * Fresh seed - removes all existing settings and seeds defaults.
     * Use with caution in production!
     */
    public function runFresh(): void
    {
        $this->command->warn('Clearing all existing system settings...');

        DB::table('system_setting_audits')->truncate();
        DB::table('system_settings')->truncate();

        $defaults = SystemSettings::getDefaults();

        foreach ($defaults as $key => $data) {
            SystemSettings::create([
                'key' => $key,
                'value' => $data['value'],
                'category' => $data['category'],
                'description' => $data['description'],
                'data_type' => $data['data_type'],
                'is_public' => $data['is_public'],
            ]);
        }

        // Clear the settings cache
        SystemSettings::clearAllCache();

        $this->command->info(count($defaults) . ' settings created fresh.');
    }
}
