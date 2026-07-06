<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Config;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class QuoteController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
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
     * Tạo PDF báo giá theo mẫu hoá đơn GTGT (view: source.web.quote.pdf).
     * $vatInvoice quyết định biến thể: thanh toán thường hay xuất hoá đơn VAT
     * (thêm cột thuế, dòng tiền thuế và dùng tài khoản ngân hàng VAT).
     */
    private function generateModernPdf(bool $vatInvoice = false)
    {
        $cart = $this->getCart();
        $user = Auth::user();
        $config = Config::current();

        $quoteNumber = 'QUOTE-' . date('Ymd') . '-' . str_pad($cart->id, 4, '0', STR_PAD_LEFT);
        $quoteDate = Carbon::now()->format('d/m/Y');
        $expireDate = Carbon::now()->addDays(10)->format('d/m/Y');

        $amounts = $this->invoiceService->computeQuoteAmounts($cart, $vatInvoice);

        // Chọn tài khoản ngân hàng theo việc có xuất hóa đơn VAT hay không
        $bank = $config ? $config->bankInfo($vatInvoice) : [];

        // QR chuyển khoản: nhúng base64 vì dompdf chạy với isRemoteEnabled=false
        $qrBase64 = null;
        if (!empty($bank['qr_code'])) {
            $qrPath = public_path('storage/' . $bank['qr_code']);
            if (file_exists($qrPath)) {
                $qrBase64 = 'data:image/' . pathinfo($qrPath, PATHINFO_EXTENSION) . ';base64,'
                    . base64_encode(file_get_contents($qrPath));
            }
        }

        $totalInWords = $this->convertNumberToWords($amounts['total']) . ' đồng';
        $totalInWords = mb_strtoupper(mb_substr($totalInWords, 0, 1)) . mb_substr($totalInWords, 1);

        $pdf = PDF::loadView('source.web.quote.pdf', [
            'cart'         => $cart,
            'user'         => $user,
            'config'       => $config,
            'quoteNumber'  => $quoteNumber,
            'quoteDate'    => $quoteDate,
            'expireDate'   => $expireDate,
            'subtotal'     => $amounts['subtotal'],
            'discount'     => $amounts['discount'],
            'vat'          => $amounts['vatAmount'],
            'vatRate'      => $amounts['vatRate'],
            'total'        => $amounts['total'],
            'vatInvoice'   => $vatInvoice,
            'bank'         => $bank,
            'qrBase64'     => $qrBase64,
            'totalInWords' => $totalInWords,
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'defaultFont' => 'DejaVu Sans',
            'dpi' => 150,
            'defaultMediaType' => 'print',
            'isFontSubsettingEnabled' => true,
        ]);

        return $pdf;
    }

    /**
     * Đọc số tiền thành chữ tiếng Việt (mươi/mốt/tư/lăm/lẻ, hỗ trợ đến hàng tỷ).
     */
    private function convertNumberToWords($number): string
    {
        $number = (int) round($number);
        if ($number === 0) {
            return 'không';
        }

        $units  = ['', ' nghìn', ' triệu', ' tỷ', ' nghìn tỷ'];
        $groups = [];
        while ($number > 0) {
            $groups[] = $number % 1000;
            $number   = intdiv($number, 1000);
        }

        $parts = [];
        for ($i = count($groups) - 1; $i >= 0; $i--) {
            if ($groups[$i] === 0) {
                continue;
            }
            // Nhóm không phải cao nhất phải đọc đủ "không trăm..." (vd 1.000.050)
            $full    = $i < count($groups) - 1;
            $parts[] = $this->readThreeDigits($groups[$i], $full) . ($units[$i] ?? '');
        }

        return trim(implode(' ', $parts));
    }

    private function readThreeDigits(int $n, bool $full): string
    {
        $digits = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
        $tram   = intdiv($n, 100);
        $chuc   = intdiv($n % 100, 10);
        $donvi  = $n % 10;
        $s      = '';

        if ($tram > 0 || $full) {
            $s = $digits[$tram] . ' trăm';
        }

        if ($chuc > 1) {
            $s .= ' ' . $digits[$chuc] . ' mươi';
            $s .= match ($donvi) {
                0       => '',
                1       => ' mốt',
                4       => ' tư',
                5       => ' lăm',
                default => ' ' . $digits[$donvi],
            };
        } elseif ($chuc === 1) {
            $s .= ' mười';
            $s .= match ($donvi) {
                0       => '',
                5       => ' lăm',
                default => ' ' . $digits[$donvi],
            };
        } elseif ($donvi > 0) {
            $s .= ($s !== '' ? ' lẻ ' : '') . $digits[$donvi];
        }

        return trim($s);
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
