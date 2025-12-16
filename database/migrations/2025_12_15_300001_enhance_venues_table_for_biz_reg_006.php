<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BIZ-REG-006: Venue Management Enhancement
 *
 * Adds comprehensive venue management fields including:
 * - Venue type and status
 * - Timezone and geofencing
 * - Worker instructions
 * - Venue settings
 * - Manager assignments
 */
class EnhanceVenuesTableForBizReg006 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('venues', function (Blueprint $table) {
            // Venue type and categorization
            $table->string('type')->default('office')->after('code'); // office, retail, warehouse, restaurant, hotel, event_venue, healthcare, industrial, other
            $table->enum('status', ['active', 'inactive', 'pending'])->default('pending')->after('is_active');

            // Timezone for operating hours
            $table->string('timezone')->default('UTC')->after('country');

            // Geofencing
            $table->integer('geofence_radius')->default(100)->after('longitude'); // in meters
            $table->json('geofence_polygon')->nullable()->after('geofence_radius'); // Optional polygon geofence

            // Manager contact details (separate from venue contact)
            $table->string('manager_name')->nullable()->after('contact_person');
            $table->string('manager_phone')->nullable()->after('manager_name');
            $table->string('manager_email')->nullable()->after('manager_phone');

            // Worker instructions
            $table->text('parking_instructions')->nullable()->after('description');
            $table->text('entrance_instructions')->nullable()->after('parking_instructions');
            $table->text('checkin_instructions')->nullable()->after('entrance_instructions');
            $table->string('dress_code')->nullable()->after('checkin_instructions'); // casual, business_casual, formal, uniform, safety_gear
            $table->text('equipment_provided')->nullable()->after('dress_code'); // JSON array or comma-separated list
            $table->text('equipment_required')->nullable()->after('equipment_provided'); // JSON array or comma-separated list

            // Venue settings
            $table->integer('default_hourly_rate')->nullable()->after('monthly_budget'); // in cents
            $table->boolean('auto_approve_favorites')->default(false)->after('default_hourly_rate');
            $table->boolean('require_checkin_photo')->default(false)->after('auto_approve_favorites');
            $table->boolean('require_checkout_signature')->default(false)->after('require_checkin_photo');
            $table->integer('gps_accuracy_required')->default(50)->after('require_checkout_signature'); // in meters

            // Additional settings as JSON
            $table->json('settings')->nullable()->after('gps_accuracy_required');

            // Venue managers (many-to-many through JSON or separate pivot)
            $table->json('manager_ids')->nullable()->after('settings'); // Array of user IDs who can manage this venue

            // Featured image
            $table->string('image_url')->nullable()->after('manager_ids');

            // Meta information
            $table->timestamp('first_shift_posted_at')->nullable()->after('image_url');
            $table->integer('active_shifts_count')->default(0)->after('first_shift_posted_at');

            // Indexes for new columns
            $table->index('type');
            $table->index('status');
            $table->index('timezone');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['status']);
            $table->dropIndex(['timezone']);

            $table->dropColumn([
                'type',
                'status',
                'timezone',
                'geofence_radius',
                'geofence_polygon',
                'manager_name',
                'manager_phone',
                'manager_email',
                'parking_instructions',
                'entrance_instructions',
                'checkin_instructions',
                'dress_code',
                'equipment_provided',
                'equipment_required',
                'default_hourly_rate',
                'auto_approve_favorites',
                'require_checkin_photo',
                'require_checkout_signature',
                'gps_accuracy_required',
                'settings',
                'manager_ids',
                'image_url',
                'first_shift_posted_at',
                'active_shifts_count',
            ]);
        });
    }
}
