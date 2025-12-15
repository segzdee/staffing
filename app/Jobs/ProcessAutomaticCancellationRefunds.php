<?php

namespace App\Jobs;

use App\Models\Shift;
use App\Services\RefundService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAutomaticCancellationRefunds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * Automatically creates refunds for shifts cancelled more than 72 hours in advance.
     *
     * @return void
     */
    public function handle(RefundService $refundService)
    {
        Log::info('Starting automatic cancellation refunds processing');

        // Find shifts that were:
        // 1. Cancelled more than 72 hours before start time
        // 2. Have a payment record
        // 3. Don't already have a refund
        $cancelledShifts = Shift::where('status', 'cancelled')
            ->whereHas('payment', function ($query) {
                $query->where('status', '!=', 'refunded')
                    ->whereDoesntHave('refund');
            })
            ->where(function ($query) {
                // Cancelled at least 72 hours before start time
                $query->whereRaw('cancelled_at <= DATE_SUB(start_time, INTERVAL 72 HOUR)');
            })
            ->where('cancelled_at', '>', Carbon::now()->subDays(30)) // Last 30 days only
            ->with(['payment'])
            ->get();

        $created = 0;

        foreach ($cancelledShifts as $shift) {
            try {
                $refund = $refundService->createAutoCancellationRefund($shift);

                if ($refund) {
                    $created++;
                    Log::info("Created auto-refund for cancelled shift {$shift->id}", [
                        'refund_number' => $refund->refund_number,
                    ]);
                }

            } catch (\Exception $e) {
                Log::error("Failed to create auto-refund for shift {$shift->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Automatic cancellation refunds processing complete', [
            'shifts_checked' => $cancelledShifts->count(),
            'refunds_created' => $created,
        ]);

        // Now dispatch job to process the newly created refunds
        if ($created > 0) {
            ProcessPendingRefunds::dispatch()->delay(now()->addMinutes(5));
        }
    }
}
