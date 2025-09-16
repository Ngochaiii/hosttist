<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        \App\Events\ProvisionCreated::class => [
            \App\Listeners\SendProvisionNotifications::class . '@handleProvisionCreated',
        ],

        \App\Events\ProvisionCompleted::class => [
            \App\Listeners\SendProvisionNotifications::class . '@handleProvisionCompleted',
        ],

        \App\Events\ProvisionFailed::class => [
            \App\Listeners\SendProvisionNotifications::class . '@handleProvisionFailed',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
