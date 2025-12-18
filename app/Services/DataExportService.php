<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * GLO-005: GDPR/CCPA Compliance - Data Export Service
 *
 * Generates comprehensive data exports for users in compliance with
 * GDPR Article 15 (Right of Access) and Article 20 (Right to Data Portability).
 */
class DataExportService
{
    /**
     * Generate a complete data export for a user.
     * GDPR Article 15 - Right of Access
     */
    public function generateDataExport(User $user): array
    {
        return [
            'export_info' => $this->getExportInfo($user),
            'personal_information' => $this->getPersonalInformation($user),
            'profile_data' => $this->getProfileData($user),
            'shift_history' => $this->getShiftHistory($user),
            'payment_history' => $this->getPaymentHistory($user),
            'messages' => $this->getMessages($user),
            'ratings' => $this->getRatings($user),
            'documents' => $this->getDocuments($user),
            'consent_records' => $this->getConsentRecords($user),
            'activity_log' => $this->getActivityLog($user),
            'preferences' => $this->getPreferences($user),
        ];
    }

    /**
     * Generate a portable data export (machine-readable format).
     * GDPR Article 20 - Right to Data Portability
     */
    public function generatePortableExport(User $user): array
    {
        return [
            'schema_version' => '1.0',
            'export_format' => 'JSON',
            'export_date' => now()->toIso8601String(),
            'data_controller' => [
                'name' => config('app.name'),
                'contact' => config('mail.from.address'),
            ],
            'subject' => [
                'id' => $user->id,
                'email' => $user->email,
            ],
            'personal_data' => $this->getPersonalInformation($user),
            'profile' => $this->getProfileData($user),
            'work_history' => $this->getPortableShiftHistory($user),
            'financial_data' => $this->getPortablePaymentHistory($user),
            'communications' => $this->getPortableMessages($user),
            'reputation' => $this->getPortableRatings($user),
            'certifications' => $this->getCertifications($user),
            'skills' => $this->getSkills($user),
        ];
    }

    /**
     * Get export metadata.
     */
    protected function getExportInfo(User $user): array
    {
        return [
            'export_date' => now()->toIso8601String(),
            'export_format' => 'JSON/PDF',
            'data_controller' => config('app.name'),
            'data_protection_officer' => config('app.dpo_email', 'dpo@'.parse_url(config('app.url'), PHP_URL_HOST)),
            'subject_id' => $user->id,
            'subject_email' => $user->email,
            'legal_basis' => 'GDPR Article 15 - Right of Access',
        ];
    }

    /**
     * Get personal information.
     */
    protected function getPersonalInformation(User $user): array
    {
        return [
            'basic_info' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'user_type' => $user->user_type,
                'role' => $user->role,
                'status' => $user->status,
                'created_at' => $user->created_at?->toIso8601String(),
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            ],
            'contact_info' => [
                'bio' => $user->bio,
                'language' => $user->language,
                'country' => $user->getCountry(),
            ],
            'account_status' => [
                'is_verified_worker' => $user->is_verified_worker ?? false,
                'is_verified_business' => $user->is_verified_business ?? false,
                'onboarding_completed' => $user->onboarding_completed ?? false,
                'mfa_enabled' => $user->mfa_enabled ?? false,
            ],
            'statistics' => [
                'total_shifts_completed' => $user->total_shifts_completed ?? 0,
                'total_shifts_posted' => $user->total_shifts_posted ?? 0,
                'rating_as_worker' => $user->rating_as_worker,
                'rating_as_business' => $user->rating_as_business,
                'reliability_score' => $user->reliability_score,
            ],
        ];
    }

    /**
     * Get profile data based on user type.
     */
    protected function getProfileData(User $user): array
    {
        $profileData = [];

        if ($user->isWorker() && $user->workerProfile) {
            $profile = $user->workerProfile;
            $profileData['worker_profile'] = [
                'phone' => $profile->phone,
                'address' => $profile->address,
                'city' => $profile->city,
                'country' => $profile->country,
                'date_of_birth' => $profile->date_of_birth,
                'gender' => $profile->gender,
                'nationality' => $profile->nationality,
                'preferred_industries' => $profile->preferred_industries,
                'preferred_locations' => $profile->preferred_locations,
                'max_commute_distance' => $profile->max_commute_distance,
                'hourly_rate_min' => $profile->hourly_rate_min,
                'hourly_rate_max' => $profile->hourly_rate_max,
                'availability_status' => $profile->availability_status,
                'emergency_contact_name' => $profile->emergency_contact_name,
                'emergency_contact_phone' => $profile->emergency_contact_phone,
                'work_eligibility_status' => $profile->work_eligibility_status,
                'created_at' => $profile->created_at?->toIso8601String(),
                'updated_at' => $profile->updated_at?->toIso8601String(),
            ];
        }

        if ($user->isBusiness() && $user->businessProfile) {
            $profile = $user->businessProfile;
            $profileData['business_profile'] = [
                'company_name' => $profile->company_name,
                'trading_name' => $profile->trading_name,
                'company_registration_number' => $profile->company_registration_number,
                'vat_number' => $profile->vat_number,
                'industry' => $profile->industry,
                'company_size' => $profile->company_size,
                'address' => $profile->address,
                'city' => $profile->city,
                'country' => $profile->country,
                'contact_name' => $profile->contact_name,
                'contact_email' => $profile->contact_email,
                'contact_phone' => $profile->contact_phone,
                'website' => $profile->website,
                'created_at' => $profile->created_at?->toIso8601String(),
                'updated_at' => $profile->updated_at?->toIso8601String(),
            ];
        }

        if ($user->isAgency() && $user->agencyProfile) {
            $profile = $user->agencyProfile;
            $profileData['agency_profile'] = [
                'agency_name' => $profile->agency_name,
                'license_number' => $profile->license_number,
                'industries_served' => $profile->industries_served,
                'regions_served' => $profile->regions_served,
                'worker_count' => $profile->worker_count,
                'commission_rate' => $profile->commission_rate,
                'contact_name' => $profile->contact_name,
                'contact_email' => $profile->contact_email,
                'contact_phone' => $profile->contact_phone,
                'address' => $profile->address,
                'created_at' => $profile->created_at?->toIso8601String(),
                'updated_at' => $profile->updated_at?->toIso8601String(),
            ];
        }

        return $profileData;
    }

    /**
     * Get shift history.
     */
    protected function getShiftHistory(User $user): array
    {
        $shifts = [];

        // Shifts as worker
        if ($user->isWorker()) {
            $assignments = $user->shiftAssignments()
                ->with('shift.business')
                ->get();

            foreach ($assignments as $assignment) {
                $shift = $assignment->shift;
                if (! $shift) {
                    continue;
                }

                $shifts[] = [
                    'type' => 'worked',
                    'shift_id' => $shift->id,
                    'title' => $shift->title,
                    'business' => $shift->business?->name ?? 'Unknown',
                    'industry' => $shift->industry,
                    'location' => [
                        'address' => $shift->location_address,
                        'city' => $shift->location_city,
                        'country' => $shift->location_country,
                    ],
                    'date' => $shift->shift_date?->toDateString(),
                    'start_time' => $shift->start_time,
                    'end_time' => $shift->end_time,
                    'status' => $assignment->status,
                    'check_in_time' => $assignment->check_in_time,
                    'check_out_time' => $assignment->check_out_time,
                    'hours_worked' => $assignment->hours_worked,
                    'created_at' => $assignment->created_at?->toIso8601String(),
                ];
            }
        }

        // Shifts as business
        if ($user->isBusiness()) {
            $postedShifts = $user->postedShifts()
                ->with('assignments.worker')
                ->get();

            foreach ($postedShifts as $shift) {
                $shifts[] = [
                    'type' => 'posted',
                    'shift_id' => $shift->id,
                    'title' => $shift->title,
                    'industry' => $shift->industry,
                    'location' => [
                        'address' => $shift->location_address,
                        'city' => $shift->location_city,
                        'country' => $shift->location_country,
                    ],
                    'date' => $shift->shift_date?->toDateString(),
                    'start_time' => $shift->start_time,
                    'end_time' => $shift->end_time,
                    'status' => $shift->status,
                    'required_workers' => $shift->required_workers,
                    'filled_workers' => $shift->filled_workers,
                    'base_rate' => $shift->base_rate,
                    'created_at' => $shift->created_at?->toIso8601String(),
                ];
            }
        }

        return $shifts;
    }

    /**
     * Get portable shift history (simplified for portability).
     */
    protected function getPortableShiftHistory(User $user): array
    {
        return array_map(function ($shift) {
            return [
                'role' => $shift['type'],
                'title' => $shift['title'],
                'date' => $shift['date'],
                'hours' => $shift['hours_worked'] ?? null,
                'status' => $shift['status'],
            ];
        }, $this->getShiftHistory($user));
    }

    /**
     * Get payment history.
     */
    protected function getPaymentHistory(User $user): array
    {
        $payments = [];

        // Payments received (as worker)
        $received = $user->shiftPaymentsReceived()->with('shift')->get();
        foreach ($received as $payment) {
            $payments[] = [
                'type' => 'received',
                'payment_id' => $payment->id,
                'shift_id' => $payment->shift_id,
                'shift_title' => $payment->shift?->title,
                'amount' => $payment->worker_payout,
                'currency' => $payment->currency ?? 'GBP',
                'status' => $payment->status,
                'payment_method' => $payment->payment_method,
                'paid_at' => $payment->paid_at,
                'created_at' => $payment->created_at?->toIso8601String(),
            ];
        }

        // Payments made (as business)
        $made = $user->shiftPaymentsMade()->with('shift')->get();
        foreach ($made as $payment) {
            $payments[] = [
                'type' => 'paid',
                'payment_id' => $payment->id,
                'shift_id' => $payment->shift_id,
                'shift_title' => $payment->shift?->title,
                'amount' => $payment->total_amount,
                'currency' => $payment->currency ?? 'GBP',
                'status' => $payment->status,
                'payment_method' => $payment->payment_method,
                'paid_at' => $payment->paid_at,
                'created_at' => $payment->created_at?->toIso8601String(),
            ];
        }

        return $payments;
    }

    /**
     * Get portable payment history.
     */
    protected function getPortablePaymentHistory(User $user): array
    {
        return array_map(function ($payment) {
            return [
                'type' => $payment['type'],
                'amount' => $payment['amount'],
                'currency' => $payment['currency'],
                'date' => $payment['paid_at'] ?? $payment['created_at'],
                'status' => $payment['status'],
            ];
        }, $this->getPaymentHistory($user));
    }

    /**
     * Get messages.
     */
    protected function getMessages(User $user): array
    {
        $messages = [];

        // Sent messages
        $sent = $user->sentMessages()->with(['toUser', 'conversation'])->get();
        foreach ($sent as $message) {
            $messages[] = [
                'type' => 'sent',
                'message_id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'to' => $message->toUser?->email ?? 'Unknown',
                'content' => $message->content,
                'read' => $message->read,
                'sent_at' => $message->created_at?->toIso8601String(),
            ];
        }

        // Received messages
        $received = $user->receivedMessages()->with(['fromUser', 'conversation'])->get();
        foreach ($received as $message) {
            $messages[] = [
                'type' => 'received',
                'message_id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'from' => $message->fromUser?->email ?? 'Unknown',
                'content' => $message->content,
                'read' => $message->read,
                'received_at' => $message->created_at?->toIso8601String(),
            ];
        }

        return $messages;
    }

    /**
     * Get portable messages (content only).
     */
    protected function getPortableMessages(User $user): array
    {
        return array_map(function ($message) {
            return [
                'direction' => $message['type'],
                'content' => $message['content'],
                'timestamp' => $message['sent_at'] ?? $message['received_at'],
            ];
        }, $this->getMessages($user));
    }

    /**
     * Get ratings.
     */
    protected function getRatings(User $user): array
    {
        $ratings = [
            'given' => [],
            'received' => [],
        ];

        // Ratings given
        foreach ($user->ratingsGiven()->with('rated')->get() as $rating) {
            $ratings['given'][] = [
                'rating_id' => $rating->id,
                'rated_user' => $rating->rated?->email ?? 'Unknown',
                'rating' => $rating->rating,
                'review' => $rating->review,
                'shift_id' => $rating->shift_id,
                'created_at' => $rating->created_at?->toIso8601String(),
            ];
        }

        // Ratings received
        foreach ($user->ratingsReceived()->with('rater')->get() as $rating) {
            $ratings['received'][] = [
                'rating_id' => $rating->id,
                'rater' => $rating->rater?->email ?? 'Unknown',
                'rating' => $rating->rating,
                'review' => $rating->review,
                'shift_id' => $rating->shift_id,
                'created_at' => $rating->created_at?->toIso8601String(),
            ];
        }

        return $ratings;
    }

    /**
     * Get portable ratings.
     */
    protected function getPortableRatings(User $user): array
    {
        $ratings = $this->getRatings($user);

        return [
            'average_received' => count($ratings['received']) > 0
                ? round(collect($ratings['received'])->avg('rating'), 2)
                : null,
            'total_received' => count($ratings['received']),
            'total_given' => count($ratings['given']),
        ];
    }

    /**
     * Get documents list.
     */
    protected function getDocuments(User $user): array
    {
        $documents = [];

        // Worker certifications
        if ($user->isWorker()) {
            $certifications = $user->certifications()->get();
            foreach ($certifications as $cert) {
                $documents[] = [
                    'type' => 'certification',
                    'name' => $cert->name,
                    'certification_number' => $cert->pivot->certification_number ?? null,
                    'issue_date' => $cert->pivot->issue_date ?? null,
                    'expiry_date' => $cert->pivot->expiry_date ?? null,
                    'verified' => $cert->pivot->verified ?? false,
                ];
            }
        }

        return $documents;
    }

    /**
     * Get consent records.
     */
    protected function getConsentRecords(User $user): array
    {
        return $user->consents ?? \App\Models\ConsentRecord::where('user_id', $user->id)
            ->get()
            ->map(function ($consent) {
                return [
                    'type' => $consent->consent_type,
                    'consented' => $consent->consented,
                    'consented_at' => $consent->consented_at?->toIso8601String(),
                    'withdrawn_at' => $consent->withdrawn_at?->toIso8601String(),
                    'source' => $consent->consent_source,
                    'version' => $consent->consent_version,
                ];
            })
            ->toArray();
    }

    /**
     * Get activity log (if available).
     */
    protected function getActivityLog(User $user): array
    {
        // This would integrate with an activity logging system
        // For now, return basic account events
        return [
            'account_created' => $user->created_at?->toIso8601String(),
            'email_verified' => $user->email_verified_at?->toIso8601String(),
            'last_login' => $user->last_login_at ?? null,
            'last_updated' => $user->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Get user preferences.
     */
    protected function getPreferences(User $user): array
    {
        return [
            'notification_preferences' => $user->notification_preferences ?? [],
            'language' => $user->language,
            'max_commute_distance' => $user->max_commute_distance,
            'availability_schedule' => $user->availability_schedule ?? [],
        ];
    }

    /**
     * Get certifications for portable export.
     */
    protected function getCertifications(User $user): array
    {
        if (! $user->isWorker()) {
            return [];
        }

        return $user->certifications()->get()->map(function ($cert) {
            return [
                'name' => $cert->name,
                'type' => $cert->type ?? 'general',
                'valid_until' => $cert->pivot->expiry_date ?? null,
            ];
        })->toArray();
    }

    /**
     * Get skills for portable export.
     */
    protected function getSkills(User $user): array
    {
        if (! $user->isWorker()) {
            return [];
        }

        return $user->skills()->get()->map(function ($skill) {
            return [
                'name' => $skill->name,
                'proficiency' => $skill->pivot->proficiency_level ?? 'intermediate',
                'years_experience' => $skill->pivot->years_experience ?? null,
            ];
        })->toArray();
    }

    /**
     * Save export data to a file.
     */
    public function saveExportToFile(User $user, array $data): string
    {
        $filename = 'data-export-'.$user->id.'-'.now()->format('Y-m-d-His').'.json';
        $path = 'exports/'.$user->id.'/'.$filename;

        Storage::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $path;
    }

    /**
     * Save portable export to a file.
     */
    public function savePortableExportToFile(User $user, array $data): string
    {
        $filename = 'portable-export-'.$user->id.'-'.now()->format('Y-m-d-His').'.json';
        $path = 'exports/'.$user->id.'/'.$filename;

        Storage::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $path;
    }

    /**
     * Create a ZIP archive of all user data.
     */
    public function createExportArchive(User $user, array $data): string
    {
        $tempDir = storage_path('app/temp/'.Str::random(16));
        $zipPath = storage_path('app/exports/'.$user->id.'/data-export-'.now()->format('Y-m-d-His').'.zip');

        // Create directories
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        if (! is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        // Save JSON data
        file_put_contents($tempDir.'/data.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Create a human-readable summary
        $summary = $this->generateReadableSummary($data);
        file_put_contents($tempDir.'/summary.txt', $summary);

        // Create ZIP archive
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFile($tempDir.'/data.json', 'data.json');
            $zip->addFile($tempDir.'/summary.txt', 'summary.txt');
            $zip->close();
        }

        // Cleanup temp files
        @unlink($tempDir.'/data.json');
        @unlink($tempDir.'/summary.txt');
        @rmdir($tempDir);

        return str_replace(storage_path('app/'), '', $zipPath);
    }

    /**
     * Generate a human-readable summary.
     */
    protected function generateReadableSummary(array $data): string
    {
        $summary = "DATA EXPORT SUMMARY\n";
        $summary .= "==================\n\n";
        $summary .= 'Generated: '.now()->format('Y-m-d H:i:s')." UTC\n";
        $summary .= 'Platform: '.config('app.name')."\n\n";

        if (isset($data['personal_information']['basic_info'])) {
            $info = $data['personal_information']['basic_info'];
            $summary .= "ACCOUNT INFORMATION\n";
            $summary .= "-------------------\n";
            $summary .= 'Name: '.($info['name'] ?? 'N/A')."\n";
            $summary .= 'Email: '.($info['email'] ?? 'N/A')."\n";
            $summary .= 'Account Type: '.($info['user_type'] ?? 'N/A')."\n";
            $summary .= 'Account Created: '.($info['created_at'] ?? 'N/A')."\n\n";
        }

        if (isset($data['shift_history'])) {
            $summary .= "SHIFT HISTORY\n";
            $summary .= "-------------\n";
            $summary .= 'Total Shifts: '.count($data['shift_history'])."\n\n";
        }

        if (isset($data['payment_history'])) {
            $summary .= "PAYMENT HISTORY\n";
            $summary .= "---------------\n";
            $summary .= 'Total Transactions: '.count($data['payment_history'])."\n\n";
        }

        if (isset($data['messages'])) {
            $summary .= "MESSAGES\n";
            $summary .= "--------\n";
            $summary .= 'Total Messages: '.count($data['messages'])."\n\n";
        }

        if (isset($data['ratings'])) {
            $summary .= "RATINGS\n";
            $summary .= "-------\n";
            $summary .= 'Given: '.count($data['ratings']['given'] ?? [])."\n";
            $summary .= 'Received: '.count($data['ratings']['received'] ?? [])."\n\n";
        }

        $summary .= "\nFor the complete data, please see the data.json file included in this archive.\n";
        $summary .= "\nIf you have questions about this data, please contact: ".config('app.dpo_email', 'privacy@'.parse_url(config('app.url'), PHP_URL_HOST))."\n";

        return $summary;
    }

    /**
     * Delete an export file.
     */
    public function deleteExport(string $path): bool
    {
        if (Storage::exists($path)) {
            return Storage::delete($path);
        }

        return false;
    }

    /**
     * Get export file for download.
     */
    public function getExportFile(string $path): ?string
    {
        if (Storage::exists($path)) {
            return Storage::path($path);
        }

        return null;
    }
}
