<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * GLO-003: Add labor_law_rule_id foreign key to compliance_violations
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check existing foreign keys using raw query for MySQL
        $existingForeignKeys = $this->getExistingForeignKeys('compliance_violations');

        Schema::table('compliance_violations', function (Blueprint $table) use ($existingForeignKeys) {
            // Add foreign key constraint to labor_law_rules table if not exists
            if (! in_array('compliance_violations_labor_law_rule_id_foreign', $existingForeignKeys)) {
                // Check if labor_law_rule_id column exists first
                if (Schema::hasColumn('compliance_violations', 'labor_law_rule_id')) {
                    $table->foreign('labor_law_rule_id')
                        ->references('id')
                        ->on('labor_law_rules')
                        ->onDelete('cascade');
                }
            }

            // Add resolved_by foreign key if not exists
            if (! in_array('compliance_violations_resolved_by_foreign', $existingForeignKeys)) {
                // Check if resolved_by column exists first
                if (Schema::hasColumn('compliance_violations', 'resolved_by')) {
                    $table->foreign('resolved_by')
                        ->references('id')
                        ->on('users')
                        ->onDelete('set null');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $existingForeignKeys = $this->getExistingForeignKeys('compliance_violations');

        Schema::table('compliance_violations', function (Blueprint $table) use ($existingForeignKeys) {
            if (in_array('compliance_violations_labor_law_rule_id_foreign', $existingForeignKeys)) {
                $table->dropForeign(['labor_law_rule_id']);
            }
            if (in_array('compliance_violations_resolved_by_foreign', $existingForeignKeys)) {
                $table->dropForeign(['resolved_by']);
            }
        });
    }

    /**
     * Get existing foreign keys for a table (database-agnostic)
     */
    protected function getExistingForeignKeys(string $tableName): array
    {
        $driver = Schema::getConnection()->getDriverName();

        // SQLite doesn't support INFORMATION_SCHEMA, so return empty array
        // The foreign keys will be created fresh when using SQLite (tests)
        if ($driver === 'sqlite') {
            return [];
        }

        // MySQL/MariaDB
        $database = config('database.connections.mysql.database');

        $results = DB::select("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$database, $tableName]);

        return array_map(fn ($row) => $row->CONSTRAINT_NAME, $results);
    }
};
