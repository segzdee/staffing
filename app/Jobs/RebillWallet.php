<?php

namespace App\Jobs;

use App\Http\Controllers\Traits\Functions;
use App\Models\Notifications;
use App\Models\Plans;
use App\Models\Subscriptions;
use App\Models\TaxRates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RebillWallet implements ShouldQueue
{
    use Dispatchable, Functions, InteractsWithQueue, Queueable, SerializesModels;

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
     *
     * @return void
     */
    public function handle()
    {
        $subscriptions = Subscriptions::where('ends_at', '<', now())
            ->whereRebillWallet('on')
            ->whereCancelled('no')
            ->get();

        if ($subscriptions) {

            foreach ($subscriptions as $subscription) {

                // Get price of Plan
                $plan = Plans::whereName($subscription->stripe_price)->first();

                // Get Taxes
                $taxes = TaxRates::whereIn('id', collect(explode('_', $subscription->taxes)))->get();
                $totalTaxes = ($plan->price * $taxes->sum('percentage') / 100);
                $planPrice = ($plan->price + $totalTaxes);

                if ($subscription->user()->wallet >= $planPrice && $subscription->subscribed()->free_subscription == 'no') {

                    // Admin and user earnings calculation
                    $earnings = $this->earningsAdminUser($subscription->subscribed()->custom_fee, $plan->price, null, null);

                    // Insert Transaction
                    $this->transaction(
                        'subw_'.Str::random(25),
                        $subscription->user()->id,
                        $subscription->id,
                        $subscription->subscribed()->id,
                        $plan->price,
                        $earnings['user'],
                        $earnings['admin'],
                        'Wallet',
                        'subscription',
                        $earnings['percentageApplied'],
                        $subscription->taxes
                    );

                    // Subtract user funds
                    $subscription->user()->decrement('wallet', $planPrice);

                    // Add Earnings to Creator
                    $subscription->subscribed()->increment('balance', $earnings['user']);

                    // Send Notification to User --- destination, author, type, target
                    Notifications::send($subscription->subscribed()->id, $subscription->user()->id, 12, $subscription->user()->id);

                    $subscription->update([
                        'ends_at' => $subscription->subscribed()->planInterval($plan->interval),
                    ]);
                }
            }
        }

    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('RebillWallet job failed', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Optionally notify admins of this critical failure
        // This job handles financial transactions, so failures should be investigated
    }
}
