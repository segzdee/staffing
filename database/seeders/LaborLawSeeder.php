<?php

namespace Database\Seeders;

use App\Models\LaborLawRule;
use Illuminate\Database\Seeder;

/**
 * GLO-003: Labor Law Compliance - Labor Law Rules Seeder
 *
 * Seeds labor law rules for EU, UK, US (California), and Australia.
 */
class LaborLawSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = $this->getRules();

        foreach ($rules as $rule) {
            LaborLawRule::updateOrCreate(
                ['rule_code' => $rule['rule_code']],
                $rule
            );
        }

        $this->command->info('Seeded '.count($rules).' labor law rules.');
    }

    /**
     * Get all labor law rules for seeding.
     */
    protected function getRules(): array
    {
        return array_merge(
            $this->getEuRules(),
            $this->getUkRules(),
            $this->getUsCaliforniaRules(),
            $this->getAustraliaRules(),
            $this->getUsFederalRules()
        );
    }

    /**
     * EU Working Time Directive Rules
     */
    protected function getEuRules(): array
    {
        return [
            // Maximum Weekly Hours (with opt-out)
            [
                'jurisdiction' => 'EU',
                'rule_code' => 'WTD_WEEKLY_MAX',
                'name' => 'EU Weekly Working Hours Limit',
                'description' => 'Maximum 48 hours of work per week on average, including overtime. Calculated over a 17-week reference period. Workers may voluntarily opt out of this limit.',
                'rule_type' => 'working_time',
                'parameters' => [
                    'max_hours' => 48,
                    'period' => 'weekly',
                    'reference_period_weeks' => 17,
                    'requires_admin_approval' => false,
                ],
                'enforcement' => 'soft_warning',
                'is_active' => true,
                'allows_opt_out' => true,
                'opt_out_requirements' => 'Worker must provide written consent. Opt-out can be withdrawn with 7 days notice.',
                'legal_reference' => 'Working Time Directive 2003/88/EC, Article 6',
                'effective_from' => '1998-10-01',
            ],

            // Daily Rest Period
            [
                'jurisdiction' => 'EU',
                'rule_code' => 'REST_PERIOD_DAILY',
                'name' => 'EU Daily Rest Period',
                'description' => 'Workers are entitled to a minimum of 11 consecutive hours of rest in every 24-hour period.',
                'rule_type' => 'rest_period',
                'parameters' => [
                    'min_hours' => 11,
                    'period' => 'daily',
                ],
                'enforcement' => 'hard_block',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'Working Time Directive 2003/88/EC, Article 3',
                'effective_from' => '1998-10-01',
            ],

            // Weekly Rest Period
            [
                'jurisdiction' => 'EU',
                'rule_code' => 'REST_PERIOD_WEEKLY',
                'name' => 'EU Weekly Rest Period',
                'description' => 'Workers are entitled to an uninterrupted rest period of at least 24 hours in each 7-day period, in addition to the 11 hours daily rest.',
                'rule_type' => 'rest_period',
                'parameters' => [
                    'min_hours' => 24,
                    'period' => 'weekly',
                    'additional_to_daily' => true,
                ],
                'enforcement' => 'soft_warning',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'Working Time Directive 2003/88/EC, Article 5',
                'effective_from' => '1998-10-01',
            ],

            // Break after 6 hours
            [
                'jurisdiction' => 'EU',
                'rule_code' => 'BREAK_6_HOURS',
                'name' => 'EU Break Requirement (6 Hours)',
                'description' => 'Workers are entitled to a rest break if the working day is longer than 6 hours. Details determined by member states.',
                'rule_type' => 'break',
                'parameters' => [
                    'threshold_hours' => 6,
                    'break_minutes' => 20,
                    'paid' => false,
                ],
                'enforcement' => 'soft_warning',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'Working Time Directive 2003/88/EC, Article 4',
                'effective_from' => '1998-10-01',
            ],

            // Night Work Limit
            [
                'jurisdiction' => 'EU',
                'rule_code' => 'NIGHT_WORK_MAX',
                'name' => 'EU Night Work Hours Limit',
                'description' => 'Night workers shall not work more than an average of 8 hours in any 24-hour period.',
                'rule_type' => 'night_work',
                'parameters' => [
                    'max_hours_per_night' => 8,
                    'night_start_hour' => 22,
                    'night_end_hour' => 6,
                    'reference_period_days' => 17 * 7,
                ],
                'enforcement' => 'soft_warning',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'Working Time Directive 2003/88/EC, Article 8',
                'effective_from' => '1998-10-01',
            ],

            // Youth Workers - Night Work
            [
                'jurisdiction' => 'EU',
                'rule_code' => 'YOUTH_NIGHT_WORK',
                'name' => 'EU Youth Workers Night Work Restriction',
                'description' => 'Young workers (under 18) are prohibited from working during night time.',
                'rule_type' => 'age_restriction',
                'parameters' => [
                    'min_age_for_night_work' => 18,
                    'night_start_hour' => 22,
                    'night_end_hour' => 6,
                ],
                'enforcement' => 'hard_block',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'Young Workers Directive 94/33/EC, Article 9',
                'effective_from' => '1998-10-01',
            ],

            // Maximum Daily Hours
            [
                'jurisdiction' => 'EU',
                'rule_code' => 'DAILY_HOURS_MAX',
                'name' => 'EU Maximum Daily Working Hours',
                'description' => 'Maximum 13 hours of work in a 24-hour period (derived from 11-hour rest requirement).',
                'rule_type' => 'working_time',
                'parameters' => [
                    'max_hours' => 13,
                    'period' => 'daily',
                ],
                'enforcement' => 'hard_block',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'Working Time Directive 2003/88/EC, Article 3 (implied)',
                'effective_from' => '1998-10-01',
            ],
        ];
    }

    /**
     * UK Working Time Regulations (post-Brexit, largely mirrors EU WTD)
     */
    protected function getUkRules(): array
    {
        return [
            [
                'jurisdiction' => 'UK',
                'rule_code' => 'UK_WEEKLY_MAX',
                'name' => 'UK Weekly Working Hours Limit',
                'description' => 'Maximum 48 hours per week averaged over 17 weeks. Workers may opt out voluntarily.',
                'rule_type' => 'working_time',
                'parameters' => [
                    'max_hours' => 48,
                    'period' => 'weekly',
                    'reference_period_weeks' => 17,
                ],
                'enforcement' => 'soft_warning',
                'is_active' => true,
                'allows_opt_out' => true,
                'opt_out_requirements' => 'Written opt-out required. 7 days minimum notice to withdraw opt-out.',
                'legal_reference' => 'Working Time Regulations 1998, Regulation 4',
                'effective_from' => '1998-10-01',
            ],

            [
                'jurisdiction' => 'UK',
                'rule_code' => 'UK_REST_DAILY',
                'name' => 'UK Daily Rest Period',
                'description' => 'Minimum 11 consecutive hours rest in every 24-hour period.',
                'rule_type' => 'rest_period',
                'parameters' => [
                    'min_hours' => 11,
                    'period' => 'daily',
                ],
                'enforcement' => 'hard_block',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'Working Time Regulations 1998, Regulation 10',
                'effective_from' => '1998-10-01',
            ],

            [
                'jurisdiction' => 'UK',
                'rule_code' => 'UK_BREAK_6_HOURS',
                'name' => 'UK Break Requirement',
                'description' => 'Minimum 20-minute break when working more than 6 hours.',
                'rule_type' => 'break',
                'parameters' => [
                    'threshold_hours' => 6,
                    'break_minutes' => 20,
                    'paid' => false,
                ],
                'enforcement' => 'soft_warning',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'Working Time Regulations 1998, Regulation 12',
                'effective_from' => '1998-10-01',
            ],

            [
                'jurisdiction' => 'UK',
                'rule_code' => 'UK_YOUTH_HOURS',
                'name' => 'UK Young Workers Hours Limit',
                'description' => 'Young workers (under 18) cannot work more than 8 hours per day or 40 hours per week.',
                'rule_type' => 'age_restriction',
                'parameters' => [
                    'max_age' => 18,
                    'max_daily_hours' => 8,
                    'max_weekly_hours' => 40,
                ],
                'enforcement' => 'hard_block',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'Working Time Regulations 1998, Regulation 5A',
                'effective_from' => '1998-10-01',
            ],
        ];
    }

    /**
     * US California Labor Law Rules
     */
    protected function getUsCaliforniaRules(): array
    {
        return [
            [
                'jurisdiction' => 'US-CA',
                'rule_code' => 'CA_DAILY_OVERTIME',
                'name' => 'California Daily Overtime',
                'description' => 'Overtime (1.5x) required for work exceeding 8 hours in a day.',
                'rule_type' => 'overtime',
                'parameters' => [
                    'daily_threshold_hours' => 8,
                    'rate_multiplier' => 1.5,
                ],
                'enforcement' => 'log_only',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'California Labor Code Section 510',
                'effective_from' => '2000-01-01',
            ],

            [
                'jurisdiction' => 'US-CA',
                'rule_code' => 'CA_WEEKLY_OVERTIME',
                'name' => 'California Weekly Overtime',
                'description' => 'Overtime (1.5x) required for work exceeding 40 hours in a week.',
                'rule_type' => 'overtime',
                'parameters' => [
                    'weekly_threshold_hours' => 40,
                    'rate_multiplier' => 1.5,
                ],
                'enforcement' => 'log_only',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'California Labor Code Section 510',
                'effective_from' => '2000-01-01',
            ],

            [
                'jurisdiction' => 'US-CA',
                'rule_code' => 'CA_DOUBLE_TIME',
                'name' => 'California Double Time',
                'description' => 'Double time (2x) required for work exceeding 12 hours in a day.',
                'rule_type' => 'overtime',
                'parameters' => [
                    'daily_threshold_hours' => 12,
                    'rate_multiplier' => 2.0,
                ],
                'enforcement' => 'log_only',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'California Labor Code Section 510',
                'effective_from' => '2000-01-01',
            ],

            [
                'jurisdiction' => 'US-CA',
                'rule_code' => 'CA_MEAL_BREAK',
                'name' => 'California Meal Break',
                'description' => '30-minute unpaid meal break required for shifts over 5 hours.',
                'rule_type' => 'break',
                'parameters' => [
                    'threshold_hours' => 5,
                    'break_minutes' => 30,
                    'paid' => false,
                    'penalty_for_violation' => 'one_hour_pay',
                ],
                'enforcement' => 'hard_block',
                'is_active' => true,
                'allows_opt_out' => true,
                'opt_out_requirements' => 'May be waived by mutual consent if shift is 6 hours or less.',
                'legal_reference' => 'California Labor Code Section 512',
                'effective_from' => '2000-01-01',
            ],

            [
                'jurisdiction' => 'US-CA',
                'rule_code' => 'CA_REST_BREAK',
                'name' => 'California Rest Break',
                'description' => '10-minute paid rest break per 4 hours worked.',
                'rule_type' => 'break',
                'parameters' => [
                    'interval_hours' => 4,
                    'break_minutes' => 10,
                    'paid' => true,
                ],
                'enforcement' => 'soft_warning',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'California Labor Code Section 226.7',
                'effective_from' => '2000-01-01',
            ],

            [
                'jurisdiction' => 'US-CA',
                'rule_code' => 'CA_SPLIT_SHIFT',
                'name' => 'California Split Shift Premium',
                'description' => 'Split shift premium of one hour at minimum wage required.',
                'rule_type' => 'wage',
                'parameters' => [
                    'premium_hours' => 1,
                    'premium_rate' => 'minimum_wage',
                ],
                'enforcement' => 'log_only',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'California IWC Wage Orders',
                'effective_from' => '2000-01-01',
            ],
        ];
    }

    /**
     * Australia Fair Work Act Rules
     */
    protected function getAustraliaRules(): array
    {
        return [
            [
                'jurisdiction' => 'AU',
                'rule_code' => 'AU_MAX_WEEKLY',
                'name' => 'Australia Maximum Weekly Hours',
                'description' => 'Maximum 38 ordinary hours per week, with reasonable additional hours.',
                'rule_type' => 'working_time',
                'parameters' => [
                    'ordinary_hours' => 38,
                    'period' => 'weekly',
                ],
                'enforcement' => 'soft_warning',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'Fair Work Act 2009, Section 62',
                'effective_from' => '2010-01-01',
            ],

            [
                'jurisdiction' => 'AU',
                'rule_code' => 'AU_MAX_DAILY',
                'name' => 'Australia Maximum Daily Hours',
                'description' => 'Maximum ordinary hours in a day varies by award, generally 10-12 hours.',
                'rule_type' => 'working_time',
                'parameters' => [
                    'max_hours' => 12,
                    'period' => 'daily',
                ],
                'enforcement' => 'soft_warning',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'Fair Work Act 2009, National Employment Standards',
                'effective_from' => '2010-01-01',
            ],

            [
                'jurisdiction' => 'AU',
                'rule_code' => 'AU_REST_BETWEEN',
                'name' => 'Australia Minimum Rest Between Shifts',
                'description' => 'Minimum 10 hours break between shifts (varies by award).',
                'rule_type' => 'rest_period',
                'parameters' => [
                    'min_hours' => 10,
                    'period' => 'between_shifts',
                ],
                'enforcement' => 'soft_warning',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'Various Modern Awards',
                'effective_from' => '2010-01-01',
            ],

            [
                'jurisdiction' => 'AU',
                'rule_code' => 'AU_MEAL_BREAK',
                'name' => 'Australia Meal Break',
                'description' => 'Unpaid meal break of 30-60 minutes required for shifts over 5-6 hours.',
                'rule_type' => 'break',
                'parameters' => [
                    'threshold_hours' => 5,
                    'break_minutes' => 30,
                    'paid' => false,
                ],
                'enforcement' => 'soft_warning',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'Various Modern Awards',
                'effective_from' => '2010-01-01',
            ],

            [
                'jurisdiction' => 'AU',
                'rule_code' => 'AU_JUNIOR_HOURS',
                'name' => 'Australia Junior Workers Hours',
                'description' => 'Restrictions on working hours for workers under 18.',
                'rule_type' => 'age_restriction',
                'parameters' => [
                    'max_age' => 18,
                    'school_day_max_hours' => 3,
                    'non_school_day_max_hours' => 8,
                ],
                'enforcement' => 'hard_block',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'State-specific child employment laws',
                'effective_from' => '2010-01-01',
            ],
        ];
    }

    /**
     * US Federal Labor Law Rules
     */
    protected function getUsFederalRules(): array
    {
        return [
            [
                'jurisdiction' => 'US-FEDERAL',
                'rule_code' => 'FLSA_WEEKLY_OVERTIME',
                'name' => 'FLSA Weekly Overtime',
                'description' => 'Federal overtime (1.5x) required for work exceeding 40 hours in a workweek.',
                'rule_type' => 'overtime',
                'parameters' => [
                    'weekly_threshold_hours' => 40,
                    'rate_multiplier' => 1.5,
                ],
                'enforcement' => 'log_only',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'Fair Labor Standards Act, 29 U.S.C. 207',
                'effective_from' => '1938-10-24',
            ],

            [
                'jurisdiction' => 'US-FEDERAL',
                'rule_code' => 'FLSA_CHILD_LABOR',
                'name' => 'FLSA Child Labor Restrictions',
                'description' => 'Minimum age of 14 for non-agricultural work, with hour restrictions for minors.',
                'rule_type' => 'age_restriction',
                'parameters' => [
                    'minimum_work_age' => 14,
                    'age_14_15_school_day_hours' => 3,
                    'age_14_15_school_week_hours' => 18,
                    'age_14_15_non_school_day_hours' => 8,
                    'age_14_15_non_school_week_hours' => 40,
                ],
                'enforcement' => 'hard_block',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'Fair Labor Standards Act, 29 U.S.C. 212',
                'effective_from' => '1938-10-24',
            ],

            [
                'jurisdiction' => 'US-FEDERAL',
                'rule_code' => 'FLSA_HAZARDOUS_YOUTH',
                'name' => 'FLSA Hazardous Work for Minors',
                'description' => 'Prohibits minors under 18 from hazardous occupations.',
                'rule_type' => 'age_restriction',
                'parameters' => [
                    'min_age_for_hazardous' => 18,
                    'hazardous_occupations' => ['mining', 'manufacturing_explosives', 'power_machinery', 'wrecking'],
                ],
                'enforcement' => 'hard_block',
                'is_active' => true,
                'allows_opt_out' => false,
                'legal_reference' => 'Fair Labor Standards Act, 29 CFR 570',
                'effective_from' => '1938-10-24',
            ],
        ];
    }
}
