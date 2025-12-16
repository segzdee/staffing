<?php

namespace App\Services;

use App\Models\AgencyInvitation;
use App\Models\AgencyWorker;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\Skill;
use App\Models\Certification;
use App\Notifications\AgencyWorkerInvitationNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * AGY-REG-004: Worker Pool Onboarding Service
 *
 * Service for importing workers from CSV files, validating worker data,
 * creating/updating workers, and sending bulk invitations.
 */
class AgencyWorkerImportService
{
    /**
     * Required CSV columns
     */
    protected const REQUIRED_COLUMNS = ['email'];

    /**
     * Optional CSV columns
     */
    protected const OPTIONAL_COLUMNS = [
        'name',
        'phone',
        'commission_rate',
        'skills',
        'certifications',
        'notes',
        'personal_message',
    ];

    /**
     * Import results
     */
    protected array $results = [
        'total' => 0,
        'successful' => 0,
        'failed' => 0,
        'skipped' => 0,
        'invited' => 0,
        'existing' => 0,
        'errors' => [],
        'created_workers' => [],
        'invited_workers' => [],
    ];

    /**
     * Import workers from a CSV file.
     *
     * @param UploadedFile $file
     * @param int $agencyId
     * @param array $options
     * @return array Import results
     */
    public function importFromCsv(UploadedFile $file, int $agencyId, array $options = []): array
    {
        $this->resetResults();

        $defaultOptions = [
            'send_invitations' => true,
            'skip_existing_workers' => true,
            'skip_existing_invitations' => true,
            'default_commission_rate' => null,
        ];

        $options = array_merge($defaultOptions, $options);
        $batchId = Str::uuid()->toString();

        // Parse CSV
        $rows = $this->parseCsv($file);

        if (empty($rows)) {
            $this->results['errors'][] = 'CSV file is empty or could not be parsed.';
            return $this->results;
        }

        $this->results['total'] = count($rows);

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // Account for header row

                // Validate row data
                $validation = $this->validateWorkerData($row);

                if (!$validation['valid']) {
                    $this->results['failed']++;
                    $this->results['errors'][] = "Row {$rowNumber}: " . implode(', ', $validation['errors']);
                    continue;
                }

                // Check for duplicates
                $duplicate = $this->checkDuplicate($row['email'], $agencyId, $options);

                if ($duplicate['is_duplicate']) {
                    if ($duplicate['type'] === 'worker') {
                        $this->results['existing']++;
                        $this->results['skipped']++;
                    } elseif ($duplicate['type'] === 'invitation') {
                        $this->results['skipped']++;
                    }

                    if ($options['skip_existing_workers'] || $options['skip_existing_invitations']) {
                        continue;
                    }
                }

                // Process worker
                $result = $this->createOrUpdateWorker($row, $agencyId, $batchId, $options);

                if ($result['success']) {
                    $this->results['successful']++;

                    if ($result['action'] === 'invited') {
                        $this->results['invited']++;
                        $this->results['invited_workers'][] = $result['data'];
                    } elseif ($result['action'] === 'linked') {
                        $this->results['created_workers'][] = $result['data'];
                    }
                } else {
                    $this->results['failed']++;
                    $this->results['errors'][] = "Row {$rowNumber}: " . $result['error'];
                }
            }

            DB::commit();

            // Send bulk invitations if enabled
            if ($options['send_invitations'] && !empty($this->results['invited_workers'])) {
                $this->sendBulkInvitations($this->results['invited_workers']);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AgencyWorkerImportService: Import failed', [
                'agency_id' => $agencyId,
                'error' => $e->getMessage(),
            ]);
            $this->results['errors'][] = 'Import failed: ' . $e->getMessage();
        }

        return $this->results;
    }

    /**
     * Validate worker data from a CSV row.
     *
     * @param array $row
     * @return array Validation result
     */
    public function validateWorkerData(array $row): array
    {
        $errors = [];

        // Check required fields
        foreach (self::REQUIRED_COLUMNS as $column) {
            if (empty($row[$column])) {
                $errors[] = "Missing required field: {$column}";
            }
        }

        // Validate email
        if (!empty($row['email']) && !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format: {$row['email']}";
        }

        // Validate phone if provided
        if (!empty($row['phone'])) {
            $phone = preg_replace('/[^0-9+]/', '', $row['phone']);
            if (strlen($phone) < 10) {
                $errors[] = "Invalid phone number: {$row['phone']}";
            }
        }

        // Validate commission rate if provided
        if (!empty($row['commission_rate'])) {
            $rate = floatval($row['commission_rate']);
            if ($rate < 0 || $rate > 100) {
                $errors[] = "Commission rate must be between 0 and 100: {$row['commission_rate']}";
            }
        }

        // Validate skills format if provided (comma-separated)
        if (!empty($row['skills'])) {
            $skills = $this->parseCommaSeparated($row['skills']);
            if (empty($skills)) {
                $errors[] = "Invalid skills format. Use comma-separated values.";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Create or update a worker and/or invitation.
     *
     * @param array $data
     * @param int $agencyId
     * @param string $batchId
     * @param array $options
     * @return array Result
     */
    public function createOrUpdateWorker(array $data, int $agencyId, string $batchId, array $options = []): array
    {
        $email = strtolower(trim($data['email']));
        $name = $data['name'] ?? null;
        $phone = $data['phone'] ?? null;

        // Get commission rate (row value > option default > agency default)
        $commissionRate = null;
        if (!empty($data['commission_rate'])) {
            $commissionRate = floatval($data['commission_rate']);
        } elseif (!empty($options['default_commission_rate'])) {
            $commissionRate = floatval($options['default_commission_rate']);
        }

        // Parse skills and certifications
        $skills = !empty($data['skills']) ? $this->parseCommaSeparated($data['skills']) : null;
        $certifications = !empty($data['certifications']) ? $this->parseCommaSeparated($data['certifications']) : null;

        // Resolve skill IDs
        $skillIds = null;
        if ($skills) {
            $skillIds = $this->resolveSkillIds($skills);
        }

        // Resolve certification IDs
        $certificationIds = null;
        if ($certifications) {
            $certificationIds = $this->resolveCertificationIds($certifications);
        }

        try {
            // Check if user already exists
            $existingUser = User::where('email', $email)->first();

            if ($existingUser && $existingUser->isWorker()) {
                // Worker exists - link them to agency
                return $this->linkExistingWorker($existingUser, $agencyId, $commissionRate, $data['notes'] ?? null);
            }

            // Create invitation for new worker
            $invitation = AgencyInvitation::create([
                'agency_id' => $agencyId,
                'email' => $email,
                'phone' => $phone,
                'name' => $name,
                'type' => 'bulk',
                'status' => 'pending',
                'preset_commission_rate' => $commissionRate,
                'preset_skills' => $skillIds,
                'preset_certifications' => $certificationIds,
                'personal_message' => $data['personal_message'] ?? null,
                'batch_id' => $batchId,
                'invitation_ip' => request()->ip(),
            ]);

            return [
                'success' => true,
                'action' => 'invited',
                'data' => $invitation,
            ];

        } catch (\Exception $e) {
            Log::error('AgencyWorkerImportService: Failed to process worker', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Link an existing worker to an agency.
     *
     * @param User $worker
     * @param int $agencyId
     * @param float|null $commissionRate
     * @param string|null $notes
     * @return array
     */
    protected function linkExistingWorker(User $worker, int $agencyId, ?float $commissionRate, ?string $notes): array
    {
        // Check if already linked
        $existing = AgencyWorker::where('agency_id', $agencyId)
            ->where('worker_id', $worker->id)
            ->first();

        if ($existing) {
            // Reactivate if removed
            if ($existing->status === 'removed') {
                $existing->update([
                    'status' => 'active',
                    'commission_rate' => $commissionRate ?? $existing->commission_rate,
                    'notes' => $notes ?? $existing->notes,
                    'added_at' => now(),
                    'removed_at' => null,
                ]);

                return [
                    'success' => true,
                    'action' => 'reactivated',
                    'data' => $existing->fresh(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Worker is already in this agency.',
            ];
        }

        // Create agency-worker relationship
        $agencyWorker = AgencyWorker::create([
            'agency_id' => $agencyId,
            'worker_id' => $worker->id,
            'commission_rate' => $commissionRate ?? 0,
            'status' => 'active',
            'notes' => $notes,
            'added_at' => now(),
        ]);

        return [
            'success' => true,
            'action' => 'linked',
            'data' => $agencyWorker,
        ];
    }

    /**
     * Send bulk invitations.
     *
     * @param array $invitations
     * @return int Number sent
     */
    public function sendBulkInvitations(array $invitations): int
    {
        $sent = 0;

        foreach ($invitations as $invitation) {
            if ($invitation instanceof AgencyInvitation) {
                try {
                    $this->sendInvitationEmail($invitation);
                    $invitation->markAsSent();
                    $sent++;
                } catch (\Exception $e) {
                    Log::error('AgencyWorkerImportService: Failed to send invitation', [
                        'invitation_id' => $invitation->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $sent;
    }

    /**
     * Send invitation email.
     *
     * @param AgencyInvitation $invitation
     * @return void
     */
    protected function sendInvitationEmail(AgencyInvitation $invitation): void
    {
        // Create a notifiable instance for email
        $notifiable = new \App\Mail\NotifiableEmail($invitation->email, $invitation->name);

        try {
            $notifiable->notify(new AgencyWorkerInvitationNotification($invitation));
        } catch (\Exception $e) {
            // Fallback to direct mail if notification fails
            Log::warning('AgencyWorkerImportService: Notification failed, using direct mail', [
                'error' => $e->getMessage(),
            ]);

            \Mail::to($invitation->email)->send(new \App\Mail\AgencyWorkerInvitationMail($invitation));
        }
    }

    /**
     * Parse a CSV file into an array of rows.
     *
     * @param UploadedFile $file
     * @return array
     */
    protected function parseCsv(UploadedFile $file): array
    {
        $rows = [];
        $headers = [];

        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return $rows;
        }

        $lineNumber = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $lineNumber++;

            // First row is headers
            if ($lineNumber === 1) {
                $headers = array_map(function ($header) {
                    return strtolower(trim($header));
                }, $data);
                continue;
            }

            // Skip empty rows
            if (empty(array_filter($data))) {
                continue;
            }

            // Map data to headers
            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = isset($data[$index]) ? trim($data[$index]) : null;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Check for duplicate workers or invitations.
     *
     * @param string $email
     * @param int $agencyId
     * @param array $options
     * @return array
     */
    protected function checkDuplicate(string $email, int $agencyId, array $options): array
    {
        $email = strtolower(trim($email));

        // Check for existing worker in agency
        if ($options['skip_existing_workers']) {
            $existingWorker = User::where('email', $email)
                ->where('user_type', 'worker')
                ->first();

            if ($existingWorker) {
                $inAgency = AgencyWorker::where('agency_id', $agencyId)
                    ->where('worker_id', $existingWorker->id)
                    ->where('status', 'active')
                    ->exists();

                if ($inAgency) {
                    return ['is_duplicate' => true, 'type' => 'worker'];
                }
            }
        }

        // Check for pending invitation
        if ($options['skip_existing_invitations']) {
            $existingInvitation = AgencyInvitation::where('agency_id', $agencyId)
                ->where('email', $email)
                ->whereIn('status', ['pending', 'sent', 'viewed'])
                ->where('expires_at', '>', now())
                ->exists();

            if ($existingInvitation) {
                return ['is_duplicate' => true, 'type' => 'invitation'];
            }
        }

        return ['is_duplicate' => false, 'type' => null];
    }

    /**
     * Parse comma-separated values.
     *
     * @param string $value
     * @return array
     */
    protected function parseCommaSeparated(string $value): array
    {
        return array_filter(array_map('trim', explode(',', $value)));
    }

    /**
     * Resolve skill names to IDs.
     *
     * @param array $skillNames
     * @return array|null
     */
    protected function resolveSkillIds(array $skillNames): ?array
    {
        if (empty($skillNames)) {
            return null;
        }

        $ids = [];

        foreach ($skillNames as $name) {
            $skill = Skill::where('name', 'LIKE', $name)
                ->orWhere('slug', 'LIKE', Str::slug($name))
                ->first();

            if ($skill) {
                $ids[] = $skill->id;
            }
        }

        return !empty($ids) ? $ids : null;
    }

    /**
     * Resolve certification names to IDs.
     *
     * @param array $certNames
     * @return array|null
     */
    protected function resolveCertificationIds(array $certNames): ?array
    {
        if (empty($certNames)) {
            return null;
        }

        $ids = [];

        foreach ($certNames as $name) {
            $cert = Certification::where('name', 'LIKE', $name)
                ->orWhere('short_code', 'LIKE', $name)
                ->first();

            if ($cert) {
                $ids[] = $cert->id;
            }
        }

        return !empty($ids) ? $ids : null;
    }

    /**
     * Reset results array.
     *
     * @return void
     */
    protected function resetResults(): void
    {
        $this->results = [
            'total' => 0,
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'invited' => 0,
            'existing' => 0,
            'errors' => [],
            'created_workers' => [],
            'invited_workers' => [],
        ];
    }

    /**
     * Get CSV template content.
     *
     * @return string
     */
    public static function getCsvTemplate(): string
    {
        $headers = array_merge(self::REQUIRED_COLUMNS, self::OPTIONAL_COLUMNS);

        $content = implode(',', $headers) . "\n";
        $content .= "john@example.com,John Smith,+1234567890,15.00,\"Bartending, Customer Service\",\"Food Handling\",\"Great worker\",\"Welcome to our agency!\"\n";
        $content .= "jane@example.com,Jane Doe,+0987654321,12.50,\"Waitstaff, Hosting\",\"ServSafe\",\"Reliable\",\"\"\n";

        return $content;
    }

    /**
     * Validate CSV file before import.
     *
     * @param UploadedFile $file
     * @return array Validation result
     */
    public function validateCsvFile(UploadedFile $file): array
    {
        $errors = [];

        // Check file type
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();

        if (!in_array($extension, ['csv', 'txt'])) {
            $errors[] = 'File must be a CSV file.';
        }

        if (!in_array($mimeType, ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])) {
            $errors[] = 'Invalid file type. Please upload a CSV file.';
        }

        // Check file size (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            $errors[] = 'File size exceeds 5MB limit.';
        }

        if (!empty($errors)) {
            return ['valid' => false, 'errors' => $errors];
        }

        // Parse and validate headers
        $rows = $this->parseCsv($file);

        if (empty($rows)) {
            return [
                'valid' => false,
                'errors' => ['CSV file is empty or has no data rows.'],
            ];
        }

        // Check first row has required columns
        $firstRow = $rows[0];
        $missingColumns = [];

        foreach (self::REQUIRED_COLUMNS as $column) {
            if (!array_key_exists($column, $firstRow)) {
                $missingColumns[] = $column;
            }
        }

        if (!empty($missingColumns)) {
            return [
                'valid' => false,
                'errors' => ['Missing required columns: ' . implode(', ', $missingColumns)],
            ];
        }

        return [
            'valid' => true,
            'row_count' => count($rows),
            'columns' => array_keys($firstRow),
        ];
    }

    /**
     * Get import statistics for an agency.
     *
     * @param int $agencyId
     * @return array
     */
    public function getImportStatistics(int $agencyId): array
    {
        $totalInvitations = AgencyInvitation::where('agency_id', $agencyId)->count();
        $pendingInvitations = AgencyInvitation::where('agency_id', $agencyId)
            ->whereIn('status', ['pending', 'sent', 'viewed'])
            ->count();
        $acceptedInvitations = AgencyInvitation::where('agency_id', $agencyId)
            ->where('status', 'accepted')
            ->count();
        $expiredInvitations = AgencyInvitation::where('agency_id', $agencyId)
            ->where('status', 'expired')
            ->count();

        $totalWorkers = AgencyWorker::where('agency_id', $agencyId)->count();
        $activeWorkers = AgencyWorker::where('agency_id', $agencyId)
            ->where('status', 'active')
            ->count();

        $recentBatches = AgencyInvitation::where('agency_id', $agencyId)
            ->whereNotNull('batch_id')
            ->select('batch_id', DB::raw('COUNT(*) as count'), DB::raw('MIN(created_at) as created_at'))
            ->groupBy('batch_id')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'invitations' => [
                'total' => $totalInvitations,
                'pending' => $pendingInvitations,
                'accepted' => $acceptedInvitations,
                'expired' => $expiredInvitations,
            ],
            'workers' => [
                'total' => $totalWorkers,
                'active' => $activeWorkers,
            ],
            'recent_batches' => $recentBatches,
        ];
    }
}
