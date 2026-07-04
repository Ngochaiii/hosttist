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
     * Tạo PDF với template hiện đại mới
     */
    private function generateModernPdf(bool $vatInvoice = false)
    {
        // Lấy giỏ hàng hiện tại
        $cart = $this->getCart();
        $user = Auth::user();
        $config = Config::current();

        // Tạo số báo giá
        $quoteNumber = 'QUOTE-' . date('Ymd') . '-' . str_pad($cart->id, 4, '0', STR_PAD_LEFT);
        $quoteDate = Carbon::now()->format('d/m/Y');
        $expireDate = Carbon::now()->addDays(10)->format('d/m/Y');

        $amounts       = $this->invoiceService->computeQuoteAmounts($cart, $vatInvoice);
        $subtotal      = $amounts['subtotal'];
        $discount      = $amounts['discount'];
        $afterDiscount = $amounts['afterDiscount'];
        $vat           = $amounts['vatAmount'];
        $vatRate       = $amounts['vatRate'];
        $total         = $amounts['total'];

        // Tạo HTML với template mới
        $html = $this->createModernPdfTemplate($cart, $user, $config, $quoteNumber, $quoteDate, $expireDate, $subtotal, $discount, $afterDiscount, $vat, $total, $vatRate, $vatInvoice);

        $pdf = PDF::loadHTML($html);

        // Thiết lập options cho PDF
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

    private function generatePdf()
    {
        return $this->generateModernPdf();
    }

    /**
     * Tạo template HTML hiện đại cho PDF
     */
    private function createModernPdfTemplate($cart, $user, $config, $quoteNumber, $quoteDate, $expireDate, $subtotal, $discount, $afterDiscount, $vat, $total, $vatRate = 0.0, $vatInvoice = false)
    {
        // Chọn tài khoản ngân hàng theo việc có xuất hóa đơn VAT hay không
        $bank = $config ? $config->bankInfo($vatInvoice) : [];

        // Tạo danh sách sản phẩm
        $productsHtml = '';
        foreach ($cart->items as $item) {
            $options = json_decode($item->options, true) ?: [];
            $period = $options['period'] ?? 1;
            $domain = $options['domain'] ?? 'N/A';
            $productName = $item->product->name ?? 'Sản phẩm';

            // Chi tiết sản phẩm dựa trên loại
            $productDetails = '';
            if ($item->product && $item->product->type == 'ssl') {
                $productDetails = "
                <div style='margin-top: 5px; color: #666; font-size: 9px; line-height: 1.5;'>
                    - Gói sản phẩm: 01 {$productName}<br>
                    - Tên miền sử dụng: " . ($domain !== 'N/A' ? "*.$domain" : 'N/A') . "<br>
                    - Mức độ xác minh: Xác minh tên miền<br><br>
                    <strong>Đã bao gồm:</strong><br>
                    - Tài khoản quản trị trực tiếp chứng thư số<br>
                    - Không giới hạn số lượng server cài đặt<br>
                    - Không giới hạn số lượng cấp khóa (keypair)<br>
                    - Hỗ trợ và khắc phục sự cố trong vòng 24h<br>
                    - Hàng hóa/dịch vụ hợp lệ, có nguồn gốc chính hãng
                </div>";
            } elseif ($item->product && $item->product->type == 'hosting') {
                $productDetails = "
                <div style='margin-top: 5px; color: #666; font-size: 9px; line-height: 1.5;'>
                    - Gói: {$productName}<br>
                    - Tên miền: {$domain}<br>
                    - Thời hạn: {$period} năm<br>
                    - Disk space: 10GB SSD<br>
                    - Bandwidth: Unlimited<br>
                    - Email accounts: 50<br>
                    - Control Panel: cPanel<br>
                    - Backup hàng ngày: Có
                </div>";
            } elseif ($item->product && $item->product->type == 'domain') {
                $productDetails = "
                <div style='margin-top: 5px; color: #666; font-size: 9px; line-height: 1.5;'>
                    - Tên miền: {$domain}<br>
                    - Thời hạn đăng ký: {$period} năm<br>
                    - Full DNS management<br>
                    - Domain theft protection<br>
                    - Email forwarding
                </div>";
            }

            $productsHtml .= "
            <tr>
                
                <td style='text-align: center; padding: 8px; border: 1px solid #ddd;'>{$item->quantity}</td>
                <td style='text-align: left; font-size: 9px; line-height: 1.5; padding: 8px; border: 1px solid #ddd; vertical-align: top;'>
                    <strong>Cung cấp {$productName} dành cho tên miền của website.</strong><br>
                    {$productDetails}
                </td>
                <td style='text-align: center; padding: 8px; border: 1px solid #ddd;'>{$item->quantity}</td>
                <td style='text-align: center; padding: 8px; border: 1px solid #ddd;'>{$period} năm</td>
                <td style='text-align: center; padding: 8px; border: 1px solid #ddd;'>Không giới hạn</td>
                <td style='text-align: center; padding: 8px; border: 1px solid #ddd;'>Không giới hạn</td>
                <td style='text-align: right; font-weight: bold; padding: 8px; border: 1px solid #ddd;'>" . number_format($item->subtotal, 0, ',', '.') . " </td>
                <td style='text-align: right; font-weight: bold; padding: 8px; border: 1px solid #ddd;'>" . number_format($item->subtotal, 0, ',', '.') . " </td>
            </tr>";
        }

        // Tạo phần QR code
        $qrCodeHtml = '';
        if (!empty($bank['qr_code'])) {
            // Sử dụng đường dẫn tuyệt đối cho PDF
            $qrCodePath = public_path('storage/' . $bank['qr_code']);

            if (file_exists($qrCodePath)) {
                // Chuyển ảnh thành base64 để embed vào PDF
                $imageData = base64_encode(file_get_contents($qrCodePath));

                // Xác định MIME type thủ công dựa trên extension
                $pathInfo = pathinfo($qrCodePath);
                $extension = strtolower($pathInfo['extension'] ?? '');

                $mimeTypes = [
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp'
                ];

                $imageMimeType = $mimeTypes[$extension] ?? 'image/jpeg'; // default to jpeg

                $qrCodeHtml = "
   <img src='data:{$imageMimeType};base64,{$imageData}'
        alt='Payment QR Code'
        style='width: 150px; height: 150px; border: 2px solid #e9ecef; border-radius: 4px; margin: 0 auto 10px; display: block; object-fit: cover;'>
   ";
            } else {
                // Hiển thị thông tin thanh toán nếu không có QR
                $qrCodeHtml = "
                <div style='width: 150px; height: 150px; background: white; border: 2px solid #e9ecef; border-radius: 4px; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 10px; color: #6c757d; text-align: center; line-height: 1.3; flex-direction: column;'>
                    <div style='font-weight: bold; margin-bottom: 8px;'>QR Code</div>
                    <div>Ngân hàng: " . ($bank['name'] ?? 'ACB') . "</div>
                    <div>TK: " . ($bank['account_number'] ?? '218906666') . "</div>
                    <div style='margin-top: 5px; color: #dc3545; font-weight: bold;'>" . number_format($total, 0, ',', '.') . " VNĐ</div>
                    <div style='margin-top: 5px; font-size: 9px;'>Ref: " . str_replace('QUOTE-', 'PAY-', $quoteNumber) . "</div>
                </div>";
            }
        }

        // Chuyển đổi số thành chữ
        $totalInWords = $this->convertNumberToWords($total);

        return "
<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Báo Giá {$quoteNumber}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            background: white;
            color: #333;
        }
        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 20px;
            background: white;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            position: relative;
        }
        .logo-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo {
            width: 60px;
            height: 40px;
            background: linear-gradient(45deg, #ff6b35, #4dabf7, #69db7c);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 8px;
            text-align: center;
            border-radius: 4px;
        }
        .company-info {
            font-size: 14px;
            font-weight: bold;
            color: #4dabf7;
        }
        .stamp {
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 120px;
            border: 3px solid #e74c3c;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: #e74c3c;
            font-weight: bold;
            text-align: center;
            background: rgba(255, 255, 255, 0.9);
        }
        .quote-title {
            position: absolute;
            top: 0;
            right: 0;
            text-align: right;
        }
        .quote-title h1 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .quote-date {
            font-size: 12px;
            color: #666;
        }
        .company-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 40px 0 20px 0;
        }
        .company-box {
            border: 1px solid #ddd;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .company-box h3 {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
            background: #e9ecef;
            padding: 5px;
            text-align: center;
            border-radius: 2px;
        }
        .company-details-content {
            font-size: 10px;
            line-height: 1.6;
        }
        .quotation-content {
            margin-top: 20px;
        }
        .section-title {
            background: #6c757d;
            color: white;
            padding: 8px;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 10px;
            text-align: center;
            border-radius: 4px;
        }
        .quotation-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 20px;
        }
        .quotation-table th,
        .quotation-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            vertical-align: top;
        }
        .quotation-table th {
            background: #f8f9fa;
            font-weight: bold;
            font-size: 9px;
        }
        .quotation-table td:first-child {
            text-align: left;
        }
        .item-details {
            text-align: left;
            font-size: 9px;
            line-height: 1.5;
        }
        .price-column {
            text-align: right;
            font-weight: bold;
        }
        .total-section {
            background: #f8f9fa;
            border: 1px solid #ddd;
        }
        .total-row {
            background: #e9ecef;
        }
        .payment-info {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            background-color: #f8f9fa;
            padding: 15px;
            border: 1px solid #e9ecef;
            margin: 20px 0;
            border-radius: 4px;
        }
        .payment-details {
            flex: 1;
        }
        .payment-details table {
            margin: 0;
            width: 100%;
        }
        .payment-details td {
            border: none;
            padding: 8px 0;
        }
        .payment-details .amount {
            font-size: 16px;
            color: #dc3545;
            font-weight: bold;
        }
        .payment-details .reference {
            font-weight: bold;
            color: #28a745;
        }
        .qr-section {
            flex: 0 0 200px;
            text-align: center;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 2px dashed #dee2e6;
        }
        .qr-instructions {
            font-size: 10px;
            color: #666;
            margin-top: 10px;
            line-height: 1.4;
        }
        .payment-highlight {
            background: #e3f2fd;
            padding: 12px;
            border-radius: 4px;
            margin: 15px 0;
            border-left: 4px solid #2196f3;
            font-size: 11px;
        }
        .tech-specs {
            background-color: #fff;
            padding: 15px;
            border: 1px solid #e9ecef;
            margin: 20px 0;
            font-size: 11px;
            line-height: 1.6;
            border-radius: 4px;
        }
        .footer-note {
            font-size: 9px;
            color: #666;
            margin-top: 10px;
            text-align: center;
            font-style: italic;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 11px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <div class='logo-section'>
                <div class='logo'>LOGO</div>
                <div>
                    <div class='company-info'>" . ($config->company_name ?? 'Hosttist Company') . "</div>
                    <div style='font-size: 10px; color: #666;'>Technology Solutions</div>
                </div>
            </div>
            <div class='quote-title'>
                <h1>BÁO GIÁ</h1>
                <div class='quote-date'>
                    NGÀY TẠO: {$quoteDate}<br>
                    HIỆU LỰC: 10 ngày
                </div>
            </div>
        </div>

        <div class='company-details'>
            <div class='company-box'>
                <h3>BÊN CUNG CẤP DỊCH VỤ</h3>
                <div class='company-details-content'>
                    <strong>" . ($config->company_name ?? 'Hosttist Company') . "</strong><br>
                    Địa chỉ: " . ($config->company_address ?? '5335 Gate Pkwy, 2nd Floor, Jacksonville, FL 32256') . "<br>
                    Điện thoại: " . ($config->support_phone ?? '') . "<br>
                    Email: " . ($config->support_email ?? 'supposthostit@gmail.com') . "<br>
                </div>
            </div>

            <div class='company-box'>
                <h3>KHÁCH HÀNG</h3>
                <div class='company-details-content'>
                    <strong>" . ($user->name ?? '') . "</strong><br><br>
                    Địa chỉ: " . ($user->address ?? '') . "<br>
                    Điện thoại: " . ($user->phone ?? '') . "<br>
                    Fax: <br>
                    Email: " . ($user->email ?? '') . "<br>
                    Website: " . ($user->website ?? '') . "
                </div>
            </div>
        </div>

        <div class='quotation-content'>
            <div class='section-title'>
                NỘI DUNG: BÁO GIÁ DỊCH VỤ HOSTING VÀ CHỨNG THƯ SỐ
            </div>

            <table class='quotation-table'>
                <thead>
                    <tr>
                        <th style='width: 5%;'>#</th>
                        <th style='width: 35%;'>NỘI DUNG</th>
                        <th style='width: 8%;'>SỐ LƯỢNG</th>
                        <th style='width: 8%;'>THỜI HẠN<br>(NĂM)</th>
                        <th style='width: 8%;'>SERVER</th>
                        <th style='width: 8%;'>CẶP KHOÁ</th>
                        <th style='width: 10%;'>ĐƠN GIÁ<br>(VNĐ)</th>
                        <th style='width: 10%;'>THÀNH TIỀN<br>(VNĐ)</th>
                    </tr>
                </thead>
                <tbody>
                    {$productsHtml}
                    <tr class='total-section'>
                        <td colspan='7' style='text-align: right; font-weight: bold;'>Tổng cộng</td>
                        <td class='price-column'>" . number_format($subtotal, 0, ',', '.') . " VNĐ</td>
                    </tr>
                    <tr class='total-section'>
                        <td colspan='7' style='text-align: right;'>Giảm giá (" . (int)(InvoiceService::DISCOUNT_RATE * 100) . "%)</td>
                        <td class='price-column'>" . number_format($discount, 0, ',', '.') . " VNĐ</td>
                    </tr>
                    <tr class='total-section'>
                        <td colspan='7' style='text-align: right; font-weight: bold;'>Tổng sau giảm giá</td>
                        <td class='price-column'>" . number_format($afterDiscount, 0, ',', '.') . " VNĐ</td>
                    </tr>
                    <tr class='total-section'>
                        <td colspan='7' style='text-align: right;'>Thuế VAT " . (int)($vatRate * 100) . "%</td>
                        <td class='price-column'>" . number_format($vat, 0, ',', '.') . " VNĐ</td>
                    </tr>
                    <tr class='total-row'>
                        <td colspan='7' style='text-align: right; font-weight: bold; font-size: 11px;'>TỔNG THANH TOÁN</td>
                        <td class='price-column' style='font-weight: bold; font-size: 11px;'>" . number_format($total, 0, ',', '.') . " VNĐ</td>
                    </tr>
                </tbody>
            </table>

            <div class='footer-note'>
                <strong>Bằng chữ: {$totalInWords} </strong><br>
                (Báo giá đã bao gồm thuế giá trị gia tăng và các khoản thuế, phí khác liên quan)
            </div>

            <div class='section-title'>THÔNG TIN THANH TOÁN</div>

            <div class='payment-info'>
                <div class='payment-details'>
                    <table>
                        <tr>
                            <td style='width: 35%; font-weight: bold; color: #495057;'>Số tiền:</td>
                            <td class='amount'>" . number_format($total, 0, ',', '.') . " VNĐ</td>
                        </tr>
                        <tr>
                            <td style='font-weight: bold; color: #495057;'>Ngân hàng:</td>
                            <td>" . ($bank['name'] ?? 'Ngân hàng Tiền Phong') . "</td>
                        </tr>
                        <tr>
                            <td style='font-weight: bold; color: #495057;'>Số tài khoản:</td>
                            <td style='font-weight: bold; color: #007bff;'>" . ($bank['account_number'] ?? '218906666') . "</td>
                        </tr>
                        <tr>
                            <td style='font-weight: bold; color: #495057;'>Chủ tài khoản:</td>
                            <td>" . ($bank['account_name'] ?? ($config->company_name ?? 'NGUYEN VAN THIEN')) . "</td>
                        </tr>
                        <tr>
                            <td style='font-weight: bold; color: #495057;'>Nội dung chuyển khoản:</td>
                            <td class='reference'>" . str_replace('QUOTE-', 'PAY-', $quoteNumber) . "</td>
                        </tr>
                        <tr>
                            <td style='font-weight: bold; color: #495057;'>Hạn thanh toán:</td>
                            <td style='color: #dc3545; font-weight: bold;'>{$expireDate}</td>
                        </tr>
                    </table>

                    <div class='payment-highlight'>
                        <strong>💡 Thanh toán nhanh:</strong> Quét mã QR để thanh toán ngay qua ứng dụng ngân hàng hoặc sử dụng thông tin tài khoản bên trên.
                    </div>
                </div>

                <div class='qr-section'>
                    {$qrCodeHtml}
                    
                    <div class='qr-instructions'>
                        <strong>📱 Cách thanh toán:</strong><br>
                        1. Mở ứng dụng ngân hàng<br>
                        2. Quét mã QR này<br>
                        3. Kiểm tra thông tin<br>
                        4. Xác nhận thanh toán
                    </div>
                </div>
            </div>
        </div>

        <div class='footer'>
            <p style='margin: 5px 0;'><strong>Cảm ơn quý khách đã tin tưởng dịch vụ của chúng tôi!</strong></p>
            <p style='margin: 5px 0;'>Mọi thắc mắc xin liên hệ: " . ($config->support_email ?? 'supposthostit@gmail.com') . " | " . ($config->support_phone ?? '0919 985 473') . "</p>
            <p style='margin: 5px 0;'>Báo giá này có hiệu lực đến ngày {$expireDate}</p>
        </div>
    </div>
</body>
</html>";
    }

    /**
     * Chuyển đổi số thành chữ (tiếng Việt)
     */
    private function convertNumberToWords($number)
    {
        $ones = array(
            '',
            'một',
            'hai',
            'ba',
            'bốn',
            'năm',
            'sáu',
            'bảy',
            'tám',
            'chín',
            'mười',
            'mười một',
            'mười hai',
            'mười ba',
            'mười bốn',
            'mười lăm',
            'mười sáu',
            'mười bảy',
            'mười tám',
            'mười chín'
        );

        $tens = array('', '', 'hai mười', 'ba mười', 'bốn mười', 'năm mười', 'sáu mười', 'bảy mười', 'tám mười', 'chín mười');

        if ($number < 20) {
            return $ones[$number];
        } elseif ($number < 100) {
            return $tens[intval($number / 10)] . ' ' . $ones[$number % 10];
        } elseif ($number < 1000) {
            return $ones[intval($number / 100)] . ' trăm ' . $this->convertNumberToWords($number % 100);
        } elseif ($number < 1000000) {
            return $this->convertNumberToWords(intval($number / 1000)) . ' nghìn ' . $this->convertNumberToWords($number % 1000);
        } elseif ($number < 1000000000) {
            return $this->convertNumberToWords(intval($number / 1000000)) . ' triệu ' . $this->convertNumberToWords($number % 1000000);
        }

        return 'Số quá lớn';
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
