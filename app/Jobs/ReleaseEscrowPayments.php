<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ShiftPaymentService;
use App\Models\ShiftAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReleaseEscrowPayments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     * Releases payments from escrow 15 minutes after shift completion
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Release escrow payments job started');

        try {
            $paymentService = new ShiftPaymentService();

            // Find completed shifts where payment is still in escrow
            // and check-out was more than 15 minutes ago
            $assignments = ShiftAssignment::with(['shift', 'shiftPayment'])
                ->where('status', 'completed')
                ->where('checked_out_at', '<=', Carbon::now()->subMinutes(15))
                ->whereHas('shiftPayment', function($query) {
                    $query->where('status', 'in_escrow');
                })
                ->get();

            $released = 0;
            $failed = 0;

            foreach ($assignments as $assignment) {
                try {
                    $result = $paymentService->releaseFromEscrow($assignment);

                    if ($result) {
                        $released++;
                        Log::info("Released payment for assignment {$assignment->id}");
                    } else {
                        $failed++;
                        Log::warning("Failed to release payment for assignment {$assignment->id}");
                    }

                } catch (\Exception $e) {
                    $failed++;
                    Log::error("Error releasing payment for assignment {$assignment->id}: " . $e->getMessage());
                }
            }

            Log::info('Escrow release completed', [
                'released' => $released,
                'failed' => $failed,
                'total_checked' => $assignments->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error in ReleaseEscrowPayments job: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('ReleaseEscrowPayments job failed after all retries', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
