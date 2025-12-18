<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\IncidentUpdate;
use App\Models\User;
use App\Notifications\IncidentAssignedNotification;
use App\Notifications\IncidentEscalatedNotification;
use App\Notifications\IncidentReportedNotification;
use App\Notifications\IncidentResolvedNotification;
use App\Notifications\IncidentStatusChangedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * SAF-002: Incident Reporting Service
 *
 * Handles all incident reporting business logic including:
 * - Incident creation and number generation
 * - Evidence and witness management
 * - Status updates and escalation
 * - Assignment and resolution
 * - Notifications to relevant parties
 */
class IncidentService
{
    /**
     * Report a new incident.
     */
    public function reportIncident(User $reporter, array $data): Incident
    {
        return DB::transaction(function () use ($reporter, $data) {
            // Generate unique incident number
            $incidentNumber = $this->generateIncidentNumber();

            // Create the incident
            $incident = Incident::create([
                'incident_number' => $incidentNumber,
                'shift_id' => $data['shift_id'] ?? null,
                'venue_id' => $data['venue_id'] ?? null,
                'reported_by' => $reporter->id,
                'involves_user_id' => $data['involves_user_id'] ?? null,
                'type' => $data['type'],
                'severity' => $data['severity'] ?? Incident::SEVERITY_MEDIUM,
                'description' => $data['description'],
                'location_description' => $data['location_description'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'incident_time' => $data['incident_time'] ?? now(),
                'evidence_urls' => $data['evidence_urls'] ?? [],
                'witness_info' => $data['witness_info'] ?? [],
                'status' => Incident::STATUS_REPORTED,
            ]);

            // Log the incident creation
            Log::info('Incident reported', [
                'incident_id' => $incident->id,
                'incident_number' => $incident->incident_number,
                'type' => $incident->type,
                'severity' => $incident->severity,
                'reported_by' => $reporter->id,
            ]);

            // Notify relevant parties
            $this->notifyRelevantParties($incident);

            // Auto-escalate critical incidents
            if ($incident->severity === Incident::SEVERITY_CRITICAL) {
                $this->autoEscalateIncident($incident);
            }

            return $incident;
        });
    }

    /**
     * Generate a unique incident number.
     * Format: INC-YYYY-NNNNN (e.g., INC-2024-00001)
     */
    public function generateIncidentNumber(): string
    {
        $year = now()->year;
        $prefix = "INC-{$year}-";

        // Get the last incident number for this year
        $lastIncident = Incident::where('incident_number', 'like', $prefix.'%')
            ->orderBy('incident_number', 'desc')
            ->first();

        if ($lastIncident) {
            // Extract the number part and increment
            $lastNumber = (int) Str::after($lastIncident->incident_number, $prefix);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix.str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Add evidence to an incident.
     */
    public function addEvidence(Incident $incident, array $files): void
    {
        DB::transaction(function () use ($incident, $files) {
            $evidence = $incident->evidence_urls ?? [];

            foreach ($files as $file) {
                // Handle file upload and get URL
                $url = $this->processEvidenceFile($file);
                if ($url) {
                    $evidence[] = [
                        'url' => $url,
                        'type' => $file['type'] ?? 'image',
                        'uploaded_at' => now()->toIso8601String(),
                        'description' => $file['description'] ?? null,
                    ];
                }
            }

            $incident->evidence_urls = $evidence;
            $incident->save();

            // Add update log
            $this->addUpdate($incident, auth()->user(), 'Evidence added to incident.', [], true);

            Log::info('Evidence added to incident', [
                'incident_id' => $incident->id,
                'files_count' => count($files),
            ]);
        });
    }

    /**
     * Process an evidence file for storage.
     */
    protected function processEvidenceFile(array $file): ?string
    {
        // If URL is already provided
        if (isset($file['url'])) {
            return $file['url'];
        }

        // If file path is provided (for uploaded files)
        if (isset($file['path'])) {
            return $file['path'];
        }

        return null;
    }

    /**
     * Add witness information to an incident.
     */
    public function addWitness(Incident $incident, array $witnessData): void
    {
        DB::transaction(function () use ($incident, $witnessData) {
            $witnesses = $incident->witness_info ?? [];

            $witnesses[] = [
                'name' => $witnessData['name'],
                'phone' => $witnessData['phone'] ?? null,
                'email' => $witnessData['email'] ?? null,
                'statement' => $witnessData['statement'] ?? null,
                'added_at' => now()->toIso8601String(),
            ];

            $incident->witness_info = $witnesses;
            $incident->save();

            // Add update log
            $this->addUpdate(
                $incident,
                auth()->user(),
                "Witness '{$witnessData['name']}' added to incident.",
                [],
                true
            );

            Log::info('Witness added to incident', [
                'incident_id' => $incident->id,
                'witness_name' => $witnessData['name'],
            ]);
        });
    }

    /**
     * Assign an investigator to an incident.
     */
    public function assignInvestigator(Incident $incident, User $admin): void
    {
        DB::transaction(function () use ($incident, $admin) {
            $previousAssignee = $incident->assigned_to;

            $incident->update([
                'assigned_to' => $admin->id,
                'status' => $incident->status === Incident::STATUS_REPORTED
                    ? Incident::STATUS_INVESTIGATING
                    : $incident->status,
            ]);

            // Add update log
            $message = $previousAssignee
                ? "Incident reassigned to {$admin->name}."
                : "Incident assigned to {$admin->name}.";
            $this->addUpdate($incident, auth()->user(), $message, [], true);

            // Notify the assignee
            $this->notifyAssignee($incident, $admin);

            Log::info('Investigator assigned to incident', [
                'incident_id' => $incident->id,
                'assigned_to' => $admin->id,
                'previous_assignee' => $previousAssignee,
            ]);
        });
    }

    /**
     * Update incident status.
     */
    public function updateStatus(Incident $incident, string $status, ?string $notes = null): void
    {
        DB::transaction(function () use ($incident, $status, $notes) {
            $oldStatus = $incident->status;

            $updateData = ['status' => $status];

            // Set resolved_at if moving to resolved status
            if ($status === Incident::STATUS_RESOLVED && ! $incident->resolved_at) {
                $updateData['resolved_at'] = now();
            }

            // Add resolution notes if provided
            if ($notes && in_array($status, [Incident::STATUS_RESOLVED, Incident::STATUS_CLOSED])) {
                $updateData['resolution_notes'] = $notes;
            }

            $incident->update($updateData);

            // Add update log
            $statusLabel = Incident::STATUS_LABELS[$status] ?? ucfirst($status);
            $message = "Status changed from '{$oldStatus}' to '{$statusLabel}'.";
            if ($notes) {
                $message .= " Notes: {$notes}";
            }
            $this->addUpdate($incident, auth()->user(), $message, [], false);

            // Notify reporter of status change
            $this->notifyStatusChange($incident, $oldStatus, $status);

            Log::info('Incident status updated', [
                'incident_id' => $incident->id,
                'old_status' => $oldStatus,
                'new_status' => $status,
            ]);
        });
    }

    /**
     * Escalate an incident.
     */
    public function escalateIncident(Incident $incident): void
    {
        DB::transaction(function () use ($incident) {
            $incident->update([
                'status' => Incident::STATUS_ESCALATED,
            ]);

            // Add update log
            $this->addUpdate(
                $incident,
                auth()->user(),
                'Incident escalated to senior management.',
                [],
                true
            );

            // Notify senior admins
            $this->notifyEscalation($incident);

            Log::info('Incident escalated', [
                'incident_id' => $incident->id,
                'escalated_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Auto-escalate critical incidents.
     */
    protected function autoEscalateIncident(Incident $incident): void
    {
        $incident->update([
            'status' => Incident::STATUS_ESCALATED,
        ]);

        // Add update log
        $this->addUpdate(
            $incident,
            null,
            'Incident automatically escalated due to critical severity.',
            [],
            true
        );

        // Notify senior admins
        $this->notifyEscalation($incident);

        Log::info('Incident auto-escalated', [
            'incident_id' => $incident->id,
            'severity' => $incident->severity,
        ]);
    }

    /**
     * Resolve an incident.
     */
    public function resolveIncident(Incident $incident, string $notes): void
    {
        DB::transaction(function () use ($incident, $notes) {
            $incident->update([
                'status' => Incident::STATUS_RESOLVED,
                'resolution_notes' => $notes,
                'resolved_at' => now(),
            ]);

            // Add update log
            $this->addUpdate(
                $incident,
                auth()->user(),
                "Incident resolved. Resolution: {$notes}",
                [],
                false
            );

            // Notify reporter
            $this->notifyResolution($incident);

            Log::info('Incident resolved', [
                'incident_id' => $incident->id,
                'resolved_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Close an incident (final state after resolution).
     */
    public function closeIncident(Incident $incident): void
    {
        DB::transaction(function () use ($incident) {
            $incident->update([
                'status' => Incident::STATUS_CLOSED,
            ]);

            // Add update log
            $this->addUpdate(
                $incident,
                auth()->user(),
                'Incident closed.',
                [],
                true
            );

            Log::info('Incident closed', [
                'incident_id' => $incident->id,
                'closed_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Add an update/comment to an incident.
     */
    public function addUpdate(Incident $incident, ?User $user, string $content, array $attachments = [], bool $isInternal = false): IncidentUpdate
    {
        return IncidentUpdate::create([
            'incident_id' => $incident->id,
            'user_id' => $user?->id ?? 1, // System user if no user provided
            'content' => $content,
            'attachments' => $attachments,
            'is_internal' => $isInternal,
        ]);
    }

    /**
     * Notify relevant parties about a new incident.
     */
    public function notifyRelevantParties(Incident $incident): void
    {
        try {
            // Notify admins
            $admins = User::where('role', 'admin')->get();

            foreach ($admins as $admin) {
                try {
                    $admin->notify(new IncidentReportedNotification($incident));
                } catch (\Exception $e) {
                    Log::warning('Failed to notify admin about incident', [
                        'admin_id' => $admin->id,
                        'incident_id' => $incident->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Notify business owner if incident is shift-related
            if ($incident->shift && $incident->shift->business_id) {
                $businessOwner = User::find($incident->shift->business_id);
                if ($businessOwner) {
                    try {
                        $businessOwner->notify(new IncidentReportedNotification($incident, true));
                    } catch (\Exception $e) {
                        Log::warning('Failed to notify business owner about incident', [
                            'business_id' => $businessOwner->id,
                            'incident_id' => $incident->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            Log::info('Incident notifications sent', [
                'incident_id' => $incident->id,
                'admins_notified' => $admins->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send incident notifications', [
                'incident_id' => $incident->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify assignee about incident assignment.
     */
    protected function notifyAssignee(Incident $incident, User $assignee): void
    {
        try {
            if (class_exists(IncidentAssignedNotification::class)) {
                $assignee->notify(new IncidentAssignedNotification($incident));
            }
        } catch (\Exception $e) {
            Log::warning('Failed to notify assignee about incident assignment', [
                'assignee_id' => $assignee->id,
                'incident_id' => $incident->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify reporter about status change.
     */
    protected function notifyStatusChange(Incident $incident, string $oldStatus, string $newStatus): void
    {
        try {
            $reporter = $incident->reporter;
            if ($reporter && class_exists(IncidentStatusChangedNotification::class)) {
                $reporter->notify(new IncidentStatusChangedNotification($incident, $oldStatus, $newStatus));
            }
        } catch (\Exception $e) {
            Log::warning('Failed to notify reporter about status change', [
                'incident_id' => $incident->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify senior admins about escalation.
     */
    protected function notifyEscalation(Incident $incident): void
    {
        try {
            // Get senior admins (admins with full_access permission)
            $seniorAdmins = User::where('role', 'admin')
                ->where('permissions', 'full_access')
                ->get();

            foreach ($seniorAdmins as $admin) {
                try {
                    if (class_exists(IncidentEscalatedNotification::class)) {
                        $admin->notify(new IncidentEscalatedNotification($incident));
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to notify senior admin about escalation', [
                        'admin_id' => $admin->id,
                        'incident_id' => $incident->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify senior admins about escalation', [
                'incident_id' => $incident->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify reporter about incident resolution.
     */
    protected function notifyResolution(Incident $incident): void
    {
        try {
            $reporter = $incident->reporter;
            if ($reporter && class_exists(IncidentResolvedNotification::class)) {
                $reporter->notify(new IncidentResolvedNotification($incident));
            }
        } catch (\Exception $e) {
            Log::warning('Failed to notify reporter about resolution', [
                'incident_id' => $incident->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Trigger an insurance claim for an incident.
     */
    public function triggerInsuranceClaim(Incident $incident): void
    {
        DB::transaction(function () use ($incident) {
            $incident->update([
                'requires_insurance_claim' => true,
            ]);

            // Add update log
            $this->addUpdate(
                $incident,
                auth()->user(),
                'Insurance claim flagged as required.',
                [],
                true
            );

            Log::info('Insurance claim triggered for incident', [
                'incident_id' => $incident->id,
            ]);
        });
    }

    /**
     * Record insurance claim number.
     */
    public function recordInsuranceClaimNumber(Incident $incident, string $claimNumber): void
    {
        DB::transaction(function () use ($incident, $claimNumber) {
            $incident->update([
                'insurance_claim_number' => $claimNumber,
            ]);

            // Add update log
            $this->addUpdate(
                $incident,
                auth()->user(),
                "Insurance claim number recorded: {$claimNumber}",
                [],
                true
            );

            Log::info('Insurance claim number recorded', [
                'incident_id' => $incident->id,
                'claim_number' => $claimNumber,
            ]);
        });
    }

    /**
     * Mark authorities as notified.
     */
    public function markAuthoritiesNotified(Incident $incident): void
    {
        DB::transaction(function () use ($incident) {
            $incident->update([
                'authorities_notified' => true,
            ]);

            // Add update log
            $this->addUpdate(
                $incident,
                auth()->user(),
                'Authorities have been notified about this incident.',
                [],
                true
            );

            Log::info('Authorities notified for incident', [
                'incident_id' => $incident->id,
            ]);
        });
    }

    /**
     * Get incident statistics for dashboard.
     */
    public function getStatistics(array $filters = []): array
    {
        $query = Incident::query();

        // Apply date filters
        if (isset($filters['start_date'])) {
            $query->where('incident_time', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('incident_time', '<=', $filters['end_date']);
        }

        // Apply venue filter
        if (isset($filters['venue_id'])) {
            $query->where('venue_id', $filters['venue_id']);
        }

        return [
            'total' => (clone $query)->count(),
            'open' => (clone $query)->open()->count(),
            'resolved' => (clone $query)->where('status', Incident::STATUS_RESOLVED)->count(),
            'escalated' => (clone $query)->where('status', Incident::STATUS_ESCALATED)->count(),
            'by_type' => (clone $query)->selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'by_severity' => (clone $query)->selectRaw('severity, count(*) as count')
                ->groupBy('severity')
                ->pluck('count', 'severity')
                ->toArray(),
            'by_status' => (clone $query)->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'avg_resolution_hours' => (clone $query)
                ->whereNotNull('resolved_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
                ->value('avg_hours'),
            'critical_open' => (clone $query)->open()->critical()->count(),
            'unassigned' => (clone $query)->open()->unassigned()->count(),
        ];
    }

    /**
     * Get incidents for a specific user (reporter or involved).
     */
    public function getIncidentsForUser(User $user, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Incident::query()
            ->where(function ($q) use ($user) {
                $q->where('reported_by', $user->id)
                    ->orWhere('involves_user_id', $user->id);
            })
            ->with(['shift', 'venue', 'reporter', 'assignee']);

        // Apply status filter
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply type filter
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Search incidents.
     */
    public function searchIncidents(string $search, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Incident::query()
            ->with(['shift', 'venue', 'reporter', 'assignee', 'involvedUser']);

        // Search by incident number or description
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('incident_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location_description', 'like', "%{$search}%");
            });
        }

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }
        if (isset($filters['venue_id'])) {
            $query->where('venue_id', $filters['venue_id']);
        }
        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }
        if (isset($filters['start_date'])) {
            $query->where('incident_time', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('incident_time', '<=', $filters['end_date']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }
}
