<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\BusinessProfile;
use App\Models\Shift;
use App\Models\ShiftApplication;
use App\Models\ShiftAssignment;
use App\Models\WorkerBadge;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class OvertimeStaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('🌱 Seeding OvertimeStaff database...');
        $this->command->info('');

        // Create Admin User
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@overtimestaff.com',
            'password' => Hash::make('password'),
            'user_type' => 'admin',
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->command->info('✓ Admin user created');

        // Create 10 Worker Users
        $workers = [];
        for ($i = 1; $i <= 10; $i++) {
            $worker = User::create([
                'name' => "Worker {$i}",
                'email' => "worker{$i}@example.com",
                'password' => Hash::make('password'),
                'user_type' => 'worker',
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => now(),
                'phone' => '+356 99' . str_pad($i, 6, '0', STR_PAD_LEFT),
            ]);

            // Create Worker Profile
            $workerProfile = WorkerProfile::create([
                'user_id' => $worker->id,
                'bio' => "Experienced {$this->getRandomIndustry()} professional with {$i} years of experience. Reliable, punctual, and dedicated to excellent service.",
                'skills' => $this->getRandomSkills(),
                'hourly_rate_min' => 12 + ($i * 2),
                'hourly_rate_max' => 20 + ($i * 2),
                'reliability_score' => rand(75, 100),
                'rating' => rand(40, 50) / 10,
                'total_shifts_completed' => rand(10, 100),
                'total_earnings' => rand(1000, 10000),
                'is_available' => true,
                'willing_to_travel' => rand(0, 1) === 1,
                'max_travel_distance' => rand(10, 50),
                'preferred_industries' => json_encode($this->getRandomIndustries()),
                'location_lat' => 35.8997 + (rand(-50, 50) / 1000), // Malta coords with variance
                'location_lng' => 14.5147 + (rand(-50, 50) / 1000),
            ]);

            // Add some badges to top workers
            if ($i <= 5) {
                WorkerBadge::create([
                    'worker_id' => $worker->id,
                    'badge_name' => 'Top Rated',
                    'badge_description' => 'Maintains 4.5+ star rating',
                    'badge_icon' => 'star',
                    'earned_at' => now()->subDays(rand(10, 60)),
                    'is_active' => true,
                ]);

                if ($i <= 3) {
                    WorkerBadge::create([
                        'worker_id' => $worker->id,
                        'badge_name' => 'Verified',
                        'badge_description' => 'ID and background check completed',
                        'badge_icon' => 'shield-check',
                        'earned_at' => now()->subDays(rand(30, 90)),
                        'is_active' => true,
                    ]);
                }
            }

            $workers[] = $worker;
        }

        $this->command->info('✓ 10 workers created with profiles and badges');

        // Create 5 Business Users
        $businesses = [];
        $businessNames = [
            'Grand Hotel Malta',
            'Mediterranean Bistro',
            'Warehouse Logistics Ltd',
            'Retail Paradise Store',
            'Event Masters Malta'
        ];

        foreach ($businessNames as $index => $businessName) {
            $business = User::create([
                'name' => $businessName,
                'email' => "business" . ($index + 1) . "@example.com",
                'password' => Hash::make('password'),
                'user_type' => 'business',
                'role' => 'user',
                'status' => 'active',
                'email_verified_at' => now(),
                'phone' => '+356 21' . str_pad($index + 1, 6, '0', STR_PAD_LEFT),
            ]);

            // Create Business Profile
            BusinessProfile::create([
                'user_id' => $business->id,
                'company_name' => $businessName,
                'bio' => "Leading {$this->getIndustryForBusiness($businessName)} in Malta. We value professionalism and punctuality.",
                'industry' => $this->getIndustryForBusiness($businessName),
                'company_size' => ['1-10', '11-50', '51-200', '201-500'][rand(0, 3)],
                'registration_number' => 'C' . rand(10000, 99999),
                'tax_number' => 'MT' . rand(10000000, 99999999),
                'location_address' => "123 Main Street, Valletta",
                'location_city' => 'Valletta',
                'location_state' => 'Malta',
                'location_country' => 'Malta',
                'location_lat' => 35.8997 + (rand(-20, 20) / 1000),
                'location_lng' => 14.5147 + (rand(-20, 20) / 1000),
                'website' => strtolower(str_replace(' ', '', $businessName)) . '.com.mt',
                'rating' => rand(40, 50) / 10,
                'total_shifts_posted' => rand(10, 100),
                'total_spent' => rand(5000, 50000),
                'is_verified' => true,
            ]);

            $businesses[] = $business;
        }

        $this->command->info('✓ 5 businesses created with profiles');

        // Create Shifts (past, current, future)
        $shifts = [];

        // Past completed shifts (last 30 days)
        for ($i = 0; $i < 15; $i++) {
            $business = $businesses[array_rand($businesses)];
            $shiftDate = Carbon::now()->subDays(rand(1, 30));

            $shift = $this->createShift($business, $shiftDate, 'completed');
            $shifts[] = $shift;

            // Assign 1-3 workers to completed shifts
            $assignmentCount = rand(1, min(3, $shift->workers_needed));
            for ($j = 0; $j < $assignmentCount; $j++) {
                $worker = $workers[array_rand($workers)];

                $application = ShiftApplication::create([
                    'shift_id' => $shift->id,
                    'worker_id' => $worker->id,
                    'status' => 'accepted',
                    'applied_at' => $shiftDate->copy()->subDays(2),
                    'responded_at' => $shiftDate->copy()->subDays(1),
                    'match_score' => rand(70, 100),
                ]);

                ShiftAssignment::create([
                    'shift_id' => $shift->id,
                    'worker_id' => $worker->id,
                    'status' => 'completed',
                    'assigned_at' => $shiftDate->copy()->subDays(1),
                    'check_in_time' => $shiftDate->copy()->setTime(9, 0),
                    'check_out_time' => $shiftDate->copy()->setTime(17, 0),
                    'gross_hours' => 8.0,
                    'net_hours_worked' => 7.5,
                    'billable_hours' => 7.5,
                    'payment_status' => 'paid',
                ]);
            }
        }

        $this->command->info('✓ 15 past completed shifts created');

        // Current/upcoming shifts (today and next 14 days)
        for ($i = 0; $i < 20; $i++) {
            $business = $businesses[array_rand($businesses)];
            $shiftDate = Carbon::now()->addDays(rand(0, 14));

            $statuses = ['open', 'open', 'open', 'assigned', 'assigned'];
            $status = $statuses[array_rand($statuses)];

            $shift = $this->createShift($business, $shiftDate, $status);
            $shifts[] = $shift;

            // Add applications (3-8 per shift)
            $applicationCount = rand(3, 8);
            for ($j = 0; $j < $applicationCount; $j++) {
                $worker = $workers[array_rand($workers)];

                // Check if worker already applied
                $exists = ShiftApplication::where('shift_id', $shift->id)
                    ->where('worker_id', $worker->id)
                    ->exists();

                if (!$exists) {
                    $appStatus = ($status === 'assigned' && $j < $shift->workers_needed) ? 'accepted' : 'pending';

                    $application = ShiftApplication::create([
                        'shift_id' => $shift->id,
                        'worker_id' => $worker->id,
                        'status' => $appStatus,
                        'applied_at' => now()->subHours(rand(1, 48)),
                        'responded_at' => $appStatus === 'accepted' ? now()->subHours(rand(1, 24)) : null,
                        'match_score' => rand(60, 100),
                    ]);

                    // If assigned, create assignment
                    if ($appStatus === 'accepted') {
                        ShiftAssignment::create([
                            'shift_id' => $shift->id,
                            'worker_id' => $worker->id,
                            'status' => 'assigned',
                            'assigned_at' => now()->subHours(rand(1, 24)),
                            'payment_status' => 'pending',
                        ]);
                    }
                }
            }
        }

        $this->command->info('✓ 20 upcoming shifts created with applications');

        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('  OvertimeStaff Database Seeded!');
        $this->command->info('========================================');
        $this->command->info('Admin: admin@overtimestaff.com / password');
        $this->command->info('Workers: worker1@example.com to worker10@example.com / password');
        $this->command->info('Businesses: business1@example.com to business5@example.com / password');
        $this->command->info('========================================');
    }

    private function createShift($business, $shiftDate, $status)
    {
        $industries = ['hospitality', 'retail', 'warehouse', 'events', 'healthcare'];
        $industry = $industries[array_rand($industries)];

        $titles = [
            'hospitality' => ['Waiter/Waitress', 'Bartender', 'Kitchen Helper', 'Hotel Receptionist'],
            'retail' => ['Sales Associate', 'Cashier', 'Stock Clerk', 'Customer Service Rep'],
            'warehouse' => ['Warehouse Worker', 'Forklift Operator', 'Packer', 'Inventory Clerk'],
            'events' => ['Event Staff', 'Setup Crew', 'Usher', 'Catering Assistant'],
            'healthcare' => ['Nursing Assistant', 'Caregiver', 'Medical Receptionist', 'Cleaner'],
        ];

        $title = $titles[$industry][array_rand($titles[$industry])];

        $startTime = ['08:00:00', '09:00:00', '14:00:00', '18:00:00'][rand(0, 3)];
        $endTime = ['16:00:00', '17:00:00', '22:00:00', '02:00:00'][rand(0, 3)];

        $shift = Shift::create([
            'business_id' => $business->id,
            'title' => $title,
            'description' => "We are looking for reliable {$title} to join our team. Must be punctual and professional.",
            'industry' => $industry,
            'location_address' => '123 Main Street, Valletta',
            'location_city' => 'Valletta',
            'location_state' => 'Malta',
            'location_country' => 'Malta',
            'location_lat' => 35.8997,
            'location_lng' => 14.5147,
            'shift_date' => $shiftDate->format('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_hours' => 8,
            'base_rate' => rand(12, 20),
            'final_rate' => rand(13, 22),
            'workers_needed' => rand(1, 5),
            'filled_workers' => $status === 'assigned' ? rand(1, 3) : 0,
            'status' => $status,
            'urgency_level' => ['normal', 'normal', 'normal', 'urgent'][rand(0, 3)],
            'dress_code' => 'Business casual',
            'parking_info' => 'Street parking available',
            'break_info' => '30-minute lunch break',
            'posted_at' => now()->subDays(rand(1, 5)),
        ]);

        return $shift;
    }

    private function getRandomIndustry()
    {
        $industries = ['hospitality', 'retail', 'warehouse', 'events', 'healthcare'];
        return $industries[array_rand($industries)];
    }

    private function getRandomIndustries()
    {
        $industries = ['hospitality', 'retail', 'warehouse', 'events', 'healthcare'];
        shuffle($industries);
        return array_slice($industries, 0, rand(2, 4));
    }

    private function getRandomSkills()
    {
        $allSkills = [
            'Customer Service', 'Cash Handling', 'POS Systems', 'Food Prep',
            'Bartending', 'Event Setup', 'Cleaning', 'Stocking',
            'Forklift Certified', 'Heavy Lifting', 'Team Player', 'Multilingual'
        ];
        shuffle($allSkills);
        return implode(',', array_slice($allSkills, 0, rand(3, 6)));
    }

    private function getIndustryForBusiness($businessName)
    {
        if (str_contains($businessName, 'Hotel')) return 'hospitality';
        if (str_contains($businessName, 'Bistro') || str_contains($businessName, 'Restaurant')) return 'hospitality';
        if (str_contains($businessName, 'Warehouse')) return 'warehouse';
        if (str_contains($businessName, 'Retail')) return 'retail';
        if (str_contains($businessName, 'Event')) return 'events';
        return 'hospitality';
    }
}
