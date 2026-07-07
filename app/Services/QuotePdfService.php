<?php

namespace App\Services;

use App\Models\Config;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Dựng PDF báo giá theo mẫu hoá đơn GTGT (view: source.web.quote.pdf).
 * Dùng chung cho báo giá giỏ hàng (QuoteController) và báo giá gia hạn
 * dịch vụ (ServiceController). $vatInvoice quyết định biến thể thanh toán
 * thường hay xuất hoá đơn VAT (cột thuế + tài khoản ngân hàng VAT).
 */
class QuotePdfService
{
    /**
     * @param array $rows     Mỗi dòng hàng: ['name','detail','unit','qty','unitPrice','lineTotal']
     * @param array $amounts  ['subtotal','vatRate','vatAmount','total'] (+ 'discount' tuỳ chọn)
     * @param string $docTitle Tiêu đề chứng từ: 'BÁO GIÁ' (mặc định) hoặc 'HÓA ĐƠN'.
     */
    public function build(array $rows, array $amounts, bool $vatInvoice, string $quoteNumber, $user, string $expireDate, string $docTitle = 'BÁO GIÁ')
    {
        $config = Config::current();

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

        $totalInWords = $this->numberToWords($amounts['total']) . ' đồng';
        $totalInWords = mb_strtoupper(mb_substr($totalInWords, 0, 1)) . mb_substr($totalInWords, 1);

        $pdf = Pdf::loadView('source.web.quote.pdf', [
            'rows'         => $rows,
            'user'         => $user,
            'config'       => $config,
            'quoteNumber'  => $quoteNumber,
            'expireDate'   => $expireDate,
            'subtotal'     => $amounts['subtotal'],
            'discount'     => $amounts['discount'] ?? 0,
            'vat'          => $amounts['vatAmount'],
            'vatRate'      => $amounts['vatRate'],
            'total'        => $amounts['total'],
            'vatInvoice'   => $vatInvoice,
            'bank'         => $bank,
            'qrBase64'     => $qrBase64,
            'totalInWords' => $totalInWords,
            'docTitle'     => $docTitle,
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
    public function numberToWords($number): string
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
}
