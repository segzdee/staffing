<?php

use App\Models\ImprovementMetric;
use App\Models\ImprovementSuggestion;
use App\Models\User;
use App\Services\ContinuousImprovementService;
use Tests\Traits\DatabaseMigrationsWithTransactions;

uses(DatabaseMigrationsWithTransactions::class);

beforeEach(function () {
    $this->service = app(ContinuousImprovementService::class);

    // Create test users
    $this->worker = User::factory()->create(['user_type' => 'worker']);
    $this->business = User::factory()->create(['user_type' => 'business']);
    $this->admin = User::factory()->create([
        'user_type' => 'admin',
        'role' => 'admin',
        'is_dev_account' => true,
    ]);
});

// ========================================
// SUGGESTION SUBMISSION TESTS
// ========================================

test('user can submit a suggestion', function () {
    $data = [
        'category' => 'feature',
        'priority' => 'medium',
        'title' => 'Add dark mode support',
        'description' => 'It would be great to have a dark mode option for the dashboard to reduce eye strain.',
        'expected_impact' => 'Better user experience for users who prefer dark themes.',
    ];

    $suggestion = $this->service->submitSuggestion($this->worker, $data);

    expect($suggestion)->toBeInstanceOf(ImprovementSuggestion::class);
    expect($suggestion->submitted_by)->toBe($this->worker->id);
    expect($suggestion->category)->toBe('feature');
    expect($suggestion->priority)->toBe('medium');
    expect($suggestion->title)->toBe('Add dark mode support');
    expect($suggestion->status)->toBe(ImprovementSuggestion::STATUS_SUBMITTED);
    expect($suggestion->votes)->toBe(0);
});

test('suggestion is stored in database', function () {
    $data = [
        'category' => 'bug',
        'priority' => 'high',
        'title' => 'Fix login timeout issue',
        'description' => 'Users are getting logged out too quickly after inactivity.',
    ];

    $this->service->submitSuggestion($this->worker, $data);

    $this->assertDatabaseHas('improvement_suggestions', [
        'submitted_by' => $this->worker->id,
        'category' => 'bug',
        'title' => 'Fix login timeout issue',
    ]);
});

// ========================================
// VOTING TESTS
// ========================================

test('user can upvote a suggestion', function () {
    $suggestion = ImprovementSuggestion::factory()->create([
        'submitted_by' => $this->worker->id,
    ]);

    $result = $this->service->voteSuggestion($suggestion, $this->business, 'up');

    expect($result['success'])->toBeTrue();
    expect($result['votes'])->toBe(1);
    expect($result['user_vote'])->toBe('up');
});

test('user can downvote a suggestion', function () {
    $suggestion = ImprovementSuggestion::factory()->create([
        'submitted_by' => $this->worker->id,
    ]);

    $result = $this->service->voteSuggestion($suggestion, $this->business, 'down');

    expect($result['success'])->toBeTrue();
    expect($result['votes'])->toBe(-1);
    expect($result['user_vote'])->toBe('down');
});

test('user cannot vote on their own suggestion', function () {
    $suggestion = ImprovementSuggestion::factory()->create([
        'submitted_by' => $this->worker->id,
    ]);

    $result = $this->service->voteSuggestion($suggestion, $this->worker, 'up');

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toContain('cannot vote on your own suggestion');
});

test('user can change their vote', function () {
    $suggestion = ImprovementSuggestion::factory()->create([
        'submitted_by' => $this->worker->id,
    ]);

    // First vote up
    $this->service->voteSuggestion($suggestion, $this->business, 'up');
    expect($suggestion->fresh()->votes)->toBe(1);

    // Change to down
    $result = $this->service->voteSuggestion($suggestion, $this->business, 'down');
    expect($result['votes'])->toBe(-1);
    expect($result['user_vote'])->toBe('down');
});

test('user can remove their vote by voting same type again', function () {
    $suggestion = ImprovementSuggestion::factory()->create([
        'submitted_by' => $this->worker->id,
    ]);

    // First vote up
    $this->service->voteSuggestion($suggestion, $this->business, 'up');

    // Vote up again to remove
    $result = $this->service->voteSuggestion($suggestion, $this->business, 'up');
    expect($result['votes'])->toBe(0);
    expect($result['user_vote'])->toBeNull();
});

// ========================================
// ADMIN REVIEW TESTS
// ========================================

test('admin can approve a suggestion', function () {
    $suggestion = ImprovementSuggestion::factory()->create([
        'submitted_by' => $this->worker->id,
        'status' => ImprovementSuggestion::STATUS_SUBMITTED,
    ]);

    $updated = $this->service->reviewSuggestion(
        $suggestion,
        ImprovementSuggestion::STATUS_APPROVED,
        'Great idea, we will implement this.'
    );

    expect($updated->status)->toBe(ImprovementSuggestion::STATUS_APPROVED);
    expect($updated->admin_notes)->toBe('Great idea, we will implement this.');
    expect($updated->reviewed_at)->not->toBeNull();
});

test('admin can reject a suggestion with reason', function () {
    $suggestion = ImprovementSuggestion::factory()->create([
        'submitted_by' => $this->worker->id,
        'status' => ImprovementSuggestion::STATUS_SUBMITTED,
    ]);

    $updated = $this->service->reviewSuggestion(
        $suggestion,
        ImprovementSuggestion::STATUS_REJECTED,
        'Not feasible at this time.',
        null,
        'This would require significant infrastructure changes.'
    );

    expect($updated->status)->toBe(ImprovementSuggestion::STATUS_REJECTED);
    expect($updated->rejection_reason)->toBe('This would require significant infrastructure changes.');
});

test('admin can assign suggestion to team member', function () {
    $suggestion = ImprovementSuggestion::factory()->create([
        'submitted_by' => $this->worker->id,
        'status' => ImprovementSuggestion::STATUS_APPROVED,
    ]);

    $updated = $this->service->reviewSuggestion(
        $suggestion,
        ImprovementSuggestion::STATUS_IN_PROGRESS,
        null,
        $this->admin->id
    );

    expect($updated->status)->toBe(ImprovementSuggestion::STATUS_IN_PROGRESS);
    expect($updated->assigned_to)->toBe($this->admin->id);
});

test('completed suggestions get completion timestamp', function () {
    $suggestion = ImprovementSuggestion::factory()->create([
        'submitted_by' => $this->worker->id,
        'status' => ImprovementSuggestion::STATUS_IN_PROGRESS,
    ]);

    $updated = $this->service->reviewSuggestion(
        $suggestion,
        ImprovementSuggestion::STATUS_COMPLETED,
        'Successfully implemented.'
    );

    expect($updated->status)->toBe(ImprovementSuggestion::STATUS_COMPLETED);
    expect($updated->completed_at)->not->toBeNull();
});

// ========================================
// QUERY TESTS
// ========================================

test('can get top voted suggestions', function () {
    // Create suggestions with different vote counts
    $suggestion1 = ImprovementSuggestion::factory()->create(['votes' => 10]);
    $suggestion2 = ImprovementSuggestion::factory()->create(['votes' => 5]);
    $suggestion3 = ImprovementSuggestion::factory()->create(['votes' => 15]);

    $topSuggestions = $this->service->getTopSuggestions(2);

    expect($topSuggestions)->toHaveCount(2);
    expect($topSuggestions->first()->votes)->toBe(15);
    expect($topSuggestions->last()->votes)->toBe(10);
});

test('can get suggestions by category', function () {
    ImprovementSuggestion::factory()->create(['category' => 'feature']);
    ImprovementSuggestion::factory()->create(['category' => 'feature']);
    ImprovementSuggestion::factory()->create(['category' => 'bug']);

    $featureSuggestions = $this->service->getSuggestionsByCategory('feature');

    expect($featureSuggestions)->toHaveCount(2);
    expect($featureSuggestions->every(fn ($s) => $s->category === 'feature'))->toBeTrue();
});

test('can get user suggestions', function () {
    ImprovementSuggestion::factory()->create(['submitted_by' => $this->worker->id]);
    ImprovementSuggestion::factory()->create(['submitted_by' => $this->worker->id]);
    ImprovementSuggestion::factory()->create(['submitted_by' => $this->business->id]);

    $workerSuggestions = $this->service->getUserSuggestions($this->worker);

    expect($workerSuggestions)->toHaveCount(2);
    expect($workerSuggestions->every(fn ($s) => $s->submitted_by === $this->worker->id))->toBeTrue();
});

// ========================================
// METRIC TESTS
// ========================================

test('can record a metric value', function () {
    $metric = $this->service->recordMetric('test_metric', 75.5, 'Test Metric');

    expect($metric->metric_key)->toBe('test_metric');
    expect((float) $metric->current_value)->toBe(75.5);
    expect($metric->name)->toBe('Test Metric');
    expect($metric->measured_at)->not->toBeNull();
});

test('recording metric updates history', function () {
    $metric = ImprovementMetric::factory()->create([
        'metric_key' => 'history_test',
        'current_value' => 50.0,
        'history' => [],
    ]);

    $metric->recordValue(60.0);

    expect((float) $metric->current_value)->toBe(60.0);
    expect($metric->history)->not->toBeEmpty();
    expect((float) $metric->history[0]['value'])->toBe(50.0);
});

test('can get metric trend data', function () {
    $metric = ImprovementMetric::factory()->create([
        'metric_key' => 'trend_test',
        'current_value' => 80.0,
        'history' => [
            ['value' => 70.0, 'recorded_at' => now()->subDays(5)->toIso8601String()],
            ['value' => 75.0, 'recorded_at' => now()->subDays(3)->toIso8601String()],
        ],
    ]);

    $trendData = $this->service->getMetricTrend('trend_test', 30);

    expect($trendData['metric'])->not->toBeNull();
    expect($trendData['trend_data'])->toHaveCount(2);
    expect($trendData['average'])->toBeGreaterThan(0);
});

test('can calculate platform health score', function () {
    $healthScore = $this->service->calculatePlatformHealthScore();

    expect($healthScore)->toHaveKeys(['overall_score', 'grade', 'components', 'weights']);
    expect($healthScore['overall_score'])->toBeGreaterThanOrEqual(0);
    expect($healthScore['overall_score'])->toBeLessThanOrEqual(100);
    expect($healthScore['grade'])->toBeIn(['A', 'B', 'C', 'D', 'F']);
});

// ========================================
// REPORT TESTS
// ========================================

test('can generate improvement report', function () {
    // Create some data for the report
    ImprovementSuggestion::factory()->count(3)->create();
    ImprovementMetric::factory()->count(2)->create();

    $report = $this->service->generateImprovementReport();

    expect($report)->toHaveKeys([
        'generated_at',
        'period',
        'suggestions',
        'metrics',
        'platform_health',
        'top_priorities',
        'recent_completions',
        'trends',
    ]);
    expect($report['suggestions']['total'])->toBe(3);
});

// ========================================
// HTTP ENDPOINT TESTS
// ========================================

test('authenticated user can view suggestions list', function () {
    ImprovementSuggestion::factory()->count(3)->create();

    $response = $this->actingAs($this->worker)
        ->get(route('suggestions.index'));

    $response->assertStatus(200);
    $response->assertViewHas('suggestions');
});

test('authenticated user can submit suggestion', function () {
    $response = $this->actingAs($this->worker)
        ->post(route('suggestions.store'), [
            'category' => 'feature',
            'priority' => 'medium',
            'title' => 'New Feature Request',
            'description' => 'This is a detailed description of the feature request that should be at least 20 characters.',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('improvement_suggestions', [
        'title' => 'New Feature Request',
        'submitted_by' => $this->worker->id,
    ]);
});

test('user can vote on suggestion via API', function () {
    $suggestion = ImprovementSuggestion::factory()->create([
        'submitted_by' => $this->worker->id,
    ]);

    $response = $this->actingAs($this->business)
        ->postJson(route('suggestions.vote', $suggestion), [
            'vote_type' => 'up',
        ]);

    $response->assertStatus(200);
    $response->assertJsonPath('success', true);
    $response->assertJsonPath('data.votes', 1);
});

test('admin can view improvement dashboard', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.improvements.index'));

    $response->assertStatus(200);
    $response->assertViewHas('healthScore');
});

test('admin can update suggestion status', function () {
    $suggestion = ImprovementSuggestion::factory()->create([
        'status' => ImprovementSuggestion::STATUS_SUBMITTED,
    ]);

    $response = $this->actingAs($this->admin)
        ->put(route('admin.improvements.suggestion.update', $suggestion), [
            'status' => ImprovementSuggestion::STATUS_APPROVED,
            'admin_notes' => 'Approved for implementation.',
        ]);

    $response->assertRedirect();
    expect($suggestion->fresh()->status)->toBe(ImprovementSuggestion::STATUS_APPROVED);
});

// ========================================
// MODEL TESTS
// ========================================

test('suggestion has correct status labels', function () {
    $suggestion = ImprovementSuggestion::factory()->create([
        'status' => ImprovementSuggestion::STATUS_IN_PROGRESS,
    ]);

    expect($suggestion->status_label)->toBe('In Progress');
});

test('suggestion has correct priority colors', function () {
    $suggestion = ImprovementSuggestion::factory()->create([
        'priority' => ImprovementSuggestion::PRIORITY_CRITICAL,
    ]);

    expect($suggestion->priority_color)->toContain('red');
});

test('suggestion can check if editable', function () {
    $submitted = ImprovementSuggestion::factory()->create([
        'status' => ImprovementSuggestion::STATUS_SUBMITTED,
    ]);
    $completed = ImprovementSuggestion::factory()->create([
        'status' => ImprovementSuggestion::STATUS_COMPLETED,
    ]);

    expect($submitted->canBeEdited())->toBeTrue();
    expect($completed->canBeEdited())->toBeFalse();
});

test('metric calculates progress percentage', function () {
    $metric = ImprovementMetric::factory()->create([
        'current_value' => 75.0,
        'target_value' => 100.0,
        'baseline_value' => 50.0,
    ]);

    expect($metric->getProgressPercentage())->toBe(50.0);
});

test('metric determines if on target', function () {
    $onTarget = ImprovementMetric::factory()->create([
        'current_value' => 100.0,
        'target_value' => 95.0,
    ]);
    $belowTarget = ImprovementMetric::factory()->create([
        'current_value' => 80.0,
        'target_value' => 95.0,
    ]);

    expect($onTarget->isOnTarget())->toBeTrue();
    expect($belowTarget->isOnTarget())->toBeFalse();
});
