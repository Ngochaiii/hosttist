<?php

namespace App\Services;

use App\Models\{Cart, Config};
use Illuminate\Support\Facades\{Auth, Log};
use Illuminate\Support\Str;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

class InvoiceService extends BaseService
{
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
     * Get cart for current user
     */
    public function getCurrentCart(): ?Cart
    {
        if (Auth::check()) {
            return Cart::where('user_id', Auth::id())->with('items.product')->first();
        }

        return Cart::where('session_id', session()->getId())->with('items.product')->first();
    }

    /**
     * Validate cart for processing
     */
    public function validateCart(?Cart $cart): void
    {
        if (!$cart || $cart->items->isEmpty()) {
            throw new Exception('Giỏ hàng trống, vui lòng thêm sản phẩm trước khi tiếp tục');
        }
    }
}