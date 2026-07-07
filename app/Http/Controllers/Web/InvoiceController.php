<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\{Config, Invoices, Cart, CartItem};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Log};
use Carbon\Carbon;
use App\Services\{OrderService, PaymentService, InvoiceService, QuotePdfService};

class InvoiceController extends Controller
{
    protected $orderService;
    protected $paymentService;
    protected $invoiceService;

    public function __construct(
        OrderService $orderService,
        PaymentService $paymentService,
        InvoiceService $invoiceService
    ) {
        $this->orderService   = $orderService;
        $this->paymentService = $paymentService;
        $this->invoiceService = $invoiceService;
    }

    public function index()
    {
        return redirect()->route('customer.invoices');
    }

    public function showQuote(Request $request)
    {
        try {
            $cart = $this->invoiceService->getCurrentCart();
            $this->invoiceService->validateCart($cart);

            $vatInvoice = $request->boolean('vat_invoice');

            return view('source.web.invoice.quote', $this->invoiceService->generateQuoteData($cart, $vatInvoice));
        } catch (\Exception $e) {
            Log::error('Show quote failed: ' . $e->getMessage());
            return redirect()->route('cart.index')
                ->with('error', 'Không thể tạo báo giá. Vui lòng kiểm tra lại giỏ hàng và thử lại.');
        }
    }

    public function downloadPdf($id)
    {
        try {
            $invoice = Invoices::with(['order.items.product', 'order.customer'])->findOrFail($id);

            if (Auth::user()->customer->id != $invoice->order->customer_id) {
                return redirect()->route('customer.invoices')
                    ->with('error', 'Bạn không có quyền truy cập hóa đơn này');
            }

            $vatInvoice = (bool) $invoice->vat_invoice_requested;
            $subtotal   = (float) $invoice->subtotal;
            $vatAmount  = (float) $invoice->tax_amount;
            // Suy ra thuế suất từ số tiền thuế thực lưu (khỏi lệ thuộc hằng số nếu sau này đổi).
            $vatRate    = $vatInvoice && $subtotal > 0 ? round($vatAmount / $subtotal, 2) : 0.0;

            $rows = [];
            foreach ($invoice->order->items as $item) {
                $options = json_decode($item->options, true) ?: [];
                $domain  = $options['domain'] ?? null;
                $period  = $options['period'] ?? ($item->duration ?? null);

                $detailParts = array_filter([
                    $domain ? 'Tên miền: ' . $domain : null,
                    $period ? 'Thời hạn: ' . $period . ' năm' : null,
                ]);

                $unitPrice = (float) ($item->price ?? 0);

                $rows[] = [
                    'name'      => $item->name ?? $item->product->name ?? 'Dịch vụ',
                    'detail'    => implode(' | ', $detailParts),
                    'unit'      => match ($item->product->type ?? null) {
                        'domain' => 'Tên miền',
                        'ssl'    => 'Chứng thư',
                        default  => 'Gói',
                    },
                    'qty'       => $item->quantity,
                    'unitPrice' => $unitPrice,
                    'lineTotal' => (float) ($item->subtotal ?? $unitPrice * $item->quantity),
                ];
            }

            $amounts = [
                'subtotal'  => $subtotal,
                'discount'  => (float) ($invoice->discount_amount ?? 0),
                'vatRate'   => $vatRate,
                'vatAmount' => $vatAmount,
                'total'     => (float) $invoice->total_amount,
            ];

            $expireDate = $invoice->due_date
                ? Carbon::parse($invoice->due_date)->format('d/m/Y')
                : Carbon::now()->addDays(7)->format('d/m/Y');

            return app(QuotePdfService::class)
                ->build($rows, $amounts, $vatInvoice, $invoice->invoice_number, Auth::user(), $expireDate, 'HÓA ĐƠN')
                ->download('hoa-don-' . $invoice->invoice_number . '.pdf');
        } catch (\Exception $e) {
            Log::error('Invoice PDF download failed: ' . $e->getMessage(), ['invoice_id' => $id]);
            return back()->with('error', 'Không thể tạo file PDF hóa đơn. Vui lòng thử lại sau.');
        }
    }

    public function proceedToPayment(Request $request, $invoiceId = null)
    {
        try {
            if ($invoiceId) {
                $invoice = Invoices::with(['order', 'order.items.product'])->findOrFail($invoiceId);

                if (Auth::user()->customer->id != $invoice->customer_id) {
                    return redirect()->route('customer.invoices')
                        ->with('error', 'Bạn không có quyền truy cập hóa đơn này');
                }
            } else {
                $vatInvoice = $request->boolean('vat_invoice');
                $vatInfo    = [];

                if ($vatInvoice) {
                    $validated = $request->validate([
                        'vat_company_name'    => 'required|string|max:255',
                        'vat_tax_code'        => 'required|string|max:50|regex:/^[0-9\-]{10,14}$/',
                        'vat_company_address' => 'required|string|max:255',
                        'vat_company_email'   => 'required|email|max:255',
                    ], [
                        'vat_tax_code.regex' => 'Mã số thuế phải gồm 10–14 ký tự số (cho phép dấu gạch ngang).',
                    ]);
                    $vatInfo = $validated;
                }

                // Lock cart + tạo order + xóa cart items trong cùng 1 transaction
                // để double-click / F5 không tạo trùng order.
                $invoice = DB::transaction(function () use ($vatInvoice, $vatInfo) {
                    $cart = Cart::where('user_id', Auth::id())
                        ->lockForUpdate()
                        ->with('items.product')
                        ->first();

                    $this->invoiceService->validateCart($cart);

                    $result = $this->orderService->createFromCart(
                        $cart,
                        Auth::user()->customer->id,
                        $vatInvoice,
                        $vatInfo
                    );

                    CartItem::where('cart_id', $cart->id)->delete();
                    $cart->update([
                        'subtotal'        => 0,
                        'tax_amount'      => 0,
                        'discount_amount' => 0,
                        'total_amount'    => 0,
                    ]);

                    return $result['invoice'];
                });

                session(['cart_count' => 0]);
            }

            $customer    = Auth::user()->customer;
            $amountToPay = $invoice->total_amount;

            // User chọn 'wallet' hoặc 'bank' (default: wallet nếu đủ tiền, else bank)
            $methodRequested = $request->input('payment_method');
            $useWallet = $methodRequested
                ? ($methodRequested === 'wallet' && $customer->hasBalance($amountToPay))
                : $customer->hasBalance($amountToPay);

            if ($methodRequested === 'wallet' && !$customer->hasBalance($amountToPay)) {
                return redirect()->route('deposit')
                    ->with('error', 'Số dư ví không đủ. Vui lòng nạp thêm hoặc chọn chuyển khoản ngân hàng.');
            }

            if ($useWallet) {
                $result = $this->paymentService->processWalletPayment($invoice->order, $customer);

                if ($result['success']) {
                    return redirect()->route('customer.orders')
                        ->with('success', 'Thanh toán thành công từ số dư tài khoản!');
                }
            } else {
                $config = Config::current();
                $bank = $config->bankInfo($invoice->vat_invoice_requested);

                $result = $this->paymentService->createBankTransferPayment($invoice->order, [
                    'bank_name'      => $bank['name'],
                    'account_number' => $bank['account_number'],
                    'account_name'   => $bank['account_name'],
                ]);

                if ($result['success']) {
                    return view('source.web.payment.bank_transfer', [
                        'payment'          => $result['payment'],
                        'transaction_code' => $result['transaction_code'],
                        'config'           => $config,
                        'invoice'          => $invoice,
                        'amountToPay'      => $amountToPay,
                    ]);
                }
            }

            return back()->with('error', 'Không thể xử lý thanh toán');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Payment process failed: ' . $e->getMessage(), ['invoice_id' => $invoiceId]);
            return back()->with('error', 'Không thể xử lý thanh toán. Vui lòng thử lại hoặc liên hệ hỗ trợ.');
        }
    }
}
