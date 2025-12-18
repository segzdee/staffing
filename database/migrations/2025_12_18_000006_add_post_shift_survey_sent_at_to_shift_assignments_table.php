<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QUA-003: Add post_shift_survey_sent_at to shift_assignments
 *
 * Tracks when post-shift survey notification was sent to avoid duplicates.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shift_assignments', function (Blueprint $table) {
            $table->timestamp('post_shift_survey_sent_at')->nullable()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_assignments', function (Blueprint $table) {
            $table->dropColumn('post_shift_survey_sent_at');
        });
    }
};
