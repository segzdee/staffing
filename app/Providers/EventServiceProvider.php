<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // ============================================================================
        // OVERTIMESTAFF EMAIL NOTIFICATIONS
        // ============================================================================
        \App\Events\ShiftCreated::class => [
            \App\Listeners\NotifyMatchedWorkers::class,
        ],
        \App\Events\ApplicationReceived::class => [
            \App\Listeners\NotifyBusinessOfApplication::class,
        ],
        \App\Events\ApplicationAccepted::class => [
            \App\Listeners\NotifyWorkerOfAcceptance::class,
        ],
        \App\Events\ApplicationRejected::class => [
            \App\Listeners\NotifyWorkerOfRejection::class,
        ],
        \App\Events\ShiftAssigned::class => [
            \App\Listeners\NotifyShiftAssigned::class,
        ],
        \App\Events\ShiftCompleted::class => [
            \App\Listeners\NotifyShiftCompleted::class,
        ],
        \App\Events\PaymentReleased::class => [
            \App\Listeners\NotifyPaymentReleased::class,
        ],
        \App\Events\ShiftCancelled::class => [
            \App\Listeners\NotifyShiftCancelled::class,
        ],
        \App\Events\ShiftUpdated::class => [
            // Future: NotifyWorkersOfShiftUpdate listener
        ],
        \App\Events\InstantPayoutCompleted::class => [
            // Future: NotifyWorkerOfInstantPayout listener
        ],
        \App\Events\PaymentDisputed::class => [
            // Future: NotifyAdminOfDispute listener
        ],

    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
