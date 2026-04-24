<?php

namespace App\Listeners;

use App\Events\{ProvisionCreated, ProvisionCompleted, ProvisionFailed};
use Illuminate\Support\Facades\Log;

class SendProvisionNotifications
{
    public function handleProvisionCreated(ProvisionCreated $event)
    {
        // Email/Notification scaffolding — disabled until Mail/Notification classes đầy đủ.
        Log::info('Provision created', ['provision_id' => $event->provision->id]);
    }

    public function handleProvisionCompleted(ProvisionCompleted $event)
    {
        Log::info('Provision completed', ['provision_id' => $event->provision->id]);
    }

    public function handleProvisionFailed(ProvisionFailed $event)
    {
        Log::warning('Provision failed', [
            'provision_id'   => $event->provision->id,
            'failure_reason' => $event->provision->failure_reason ?? null,
        ]);
    }
}
