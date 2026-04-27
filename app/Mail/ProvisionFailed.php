<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\ServiceProvision;

class ProvisionFailed extends Mailable
{
    use Queueable, SerializesModels;

    public $provision;

    public function __construct(ServiceProvision $provision)
    {
        $this->provision = $provision;
    }

    public function envelope(): Envelope
    {
        $subject = match($this->provision->provision_type) {
            'ssl'     => 'Lỗi cung cấp SSL Certificate',
            'domain'  => 'Lỗi đăng ký tên miền',
            'hosting' => 'Lỗi tạo hosting account',
            'vps'     => 'Lỗi cung cấp VPS',
            default   => 'Lỗi cung cấp dịch vụ',
        };

        return new Envelope(subject: $subject . ' #' . $this->provision->id);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.provision.failed');
    }

    public function attachments(): array
    {
        return [];
    }
}
