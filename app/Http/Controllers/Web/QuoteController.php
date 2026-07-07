<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Services\InvoiceService;
use App\Services\QuotePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class QuoteController extends Controller
{
    protected InvoiceService $invoiceService;
    protected QuotePdfService $quotePdf;

    public function __construct(InvoiceService $invoiceService, QuotePdfService $quotePdf)
    {
        $this->invoiceService = $invoiceService;
        $this->quotePdf       = $quotePdf;
    }

    /**
     * Tạo và tải xuống file PDF báo giá
     */
    public function downloadPdf(Request $request)
    {
        // Lấy giỏ hàng hiện tại
        $cart = $this->getCart();

        // Nếu giỏ hàng trống, chuyển hướng về trang giỏ hàng
        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng trống. Vui lòng thêm sản phẩm trước khi tạo báo giá.');
        }

        $vatInvoice = $request->boolean('vat_invoice');

        // Tạo tên file
        $fileName = 'bao-gia-' . date('Ymd') . '-' . $cart->id . '.pdf';

        // Tạo PDF và tải xuống
        return $this->generateModernPdf($vatInvoice)->download($fileName);
    }

    /**
     * Tạo PDF báo giá từ giỏ hàng (mẫu hoá đơn GTGT — view source.web.quote.pdf).
     */
    private function generateModernPdf(bool $vatInvoice = false)
    {
        $cart = $this->getCart();

        $quoteNumber = 'QUOTE-' . date('Ymd') . '-' . str_pad($cart->id, 4, '0', STR_PAD_LEFT);
        $expireDate  = Carbon::now()->addDays(10)->format('d/m/Y');
        $amounts     = $this->invoiceService->computeQuoteAmounts($cart, $vatInvoice);

        $rows = [];
        foreach ($cart->items as $item) {
            $options = json_decode($item->options, true) ?: [];
            $domain  = $options['domain'] ?? null;
            $period  = $options['period'] ?? null;

            $detailParts = array_filter([
                $domain ? 'Tên miền: ' . $domain : null,
                $period ? 'Thời hạn: ' . $period . ' năm' : null,
            ]);

            $unitPrice = (float) ($item->unit_price ?? $item->price ?? 0);

            $rows[] = [
                'name'      => $item->product->name ?? $item->name,
                'detail'    => implode(' | ', $detailParts),
                'unit'      => match ($item->product->type ?? null) {
                    'domain'  => 'Tên miền',
                    'ssl'     => 'Chứng thư',
                    default   => 'Gói',
                },
                'qty'       => $item->quantity,
                'unitPrice' => $unitPrice,
                'lineTotal' => (float) ($item->subtotal ?? $unitPrice * $item->quantity),
            ];
        }

        return $this->quotePdf->build($rows, $amounts, $vatInvoice, $quoteNumber, Auth::user(), $expireDate);
    }

    /**
     * Lấy giỏ hàng hiện tại
     */
    private function getCart()
    {
        // Route nằm sau middleware frontend.auth — không có guest.
        return Cart::where('user_id', Auth::id())
            ->with('items.product')
            ->first();
    }
}
