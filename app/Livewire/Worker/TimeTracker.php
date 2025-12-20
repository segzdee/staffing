<?php

namespace App\Livewire\Worker;

use App\Models\ShiftAssignment;
use App\Services\TimeTrackingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Time Tracker Livewire Component
 *
 * Provides a web-based interface for workers to clock in/out and manage breaks.
 */
class TimeTracker extends Component
{
    public ?int $assignmentId = null;

    public ?ShiftAssignment $assignment = null;

    public bool $isClockedIn = false;

    public bool $onBreak = false;

    public ?string $clockInTime = null;

    public ?float $latitude = null;

    public ?float $longitude = null;

    public ?float $accuracy = null;

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    public bool $isLoading = false;

    protected TimeTrackingService $timeTrackingService;

    public function boot(TimeTrackingService $timeTrackingService): void
    {
        $this->timeTrackingService = $timeTrackingService;
    }

    public function mount(?int $assignmentId = null): void
    {
        if ($assignmentId) {
            $this->assignmentId = $assignmentId;
            $this->loadAssignment();
        } else {
            $this->loadActiveAssignment();
        }
    }

    public function loadAssignment(): void
    {
        if (! $this->assignmentId) {
            return;
        }

        $user = Auth::user();
        $this->assignment = ShiftAssignment::with(['shift.venue', 'shift.business'])
            ->where('id', $this->assignmentId)
            ->where('worker_id', $user->id)
            ->first();

        if ($this->assignment) {
            $this->syncState();
        }
    }

    public function loadActiveAssignment(): void
    {
        $user = Auth::user();

        $this->assignment = ShiftAssignment::with(['shift.venue', 'shift.business'])
            ->where('worker_id', $user->id)
            ->whereIn('status', ['assigned', 'checked_in'])
            ->whereHas('shift', function ($query) {
                $query->whereDate('shift_date', now()->toDateString())
                    ->orWhere(function ($q) {
                        $q->whereDate('shift_date', now()->subDay()->toDateString())
                            ->where('end_time', '<', 'start_time'); // Overnight shift
                    });
            })
            ->orderBy('created_at', 'desc')
            ->first();

        if ($this->assignment) {
            $this->assignmentId = $this->assignment->id;
            $this->syncState();
        }
    }

    protected function syncState(): void
    {
        if (! $this->assignment) {
            return;
        }

        $this->isClockedIn = ! empty($this->assignment->actual_clock_in) || ! empty($this->assignment->check_in_time);
        $this->clockInTime = $this->assignment->actual_clock_in ?? $this->assignment->check_in_time;

        $breakStatus = $this->timeTrackingService->getBreakStatus($this->assignment);
        $this->onBreak = $breakStatus['on_break'];
    }

    public function updateLocation(float $latitude, float $longitude, ?float $accuracy = null): void
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->accuracy = $accuracy;
    }

    public function clockIn(): void
    {
        $this->resetMessages();
        $this->isLoading = true;

        if (! $this->assignment) {
            $this->errorMessage = 'No active shift assignment found.';
            $this->isLoading = false;

            return;
        }

        if (! $this->latitude || ! $this->longitude) {
            $this->errorMessage = 'Location is required for clock-in. Please enable location services.';
            $this->isLoading = false;

            return;
        }

        $verificationData = [
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'accuracy' => $this->accuracy,
            ],
        ];

        $result = $this->timeTrackingService->processClockIn($this->assignment, $verificationData);

        if ($result['success']) {
            $this->isClockedIn = true;
            $this->clockInTime = now()->toDateTimeString();
            $this->successMessage = $result['message'];
            $this->assignment->refresh();
            $this->dispatch('clocked-in');
        } else {
            $this->errorMessage = $result['error'] ?? 'Clock-in failed.';
        }

        $this->isLoading = false;
    }

    public function clockOut(): void
    {
        $this->resetMessages();
        $this->isLoading = true;

        if (! $this->assignment) {
            $this->errorMessage = 'No active shift assignment found.';
            $this->isLoading = false;

            return;
        }

        $verificationData = [];

        if ($this->latitude && $this->longitude) {
            $verificationData['location'] = [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'accuracy' => $this->accuracy,
            ];
        }

        $result = $this->timeTrackingService->processClockOut($this->assignment, $verificationData);

        if ($result['success']) {
            $this->isClockedIn = false;
            $this->onBreak = false;
            $this->successMessage = $result['message'];
            $this->assignment->refresh();
            $this->dispatch('clocked-out', hoursWorked: $result['actual_hours'] ?? 0);
        } else {
            $this->errorMessage = $result['error'] ?? 'Clock-out failed.';
        }

        $this->isLoading = false;
    }

    public function startBreak(): void
    {
        $this->resetMessages();
        $this->isLoading = true;

        if (! $this->assignment) {
            $this->errorMessage = 'No active shift assignment found.';
            $this->isLoading = false;

            return;
        }

        $result = $this->timeTrackingService->processBreakStart($this->assignment);

        if ($result['success']) {
            $this->onBreak = true;
            $this->successMessage = $result['message'];
            $this->dispatch('break-started');
        } else {
            $this->errorMessage = $result['error'] ?? 'Failed to start break.';
        }

        $this->isLoading = false;
    }

    public function endBreak(): void
    {
        $this->resetMessages();
        $this->isLoading = true;

        if (! $this->assignment) {
            $this->errorMessage = 'No active shift assignment found.';
            $this->isLoading = false;

            return;
        }

        $result = $this->timeTrackingService->processBreakEnd($this->assignment);

        if ($result['success']) {
            $this->onBreak = false;
            $this->successMessage = $result['message'];
            $this->dispatch('break-ended', duration: $result['break_duration_minutes'] ?? 0);
        } else {
            $this->errorMessage = $result['error'] ?? 'Failed to end break.';
        }

        $this->isLoading = false;
    }

    protected function resetMessages(): void
    {
        $this->errorMessage = null;
        $this->successMessage = null;
    }

    #[Computed]
    public function elapsedTime(): string
    {
        if (! $this->isClockedIn || ! $this->clockInTime) {
            return '00:00:00';
        }

        $clockIn = \Carbon\Carbon::parse($this->clockInTime);
        $elapsed = $clockIn->diffInSeconds(now());

        $hours = floor($elapsed / 3600);
        $minutes = floor(($elapsed % 3600) / 60);
        $seconds = $elapsed % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    #[Computed]
    public function breakStatus(): array
    {
        if (! $this->assignment) {
            return [
                'on_break' => false,
                'total_break_minutes' => 0,
                'mandatory_break_taken' => false,
                'mandatory_break_required' => false,
            ];
        }

        return $this->timeTrackingService->getBreakStatus($this->assignment);
    }

    #[Computed]
    public function shiftInfo(): ?array
    {
        if (! $this->assignment || ! $this->assignment->shift) {
            return null;
        }

        $shift = $this->assignment->shift;

        return [
            'date' => $shift->shift_date,
            'start_time' => $shift->start_time,
            'end_time' => $shift->end_time,
            'duration_hours' => $shift->duration_hours,
            'venue_name' => $shift->venue?->name,
            'venue_address' => $shift->venue?->address,
            'business_name' => $shift->business?->businessProfile?->business_name,
        ];
    }

    #[On('location-updated')]
    public function handleLocationUpdate(float $lat, float $lng, ?float $acc = null): void
    {
        $this->updateLocation($lat, $lng, $acc);
    }

    public function refresh(): void
    {
        if ($this->assignment) {
            $this->assignment->refresh();
            $this->syncState();
        }
    }

    public function render()
    {
        return view('livewire.worker.time-tracker');
    }
}
