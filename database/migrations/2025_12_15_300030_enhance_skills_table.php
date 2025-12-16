<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STAFF-REG-007: Skills & Certifications Enhancement
 *
 * Enhances the skills table with category, subcategory, and certification requirements.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            // Add category for industry grouping
            $table->string('category')->nullable()->after('industry');
            $table->string('subcategory')->nullable()->after('category');

            // Skill status and ordering
            $table->boolean('is_active')->default(true)->after('description');
            $table->integer('sort_order')->default(0)->after('is_active');

            // Certification requirement
            $table->boolean('requires_certification')->default(false)->after('sort_order');
            $table->json('required_certification_ids')->nullable()->after('requires_certification');

            // Skill metadata
            $table->string('icon')->nullable()->after('required_certification_ids');
            $table->string('color')->nullable()->after('icon');

            // Indexes for efficient queries
            $table->index('category');
            $table->index('subcategory');
            $table->index('is_active');
            $table->index('requires_certification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['subcategory']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['requires_certification']);

            $table->dropColumn([
                'category',
                'subcategory',
                'is_active',
                'sort_order',
                'requires_certification',
                'required_certification_ids',
                'icon',
                'color',
            ]);
        });
    }
};
