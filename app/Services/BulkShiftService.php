<?php

namespace App\Services;

use App\Jobs\ProcessBulkShiftUpload;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Service for handling bulk shift uploads via CSV.
 * BIZ-005: Bulk Shift Posting
 */
class BulkShiftService
{
    /**
     * Generate CSV template for bulk shift upload.
     *
     * @return string CSV content
     */
    public function generateTemplate(): string
    {
        $headers = [
            'title',
            'description',
            'role_type',
            'industry',
            'location_address',
            'location_city',
            'location_state',
            'location_country',
            'shift_date',
            'start_time',
            'end_time',
            'base_rate',
            'required_workers',
            'required_skills',
            'required_certifications',
            'dress_code',
            'parking_info',
            'special_instructions',
        ];

        $exampleRow = [
            'Server for Gala Event',
            'Professional server needed for corporate gala dinner. Black tie attire required.',
            'server',
            'hospitality',
            '123 Main Street',
            'New York',
            'NY',
            'US',
            '2025-12-20',
            '18:00',
            '23:00',
            '25.00',
            '5',
            'food_service,customer_service',
            'food_handlers_certificate',
            'Black tie formal attire',
            'Valet parking available',
            'Arrive 30 minutes early for briefing',
        ];

        $csv = implode(',', $headers) . "\n";
        $csv .= implode(',', array_map(function ($value) {
            return '"' . str_replace('"', '""', $value) . '"';
        }, $exampleRow)) . "\n";

        return $csv;
    }

    /**
     * Validate CSV file and return parsed data.
     *
     * @param string $filePath
     * @return array
     */
    public function validateCsvFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [
                'valid' => false,
                'errors' => ['File not found'],
                'data' => [],
            ];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [
                'valid' => false,
                'errors' => ['Unable to read file'],
                'data' => [],
            ];
        }

        // Read headers
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return [
                'valid' => false,
                'errors' => ['Invalid CSV format - no headers found'],
                'data' => [],
            ];
        }

        // Validate required headers
        $requiredHeaders = [
            'title',
            'description',
            'location_address',
            'location_city',
            'location_state',
            'shift_date',
            'start_time',
            'end_time',
            'base_rate',
            'required_workers',
        ];

        $missingHeaders = array_diff($requiredHeaders, $headers);
        if (!empty($missingHeaders)) {
            fclose($handle);
            return [
                'valid' => false,
                'errors' => ['Missing required headers: ' . implode(', ', $missingHeaders)],
                'data' => [],
            ];
        }

        // Parse rows
        $rows = [];
        $rowNumber = 1; // Start at 1 (header is row 0)
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Combine headers with row data
            $rowData = array_combine($headers, $row);

            // Validate row
            $rowErrors = $this->validateRow($rowData, $rowNumber);

            if (!empty($rowErrors)) {
                $errors = array_merge($errors, $rowErrors);
            } else {
                $rows[] = [
                    'row_number' => $rowNumber,
                    'data' => $rowData,
                ];
            }
        }

        fclose($handle);

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $rows,
            'total_rows' => count($rows),
        ];
    }

    /**
     * Validate a single row of CSV data.
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return array
     */
    protected function validateRow(array $rowData, int $rowNumber): array
    {
        $errors = [];

        $validator = Validator::make($rowData, [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location_address' => 'required|string',
            'location_city' => 'required|string',
            'location_state' => 'required|string',
            'location_country' => 'nullable|string|size:2',
            'shift_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'base_rate' => 'required|numeric|min:0',
            'required_workers' => 'required|integer|min:1|max:100',
            'industry' => 'nullable|string',
            'role_type' => 'nullable|string',
            'required_skills' => 'nullable|string',
            'required_certifications' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $errors[] = "Row {$rowNumber}: {$error}";
            }
        }

        return $errors;
    }

    /**
     * Process bulk shift upload (dispatches job for async processing).
     *
     * @param User $business
     * @param array $validatedData
     * @param string $batchId
     * @return array
     */
    public function processBulkUpload(User $business, array $validatedData, string $batchId): array
    {
        // For small uploads (< 50 rows), process synchronously
        if (count($validatedData) < 50) {
            return $this->processShifts($business, $validatedData, $batchId);
        }

        // For large uploads, dispatch job
        ProcessBulkShiftUpload::dispatch($business->id, $validatedData, $batchId);

        return [
            'processing' => 'async',
            'batch_id' => $batchId,
            'total_rows' => count($validatedData),
            'message' => 'Your bulk upload is being processed. You will be notified when complete.',
        ];
    }

    /**
     * Process shifts synchronously.
     *
     * @param User $business
     * @param array $validatedData
     * @param string $batchId
     * @return array
     */
    public function processShifts(User $business, array $validatedData, string $batchId): array
    {
        $results = [
            'batch_id' => $batchId,
            'total_rows' => count($validatedData),
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
            'shift_ids' => [],
        ];

        foreach ($validatedData as $item) {
            try {
                $shift = $this->createShiftFromRow($business, $item['data']);
                $results['successful']++;
                $results['shift_ids'][] = $shift->id;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'row' => $item['row_number'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Create shift from CSV row data.
     *
     * @param User $business
     * @param array $rowData
     * @return Shift
     */
    protected function createShiftFromRow(User $business, array $rowData): Shift
    {
        // Parse dates and times
        $shiftDate = \Carbon\Carbon::parse($rowData['shift_date']);
        $startTime = \Carbon\Carbon::parse($rowData['shift_date'] . ' ' . $rowData['start_time']);
        $endTime = \Carbon\Carbon::parse($rowData['shift_date'] . ' ' . $rowData['end_time']);

        // Calculate duration
        $durationHours = $startTime->floatDiffInHours($endTime);

        // Parse comma-separated lists
        $requiredSkills = !empty($rowData['required_skills'])
            ? array_map('trim', explode(',', $rowData['required_skills']))
            : null;

        $requiredCertifications = !empty($rowData['required_certifications'])
            ? array_map('trim', explode(',', $rowData['required_certifications']))
            : null;

        // Create shift
        $shift = Shift::create([
            'business_id' => $business->id,
            'title' => $rowData['title'],
            'description' => $rowData['description'],
            'role_type' => $rowData['role_type'] ?? null,
            'industry' => $rowData['industry'] ?? 'general',
            'location_address' => $rowData['location_address'],
            'location_city' => $rowData['location_city'],
            'location_state' => $rowData['location_state'],
            'location_country' => $rowData['location_country'] ?? 'US',
            'shift_date' => $shiftDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_hours' => $durationHours,
            'base_rate' => (float) $rowData['base_rate'] * 100, // Convert to cents
            'required_workers' => (int) $rowData['required_workers'],
            'filled_workers' => 0,
            'required_skills' => $requiredSkills,
            'required_certifications' => $requiredCertifications,
            'dress_code' => $rowData['dress_code'] ?? null,
            'parking_info' => $rowData['parking_info'] ?? null,
            'special_instructions' => $rowData['special_instructions'] ?? null,
            'status' => 'draft', // Start as draft for review
            'urgency_level' => $this->calculateUrgencyLevel($startTime),
            'geofence_radius' => 100, // Default 100 meters
            'early_clockin_minutes' => 30,
            'late_grace_minutes' => 10,
        ]);

        // Calculate pricing
        $shift->calculatePricing();

        return $shift;
    }

    /**
     * Calculate urgency level based on shift start time.
     *
     * @param \Carbon\Carbon $startTime
     * @return string
     */
    protected function calculateUrgencyLevel(\Carbon\Carbon $startTime): string
    {
        $hoursUntilShift = now()->floatDiffInHours($startTime, false);

        if ($hoursUntilShift < 24) {
            return 'critical';
        } elseif ($hoursUntilShift < 48) {
            return 'high';
        } elseif ($hoursUntilShift < 168) { // 7 days
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get bulk upload results.
     *
     * @param string $batchId
     * @return array|null
     */
    public function getUploadResults(string $batchId): ?array
    {
        // In a real implementation, you would store results in cache or database
        // For now, return null (implement caching as needed)
        return null;
    }

    /**
     * Generate batch ID for tracking.
     *
     * @return string
     */
    public function generateBatchId(): string
    {
        return 'bulk_' . Str::random(16) . '_' . time();
    }
}
