<?php

// app/Events/ProvisionCreated.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\ServiceProvision;

class ProvisionCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $provision;

    public function __construct(ServiceProvision $provision)
    {
        $this->provision = $provision;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}

// app/Events/ProvisionCompleted.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\ServiceProvision;

class ProvisionCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $provision;

    public function __construct(ServiceProvision $provision)
    {
        $this->provision = $provision;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}

// app/Events/ProvisionFailed.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\ServiceProvision;

class ProvisionFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $provision;

    public function __construct(ServiceProvision $provision)
    {
        $this->provision = $provision;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}

// app/Listeners/SendProvisionNotifications.php
namespace App\Listeners;

use App\Events\{ProvisionCreated, ProvisionCompleted, ProvisionFailed};
use App\Mail\{ProvisionCreated as ProvisionCreatedMail, ProvisionCompleted as ProvisionCompletedMail, ProvisionFailed as ProvisionFailedMail};
use App\Notifications\{AdminProvisionAlert, CustomerServiceReady};
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class SendProvisionNotifications
{
    /**
     * Handle provision created event
     */
    public function handleProvisionCreated(ProvisionCreated $event)
    {
        try {
            $provision = $event->provision;
            
            // Send email to customer
            Mail::to($provision->customer->user->email)
                ->queue(new ProvisionCreatedMail($provision));

            // Notify admins
            $admins = User::where('role', 'admin')->orWhere('role', 'super_admin')->get();
            Notification::send($admins, new AdminProvisionAlert($provision, 'created'));

            // Update delivery status
            $provision->update([
                'delivery_status' => 'sent',
                'delivered_at' => now()
            ]);

            Log::info('Provision created notifications sent', [
                'provision_id' => $provision->id,
                'customer_id' => $provision->customer_id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send provision created notifications', [
                'provision_id' => $provision->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle provision completed event
     */
    public function handleProvisionCompleted(ProvisionCompleted $event)
    {
        try {
            $provision = $event->provision;

            // Send completion email to customer
            Mail::to($provision->customer->user->email)
                ->queue(new ProvisionCompletedMail($provision));

            // Send notification to customer user (for in-app notifications)
            $provision->customer->user->notify(new CustomerServiceReady($provision));

            // Notify admins
            $admins = User::where('role', 'admin')->orWhere('role', 'super_admin')->get();
            Notification::send($admins, new AdminProvisionAlert($provision, 'completed'));

            // Update delivery status
            $provision->update([
                'delivery_status' => 'sent',
                'delivered_at' => now()
            ]);

            Log::info('Provision completed notifications sent', [
                'provision_id' => $provision->id,
                'customer_id' => $provision->customer_id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send provision completed notifications', [
                'provision_id' => $provision->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle provision failed event
     */
    public function handleProvisionFailed(ProvisionFailed $event)
    {
        try {
            $provision = $event->provision;

            // Send failure email to customer
            Mail::to($provision->customer->user->email)
                ->queue(new ProvisionFailedMail($provision));

            // Notify admins with high priority
            $admins = User::where('role', 'admin')->orWhere('role', 'super_admin')->get();
            Notification::send($admins, new AdminProvisionAlert($provision, 'failed'));

            // Update delivery status
            $provision->update([
                'delivery_status' => 'sent',
                'delivered_at' => now()
            ]);

            Log::warning('Provision failed notifications sent', [
                'provision_id' => $provision->id,
                'customer_id' => $provision->customer_id,
                'failure_reason' => $provision->failure_reason
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send provision failed notifications', [
                'provision_id' => $provision->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }
}

// app/Providers/EventServiceProvider.php - ADD TO EXISTING FILE
namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

// Import provision events and listeners
use App\Events\{ProvisionCreated, ProvisionCompleted, ProvisionFailed};
use App\Listeners\SendProvisionNotifications;

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

        // Provision Events
        ProvisionCreated::class => [
            SendProvisionNotifications::class . '@handleProvisionCreated',
        ],

        ProvisionCompleted::class => [
            SendProvisionNotifications::class . '@handleProvisionCompleted',
        ],

        ProvisionFailed::class => [
            SendProvisionNotifications::class . '@handleProvisionFailed',
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