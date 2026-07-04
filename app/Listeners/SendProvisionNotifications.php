<?php

namespace App\Listeners;

use App\Events\{ProvisionCreated, ProvisionCompleted, ProvisionFailed};
use App\Notifications\CustomerAlert;
use App\Notifications\CustomerServiceReady;
use Illuminate\Support\Facades\Log;

class SendProvisionNotifications
{
    public function handleProvisionCreated(ProvisionCreated $event): void
    {
        $provision = $event->provision;
        $this->notify($provision, new CustomerAlert(
            'provision_created',
            'Yêu cầu thiết lập dịch vụ đã được tạo',
            'Dịch vụ ' . ($provision->product->name ?? '#' . $provision->id)
                . ' đang được thiết lập. Chúng tôi sẽ thông báo khi hoàn tất.',
            route('customer.services.provision.show', $provision->id),
            'info'
        ), 'created');
    }

    public function handleProvisionCompleted(ProvisionCompleted $event): void
    {
        $provision = $event->provision;
        $this->notify($provision, new CustomerServiceReady($provision), 'completed');
    }

    public function handleProvisionFailed(ProvisionFailed $event): void
    {
        $provision = $event->provision;
        Log::warning('Provision failed', [
            'provision_id'   => $provision->id,
            'failure_reason' => $provision->failure_reason ?? null,
        ]);
        $this->notify($provision, new CustomerAlert(
            'provision_failed',
            'Thiết lập dịch vụ gặp sự cố',
            'Dịch vụ ' . ($provision->product->name ?? '#' . $provision->id)
                . ' chưa thể thiết lập. Chúng tôi đang xử lý và sẽ liên hệ với bạn.',
            route('customer.services.provision.show', $provision->id),
            'danger'
        ), 'failed');
    }

    /**
     * Gửi thông báo in-app cho khách. Không throw — thông báo không được phép
     * làm fail flow nghiệp vụ chính (provision/payment).
     */
    private function notify($provision, $notification, string $stage): void
    {
        try {
            $user = $provision->customer?->user;
            if (!$user) {
                Log::warning('Provision notification skip: no user', [
                    'provision_id' => $provision->id,
                    'stage'        => $stage,
                ]);
                return;
            }

            $user->notify($notification);

            Log::info('Provision notification sent', [
                'provision_id' => $provision->id,
                'stage'        => $stage,
                'user_id'      => $user->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Provision notification failed', [
                'provision_id' => $provision->id,
                'stage'        => $stage,
                'error'        => $e->getMessage(),
            ]);
        }
    }
}
