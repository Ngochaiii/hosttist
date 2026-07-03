<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Thông báo in-app (kênh database) hiển thị trên chuông thông báo của khách.
 *
 * CHỦ ĐÍCH chỉ dùng kênh 'database' — email đã được gửi riêng qua
 * EmailService/Mailable. Tách 2 kênh để SMTP lỗi không bao giờ làm mất
 * thông báo trên UI (và ngược lại).
 */
class CustomerAlert extends Notification
{
    use Queueable;

    public function __construct(
        public string $type,        // payment_approved | payment_rejected | deposit_approved | deposit_rejected | service_ready | ...
        public string $title,
        public string $message,
        public ?string $actionUrl = null,
        public string $level = 'info' // info | success | warning | danger — màu badge trên UI
    ) {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'       => $this->type,
            'title'      => $this->title,
            'message'    => $this->message,
            'action_url' => $this->actionUrl,
            'level'      => $this->level,
        ];
    }
}
