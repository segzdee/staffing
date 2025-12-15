<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agency_performance_scorecards', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('agency_id')->unsigned();

            // Reporting period
            $table->date('period_start');
            $table->date('period_end');
            $table->string('period_type')->default('weekly'); // weekly, monthly, quarterly

            // Performance metrics
            $table->decimal('fill_rate', 5, 2)->default(0.00); // Percentage: 0-100
            $table->decimal('no_show_rate', 5, 2)->default(0.00); // Percentage: 0-100
            $table->decimal('average_worker_rating', 3, 2)->default(0.00); // 0-5.00
            $table->decimal('complaint_rate', 5, 2)->default(0.00); // Percentage: 0-100

            // Raw counts for transparency
            $table->integer('total_shifts_assigned')->default(0);
            $table->integer('shifts_filled')->default(0);
            $table->integer('shifts_unfilled')->default(0);
            $table->integer('no_shows')->default(0);
            $table->integer('complaints_received')->default(0);
            $table->integer('total_ratings')->default(0);
            $table->decimal('total_rating_sum', 8, 2)->default(0.00);

            // Urgent fill metrics
            $table->integer('urgent_fill_requests')->default(0);
            $table->integer('urgent_fills_completed')->default(0);
            $table->decimal('urgent_fill_rate', 5, 2)->default(0.00);
            $table->decimal('average_response_time_minutes', 8, 2)->nullable();

            // Performance status
            $table->enum('status', ['green', 'yellow', 'red'])->default('green');
            $table->json('warnings')->nullable(); // Array of warning messages
            $table->json('flags')->nullable(); // Array of failed metrics

            // Targets
            $table->decimal('target_fill_rate', 5, 2)->default(90.00);
            $table->decimal('target_no_show_rate', 5, 2)->default(3.00);
            $table->decimal('target_average_rating', 3, 2)->default(4.30);
            $table->decimal('target_complaint_rate', 5, 2)->default(2.00);

            // Actions taken
            $table->boolean('warning_sent')->default(false);
            $table->timestamp('warning_sent_at')->nullable();
            $table->boolean('sanction_applied')->default(false);
            $table->string('sanction_type')->nullable(); // warning, fee_increase, suspension
            $table->timestamp('sanction_applied_at')->nullable();
            $table->text('notes')->nullable();

            // Processing metadata
            $table->timestamp('generated_at')->nullable();
            $table->bigInteger('generated_by')->unsigned()->nullable();

            $table->timestamps();

            // Indexes
            $table->index('agency_id');
            $table->index(['period_start', 'period_end']);
            $table->index('status');
            $table->index('generated_at');

            // Composite index for period queries
            $table->index(['agency_id', 'period_start', 'period_end'], 'agency_period_idx');

            // Foreign keys
            $table->foreign('agency_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('generated_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agency_performance_scorecards');
    }
};
