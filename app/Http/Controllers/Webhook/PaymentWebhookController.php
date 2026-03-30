<?php

namespace App\Http\Controllers\Webhook;

use App\Contracts\PaymentGatewayInterface;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessPaymentWebhook;
use App\Services\Payment\GatewayFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * POST /webhook/payment/{provider}
     *
     * Nhận callback từ VNPay / MoMo / ZaloPay.
     * - Verify chữ ký của provider
     * - Lấy transaction_id từ payload
     * - Dispatch job xử lý bất đồng bộ
     * - Trả về 200 ngay lập tức (provider yêu cầu response nhanh)
     */
    public function handle(Request $request, string $provider): Response
    {
        Log::info("Webhook received: {$provider}", [
            'ip'      => $request->ip(),
            'payload' => $request->all(),
        ]);

        try {
            $gateway = GatewayFactory::make($provider);
        } catch (\InvalidArgumentException $e) {
            Log::warning("Webhook: provider không hợp lệ '{$provider}'");
            return response('Provider not supported', 400);
        }

        // Verify chữ ký — từ chối ngay nếu không hợp lệ
        if (!$gateway->verifyWebhook($request)) {
            Log::warning("Webhook: chữ ký không hợp lệ từ {$provider}", [
                'ip' => $request->ip(),
            ]);
            return response('Invalid signature', 400);
        }

        $transactionId = $this->extractTransactionId($provider, $request);

        if (!$transactionId) {
            Log::error("Webhook: không tìm được transaction_id từ payload {$provider}");
            return response('Missing transaction ID', 400);
        }

        // Dispatch job — xử lý bất đồng bộ để response về provider nhanh nhất có thể
        ProcessPaymentWebhook::dispatch($transactionId, $provider, $request->all());

        // Provider cần nhận 200 OK trong vài giây, nếu không sẽ retry webhook
        return response('OK', 200);
    }

    /**
     * Lấy transaction_id từ payload theo từng provider.
     * Mỗi provider có field name khác nhau.
     */
    private function extractTransactionId(string $provider, Request $request): ?string
    {
        return match ($provider) {
            'vnpay'   => $request->input('vnp_TxnRef'),
            'momo'    => $request->input('orderId'),
            'zalopay' => $request->input('app_trans_id'),
            default   => $request->input('transaction_id'),
        };
    }
}
