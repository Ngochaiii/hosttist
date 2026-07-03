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
        // 'database' đứng trước: nếu SMTP lỗi thì thông báo UI vẫn đã được lưu.
        return ['database', 'mail'];
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
            // Các key title/message/action_url/level để render trên chuông thông báo UI
            'type' => 'provision_' . $this->type,
            'title' => match($this->type) {
                'created' => 'Yêu cầu provision mới #' . $this->provision->id,
                'completed' => 'Provision hoàn thành #' . $this->provision->id,
                'failed' => 'Provision thất bại #' . $this->provision->id,
                default => 'Cập nhật provision #' . $this->provision->id
            },
            'message' => $this->getNotificationMessage(),
            'action_url' => url('/admin/provisions/' . $this->provision->id),
            'level' => $this->type === 'failed' ? 'danger' : 'info',
            'provision_id' => $this->provision->id,
            'customer_name' => $this->provision->customer->user->name,
            'provision_type' => $this->provision->provision_type,
            'product_name' => $this->provision->product->name,
            'status' => $this->provision->provision_status,
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
