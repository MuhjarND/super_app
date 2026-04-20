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
        \App\Events\LeaveRequestSubmitted::class => [
            \App\Listeners\CreateLeaveApprovalSteps::class,
            \App\Listeners\RecordLeaveAuditTrail::class,
            \App\Listeners\SendLeaveSubmissionNotification::class,
        ],
        \App\Events\LeaveRequestStatusChanged::class => [
            \App\Listeners\RecordLeaveAuditTrail::class,
            \App\Listeners\SendLeaveStatusNotification::class,
        ],
    ];

    /**
     * Register any events for the application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
