<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Wizard fields to Shift Templates
 *
 * BIZ-REG-009: First Shift Wizard - Template Creation
 *
 * Adds fields for wizard-created templates and quick posting.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shift_templates', function (Blueprint $table) {
            // Venue association
            $table->foreignId('venue_id')->nullable()->after('business_id')->constrained('venues')->nullOnDelete();

            // Creation source
            $table->string('created_via')->default('manual')->after('last_used_at'); // manual, wizard, duplicate, api
            $table->boolean('is_from_first_shift')->default(false)->after('created_via');

            // Quick post settings
            $table->boolean('quick_post_enabled')->default(false)->after('is_from_first_shift');
            $table->integer('default_lead_time_hours')->default(24)->after('quick_post_enabled'); // How far in advance to post

            // Template visibility
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_archived')->default(false);

            // Usage statistics
            $table->integer('successful_fills')->default(0);
            $table->decimal('average_fill_time_hours', 8, 2)->nullable();
            $table->decimal('average_applications', 8, 2)->nullable();

            // Suggested rate tracking
            $table->boolean('used_suggested_rate')->default(false);
            $table->integer('original_suggested_rate_cents')->nullable();

            // Skills and certifications (JSON arrays)
            $table->json('required_skills')->nullable();
            $table->json('preferred_skills')->nullable();
            $table->json('required_certifications')->nullable();

            // Indexes
            $table->index('venue_id');
            $table->index(['business_id', 'is_favorite']);
            $table->index(['business_id', 'is_archived']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_templates', function (Blueprint $table) {
            $table->dropIndex(['venue_id']);
            $table->dropIndex(['business_id', 'is_favorite']);
            $table->dropIndex(['business_id', 'is_archived']);

            $table->dropColumn([
                'venue_id',
                'created_via',
                'is_from_first_shift',
                'quick_post_enabled',
                'default_lead_time_hours',
                'is_favorite',
                'is_archived',
                'successful_fills',
                'average_fill_time_hours',
                'average_applications',
                'used_suggested_rate',
                'original_suggested_rate_cents',
                'required_skills',
                'preferred_skills',
                'required_certifications',
            ]);
        });
    }
};
