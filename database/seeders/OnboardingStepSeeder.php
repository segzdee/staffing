<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * OnboardingStepSeeder
 *
 * Seeds all onboarding steps for Workers and Businesses
 * Based on protocols STAFF-REG-002 through 011 and BIZ-REG-002 through 011
 *
 * Weight Distribution:
 * - Required steps: 70% of total weight
 * - Recommended steps: 30% of total weight
 */
class OnboardingStepSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Clear existing onboarding steps (for re-seeding)
        // Disable foreign key checks to allow truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('onboarding_progress')->truncate();
        DB::table('onboarding_steps')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ============================================================
        // WORKER ONBOARDING STEPS (STAFF-REG-002 to 011)
        // ============================================================

        $workerSteps = [
            // STEP 1: Account Creation & Verification (STAFF-REG-002)
            [
                'step_id' => 'account_created',
                'user_type' => 'worker',
                'name' => 'Account Creation',
                'description' => 'Create your OvertimeStaff account and verify your email address',
                'help_text' => 'You will receive an email with a verification link. Click the link to verify your account.',
                'help_url' => null,
                'step_type' => 'required',
                'category' => 'account',
                'order' => 1,
                'dependencies' => null,
                'weight' => 5,
                'estimated_minutes' => 2,
                'threshold' => null,
                'target' => null,
                'auto_complete' => true,
                'auto_complete_event' => 'UserRegistered',
                'route_name' => null,
                'route_params' => null,
                'icon' => 'user-plus',
                'color' => 'green',
                'is_active' => true,
                'cohort_variant' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // STEP 2: Profile Creation (STAFF-REG-003)
            [
                'step_id' => 'profile_complete',
                'user_type' => 'worker',
                'name' => 'Complete Profile',
                'description' => 'Add your personal information, work experience, and profile photo',
                'help_text' => 'A complete profile helps businesses find you for shifts. Include your work history, bio, and a professional photo.',
                'help_url' => '/help/worker-profile',
                'step_type' => 'required',
                'category' => 'profile',
                'order' => 2,
                'dependencies' => ['account_created'],
                'weight' => 10,
                'estimated_minutes' => 10,
                'threshold' => 80,
                'target' => null,
                'auto_complete' => false,
                'auto_complete_event' => null,
                'route_name' => 'worker.profile.edit',
                'route_params' => null,
                'icon' => 'identification',
                'color' => 'blue',
                'is_active' => true,
                'cohort_variant' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // STEP 3: Identity Verification (KYC) (STAFF-REG-004)
            [
                'step_id' => 'identity_verified',
                'user_type' => 'worker',
                'name' => 'Identity Verification',
                'description' => 'Verify your identity using government-issued ID (KYC)',
                'help_text' => 'Upload a clear photo of your government ID (passport, driver\'s license, or national ID card). We use Onfido for secure verification.',
                'help_url' => '/help/identity-verification',
                'step_type' => 'required',
                'category' => 'verification',
                'order' => 3,
                'dependencies' => ['profile_complete'],
                'weight' => 15,
                'estimated_minutes' => 5,
                'threshold' => null,
                'target' => null,
                'auto_complete' => true,
                'auto_complete_event' => 'IdentityVerified',
                'route_name' => 'worker.verification.identity',
                'route_params' => null,
                'icon' => 'shield-check',
                'color' => 'purple',
                'is_active' => true,
                'cohort_variant' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // STEP 4: Right-to-Work Verification (STAFF-REG-005)
            [
                'step_id' => 'rtw_verified',
                'user_type' => 'worker',
                'name' => 'Right-to-Work Verification',
                'description' => 'Verify your legal right to work in your country',
                'help_text' => 'Upload documents proving your right to work (work visa, passport, national ID, etc.). Required by law for all employers.',
                'help_url' => '/help/right-to-work',
                'step_type' => 'required',
                'category' => 'verification',
                'order' => 4,
                'dependencies' => ['identity_verified'],
                'weight' => 15,
                'estimated_minutes' => 5,
                'threshold' => null,
                'target' => null,
                'auto_complete' => true,
                'auto_complete_event' => 'RightToWorkVerified',
                'route_name' => 'worker.verification.rtw',
                'route_params' => null,
                'icon' => 'document-check',
                'color' => 'indigo',
                'is_active' => true,
                'cohort_variant' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // STEP 5: Background Check (STAFF-REG-006)
            [
                'step_id' => 'background_check_complete',
                'user_type' => 'worker',
                'name' => 'Background Check',
                'description' => 'Complete a background check to build trust with businesses',
                'help_text' => 'We use Checkr for criminal background checks. This is required for many shifts, especially those involving vulnerable populations.',
                'help_url' => '/help/background-check',
                'step_type' => 'required',
                'category' => 'verification',
                'order' => 5,
                'dependencies' => ['identity_verified', 'rtw_verified'],
                'weight' => 15,
                'estimated_minutes' => 10,
                'threshold' => null,
                'target' => null,
                'auto_complete' => true,
                'auto_complete_event' => 'BackgroundCheckCleared',
                'route_name' => 'worker.verification.background',
                'route_params' => null,
                'icon' => 'clipboard-check',
                'color' => 'green',
                'is_active' => true,
                'cohort_variant' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // STEP 6: Skills & Certifications (STAFF-REG-007)
            [
                'step_id' => 'skills_added',
                'user_type' => 'worker',
                'name' => 'Add Skills & Certifications',
                'description' => 'Add your skills and upload any professional certifications',
                'help_text' => 'Add at least 3 skills to increase your visibility in search results. Upload certifications like food handler cards, CPR, etc.',
                'help_url' => '/help/skills-certifications',
                'step_type' => 'required',
                'category' => 'profile',
                'order' => 6,
                'dependencies' => ['profile_complete'],
                'weight' => 10,
                'estimated_minutes' => 10,
                'threshold' => null,
                'target' => 3,
                'auto_complete' => false,
                'auto_complete_event' => null,
                'route_name' => 'worker.skills.index',
                'route_params' => null,
                'icon' => 'academic-cap',
                'color' => 'yellow',
                'is_active' => true,
                'cohort_variant' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // STEP 7: Payment Setup (STAFF-REG-008)
            [
                'step_id' => 'payment_setup',
                'user_type' => 'worker',
                'name' => 'Payment Setup',
                'description' => 'Add your bank account or payment method to receive earnings',
                'help_text' => 'Set up your payment method to receive money from completed shifts. We support bank transfers, Stripe, and other methods depending on your country.',
                'help_url' => '/help/payment-setup',
                'step_type' => 'required',
                'category' => 'payment',
                'order' => 7,
                'dependencies' => ['identity_verified'],
                'weight' => 10,
                'estimated_minutes' => 5,
                'threshold' => null,
                'target' => null,
                'auto_complete' => true,
                'auto_complete_event' => 'PaymentMethodAdded',
                'route_name' => 'worker.payment.setup',
                'route_params' => null,
                'icon' => 'credit-card',
                'color' => 'green',
                'is_active' => true,
                'cohort_variant' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // STEP 8: Availability Configuration (STAFF-REG-009)
            [
                'step_id' => 'availability_set',
                'user_type' => 'worker',
                'name' => 'Set Availability',
                'description' => 'Configure your weekly availability schedule',
                'help_text' => 'Set your weekly availability to receive relevant shift notifications. You can update this anytime.',
                'help_url' => '/help/availability',
                'step_type' => 'recommended',
                'category' => 'profile',
                'order' => 8,
                'dependencies' => ['profile_complete'],
                'weight' => 15,
                'estimated_minutes' => 5,
                'threshold' => null,
                'target' => null,
                'auto_complete' => false,
                'auto_complete_event' => null,
                'route_name' => 'worker.availability.index',
                'route_params' => null,
                'icon' => 'calendar',
                'color' => 'blue',
                'is_active' => true,
                'cohort_variant' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // STEP 9: Onboarding Review (STAFF-REG-010)
            [
                'step_id' => 'onboarding_reviewed',
                'user_type' => 'worker',
                'name' => 'Review Your Profile',
                'description' => 'Review all your information before activation',
                'help_text' => 'Take a moment to review all your information for accuracy. Once activated, you can start applying for shifts.',
                'help_url' => null,
                'step_type' => 'recommended',
                'category' => 'onboarding',
                'order' => 9,
                'dependencies' => ['profile_complete', 'skills_added'],
                'weight' => 10,
                'estimated_minutes' => 3,
                'threshold' => null,
                'target' => null,
                'auto_complete' => false,
                'auto_complete_event' => null,
                'route_name' => 'worker.onboarding.review',
                'route_params' => null,
                'icon' => 'eye',
                'color' => 'gray',
                'is_active' => true,
                'cohort_variant' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // STEP 10: Account Activation (STAFF-REG-011)
            [
                'step_id' => 'account_activated',
                'user_type' => 'worker',
                'name' => 'Account Activation',
                'description' => 'Your account is activated and ready to use',
                'help_text' => 'Congratulations! Your account is now active. You can browse and apply for shifts.',
                'help_url' => null,
                'step_type' => 'required',
                'category' => 'onboarding',
                'order' => 10,
                'dependencies' => ['profile_complete', 'identity_verified', 'rtw_verified', 'background_check_complete', 'skills_added', 'payment_setup'],
                'weight' => 5,
                'estimated_minutes' => 1,
                'threshold' => null,
                'target' => null,
                'auto_complete' => true,
                'auto_complete_event' => 'WorkerActivated',
                'route_name' => 'worker.dashboard',
                'route_params' => null,
                'icon' => 'check-circle',
                'color' => 'green',
                'is_active' => true,
                'cohort_variant' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // ============================================================
        // BUSINESS ONBOARDING STEPS (BIZ-REG-002 to 011)
        // ============================================================

        $businessSteps = [
            // STEP 1: Account Creation (BIZ-REG-002)
            [
                'step_id' => 'business_account_created',
                'user_type' => 'business',
                'name' => 'Account Creation',
                'description' => 'Create your business account on OvertimeStaff',
                'help_text' => 'Register your business and create an account to start posting shifts.',
                'help_url' => null,
                'step_type' => 'required',
                'category' => 'account',
                'order' => 1,
                'dependencies' => null,
                'weight' => 5,
                'estimated_minutes' => 2,
                'threshold' => null,
                'target' => null,
                'auto_complete' => true,
                'auto_complete_event' => 'BusinessRegistered',
                'route_name' => null,
                'route_params' => null,
                'icon' => 'building-office',
                'color' => 'green',
                'is_active' => true,
                'cohort_variant' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // STEP 2: Email Verification (BIZ-REG-003)
            [
                'step_id' => 'business_email_verified',
                'user_type' => 'business',
                'name' => 'Email Verification',
                'description' => 'Verify your business email address',
                'help_text' => 'Click the verification link sent to your email to confirm your account.',
                'help_url' => null,
                'step_type' => 'required',
                'category' => 'account',
                'order' => 2,
                'dependencies' => ['business_account_created'],
                'weight' => 5,
                'estimated_minutes' => 2,
                'threshold' => null,
                'target' => null,
                'auto_complete' => true,
                'auto_complete_event' => 'BusinessEmailVerified',
                'route_name' => null,
                'route_params' => null,
                'icon' => 'envelope-check',
                'color' => 'blue',
                'is_active' => true,
                'cohort_variant' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // STEP 3: Company Profile Setup (BIZ-REG-004)
            [
                'step_id' => 'company_profile_complete',
                'user_type' => 'business',
                'name' => 'Company Profile Setup',
                'description' => 'Complete your company profile with business details',
                'help_text' => 'Add your company name, description, logo, and contact information. A complete profile helps attract quality workers.',
                'help_url' => '/help/business-profile',
                'step_type' => 'required',
                'category' => 'profile',
                'order' => 3,
                'dependencies' => ['business_email_verified'],
                'weight' => 10,
                'estimated_minutes' => 10,
                'threshold' => 80,
                'target' => null,
                'auto_complete' => false,
                'auto_complete_event' => null,
                'route_name' => 'business.profile.edit',
                'route_params' => null,
                'icon' => 'building-storefront',
                'color' => 'purple',
                'is_active' => true,
                'cohort_variant' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // STEP 4: KYB Verification (BIZ-REG-005)
            [
                'step_id' => 'kyb_verified',
                'user_type' => 'business',
                'name' => 'Business Verification (KYB)',
                'description' => 'Verify your business identity and legal status',
                'help_text' => 'Upload business registration documents, tax ID, and other verification materials. This builds trust with workers.',
                'help_url' => '/help/kyb-verification',
                'step_type' => 'required',
                'category' => 'verification',
                'order' => 4,
                'dependencies' => ['company_profile_complete'],
                'weight' => 15,
                'estimated_minutes' => 15,
                'threshold' => null,
                'target' => null,
                'auto_complete' => true,
                'auto_complete_event' => 'BusinessVerified',
                'route_name' => 'business.verification.kyb',
                'route_params' => null,
                'icon' => 'shield-check',
                'color' => 'indigo',
                'is_active' => true,
                'cohort_variant' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // STEP 5: Insurance & Compliance (BIZ-REG-006)
            [
                'step_id' => 'insurance_verified',
                'user_type' => 'business',
                'name' => 'Insurance & Compliance',
                'description' => 'Upload insurance certificates and compliance documents',
                'help_text' => 'Upload your liability insurance, workers\' compensation insurance, and any industry-specific compliance documents.',
                'help_url' => '/help/insurance-compliance',
                'step_type' => 'required',
                'category' => 'compliance',
                'order' => 5,
                'dependencies' => ['kyb_verified'],
                'weight' => 15,
                'estimated_minutes' => 15,
                'threshold' => null,
                'target' => null,
                'auto_complete' => true,
                'auto_complete_event' => 'InsuranceVerified',
                'route_name' => 'business.insurance.index',
                'route_params' => null,
                'icon' => 'document-text',
                'color' => 'yellow',
                'is_active' => true,
                'cohort_variant' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // STEP 6: Venue Setup (BIZ-REG-007)
            [
                'step_id' => 'venue_added',
                'user_type' => 'business',
                'name' => 'Venue Setup',
                'description' => 'Add your business locations where workers will report',
                'help_text' => 'Add at least one venue (location) where workers will work. Include address, instructions, and parking details.',
                'help_url' => '/help/venue-setup',
                'step_type' => 'required',
                'category' => 'configuration',
                'order' => 6,
                'dependencies' => ['company_profile_complete'],
                'weight' => 10,
                'estimated_minutes' => 10,
                'threshold' => null,
                'target' => 1,
                'auto_complete' => false,
                'auto_complete_event' => null,
                'route_name' => 'business.venues.create',
                'route_params' => null,
                'icon' => 'map-pin',
                'color' => 'red',
                'is_active' => true,
                'cohort_variant' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // STEP 7: Payment Method Setup (BIZ-REG-008)
            [
                'step_id' => 'payment_method_added',
                'user_type' => 'business',
                'name' => 'Payment Method Setup',
                'description' => 'Add a payment method to pay workers',
                'help_text' => 'Add a credit card or bank account to fund shift payments. Payments are held in escrow and released when shifts are completed.',
                'help_url' => '/help/business-payment',
                'step_type' => 'required',
                'category' => 'payment',
                'order' => 7,
                'dependencies' => ['kyb_verified'],
                'weight' => 10,
                'estimated_minutes' => 5,
                'threshold' => null,
                'target' => null,
                'auto_complete' => true,
                'auto_complete_event' => 'PaymentMethodAdded',
                'route_name' => 'business.payment.methods',
                'route_params' => null,
                'icon' => 'credit-card',
                'color' => 'green',
                'is_active' => true,
                'cohort_variant' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // STEP 8: Team Member Invitations (BIZ-REG-009)
            [
                'step_id' => 'team_members_invited',
                'user_type' => 'business',
                'name' => 'Invite Team Members',
                'description' => 'Invite managers and team members to help manage shifts',
                'help_text' => 'Invite colleagues to help post shifts, review applications, and manage workers. This step is optional but recommended for larger businesses.',
                'help_url' => '/help/team-management',
                'step_type' => 'recommended',
                'category' => 'configuration',
                'order' => 8,
                'dependencies' => ['company_profile_complete'],
                'weight' => 10,
                'estimated_minutes' => 5,
                'threshold' => null,
                'target' => 1,
                'auto_complete' => false,
                'auto_complete_event' => null,
                'route_name' => 'business.team.invite',
                'route_params' => null,
                'icon' => 'user-group',
                'color' => 'blue',
                'is_active' => true,
                'cohort_variant' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // STEP 9: First Shift Wizard (BIZ-REG-010)
            [
                'step_id' => 'first_shift_created',
                'user_type' => 'business',
                'name' => 'Create First Shift',
                'description' => 'Post your first shift using our guided wizard',
                'help_text' => 'Use our step-by-step wizard to create your first shift posting. This helps you understand how the platform works.',
                'help_url' => '/help/first-shift',
                'step_type' => 'recommended',
                'category' => 'onboarding',
                'order' => 9,
                'dependencies' => ['venue_added', 'payment_method_added'],
                'weight' => 15,
                'estimated_minutes' => 10,
                'threshold' => null,
                'target' => null,
                'auto_complete' => true,
                'auto_complete_event' => 'FirstShiftCreated',
                'route_name' => 'business.shifts.wizard',
                'route_params' => null,
                'icon' => 'sparkles',
                'color' => 'yellow',
                'is_active' => true,
                'cohort_variant' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // STEP 10: Business Activation (BIZ-REG-011)
            [
                'step_id' => 'business_activated',
                'user_type' => 'business',
                'name' => 'Business Activation',
                'description' => 'Your business account is fully activated',
                'help_text' => 'Congratulations! Your business is now active. You can post shifts, review applications, and hire workers.',
                'help_url' => null,
                'step_type' => 'required',
                'category' => 'onboarding',
                'order' => 10,
                'dependencies' => ['company_profile_complete', 'kyb_verified', 'insurance_verified', 'venue_added', 'payment_method_added'],
                'weight' => 5,
                'estimated_minutes' => 1,
                'threshold' => null,
                'target' => null,
                'auto_complete' => true,
                'auto_complete_event' => 'BusinessActivated',
                'route_name' => 'business.dashboard',
                'route_params' => null,
                'icon' => 'check-circle',
                'color' => 'green',
                'is_active' => true,
                'cohort_variant' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Insert all steps (encode JSON fields properly)
        $allSteps = array_merge($workerSteps, $businessSteps);
        foreach ($allSteps as &$step) {
            // Encode JSON fields
            if (isset($step['dependencies'])) {
                $step['dependencies'] = json_encode($step['dependencies']);
            }
            if (isset($step['route_params'])) {
                $step['route_params'] = json_encode($step['route_params']);
            }
        }

        DB::table('onboarding_steps')->insert($allSteps);

        $this->command->info('✓ Seeded ' . count($workerSteps) . ' worker onboarding steps');
        $this->command->info('✓ Seeded ' . count($businessSteps) . ' business onboarding steps');
        $this->command->info('✓ Total: ' . (count($workerSteps) + count($businessSteps)) . ' onboarding steps');

        // Display weight distribution
        $this->displayWeightDistribution($workerSteps, 'Worker');
        $this->displayWeightDistribution($businessSteps, 'Business');
    }

    /**
     * Display weight distribution for verification
     */
    private function displayWeightDistribution(array $steps, string $userType): void
    {
        $requiredWeight = 0;
        $recommendedWeight = 0;
        $optionalWeight = 0;

        foreach ($steps as $step) {
            switch ($step['step_type']) {
                case 'required':
                    $requiredWeight += $step['weight'];
                    break;
                case 'recommended':
                    $recommendedWeight += $step['weight'];
                    break;
                case 'optional':
                    $optionalWeight += $step['weight'];
                    break;
            }
        }

        $totalWeight = $requiredWeight + $recommendedWeight + $optionalWeight;
        $requiredPercentage = $totalWeight > 0 ? round(($requiredWeight / $totalWeight) * 100, 1) : 0;
        $recommendedPercentage = $totalWeight > 0 ? round(($recommendedWeight / $totalWeight) * 100, 1) : 0;

        $this->command->info("\n{$userType} Weight Distribution:");
        $this->command->info("  Required: {$requiredWeight} points ({$requiredPercentage}%)");
        $this->command->info("  Recommended: {$recommendedWeight} points ({$recommendedPercentage}%)");
        $this->command->info("  Total: {$totalWeight} points");

        // Warn if distribution doesn't match target (70% required, 30% recommended)
        if ($requiredPercentage < 65 || $requiredPercentage > 75) {
            $this->command->warn("  ⚠ Required weight should be ~70% (currently {$requiredPercentage}%)");
        }
        if ($recommendedPercentage < 25 || $recommendedPercentage > 35) {
            $this->command->warn("  ⚠ Recommended weight should be ~30% (currently {$recommendedPercentage}%)");
        }
    }
}
