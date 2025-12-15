<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBusinessLogicToShiftApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shift_applications', function (Blueprint $table) {
            // ===== SL-002: AI-Powered Worker Matching & Ranking =====

            // Overall match score (0-100)
            $table->decimal('match_score', 5, 2)->default(0.00)->after('status');

            // Component scores (for transparency and debugging)
            $table->decimal('skill_score', 5, 2)->default(0.00)->after('match_score');
            $table->decimal('proximity_score', 5, 2)->default(0.00)->after('skill_score');
            $table->decimal('reliability_score', 5, 2)->default(0.00)->after('proximity_score');
            $table->decimal('rating_score', 5, 2)->default(0.00)->after('reliability_score');
            $table->decimal('recency_score', 5, 2)->default(0.00)->after('rating_score');

            // Ranking position (1 = top match)
            $table->integer('rank_position')->nullable()->after('recency_score');

            // Distance from venue (in km)
            $table->decimal('distance_km', 8, 2)->nullable()->after('rank_position');

            // ===== SL-003: Worker Application & Business Selection =====

            // Priority tier (Gold/Platinum get head start)
            $table->enum('priority_tier', ['bronze', 'silver', 'gold', 'platinum'])->default('bronze')->after('distance_km');

            // Notification tracking
            $table->timestamp('notification_sent_at')->nullable()->after('applied_at');
            $table->timestamp('notification_opened_at')->nullable()->after('notification_sent_at');

            // Business actions
            $table->timestamp('viewed_by_business_at')->nullable()->after('responded_at');
            $table->foreignId('responded_by')->nullable()->after('viewed_by_business_at')->constrained('users')->onDelete('set null');
            $table->text('rejection_reason')->nullable()->after('responded_by');

            // ===== SL-004: Booking Confirmation & Acknowledgment =====

            $table->timestamp('acknowledged_at')->nullable()->after('responded_at');
            $table->timestamp('acknowledgment_required_by')->nullable()->after('acknowledged_at'); // 2 hours from confirmation
            $table->timestamp('reminder_sent_at')->nullable()->after('acknowledgment_required_by'); // If no ack within 2 hours
            $table->timestamp('auto_cancelled_at')->nullable()->after('reminder_sent_at'); // If no ack within 6 hours
            $table->boolean('acknowledgment_late')->default(false)->after('auto_cancelled_at');

            // Business preferences (favorite/blocked)
            $table->boolean('is_favorited')->default(false)->after('acknowledgment_late');
            $table->boolean('is_blocked')->default(false)->after('is_favorited');

            // Application metadata
            $table->string('application_source')->default('mobile_app')->after('is_blocked'); // mobile_app, web, agency, direct_invite
            $table->string('device_type')->nullable()->after('application_source'); // iOS, Android, web
            $table->string('app_version')->nullable()->after('device_type');

            // Indexes
            $table->index('match_score');
            $table->index('rank_position');
            $table->index('priority_tier');
            $table->index('is_favorited');
            $table->index('acknowledged_at');
            $table->index(['shift_id', 'match_score']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shift_applications', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['shift_applications_match_score_index']);
            $table->dropIndex(['shift_applications_rank_position_index']);
            $table->dropIndex(['shift_applications_priority_tier_index']);
            $table->dropIndex(['shift_applications_is_favorited_index']);
            $table->dropIndex(['shift_applications_acknowledged_at_index']);
            $table->dropIndex(['shift_applications_shift_id_match_score_index']);

            // Drop columns
            $table->dropColumn([
                'app_version',
                'device_type',
                'application_source',
                'is_blocked',
                'is_favorited',
                'acknowledgment_late',
                'auto_cancelled_at',
                'reminder_sent_at',
                'acknowledgment_required_by',
                'acknowledged_at',
                'rejection_reason',
                'responded_by',
                'viewed_by_business_at',
                'notification_opened_at',
                'notification_sent_at',
                'priority_tier',
                'distance_km',
                'rank_position',
                'recency_score',
                'rating_score',
                'reliability_score',
                'proximity_score',
                'skill_score',
                'match_score',
            ]);
        });
    }
}
