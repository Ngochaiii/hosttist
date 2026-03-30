<?php

namespace App\Jobs;

use App\Services\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPaymentWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30; // giây giữa các lần retry

    public function __construct(
        private string $transactionId,
        private string $provider,
        private array  $rawData
    ) {}

    public function handle(PaymentService $paymentService): void
    {
        Log::info('ProcessPaymentWebhook: bắt đầu xử lý', [
            'transaction_id' => $this->transactionId,
            'provider'       => $this->provider,
        ]);

        $result = $paymentService->confirmPaymentFromGateway(
            $this->transactionId,
            $this->provider,
            $this->rawData
        );

        if ($result['success']) {
            Log::info('ProcessPaymentWebhook: thanh toán xác nhận thành công', [
                'transaction_id' => $this->transactionId,
                'payment_id'     => $result['payment']->id ?? null,
            ]);
        } else {
            Log::error('ProcessPaymentWebhook: xác nhận thất bại', [
                'transaction_id' => $this->transactionId,
                'error'          => $result['error'] ?? 'unknown',
            ]);

            // Nếu là lỗi business (không phải network) thì không retry
            if (isset($result['no_retry']) && $result['no_retry']) {
                $this->fail(new \RuntimeException($result['error'] ?? 'Payment confirmation failed'));
            } else {
                throw new \RuntimeException($result['error'] ?? 'Payment confirmation failed — will retry');
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessPaymentWebhook: job thất bại sau tất cả các lần retry', [
            'transaction_id' => $this->transactionId,
            'provider'       => $this->provider,
            'error'          => $exception->getMessage(),
        ]);
    }
}
