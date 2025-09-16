<?php

// app/Mail/ProvisionCreated.php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\ServiceProvision;

class ProvisionCreated extends Mailable
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
            'ssl' => 'Yêu cầu SSL Certificate đang được xử lý',
            'domain' => 'Yêu cầu tên miền đang được xử lý',
            'hosting' => 'Yêu cầu hosting đang được xử lý',
            default => 'Yêu cầu dịch vụ đang được xử lý'
        };

        return new Envelope(
            subject: $subject . ' #' . $this->provision->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.provision.created',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

// app/Mail/ProvisionCompleted.php
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
            'ssl' => 'SSL Certificate đã sẵn sàng sử dụng',
            'domain' => 'Tên miền đã được kích hoạt',
            'hosting' => 'Hosting Account đã sẵn sàng',
            default => 'Dịch vụ đã được cung cấp'
        };

        return new Envelope(
            subject: $subject . ' #' . $this->provision->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.provision.completed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

// app/Mail/ProvisionFailed.php
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
            'ssl' => 'Lỗi cung cấp SSL Certificate',
            'domain' => 'Lỗi đăng ký tên miền',
            'hosting' => 'Lỗi tạo hosting account',
            default => 'Lỗi cung cấp dịch vụ'
        };

        return new Envelope(
            subject: $subject . ' #' . $this->provision->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.provision.failed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}