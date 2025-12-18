<?php

namespace App\Services;

use App\Models\Integration;
use App\Models\IntegrationSync;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * BIZ-012: Integration APIs - External Integration Service
 *
 * Handles connections and data synchronization with external systems:
 * - HR Systems (ADP, Gusto)
 * - Scheduling Systems (Deputy, When I Work)
 * - POS Systems (Square, Toast)
 * - Calendar Systems (Google Calendar, Outlook)
 * - Accounting Systems (QuickBooks, Xero)
 */
class IntegrationService
{
    /**
     * Provider-specific API configurations.
     */
    protected array $providerConfigs = [
        'deputy' => [
            'base_url' => 'https://once.deputy.com/api/v1',
            'auth_type' => 'oauth2',
        ],
        'when_i_work' => [
            'base_url' => 'https://api.wheniwork.com/2',
            'auth_type' => 'api_key',
        ],
        'gusto' => [
            'base_url' => 'https://api.gusto.com/v1',
            'auth_type' => 'oauth2',
        ],
        'adp' => [
            'base_url' => 'https://api.adp.com',
            'auth_type' => 'oauth2',
        ],
        'google_calendar' => [
            'base_url' => 'https://www.googleapis.com/calendar/v3',
            'auth_type' => 'oauth2',
        ],
        'outlook' => [
            'base_url' => 'https://graph.microsoft.com/v1.0',
            'auth_type' => 'oauth2',
        ],
        'square_pos' => [
            'base_url' => 'https://connect.squareup.com/v2',
            'auth_type' => 'oauth2',
        ],
        'toast_pos' => [
            'base_url' => 'https://api.toasttab.com',
            'auth_type' => 'api_key',
        ],
        'quickbooks' => [
            'base_url' => 'https://quickbooks.api.intuit.com/v3',
            'auth_type' => 'oauth2',
        ],
        'xero' => [
            'base_url' => 'https://api.xero.com/api.xro/2.0',
            'auth_type' => 'oauth2',
        ],
    ];

    /**
     * Connect a new integration.
     */
    public function connect(User $business, string $provider, array $credentials): Integration
    {
        if (! $business->isBusiness()) {
            throw new \InvalidArgumentException('User must be a business account');
        }

        $providerConfig = $this->providerConfigs[$provider] ?? null;
        if (! $providerConfig) {
            throw new \InvalidArgumentException("Unsupported provider: {$provider}");
        }

        $providerMeta = Integration::getAvailableProviders()[$provider] ?? null;
        if (! $providerMeta) {
            throw new \InvalidArgumentException("Provider metadata not found: {$provider}");
        }

        // Check if integration already exists
        $existingIntegration = Integration::where('business_id', $business->id)
            ->where('provider', $provider)
            ->first();

        if ($existingIntegration) {
            // Update existing integration
            $existingIntegration->update([
                'credentials' => $credentials,
                'is_active' => true,
                'connected_at' => now(),
                'sync_errors' => 0,
            ]);

            Log::info('Integration reconnected', [
                'integration_id' => $existingIntegration->id,
                'business_id' => $business->id,
                'provider' => $provider,
            ]);

            return $existingIntegration;
        }

        // Create new integration
        $integration = Integration::create([
            'business_id' => $business->id,
            'provider' => $provider,
            'name' => $providerMeta['name'],
            'type' => $providerMeta['type'],
            'credentials' => $credentials,
            'settings' => [
                'auto_sync' => true,
                'sync_interval' => 60, // minutes
            ],
            'is_active' => true,
            'connected_at' => now(),
        ]);

        Log::info('Integration connected', [
            'integration_id' => $integration->id,
            'business_id' => $business->id,
            'provider' => $provider,
        ]);

        return $integration;
    }

    /**
     * Disconnect an integration.
     */
    public function disconnect(Integration $integration): void
    {
        // Revoke tokens if OAuth
        $this->revokeOAuthTokens($integration);

        // Mark as disconnected
        $integration->markDisconnected();

        Log::info('Integration disconnected', [
            'integration_id' => $integration->id,
            'business_id' => $integration->business_id,
            'provider' => $integration->provider,
        ]);
    }

    /**
     * Sync shifts from external system.
     */
    public function syncShifts(Integration $integration): IntegrationSync
    {
        $sync = $this->createSync($integration, IntegrationSync::DIRECTION_INBOUND, IntegrationSync::ENTITY_SHIFTS);
        $sync->start();

        try {
            $externalShifts = $this->fetchShiftsFromProvider($integration);

            foreach ($externalShifts as $externalShift) {
                try {
                    $sync->incrementProcessed();
                    $this->processShiftImport($integration, $externalShift, $sync);
                } catch (\Exception $e) {
                    $sync->incrementFailed("Failed to import shift: {$e->getMessage()}");
                    Log::warning('Failed to import shift from integration', [
                        'integration_id' => $integration->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $sync->complete();
        } catch (\Exception $e) {
            $sync->fail(['main_error' => $e->getMessage()]);
            Log::error('Shift sync failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $sync->fresh();
    }

    /**
     * Sync timesheets to/from external system.
     */
    public function syncTimesheets(Integration $integration): IntegrationSync
    {
        $sync = $this->createSync($integration, IntegrationSync::DIRECTION_OUTBOUND, IntegrationSync::ENTITY_TIMESHEETS);
        $sync->start();

        try {
            // Get completed assignments for this business
            $assignments = ShiftAssignment::whereHas('shift', function ($query) use ($integration) {
                $query->where('business_id', $integration->business_id);
            })
                ->where('status', 'completed')
                ->whereNotNull('check_out_time')
                ->where('created_at', '>=', now()->subDays(7))
                ->get();

            foreach ($assignments as $assignment) {
                try {
                    $sync->incrementProcessed();
                    $this->exportTimesheetToProvider($integration, $assignment, $sync);
                } catch (\Exception $e) {
                    $sync->incrementFailed("Failed to export timesheet: {$e->getMessage()}");
                }
            }

            $sync->complete();
        } catch (\Exception $e) {
            $sync->fail(['main_error' => $e->getMessage()]);
        }

        return $sync->fresh();
    }

    /**
     * Import workers from external HR system.
     */
    public function importWorkers(Integration $integration): IntegrationSync
    {
        $sync = $this->createSync($integration, IntegrationSync::DIRECTION_INBOUND, IntegrationSync::ENTITY_WORKERS);
        $sync->start();

        try {
            $externalWorkers = $this->fetchWorkersFromProvider($integration);

            foreach ($externalWorkers as $externalWorker) {
                try {
                    $sync->incrementProcessed();
                    $this->processWorkerImport($integration, $externalWorker, $sync);
                } catch (\Exception $e) {
                    $sync->incrementFailed("Failed to import worker: {$e->getMessage()}");
                }
            }

            $sync->complete();
        } catch (\Exception $e) {
            $sync->fail(['main_error' => $e->getMessage()]);
        }

        return $sync->fresh();
    }

    /**
     * Export payroll data to external system.
     */
    public function exportPayroll(Integration $integration, Carbon $period): IntegrationSync
    {
        $sync = $this->createSync($integration, IntegrationSync::DIRECTION_OUTBOUND, IntegrationSync::ENTITY_PAYROLL);
        $sync->start();

        try {
            // Get all completed payments for the period
            $payments = DB::table('shift_payments')
                ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
                ->join('shifts', 'shift_assignments.shift_id', '=', 'shifts.id')
                ->where('shifts.business_id', $integration->business_id)
                ->where('shift_payments.status', 'paid_out')
                ->whereMonth('shift_payments.payout_completed_at', $period->month)
                ->whereYear('shift_payments.payout_completed_at', $period->year)
                ->get();

            $payrollData = $this->formatPayrollData($payments);

            foreach ($payrollData as $entry) {
                try {
                    $sync->incrementProcessed();
                    $this->exportPayrollEntryToProvider($integration, $entry, $sync);
                } catch (\Exception $e) {
                    $sync->incrementFailed("Failed to export payroll entry: {$e->getMessage()}");
                }
            }

            $sync->complete();
        } catch (\Exception $e) {
            $sync->fail(['main_error' => $e->getMessage()]);
        }

        return $sync->fresh();
    }

    /**
     * Get available integration providers.
     */
    public function getAvailableProviders(): array
    {
        return Integration::getAvailableProviders();
    }

    /**
     * Test connection to integration provider.
     */
    public function testConnection(Integration $integration): bool
    {
        try {
            $config = $this->providerConfigs[$integration->provider] ?? null;
            if (! $config) {
                return false;
            }

            // Make a test API call based on provider
            $response = $this->makeAuthenticatedRequest(
                $integration,
                'GET',
                $this->getTestEndpoint($integration->provider)
            );

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Integration connection test failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Refresh OAuth tokens if needed.
     */
    public function refreshTokensIfNeeded(Integration $integration): bool
    {
        $credentials = $integration->credentials;

        if (! isset($credentials['expires_at'])) {
            return true;
        }

        $expiresAt = Carbon::parse($credentials['expires_at']);

        // Refresh if token expires in less than 5 minutes
        if ($expiresAt->subMinutes(5)->isPast()) {
            return $this->refreshOAuthTokens($integration);
        }

        return true;
    }

    /**
     * Get sync history for an integration.
     */
    public function getSyncHistory(Integration $integration, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $integration->syncs()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get integrations for a business.
     */
    public function getBusinessIntegrations(User $business): \Illuminate\Database\Eloquent\Collection
    {
        return Integration::where('business_id', $business->id)
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a new sync record.
     */
    protected function createSync(Integration $integration, string $direction, string $entityType): IntegrationSync
    {
        return IntegrationSync::create([
            'integration_id' => $integration->id,
            'direction' => $direction,
            'entity_type' => $entityType,
            'status' => IntegrationSync::STATUS_PENDING,
        ]);
    }

    /**
     * Fetch shifts from external provider.
     */
    protected function fetchShiftsFromProvider(Integration $integration): array
    {
        $config = $this->providerConfigs[$integration->provider];

        $response = $this->makeAuthenticatedRequest(
            $integration,
            'GET',
            $this->getShiftsEndpoint($integration->provider)
        );

        if (! $response->successful()) {
            throw new \Exception("Failed to fetch shifts: {$response->status()}");
        }

        return $this->normalizeShiftsResponse($integration->provider, $response->json());
    }

    /**
     * Process shift import from external data.
     */
    protected function processShiftImport(Integration $integration, array $externalShift, IntegrationSync $sync): void
    {
        // Check if shift already exists (by external ID)
        $existingShift = Shift::where('business_id', $integration->business_id)
            ->whereJsonContains('requirements->external_id', $externalShift['external_id'] ?? null)
            ->first();

        if ($existingShift) {
            // Update existing shift
            $existingShift->update($this->mapExternalShiftToLocal($externalShift, $integration));
            $sync->incrementUpdated();
        } else {
            // Create new shift
            Shift::create(array_merge(
                $this->mapExternalShiftToLocal($externalShift, $integration),
                ['business_id' => $integration->business_id]
            ));
            $sync->incrementCreated();
        }
    }

    /**
     * Fetch workers from external HR provider.
     */
    protected function fetchWorkersFromProvider(Integration $integration): array
    {
        $response = $this->makeAuthenticatedRequest(
            $integration,
            'GET',
            $this->getWorkersEndpoint($integration->provider)
        );

        if (! $response->successful()) {
            throw new \Exception("Failed to fetch workers: {$response->status()}");
        }

        return $this->normalizeWorkersResponse($integration->provider, $response->json());
    }

    /**
     * Process worker import.
     */
    protected function processWorkerImport(Integration $integration, array $externalWorker, IntegrationSync $sync): void
    {
        // Check if worker exists by email
        $existingUser = User::where('email', $externalWorker['email'])->first();

        if ($existingUser) {
            // Could update worker profile here if needed
            $sync->incrementUpdated();
        } else {
            // Log for manual review - don't auto-create users
            Log::info('External worker found for import', [
                'integration_id' => $integration->id,
                'external_worker' => $externalWorker,
            ]);
            $sync->incrementCreated();
        }
    }

    /**
     * Export timesheet to external provider.
     */
    protected function exportTimesheetToProvider(Integration $integration, ShiftAssignment $assignment, IntegrationSync $sync): void
    {
        $timesheetData = $this->formatTimesheetData($assignment);

        $response = $this->makeAuthenticatedRequest(
            $integration,
            'POST',
            $this->getTimesheetsEndpoint($integration->provider),
            $timesheetData
        );

        if ($response->successful()) {
            $sync->incrementCreated();
        } else {
            throw new \Exception("Failed to export timesheet: {$response->status()}");
        }
    }

    /**
     * Export payroll entry to external provider.
     */
    protected function exportPayrollEntryToProvider(Integration $integration, array $entry, IntegrationSync $sync): void
    {
        $response = $this->makeAuthenticatedRequest(
            $integration,
            'POST',
            $this->getPayrollEndpoint($integration->provider),
            $entry
        );

        if ($response->successful()) {
            $sync->incrementCreated();
        } else {
            throw new \Exception("Failed to export payroll: {$response->status()}");
        }
    }

    /**
     * Make an authenticated request to the provider.
     */
    protected function makeAuthenticatedRequest(
        Integration $integration,
        string $method,
        string $endpoint,
        array $data = []
    ): \Illuminate\Http\Client\Response {
        $this->refreshTokensIfNeeded($integration);

        $config = $this->providerConfigs[$integration->provider];
        $credentials = $integration->credentials;

        $request = Http::timeout(30);

        // Add authentication based on type
        if ($config['auth_type'] === 'oauth2') {
            $request = $request->withToken($credentials['access_token'] ?? '');
        } elseif ($config['auth_type'] === 'api_key') {
            $request = $request->withHeaders([
                'Authorization' => 'Bearer '.($credentials['api_key'] ?? ''),
            ]);
        }

        $url = $config['base_url'].$endpoint;

        return match (strtoupper($method)) {
            'GET' => $request->get($url, $data),
            'POST' => $request->post($url, $data),
            'PUT' => $request->put($url, $data),
            'DELETE' => $request->delete($url),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };
    }

    /**
     * Refresh OAuth tokens.
     */
    protected function refreshOAuthTokens(Integration $integration): bool
    {
        $credentials = $integration->credentials;

        if (! isset($credentials['refresh_token'])) {
            return false;
        }

        try {
            $response = Http::post($this->getTokenRefreshUrl($integration->provider), [
                'grant_type' => 'refresh_token',
                'refresh_token' => $credentials['refresh_token'],
                'client_id' => config("services.{$integration->provider}.client_id"),
                'client_secret' => config("services.{$integration->provider}.client_secret"),
            ]);

            if ($response->successful()) {
                $newTokens = $response->json();
                $integration->update([
                    'credentials' => array_merge($credentials, [
                        'access_token' => $newTokens['access_token'],
                        'refresh_token' => $newTokens['refresh_token'] ?? $credentials['refresh_token'],
                        'expires_at' => now()->addSeconds($newTokens['expires_in'] ?? 3600)->toIso8601String(),
                    ]),
                ]);

                return true;
            }
        } catch (\Exception $e) {
            Log::error('Failed to refresh OAuth tokens', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Revoke OAuth tokens.
     */
    protected function revokeOAuthTokens(Integration $integration): void
    {
        $credentials = $integration->credentials;

        if (! isset($credentials['access_token'])) {
            return;
        }

        try {
            Http::post($this->getTokenRevokeUrl($integration->provider), [
                'token' => $credentials['access_token'],
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to revoke OAuth tokens', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get provider-specific endpoints.
     */
    protected function getTestEndpoint(string $provider): string
    {
        return match ($provider) {
            'deputy' => '/me',
            'when_i_work' => '/users/me',
            'gusto' => '/v1/me',
            'adp' => '/core/v1/workers',
            'google_calendar' => '/calendars/primary',
            'outlook' => '/me/calendar',
            'square_pos' => '/merchants/me',
            'toast_pos' => '/authentication/v1/authentication/login',
            'quickbooks' => '/company/info',
            'xero' => '/Organisation',
            default => '/',
        };
    }

    protected function getShiftsEndpoint(string $provider): string
    {
        return match ($provider) {
            'deputy' => '/resource/Roster',
            'when_i_work' => '/shifts',
            default => '/shifts',
        };
    }

    protected function getWorkersEndpoint(string $provider): string
    {
        return match ($provider) {
            'deputy' => '/resource/Employee',
            'when_i_work' => '/users',
            'gusto' => '/v1/companies/{company_id}/employees',
            'adp' => '/hr/v2/workers',
            default => '/employees',
        };
    }

    protected function getTimesheetsEndpoint(string $provider): string
    {
        return match ($provider) {
            'deputy' => '/resource/Timesheet',
            'when_i_work' => '/times',
            'adp' => '/time/v2/workers/{worker_id}/time-cards',
            default => '/timesheets',
        };
    }

    protected function getPayrollEndpoint(string $provider): string
    {
        return match ($provider) {
            'gusto' => '/v1/companies/{company_id}/payrolls',
            'adp' => '/payroll/v1/payroll-outputs',
            'quickbooks' => '/company/{company_id}/payrollitem',
            'xero' => '/PayItems',
            default => '/payroll',
        };
    }

    protected function getTokenRefreshUrl(string $provider): string
    {
        return match ($provider) {
            'google_calendar' => 'https://oauth2.googleapis.com/token',
            'outlook' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
            'quickbooks' => 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer',
            'xero' => 'https://identity.xero.com/connect/token',
            default => config("services.{$provider}.token_url", ''),
        };
    }

    protected function getTokenRevokeUrl(string $provider): string
    {
        return match ($provider) {
            'google_calendar' => 'https://oauth2.googleapis.com/revoke',
            default => config("services.{$provider}.revoke_url", ''),
        };
    }

    /**
     * Normalize shifts response from different providers.
     */
    protected function normalizeShiftsResponse(string $provider, array $response): array
    {
        return match ($provider) {
            'deputy' => collect($response)->map(fn ($s) => [
                'external_id' => $s['Id'] ?? null,
                'title' => $s['Comment'] ?? 'Shift',
                'start_time' => $s['StartTime'] ?? null,
                'end_time' => $s['EndTime'] ?? null,
                'location' => $s['Location'] ?? null,
            ])->toArray(),
            'when_i_work' => collect($response['shifts'] ?? [])->map(fn ($s) => [
                'external_id' => $s['id'] ?? null,
                'title' => $s['notes'] ?? 'Shift',
                'start_time' => $s['start_time'] ?? null,
                'end_time' => $s['end_time'] ?? null,
                'location' => $s['location_id'] ?? null,
            ])->toArray(),
            default => $response,
        };
    }

    /**
     * Normalize workers response from different providers.
     */
    protected function normalizeWorkersResponse(string $provider, array $response): array
    {
        return match ($provider) {
            'deputy' => collect($response)->map(fn ($w) => [
                'external_id' => $w['Id'] ?? null,
                'name' => ($w['FirstName'] ?? '').' '.($w['LastName'] ?? ''),
                'email' => $w['Email'] ?? null,
                'phone' => $w['Mobile'] ?? null,
            ])->toArray(),
            'when_i_work' => collect($response['users'] ?? [])->map(fn ($w) => [
                'external_id' => $w['id'] ?? null,
                'name' => ($w['first_name'] ?? '').' '.($w['last_name'] ?? ''),
                'email' => $w['email'] ?? null,
                'phone' => $w['phone_number'] ?? null,
            ])->toArray(),
            'gusto' => collect($response)->map(fn ($w) => [
                'external_id' => $w['uuid'] ?? null,
                'name' => ($w['first_name'] ?? '').' '.($w['last_name'] ?? ''),
                'email' => $w['email'] ?? null,
                'phone' => $w['phone'] ?? null,
            ])->toArray(),
            default => $response,
        };
    }

    /**
     * Map external shift data to local format.
     */
    protected function mapExternalShiftToLocal(array $externalShift, Integration $integration): array
    {
        return [
            'title' => $externalShift['title'] ?? 'Imported Shift',
            'description' => $externalShift['description'] ?? '',
            'shift_date' => Carbon::parse($externalShift['start_time'])->toDateString(),
            'start_time' => Carbon::parse($externalShift['start_time'])->toTimeString(),
            'end_time' => Carbon::parse($externalShift['end_time'])->toTimeString(),
            'requirements' => [
                'external_id' => $externalShift['external_id'],
                'imported_from' => $integration->provider,
                'imported_at' => now()->toIso8601String(),
            ],
            'status' => 'draft',
        ];
    }

    /**
     * Format timesheet data for export.
     */
    protected function formatTimesheetData(ShiftAssignment $assignment): array
    {
        return [
            'worker_id' => $assignment->worker_id,
            'worker_name' => $assignment->worker->name ?? 'Unknown',
            'shift_id' => $assignment->shift_id,
            'shift_date' => $assignment->shift->shift_date->toDateString(),
            'clock_in' => $assignment->check_in_time?->toIso8601String(),
            'clock_out' => $assignment->check_out_time?->toIso8601String(),
            'hours_worked' => $assignment->hours_worked ?? 0,
            'break_minutes' => $assignment->break_minutes ?? 0,
        ];
    }

    /**
     * Format payroll data for export.
     */
    protected function formatPayrollData($payments): array
    {
        return $payments->groupBy('worker_id')
            ->map(function ($workerPayments) {
                $first = $workerPayments->first();

                return [
                    'worker_id' => $first->worker_id,
                    'total_hours' => $workerPayments->sum('hours_worked'),
                    'total_amount' => $workerPayments->sum('amount_net'),
                    'payment_count' => $workerPayments->count(),
                ];
            })
            ->values()
            ->toArray();
    }
}
