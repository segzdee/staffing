<?php

namespace App\Console\Commands;

use App\Models\ShiftAssignment;
use App\Models\ShiftPayment;
use App\Services\ShiftPaymentService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoReleaseEscrow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shifts:auto-release-escrow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically release escrow payments for completed shifts (15+ minutes after completion)';

    protected $paymentService;

    /**
     * Create a new command instance.
     */
    public function __construct(ShiftPaymentService $paymentService)
    {
        parent::__construct();
        $this->paymentService = $paymentService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting automatic escrow release...');

        try {
            // Find completed shifts where escrow has been held for 15+ minutes
            $fifteenMinutesAgo = Carbon::now()->subMinutes(15);

            $payments = ShiftPayment::with(['assignment.shift', 'worker', 'business'])
                ->where('status', 'in_escrow')
                ->where('escrow_held_at', '<=', $fifteenMinutesAgo)
                ->whereHas('assignment', function($query) {
                    $query->where('status', 'completed')
                          ->whereNotNull('check_out_time');
                })
                ->get();

            if ($payments->isEmpty()) {
                $this->info('No payments ready for release.');
                return 0;
            }

            $this->info("Found {$payments->count()} payment(s) ready for release.");

            $released = 0;
            $failed = 0;

            foreach ($payments as $payment) {
                try {
                    $result = $this->paymentService->releaseFromEscrow($payment->assignment);

                    if ($result) {
                        $released++;
                        $this->info("✓ Released payment for assignment {$payment->assignment->id}");
                    } else {
                        $failed++;
                        $this->warn("✗ Failed to release payment for assignment {$payment->assignment->id}");
                    }
                } catch (\Exception $e) {
                    $failed++;
                    Log::error("Error releasing escrow for payment {$payment->id}: " . $e->getMessage());
                    $this->error("Error: {$e->getMessage()}");
                }
            }

            $this->info("\nSummary: {$released} released, {$failed} failed");

            return 0;
        } catch (\Exception $e) {
            Log::error("AutoReleaseEscrow command error: " . $e->getMessage());
            $this->error("Command failed: {$e->getMessage()}");
            return 1;
        }
    }
}
