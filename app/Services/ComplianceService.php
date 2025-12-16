<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\User;
use App\Models\Jurisdiction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Compliance Service
 * 
 * Handles global jurisdiction compliance for OvertimeStaff platform
 * Validates labor laws, minimum wages, and regulatory requirements
 * 
 * GLO-001: Jurisdiction Management System
 * GLO-003: Global Tax Framework
 * GLO-006: Global Minimum Wage Compliance
 */
class ComplianceService
{
    /**
     * Jurisdiction cache duration (24 hours)
     */
    const CACHE_DURATION = 86400;

    /**
     * Validate shift creation against jurisdiction rules
     * SL-001: Shift Creation & Cost Calculation
     */
    public function validateShiftCreation(Shift $shift): array
    {
        $violations = [];
        $warnings = [];

        try {
            $jurisdiction = $this->getJurisdictionRules($shift->country, $shift->state);

            // Minimum wage validation
            $minWageValidation = $this->validateMinimumWage($shift, $jurisdiction);
            if (!$minWageValidation['compliant']) {
                $violations[] = $minWageValidation['message'];
            }

            // Maximum shift duration validation
            $durationValidation = $this->validateShiftDuration($shift, $jurisdiction);
            if (!$durationValidation['compliant']) {
                $violations[] = $durationValidation['message'];
            }

            // Break requirement validation
            $breakValidation = $this->validateBreakRequirements($shift, $jurisdiction);
            if (!$breakValidation['compliant']) {
                $warnings[] = $breakValidation['message'];
            }

            // Night work restrictions
            $nightWorkValidation = $this->validateNightWork($shift, $jurisdiction);
            if (!$nightWorkValidation['compliant']) {
                $warnings[] = $nightWorkValidation['message'];
            }

            // Youth worker restrictions
            $youthValidation = $this->validateYouthWorkerRestrictions($shift, $jurisdiction);
            if (!$youthValidation['compliant']) {
                $violations[] = $youthValidation['message'];
            }

            // Sunday/Weekend restrictions
            $weekendValidation = $this->validateWeekendRestrictions($shift, $jurisdiction);
            if (!$weekendValidation['compliant']) {
                $warnings[] = $weekendValidation['message'];
            }

            // Rest period between shifts
            $restValidation = $this->validateRestPeriods($shift, $jurisdiction);
            if (!$restValidation['compliant']) {
                $warnings[] = $restValidation['message'];
            }

            return [
                'compliant' => empty($violations),
                'violations' => $violations,
                'warnings' => $warnings,
                'jurisdiction' => $jurisdiction,
                'recommendations' => $this->getRecommendations($shift, $jurisdiction)
            ];

        } catch (\Exception $e) {
            Log::error('Shift validation error', [
                'shift_id' => $shift->id,
                'error' => $e->getMessage()
            ]);

            return [
                'compliant' => false,
                'violations' => ['Validation service unavailable'],
                'warnings' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate worker right-to-work for jurisdiction
     * GLO-005: Right-to-Work Verification
     */
    public function validateRightToWork(User $worker, string $country, string $state = null): array
    {
        try {
            $jurisdiction = $this->getJurisdictionRules($country, $state);
            $documents = $worker->documents()->where('status', 'verified')->get();

            $requiredDocuments = $jurisdiction['right_to_work']['required_documents'] ?? [];
            $hasRequiredDocs = true;
            $missingDocs = [];

            foreach ($requiredDocuments as $docType) {
                $hasDoc = $documents->contains('type', $docType);
                if (!$hasDoc) {
                    $hasRequiredDocs = false;
                    $missingDocs[] = $docType;
                }
            }

            // Check document expiry
            $expiringSoon = false;
            $expiredDocs = [];

            foreach ($documents as $doc) {
                if ($doc->expiry_date) {
                    if ($doc->expiry_date->isPast()) {
                        $expiredDocs[] = $doc->type;
                    } elseif ($doc->expiry_date->diffInDays(now()) < 30) {
                        $expiringSoon = true;
                    }
                }
            }

            $compliant = $hasRequiredDocs && empty($expiredDocs);

            return [
                'compliant' => $compliant,
                'has_required_documents' => $hasRequiredDocs,
                'missing_documents' => $missingDocs,
                'expired_documents' => $expiredDocs,
                'expiring_soon' => $expiringSoon,
                'jurisdiction_requirements' => $requiredDocuments
            ];

        } catch (\Exception $e) {
            Log::error('Right-to-work validation error', [
                'worker_id' => $worker->id,
                'country' => $country,
                'error' => $e->getMessage()
            ]);

            return [
                'compliant' => false,
                'error' => 'Validation service unavailable'
            ];
        }
    }

    /**
     * Get jurisdiction rules and regulations
     */
    public function getJurisdictionRules(string $country, string $state = null): array
    {
        $cacheKey = "jurisdiction_rules_{$country}_{$state}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($country, $state) {
            // In production, this would pull from database or external API
            // For now, return mock jurisdiction data
            return $this->getMockJurisdictionData($country, $state);
        });
    }

    /**
     * Validate minimum wage compliance
     */
    private function validateMinimumWage(Shift $shift, array $jurisdiction): array
    {
        $minWage = $this->getMinimumWage($jurisdiction, $shift->role_type);
        $actualWage = $shift->final_rate ?? $shift->base_rate ?? 0;

        if ($actualWage < $minWage) {
            return [
                'compliant' => false,
                'message' => "Rate \${$actualWage}/hr is below minimum wage of \${$minWage}/hr for {$shift->role_type} in {$jurisdiction['name']}",
                'required_rate' => $minWage,
                'actual_rate' => $actualWage
            ];
        }

        return [
            'compliant' => true,
            'minimum_wage' => $minWage,
            'actual_wage' => $actualWage
        ];
    }

    /**
     * Validate shift duration against jurisdiction limits
     */
    private function validateShiftDuration(Shift $shift, array $jurisdiction): array
    {
        $maxHours = $jurisdiction['labor_rules']['maximum_shift_hours'] ?? 16;
        $actualHours = $shift->duration_hours;

        if ($actualHours > $maxHours) {
            return [
                'compliant' => false,
                'message' => "Shift duration of {$actualHours}h exceeds maximum of {$maxHours}h in {$jurisdiction['name']}",
                'max_hours' => $maxHours,
                'actual_hours' => $actualHours
            ];
        }

        return [
            'compliant' => true,
            'max_hours' => $maxHours,
            'actual_hours' => $actualHours
        ];
    }

    /**
     * Validate break requirements
     */
    private function validateBreakRequirements(Shift $shift, array $jurisdiction): array
    {
        $breakRules = $jurisdiction['labor_rules']['breaks'] ?? [];
        
        if (empty($breakRules)) {
            return ['compliant' => true, 'message' => 'No break requirements'];
        }

        $threshold = $breakRules['meal_break_threshold_hours'] ?? 6;
        $requiredDuration = $breakRules['meal_break_duration_minutes'] ?? 30;
        $isPaid = $breakRules['meal_break_paid'] ?? false;

        if ($shift->duration_hours >= $threshold) {
            return [
                'compliant' => true,
                'message' => "Break required: {$requiredDuration} minutes " . ($isPaid ? 'paid' : 'unpaid'),
                'break_required' => true,
                'duration_minutes' => $requiredDuration,
                'paid' => $isPaid
            ];
        }

        return [
            'compliant' => true,
            'message' => 'No break required for this duration',
            'break_required' => false
        ];
    }

    /**
     * Validate night work restrictions
     */
    private function validateNightWork(Shift $shift, array $jurisdiction): array
    {
        $restrictions = $jurisdiction['operational_restrictions'] ?? [];
        
        if (!($restrictions['night_work'] ?? true)) {
            return ['compliant' => false, 'message' => 'Night work not permitted in this jurisdiction'];
        }

        // Check if shift is during night hours
        $nightStartHour = $restrictions['night_start_hour'] ?? 22; // 10 PM
        $nightEndHour = $restrictions['night_end_hour'] ?? 6; // 6 AM

        $shiftStart = $shift->start_time;
        $shiftEnd = $shift->end_time;

        $isNightShift = $this->isTimeInRange($shiftStart, $nightStartHour, $nightEndHour) ||
                        $this->isTimeInRange($shiftEnd, $nightStartHour, $nightEndHour);

        if ($isNightShift) {
            return [
                'compliant' => true,
                'message' => 'Night work detected - ensure compliance with night work regulations',
                'night_shift' => true
            ];
        }

        return [
            'compliant' => true,
            'night_shift' => false
        ];
    }

    /**
     * Validate youth worker restrictions
     */
    private function validateYouthWorkerRestrictions(Shift $shift, array $jurisdiction): array
    {
        $youthRules = $jurisdiction['operational_restrictions']['youth_restrictions'] ?? null;

        if (!$youthRules) {
            return ['compliant' => true];
        }

        $minAge = $youthRules['minimum_age'] ?? 18;
        $nightRestriction = $youthRules['no_night_work_under_age'] ?? 18;

        // This would be validated at worker assignment time
        // For shift creation, just show the restriction
        return [
            'compliant' => true,
            'message' => "Workers must be at least {$minAge} years old. Night work restricted for workers under {$nightRestriction}."
        ];
    }

    /**
     * Validate weekend work restrictions
     */
    private function validateWeekendRestrictions(Shift $shift, array $jurisdiction): array
    {
        $sundayWork = $jurisdiction['operational_restrictions']['sunday_work'] ?? 'allowed';

        if ($sundayWork === 'forbidden') {
            $shiftDate = Carbon::parse($shift->shift_date);
            if ($shiftDate->dayOfWeek === Carbon::SUNDAY) {
                return [
                    'compliant' => false,
                    'message' => 'Sunday work not permitted in this jurisdiction'
                ];
            }
        }

        if ($sundayWork === 'restricted') {
            $shiftDate = Carbon::parse($shift->shift_date);
            if ($shiftDate->dayOfWeek === Carbon::SUNDAY) {
                return [
                    'compliant' => true,
                    'message' => 'Sunday work requires special permits in this jurisdiction'
                ];
            }
        }

        return ['compliant' => true];
    }

    /**
     * Validate rest periods between shifts
     */
    private function validateRestPeriods(Shift $shift, array $jurisdiction): array
    {
        $minRestHours = $jurisdiction['labor_rules']['minimum_rest_between_shifts_hours'] ?? 8;

        // This would be validated at worker assignment time
        return [
            'compliant' => true,
            'message' => "Workers must have at least {$minRestHours} hours between shifts"
        ];
    }

    /**
     * Get minimum wage for role in jurisdiction
     */
    private function getMinimumWage(array $jurisdiction, string $role = null): float
    {
        $minimumWages = $jurisdiction['minimum_wage'] ?? [];

        // Check role-specific minimum
        if ($role && isset($minimumWages['role_specific'][$role])) {
            return $minimumWages['role_specific'][$role];
        }

        // Check age-specific minimum
        if (isset($minimumWages['under_18'])) {
            return $minimumWages['under_18'];
        }

        // Return general minimum
        return $minimumWages['general'] ?? 7.25; // US federal fallback
    }

    /**
     * Get recommendations for compliance improvement
     */
    private function getRecommendations(Shift $shift, array $jurisdiction): array
    {
        $recommendations = [];

        // Recommend advance posting to avoid surge pricing
        $hoursUntilShift = Carbon::parse($shift->shift_date . ' ' . $shift->start_time)->diffInHours(now());
        if ($hoursUntilShift < 72) {
            $recommendations[] = 'Post shifts 72+ hours in advance to avoid surge pricing';
        }

        // Recommend templates for recurring shifts
        if (!$shift->template_id && $this->isRecurringPattern($shift)) {
            $recommendations[] = 'Consider creating a shift template for recurring patterns';
        }

        // Recommend adding more details for better matching
        if (strlen($shift->description) < 100) {
            $recommendations[] = 'Add more detailed description for better worker matching';
        }

        return $recommendations;
    }

    /**
     * Check if time falls within night hours range
     */
    private function isTimeInRange(string $time, int $startHour, int $endHour): bool
    {
        $hour = (int) explode(':', $time)[0];

        if ($startHour > $endHour) { // Overnight range (e.g., 22:00 to 06:00)
            return $hour >= $startHour || $hour < $endHour;
        } else { // Same day range
            return $hour >= $startHour && $hour < $endHour;
        }
    }

    /**
     * Check if shift appears to be part of a recurring pattern
     */
    private function isRecurringPattern(Shift $shift): bool
    {
        // Simple heuristic - same time/day patterns would be detected in production
        return false;
    }

    /**
     * Get mock jurisdiction data (replace with real database/API)
     */
    private function getMockJurisdictionData(string $country, string $state = null): array
    {
        $jurisdictions = [
            'US' => [
                'name' => 'United States',
                'currency' => 'USD',
                'minimum_wage' => [
                    'general' => 7.25, // Federal minimum
                    'role_specific' => [
                        'server' => 7.25,
                        'bartender' => 7.25,
                        'nurse' => 15.00
                    ]
                ],
                'labor_rules' => [
                    'maximum_shift_hours' => 16,
                    'minimum_rest_between_shifts_hours' => 8,
                    'breaks' => [
                        'meal_break_threshold_hours' => 6,
                        'meal_break_duration_minutes' => 30,
                        'meal_break_paid' => false
                    ]
                ],
                'operational_restrictions' => [
                    'sunday_work' => 'allowed',
                    'night_work' => true,
                    'youth_restrictions' => [
                        'minimum_age' => 16,
                        'no_night_work_under_age' => 18
                    ]
                ],
                'right_to_work' => [
                    'required_documents' => ['government_id', 'i9_documentation']
                ],
                'tax_rules' => [
                    'platform_service_tax_rate' => 0.0,
                    'sales_tax_applicable' => true
                ]
            ],
            'CA' => [ // California specific
                'name' => 'California, USA',
                'currency' => 'USD',
                'minimum_wage' => [
                    'general' => 16.00,
                    'role_specific' => [
                        'server' => 16.00,
                        'bartender' => 16.00
                    ]
                ],
                'labor_rules' => [
                    'maximum_shift_hours' => 12,
                    'minimum_rest_between_shifts_hours' => 11,
                    'breaks' => [
                        'meal_break_threshold_hours' => 5,
                        'meal_break_duration_minutes' => 30,
                        'meal_break_paid' => false,
                        'rest_break_interval_hours' => 4,
                        'rest_break_duration_minutes' => 10,
                        'rest_break_paid' => true
                    ],
                    'overtime' => [
                        'daily_threshold_hours' => 8,
                        'weekly_threshold_hours' => 40,
                        'rate_multiplier' => 1.5,
                        'double_time_threshold' => 12,
                        'double_time_multiplier' => 2.0
                    ]
                ],
                'operational_restrictions' => [
                    'sunday_work' => 'allowed',
                    'night_work' => true,
                    'youth_restrictions' => [
                        'minimum_age' => 18
                    ]
                ]
            ],
            'UK' => [
                'name' => 'United Kingdom',
                'currency' => 'GBP',
                'minimum_wage' => [
                    'general' => 11.44, // National Living Wage
                    'role_specific' => [
                        'apprentice' => 5.28,
                        'under_18' => 8.60,
                        '18_20' => 8.60,
                        '21_22' => 11.44
                    ]
                ],
                'labor_rules' => [
                    'maximum_shift_hours' => 13, // Working Time Directive
                    'minimum_rest_between_shifts_hours' => 11,
                    'breaks' => [
                        'meal_break_threshold_hours' => 6,
                        'meal_break_duration_minutes' => 20,
                        'meal_break_paid' => false
                    ]
                ],
                'operational_restrictions' => [
                    'sunday_work' => 'allowed',
                    'night_work' => true
                ],
                'tax_rules' => [
                    'platform_service_tax_rate' => 0.20, // 20% VAT
                    'sales_tax_applicable' => true
                ]
            ]
        ];

        return $jurisdictions[$country] ?? $jurisdictions['US'];
    }

    /**
     * Clear jurisdiction cache (for updates)
     */
    public function clearJurisdictionCache(string $country, string $state = null): void
    {
        $cacheKey = "jurisdiction_rules_{$country}_{$state}";
        Cache::forget($cacheKey);
    }

    /**
     * Check for regulatory updates in jurisdiction
     */
    public function checkRegulatoryUpdates(string $country): array
    {
        // In production, this would check external sources
        return [
            'updates' => [],
            'last_checked' => now(),
            'jurisdiction' => $country
        ];
    }
}