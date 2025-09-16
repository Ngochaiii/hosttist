<?php

// app/Notifications/AdminProvisionAlert.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\ServiceProvision;

class AdminProvisionAlert extends Notification
{
    use Queueable;

    public $provision;
    public $type;

    public function __construct(ServiceProvision $provision, string $type = 'completed')
    {
        $this->provision = $provision;
        $this->type = $type; // created, completed, failed
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $subject = match($this->type) {
            'created' => 'Yêu cầu provision mới #' . $this->provision->id,
            'completed' => 'Provision hoàn thành #' . $this->provision->id,
            'failed' => 'Provision thất bại #' . $this->provision->id,
            default => 'Cập nhật provision #' . $this->provision->id
        };

        $message = (new MailMessage)
            ->subject($subject)
            ->line($this->getNotificationMessage())
            ->line('Chi tiết provision:')
            ->line('- Khách hàng: ' . $this->provision->customer->user->name)
            ->line('- Loại dịch vụ: ' . ucfirst($this->provision->provision_type))
            ->line('- Sản phẩm: ' . $this->provision->product->name)
            ->line('- Trạng thái: ' . $this->provision->getStatusLabel());

        if ($this->type === 'failed' && $this->provision->failure_reason) {
            $message->line('- Lý do thất bại: ' . $this->provision->failure_reason);
        }

        $message->action('Xem chi tiết', url('/admin/provisions/' . $this->provision->id));

        return $message;
    }

    public function toDatabase($notifiable)
    {
        return [
            'provision_id' => $this->provision->id,
            'customer_name' => $this->provision->customer->user->name,
            'provision_type' => $this->provision->provision_type,
            'product_name' => $this->provision->product->name,
            'status' => $this->provision->provision_status,
            'type' => $this->type,
            'message' => $this->getNotificationMessage()
        ];
    }

    private function getNotificationMessage(): string
    {
        return match($this->type) {
            'created' => 'Có yêu cầu provision mới cần được xử lý.',
            'completed' => 'Provision đã hoàn thành và khách hàng đã được thông báo.',
            'failed' => 'Provision thất bại và cần được xem xét.',
            default => 'Có cập nhật mới về provision.'
        };
    }
}

// app/Notifications/CustomerServiceReady.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\ServiceProvision;

class CustomerServiceReady extends Notification
{
    use Queueable;

    public $provision;

    public function __construct(ServiceProvision $provision)
    {
        $this->provision = $provision;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $serviceType = match($this->provision->provision_type) {
            'ssl' => 'SSL Certificate',
            'domain' => 'Tên miền',
            'hosting' => 'Hosting Account',
            default => 'Dịch vụ'
        };

        return (new MailMessage)
            ->subject($serviceType . ' đã sẵn sàng sử dụng')
            ->greeting('Xin chào ' . $notifiable->name . ',')
            ->line($serviceType . ' của bạn đã được cung cấp thành công và sẵn sàng sử dụng.')
            ->line('Thông tin dịch vụ:')
            ->line('- Loại dịch vụ: ' . $serviceType)
            ->line('- Sản phẩm: ' . $this->provision->product->name)
            ->line('- Ngày hoàn thành: ' . $this->provision->provisioned_at->format('d/m/Y H:i'))
            ->action('Xem chi tiết dịch vụ', route('customer.services.provision.show', $this->provision->id))
            ->line('Để bảo mật, thông tin đăng nhập chỉ có thể xem qua dashboard bảo mật của bạn.')
            ->line('Cảm ơn bạn đã tin tưởng dịch vụ của chúng tôi!');
    }

    public function toDatabase($notifiable)
    {
        return [
            'provision_id' => $this->provision->id,
            'provision_type' => $this->provision->provision_type,
            'product_name' => $this->provision->product->name,
            'completed_at' => $this->provision->provisioned_at,
            'message' => 'Dịch vụ ' . $this->provision->product->name . ' đã sẵn sàng sử dụng.'
        ];
    }
}