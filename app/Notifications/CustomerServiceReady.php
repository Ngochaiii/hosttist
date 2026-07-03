<?php

// app/Notifications/CustomerServiceReady.php
// Tách từ AdminProvisionAlert.php — 2 class chung 1 file làm PSR-4 autoload fail
// ("Class CustomerServiceReady not found" khi file AdminProvisionAlert chưa được load).
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\ServiceProvision;

class CustomerServiceReady extends Notification
{
    use Queueable;

    public $provision;

    public function __construct(ServiceProvision $provision)
    {
        $this->provision = $provision;
    }

    /**
     * Chỉ kênh database: email hoàn thành đã gửi qua ProvisionCompletedMail,
     * giữ 'mail' ở đây sẽ gửi trùng 2 email — và khi SMTP lỗi, exception của
     * kênh mail chặn luôn kênh database (mất thông báo UI).
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        $serviceType = match ($this->provision->provision_type) {
            'ssl' => 'SSL Certificate',
            'domain' => 'Tên miền',
            'hosting' => 'Hosting Account',
            default => 'Dịch vụ'
        };

        return [
            'type'       => 'service_ready',
            'title'      => $serviceType . ' đã sẵn sàng sử dụng',
            'message'    => 'Dịch vụ ' . $this->provision->product->name . ' đã được cung cấp thành công và sẵn sàng sử dụng.',
            'action_url' => route('customer.services.provision.show', $this->provision->id),
            'level'      => 'success',
            'provision_id' => $this->provision->id,
            'provision_type' => $this->provision->provision_type,
            'product_name' => $this->provision->product->name,
            'completed_at' => $this->provision->provisioned_at,
        ];
    }
}
