<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('emergency_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('alert_number')->unique(); // SOS-2024-00001
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('venue_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['sos', 'medical', 'safety', 'harassment', 'other'])->default('sos');
            $table->enum('status', ['active', 'responded', 'resolved', 'false_alarm'])->default('active');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('location_address')->nullable();
            $table->text('message')->nullable();
            $table->json('location_history')->nullable(); // track location over time
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution_notes')->nullable();
            $table->boolean('emergency_services_called')->default(false);
            $table->boolean('emergency_contacts_notified')->default(false);
            $table->timestamps();

            // Indexes for quick lookups
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->index(['shift_id', 'status']);
            $table->index(['venue_id', 'status']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_alerts');
    }
};
