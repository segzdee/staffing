<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Market Rate Data
 *
 * BIZ-REG-009: Rate Suggestions
 *
 * Stores market rate data for different roles/positions by location
 * Used to provide suggested rates when posting shifts.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('market_rates', function (Blueprint $table) {
            $table->id();

            // Location
            $table->string('country_code', 2);
            $table->string('state_code', 10)->nullable();
            $table->string('city')->nullable();
            $table->string('metro_area')->nullable(); // E.g., "Bay Area", "Greater London"

            // Role/Position
            $table->string('role_category'); // hospitality, retail, warehouse, etc.
            $table->string('role_name'); // Server, Cashier, Picker, etc.

            // Industry
            $table->string('industry')->nullable();

            // Rate data (in cents)
            $table->integer('rate_low_cents'); // 25th percentile
            $table->integer('rate_median_cents'); // 50th percentile
            $table->integer('rate_high_cents'); // 75th percentile
            $table->integer('rate_premium_cents')->nullable(); // 90th percentile

            // Rate modifiers
            $table->decimal('night_shift_multiplier', 3, 2)->default(1.15); // 15% night premium
            $table->decimal('weekend_multiplier', 3, 2)->default(1.10); // 10% weekend premium
            $table->decimal('holiday_multiplier', 3, 2)->default(1.50); // 50% holiday premium
            $table->decimal('urgent_multiplier', 3, 2)->default(1.25); // 25% urgent premium

            // Currency
            $table->string('currency', 3)->default('USD');

            // Experience adjustments
            $table->integer('entry_level_adjustment_cents')->default(0); // Negative for entry-level
            $table->integer('experienced_adjustment_cents')->default(0); // Positive for experienced

            // Data source
            $table->string('data_source')->default('platform'); // platform, bls, indeed, glassdoor
            $table->integer('sample_size')->default(0); // Number of data points
            $table->date('data_collected_at')->nullable();

            // Validity
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index(['country_code', 'state_code', 'city'], 'mr_location_idx');
            $table->index(['role_category', 'role_name'], 'mr_role_idx');
            $table->index(['industry']);
            $table->index(['is_active', 'valid_from']);
        });

        // Seed initial market rates
        $this->seedMarketRates();
    }

    /**
     * Seed initial market rate data.
     */
    protected function seedMarketRates(): void
    {
        $roles = [
            // Hospitality
            ['category' => 'hospitality', 'name' => 'Server', 'low' => 1200, 'median' => 1500, 'high' => 2000],
            ['category' => 'hospitality', 'name' => 'Bartender', 'low' => 1400, 'median' => 1800, 'high' => 2500],
            ['category' => 'hospitality', 'name' => 'Host/Hostess', 'low' => 1100, 'median' => 1400, 'high' => 1800],
            ['category' => 'hospitality', 'name' => 'Busser', 'low' => 1000, 'median' => 1300, 'high' => 1600],
            ['category' => 'hospitality', 'name' => 'Line Cook', 'low' => 1400, 'median' => 1700, 'high' => 2200],
            ['category' => 'hospitality', 'name' => 'Prep Cook', 'low' => 1200, 'median' => 1500, 'high' => 1900],
            ['category' => 'hospitality', 'name' => 'Dishwasher', 'low' => 1000, 'median' => 1300, 'high' => 1600],
            ['category' => 'hospitality', 'name' => 'Barista', 'low' => 1100, 'median' => 1400, 'high' => 1800],

            // Retail
            ['category' => 'retail', 'name' => 'Cashier', 'low' => 1100, 'median' => 1400, 'high' => 1700],
            ['category' => 'retail', 'name' => 'Sales Associate', 'low' => 1200, 'median' => 1500, 'high' => 1900],
            ['category' => 'retail', 'name' => 'Stock Associate', 'low' => 1100, 'median' => 1400, 'high' => 1700],
            ['category' => 'retail', 'name' => 'Visual Merchandiser', 'low' => 1400, 'median' => 1700, 'high' => 2100],
            ['category' => 'retail', 'name' => 'Customer Service Rep', 'low' => 1200, 'median' => 1500, 'high' => 1900],

            // Warehouse & Logistics
            ['category' => 'warehouse', 'name' => 'Picker/Packer', 'low' => 1300, 'median' => 1600, 'high' => 2000],
            ['category' => 'warehouse', 'name' => 'Forklift Operator', 'low' => 1500, 'median' => 1900, 'high' => 2400],
            ['category' => 'warehouse', 'name' => 'Warehouse Associate', 'low' => 1200, 'median' => 1500, 'high' => 1900],
            ['category' => 'warehouse', 'name' => 'Loader/Unloader', 'low' => 1300, 'median' => 1600, 'high' => 2000],
            ['category' => 'warehouse', 'name' => 'Inventory Clerk', 'low' => 1300, 'median' => 1600, 'high' => 2000],

            // Events & Catering
            ['category' => 'events', 'name' => 'Event Server', 'low' => 1400, 'median' => 1800, 'high' => 2400],
            ['category' => 'events', 'name' => 'Event Bartender', 'low' => 1600, 'median' => 2200, 'high' => 3000],
            ['category' => 'events', 'name' => 'Catering Staff', 'low' => 1300, 'median' => 1700, 'high' => 2200],
            ['category' => 'events', 'name' => 'Event Setup', 'low' => 1200, 'median' => 1500, 'high' => 1900],
            ['category' => 'events', 'name' => 'Brand Ambassador', 'low' => 1500, 'median' => 2000, 'high' => 2800],

            // Healthcare Support
            ['category' => 'healthcare', 'name' => 'CNA', 'low' => 1400, 'median' => 1800, 'high' => 2300],
            ['category' => 'healthcare', 'name' => 'Medical Assistant', 'low' => 1500, 'median' => 1900, 'high' => 2400],
            ['category' => 'healthcare', 'name' => 'Patient Transporter', 'low' => 1200, 'median' => 1500, 'high' => 1900],
            ['category' => 'healthcare', 'name' => 'Dietary Aide', 'low' => 1100, 'median' => 1400, 'high' => 1800],

            // Office & Admin
            ['category' => 'office', 'name' => 'Receptionist', 'low' => 1300, 'median' => 1600, 'high' => 2000],
            ['category' => 'office', 'name' => 'Data Entry', 'low' => 1200, 'median' => 1500, 'high' => 1900],
            ['category' => 'office', 'name' => 'Administrative Assistant', 'low' => 1400, 'median' => 1800, 'high' => 2300],

            // Cleaning & Maintenance
            ['category' => 'cleaning', 'name' => 'Janitor', 'low' => 1100, 'median' => 1400, 'high' => 1800],
            ['category' => 'cleaning', 'name' => 'Housekeeper', 'low' => 1200, 'median' => 1500, 'high' => 1900],
            ['category' => 'cleaning', 'name' => 'Commercial Cleaner', 'low' => 1200, 'median' => 1500, 'high' => 1900],

            // Security
            ['category' => 'security', 'name' => 'Security Guard', 'low' => 1400, 'median' => 1700, 'high' => 2200],
            ['category' => 'security', 'name' => 'Event Security', 'low' => 1500, 'median' => 1900, 'high' => 2500],

            // Delivery & Driving
            ['category' => 'delivery', 'name' => 'Delivery Driver', 'low' => 1400, 'median' => 1800, 'high' => 2300],
            ['category' => 'delivery', 'name' => 'Courier', 'low' => 1300, 'median' => 1600, 'high' => 2100],
        ];

        $now = now();

        // Create rates for common US states
        $states = [
            'CA' => 1.20, // 20% higher than baseline
            'NY' => 1.15,
            'WA' => 1.10,
            'TX' => 0.95,
            'FL' => 0.98,
            'IL' => 1.05,
            'PA' => 1.00,
            'OH' => 0.95,
            'GA' => 0.95,
            'NC' => 0.95,
        ];

        foreach ($states as $stateCode => $multiplier) {
            foreach ($roles as $role) {
                \DB::table('market_rates')->insert([
                    'country_code' => 'US',
                    'state_code' => $stateCode,
                    'city' => null,
                    'metro_area' => null,
                    'role_category' => $role['category'],
                    'role_name' => $role['name'],
                    'industry' => null,
                    'rate_low_cents' => (int)($role['low'] * $multiplier),
                    'rate_median_cents' => (int)($role['median'] * $multiplier),
                    'rate_high_cents' => (int)($role['high'] * $multiplier),
                    'rate_premium_cents' => (int)($role['high'] * $multiplier * 1.20),
                    'night_shift_multiplier' => 1.15,
                    'weekend_multiplier' => 1.10,
                    'holiday_multiplier' => 1.50,
                    'urgent_multiplier' => 1.25,
                    'currency' => 'USD',
                    'entry_level_adjustment_cents' => -200,
                    'experienced_adjustment_cents' => 300,
                    'data_source' => 'platform',
                    'sample_size' => rand(50, 500),
                    'data_collected_at' => now()->subDays(rand(1, 30)),
                    'valid_from' => now()->startOfYear(),
                    'valid_until' => now()->endOfYear(),
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_rates');
    }
};
