<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SAF-005: COVID/Health Protocols - Vaccination Records Table (Optional/Encrypted)
     */
    public function up(): void
    {
        Schema::create('vaccination_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('vaccine_type')->nullable(); // COVID-19, Flu, Hepatitis B, etc.
            $table->date('vaccination_date')->nullable();
            $table->string('document_url')->nullable(); // Encrypted storage path
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('rejection_reason')->nullable();
            $table->string('lot_number')->nullable(); // Vaccine lot number (encrypted)
            $table->string('provider_name')->nullable(); // Healthcare provider/clinic
            $table->date('expiry_date')->nullable(); // For vaccines with expiry
            $table->boolean('is_booster')->default(false);
            $table->integer('dose_number')->nullable(); // e.g., 1st, 2nd, 3rd dose
            $table->timestamps();

            // Indexes for common queries
            $table->index(['user_id', 'vaccine_type']);
            $table->index(['verification_status', 'created_at']);
            $table->index('vaccine_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vaccination_records');
    }
};
