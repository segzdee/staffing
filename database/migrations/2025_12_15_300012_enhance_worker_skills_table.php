<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STAFF-REG-007: Worker Skills Enhancement
 *
 * Enhances the worker_skills table with detailed experience tracking and notes.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('worker_skills', function (Blueprint $table) {
            // Rename proficiency_level to experience_level for clarity
            // and add proper enum values: entry, intermediate, advanced, expert
            // Note: The column already exists, we'll add a new one and migrate data

            // Experience details
            $table->string('experience_level')->default('entry')->after('years_experience');
            $table->text('experience_notes')->nullable()->after('experience_level');

            // Last used tracking
            $table->date('last_used_date')->nullable()->after('experience_notes');

            // Self-assessment and verification
            $table->boolean('self_assessed')->default(true)->after('verified');
            $table->timestamp('verified_at')->nullable()->after('self_assessed');
            $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->onDelete('set null');

            // Skill activation (can be deactivated if certification expires)
            $table->boolean('is_active')->default(true)->after('verified_by');

            // Metadata
            $table->json('metadata')->nullable()->after('is_active');

            // Indexes
            $table->index('experience_level');
            $table->index('is_active');
            $table->index('verified_at');
        });

        // Migrate data from old proficiency_level to new experience_level
        DB::statement("
            UPDATE worker_skills
            SET experience_level = CASE
                WHEN proficiency_level = 'beginner' THEN 'entry'
                WHEN proficiency_level = 'intermediate' THEN 'intermediate'
                WHEN proficiency_level = 'advanced' THEN 'advanced'
                WHEN proficiency_level = 'expert' THEN 'expert'
                ELSE 'entry'
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worker_skills', function (Blueprint $table) {
            $table->dropIndex(['experience_level']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['verified_at']);

            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'experience_level',
                'experience_notes',
                'last_used_date',
                'self_assessed',
                'verified_at',
                'verified_by',
                'is_active',
                'metadata',
            ]);
        });
    }
};
