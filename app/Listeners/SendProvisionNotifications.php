<?php

namespace App\Listeners;

use App\Events\{ProvisionCreated, ProvisionCompleted, ProvisionFailed};
use App\Mail\ProvisionCreated as ProvisionCreatedMail;
use App\Mail\ProvisionCompleted as ProvisionCompletedMail;
use App\Mail\ProvisionFailed as ProvisionFailedMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendProvisionNotifications
{
    public function handleProvisionCreated(ProvisionCreated $event): void
    {
        $this->dispatchMail($event->provision, ProvisionCreatedMail::class, 'created');
    }

    public function handleProvisionCompleted(ProvisionCompleted $event): void
    {
        $this->dispatchMail($event->provision, ProvisionCompletedMail::class, 'completed');
    }

    public function handleProvisionFailed(ProvisionFailed $event): void
    {
        Log::warning('Provision failed', [
            'provision_id'   => $event->provision->id,
            'failure_reason' => $event->provision->failure_reason ?? null,
        ]);
        $this->dispatchMail($event->provision, ProvisionFailedMail::class, 'failed');
    }

    /**
     * Gửi mail tới email khách + admin (BCC). Không throw — email không được phép
     * làm fail flow nghiệp vụ chính (provision/payment).
     */
    private function dispatchMail($provision, string $mailClass, string $stage): void
    {
        try {
            $email = $provision->customer?->user?->email;
            if (!$email) {
                Log::warning('Provision email skip: no recipient', [
                    'provision_id' => $provision->id,
                    'stage'        => $stage,
                ]);
                return;
            }

            Mail::to($email)->queue(new $mailClass($provision));

            Log::info('Provision email queued', [
                'provision_id' => $provision->id,
                'stage'        => $stage,
                'to'           => $email,
            ]);
        } catch (\Throwable $e) {
            Log::error('Provision email dispatch failed', [
                'provision_id' => $provision->id,
                'stage'        => $stage,
                'error'        => $e->getMessage(),
            ]);
        }
    }
}
