<?php

namespace App\Providers;

use App\Auth\SessionGuard;
use App\Models\AdminDisputeQueue;
use App\Models\AdminSettings;
use App\Models\AgencyProfile;
use App\Models\AgencyWorker;
use App\Models\AvailabilityBroadcast;
use App\Models\BankAccount;
use App\Models\Blogs;
use App\Models\BusinessProfile;
use App\Models\BusinessRoster;
use App\Models\Certification;
use App\Models\Conversation;
use App\Models\Countries;
use App\Models\Message;
use App\Models\Pages;
use App\Models\Rating;
use App\Models\Shift;
use App\Models\ShiftApplication;
use App\Models\ShiftAssignment;
use App\Models\ShiftAttachment;
use App\Models\ShiftInvitation;
use App\Models\ShiftNotification;
use App\Models\ShiftPayment;
use App\Models\ShiftSwap;
use App\Models\ShiftTemplate;
use App\Models\Skill;
use App\Models\States;
use App\Models\TaxForm;
use App\Models\TaxRates;
use App\Models\User;
use App\Models\VerificationQueue;
use App\Models\WorkerAvailabilitySchedule;
use App\Models\WorkerBadge;
use App\Models\WorkerBlackoutDate;
use App\Models\WorkerCertification;
use App\Models\WorkerProfile;
use App\Models\WorkerSkill;
use App\Policies\AdminDisputeQueuePolicy;
use App\Policies\AdminSettingsPolicy;
use App\Policies\AgencyProfilePolicy;
use App\Policies\AgencyWorkerPolicy;
use App\Policies\AvailabilityBroadcastPolicy;
use App\Policies\BankAccountPolicy;
use App\Policies\BlogsPolicy;
use App\Policies\BusinessProfilePolicy;
use App\Policies\BusinessRosterPolicy;
use App\Policies\CertificationPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\CountriesPolicy;
use App\Policies\MessagePolicy;
use App\Policies\PagesPolicy;
use App\Policies\RatingPolicy;
use App\Policies\ShiftApplicationPolicy;
use App\Policies\ShiftAssignmentPolicy;
use App\Policies\ShiftAttachmentPolicy;
use App\Policies\ShiftInvitationPolicy;
use App\Policies\ShiftNotificationPolicy;
use App\Policies\ShiftPaymentPolicy;
use App\Policies\ShiftPolicy;
use App\Policies\ShiftSwapPolicy;
use App\Policies\ShiftTemplatePolicy;
use App\Policies\SkillPolicy;
use App\Policies\StatesPolicy;
use App\Policies\TaxFormPolicy;
use App\Policies\TaxRatesPolicy;
use App\Policies\UserPolicy;
use App\Policies\VerificationQueuePolicy;
use App\Policies\WorkerAvailabilitySchedulePolicy;
use App\Policies\WorkerBadgePolicy;
use App\Policies\WorkerBlackoutDatePolicy;
use App\Policies\WorkerCertificationPolicy;
use App\Policies\WorkerProfilePolicy;
use App\Policies\WorkerSkillPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        User::class => UserPolicy::class,
        WorkerProfile::class => WorkerProfilePolicy::class,
        BusinessProfile::class => BusinessProfilePolicy::class,
        AgencyProfile::class => AgencyProfilePolicy::class,
        Shift::class => ShiftPolicy::class,
        ShiftTemplate::class => ShiftTemplatePolicy::class,
        ShiftApplication::class => ShiftApplicationPolicy::class,
        ShiftAssignment::class => ShiftAssignmentPolicy::class,
        ShiftPayment::class => ShiftPaymentPolicy::class,
        ShiftSwap::class => ShiftSwapPolicy::class,
        ShiftInvitation::class => ShiftInvitationPolicy::class,
        ShiftNotification::class => ShiftNotificationPolicy::class,
        ShiftAttachment::class => ShiftAttachmentPolicy::class,
        WorkerSkill::class => WorkerSkillPolicy::class,
        WorkerCertification::class => WorkerCertificationPolicy::class,
        WorkerBadge::class => WorkerBadgePolicy::class,
        WorkerAvailabilitySchedule::class => WorkerAvailabilitySchedulePolicy::class,
        WorkerBlackoutDate::class => WorkerBlackoutDatePolicy::class,
        AvailabilityBroadcast::class => AvailabilityBroadcastPolicy::class,
        Skill::class => SkillPolicy::class,
        Certification::class => CertificationPolicy::class,
        Rating::class => RatingPolicy::class,
        Message::class => MessagePolicy::class,
        Conversation::class => ConversationPolicy::class,
        VerificationQueue::class => VerificationQueuePolicy::class,
        AdminDisputeQueue::class => AdminDisputeQueuePolicy::class,
        AdminSettings::class => AdminSettingsPolicy::class,
        AgencyWorker::class => AgencyWorkerPolicy::class,
        Countries::class => CountriesPolicy::class,
        States::class => StatesPolicy::class,
        TaxForm::class => TaxFormPolicy::class, // GLO-002: Tax Jurisdiction Engine
        TaxRates::class => TaxRatesPolicy::class,
        Pages::class => PagesPolicy::class,
        Blogs::class => BlogsPolicy::class,
        BankAccount::class => BankAccountPolicy::class,
        BusinessRoster::class => BusinessRosterPolicy::class, // BIZ-005: Roster Management
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        $this->registerSessionRotatingGuard();

        // Define additional gates for role-based access
        Gate::define('view-admin-panel', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('manage-shifts', function (User $user) {
            return $user->isBusiness();
        });

        Gate::define('apply-to-shifts', function (User $user) {
            return $user->isWorker();
        });

        Gate::define('manage-agency-workers', function (User $user) {
            return $user->isAgency();
        });
    }

    /**
     * Register the custom session guard with remember token rotation.
     *
     * This guard enhances security by rotating the remember_token on every
     * authentication event, preventing session fixation attacks and limiting
     * the damage if a remember token is compromised.
     */
    protected function registerSessionRotatingGuard(): void
    {
        Auth::extend('session-rotating', function ($app, $name, array $config) {
            $provider = Auth::createUserProvider($config['provider'] ?? null);

            $guard = new SessionGuard(
                $name,
                $provider,
                $app['session.store'],
                $app['request']
            );

            // Set the cookie jar for remember me functionality
            if (method_exists($guard, 'setCookieJar')) {
                $guard->setCookieJar($app['cookie']);
            }

            // Set the event dispatcher
            if (method_exists($guard, 'setDispatcher')) {
                $guard->setDispatcher($app['events']);
            }

            // Set the request instance
            if (method_exists($guard, 'setRequest')) {
                $guard->setRequest($app->refresh('request', $guard, 'setRequest'));
            }

            // Set password broker for password confirmation timeout
            if (isset($config['password_timeout'])) {
                $guard->setPasswordTimeout($config['password_timeout']);
            }

            return $guard;
        });
    }
}
