<?php

use App\Models\DataRegion;
use App\Models\DataTransferLog;
use App\Models\User;
use App\Models\UserDataResidency;
use App\Services\DataResidencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test regions
    $this->euRegion = DataRegion::create([
        'code' => 'eu',
        'name' => 'European Union',
        'countries' => ['DE', 'FR', 'IT', 'ES', 'NL'],
        'primary_storage' => 's3-eu',
        'backup_storage' => 's3-eu-backup',
        'compliance_frameworks' => ['GDPR'],
        'is_active' => true,
    ]);

    $this->usRegion = DataRegion::create([
        'code' => 'us',
        'name' => 'United States',
        'countries' => ['US', 'CA'],
        'primary_storage' => 's3-us',
        'backup_storage' => 's3-us-backup',
        'compliance_frameworks' => ['CCPA'],
        'is_active' => true,
    ]);

    $this->apacRegion = DataRegion::create([
        'code' => 'apac',
        'name' => 'Asia Pacific',
        'countries' => ['AU', 'NZ', 'SG', 'JP'],
        'primary_storage' => 's3-apac',
        'backup_storage' => null,
        'compliance_frameworks' => ['APP', 'PDPA'],
        'is_active' => true,
    ]);

    $this->service = app(DataResidencyService::class);
});

// ==========================================
// DataRegion Model Tests
// ==========================================

describe('DataRegion Model', function () {
    it('can find a region by country code', function () {
        $region = DataRegion::findByCountry('DE');

        expect($region)->not->toBeNull()
            ->and($region->code)->toBe('eu');
    });

    it('returns null for unknown country code', function () {
        $region = DataRegion::findByCountry('XX');

        expect($region)->toBeNull();
    });

    it('correctly checks if country belongs to region', function () {
        expect($this->euRegion->hasCountry('DE'))->toBeTrue()
            ->and($this->euRegion->hasCountry('US'))->toBeFalse();
    });

    it('correctly checks compliance frameworks', function () {
        expect($this->euRegion->hasComplianceFramework('GDPR'))->toBeTrue()
            ->and($this->euRegion->hasComplianceFramework('CCPA'))->toBeFalse();
    });

    it('returns correct storage disk', function () {
        expect($this->euRegion->getStorageDisk())->toBe('s3-eu');
    });

    it('can scope to active regions only', function () {
        $inactiveRegion = DataRegion::create([
            'code' => 'inactive',
            'name' => 'Inactive Region',
            'countries' => ['ZZ'],
            'primary_storage' => 's3-test',
            'compliance_frameworks' => [],
            'is_active' => false,
        ]);

        $activeRegions = DataRegion::active()->get();

        expect($activeRegions)->toHaveCount(3)
            ->and($activeRegions->pluck('code')->toArray())->not->toContain('inactive');
    });
});

// ==========================================
// UserDataResidency Model Tests
// ==========================================

describe('UserDataResidency Model', function () {
    it('can record consent', function () {
        $user = User::factory()->create();
        $residency = UserDataResidency::create([
            'user_id' => $user->id,
            'data_region_id' => $this->euRegion->id,
            'detected_country' => 'DE',
        ]);

        expect($residency->hasConsent())->toBeFalse();

        $residency->recordConsent();

        expect($residency->hasConsent())->toBeTrue()
            ->and($residency->consent_given_at)->not->toBeNull();
    });

    it('can mark as user selected', function () {
        $user = User::factory()->create();
        $residency = UserDataResidency::create([
            'user_id' => $user->id,
            'data_region_id' => $this->euRegion->id,
            'detected_country' => 'DE',
        ]);

        expect($residency->user_selected)->toBeFalse();

        $residency->markAsUserSelected();

        expect($residency->fresh()->user_selected)->toBeTrue();
    });

    it('can update data locations', function () {
        $user = User::factory()->create();
        $residency = UserDataResidency::create([
            'user_id' => $user->id,
            'data_region_id' => $this->euRegion->id,
            'detected_country' => 'DE',
        ]);

        $residency->updateDataLocations(['documents' => 's3-eu']);

        expect($residency->fresh()->getDataLocation('documents'))->toBe('s3-eu');
    });

    it('provides correct storage path prefix', function () {
        $user = User::factory()->create();
        $residency = UserDataResidency::create([
            'user_id' => $user->id,
            'data_region_id' => $this->euRegion->id,
            'detected_country' => 'DE',
        ]);

        $pathPrefix = $residency->getStoragePathPrefix();

        expect($pathPrefix)->toBe("regions/eu/users/{$user->id}");
    });

    it('can scope by consent status', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        UserDataResidency::create([
            'user_id' => $user1->id,
            'data_region_id' => $this->euRegion->id,
            'detected_country' => 'DE',
            'consent_given_at' => now(),
        ]);

        UserDataResidency::create([
            'user_id' => $user2->id,
            'data_region_id' => $this->euRegion->id,
            'detected_country' => 'FR',
            'consent_given_at' => null,
        ]);

        $withConsent = UserDataResidency::withConsent()->get();

        expect($withConsent)->toHaveCount(1)
            ->and($withConsent->first()->user_id)->toBe($user1->id);
    });
});

// ==========================================
// DataTransferLog Model Tests
// ==========================================

describe('DataTransferLog Model', function () {
    it('can track transfer lifecycle', function () {
        $user = User::factory()->create();
        $transfer = DataTransferLog::create([
            'user_id' => $user->id,
            'from_region' => 'eu',
            'to_region' => 'us',
            'transfer_type' => DataTransferLog::TYPE_MIGRATION,
            'status' => DataTransferLog::STATUS_PENDING,
            'data_types' => [DataTransferLog::DATA_TYPE_ALL],
            'legal_basis' => DataTransferLog::LEGAL_BASIS_CONSENT,
        ]);

        expect($transfer->isPending())->toBeTrue();

        $transfer->start();
        expect($transfer->fresh()->isInProgress())->toBeTrue();

        $transfer->complete(['test' => 'metadata']);
        $transfer->refresh();

        expect($transfer->isCompleted())->toBeTrue()
            ->and($transfer->completed_at)->not->toBeNull()
            ->and($transfer->metadata['test'])->toBe('metadata');
    });

    it('can track failed transfers', function () {
        $user = User::factory()->create();
        $transfer = DataTransferLog::create([
            'user_id' => $user->id,
            'from_region' => 'eu',
            'to_region' => 'us',
            'transfer_type' => DataTransferLog::TYPE_MIGRATION,
            'status' => DataTransferLog::STATUS_PENDING,
            'data_types' => [DataTransferLog::DATA_TYPE_PROFILE],
        ]);

        $transfer->start();
        $transfer->fail('Test error message');
        $transfer->refresh();

        expect($transfer->isFailed())->toBeTrue()
            ->and($transfer->error_message)->toBe('Test error message')
            ->and($transfer->completed_at)->not->toBeNull();
    });

    it('calculates transfer duration', function () {
        $user = User::factory()->create();
        $transfer = DataTransferLog::create([
            'user_id' => $user->id,
            'from_region' => 'eu',
            'to_region' => 'us',
            'transfer_type' => DataTransferLog::TYPE_MIGRATION,
            'status' => DataTransferLog::STATUS_COMPLETED,
            'data_types' => [DataTransferLog::DATA_TYPE_ALL],
            'completed_at' => now(),
        ]);

        expect($transfer->duration)->toBeInt()
            ->and($transfer->duration)->toBeGreaterThanOrEqual(0);
    });

    it('can filter by status scopes', function () {
        $user = User::factory()->create();

        DataTransferLog::create([
            'user_id' => $user->id,
            'from_region' => 'eu',
            'to_region' => 'us',
            'transfer_type' => DataTransferLog::TYPE_MIGRATION,
            'status' => DataTransferLog::STATUS_PENDING,
            'data_types' => [DataTransferLog::DATA_TYPE_ALL],
        ]);

        DataTransferLog::create([
            'user_id' => $user->id,
            'from_region' => 'us',
            'to_region' => 'apac',
            'transfer_type' => DataTransferLog::TYPE_BACKUP,
            'status' => DataTransferLog::STATUS_COMPLETED,
            'data_types' => [DataTransferLog::DATA_TYPE_DOCUMENTS],
            'completed_at' => now(),
        ]);

        expect(DataTransferLog::pending()->count())->toBe(1)
            ->and(DataTransferLog::completed()->count())->toBe(1);
    });
});

// ==========================================
// DataResidencyService Tests
// ==========================================

describe('DataResidencyService', function () {
    it('determines user region based on country', function () {
        $user = User::factory()->create();

        // Mock worker profile with country
        $user->workerProfile = (object) ['country' => 'DE'];

        $region = $this->service->determineUserRegion($user);

        expect($region)->not->toBeNull()
            ->and($region->code)->toBe('eu');
    });

    it('assigns data region to user', function () {
        $user = User::factory()->create();

        $residency = $this->service->assignDataRegion($user, $this->euRegion, true, true);

        expect($residency)->toBeInstanceOf(UserDataResidency::class)
            ->and($residency->data_region_id)->toBe($this->euRegion->id)
            ->and($residency->user_selected)->toBeTrue()
            ->and($residency->consent_given_at)->not->toBeNull();
    });

    it('gets storage path for user', function () {
        $user = User::factory()->create();
        $this->service->assignDataRegion($user, $this->euRegion);

        $path = $this->service->getStoragePath($user, 'documents');

        expect($path)->toBe("regions/eu/users/{$user->id}/documents");
    });

    it('validates cross-region access for same region', function () {
        $user = User::factory()->create();
        $this->service->assignDataRegion($user, $this->euRegion);

        $result = $this->service->validateCrossRegionAccess($user, 'eu');

        expect($result['allowed'])->toBeTrue()
            ->and($result['reason'])->toBe('Same region access.');
    });

    it('validates cross-region access for different regions', function () {
        $user = User::factory()->create();
        $this->service->assignDataRegion($user, $this->euRegion);

        $result = $this->service->validateCrossRegionAccess($user, 'us');

        expect($result)->toHaveKey('allowed');
    });

    it('logs data transfers', function () {
        $user = User::factory()->create();

        $transfer = $this->service->logDataTransfer([
            'user_id' => $user->id,
            'from_region' => 'eu',
            'to_region' => 'us',
            'transfer_type' => DataTransferLog::TYPE_EXPORT,
            'data_types' => [DataTransferLog::DATA_TYPE_PROFILE],
            'legal_basis' => DataTransferLog::LEGAL_BASIS_CONSENT,
        ]);

        expect($transfer)->toBeInstanceOf(DataTransferLog::class)
            ->and($transfer->status)->toBe(DataTransferLog::STATUS_PENDING);
    });

    it('gets region for country code', function () {
        $region = $this->service->getRegionForCountry('AU');

        expect($region)->not->toBeNull()
            ->and($region->code)->toBe('apac');
    });

    it('generates data residency report', function () {
        $user = User::factory()->create();
        $this->service->assignDataRegion($user, $this->euRegion, true, true);

        $report = $this->service->generateDataResidencyReport($user);

        expect($report)
            ->toHaveKey('user_id')
            ->toHaveKey('has_residency')
            ->toHaveKey('region')
            ->toHaveKey('assignment')
            ->and($report['has_residency'])->toBeTrue()
            ->and($report['region']['code'])->toBe('eu');
    });

    it('gets residency statistics', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->service->assignDataRegion($user1, $this->euRegion, false, true);
        $this->service->assignDataRegion($user2, $this->usRegion, true, true);

        $stats = $this->service->getResidencyStatistics();

        expect($stats)
            ->toHaveKey('regions')
            ->toHaveKey('transfers')
            ->toHaveKey('users')
            ->and($stats['users']['total_with_residency'])->toBe(2)
            ->and($stats['users']['with_consent'])->toBe(2);
    });
});

// ==========================================
// User Model Relationship Tests
// ==========================================

describe('User Data Residency Relationship', function () {
    it('user has data residency relationship', function () {
        $user = User::factory()->create();

        UserDataResidency::create([
            'user_id' => $user->id,
            'data_region_id' => $this->euRegion->id,
            'detected_country' => 'DE',
        ]);

        expect($user->dataResidency)->toBeInstanceOf(UserDataResidency::class)
            ->and($user->dataResidency->dataRegion->code)->toBe('eu');
    });

    it('user has data transfer logs relationship', function () {
        $user = User::factory()->create();

        DataTransferLog::create([
            'user_id' => $user->id,
            'from_region' => 'eu',
            'to_region' => 'us',
            'transfer_type' => DataTransferLog::TYPE_MIGRATION,
            'status' => DataTransferLog::STATUS_COMPLETED,
            'data_types' => [DataTransferLog::DATA_TYPE_ALL],
            'completed_at' => now(),
        ]);

        expect($user->dataTransferLogs)->toHaveCount(1);
    });
});
