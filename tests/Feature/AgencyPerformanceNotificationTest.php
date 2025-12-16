<?php

/**
 * AGY-005: Agency Performance Notification System Tests
 *
 * Tests for the complete performance notification system including:
 * - Notification type determination
 * - Email template rendering
 * - Escalation workflow
 * - Acknowledgment handling
 */

use App\Jobs\EscalateUnacknowledgedNotifications;
use App\Jobs\GenerateAgencyScorecards;
use App\Models\AgencyPerformanceNotification;
use App\Models\AgencyPerformanceScorecard;
use App\Models\AgencyProfile;
use App\Models\User;
use App\Notifications\Agency\PerformanceYellowWarningNotification;
use App\Notifications\Agency\PerformanceRedAlertNotification;
use App\Notifications\Agency\PerformanceFeeIncreaseNotification;
use App\Notifications\Agency\PerformanceSuspensionNotification;
use App\Notifications\Agency\PerformanceImprovementNotification;
use App\Services\AgencyPerformanceNotificationService;
use Tests\Traits\DatabaseMigrationsWithTransactions;
use Illuminate\Support\Facades\Notification;

// Use hybrid approach: migrations run once, transactions for isolation
uses(DatabaseMigrationsWithTransactions::class);

beforeEach(function () {
    // Create an agency user with profile
    $this->agency = User::factory()->create(['user_type' => 'agency']);
    $this->agencyProfile = AgencyProfile::factory()->create([
        'user_id' => $this->agency->id,
        'commission_rate' => 10.00,
    ]);

    // Create an admin user
    $this->admin = User::factory()->create(['role' => 'admin']);

    // Initialize the notification service
    $this->notificationService = new AgencyPerformanceNotificationService();
});

describe('Notification Type Determination', function () {

    it('returns yellow_warning when status changes from green to yellow', function () {
        $scorecard = AgencyPerformanceScorecard::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'yellow',
            'period_start' => now()->subWeek()->startOfWeek(),
            'period_end' => now()->subWeek()->endOfWeek(),
        ]);

        $type = $this->notificationService->determineNotificationType($scorecard, 'green');

        expect($type)->toBe(AgencyPerformanceNotification::TYPE_YELLOW_WARNING);
    });

    it('returns red_alert when status changes from yellow to red', function () {
        $scorecard = AgencyPerformanceScorecard::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'red',
            'period_start' => now()->subWeek()->startOfWeek(),
            'period_end' => now()->subWeek()->endOfWeek(),
        ]);

        $type = $this->notificationService->determineNotificationType($scorecard, 'yellow');

        expect($type)->toBe(AgencyPerformanceNotification::TYPE_RED_ALERT);
    });

    it('returns red_alert when status changes from green to red', function () {
        $scorecard = AgencyPerformanceScorecard::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'red',
            'period_start' => now()->subWeek()->startOfWeek(),
            'period_end' => now()->subWeek()->endOfWeek(),
        ]);

        $type = $this->notificationService->determineNotificationType($scorecard, 'green');

        expect($type)->toBe(AgencyPerformanceNotification::TYPE_RED_ALERT);
    });

    it('returns improvement when status improves from red to yellow', function () {
        $scorecard = AgencyPerformanceScorecard::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'yellow',
            'period_start' => now()->subWeek()->startOfWeek(),
            'period_end' => now()->subWeek()->endOfWeek(),
        ]);

        $type = $this->notificationService->determineNotificationType($scorecard, 'red');

        expect($type)->toBe(AgencyPerformanceNotification::TYPE_IMPROVEMENT);
    });

    it('returns improvement when status improves from red to green', function () {
        $scorecard = AgencyPerformanceScorecard::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'green',
            'period_start' => now()->subWeek()->startOfWeek(),
            'period_end' => now()->subWeek()->endOfWeek(),
        ]);

        $type = $this->notificationService->determineNotificationType($scorecard, 'red');

        expect($type)->toBe(AgencyPerformanceNotification::TYPE_IMPROVEMENT);
    });

    it('returns suspension when sanction_type is suspension', function () {
        $scorecard = AgencyPerformanceScorecard::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'red',
            'sanction_type' => 'suspension',
            'sanction_applied' => true,
            'period_start' => now()->subWeek()->startOfWeek(),
            'period_end' => now()->subWeek()->endOfWeek(),
        ]);

        $type = $this->notificationService->determineNotificationType($scorecard, 'red');

        expect($type)->toBe(AgencyPerformanceNotification::TYPE_SUSPENSION);
    });

    it('returns fee_increase when sanction_type is fee_increase', function () {
        $scorecard = AgencyPerformanceScorecard::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'red',
            'sanction_type' => 'fee_increase',
            'sanction_applied' => true,
            'period_start' => now()->subWeek()->startOfWeek(),
            'period_end' => now()->subWeek()->endOfWeek(),
        ]);

        $type = $this->notificationService->determineNotificationType($scorecard, 'red');

        expect($type)->toBe(AgencyPerformanceNotification::TYPE_FEE_INCREASE);
    });

    it('returns null when status stays green', function () {
        $scorecard = AgencyPerformanceScorecard::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'green',
            'period_start' => now()->subWeek()->startOfWeek(),
            'period_end' => now()->subWeek()->endOfWeek(),
        ]);

        $type = $this->notificationService->determineNotificationType($scorecard, 'green');

        expect($type)->toBeNull();
    });

});

describe('Duplicate Notification Prevention', function () {

    it('prevents duplicate notifications of same type within 7 days', function () {
        // Create existing notification
        AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'notification_type' => AgencyPerformanceNotification::TYPE_YELLOW_WARNING,
            'scorecard_id' => 1,
            'created_at' => now()->subDays(3),
        ]);

        $shouldSend = $this->notificationService->shouldSendNotification(
            $this->agency->id,
            AgencyPerformanceNotification::TYPE_YELLOW_WARNING,
            1 // Same scorecard
        );

        expect($shouldSend)->toBeFalse();
    });

    it('allows notification for different scorecard', function () {
        // Create existing notification for scorecard 1
        AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'notification_type' => AgencyPerformanceNotification::TYPE_YELLOW_WARNING,
            'scorecard_id' => 1,
            'created_at' => now()->subDays(3),
        ]);

        $shouldSend = $this->notificationService->shouldSendNotification(
            $this->agency->id,
            AgencyPerformanceNotification::TYPE_YELLOW_WARNING,
            2 // Different scorecard
        );

        expect($shouldSend)->toBeTrue();
    });

    it('allows notification after 7 days', function () {
        // Create old notification
        AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'notification_type' => AgencyPerformanceNotification::TYPE_YELLOW_WARNING,
            'created_at' => now()->subDays(8),
        ]);

        $shouldSend = $this->notificationService->shouldSendNotification(
            $this->agency->id,
            AgencyPerformanceNotification::TYPE_YELLOW_WARNING
        );

        expect($shouldSend)->toBeTrue();
    });

});

describe('Action Plan Generation', function () {

    it('generates action items for failed metrics', function () {
        $scorecard = AgencyPerformanceScorecard::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'yellow',
            'fill_rate' => 80.00,
            'target_fill_rate' => 90.00,
            'no_show_rate' => 5.00,
            'target_no_show_rate' => 3.00,
            'period_start' => now()->subWeek()->startOfWeek(),
            'period_end' => now()->subWeek()->endOfWeek(),
        ]);

        $actionPlan = $this->notificationService->generateActionPlan(
            $scorecard,
            AgencyPerformanceNotification::TYPE_YELLOW_WARNING
        );

        expect($actionPlan)->toHaveKey('items')
            ->and($actionPlan['items'])->toBeArray()
            ->and(count($actionPlan['items']))->toBeGreaterThan(0);
    });

    it('includes type-specific actions for red alert', function () {
        $scorecard = AgencyPerformanceScorecard::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'red',
            'fill_rate' => 70.00,
            'target_fill_rate' => 90.00,
            'period_start' => now()->subWeek()->startOfWeek(),
            'period_end' => now()->subWeek()->endOfWeek(),
        ]);

        $actionPlan = $this->notificationService->generateActionPlan(
            $scorecard,
            AgencyPerformanceNotification::TYPE_RED_ALERT
        );

        expect($actionPlan['items'])->toContain('URGENT: Acknowledge this alert within 24 hours');
    });

});

describe('Notification Processing', function () {

    it('creates notification record when processing scorecard', function () {
        Notification::fake();

        $scorecard = AgencyPerformanceScorecard::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'yellow',
            'fill_rate' => 80.00,
            'target_fill_rate' => 90.00,
            'period_start' => now()->subWeek()->startOfWeek(),
            'period_end' => now()->subWeek()->endOfWeek(),
        ]);

        // Create previous green scorecard
        AgencyPerformanceScorecard::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'green',
            'period_start' => now()->subWeeks(2)->startOfWeek(),
            'period_end' => now()->subWeeks(2)->endOfWeek(),
        ]);

        $result = $this->notificationService->processScorecard($scorecard);

        expect($result['notifications_sent'])->not->toBeEmpty();

        $notification = AgencyPerformanceNotification::where('agency_id', $this->agency->id)
            ->where('notification_type', AgencyPerformanceNotification::TYPE_YELLOW_WARNING)
            ->first();

        expect($notification)->not->toBeNull()
            ->and($notification->scorecard_id)->toBe($scorecard->id)
            ->and($notification->severity)->toBe(AgencyPerformanceNotification::SEVERITY_WARNING);
    });

    it('sends Laravel notification when processing', function () {
        Notification::fake();

        $scorecard = AgencyPerformanceScorecard::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'yellow',
            'fill_rate' => 80.00,
            'target_fill_rate' => 90.00,
            'period_start' => now()->subWeek()->startOfWeek(),
            'period_end' => now()->subWeek()->endOfWeek(),
        ]);

        // Create previous green scorecard
        AgencyPerformanceScorecard::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'green',
            'period_start' => now()->subWeeks(2)->startOfWeek(),
            'period_end' => now()->subWeeks(2)->endOfWeek(),
        ]);

        $this->notificationService->processScorecard($scorecard);

        Notification::assertSentTo(
            $this->agency,
            PerformanceYellowWarningNotification::class
        );
    });

});

describe('Consecutive Status Tracking', function () {

    it('correctly counts consecutive red weeks', function () {
        // Create 3 consecutive red scorecards
        foreach ([3, 2, 1] as $weeksAgo) {
            AgencyPerformanceScorecard::factory()->create([
                'agency_id' => $this->agency->id,
                'status' => 'red',
                'period_start' => now()->subWeeks($weeksAgo)->startOfWeek(),
                'period_end' => now()->subWeeks($weeksAgo)->endOfWeek(),
            ]);
        }

        $counts = $this->notificationService->getConsecutiveStatusCounts(
            $this->agency->id,
            now()->endOfWeek()
        );

        expect($counts['red'])->toBe(3)
            ->and($counts['yellow'])->toBe(0);
    });

    it('stops counting at first non-red scorecard', function () {
        // Create: red, red, yellow, red (should count 2)
        AgencyPerformanceScorecard::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'red',
            'period_start' => now()->subWeeks(1)->startOfWeek(),
            'period_end' => now()->subWeeks(1)->endOfWeek(),
        ]);

        AgencyPerformanceScorecard::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'red',
            'period_start' => now()->subWeeks(2)->startOfWeek(),
            'period_end' => now()->subWeeks(2)->endOfWeek(),
        ]);

        AgencyPerformanceScorecard::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'yellow',
            'period_start' => now()->subWeeks(3)->startOfWeek(),
            'period_end' => now()->subWeeks(3)->endOfWeek(),
        ]);

        AgencyPerformanceScorecard::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'red',
            'period_start' => now()->subWeeks(4)->startOfWeek(),
            'period_end' => now()->subWeeks(4)->endOfWeek(),
        ]);

        $counts = $this->notificationService->getConsecutiveStatusCounts(
            $this->agency->id,
            now()->endOfWeek()
        );

        expect($counts['red'])->toBe(2);
    });

});

describe('Notification Acknowledgment', function () {

    it('allows agency to acknowledge notification', function () {
        $notification = AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'notification_type' => AgencyPerformanceNotification::TYPE_YELLOW_WARNING,
            'requires_acknowledgment' => true,
            'acknowledged' => false,
        ]);

        $result = $this->notificationService->acknowledgeNotification(
            $notification->id,
            $this->agency->id,
            'We will improve our fill rate.'
        );

        expect($result->acknowledged)->toBeTrue()
            ->and($result->acknowledged_at)->not->toBeNull()
            ->and($result->acknowledged_by)->toBe($this->agency->id)
            ->and($result->acknowledgment_notes)->toBe('We will improve our fill rate.');
    });

});

describe('Notification Model', function () {

    it('creates notification with correct severity for yellow warning', function () {
        $notification = AgencyPerformanceNotification::createForAgency(
            $this->agency->id,
            AgencyPerformanceNotification::TYPE_YELLOW_WARNING,
            'Test Title',
            'Test Message'
        );

        expect($notification->severity)->toBe(AgencyPerformanceNotification::SEVERITY_WARNING)
            ->and($notification->requires_acknowledgment)->toBeTrue();
    });

    it('creates notification with critical severity for red alert', function () {
        $notification = AgencyPerformanceNotification::createForAgency(
            $this->agency->id,
            AgencyPerformanceNotification::TYPE_RED_ALERT,
            'Test Title',
            'Test Message'
        );

        expect($notification->severity)->toBe(AgencyPerformanceNotification::SEVERITY_CRITICAL);
    });

    it('creates notification with info severity for improvement', function () {
        $notification = AgencyPerformanceNotification::createForAgency(
            $this->agency->id,
            AgencyPerformanceNotification::TYPE_IMPROVEMENT,
            'Test Title',
            'Test Message'
        );

        expect($notification->severity)->toBe(AgencyPerformanceNotification::SEVERITY_INFO)
            ->and($notification->requires_acknowledgment)->toBeFalse();
    });

    it('correctly identifies overdue notifications', function () {
        $notification = AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'requires_acknowledgment' => true,
            'acknowledged' => false,
            'escalation_due_at' => now()->subHours(1),
        ]);

        expect($notification->is_overdue)->toBeTrue();
    });

    it('calculates hours until escalation', function () {
        $notification = AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'requires_acknowledgment' => true,
            'acknowledged' => false,
            'escalated' => false,
            'escalation_due_at' => now()->addHours(24),
        ]);

        expect($notification->hours_until_escalation)->toBeGreaterThanOrEqual(23)
            ->and($notification->hours_until_escalation)->toBeLessThanOrEqual(24);
    });

});

describe('Escalation Workflow', function () {

    it('marks notification as escalated', function () {
        $notification = AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'requires_acknowledgment' => true,
            'acknowledged' => false,
            'escalated' => false,
        ]);

        $notification->escalate($this->admin->id, 'Agency failed to respond');

        expect($notification->escalated)->toBeTrue()
            ->and($notification->escalated_at)->not->toBeNull()
            ->and($notification->escalated_to)->toBe($this->admin->id)
            ->and($notification->escalation_level)->toBe(1)
            ->and($notification->escalation_reason)->toBe('Agency failed to respond');
    });

    it('increments escalation level on subsequent escalations', function () {
        $notification = AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'escalated' => true,
            'escalation_level' => 1,
        ]);

        $notification->escalate($this->admin->id, 'Second escalation');

        expect($notification->escalation_level)->toBe(2);
    });

    it('records follow-ups correctly', function () {
        $notification = AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'follow_up_count' => 0,
        ]);

        $notification->recordFollowUp();

        expect($notification->follow_up_count)->toBe(1)
            ->and($notification->last_follow_up_at)->not->toBeNull()
            ->and($notification->next_follow_up_at)->not->toBeNull();
    });

});

describe('Appeal Handling', function () {

    it('allows agency to submit appeal', function () {
        $notification = AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'notification_type' => AgencyPerformanceNotification::TYPE_SUSPENSION,
            'appealed' => false,
        ]);

        $notification->submitAppeal('We had exceptional circumstances due to weather.');

        expect($notification->appealed)->toBeTrue()
            ->and($notification->appealed_at)->not->toBeNull()
            ->and($notification->appeal_reason)->toBe('We had exceptional circumstances due to weather.')
            ->and($notification->appeal_status)->toBe(AgencyPerformanceNotification::APPEAL_PENDING);
    });

    it('allows admin to resolve appeal', function () {
        $notification = AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'appealed' => true,
            'appeal_status' => AgencyPerformanceNotification::APPEAL_PENDING,
        ]);

        $notification->resolveAppeal(
            AgencyPerformanceNotification::APPEAL_APPROVED,
            'Appeal approved due to documented weather emergency.'
        );

        expect($notification->appeal_status)->toBe(AgencyPerformanceNotification::APPEAL_APPROVED)
            ->and($notification->appeal_response)->toBe('Appeal approved due to documented weather emergency.')
            ->and($notification->appeal_resolved_at)->not->toBeNull();
    });

});

describe('Admin Review', function () {

    it('records admin review decision', function () {
        $notification = AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'admin_reviewed' => false,
        ]);

        $notification->recordAdminReview(
            $this->admin->id,
            AgencyPerformanceNotification::DECISION_UPHOLD,
            'Reviewed metrics, decision upheld.'
        );

        expect($notification->admin_reviewed)->toBeTrue()
            ->and($notification->admin_reviewed_at)->not->toBeNull()
            ->and($notification->admin_reviewed_by)->toBe($this->admin->id)
            ->and($notification->admin_decision)->toBe(AgencyPerformanceNotification::DECISION_UPHOLD)
            ->and($notification->admin_notes)->toBe('Reviewed metrics, decision upheld.');
    });

});

describe('Notification History', function () {

    it('returns notification history for agency', function () {
        // Create multiple notifications
        AgencyPerformanceNotification::factory()->count(5)->create([
            'agency_id' => $this->agency->id,
        ]);

        $history = $this->notificationService->getNotificationHistory($this->agency->id);

        expect($history)->toHaveCount(5);
    });

    it('returns unacknowledged notifications', function () {
        // Create acknowledged and unacknowledged notifications
        AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'requires_acknowledgment' => true,
            'acknowledged' => true,
        ]);

        AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'requires_acknowledgment' => true,
            'acknowledged' => false,
        ]);

        AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'requires_acknowledgment' => false,
        ]);

        $unacknowledged = $this->notificationService->getUnacknowledgedNotifications($this->agency->id);

        expect($unacknowledged)->toHaveCount(1);
    });

});

describe('Statistics', function () {

    it('calculates response rate statistics', function () {
        // Create notifications with various states
        AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'requires_acknowledgment' => true,
            'acknowledged' => true,
            'sent_at' => now()->subDays(2),
            'acknowledged_at' => now()->subDays(1),
        ]);

        AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'requires_acknowledgment' => true,
            'acknowledged' => false,
            'escalated' => true,
        ]);

        $stats = $this->notificationService->getResponseRateStatistics(30);

        expect($stats['total_notifications'])->toBe(2)
            ->and($stats['acknowledged'])->toBe(1)
            ->and($stats['acknowledgment_rate'])->toBe(50.0)
            ->and($stats['escalated'])->toBe(1);
    });

});

describe('Scopes', function () {

    it('filters by notification type', function () {
        AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'notification_type' => AgencyPerformanceNotification::TYPE_YELLOW_WARNING,
        ]);

        AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'notification_type' => AgencyPerformanceNotification::TYPE_RED_ALERT,
        ]);

        $yellowWarnings = AgencyPerformanceNotification::ofType(
            AgencyPerformanceNotification::TYPE_YELLOW_WARNING
        )->get();

        expect($yellowWarnings)->toHaveCount(1);
    });

    it('filters critical notifications', function () {
        AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'severity' => AgencyPerformanceNotification::SEVERITY_CRITICAL,
        ]);

        AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'severity' => AgencyPerformanceNotification::SEVERITY_WARNING,
        ]);

        $critical = AgencyPerformanceNotification::critical()->get();

        expect($critical)->toHaveCount(1);
    });

    it('filters pending escalation notifications', function () {
        // Should be included
        AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'requires_acknowledgment' => true,
            'acknowledged' => false,
            'escalated' => false,
            'escalation_due_at' => now()->subHour(),
        ]);

        // Should not be included (acknowledged)
        AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'requires_acknowledgment' => true,
            'acknowledged' => true,
            'escalated' => false,
            'escalation_due_at' => now()->subHour(),
        ]);

        // Should not be included (not yet due)
        AgencyPerformanceNotification::factory()->create([
            'agency_id' => $this->agency->id,
            'requires_acknowledgment' => true,
            'acknowledged' => false,
            'escalated' => false,
            'escalation_due_at' => now()->addHour(),
        ]);

        $pending = AgencyPerformanceNotification::pendingEscalation()->get();

        expect($pending)->toHaveCount(1);
    });

});
