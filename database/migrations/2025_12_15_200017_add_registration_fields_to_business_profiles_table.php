<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * BIZ-REG-002 & BIZ-REG-003: Additional fields for business registration and profile
     */
    public function up(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            // Legal Information
            $table->string('legal_business_name')->nullable()->after('business_name');
            $table->string('trading_name')->nullable()->after('legal_business_name'); // DBA

            // Business Type Categorization (expanded)
            $table->string('business_category')->nullable()->after('business_type');
            // Categories: restaurant_bar, hotel, event_venue, retail, warehouse, healthcare, corporate, manufacturing, logistics, education, government, non_profit, other

            // Company Size
            $table->enum('company_size', [
                'sole_proprietor',    // 1 person
                'micro',              // 2-9 employees
                'small',              // 10-49 employees
                'medium',             // 50-249 employees
                'large',              // 250-999 employees
                'enterprise'          // 1000+ employees
            ])->nullable()->after('employee_count');

            // Logo
            $table->string('logo_url')->nullable()->after('website');
            $table->string('logo_public_id')->nullable()->after('logo_url'); // Cloudinary public ID

            // Currency & Timezone
            $table->string('default_currency', 3)->default('USD')->after('country');
            $table->string('default_timezone')->nullable()->after('default_currency');

            // Jurisdiction
            $table->string('jurisdiction_country', 2)->nullable()->after('default_timezone');
            $table->string('jurisdiction_state')->nullable()->after('jurisdiction_country');
            $table->string('tax_jurisdiction')->nullable()->after('jurisdiction_state');

            // Email verification for business
            $table->string('work_email')->nullable()->after('phone');
            $table->string('work_email_domain')->nullable()->after('work_email');
            $table->boolean('work_email_verified')->default(false)->after('work_email_domain');
            $table->timestamp('work_email_verified_at')->nullable()->after('work_email_verified');
            $table->string('email_verification_token')->nullable()->after('work_email_verified_at');
            $table->timestamp('email_verification_sent_at')->nullable()->after('email_verification_token');

            // Registration tracking
            $table->enum('registration_source', ['self_service', 'sales_assisted', 'referral', 'partnership', 'import'])->default('self_service')->after('email_verification_sent_at');
            $table->string('sales_rep_name')->nullable()->after('registration_source');
            $table->string('sales_rep_email')->nullable()->after('sales_rep_name');
            $table->string('referral_code_used')->nullable()->after('sales_rep_email');

            // Profile completion tracking
            $table->decimal('profile_completion_percentage', 5, 2)->default(0)->after('is_complete');
            $table->json('profile_completion_details')->nullable()->after('profile_completion_percentage');

            // Primary admin user tracking
            $table->foreignId('primary_admin_user_id')->nullable()->after('user_id');

            // Indexes
            $table->index('work_email_domain');
            $table->index('business_category');
            $table->index('company_size');
            $table->index('registration_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropIndex(['work_email_domain']);
            $table->dropIndex(['business_category']);
            $table->dropIndex(['company_size']);
            $table->dropIndex(['registration_source']);

            $table->dropColumn([
                'legal_business_name',
                'trading_name',
                'business_category',
                'company_size',
                'logo_url',
                'logo_public_id',
                'default_currency',
                'default_timezone',
                'jurisdiction_country',
                'jurisdiction_state',
                'tax_jurisdiction',
                'work_email',
                'work_email_domain',
                'work_email_verified',
                'work_email_verified_at',
                'email_verification_token',
                'email_verification_sent_at',
                'registration_source',
                'sales_rep_name',
                'sales_rep_email',
                'referral_code_used',
                'profile_completion_percentage',
                'profile_completion_details',
                'primary_admin_user_id',
            ]);
        });
    }
};
