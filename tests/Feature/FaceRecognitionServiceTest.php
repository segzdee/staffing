<?php

use App\Models\FaceProfile;
use App\Models\FaceVerificationLog;
use App\Models\User;
use App\Services\FaceRecognitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(FaceRecognitionService::class);
});

describe('FaceRecognitionService', function () {
    describe('isEnabled', function () {
        it('returns true when face recognition is enabled in config', function () {
            config(['face_recognition.enabled' => true]);

            expect($this->service->isEnabled())->toBeTrue();
        });

        it('returns false when face recognition is disabled in config', function () {
            config(['face_recognition.enabled' => false]);

            expect($this->service->isEnabled())->toBeFalse();
        });
    });

    describe('getEnrollmentStatus', function () {
        it('returns not enrolled status when user has no face profile', function () {
            $user = User::factory()->create(['user_type' => 'worker']);

            $status = $this->service->getEnrollmentStatus($user);

            expect($status)
                ->enrolled->toBeFalse()
                ->status->toBe('not_started')
                ->verification_count->toBe(0);
        });

        it('returns enrolled status when user has active face profile', function () {
            $user = User::factory()->create(['user_type' => 'worker']);
            $faceProfile = FaceProfile::create([
                'user_id' => $user->id,
                'face_id' => 'test-face-id',
                'provider' => 'aws',
                'is_enrolled' => true,
                'enrolled_at' => now(),
                'status' => FaceProfile::STATUS_ACTIVE,
                'verification_count' => 5,
            ]);

            $status = $this->service->getEnrollmentStatus($user);

            expect($status)
                ->enrolled->toBeTrue()
                ->status->toBe('active')
                ->verification_count->toBe(5)
                ->provider->toBe('aws');
        });

        it('returns pending status when enrollment is pending', function () {
            $user = User::factory()->create(['user_type' => 'worker']);
            FaceProfile::create([
                'user_id' => $user->id,
                'provider' => 'manual',
                'is_enrolled' => false,
                'status' => FaceProfile::STATUS_PENDING,
            ]);

            $status = $this->service->getEnrollmentStatus($user);

            expect($status)
                ->enrolled->toBeFalse()
                ->status->toBe('pending');
        });
    });

    describe('getVerificationStats', function () {
        it('returns empty stats for user with no verification logs', function () {
            $user = User::factory()->create(['user_type' => 'worker']);

            $stats = $this->service->getVerificationStats($user);

            expect($stats)
                ->total_verifications->toBe(0)
                ->successful->toBe(0)
                ->failed->toBe(0);
        });

        it('calculates correct statistics from verification logs', function () {
            $user = User::factory()->create(['user_type' => 'worker']);

            // Create some verification logs
            FaceVerificationLog::create([
                'user_id' => $user->id,
                'action' => FaceVerificationLog::ACTION_VERIFY_CLOCK_IN,
                'provider' => 'aws',
                'match_result' => true,
                'confidence_score' => 95.5,
                'liveness_passed' => true,
            ]);

            FaceVerificationLog::create([
                'user_id' => $user->id,
                'action' => FaceVerificationLog::ACTION_VERIFY_CLOCK_OUT,
                'provider' => 'aws',
                'match_result' => true,
                'confidence_score' => 92.3,
                'liveness_passed' => true,
            ]);

            FaceVerificationLog::create([
                'user_id' => $user->id,
                'action' => FaceVerificationLog::ACTION_VERIFY_CLOCK_IN,
                'provider' => 'aws',
                'match_result' => false,
                'confidence_score' => 45.0,
                'liveness_passed' => false,
            ]);

            $stats = $this->service->getVerificationStats($user);

            expect($stats['total_verifications'])->toBe(3);
            expect($stats['successful'])->toBe(2);
            expect($stats['failed'])->toBe(1);
            expect($stats['manual_overrides'])->toBe(0);
        });
    });
});

describe('FaceProfile Model', function () {
    it('creates face profile with correct attributes', function () {
        $user = User::factory()->create(['user_type' => 'worker']);

        $profile = FaceProfile::create([
            'user_id' => $user->id,
            'face_id' => 'test-face-123',
            'provider' => 'aws',
            'is_enrolled' => true,
            'enrolled_at' => now(),
            'status' => FaceProfile::STATUS_ACTIVE,
        ]);

        expect($profile)
            ->user_id->toBe($user->id)
            ->face_id->toBe('test-face-123')
            ->provider->toBe('aws')
            ->is_enrolled->toBeTrue()
            ->status->toBe('active');
    });

    it('marks profile as enrolled correctly', function () {
        $user = User::factory()->create(['user_type' => 'worker']);
        $profile = FaceProfile::create([
            'user_id' => $user->id,
            'provider' => 'aws',
            'is_enrolled' => false,
            'status' => FaceProfile::STATUS_PENDING,
        ]);

        $profile->markEnrolled('new-face-id', 'http://example.com/image.jpg');

        $profile->refresh();

        expect($profile)
            ->face_id->toBe('new-face-id')
            ->is_enrolled->toBeTrue()
            ->status->toBe(FaceProfile::STATUS_ACTIVE)
            ->enrolled_at->not->toBeNull()
            ->enrollment_image_url->toBe('http://example.com/image.jpg');
    });

    it('suspends profile with reason', function () {
        $user = User::factory()->create(['user_type' => 'worker']);
        $profile = FaceProfile::create([
            'user_id' => $user->id,
            'provider' => 'aws',
            'is_enrolled' => true,
            'status' => FaceProfile::STATUS_ACTIVE,
        ]);

        $profile->suspend('Suspicious activity detected');

        $profile->refresh();

        expect($profile)
            ->status->toBe(FaceProfile::STATUS_SUSPENDED)
            ->notes->toBe('Suspicious activity detected');
    });

    it('records verification and updates statistics', function () {
        $user = User::factory()->create(['user_type' => 'worker']);
        $profile = FaceProfile::create([
            'user_id' => $user->id,
            'provider' => 'aws',
            'is_enrolled' => true,
            'status' => FaceProfile::STATUS_ACTIVE,
            'verification_count' => 0,
            'avg_confidence' => null,
        ]);

        $profile->recordVerification(95.0, true);
        $profile->refresh();

        expect($profile->verification_count)->toBe(1);
        expect($profile->avg_confidence)->toBe(95.0);
        expect($profile->last_verified_at)->not->toBeNull();

        $profile->recordVerification(85.0, true);
        $profile->refresh();

        expect($profile->verification_count)->toBe(2);
        expect($profile->avg_confidence)->toBe(90.0); // (95 + 85) / 2
    });

    it('checks if profile is ready for verification', function () {
        $user = User::factory()->create(['user_type' => 'worker']);

        // Not ready - not enrolled
        $profile1 = FaceProfile::create([
            'user_id' => $user->id,
            'provider' => 'aws',
            'is_enrolled' => false,
            'status' => FaceProfile::STATUS_PENDING,
        ]);
        expect($profile1->isReadyForVerification())->toBeFalse();

        // Clean up for next test
        $profile1->delete();

        // Not ready - suspended
        $profile2 = FaceProfile::create([
            'user_id' => $user->id,
            'face_id' => 'face-123',
            'provider' => 'aws',
            'is_enrolled' => true,
            'status' => FaceProfile::STATUS_SUSPENDED,
        ]);
        expect($profile2->isReadyForVerification())->toBeFalse();

        // Clean up for next test
        $profile2->delete();

        // Ready
        $profile3 = FaceProfile::create([
            'user_id' => $user->id,
            'face_id' => 'face-456',
            'provider' => 'aws',
            'is_enrolled' => true,
            'status' => FaceProfile::STATUS_ACTIVE,
        ]);
        expect($profile3->isReadyForVerification())->toBeTrue();
    });

    it('uses correct scopes', function () {
        $user1 = User::factory()->create(['user_type' => 'worker']);
        $user2 = User::factory()->create(['user_type' => 'worker']);

        FaceProfile::create([
            'user_id' => $user1->id,
            'provider' => 'aws',
            'is_enrolled' => true,
            'status' => FaceProfile::STATUS_ACTIVE,
        ]);

        FaceProfile::create([
            'user_id' => $user2->id,
            'provider' => 'azure',
            'is_enrolled' => false,
            'status' => FaceProfile::STATUS_PENDING,
        ]);

        expect(FaceProfile::enrolled()->count())->toBe(1);
        expect(FaceProfile::active()->count())->toBe(1);
        expect(FaceProfile::forProvider('aws')->count())->toBe(1);
        expect(FaceProfile::forProvider('azure')->count())->toBe(1);
    });
});

describe('FaceVerificationLog Model', function () {
    it('creates verification log with correct attributes', function () {
        $user = User::factory()->create(['user_type' => 'worker']);

        $log = FaceVerificationLog::create([
            'user_id' => $user->id,
            'action' => FaceVerificationLog::ACTION_VERIFY_CLOCK_IN,
            'provider' => 'aws',
            'confidence_score' => 92.5,
            'liveness_passed' => true,
            'match_result' => true,
            'ip_address' => '192.168.1.1',
        ]);

        expect($log)
            ->user_id->toBe($user->id)
            ->action->toBe('verify_clock_in')
            ->provider->toBe('aws')
            ->confidence_score->toBe(92.5)
            ->liveness_passed->toBeTrue()
            ->match_result->toBeTrue();
    });

    it('logs verification using helper method', function () {
        $user = User::factory()->create(['user_type' => 'worker']);

        $log = FaceVerificationLog::logVerification(
            user: $user,
            action: FaceVerificationLog::ACTION_VERIFY_CLOCK_IN,
            provider: 'aws',
            matchResult: true,
            confidence: 91.0,
            livenessPassed: true
        );

        expect($log)
            ->user_id->toBe($user->id)
            ->action->toBe('verify_clock_in')
            ->match_result->toBeTrue()
            ->confidence_score->toBe(91.0);
    });

    it('logs enrollment using helper method', function () {
        $user = User::factory()->create(['user_type' => 'worker']);

        $log = FaceVerificationLog::logEnrollment(
            user: $user,
            provider: 'aws',
            confidence: 99.0,
            imageUrl: 'http://example.com/face.jpg'
        );

        expect($log)
            ->action->toBe('enroll')
            ->provider->toBe('aws')
            ->confidence_score->toBe(99.0)
            ->source_image_url->toBe('http://example.com/face.jpg');
    });

    it('logs manual override correctly', function () {
        $worker = User::factory()->create(['user_type' => 'worker']);
        $admin = User::factory()->create(['user_type' => 'admin', 'role' => 'admin']);

        // Don't pass shiftId to avoid FK constraint (no shift exists)
        $log = FaceVerificationLog::logManualOverride(
            user: $worker,
            approver: $admin,
            shiftId: null,
            reason: 'Camera malfunction'
        );

        expect($log)
            ->action->toBe('manual_override')
            ->provider->toBe('manual')
            ->match_result->toBeTrue()
            ->fallback_used->toBeTrue()
            ->approved_by->toBe($admin->id)
            ->failure_reason->toBe('Camera malfunction');
    });

    it('calculates confidence level correctly', function () {
        $user = User::factory()->create(['user_type' => 'worker']);

        $log1 = FaceVerificationLog::create([
            'user_id' => $user->id,
            'action' => 'verify_clock_in',
            'provider' => 'aws',
            'confidence_score' => 98.0,
        ]);
        expect($log1->confidence_level)->toBe('Very High');

        $log2 = FaceVerificationLog::create([
            'user_id' => $user->id,
            'action' => 'verify_clock_in',
            'provider' => 'aws',
            'confidence_score' => 87.0,
        ]);
        expect($log2->confidence_level)->toBe('High');

        $log3 = FaceVerificationLog::create([
            'user_id' => $user->id,
            'action' => 'verify_clock_in',
            'provider' => 'aws',
            'confidence_score' => 72.0,
        ]);
        expect($log3->confidence_level)->toBe('Medium');

        $log4 = FaceVerificationLog::create([
            'user_id' => $user->id,
            'action' => 'verify_clock_in',
            'provider' => 'aws',
            'confidence_score' => 55.0,
        ]);
        expect($log4->confidence_level)->toBe('Low');

        $log5 = FaceVerificationLog::create([
            'user_id' => $user->id,
            'action' => 'verify_clock_in',
            'provider' => 'aws',
            'confidence_score' => 30.0,
        ]);
        expect($log5->confidence_level)->toBe('Very Low');

        $log6 = FaceVerificationLog::create([
            'user_id' => $user->id,
            'action' => 'verify_clock_in',
            'provider' => 'aws',
            'confidence_score' => null,
        ]);
        expect($log6->confidence_level)->toBe('Unknown');
    });

    it('uses correct scopes', function () {
        $user = User::factory()->create(['user_type' => 'worker']);

        FaceVerificationLog::create([
            'user_id' => $user->id,
            'action' => FaceVerificationLog::ACTION_VERIFY_CLOCK_IN,
            'provider' => 'aws',
            'match_result' => true,
            'liveness_passed' => true,
        ]);

        FaceVerificationLog::create([
            'user_id' => $user->id,
            'action' => FaceVerificationLog::ACTION_VERIFY_CLOCK_OUT,
            'provider' => 'azure',
            'match_result' => false,
            'liveness_passed' => false,
        ]);

        FaceVerificationLog::create([
            'user_id' => $user->id,
            'action' => FaceVerificationLog::ACTION_MANUAL_OVERRIDE,
            'provider' => 'manual',
            'match_result' => true,
            'fallback_used' => true,
        ]);

        expect(FaceVerificationLog::query()->successful()->count())->toBe(2);
        expect(FaceVerificationLog::query()->failed()->count())->toBe(1);
        expect(FaceVerificationLog::query()->clockIn()->count())->toBe(1);
        expect(FaceVerificationLog::query()->clockOut()->count())->toBe(1);
        expect(FaceVerificationLog::query()->manualOverrides()->count())->toBe(1);
        expect(FaceVerificationLog::query()->forProvider('aws')->count())->toBe(1);
        expect(FaceVerificationLog::query()->livenessVerified()->count())->toBe(1);
    });
});
