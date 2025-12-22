<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\BusinessProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Worker
        $worker = User::updateOrCreate(
            ['email' => 'worker@test.com'],
            [
                'name' => 'Test Worker',
                'password' => Hash::make('password'),
                'user_type' => 'worker',
                'role' => 'normal',
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        );

        WorkerProfile::updateOrCreate(
            ['user_id' => $worker->id],
            [
                'first_name' => 'Test',
                'last_name' => 'Worker',
                'phone' => '555-0001',
                'onboarding_completed' => true,
                'is_complete' => true,
            ]
        );

        // Business
        $business = User::updateOrCreate(
            ['email' => 'business@test.com'],
            [
                'name' => 'Test Business',
                'password' => Hash::make('password'),
                'user_type' => 'business',
                'role' => 'normal',
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        );

        BusinessProfile::updateOrCreate(
            ['user_id' => $business->id],
            [
                'business_name' => 'Test Company',
                'business_type' => 'restaurant',
                'phone' => '555-0002',
                'onboarding_completed' => true,
                'is_complete' => true,
                'is_verified' => true,
            ]
        );

        // Agency
        User::updateOrCreate(
            ['email' => 'agency@test.com'],
            [
                'name' => 'Test Agency',
                'password' => Hash::make('password'),
                'user_type' => 'agency',
                'role' => 'normal',
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        );

        // Admin
        User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Test Admin',
                'password' => Hash::make('password'),
                'user_type' => 'admin',
                'role' => 'admin',
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        );

        $this->command->info('Test users created/updated successfully!');
        $this->command->info('Worker: worker@test.com / password');
        $this->command->info('Business: business@test.com / password');
        $this->command->info('Agency: agency@test.com / password');
        $this->command->info('Admin: admin@test.com / password');
    }
}
