<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SAF-005: COVID/Health Protocols - Add Health Fields to Shifts Table
     */
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->boolean('requires_health_declaration')->default(false)->after('special_instructions');
            $table->boolean('requires_vaccination')->default(false)->after('requires_health_declaration');
            $table->json('required_vaccinations')->nullable()->after('requires_vaccination'); // ['COVID-19', 'Flu', etc.]
            $table->json('ppe_requirements')->nullable()->after('required_vaccinations'); // ['mask', 'gloves', 'face_shield', 'gown', 'goggles']
            $table->integer('max_capacity')->nullable()->after('ppe_requirements'); // Social distancing limit
            $table->text('health_protocols_notes')->nullable()->after('max_capacity'); // Additional health instructions
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn([
                'requires_health_declaration',
                'requires_vaccination',
                'required_vaccinations',
                'ppe_requirements',
                'max_capacity',
                'health_protocols_notes',
            ]);
        });
    }
};
