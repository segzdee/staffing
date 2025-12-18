<?php

namespace Database\Seeders;

use App\Models\FraudRule;
use Illuminate\Database\Seeder;

/**
 * FIN-015: Fraud Rules Seeder
 *
 * Seeds the default fraud detection rules.
 */
class FraudRulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = FraudRule::getDefaultRules();

        foreach ($rules as $ruleData) {
            FraudRule::updateOrCreate(
                ['code' => $ruleData['code']],
                $ruleData
            );
        }

        $this->command->info('Default fraud rules seeded successfully.');
    }
}
