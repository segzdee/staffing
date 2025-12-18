<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * GLO-002: Tax Jurisdiction Engine - Tax jurisdictions for automated tax calculation
     */
    public function up(): void
    {
        Schema::create('tax_jurisdictions', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2)->comment('ISO 3166-1 alpha-2 country code');
            $table->string('state_code')->nullable()->comment('State/province code for sub-national jurisdictions');
            $table->string('city')->nullable()->comment('City name for city-level tax jurisdictions');
            $table->string('name')->comment('Human-readable jurisdiction name');
            $table->decimal('income_tax_rate', 5, 2)->default(0)->comment('Income tax rate as percentage');
            $table->decimal('social_security_rate', 5, 2)->default(0)->comment('Social security/NI rate as percentage');
            $table->decimal('vat_rate', 5, 2)->default(0)->comment('VAT/GST rate as percentage');
            $table->boolean('vat_reverse_charge')->default(false)->comment('EU B2B reverse charge mechanism');
            $table->decimal('withholding_rate', 5, 2)->default(0)->comment('Withholding tax rate as percentage');
            $table->json('tax_brackets')->nullable()->comment('Progressive tax brackets JSON');
            $table->decimal('tax_free_threshold', 10, 2)->default(0)->comment('Annual tax-free allowance');
            $table->boolean('requires_w9')->default(false)->comment('US W-9 form required');
            $table->boolean('requires_w8ben')->default(false)->comment('US W-8BEN form required for non-residents');
            $table->string('tax_id_format')->nullable()->comment('Regex pattern for tax ID validation');
            $table->string('tax_id_name')->default('Tax ID')->comment('Local name for tax ID (SSN, NI Number, etc.)');
            $table->string('currency_code', 3)->default('USD')->comment('Default currency for this jurisdiction');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique constraint on country+state+city combination
            $table->unique(['country_code', 'state_code', 'city'], 'tax_jurisdictions_unique');

            // Indexes for common lookups
            $table->index('country_code');
            $table->index(['country_code', 'state_code']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_jurisdictions');
    }
};
