<?php

namespace App\Services;

use App\Models\{Cart, Config};
use Illuminate\Support\Facades\{Auth, Log};
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class InvoiceService extends BaseService
{
    // VAT chỉ áp dụng khi khách yêu cầu xuất hoá đơn công ty.
    public const VAT_RATE_COMPANY = 0.08;
    public const DISCOUNT_RATE    = 0.0;

    /**
     * Compute canonical quote amounts for a cart.
     *
     * @param Cart $cart
     * @param bool $vatInvoice  true nếu khách yêu cầu xuất hoá đơn công ty (áp VAT 8%)
     */
    public function computeQuoteAmounts(Cart $cart, bool $vatInvoice = false): array
    {
        $subtotal      = (float) $cart->subtotal;
        $discount      = round($subtotal * self::DISCOUNT_RATE);
        $afterDiscount = $subtotal - $discount;
        $vatRate       = $vatInvoice ? self::VAT_RATE_COMPANY : 0.0;
        $vatAmount     = round($afterDiscount * $vatRate);
        $total         = $afterDiscount + $vatAmount;

        return [
            'subtotal'       => $subtotal,
            'discount'       => $discount,
            'discountRate'   => self::DISCOUNT_RATE,
            'afterDiscount'  => $afterDiscount,
            'vatInvoice'     => $vatInvoice,
            'vatRate'        => $vatRate,
            'vatAmount'      => $vatAmount,
            'total'          => $total,
        ];
    }

    /**
     * Generate quote data for display
     */
    public function generateQuoteData(Cart $cart, bool $vatInvoice = false): array
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

            $amounts   = $this->computeQuoteAmounts($cart, $vatInvoice);
            $subtotal  = $amounts['subtotal'];
            $vatRate   = $amounts['vatRate'];
            $vatAmount = $amounts['vatAmount'];
            $total     = $amounts['total'];

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
                'vatInvoice' => $vatInvoice,
                'vatRate' => $vatRate,
                'vatAmount' => $vatAmount,
                'total' => $total,
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
     * Get cart for current user
     */
    public function getCurrentCart(): ?Cart
    {
        return Cart::where('user_id', Auth::id())->with('items.product')->first();
    }

    /**
     * Validate cart for processing
     */
    public function validateCart(?Cart $cart): void
    {
        if (!$cart || $cart->items->isEmpty()) {
            throw new Exception('Giỏ hàng trống, vui lòng thêm sản phẩm trước khi tiếp tục');
        }

        if ($cart->expires_at && Carbon::parse($cart->expires_at)->isPast()) {
            throw new Exception('Giỏ hàng đã hết hạn, vui lòng tạo lại');
        }
    }
}