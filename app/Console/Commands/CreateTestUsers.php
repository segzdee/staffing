<?php

namespace App\Console\Commands;

use App\Models\BusinessProfile;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test-users:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or update test users for development/testing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Creating/updating test users...');
        $this->newLine();

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

        $this->info('✅ Worker: worker@test.com / password');

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

        $this->info('✅ Business: business@test.com / password');

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

        $this->info('✅ Agency: agency@test.com / password');

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

        $this->info('✅ Admin: admin@test.com / password');

        $this->newLine();
        $this->info('Test users created/updated successfully!');

        return Command::SUCCESS;
    }
}
