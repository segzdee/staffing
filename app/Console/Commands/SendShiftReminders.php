<?php

namespace App\Console\Commands;

use App\Models\ShiftAssignment;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendShiftReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shifts:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send shift reminder notifications to workers';

    protected $notificationService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Checking for shifts requiring reminders...');

        $now = Carbon::now();
        $twoHoursFromNow = $now->copy()->addHours(2);
        $thirtyMinutesFromNow = $now->copy()->addMinutes(30);

        // Get assignments needing 2-hour reminder
        $twoHourReminders = ShiftAssignment::where('status', 'assigned')
            ->whereHas('shift', function($q) use ($twoHoursFromNow) {
                $q->where(function($query) use ($twoHoursFromNow) {
                    $query->whereRaw("CONCAT(shift_date, ' ', start_time) BETWEEN ? AND ?", [
                        $twoHoursFromNow->format('Y-m-d H:i:00'),
                        $twoHoursFromNow->addMinute()->format('Y-m-d H:i:00')
                    ]);
                });
            })
            ->whereDoesntHave('notifications', function($q) {
                $q->where('type', 'shift_reminder_2h');
            })
            ->get();

        foreach ($twoHourReminders as $assignment) {
            $this->notificationService->sendShiftReminder2Hours($assignment);
            $this->info("2-hour reminder sent for assignment {$assignment->id}");
        }

        // Get assignments needing 30-minute reminder
        $thirtyMinuteReminders = ShiftAssignment::where('status', 'assigned')
            ->whereHas('shift', function($q) use ($thirtyMinutesFromNow) {
                $q->where(function($query) use ($thirtyMinutesFromNow) {
                    $query->whereRaw("CONCAT(shift_date, ' ', start_time) BETWEEN ? AND ?", [
                        $thirtyMinutesFromNow->format('Y-m-d H:i:00'),
                        $thirtyMinutesFromNow->addMinute()->format('Y-m-d H:i:00')
                    ]);
                });
            })
            ->whereDoesntHave('notifications', function($q) {
                $q->where('type', 'shift_reminder_30m');
            })
            ->get();

        foreach ($thirtyMinuteReminders as $assignment) {
            $this->notificationService->sendShiftReminder30Minutes($assignment);
            $this->info("30-minute reminder sent for assignment {$assignment->id}");
        }

        $total = $twoHourReminders->count() + $thirtyMinuteReminders->count();
        $this->info("Total reminders sent: {$total}");

        return 0;
    }
}
