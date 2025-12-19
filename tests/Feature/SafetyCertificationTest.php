<?php

use App\Models\SafetyCertification;
use App\Models\Shift;
use App\Models\ShiftCertificationRequirement;
use App\Models\User;
use App\Models\WorkerCertification;
use App\Services\CertificationService;

beforeEach(function () {
    $this->certificationService = app(CertificationService::class);
});

describe('SafetyCertification Model', function () {
    test('can create a safety certification', function () {
        $certification = SafetyCertification::create([
            'name' => 'Test Certification',
            'category' => 'food_safety',
            'validity_months' => 24,
            'requires_renewal' => true,
            'is_active' => true,
        ]);

        expect($certification)->toBeInstanceOf(SafetyCertification::class)
            ->and($certification->slug)->toBe('test-certification')
            ->and($certification->category)->toBe('food_safety')
            ->and($certification->validity_months)->toBe(24);
    });

    test('auto-generates slug from name', function () {
        $certification = SafetyCertification::create([
            'name' => 'First Aid CPR Training',
            'category' => 'health',
            'is_active' => true,
        ]);

        expect($certification->slug)->toBe('first-aid-cpr-training');
    });

    test('calculates expiry date correctly', function () {
        $certification = SafetyCertification::create([
            'name' => 'Test Cert',
            'category' => 'general',
            'validity_months' => 12,
            'is_active' => true,
        ]);

        $issueDate = now();
        $expiryDate = $certification->calculateExpiryDate($issueDate);

        expect($expiryDate)->toBeInstanceOf(\Carbon\Carbon::class)
            ->and((int) $issueDate->diffInMonths($expiryDate))->toBe(12);
    });

    test('returns null expiry date when validity_months is null', function () {
        $certification = SafetyCertification::create([
            'name' => 'No Expiry Cert',
            'category' => 'general',
            'validity_months' => null,
            'requires_renewal' => false,
            'is_active' => true,
        ]);

        $expiryDate = $certification->calculateExpiryDate(now());

        expect($expiryDate)->toBeNull();
    });

    test('scopes work correctly', function () {
        SafetyCertification::query()->delete();

        SafetyCertification::create([
            'name' => 'Active Cert',
            'category' => 'food_safety',
            'is_active' => true,
        ]);

        SafetyCertification::create([
            'name' => 'Inactive Cert',
            'category' => 'health',
            'is_active' => false,
        ]);

        SafetyCertification::create([
            'name' => 'Mandatory Cert',
            'category' => 'security',
            'is_mandatory' => true,
            'is_active' => true,
        ]);

        expect(SafetyCertification::active()->count())->toBe(2)
            ->and(SafetyCertification::mandatory()->count())->toBe(1)
            ->and(SafetyCertification::byCategory('food_safety')->count())->toBe(1);
    });

    test('checks applicable industry correctly', function () {
        $certification = SafetyCertification::create([
            'name' => 'Industry Specific',
            'category' => 'industry_specific',
            'applicable_industries' => ['hospitality', 'food_service'],
            'is_active' => true,
        ]);

        expect($certification->isApplicableToIndustry('hospitality'))->toBeTrue()
            ->and($certification->isApplicableToIndustry('construction'))->toBeFalse();
    });

    test('allows all industries when applicable_industries is null', function () {
        $certification = SafetyCertification::create([
            'name' => 'General Cert',
            'category' => 'general',
            'applicable_industries' => null,
            'is_active' => true,
        ]);

        expect($certification->isApplicableToIndustry('anything'))->toBeTrue();
    });
});

describe('ShiftCertificationRequirement', function () {
    test('can require a certification for a shift', function () {
        $certification = SafetyCertification::factory()->create();
        $shift = Shift::factory()->create();

        $requirement = ShiftCertificationRequirement::create([
            'shift_id' => $shift->id,
            'safety_certification_id' => $certification->id,
            'is_mandatory' => true,
        ]);

        expect($requirement->shift->id)->toBe($shift->id)
            ->and($requirement->safetyCertification->id)->toBe($certification->id)
            ->and($requirement->is_mandatory)->toBeTrue();
    });

    test('shift can have multiple certification requirements', function () {
        $cert1 = SafetyCertification::factory()->create();
        $cert2 = SafetyCertification::factory()->create();
        $shift = Shift::factory()->create();

        ShiftCertificationRequirement::create([
            'shift_id' => $shift->id,
            'safety_certification_id' => $cert1->id,
            'is_mandatory' => true,
        ]);

        ShiftCertificationRequirement::create([
            'shift_id' => $shift->id,
            'safety_certification_id' => $cert2->id,
            'is_mandatory' => false,
        ]);

        $shift->refresh();

        expect($shift->certificationRequirements)->toHaveCount(2)
            ->and($shift->mandatoryCertificationRequirements)->toHaveCount(1);
    });
});

describe('CertificationService', function () {
    test('worker without certification fails shift requirements', function () {
        $certification = SafetyCertification::factory()->create();
        $shift = Shift::factory()->create();
        $worker = User::factory()->create(['role' => 'worker']);

        ShiftCertificationRequirement::create([
            'shift_id' => $shift->id,
            'safety_certification_id' => $certification->id,
            'is_mandatory' => true,
        ]);

        $meetsRequirements = $this->certificationService->workerMeetsShiftRequirements($worker, $shift);

        expect($meetsRequirements)->toBeFalse();
    });

    test('worker with valid certification passes shift requirements', function () {
        $certification = SafetyCertification::factory()->create();
        $shift = Shift::factory()->create();
        $worker = User::factory()->create(['role' => 'worker']);

        ShiftCertificationRequirement::create([
            'shift_id' => $shift->id,
            'safety_certification_id' => $certification->id,
            'is_mandatory' => true,
        ]);

        WorkerCertification::create([
            'worker_id' => $worker->id,
            'safety_certification_id' => $certification->id,
            'issue_date' => now()->subMonths(6),
            'expiry_date' => now()->addMonths(6),
            'verification_status' => WorkerCertification::STATUS_VERIFIED,
            'verified' => true,
        ]);

        $meetsRequirements = $this->certificationService->workerMeetsShiftRequirements($worker, $shift);

        expect($meetsRequirements)->toBeTrue();
    });

    test('worker with expired certification fails shift requirements', function () {
        $certification = SafetyCertification::factory()->create();
        $shift = Shift::factory()->create();
        $worker = User::factory()->create(['role' => 'worker']);

        ShiftCertificationRequirement::create([
            'shift_id' => $shift->id,
            'safety_certification_id' => $certification->id,
            'is_mandatory' => true,
        ]);

        WorkerCertification::create([
            'worker_id' => $worker->id,
            'safety_certification_id' => $certification->id,
            'issue_date' => now()->subMonths(24),
            'expiry_date' => now()->subDays(1),
            'verification_status' => WorkerCertification::STATUS_VERIFIED,
            'verified' => true,
        ]);

        $meetsRequirements = $this->certificationService->workerMeetsShiftRequirements($worker, $shift);

        expect($meetsRequirements)->toBeFalse();
    });

    test('worker with pending certification fails shift requirements', function () {
        $certification = SafetyCertification::factory()->create();
        $shift = Shift::factory()->create();
        $worker = User::factory()->create(['role' => 'worker']);

        ShiftCertificationRequirement::create([
            'shift_id' => $shift->id,
            'safety_certification_id' => $certification->id,
            'is_mandatory' => true,
        ]);

        WorkerCertification::create([
            'worker_id' => $worker->id,
            'safety_certification_id' => $certification->id,
            'issue_date' => now()->subMonths(6),
            'expiry_date' => now()->addMonths(6),
            'verification_status' => WorkerCertification::STATUS_PENDING,
            'verified' => false,
        ]);

        $meetsRequirements = $this->certificationService->workerMeetsShiftRequirements($worker, $shift);

        expect($meetsRequirements)->toBeFalse();
    });

    test('shift with no requirements returns true for any worker', function () {
        $shift = Shift::factory()->create();
        $worker = User::factory()->create(['role' => 'worker']);

        $meetsRequirements = $this->certificationService->workerMeetsShiftRequirements($worker, $shift);

        expect($meetsRequirements)->toBeTrue();
    });

    test('getMissingCertifications returns correct certifications', function () {
        $cert1 = SafetyCertification::factory()->create(['name' => 'Cert 1']);
        $cert2 = SafetyCertification::factory()->create(['name' => 'Cert 2']);
        $shift = Shift::factory()->create();
        $worker = User::factory()->create(['role' => 'worker']);

        ShiftCertificationRequirement::create([
            'shift_id' => $shift->id,
            'safety_certification_id' => $cert1->id,
            'is_mandatory' => true,
        ]);

        ShiftCertificationRequirement::create([
            'shift_id' => $shift->id,
            'safety_certification_id' => $cert2->id,
            'is_mandatory' => false,
        ]);

        // Worker has cert1 but not cert2
        WorkerCertification::create([
            'worker_id' => $worker->id,
            'safety_certification_id' => $cert1->id,
            'issue_date' => now()->subMonths(6),
            'verification_status' => WorkerCertification::STATUS_VERIFIED,
            'verified' => true,
        ]);

        $missing = $this->certificationService->getMissingCertifications($worker, $shift);

        expect($missing)->toHaveCount(1)
            ->and($missing->first()['certification']->name)->toBe('Cert 2')
            ->and($missing->first()['is_mandatory'])->toBeFalse();
    });

    test('getComplianceReport returns correct structure', function () {
        $report = $this->certificationService->getComplianceReport();

        expect($report)->toHaveKeys(['summary', 'by_category', 'compliance_rate', 'generated_at'])
            ->and($report['summary'])->toHaveKeys([
                'workers_with_certifications',
                'total_verified',
                'total_pending',
                'total_rejected',
                'total_expired',
                'expiring_in_30_days',
            ]);
    });
});

describe('Worker Certification Submission', function () {
    test('worker can add a certification', function () {
        $certification = SafetyCertification::factory()->create([
            'validity_months' => 24,
        ]);
        $worker = User::factory()->create(['role' => 'worker']);

        $result = $this->certificationService->addWorkerCertification($worker, [
            'safety_certification_id' => $certification->id,
            'certificate_number' => 'CERT-12345',
            'issue_date' => now()->subMonths(1)->format('Y-m-d'),
        ]);

        expect($result['success'])->toBeTrue()
            ->and($result['certification'])->toBeInstanceOf(WorkerCertification::class)
            ->and($result['certification']->certification_number)->toBe('CERT-12345')
            ->and($result['certification']->verification_status)->toBe(WorkerCertification::STATUS_PENDING);
    });

    test('worker cannot add duplicate certification', function () {
        $certification = SafetyCertification::factory()->create();
        $worker = User::factory()->create(['role' => 'worker']);

        // Add first certification
        $this->certificationService->addWorkerCertification($worker, [
            'safety_certification_id' => $certification->id,
            'issue_date' => now()->subMonths(1)->format('Y-m-d'),
        ]);

        // Try to add duplicate
        $result = $this->certificationService->addWorkerCertification($worker, [
            'safety_certification_id' => $certification->id,
            'issue_date' => now()->format('Y-m-d'),
        ]);

        expect($result['success'])->toBeFalse()
            ->and($result['error'])->toContain('already have this certification');
    });

    test('expiry date is calculated from safety certification validity', function () {
        $certification = SafetyCertification::factory()->create([
            'validity_months' => 12,
        ]);
        $worker = User::factory()->create(['role' => 'worker']);

        $issueDate = now()->subMonths(1);

        $result = $this->certificationService->addWorkerCertification($worker, [
            'safety_certification_id' => $certification->id,
            'issue_date' => $issueDate->format('Y-m-d'),
        ]);

        $expectedExpiry = $issueDate->copy()->addMonths(12);

        expect($result['success'])->toBeTrue()
            ->and($result['certification']->expiry_date->format('Y-m-d'))
            ->toBe($expectedExpiry->format('Y-m-d'));
    });
});
