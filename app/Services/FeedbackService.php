<?php

namespace App\Services;

use App\Models\BugReport;
use App\Models\FeatureRequest;
use App\Models\Shift;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Notifications\PostShiftSurveyNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * QUA-003: FeedbackService
 *
 * Handles surveys, NPS calculation, feature requests, and bug reports.
 */
class FeedbackService
{
    /**
     * Create a new survey.
     */
    public function createSurvey(array $data): Survey
    {
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']).'-'.Str::random(6);
        }

        // Ensure questions have IDs
        if (! empty($data['questions'])) {
            $data['questions'] = array_map(function ($question, $index) {
                if (empty($question['id'])) {
                    $question['id'] = 'q_'.($index + 1);
                }

                return $question;
            }, $data['questions'], array_keys($data['questions']));
        }

        return Survey::create($data);
    }

    /**
     * Submit a response to a survey.
     */
    public function submitResponse(Survey $survey, User $user, array $answers, ?Shift $shift = null): SurveyResponse
    {
        // Extract NPS score if this is an NPS survey
        $npsScore = null;
        $feedbackText = null;

        if ($survey->isNps()) {
            // NPS surveys typically have a single 0-10 question
            foreach ($answers as $questionId => $answer) {
                if (is_numeric($answer) && $answer >= 0 && $answer <= 10) {
                    $npsScore = (int) $answer;
                    break;
                }
            }
        }

        // Extract feedback text from common feedback fields
        $feedbackFields = ['feedback', 'comments', 'feedback_text', 'additional_comments'];
        foreach ($feedbackFields as $field) {
            if (! empty($answers[$field])) {
                $feedbackText = $answers[$field];
                break;
            }
        }

        return SurveyResponse::create([
            'survey_id' => $survey->id,
            'user_id' => $user->id,
            'shift_id' => $shift?->id,
            'answers' => $answers,
            'nps_score' => $npsScore,
            'feedback_text' => $feedbackText,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Calculate NPS for a survey.
     *
     * NPS = (% Promoters - % Detractors)
     * Promoters: 9-10
     * Passives: 7-8
     * Detractors: 0-6
     *
     * @return array{score: float, promoters: int, passives: int, detractors: int, total: int, promoters_pct: float, passives_pct: float, detractors_pct: float}
     */
    public function calculateNPS(Survey $survey, ?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $query = $survey->responses()->withNpsScore();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $responses = $query->get();
        $total = $responses->count();

        if ($total === 0) {
            return [
                'score' => 0,
                'promoters' => 0,
                'passives' => 0,
                'detractors' => 0,
                'total' => 0,
                'promoters_pct' => 0,
                'passives_pct' => 0,
                'detractors_pct' => 0,
            ];
        }

        $promoters = $responses->filter(fn ($r) => $r->isPromoter())->count();
        $passives = $responses->filter(fn ($r) => $r->isPassive())->count();
        $detractors = $responses->filter(fn ($r) => $r->isDetractor())->count();

        $promotersPct = ($promoters / $total) * 100;
        $detractorsPct = ($detractors / $total) * 100;
        $passivesPct = ($passives / $total) * 100;

        // NPS = Promoters% - Detractors%
        $npsScore = $promotersPct - $detractorsPct;

        return [
            'score' => round($npsScore, 1),
            'promoters' => $promoters,
            'passives' => $passives,
            'detractors' => $detractors,
            'total' => $total,
            'promoters_pct' => round($promotersPct, 1),
            'passives_pct' => round($passivesPct, 1),
            'detractors_pct' => round($detractorsPct, 1),
        ];
    }

    /**
     * Calculate platform-wide NPS across all NPS surveys.
     *
     * @return array{score: float, promoters: int, passives: int, detractors: int, total: int}
     */
    public function calculatePlatformNPS(?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $query = SurveyResponse::query()
            ->withNpsScore()
            ->whereHas('survey', fn ($q) => $q->where('type', Survey::TYPE_NPS));

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $responses = $query->get();
        $total = $responses->count();

        if ($total === 0) {
            return [
                'score' => 0,
                'promoters' => 0,
                'passives' => 0,
                'detractors' => 0,
                'total' => 0,
            ];
        }

        $promoters = $responses->filter(fn ($r) => $r->isPromoter())->count();
        $passives = $responses->filter(fn ($r) => $r->isPassive())->count();
        $detractors = $responses->filter(fn ($r) => $r->isDetractor())->count();

        $promotersPct = ($promoters / $total) * 100;
        $detractorsPct = ($detractors / $total) * 100;

        return [
            'score' => round($promotersPct - $detractorsPct, 1),
            'promoters' => $promoters,
            'passives' => $passives,
            'detractors' => $detractors,
            'total' => $total,
        ];
    }

    /**
     * Get the active post-shift survey.
     */
    public function getPostShiftSurvey(): ?Survey
    {
        return Survey::query()
            ->active()
            ->withinDateRange()
            ->ofType(Survey::TYPE_POST_SHIFT)
            ->first();
    }

    /**
     * Send a post-shift survey notification to a user.
     */
    public function sendPostShiftSurvey(User $user, Shift $shift): void
    {
        $survey = $this->getPostShiftSurvey();

        if (! $survey) {
            Log::warning('No active post-shift survey found', [
                'user_id' => $user->id,
                'shift_id' => $shift->id,
            ]);

            return;
        }

        // Check if user already responded to this survey for this shift
        if ($survey->hasUserRespondedForShift($user->id, $shift->id)) {
            Log::info('User already responded to post-shift survey', [
                'user_id' => $user->id,
                'shift_id' => $shift->id,
                'survey_id' => $survey->id,
            ]);

            return;
        }

        try {
            $user->notify(new PostShiftSurveyNotification($survey, $shift));

            Log::info('Post-shift survey notification sent', [
                'user_id' => $user->id,
                'shift_id' => $shift->id,
                'survey_id' => $survey->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send post-shift survey notification', [
                'user_id' => $user->id,
                'shift_id' => $shift->id,
                'survey_id' => $survey->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get NPS trend over time.
     *
     * @param  string  $period  'daily', 'weekly', 'monthly'
     */
    public function getNPSOverTime(string $period = 'monthly', ?int $surveyId = null, int $limit = 12): Collection
    {
        $dateFormat = match ($period) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
            default => '%Y-%m',
        };

        $query = SurveyResponse::query()
            ->withNpsScore()
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN nps_score >= 9 THEN 1 ELSE 0 END) as promoters'),
                DB::raw('SUM(CASE WHEN nps_score >= 7 AND nps_score <= 8 THEN 1 ELSE 0 END) as passives'),
                DB::raw('SUM(CASE WHEN nps_score <= 6 THEN 1 ELSE 0 END) as detractors')
            )
            ->groupBy('period')
            ->orderBy('period', 'desc')
            ->limit($limit);

        if ($surveyId) {
            $query->where('survey_id', $surveyId);
        }

        return $query->get()->map(function ($item) {
            $promotersPct = $item->total > 0 ? ($item->promoters / $item->total) * 100 : 0;
            $detractorsPct = $item->total > 0 ? ($item->detractors / $item->total) * 100 : 0;

            return [
                'period' => $item->period,
                'nps_score' => round($promotersPct - $detractorsPct, 1),
                'total' => $item->total,
                'promoters' => $item->promoters,
                'passives' => $item->passives,
                'detractors' => $item->detractors,
            ];
        })->reverse()->values();
    }

    /**
     * Get top feature requests by vote count.
     */
    public function getTopFeatureRequests(int $limit = 10): Collection
    {
        return FeatureRequest::query()
            ->open()
            ->popular()
            ->with('user:id,name')
            ->limit($limit)
            ->get();
    }

    /**
     * Vote for a feature request.
     */
    public function voteForFeature(FeatureRequest $request, User $user): bool
    {
        return $request->addVote($user->id);
    }

    /**
     * Remove vote from a feature request.
     */
    public function unvoteFeature(FeatureRequest $request, User $user): bool
    {
        return $request->removeVote($user->id);
    }

    /**
     * Toggle vote for a feature request.
     */
    public function toggleFeatureVote(FeatureRequest $request, User $user): array
    {
        $voted = $request->toggleVote($user->id);

        return [
            'voted' => $voted,
            'vote_count' => $request->fresh()->vote_count,
        ];
    }

    /**
     * Submit a feature request.
     */
    public function submitFeatureRequest(User $user, array $data): FeatureRequest
    {
        return FeatureRequest::create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'category' => $data['category'] ?? FeatureRequest::CATEGORY_FEATURE,
            'status' => FeatureRequest::STATUS_SUBMITTED,
            'vote_count' => 1, // Creator's vote
        ]);
    }

    /**
     * Submit a bug report.
     */
    public function submitBugReport(User $user, array $data): BugReport
    {
        return BugReport::create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'steps_to_reproduce' => $data['steps_to_reproduce'] ?? null,
            'expected_behavior' => $data['expected_behavior'] ?? null,
            'actual_behavior' => $data['actual_behavior'] ?? null,
            'severity' => $data['severity'] ?? BugReport::SEVERITY_MEDIUM,
            'status' => BugReport::STATUS_REPORTED,
            'attachments' => $data['attachments'] ?? null,
            'browser' => $data['browser'] ?? request()->header('User-Agent'),
            'os' => $data['os'] ?? null,
            'app_version' => $data['app_version'] ?? config('app.version'),
        ]);
    }

    /**
     * Get feedback statistics for dashboard.
     */
    public function getFeedbackStats(): array
    {
        $thirtyDaysAgo = now()->subDays(30);

        return [
            'total_survey_responses' => SurveyResponse::count(),
            'responses_last_30_days' => SurveyResponse::where('created_at', '>=', $thirtyDaysAgo)->count(),
            'open_feature_requests' => FeatureRequest::open()->count(),
            'open_bug_reports' => BugReport::open()->count(),
            'critical_bugs' => BugReport::critical()->open()->count(),
            'platform_nps' => $this->calculatePlatformNPS($thirtyDaysAgo)['score'],
        ];
    }

    /**
     * Get surveys available for a user.
     */
    public function getAvailableSurveys(User $user): Collection
    {
        $audience = match (true) {
            $user->isWorker() => Survey::AUDIENCE_WORKERS,
            $user->isBusiness() => Survey::AUDIENCE_BUSINESSES,
            default => Survey::AUDIENCE_ALL,
        };

        return Survey::query()
            ->active()
            ->withinDateRange()
            ->forAudience($audience)
            ->whereNotIn('id', function ($query) use ($user) {
                $query->select('survey_id')
                    ->from('survey_responses')
                    ->where('user_id', $user->id)
                    ->whereNull('shift_id'); // Only exclude non-shift-specific surveys
            })
            ->get();
    }

    /**
     * Get post-shift surveys pending for a user.
     */
    public function getPendingPostShiftSurveys(User $user): Collection
    {
        $survey = $this->getPostShiftSurvey();

        if (! $survey) {
            return collect();
        }

        // Get completed shifts that don't have survey responses
        return $user->completedShifts()
            ->whereNotIn('shifts.id', function ($query) use ($survey, $user) {
                $query->select('shift_id')
                    ->from('survey_responses')
                    ->where('survey_id', $survey->id)
                    ->where('user_id', $user->id)
                    ->whereNotNull('shift_id');
            })
            ->where('shifts.completed_at', '>=', now()->subDays(7)) // Only show surveys for shifts in last 7 days
            ->get();
    }

    /**
     * Create a default NPS survey.
     */
    public function createDefaultNPSSurvey(): Survey
    {
        return $this->createSurvey([
            'name' => 'Net Promoter Score Survey',
            'slug' => 'nps-survey',
            'description' => 'Help us improve by rating your experience',
            'type' => Survey::TYPE_NPS,
            'target_audience' => Survey::AUDIENCE_ALL,
            'is_active' => true,
            'questions' => [
                [
                    'id' => 'nps_score',
                    'type' => 'nps',
                    'text' => 'How likely are you to recommend OvertimeStaff to a friend or colleague?',
                    'required' => true,
                ],
                [
                    'id' => 'feedback',
                    'type' => 'textarea',
                    'text' => 'What is the main reason for your score?',
                    'required' => false,
                ],
            ],
        ]);
    }

    /**
     * Create a default post-shift survey.
     */
    public function createDefaultPostShiftSurvey(): Survey
    {
        return $this->createSurvey([
            'name' => 'Post-Shift Feedback',
            'slug' => 'post-shift-feedback',
            'description' => 'Tell us about your recent shift experience',
            'type' => Survey::TYPE_POST_SHIFT,
            'target_audience' => Survey::AUDIENCE_WORKERS,
            'is_active' => true,
            'questions' => [
                [
                    'id' => 'overall_rating',
                    'type' => 'rating',
                    'text' => 'How would you rate your overall shift experience?',
                    'options' => ['min' => 1, 'max' => 5],
                    'required' => true,
                ],
                [
                    'id' => 'communication',
                    'type' => 'rating',
                    'text' => 'How was communication with the business?',
                    'options' => ['min' => 1, 'max' => 5],
                    'required' => true,
                ],
                [
                    'id' => 'work_environment',
                    'type' => 'rating',
                    'text' => 'How was the work environment?',
                    'options' => ['min' => 1, 'max' => 5],
                    'required' => true,
                ],
                [
                    'id' => 'would_work_again',
                    'type' => 'boolean',
                    'text' => 'Would you work with this business again?',
                    'required' => true,
                ],
                [
                    'id' => 'feedback',
                    'type' => 'textarea',
                    'text' => 'Any additional feedback about your shift?',
                    'required' => false,
                ],
            ],
        ]);
    }
}
