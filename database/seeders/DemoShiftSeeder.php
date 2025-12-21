<?php

namespace Database\Seeders;

use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DemoShiftSeeder extends Seeder
{
    /**
     * Demo shifts for the Live Shift Market.
     *
     * These shifts showcase platform value to visitors before signup:
     * - Diverse industries and positions
     * - Varied pay rates (competitive market rates)
     * - Different urgency levels
     * - Spread across upcoming dates
     */
    public function run(): void
    {
        // Get or create a demo business account
        $demoBusiness = $this->getOrCreateDemoBusiness();

        if (! $demoBusiness) {
            $this->command->error('Could not create demo business account.');

            return;
        }

        // Clear existing demo shifts (only if is_demo column exists)
        if (Schema::hasColumn('shifts', 'is_demo')) {
            Shift::where('is_demo', true)->delete();
        }

        $demoShifts = $this->getDemoShiftData();
        $created = 0;

        foreach ($demoShifts as $shiftData) {
            try {
                // Build shift data with only columns that exist
                $shiftRecord = $this->buildShiftRecord($shiftData, $demoBusiness->id);
                Shift::create($shiftRecord);
                $created++;
            } catch (\Exception $e) {
                $this->command->warn("Failed to create shift '{$shiftData['title']}': ".$e->getMessage());
            }
        }

        $this->command->info("Created {$created} demo shifts for the Live Shift Market.");
    }

    /**
     * Build shift record with only existing columns.
     */
    private function buildShiftRecord(array $shiftData, int $businessId): array
    {
        $record = [
            'business_id' => $businessId,
            'title' => $shiftData['title'],
            'description' => $shiftData['description'] ?? '',
            'shift_date' => $shiftData['shift_date'],
            'start_time' => $shiftData['start_time'],
            'end_time' => $shiftData['end_time'],
            'base_rate' => $shiftData['base_rate'],
            'status' => 'open',
        ];

        // Add optional columns if they exist
        $optionalColumns = [
            'industry' => $shiftData['industry'] ?? null,
            'demo_business_name' => $shiftData['demo_business_name'] ?? null,
            'location_address' => $shiftData['location_address'] ?? null,
            'location_city' => $shiftData['location_city'] ?? null,
            'location_state' => $shiftData['location_state'] ?? null,
            'location_country' => $shiftData['location_country'] ?? null,
            'duration_hours' => $shiftData['duration_hours'] ?? null,
            'urgency_level' => $shiftData['urgency_level'] ?? 'normal',
            'required_workers' => $shiftData['required_workers'] ?? 1,
            'filled_workers' => $shiftData['filled_workers'] ?? 0,
            'required_skills' => $shiftData['required_skills'] ?? null,
            'required_certifications' => $shiftData['required_certifications'] ?? null,
            'is_demo' => true,
            'in_market' => true,
            'market_posted_at' => now(),
            'market_views' => rand(5, 150),
            'application_count' => rand(0, 8),
        ];

        foreach ($optionalColumns as $column => $value) {
            if ($value !== null && Schema::hasColumn('shifts', $column)) {
                $record[$column] = $value;
            }
        }

        return $record;
    }

    private function getOrCreateDemoBusiness(): ?User
    {
        try {
            // Check which column to use for user type
            $userTypeColumn = Schema::hasColumn('users', 'user_type') ? 'user_type' : 'role';

            return User::firstOrCreate(
                ['email' => 'demo-business@overtimestaff.com'],
                [
                    'name' => 'OvertimeStaff Demo',
                    'password' => bcrypt('demo-business-'.uniqid()),
                    $userTypeColumn => 'business',
                    'email_verified_at' => now(),
                ]
            );
        } catch (\Exception $e) {
            $this->command->error('Error creating demo business: '.$e->getMessage());

            return null;
        }
    }

    private function getDemoShiftData(): array
    {
        $now = Carbon::now();

        return [
            // URGENT - Hospitality (4 hours away)
            [
                'title' => 'Bartender',
                'description' => 'Experienced bartender needed for busy Saturday night shift at upscale cocktail bar. Must know classic cocktails and have excellent customer service skills.',
                'industry' => 'hospitality',
                'demo_business_name' => 'The Grand Hotel',
                'location_address' => 'Triq San Gorg',
                'location_city' => 'St. Julian\'s',
                'location_state' => 'Malta',
                'location_country' => 'Malta',
                'shift_date' => $now->copy()->addHours(4)->toDateString(),
                'start_time' => '18:00:00',
                'end_time' => '02:00:00',
                'duration_hours' => 8,
                'base_rate' => 2800, // $28/hr in cents
                'urgency_level' => 'urgent',
                'required_workers' => 2,
                'filled_workers' => 1,
                'required_skills' => json_encode(['Cocktail Making', 'POS Systems', 'Customer Service']),
            ],

            // HIGH - Healthcare (12 hours away)
            [
                'title' => 'Registered Nurse',
                'description' => 'RN needed for overnight shift at private clinic. Must have valid nursing license and at least 2 years experience.',
                'industry' => 'healthcare',
                'demo_business_name' => 'MediCare Plus Clinic',
                'location_address' => 'Triq il-Kbira',
                'location_city' => 'Sliema',
                'location_state' => 'Malta',
                'location_country' => 'Malta',
                'shift_date' => $now->copy()->addHours(12)->toDateString(),
                'start_time' => '20:00:00',
                'end_time' => '08:00:00',
                'duration_hours' => 12,
                'base_rate' => 4500, // $45/hr
                'urgency_level' => 'high',
                'required_workers' => 1,
                'required_skills' => json_encode(['Patient Care', 'IV Administration', 'Emergency Response']),
                'required_certifications' => json_encode(['Nursing License', 'BLS Certification']),
            ],

            // MEDIUM - Events (24 hours away)
            [
                'title' => 'Event Staff',
                'description' => 'Staff needed for corporate conference. Duties include registration desk, guest assistance, and general event support.',
                'industry' => 'events',
                'demo_business_name' => 'EventPro Malta',
                'location_address' => 'MFCC Ta\' Qali',
                'location_city' => 'Ta\' Qali',
                'location_state' => 'Malta',
                'location_country' => 'Malta',
                'shift_date' => $now->copy()->addDay()->toDateString(),
                'start_time' => '08:00:00',
                'end_time' => '18:00:00',
                'duration_hours' => 10,
                'base_rate' => 2200, // $22/hr
                'urgency_level' => 'normal',
                'required_workers' => 5,
                'filled_workers' => 2,
                'required_skills' => json_encode(['Customer Service', 'Communication', 'Professional Appearance']),
            ],

            // NORMAL - Security (2 days away)
            [
                'title' => 'Security Officer',
                'description' => 'Licensed security officer for night patrol at commercial complex. Must have valid SIA license.',
                'industry' => 'security',
                'demo_business_name' => 'SecureGuard Services',
                'location_address' => 'Triq D\'Argens',
                'location_city' => 'Gzira',
                'location_state' => 'Malta',
                'location_country' => 'Malta',
                'shift_date' => $now->copy()->addDays(2)->toDateString(),
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
                'duration_hours' => 8,
                'base_rate' => 1800, // $18/hr
                'urgency_level' => 'normal',
                'required_workers' => 2,
                'required_skills' => json_encode(['Patrol', 'CCTV Monitoring', 'Incident Reporting']),
            ],

            // URGENT - Food Service (6 hours away)
            [
                'title' => 'Line Cook',
                'description' => 'Experienced line cook for busy restaurant kitchen. Must be able to work under pressure and follow recipes precisely.',
                'industry' => 'food_service',
                'demo_business_name' => 'Harbour View Restaurant',
                'location_address' => 'Triq il-Marina',
                'location_city' => 'Valletta',
                'location_state' => 'Malta',
                'location_country' => 'Malta',
                'shift_date' => $now->copy()->addHours(6)->toDateString(),
                'start_time' => '16:00:00',
                'end_time' => '23:00:00',
                'duration_hours' => 7,
                'base_rate' => 2400, // $24/hr
                'urgency_level' => 'urgent',
                'required_workers' => 1,
                'required_skills' => json_encode(['Food Prep', 'Kitchen Safety', 'Time Management']),
            ],

            // HIGH - Retail (18 hours away)
            [
                'title' => 'Sales Associate',
                'description' => 'Retail sales associate for fashion boutique. Weekend shift with commission opportunities.',
                'industry' => 'retail',
                'demo_business_name' => 'Fashion Forward',
                'location_address' => 'Bay Street Complex',
                'location_city' => 'St. Julian\'s',
                'location_state' => 'Malta',
                'location_country' => 'Malta',
                'shift_date' => $now->copy()->addHours(18)->toDateString(),
                'start_time' => '10:00:00',
                'end_time' => '19:00:00',
                'duration_hours' => 9,
                'base_rate' => 1600, // $16/hr
                'urgency_level' => 'normal',
                'required_workers' => 2,
                'required_skills' => json_encode(['Sales', 'Customer Service', 'Cash Handling']),
            ],

            // MEDIUM - Logistics (36 hours away)
            [
                'title' => 'Warehouse Associate',
                'description' => 'Warehouse worker for inventory management and order fulfillment. Forklift experience preferred.',
                'industry' => 'logistics',
                'demo_business_name' => 'FastShip Logistics',
                'location_address' => 'Hal Far Industrial Estate',
                'location_city' => 'Hal Far',
                'location_state' => 'Malta',
                'location_country' => 'Malta',
                'shift_date' => $now->copy()->addHours(36)->toDateString(),
                'start_time' => '06:00:00',
                'end_time' => '14:00:00',
                'duration_hours' => 8,
                'base_rate' => 1900, // $19/hr
                'urgency_level' => 'normal',
                'required_workers' => 3,
                'filled_workers' => 1,
                'required_skills' => json_encode(['Inventory Management', 'Forklift', 'Physical Fitness']),
            ],

            // NORMAL - Construction (3 days away)
            [
                'title' => 'General Laborer',
                'description' => 'Construction laborer for residential building project. Must be physically fit and have safety boots.',
                'industry' => 'construction',
                'demo_business_name' => 'BuildRight Construction',
                'location_address' => 'Triq il-Wied',
                'location_city' => 'Mosta',
                'location_state' => 'Malta',
                'location_country' => 'Malta',
                'shift_date' => $now->copy()->addDays(3)->toDateString(),
                'start_time' => '07:00:00',
                'end_time' => '16:00:00',
                'duration_hours' => 9,
                'base_rate' => 2100, // $21/hr
                'urgency_level' => 'normal',
                'required_workers' => 4,
                'required_skills' => json_encode(['Construction', 'Heavy Lifting', 'Safety Awareness']),
            ],

            // HIGH - Manufacturing (10 hours away)
            [
                'title' => 'Machine Operator',
                'description' => 'CNC machine operator for precision manufacturing. Must have experience with metal fabrication.',
                'industry' => 'manufacturing',
                'demo_business_name' => 'Precision Parts Ltd',
                'location_address' => 'San Gwann Industrial Estate',
                'location_city' => 'San Gwann',
                'location_state' => 'Malta',
                'location_country' => 'Malta',
                'shift_date' => $now->copy()->addHours(10)->toDateString(),
                'start_time' => '14:00:00',
                'end_time' => '22:00:00',
                'duration_hours' => 8,
                'base_rate' => 2600, // $26/hr
                'urgency_level' => 'high',
                'required_workers' => 1,
                'required_skills' => json_encode(['CNC Operation', 'Quality Control', 'Blueprint Reading']),
            ],

            // URGENT - Hospitality (3 hours away)
            [
                'title' => 'Server',
                'description' => 'Experienced server for beachfront restaurant. Must speak English fluently, additional languages a plus.',
                'industry' => 'hospitality',
                'demo_business_name' => 'Sunset Beach Club',
                'location_address' => 'Mellieha Bay',
                'location_city' => 'Mellieha',
                'location_state' => 'Malta',
                'location_country' => 'Malta',
                'shift_date' => $now->copy()->addHours(3)->toDateString(),
                'start_time' => '11:00:00',
                'end_time' => '20:00:00',
                'duration_hours' => 9,
                'base_rate' => 2000, // $20/hr + tips
                'urgency_level' => 'urgent',
                'required_workers' => 3,
                'filled_workers' => 1,
                'required_skills' => json_encode(['Table Service', 'POS Systems', 'Wine Knowledge']),
            ],

            // PREMIUM - Healthcare (48 hours away)
            [
                'title' => 'Care Assistant',
                'description' => 'Healthcare assistant for elderly care home. Compassionate individual with patience and experience.',
                'industry' => 'healthcare',
                'demo_business_name' => 'Golden Years Care Home',
                'location_address' => 'Triq Santa Marija',
                'location_city' => 'Attard',
                'location_state' => 'Malta',
                'location_country' => 'Malta',
                'shift_date' => $now->copy()->addDays(2)->toDateString(),
                'start_time' => '07:00:00',
                'end_time' => '15:00:00',
                'duration_hours' => 8,
                'base_rate' => 2300, // $23/hr
                'urgency_level' => 'normal',
                'required_workers' => 2,
                'required_skills' => json_encode(['Elderly Care', 'First Aid', 'Medication Administration']),
            ],

            // NORMAL - Cleaning (5 days away)
            [
                'title' => 'Cleaner',
                'description' => 'Office cleaning in the evening after business hours. Must be reliable and detail-oriented.',
                'industry' => 'cleaning',
                'demo_business_name' => 'Sparkle Clean Services',
                'location_address' => 'Portomaso Business Tower',
                'location_city' => 'St. Julian\'s',
                'location_state' => 'Malta',
                'location_country' => 'Malta',
                'shift_date' => $now->copy()->addDays(5)->toDateString(),
                'start_time' => '18:00:00',
                'end_time' => '22:00:00',
                'duration_hours' => 4,
                'base_rate' => 1500, // $15/hr
                'urgency_level' => 'normal',
                'required_workers' => 2,
                'required_skills' => json_encode(['Commercial Cleaning', 'Attention to Detail']),
            ],
        ];
    }
}
