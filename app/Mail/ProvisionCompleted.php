<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\ServiceProvision;

class ProvisionCompleted extends Mailable
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
            'ssl'     => 'SSL Certificate đã sẵn sàng sử dụng',
            'domain'  => 'Tên miền đã được kích hoạt',
            'hosting' => 'Hosting Account đã sẵn sàng',
            'vps'     => 'VPS đã sẵn sàng sử dụng',
            default   => 'Dịch vụ đã được cung cấp',
        };

        return new Envelope(subject: $subject . ' #' . $this->provision->id);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.provision.completed');
    }

    public function attachments(): array
    {
        return [];
    }
}
