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
        Schema::create('white_label_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('users')->onDelete('cascade');
            $table->string('subdomain')->unique()->nullable(); // agency-name.overtimestaff.com
            $table->string('custom_domain')->unique()->nullable(); // staffing.agencyname.com
            $table->boolean('custom_domain_verified')->default(false);
            $table->string('brand_name');
            $table->string('logo_url')->nullable();
            $table->string('favicon_url')->nullable();
            $table->string('primary_color')->default('#3B82F6');
            $table->string('secondary_color')->default('#1E40AF');
            $table->string('accent_color')->default('#10B981');
            $table->json('theme_config')->nullable(); // extended theming
            $table->string('support_email')->nullable();
            $table->string('support_phone')->nullable();
            $table->text('custom_css')->nullable();
            $table->text('custom_js')->nullable();
            $table->json('email_templates')->nullable(); // custom email branding
            $table->boolean('hide_powered_by')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('subdomain');
            $table->index('custom_domain');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('white_label_configs');
    }
};
