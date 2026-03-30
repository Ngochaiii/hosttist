<?php

namespace App\Mail;

use App\Models\CustomerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServiceExpiryReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public CustomerService $service,
        public int             $daysLeft
    ) {}

    public function envelope(): Envelope
    {
        $productName = $this->service->product->name ?? 'Dịch vụ';

        return new Envelope(
            subject: "Nhắc nhở: {$productName} sẽ hết hạn trong {$this->daysLeft} ngày",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.service_expiry',
        );
    }
}
