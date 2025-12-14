<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\BusinessProfile;
use App\Models\AgencyProfile;
use App\Models\AiAgentProfile;
use App\Models\Shift;
use App\Models\ShiftTemplate;
use App\Models\ShiftApplication;
use App\Models\ShiftAssignment;
use App\Models\ShiftPayment;
use App\Models\ShiftSwap;
use App\Models\ShiftInvitation;
use App\Models\ShiftNotification;
use App\Models\ShiftAttachment;
use App\Models\WorkerSkill;
use App\Models\WorkerCertification;
use App\Models\WorkerBadge;
use App\Models\WorkerAvailabilitySchedule;
use App\Models\WorkerBlackoutDate;
use App\Models\AvailabilityBroadcast;
use App\Models\Skill;
use App\Models\Certification;
use App\Models\Rating;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\VerificationQueue;
use App\Models\AdminDisputeQueue;
use App\Models\AdminSettings;
use App\Models\AgencyWorker;
use App\Models\Countries;
use App\Models\States;
use App\Models\TaxRates;
use App\Models\Pages;
use App\Models\Blogs;
use App\Policies\UserPolicy;
use App\Policies\WorkerProfilePolicy;
use App\Policies\BusinessProfilePolicy;
use App\Policies\AgencyProfilePolicy;
use App\Policies\AiAgentProfilePolicy;
use App\Policies\ShiftPolicy;
use App\Policies\ShiftTemplatePolicy;
use App\Policies\ShiftApplicationPolicy;
use App\Policies\ShiftAssignmentPolicy;
use App\Policies\ShiftPaymentPolicy;
use App\Policies\ShiftSwapPolicy;
use App\Policies\ShiftInvitationPolicy;
use App\Policies\ShiftNotificationPolicy;
use App\Policies\ShiftAttachmentPolicy;
use App\Policies\WorkerSkillPolicy;
use App\Policies\WorkerCertificationPolicy;
use App\Policies\WorkerBadgePolicy;
use App\Policies\WorkerAvailabilitySchedulePolicy;
use App\Policies\WorkerBlackoutDatePolicy;
use App\Policies\AvailabilityBroadcastPolicy;
use App\Policies\SkillPolicy;
use App\Policies\CertificationPolicy;
use App\Policies\RatingPolicy;
use App\Policies\MessagePolicy;
use App\Policies\ConversationPolicy;
use App\Policies\VerificationQueuePolicy;
use App\Policies\AdminDisputeQueuePolicy;
use App\Policies\AdminSettingsPolicy;
use App\Policies\AgencyWorkerPolicy;
use App\Policies\CountriesPolicy;
use App\Policies\StatesPolicy;
use App\Policies\TaxRatesPolicy;
use App\Policies\PagesPolicy;
use App\Policies\BlogsPolicy;

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
        AiAgentProfile::class => AiAgentProfilePolicy::class,
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
        TaxRates::class => TaxRatesPolicy::class,
        Pages::class => PagesPolicy::class,
        Blogs::class => BlogsPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Define additional gates for role-based access
        Gate::define('view-admin-panel', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('manage-shifts', function (User $user) {
            return $user->isBusiness() || $user->isAiAgent();
        });

        Gate::define('apply-to-shifts', function (User $user) {
            return $user->isWorker();
        });

        Gate::define('manage-agency-workers', function (User $user) {
            return $user->isAgency();
        });
    }
}
