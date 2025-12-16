<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Minimum Wages by Jurisdiction
 *
 * BIZ-REG-007/009: Minimum Wage Enforcement
 *
 * Stores minimum wage data by jurisdiction for real-time validation
 * when businesses set pay rates.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('minimum_wages', function (Blueprint $table) {
            $table->id();

            // Jurisdiction identification
            $table->string('country_code', 2); // ISO country code
            $table->string('state_code', 10)->nullable(); // State/province code
            $table->string('city')->nullable(); // City-level overrides
            $table->string('jurisdiction_name'); // Human-readable name

            // Wage amounts (stored in cents)
            $table->integer('hourly_rate_cents'); // Standard minimum wage
            $table->integer('tipped_rate_cents')->nullable(); // Tipped employee rate
            $table->integer('youth_rate_cents')->nullable(); // Youth/trainee rate
            $table->integer('overtime_rate_cents')->nullable(); // OT rate if different

            // Currency
            $table->string('currency', 3)->default('USD');

            // Effective dates
            $table->date('effective_date');
            $table->date('expiry_date')->nullable();

            // Rate type and conditions
            $table->string('rate_type')->default('standard'); // standard, tipped, youth, training
            $table->json('conditions')->nullable(); // Special conditions/exemptions

            // Overtime rules
            $table->decimal('overtime_multiplier', 3, 2)->default(1.50); // 1.5x
            $table->integer('overtime_threshold_daily')->nullable(); // Hours per day
            $table->integer('overtime_threshold_weekly')->default(40); // Hours per week

            // Source and verification
            $table->string('source')->nullable(); // government, dol.gov, etc.
            $table->string('source_url')->nullable();
            $table->date('last_verified_at')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_federal')->default(false); // Federal vs state/local

            $table->timestamps();

            // Indexes
            $table->index(['country_code', 'state_code', 'city']);
            $table->index(['country_code', 'effective_date']);
            $table->index(['is_active', 'effective_date']);
            $table->unique(['country_code', 'state_code', 'city', 'rate_type', 'effective_date'], 'mw_jurisdiction_rate_unique');
        });

        // Seed common minimum wages
        $this->seedMinimumWages();
    }

    /**
     * Seed initial minimum wage data.
     */
    protected function seedMinimumWages(): void
    {
        $minimumWages = [
            // United States - Federal
            [
                'country_code' => 'US',
                'state_code' => null,
                'city' => null,
                'jurisdiction_name' => 'United States (Federal)',
                'hourly_rate_cents' => 725, // $7.25
                'tipped_rate_cents' => 213, // $2.13
                'currency' => 'USD',
                'effective_date' => '2009-07-24',
                'is_federal' => true,
                'source' => 'U.S. Department of Labor',
                'source_url' => 'https://www.dol.gov/agencies/whd/minimum-wage',
            ],
            // US States with higher minimums
            [
                'country_code' => 'US',
                'state_code' => 'CA',
                'city' => null,
                'jurisdiction_name' => 'California',
                'hourly_rate_cents' => 1600, // $16.00
                'tipped_rate_cents' => 1600,
                'currency' => 'USD',
                'effective_date' => '2024-01-01',
                'source' => 'CA DIR',
            ],
            [
                'country_code' => 'US',
                'state_code' => 'NY',
                'city' => null,
                'jurisdiction_name' => 'New York',
                'hourly_rate_cents' => 1500, // $15.00
                'tipped_rate_cents' => 1250,
                'currency' => 'USD',
                'effective_date' => '2024-01-01',
                'source' => 'NY DOL',
            ],
            [
                'country_code' => 'US',
                'state_code' => 'WA',
                'city' => null,
                'jurisdiction_name' => 'Washington',
                'hourly_rate_cents' => 1628, // $16.28
                'tipped_rate_cents' => 1628,
                'currency' => 'USD',
                'effective_date' => '2024-01-01',
                'source' => 'WA L&I',
            ],
            [
                'country_code' => 'US',
                'state_code' => 'TX',
                'city' => null,
                'jurisdiction_name' => 'Texas',
                'hourly_rate_cents' => 725, // $7.25 (federal)
                'tipped_rate_cents' => 213,
                'currency' => 'USD',
                'effective_date' => '2009-07-24',
                'source' => 'TX TWC',
            ],
            [
                'country_code' => 'US',
                'state_code' => 'FL',
                'city' => null,
                'jurisdiction_name' => 'Florida',
                'hourly_rate_cents' => 1300, // $13.00
                'tipped_rate_cents' => 900,
                'currency' => 'USD',
                'effective_date' => '2024-09-30',
                'source' => 'FL DEO',
            ],
            // City-level examples
            [
                'country_code' => 'US',
                'state_code' => 'CA',
                'city' => 'San Francisco',
                'jurisdiction_name' => 'San Francisco, CA',
                'hourly_rate_cents' => 1807, // $18.07
                'tipped_rate_cents' => 1807,
                'currency' => 'USD',
                'effective_date' => '2024-07-01',
                'source' => 'SF OLSE',
            ],
            [
                'country_code' => 'US',
                'state_code' => 'WA',
                'city' => 'Seattle',
                'jurisdiction_name' => 'Seattle, WA',
                'hourly_rate_cents' => 1950, // $19.50
                'tipped_rate_cents' => 1950,
                'currency' => 'USD',
                'effective_date' => '2024-01-01',
                'source' => 'Seattle OLS',
            ],
            [
                'country_code' => 'US',
                'state_code' => 'NY',
                'city' => 'New York City',
                'jurisdiction_name' => 'New York City, NY',
                'hourly_rate_cents' => 1600, // $16.00
                'tipped_rate_cents' => 1600,
                'currency' => 'USD',
                'effective_date' => '2024-01-01',
                'source' => 'NYC Consumer Affairs',
            ],
            // United Kingdom
            [
                'country_code' => 'GB',
                'state_code' => null,
                'city' => null,
                'jurisdiction_name' => 'United Kingdom',
                'hourly_rate_cents' => 1142, // GBP 11.42 (stored as cents)
                'youth_rate_cents' => 842, // 18-20 rate
                'currency' => 'GBP',
                'effective_date' => '2024-04-01',
                'source' => 'UK Government',
                'source_url' => 'https://www.gov.uk/national-minimum-wage-rates',
            ],
            // European Union examples
            [
                'country_code' => 'DE',
                'state_code' => null,
                'city' => null,
                'jurisdiction_name' => 'Germany',
                'hourly_rate_cents' => 1241, // EUR 12.41
                'currency' => 'EUR',
                'effective_date' => '2024-01-01',
                'source' => 'German Federal Government',
            ],
            [
                'country_code' => 'FR',
                'state_code' => null,
                'city' => null,
                'jurisdiction_name' => 'France',
                'hourly_rate_cents' => 1178, // EUR 11.78 (SMIC)
                'currency' => 'EUR',
                'effective_date' => '2024-01-01',
                'source' => 'French Government',
            ],
            [
                'country_code' => 'NL',
                'state_code' => null,
                'city' => null,
                'jurisdiction_name' => 'Netherlands',
                'hourly_rate_cents' => 1316, // EUR 13.16
                'currency' => 'EUR',
                'effective_date' => '2024-01-01',
                'source' => 'Dutch Government',
            ],
            // Australia
            [
                'country_code' => 'AU',
                'state_code' => null,
                'city' => null,
                'jurisdiction_name' => 'Australia',
                'hourly_rate_cents' => 2333, // AUD 23.33
                'currency' => 'AUD',
                'effective_date' => '2024-07-01',
                'source' => 'Fair Work Commission',
            ],
            // Canada
            [
                'country_code' => 'CA',
                'state_code' => null,
                'city' => null,
                'jurisdiction_name' => 'Canada (Federal)',
                'hourly_rate_cents' => 1765, // CAD 17.65
                'currency' => 'CAD',
                'effective_date' => '2024-04-01',
                'source' => 'Government of Canada',
                'is_federal' => true,
            ],
            [
                'country_code' => 'CA',
                'state_code' => 'ON',
                'city' => null,
                'jurisdiction_name' => 'Ontario, Canada',
                'hourly_rate_cents' => 1655, // CAD 16.55
                'currency' => 'CAD',
                'effective_date' => '2024-10-01',
                'source' => 'Ontario Ministry of Labour',
            ],
            [
                'country_code' => 'CA',
                'state_code' => 'BC',
                'city' => null,
                'jurisdiction_name' => 'British Columbia, Canada',
                'hourly_rate_cents' => 1762, // CAD 17.62
                'currency' => 'CAD',
                'effective_date' => '2024-06-01',
                'source' => 'BC Employment Standards',
            ],
        ];

        $now = now();
        foreach ($minimumWages as $wage) {
            $wage['is_active'] = true;
            $wage['is_federal'] = $wage['is_federal'] ?? false;
            $wage['overtime_multiplier'] = 1.50;
            $wage['overtime_threshold_weekly'] = 40;
            $wage['created_at'] = $now;
            $wage['updated_at'] = $now;

            \DB::table('minimum_wages')->insert($wage);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('minimum_wages');
    }
};
