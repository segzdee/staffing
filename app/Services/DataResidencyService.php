<?php

namespace App\Services;

use App\Models\DataRegion;
use App\Models\DataTransferLog;
use App\Models\User;
use App\Models\UserDataResidency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * GLO-010: Data Residency Service
 *
 * Manages data residency assignments, cross-region transfers,
 * and compliance with regional data storage requirements.
 */
class DataResidencyService
{
    /**
     * Cache duration for region lookups (in seconds).
     */
    protected const CACHE_DURATION = 3600;

    /**
     * Determine the appropriate data region for a user based on their location.
     */
    public function determineUserRegion(User $user): DataRegion
    {
        // First check if user has an existing residency record
        $existingResidency = $user->dataResidency;
        if ($existingResidency) {
            return $existingResidency->dataRegion;
        }

        // Try to detect country from user's profile or IP
        $countryCode = $this->detectUserCountry($user);

        // Find the region for this country
        $region = $this->getRegionForCountry($countryCode);

        return $region ?? DataRegion::getDefault();
    }

    /**
     * Detect the user's country from their profile or cached IP data.
     */
    protected function detectUserCountry(User $user): string
    {
        // Check worker profile for country
        if ($user->workerProfile && $user->workerProfile->country) {
            return $user->workerProfile->country;
        }

        // Check business profile for country
        if ($user->businessProfile && $user->businessProfile->country) {
            return $user->businessProfile->country;
        }

        // Try to get from IP-based cache
        $ip = request()->ip();
        $cachedCountry = Cache::get('userCountry-'.$ip);

        if ($cachedCountry) {
            return $cachedCountry;
        }

        // Default to configured default region's primary country
        return config('data_residency.default_country', 'US');
    }

    /**
     * Assign a data region to a user.
     */
    public function assignDataRegion(
        User $user,
        DataRegion $region,
        bool $userSelected = false,
        bool $recordConsent = false
    ): UserDataResidency {
        $countryCode = $this->detectUserCountry($user);

        $residency = UserDataResidency::updateOrCreate(
            ['user_id' => $user->id],
            [
                'data_region_id' => $region->id,
                'detected_country' => $countryCode,
                'user_selected' => $userSelected,
                'consent_given_at' => $recordConsent ? now() : null,
                'data_locations' => [
                    'primary' => $region->primary_storage,
                    'assigned_at' => now()->toIso8601String(),
                ],
            ]
        );

        Log::info('Data region assigned to user', [
            'user_id' => $user->id,
            'region_code' => $region->code,
            'detected_country' => $countryCode,
            'user_selected' => $userSelected,
        ]);

        return $residency;
    }

    /**
     * Get the storage path for a user's data of a specific type.
     */
    public function getStoragePath(User $user, string $dataType): string
    {
        $residency = $user->dataResidency;

        if (! $residency) {
            // Assign default region if none exists
            $region = $this->determineUserRegion($user);
            $residency = $this->assignDataRegion($user, $region);
        }

        $region = $residency->dataRegion;
        $basePath = $residency->getStoragePathPrefix();

        return "{$basePath}/{$dataType}";
    }

    /**
     * Get the storage disk for a user.
     */
    public function getStorageDisk(User $user): string
    {
        $residency = $user->dataResidency;

        if (! $residency) {
            $region = $this->determineUserRegion($user);
            $residency = $this->assignDataRegion($user, $region);
        }

        return $residency->dataRegion->getStorageDisk();
    }

    /**
     * Migrate user data to a new region.
     */
    public function migrateUserData(
        User $user,
        DataRegion $newRegion,
        ?string $legalBasis = null
    ): DataTransferLog {
        $currentResidency = $user->dataResidency;

        if (! $currentResidency) {
            throw new \Exception('User does not have a data residency record.');
        }

        $fromRegion = $currentResidency->dataRegion;

        if ($fromRegion->id === $newRegion->id) {
            throw new \Exception('User is already in the target region.');
        }

        // Create transfer log
        $transfer = $this->logDataTransfer([
            'user_id' => $user->id,
            'from_region' => $fromRegion->code,
            'to_region' => $newRegion->code,
            'transfer_type' => DataTransferLog::TYPE_MIGRATION,
            'status' => DataTransferLog::STATUS_PENDING,
            'data_types' => [DataTransferLog::DATA_TYPE_ALL],
            'legal_basis' => $legalBasis ?? DataTransferLog::LEGAL_BASIS_CONSENT,
        ]);

        DB::beginTransaction();
        try {
            // Start the transfer
            $transfer->start();

            // Perform the actual data migration
            $this->performDataMigration($user, $fromRegion, $newRegion);

            // Update the user's residency record
            $currentResidency->update([
                'data_region_id' => $newRegion->id,
                'data_locations' => [
                    'primary' => $newRegion->primary_storage,
                    'previous' => $fromRegion->primary_storage,
                    'migrated_at' => now()->toIso8601String(),
                ],
            ]);

            // Complete the transfer
            $transfer->complete([
                'files_migrated' => true,
                'completed_by' => 'system',
            ]);

            DB::commit();

            Log::info('User data migrated to new region', [
                'user_id' => $user->id,
                'from_region' => $fromRegion->code,
                'to_region' => $newRegion->code,
                'transfer_id' => $transfer->id,
            ]);

            return $transfer;

        } catch (\Exception $e) {
            DB::rollBack();

            $transfer->fail($e->getMessage());

            Log::error('Data migration failed', [
                'user_id' => $user->id,
                'from_region' => $fromRegion->code,
                'to_region' => $newRegion->code,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Perform the actual data migration between storage locations.
     */
    protected function performDataMigration(
        User $user,
        DataRegion $fromRegion,
        DataRegion $toRegion
    ): void {
        $fromDisk = $fromRegion->getStorageDisk();
        $toDisk = $toRegion->getStorageDisk();

        // Build paths
        $fromPath = "regions/{$fromRegion->code}/users/{$user->id}";
        $toPath = "regions/{$toRegion->code}/users/{$user->id}";

        // Check if source storage exists and has files
        if (! Storage::disk($fromDisk)->exists($fromPath)) {
            // No data to migrate
            return;
        }

        // Get all files from source
        $files = Storage::disk($fromDisk)->allFiles($fromPath);

        foreach ($files as $file) {
            // Calculate relative path
            $relativePath = str_replace($fromPath.'/', '', $file);
            $newFilePath = "{$toPath}/{$relativePath}";

            // Copy file to new location
            $contents = Storage::disk($fromDisk)->get($file);
            Storage::disk($toDisk)->put($newFilePath, $contents);

            // Verify copy
            if (! Storage::disk($toDisk)->exists($newFilePath)) {
                throw new \Exception("Failed to copy file: {$file}");
            }
        }

        // Delete source files after successful migration
        foreach ($files as $file) {
            Storage::disk($fromDisk)->delete($file);
        }

        // Clean up empty directories
        Storage::disk($fromDisk)->deleteDirectory($fromPath);
    }

    /**
     * Log a data transfer operation.
     */
    public function logDataTransfer(array $data): DataTransferLog
    {
        $transfer = DataTransferLog::create([
            'user_id' => $data['user_id'],
            'from_region' => $data['from_region'],
            'to_region' => $data['to_region'],
            'transfer_type' => $data['transfer_type'],
            'status' => $data['status'] ?? DataTransferLog::STATUS_PENDING,
            'data_types' => $data['data_types'],
            'legal_basis' => $data['legal_basis'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);

        if (config('data_residency.log_all_transfers', true)) {
            Log::info('Data transfer logged', [
                'transfer_id' => $transfer->id,
                'user_id' => $transfer->user_id,
                'from_region' => $transfer->from_region,
                'to_region' => $transfer->to_region,
                'type' => $transfer->transfer_type,
            ]);
        }

        return $transfer;
    }

    /**
     * Validate if a user can access data in a target region.
     */
    public function validateCrossRegionAccess(User $user, string $targetRegion): array
    {
        $userResidency = $user->dataResidency;

        if (! $userResidency) {
            return [
                'allowed' => false,
                'reason' => 'User has no data residency record.',
            ];
        }

        $userRegion = $userResidency->dataRegion;

        // Same region - always allowed
        if ($userRegion->code === $targetRegion) {
            return [
                'allowed' => true,
                'reason' => 'Same region access.',
            ];
        }

        // Check if cross-region access is allowed
        $targetRegionModel = DataRegion::where('code', $targetRegion)->first();

        if (! $targetRegionModel) {
            return [
                'allowed' => false,
                'reason' => 'Target region does not exist.',
            ];
        }

        // Check compliance frameworks
        $userFrameworks = $userRegion->compliance_frameworks ?? [];
        $targetFrameworks = $targetRegionModel->compliance_frameworks ?? [];

        // GDPR regions require special handling
        if (in_array(DataRegion::FRAMEWORK_GDPR, $userFrameworks)) {
            // Check if target region has adequacy decision or other legal basis
            $hasAdequacy = $this->checkAdequacyDecision($userRegion->code, $targetRegion);

            if (! $hasAdequacy) {
                return [
                    'allowed' => false,
                    'reason' => 'GDPR region requires adequacy decision or SCCs for cross-region access.',
                    'requires_consent' => true,
                    'legal_basis_options' => [
                        DataTransferLog::LEGAL_BASIS_CONSENT,
                        DataTransferLog::LEGAL_BASIS_SCC,
                    ],
                ];
            }
        }

        return [
            'allowed' => true,
            'reason' => 'Cross-region access permitted.',
            'from_region' => $userRegion->code,
            'to_region' => $targetRegion,
        ];
    }

    /**
     * Check if there's an adequacy decision between two regions.
     */
    protected function checkAdequacyDecision(string $fromRegion, string $toRegion): bool
    {
        // EU adequacy decisions (as of knowledge cutoff)
        $euAdequacyCountries = [
            'uk', // UK GDPR considered adequate
            'us', // US with Privacy Shield framework / Data Privacy Framework
        ];

        if ($fromRegion === 'eu' && in_array($toRegion, $euAdequacyCountries)) {
            return true;
        }

        // UK adequacy decisions
        if ($fromRegion === 'uk' && in_array($toRegion, ['eu', 'us'])) {
            return true;
        }

        return false;
    }

    /**
     * Get the appropriate data region for a country code.
     */
    public function getRegionForCountry(string $countryCode): ?DataRegion
    {
        $countryCode = strtoupper($countryCode);

        // Check cache first
        $cacheKey = "data_region_for_country_{$countryCode}";
        $cachedRegion = Cache::get($cacheKey);

        if ($cachedRegion) {
            return DataRegion::find($cachedRegion);
        }

        // Find region containing this country
        $region = DataRegion::findByCountry($countryCode);

        if ($region) {
            Cache::put($cacheKey, $region->id, self::CACHE_DURATION);
        }

        return $region;
    }

    /**
     * Generate a data residency report for a user.
     */
    public function generateDataResidencyReport(User $user): array
    {
        $residency = $user->dataResidency;

        if (! $residency) {
            return [
                'user_id' => $user->id,
                'has_residency' => false,
                'message' => 'No data residency record found for this user.',
            ];
        }

        $region = $residency->dataRegion;
        $transfers = DataTransferLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'user_id' => $user->id,
            'has_residency' => true,
            'region' => [
                'code' => $region->code,
                'name' => $region->name,
                'primary_storage' => $region->primary_storage,
                'backup_storage' => $region->backup_storage,
                'compliance_frameworks' => $region->compliance_frameworks,
            ],
            'assignment' => [
                'detected_country' => $residency->detected_country,
                'user_selected' => $residency->user_selected,
                'consent_given_at' => $residency->consent_given_at?->toIso8601String(),
                'assigned_at' => $residency->created_at->toIso8601String(),
            ],
            'data_locations' => $residency->data_locations,
            'transfer_history' => $transfers->map(function ($transfer) {
                return [
                    'id' => $transfer->id,
                    'from_region' => $transfer->from_region,
                    'to_region' => $transfer->to_region,
                    'type' => $transfer->transfer_type,
                    'status' => $transfer->status,
                    'legal_basis' => $transfer->legal_basis,
                    'data_types' => $transfer->data_types,
                    'created_at' => $transfer->created_at->toIso8601String(),
                    'completed_at' => $transfer->completed_at?->toIso8601String(),
                ];
            })->toArray(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get statistics for admin dashboard.
     */
    public function getResidencyStatistics(): array
    {
        $regions = DataRegion::withCount('userDataResidencies')->get();

        $usersByRegion = [];
        foreach ($regions as $region) {
            $usersByRegion[$region->code] = [
                'name' => $region->name,
                'count' => $region->user_data_residencies_count,
                'is_active' => $region->is_active,
            ];
        }

        $transferStats = [
            'total' => DataTransferLog::count(),
            'pending' => DataTransferLog::pending()->count(),
            'in_progress' => DataTransferLog::inProgress()->count(),
            'completed' => DataTransferLog::completed()->count(),
            'failed' => DataTransferLog::failed()->count(),
            'last_30_days' => DataTransferLog::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        $userStats = [
            'total_with_residency' => UserDataResidency::count(),
            'with_consent' => UserDataResidency::withConsent()->count(),
            'user_selected' => UserDataResidency::userSelected()->count(),
        ];

        return [
            'regions' => $usersByRegion,
            'transfers' => $transferStats,
            'users' => $userStats,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Batch assign regions to users without residency records.
     */
    public function batchAssignRegions(): array
    {
        $usersWithoutResidency = User::whereDoesntHave('dataResidency')
            ->where('status', '!=', 'anonymized')
            ->get();

        $assigned = 0;
        $errors = [];

        foreach ($usersWithoutResidency as $user) {
            try {
                $region = $this->determineUserRegion($user);
                $this->assignDataRegion($user, $region);
                $assigned++;
            } catch (\Exception $e) {
                $errors[] = [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Batch region assignment completed', [
            'assigned' => $assigned,
            'errors' => count($errors),
        ]);

        return [
            'processed' => count($usersWithoutResidency),
            'assigned' => $assigned,
            'errors' => $errors,
        ];
    }
}
