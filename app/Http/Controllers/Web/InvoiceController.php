<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Config;
use App\Models\User;
use App\Models\Invoices;
use App\Models\Order_items;
use App\Models\Orders;
use App\Models\Payments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\{OrderService, PaymentService};


class InvoiceController extends Controller
{
    protected $orderService;
    protected $paymentService;

    public function __construct(OrderService $orderService, PaymentService $paymentService)
    {
        $this->orderService = $orderService;
        $this->paymentService = $paymentService;
    }
    /**
     * Hiển thị trang tạo báo giá từ giỏ hàng
     */
    public function showQuote(Request $request)
    {
        // Lấy giỏ hàng hiện tại
        $cart = $this->getCart();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng trống, vui lòng thêm sản phẩm trước khi tạo báo giá');
        }

        // Lấy thông tin người dùng
        $user = Auth::user();

        // Tạo số báo giá duy nhất
        $quoteNumber = 'QUOTE-' . time() . Str::random(5);

        // Lấy thông tin công ty
        $config = Config::current();

        // Tạo ngày báo giá và ngày hết hạn
        $quoteDate = Carbon::now()->format('d/m/Y');
        $expireDate = Carbon::now()->addDays(7)->format('d/m/Y');

        // Tính tổng tiền
        $subtotal = $cart->subtotal;
        $vatRate = 0; // Đã bỏ VAT
        $vatAmount = 0;
        $total = $subtotal;

        return view('source.web.invoice.quote', compact(
            'cart',
            'user',
            'quoteNumber',
            'quoteDate',
            'expireDate',
            'config',
            'subtotal',
            'vatRate',
            'vatAmount',
            'total'
        ));
    }

    /**
     * Lấy giỏ hàng hiện tại
     */
    private function getCart()
    {
        if (Auth::check()) {
            $cart = Cart::where('user_id', Auth::id())->with('items.product')->first();
        } else {
            $sessionId = session()->getId();
            $cart = Cart::where('session_id', $sessionId)->with('items.product')->first();
        }

        return $cart;
    }

    /**
     * Tải PDF báo giá
     */
    public function downloadPdf(Request $request, $id = null)
    {
        // Nếu có ID, lấy hóa đơn cụ thể; nếu không, lấy từ giỏ hàng hiện tại
        if ($id) {
            $invoice = Invoices::with(['order.items.product', 'order.customer'])->findOrFail($id);

            // Kiểm tra quyền truy cập
            if (Auth::user()->customer->id != $invoice->order->customer_id) {
                return redirect()->route('customer.invoices')
                    ->with('error', 'Bạn không có quyền truy cập hóa đơn này');
            }

            // Thiết lập dữ liệu cho PDF
            $user = Auth::user();
            $config = Config::current();
            $quoteNumber = $invoice->invoice_number;
            $quoteDate = $invoice->created_at->format('d/m/Y');
            $expireDate = $invoice->due_date ? $invoice->due_date->format('d/m/Y') : Carbon::now()->addDays(7)->format('d/m/Y');
            $subtotal = $invoice->order->subtotal;
            $vatRate = 0; // Không tính VAT
            $vatAmount = 0;
            $total = $invoice->order->total_amount;

            // Dữ liệu cho view PDF
            $data = compact(
                'invoice',
                'user',
                'config',
                'quoteNumber',
                'quoteDate',
                'expireDate',
                'subtotal',
                'vatRate',
                'vatAmount',
                'total'
            );

            // Chuẩn bị QR code
            if ($config && $config->company_bank_qr_code) {
                $qrPath = storage_path('app/public/' . $config->company_bank_qr_code);
                if (file_exists($qrPath)) {
                    $data['qrBase64'] = 'data:image/png;base64,' . base64_encode(file_get_contents($qrPath));
                }
            }

            // Tạo PDF
            $pdf = PDF::loadView('source.web.invoice.pdf', $data);

            // Tạo tên file
            $fileName = 'hoa-don-' . $invoice->invoice_number . '.pdf';
        } else {
            // Lấy từ giỏ hàng hiện tại (cho báo giá mới)
            $cart = $this->getCart();

            if (!$cart || $cart->items->isEmpty()) {
                return redirect()->route('cart.index')
                    ->with('error', 'Giỏ hàng trống, vui lòng thêm sản phẩm trước khi tạo báo giá');
            }

            // Lấy thông tin người dùng
            $user = Auth::user();

            // Tạo số báo giá duy nhất
            $quoteNumber = 'QUOTE-' . time() . Str::random(5);

            // Lấy thông tin công ty
            $config = Config::current();

            // Tạo ngày báo giá và ngày hết hạn
            $quoteDate = Carbon::now()->format('d/m/Y');
            $expireDate = Carbon::now()->addDays(7)->format('d/m/Y');

            // Tính tổng tiền
            $subtotal = $cart->subtotal;
            $vatRate = 0; // Không tính VAT
            $vatAmount = 0;
            $total = $subtotal;

            // Dữ liệu cho view PDF
            $data = compact(
                'cart',
                'user',
                'config',
                'quoteNumber',
                'quoteDate',
                'expireDate',
                'subtotal',
                'vatRate',
                'vatAmount',
                'total'
            );

            // Chuẩn bị QR code
            if ($config && $config->company_bank_qr_code) {
                $qrPath = storage_path('app/public/' . $config->company_bank_qr_code);
                if (file_exists($qrPath)) {
                    $data['qrBase64'] = 'data:image/png;base64,' . base64_encode(file_get_contents($qrPath));
                }
            }

            // Tạo PDF
            $pdf = PDF::loadView('source.web.invoice.pdf', $data);

            // Tạo tên file
            $fileName = 'bao-gia-' . date('Ymd') . '.pdf';
        }

        // Cấu hình PDF
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif',
        ]);

        // Tải xuống file
        return $pdf->download($fileName);
    }

    /**
     * Gửi báo giá qua email
     */
    public function sendEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'message' => 'nullable|string'
        ]);

        // Dùng cách đơn giản để gửi email HTML trực tiếp
        $email = $request->input('email');
        $message = $request->input('message', '');

        try {
            // Lấy giỏ hàng hiện tại
            $cart = $this->getCart();

            if (!$cart || $cart->items->isEmpty()) {
                return redirect()->route('cart.index')
                    ->with('error', 'Giỏ hàng trống, vui lòng thêm sản phẩm trước khi gửi báo giá');
            }

            // Lấy thông tin người dùng và công ty
            $user = Auth::user();
            $config = Config::current();
            $quoteNumber = 'QUOTE-' . time() . Str::random(5);

            // Tạo danh sách sản phẩm HTML
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
                    </tr>
                ";
            }

            // Tạo email content
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

            // Thêm lời nhắn nếu có
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
                ($config->support_email ?? 'supposthostit@gmail.com') . " hoặc số điện thoại " .
                ($config->support_phone ?? 'N/A') . ".</p>

                        <p>Trân trọng,<br>
                        " . ($config->company_name ?? 'Hostist company') . "</p>

                        <div class='footer'>
                            <p>© " . date('Y') . " " . ($config->company_name ?? 'Hostist company') . ". Tất cả các quyền được bảo lưu.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";

            // Tạo PDF để đính kèm
            $pdf = PDF::loadView('source.web.invoice.pdf', [
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
            ]);

            // Cấu hình PDF
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

            // Gửi email với nội dung HTML trực tiếp
            Mail::html($emailContent, function ($mail) use ($email, $quoteNumber, $config, $pdf) {
                $mail->to($email)
                    ->subject('Báo giá #' . $quoteNumber . ' - ' . ($config->company_name ?? 'Công ty chúng tôi'))
                    ->attachData($pdf->output(), 'bao-gia-' . date('Ymd') . '.pdf');
            });

            return redirect()->back()->with('success', 'Đã gửi báo giá qua email thành công.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi khi gửi email: ' . $e->getMessage());
        }
    }

    /**
     * Tiến hành thanh toán
     */
    public function proceedToPayment(Request $request, $invoiceId = null)
    {
        try {
            if ($invoiceId) {
                $invoice = Invoices::with(['order', 'order.items.product'])->findOrFail($invoiceId);
            } else {
                // Create invoice from cart using service
                $cart = $this->getCart();
                $result = $this->orderService->createFromCart($cart, Auth::user()->customer->id);
                $invoice = $result['invoice'];
            }

            $customer = Auth::user()->customer;
            $amountToPay = $invoice->total_amount;

            if ($customer->hasBalance($amountToPay)) {
                // Process wallet payment using service
                $result = $this->paymentService->processWalletPayment($invoice->order, $customer);

                if ($result['success']) {
                    $this->clearCart();
                    return redirect()->route('customer.orders')
                        ->with('success', 'Thanh toán thành công từ số dư tài khoản!');
                }
            } else {
                // Create bank transfer payment using service
                $config = Config::current();
                $bankDetails = [
                    'bank_name' => $config->company_bank_name,
                    'account_number' => $config->company_bank_account_number,
                    'account_name' => $config->company_bank_account_name,
                ];

                $result = $this->paymentService->createBankTransferPayment($invoice->order, $bankDetails);

                if ($result['success']) {
                    $this->clearCart();
                    return view('source.web.payment.bank_transfer', [
                        'payment' => $result['payment'],
                        'transaction_code' => $result['transaction_code'],
                        'config' => $config
                    ]);
                }
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi xử lý thanh toán: ' . $e->getMessage());
        }
    }

    /**
     * Xóa giỏ hàng sau khi thanh toán
     */
    private function clearCart()
    {
        if (Auth::check()) {
            $cart = Cart::where('user_id', Auth::id())->first();
            if ($cart) {
                // Xóa các mục trong giỏ hàng
                CartItem::where('cart_id', $cart->id)->delete();
                // Xóa giỏ hàng
                $cart->delete();
            }
        }
    }
}
