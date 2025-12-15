<?php

namespace App\Jobs;

use App\Models\BusinessProfile;
use App\Models\CreditInvoice;
use App\Models\CreditInvoiceItem;
use App\Models\ShiftPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateWeeklyCreditInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $weekStart;
    protected $weekEnd;

    /**
     * Create a new job instance.
     *
     * @param Carbon|null $weekStart Start of the week (defaults to last Monday)
     * @param Carbon|null $weekEnd End of the week (defaults to last Sunday)
     */
    public function __construct($weekStart = null, $weekEnd = null)
    {
        $this->weekStart = $weekStart ?? Carbon::now()->subWeek()->startOfWeek();
        $this->weekEnd = $weekEnd ?? Carbon::now()->subWeek()->endOfWeek();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Starting weekly credit invoice generation', [
            'period_start' => $this->weekStart->toDateString(),
            'period_end' => $this->weekEnd->toDateString(),
        ]);

        // Get all businesses with credit enabled
        $businesses = User::whereHas('businessProfile', function ($query) {
            $query->where('credit_enabled', true);
        })->with('businessProfile')->get();

        $invoicesGenerated = 0;

        foreach ($businesses as $business) {
            try {
                $invoice = $this->generateInvoiceForBusiness($business);
                if ($invoice) {
                    $invoicesGenerated++;
                    Log::info("Generated invoice for business {$business->id}", [
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => $invoice->total_amount,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Failed to generate invoice for business {$business->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("Weekly credit invoice generation complete", [
            'invoices_generated' => $invoicesGenerated,
        ]);
    }

    /**
     * Generate invoice for a specific business.
     */
    protected function generateInvoiceForBusiness(User $business)
    {
        // Get all shift payments charged to credit during this period
        $shiftPayments = ShiftPayment::where('business_id', $business->id)
            ->whereHas('assignment', function ($query) {
                $query->whereBetween('completed_at', [
                    $this->weekStart,
                    $this->weekEnd,
                ]);
            })
            ->where('status', 'paid_out')
            ->whereDoesntHave('invoice') // Not already invoiced
            ->with(['shift', 'worker'])
            ->get();

        // Skip if no charges this week
        if ($shiftPayments->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($business, $shiftPayments) {
            $profile = $business->businessProfile;

            // Calculate payment terms
            $paymentTermsDays = match ($profile->payment_terms) {
                'net_7' => 7,
                'net_14' => 14,
                'net_30' => 30,
                default => 14,
            };

            // Calculate subtotal
            $subtotal = $shiftPayments->sum(function ($payment) {
                return $payment->amount_gross->getAmount() / 100; // Convert cents to dollars
            });

            // Create invoice
            $invoice = CreditInvoice::create([
                'business_id' => $business->id,
                'invoice_date' => now(),
                'due_date' => now()->addDays($paymentTermsDays),
                'period_start' => $this->weekStart,
                'period_end' => $this->weekEnd,
                'subtotal' => $subtotal,
                'late_fees' => 0,
                'adjustments' => 0,
                'total_amount' => $subtotal,
                'amount_paid' => 0,
                'amount_due' => $subtotal,
                'status' => 'issued',
            ]);

            // Create invoice items
            foreach ($shiftPayments as $payment) {
                CreditInvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'shift_id' => $payment->shift_id,
                    'shift_payment_id' => $payment->id,
                    'description' => $this->generateItemDescription($payment),
                    'service_date' => $payment->shift->start_time ?? $payment->created_at,
                    'quantity' => 1,
                    'unit_price' => $payment->amount_gross->getAmount() / 100,
                    'amount' => $payment->amount_gross->getAmount() / 100,
                    'metadata' => [
                        'worker_name' => $payment->worker->name,
                        'shift_title' => $payment->shift->title ?? 'Shift',
                    ],
                ]);
            }

            // Update business credit balance
            $profile->credit_used += $subtotal;
            $profile->credit_available = $profile->credit_limit - $profile->credit_used;
            $profile->credit_utilization = $profile->credit_limit > 0
                ? ($profile->credit_used / $profile->credit_limit) * 100
                : 0;
            $profile->save();

            // Send invoice to business (implement notification service)
            // NotificationService::sendCreditInvoice($invoice);

            return $invoice;
        });
    }

    /**
     * Generate description for invoice item.
     */
    protected function generateItemDescription(ShiftPayment $payment)
    {
        $shift = $payment->shift;
        $worker = $payment->worker;

        if ($shift && $shift->start_time) {
            return sprintf(
                '%s - %s (%s)',
                $shift->title ?? 'Shift',
                $worker->name,
                $shift->start_time->format('M j, Y')
            );
        }

        return sprintf(
            'Shift - %s',
            $worker->name
        );
    }
}
