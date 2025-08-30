<?php

namespace App\Services;

use App\Models\{Cart, Config, Invoices};
use Illuminate\Support\Facades\{Auth, Log, Mail};
use Illuminate\Support\Str;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

class InvoiceService extends BaseService
{
    /**
     * Get or create invoice from cart or existing invoice ID
     */
    public function getOrCreateInvoice($invoiceId = null, Cart $cart = null): Invoices
    {
        $requestId = uniqid('invoice_');
        Log::info("[{$requestId}] Getting or creating invoice", [
            'invoice_id' => $invoiceId,
            'has_cart' => !is_null($cart),
            'cart_id' => $cart->id ?? null,
            'user_id' => Auth::id()
        ]);

        if ($invoiceId) {
            Log::debug("[{$requestId}] Fetching existing invoice", [
                'invoice_id' => $invoiceId
            ]);
            
            $invoice = Invoices::with(['order', 'order.items.product'])->findOrFail($invoiceId);
            
            Log::info("[{$requestId}] Retrieved existing invoice successfully", [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total_amount' => $invoice->total_amount,
                'status' => $invoice->status,
                'customer_id' => $invoice->customer_id
            ]);
            
            return $invoice;
        }

        if (!$cart) {
            Log::error("[{$requestId}] Cannot create invoice: no cart provided");
            throw new Exception('Cart is required to create invoice');
        }

        Log::debug("[{$requestId}] Would create new invoice from cart", [
            'cart_id' => $cart->id,
            'cart_items_count' => $cart->items->count(),
            'cart_total' => $cart->subtotal
        ]);

        // This integration point would use OrderService->createFromCart
        Log::info("[{$requestId}] Invoice creation process initiated - OrderService integration needed");
        
        throw new Exception('OrderService integration needed for createFromCart');
    }

    /**
     * Generate quote data for display
     */
    public function generateQuoteData(Cart $cart): array
    {
        $requestId = uniqid('quote_data_');
        Log::info("[{$requestId}] Generating quote data", [
            'cart_id' => $cart->id,
            'items_count' => $cart->items->count(),
            'cart_subtotal' => $cart->subtotal
        ]);

        try {
            $user = Auth::user();
            $config = Config::current();
            
            $quoteNumber = 'QUOTE-' . time() . Str::random(5);
            $quoteDate = Carbon::now()->format('d/m/Y');
            $expireDate = Carbon::now()->addDays(7)->format('d/m/Y');
            
            $subtotal = $cart->subtotal;
            $vatRate = 0;
            $vatAmount = 0;
            $total = $subtotal;

            Log::debug("[{$requestId}] Quote data calculation completed", [
                'quote_number' => $quoteNumber,
                'subtotal' => $subtotal,
                'total' => $total,
                'expire_date' => $expireDate
            ]);

            $quoteData = [
                'cart' => $cart,
                'user' => $user,
                'quoteNumber' => $quoteNumber,
                'quoteDate' => $quoteDate,
                'expireDate' => $expireDate,
                'config' => $config,
                'subtotal' => $subtotal,
                'vatRate' => $vatRate,
                'vatAmount' => $vatAmount,
                'total' => $total
            ];

            Log::info("[{$requestId}] Quote data generated successfully", [
                'quote_number' => $quoteNumber,
                'customer_name' => $user->name ?? 'Unknown',
                'total_amount' => $total,
                'items_count' => $cart->items->count()
            ]);

            return $quoteData;

        } catch (Exception $e) {
            Log::error("[{$requestId}] Quote data generation failed", [
                'cart_id' => $cart->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate PDF from quote data
     */
    public function generatePDF(array $data, string $fileName): \Illuminate\Http\Response
    {
        $requestId = uniqid('pdf_gen_');
        Log::info("[{$requestId}] Starting PDF generation", [
            'filename' => $fileName,
            'has_cart' => isset($data['cart']),
            'has_invoice' => isset($data['invoice']),
            'data_keys' => array_keys($data)
        ]);

        try {
            // Add QR code if available
            $config = $data['config'] ?? Config::current();
            if ($config && $config->company_bank_qr_code) {
                $qrPath = storage_path('app/public/' . $config->company_bank_qr_code);
                if (file_exists($qrPath)) {
                    $data['qrBase64'] = 'data:image/png;base64,' . base64_encode(file_get_contents($qrPath));
                    Log::debug("[{$requestId}] QR code added to PDF data", [
                        'qr_path' => $qrPath
                    ]);
                } else {
                    Log::debug("[{$requestId}] QR code file not found", [
                        'qr_path' => $qrPath
                    ]);
                }
            }

            Log::debug("[{$requestId}] Loading PDF view", [
                'view' => 'source.web.quote.pdf',
                'paper_size' => 'a4'
            ]);

            $pdf = PDF::loadView('source.web.quote.pdf', $data);
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

            Log::info("[{$requestId}] PDF generated successfully", [
                'filename' => $fileName,
                'view_template' => 'source.web.quote.pdf'
            ]);

            return $pdf->download($fileName);

        } catch (Exception $e) {
            Log::error("[{$requestId}] PDF generation failed", [
                'filename' => $fileName,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    /**
     * Send quote email with PDF attachment
     */
    public function sendQuoteEmail(string $email, string $message, Cart $cart): bool
    {
        $requestId = uniqid('quote_email_');
        Log::info("[{$requestId}] Starting quote email process", [
            'recipient_email' => $email,
            'cart_id' => $cart->id,
            'has_message' => !empty($message),
            'cart_items_count' => $cart->items->count()
        ]);

        try {
            $user = Auth::user();
            $config = Config::current();
            $quoteNumber = 'QUOTE-' . time() . Str::random(5);

            Log::debug("[{$requestId}] Building quote email components", [
                'quote_number' => $quoteNumber,
                'sender_name' => $user->name ?? 'Unknown',
                'company_name' => $config->company_name ?? 'Hostist Company'
            ]);

            // Build email content
            $emailContent = $this->buildQuoteEmailContent($cart, $user, $config, $quoteNumber, $message);
            
            Log::debug("[{$requestId}] Email content built, preparing PDF attachment", [
                'content_length' => strlen($emailContent)
            ]);
            
            // Generate PDF for attachment
            $pdfData = [
                'cart' => $cart,
                'user' => $user,
                'config' => $config,
                'quoteNumber' => $quoteNumber,
                'quoteDate' => date('d/m/Y'),
                'expireDate' => Carbon::now()->addDays(7)->format('d/m/Y'),
                'subtotal' => $cart->subtotal,
                'vatRate' => 0,
                'vatAmount' => 0,
                'total' => $cart->subtotal
            ];

            $pdf = PDF::loadView('source.web.quote.pdf', $pdfData);
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

            Log::debug("[{$requestId}] PDF attachment prepared, sending email", [
                'pdf_view' => 'source.web.quote.pdf'
            ]);

            $subject = 'Báo giá #' . $quoteNumber . ' - ' . ($config->company_name ?? 'Công ty chúng tôi');
            $attachmentName = 'bao-gia-' . date('Ymd') . '.pdf';

            Mail::html($emailContent, function ($mail) use ($email, $subject, $pdf, $attachmentName) {
                $mail->to($email)
                    ->subject($subject)
                    ->attachData($pdf->output(), $attachmentName);
            });

            Log::info("[{$requestId}] Quote email sent successfully", [
                'recipient_email' => $email,
                'quote_number' => $quoteNumber,
                'subject' => $subject,
                'attachment_name' => $attachmentName
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("[{$requestId}] Quote email sending failed", [
                'recipient_email' => $email,
                'cart_id' => $cart->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    /**
     * Build quote email content
     */
    private function buildQuoteEmailContent(Cart $cart, $user, $config, string $quoteNumber, string $message = ''): string
    {
        Log::debug("Building quote email content", [
            'quote_number' => $quoteNumber,
            'items_count' => $cart->items->count(),
            'has_custom_message' => !empty($message),
            'customer_name' => $user->name ?? 'Unknown'
        ]);

        // Build products HTML
        $productsHtml = '';
        foreach ($cart->items as $item) {
            $options = json_decode($item->options, true) ?: [];
            $period = $options['period'] ?? 1;
            $productName = $item->product->name ?? 'Sản phẩm';
            $productSubtotal = number_format($item->subtotal, 0, ',', '.') . ' đ';

            $productsHtml .= "
            <tr>
                <td>{$period} năm {$productName}</td>
                <td>{$productSubtotal}</td>
            </tr>";
        }

        Log::debug("Products HTML built", [
            'products_html_length' => strlen($productsHtml),
            'total_cart_value' => $cart->subtotal
        ]);

        // Main email content
        $emailContent = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                .header h1 { margin: 0; color: #333; font-size: 24px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
                th { font-weight: bold; }
                .footer { margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; font-size: 12px; color: #777; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>" . ($config->company_name ?? 'Hostist company') . "</h1>
                    <p>Báo giá #{$quoteNumber}</p>
                </div>
                <p>Kính gửi {$user->name},</p>
                <p>Cảm ơn bạn đã quan tâm đến dịch vụ của chúng tôi. Chúng tôi gửi đến bạn báo giá theo yêu cầu.</p>";

        // Add message if provided
        if (!empty($message)) {
            $emailContent .= "
            <div style='padding: 15px; background-color: #f5f5f5; border-left: 4px solid #007bff; margin-bottom: 20px;'>
                <p><strong>Lời nhắn:</strong></p>
                <p>{$message}</p>
            </div>";
        }

        $emailContent .= "
            <div style='background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>
                <p><strong>Ngày tạo báo giá:</strong> " . date('d/m/Y') . "</p>
                <p><strong>Ngày hết hạn:</strong> " . Carbon::now()->addDays(7)->format('d/m/Y') . "</p>
                <p><strong>Mã báo giá:</strong> {$quoteNumber}</p>
            </div>
            <div>
                <h3>Thông tin báo giá</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$productsHtml}
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Tổng cộng</th>
                            <th>" . number_format($cart->subtotal, 0, ',', '.') . " đ</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p>Vui lòng kiểm tra file PDF đính kèm để xem chi tiết báo giá.</p>
            <p>Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi qua email " .
            ($config->support_email ?? 'support@hostist.com') . " hoặc số điện thoại " .
            ($config->support_phone ?? 'N/A') . ".</p>
            <p>Trân trọng,<br>" . ($config->company_name ?? 'Hostist company') . "</p>
            <div class='footer'>
                <p>© " . date('Y') . " " . ($config->company_name ?? 'Hostist company') . ". Tất cả các quyền được bảo lưu.</p>
            </div>
            </div>
        </body>
        </html>";

        Log::debug("Quote email content completed", [
            'total_content_length' => strlen($emailContent),
            'has_custom_message_section' => !empty($message)
        ]);
        
        return $emailContent;
    }

    /**
     * Get cart for current user
     */
    public function getCurrentCart(): ?Cart
    {
        $requestId = uniqid('get_cart_');
        Log::debug("[{$requestId}] Getting current cart for user", [
            'user_id' => Auth::id(),
            'is_authenticated' => Auth::check()
        ]);

        $cart = null;
        
        if (Auth::check()) {
            $cart = Cart::where('user_id', Auth::id())->with('items.product')->first();
            Log::debug("[{$requestId}] Retrieved authenticated user cart", [
                'cart_id' => $cart->id ?? null,
                'items_count' => $cart ? $cart->items->count() : 0,
                'cart_total' => $cart ? $cart->subtotal : 0
            ]);
        } else {
            $sessionId = session()->getId();
            $cart = Cart::where('session_id', $sessionId)->with('items.product')->first();
            Log::debug("[{$requestId}] Retrieved session cart", [
                'session_id' => $sessionId,
                'cart_id' => $cart->id ?? null,
                'items_count' => $cart ? $cart->items->count() : 0,
                'cart_total' => $cart ? $cart->subtotal : 0
            ]);
        }

        return $cart;
    }

    /**
     * Validate cart for processing
     */
    public function validateCart(?Cart $cart): void
    {
        $requestId = uniqid('validate_cart_');
        Log::debug("[{$requestId}] Validating cart", [
            'cart_exists' => !is_null($cart),
            'cart_id' => $cart->id ?? null
        ]);

        if (!$cart || $cart->items->isEmpty()) {
            Log::error("[{$requestId}] Cart validation failed: empty or missing cart", [
                'cart_exists' => !is_null($cart),
                'items_count' => $cart ? $cart->items->count() : 0
            ]);
            throw new Exception('Giỏ hàng trống, vui lòng thêm sản phẩm trước khi tiếp tục');
        }

        Log::info("[{$requestId}] Cart validation passed", [
            'cart_id' => $cart->id,
            'items_count' => $cart->items->count(),
            'total_amount' => $cart->subtotal,
            'user_id' => Auth::id()
        ]);
    }
}