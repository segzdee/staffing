<?php

use App\Models\KycVerification;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Notifications\KycApprovedNotification;
use App\Notifications\KycRejectedNotification;
use App\Notifications\KycSubmittedNotification;
use App\Services\KycService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Traits\DatabaseMigrationsWithTransactions;

uses(DatabaseMigrationsWithTransactions::class);

beforeEach(function () {
    Storage::fake('private');
    Notification::fake();
});

describe('WKR-001: KYC Verification', function () {
    describe('KycVerification Model', function () {
        it('creates a verification with all required fields', function () {
            $user = User::factory()->create(['user_type' => 'worker']);

            $verification = KycVerification::create([
                'user_id' => $user->id,
                'status' => KycVerification::STATUS_PENDING,
                'document_type' => KycVerification::DOC_TYPE_PASSPORT,
                'document_country' => 'US',
                'document_front_path' => 'kyc/test/front.jpg',
                'provider' => KycVerification::PROVIDER_MANUAL,
            ]);

            expect($verification)->toBeInstanceOf(KycVerification::class)
                ->and($verification->status)->toBe(KycVerification::STATUS_PENDING)
                ->and($verification->document_type)->toBe(KycVerification::DOC_TYPE_PASSPORT)
                ->and($verification->user_id)->toBe($user->id);
        });

        it('correctly identifies status with helper methods', function () {
            $user = User::factory()->create(['user_type' => 'worker']);

            $pending = KycVerification::factory()->create([
                'user_id' => $user->id,
                'status' => KycVerification::STATUS_PENDING,
            ]);

            $approved = KycVerification::factory()->create([
                'user_id' => $user->id,
                'status' => KycVerification::STATUS_APPROVED,
            ]);

            $rejected = KycVerification::factory()->create([
                'user_id' => $user->id,
                'status' => KycVerification::STATUS_REJECTED,
            ]);

            expect($pending->isPending())->toBeTrue()
                ->and($pending->isApproved())->toBeFalse()
                ->and($approved->isApproved())->toBeTrue()
                ->and($approved->isPending())->toBeFalse()
                ->and($rejected->isRejected())->toBeTrue();
        });

        it('determines if user can retry after rejection', function () {
            $user = User::factory()->create(['user_type' => 'worker']);

            $canRetry = KycVerification::factory()->create([
                'user_id' => $user->id,
                'status' => KycVerification::STATUS_REJECTED,
                'attempt_count' => 1,
                'max_attempts' => 3,
            ]);

            $cannotRetry = KycVerification::factory()->create([
                'user_id' => $user->id,
                'status' => KycVerification::STATUS_REJECTED,
                'attempt_count' => 3,
                'max_attempts' => 3,
            ]);

            expect($canRetry->canRetry())->toBeTrue()
                ->and($cannotRetry->canRetry())->toBeFalse();
        });

        it('detects expiring documents', function () {
            $user = User::factory()->create(['user_type' => 'worker']);

            $expiringSoon = KycVerification::factory()->create([
                'user_id' => $user->id,
                'status' => KycVerification::STATUS_APPROVED,
                'document_expiry' => now()->addDays(15),
            ]);

            $notExpiring = KycVerification::factory()->create([
                'user_id' => $user->id,
                'status' => KycVerification::STATUS_APPROVED,
                'document_expiry' => now()->addDays(60),
            ]);

            expect($expiringSoon->isDocumentExpiringSoon(30))->toBeTrue()
                ->and($notExpiring->isDocumentExpiringSoon(30))->toBeFalse();
        });

        it('generates correct document type display names', function () {
            $user = User::factory()->create(['user_type' => 'worker']);

            $passport = KycVerification::factory()->create([
                'user_id' => $user->id,
                'document_type' => KycVerification::DOC_TYPE_PASSPORT,
            ]);

            $license = KycVerification::factory()->create([
                'user_id' => $user->id,
                'document_type' => KycVerification::DOC_TYPE_DRIVERS_LICENSE,
            ]);

            expect($passport->document_type_name)->toBe('Passport')
                ->and($license->document_type_name)->toBe("Driver's License");
        });
    });

    describe('KycService', function () {
        it('initiates verification for a worker', function () {
            $user = User::factory()->create(['user_type' => 'worker']);
            WorkerProfile::factory()->create(['user_id' => $user->id, 'country' => 'US']);

            $service = new KycService;

            $documentFront = UploadedFile::fake()->image('passport-front.jpg', 800, 600);
            $selfie = UploadedFile::fake()->image('selfie.jpg', 400, 400);

            $result = $service->initiateVerification($user, [
                'document_type' => 'passport',
                'document_country' => 'US',
                'document_front' => $documentFront,
                'selfie' => $selfie,
            ]);

            expect($result['success'])->toBeTrue()
                ->and($result)->toHaveKey('verification_id');

            $verification = KycVerification::find($result['verification_id']);
            expect($verification)->not->toBeNull()
                ->and($verification->status)->toBe(KycVerification::STATUS_PENDING);

            Notification::assertSentTo($user, KycSubmittedNotification::class);
        });

        it('prevents duplicate pending verifications', function () {
            $user = User::factory()->create(['user_type' => 'worker']);
            WorkerProfile::factory()->create(['user_id' => $user->id, 'country' => 'US']);

            KycVerification::factory()->create([
                'user_id' => $user->id,
                'status' => KycVerification::STATUS_PENDING,
            ]);

            $service = new KycService;

            $result = $service->initiateVerification($user, [
                'document_type' => 'passport',
                'document_country' => 'US',
                'document_front' => UploadedFile::fake()->image('front.jpg'),
                'selfie' => UploadedFile::fake()->image('selfie.jpg'),
            ]);

            expect($result['success'])->toBeFalse()
                ->and($result['error'])->toContain('pending');
        });

        it('approves verification and updates user KYC status', function () {
            $user = User::factory()->create([
                'user_type' => 'worker',
                'kyc_verified' => false,
                'kyc_level' => 'none',
            ]);
            $admin = User::factory()->create(['user_type' => 'admin']);

            $verification = KycVerification::factory()->create([
                'user_id' => $user->id,
                'status' => KycVerification::STATUS_IN_REVIEW,
                'document_type' => KycVerification::DOC_TYPE_PASSPORT,
                'selfie_path' => 'kyc/test/selfie.jpg',
                'confidence_score' => 0.96,
            ]);

            $service = new KycService;
            $result = $service->approveVerification($verification, $admin, 'Documents verified');

            expect($result['success'])->toBeTrue();

            $verification->refresh();
            $user->refresh();

            expect($verification->status)->toBe(KycVerification::STATUS_APPROVED)
                ->and($verification->reviewed_by)->toBe($admin->id)
                ->and($verification->reviewer_notes)->toBe('Documents verified')
                ->and($user->kyc_verified)->toBeTrue()
                ->and($user->kyc_level)->not->toBe('none');

            Notification::assertSentTo($user, KycApprovedNotification::class);
        });

        it('rejects verification with reason', function () {
            $user = User::factory()->create(['user_type' => 'worker']);
            $admin = User::factory()->create(['user_type' => 'admin']);

            $verification = KycVerification::factory()->create([
                'user_id' => $user->id,
                'status' => KycVerification::STATUS_IN_REVIEW,
            ]);

            $service = new KycService;
            $result = $service->rejectVerification(
                $verification,
                'Document is blurry and unreadable',
                $admin
            );

            expect($result['success'])->toBeTrue();

            $verification->refresh();

            expect($verification->status)->toBe(KycVerification::STATUS_REJECTED)
                ->and($verification->rejection_reason)->toBe('Document is blurry and unreadable')
                ->and($verification->reviewed_by)->toBe($admin->id);

            Notification::assertSentTo($user, KycRejectedNotification::class);
        });

        it('gets verification requirements for different countries', function () {
            $service = new KycService;

            $usRequirements = $service->getVerificationRequirements('US');
            $defaultRequirements = $service->getVerificationRequirements('XX');

            expect($usRequirements)->toHaveKey('document_types')
                ->and($usRequirements)->toHaveKey('selfie_required')
                ->and($defaultRequirements['document_types'])->toContain('passport');
        });

        it('gets user KYC status correctly', function () {
            $user = User::factory()->create([
                'user_type' => 'worker',
                'kyc_verified' => true,
                'kyc_level' => 'enhanced',
            ]);

            KycVerification::factory()->create([
                'user_id' => $user->id,
                'status' => KycVerification::STATUS_APPROVED,
                'expires_at' => now()->addYear(),
            ]);

            $service = new KycService;
            $status = $service->getKycStatus($user);

            expect($status['is_verified'])->toBeTrue()
                ->and($status['kyc_level'])->toBe('enhanced')
                ->and($status['latest_verification'])->not->toBeNull()
                ->and($status['can_submit'])->toBeFalse();
        });
    });

    describe('Worker KYC Routes', function () {
        it('shows KYC status page for authenticated workers', function () {
            $user = User::factory()->create(['user_type' => 'worker']);
            WorkerProfile::factory()->create(['user_id' => $user->id]);

            $response = $this->actingAs($user)->get('/worker/kyc');

            $response->assertStatus(200);
        });

        it('returns KYC status via API', function () {
            $user = User::factory()->create(['user_type' => 'worker']);
            WorkerProfile::factory()->create(['user_id' => $user->id]);

            $response = $this->actingAs($user)
                ->getJson('/worker/kyc/status');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'is_verified',
                        'kyc_level',
                        'can_submit',
                    ],
                ]);
        });

        it('submits KYC verification with documents', function () {
            $user = User::factory()->create(['user_type' => 'worker']);
            WorkerProfile::factory()->create(['user_id' => $user->id, 'country' => 'US']);

            $response = $this->actingAs($user)
                ->postJson('/worker/kyc', [
                    'document_type' => 'passport',
                    'document_country' => 'US',
                    'document_front' => UploadedFile::fake()->image('front.jpg'),
                    'selfie' => UploadedFile::fake()->image('selfie.jpg'),
                ]);

            $response->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('kyc_verifications', [
                'user_id' => $user->id,
                'status' => 'pending',
                'document_type' => 'passport',
            ]);
        });

        it('validates required fields on submission', function () {
            $user = User::factory()->create(['user_type' => 'worker']);
            WorkerProfile::factory()->create(['user_id' => $user->id]);

            $response = $this->actingAs($user)
                ->postJson('/worker/kyc', [
                    'document_type' => 'passport',
                    // Missing required fields
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['document_country', 'document_front']);
        });
    });

    describe('Admin KYC Review Routes', function () {
        it('returns pending verifications stats for admin', function () {
            $admin = User::factory()->create(['user_type' => 'admin']);

            KycVerification::factory()->count(3)->create([
                'status' => KycVerification::STATUS_PENDING,
            ]);

            $response = $this->actingAs($admin)->getJson('/dashboard/admin/kyc/stats');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'pending',
                        'in_review',
                        'approved_today',
                        'rejected_today',
                    ],
                ]);
        });

        it('allows admin to approve verification via API', function () {
            $admin = User::factory()->create(['user_type' => 'admin']);

            $verification = KycVerification::factory()->create([
                'status' => KycVerification::STATUS_IN_REVIEW,
            ]);

            $response = $this->actingAs($admin)
                ->postJson("/dashboard/admin/kyc/{$verification->id}/approve", [
                    'notes' => 'Documents verified successfully',
                ]);

            $response->assertStatus(200)
                ->assertJson(['success' => true]);

            $verification->refresh();
            expect($verification->status)->toBe(KycVerification::STATUS_APPROVED);
        });

        it('allows admin to reject verification with reason', function () {
            $admin = User::factory()->create(['user_type' => 'admin']);

            $verification = KycVerification::factory()->create([
                'status' => KycVerification::STATUS_IN_REVIEW,
            ]);

            $response = $this->actingAs($admin)
                ->postJson("/dashboard/admin/kyc/{$verification->id}/reject", [
                    'reason' => 'Document appears to be altered',
                ]);

            $response->assertStatus(200)
                ->assertJson(['success' => true]);

            $verification->refresh();
            expect($verification->status)->toBe(KycVerification::STATUS_REJECTED)
                ->and($verification->rejection_reason)->toBe('Document appears to be altered');
        });

        it('requires rejection reason when rejecting', function () {
            $admin = User::factory()->create(['user_type' => 'admin']);

            $verification = KycVerification::factory()->create([
                'status' => KycVerification::STATUS_IN_REVIEW,
            ]);

            $response = $this->actingAs($admin)
                ->postJson("/dashboard/admin/kyc/{$verification->id}/reject", []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['reason']);
        });

        it('supports bulk approval', function () {
            $admin = User::factory()->create(['user_type' => 'admin']);

            $verifications = KycVerification::factory()->count(3)->create([
                'status' => KycVerification::STATUS_IN_REVIEW,
            ]);

            $ids = $verifications->pluck('id')->toArray();

            $response = $this->actingAs($admin)
                ->postJson('/dashboard/admin/kyc/bulk-approve', [
                    'ids' => $ids,
                ]);

            $response->assertStatus(200)
                ->assertJson(['success' => true]);

            foreach ($verifications as $verification) {
                $verification->refresh();
                expect($verification->status)->toBe(KycVerification::STATUS_APPROVED);
            }
        });
    });

    describe('KYC Expiry Management', function () {
        it('finds expiring verifications', function () {
            $user = User::factory()->create(['user_type' => 'worker']);

            KycVerification::factory()->create([
                'user_id' => $user->id,
                'status' => KycVerification::STATUS_APPROVED,
                'document_expiry' => now()->addDays(15),
            ]);

            KycVerification::factory()->create([
                'user_id' => $user->id,
                'status' => KycVerification::STATUS_APPROVED,
                'document_expiry' => now()->addDays(60),
            ]);

            $expiring = KycVerification::expiringSoon(30)->get();

            expect($expiring)->toHaveCount(1);
        });

        it('marks expired verifications', function () {
            $user = User::factory()->create([
                'user_type' => 'worker',
                'kyc_verified' => true,
                'kyc_level' => 'basic',
            ]);

            $verification = KycVerification::factory()->create([
                'user_id' => $user->id,
                'status' => KycVerification::STATUS_APPROVED,
                'document_expiry' => now()->subDay(),
            ]);

            $verification->markExpired();

            $verification->refresh();
            $user->refresh();

            expect($verification->status)->toBe(KycVerification::STATUS_EXPIRED)
                ->and($user->kyc_verified)->toBeFalse()
                ->and($user->kyc_level)->toBe('none');
        });
    });
});
