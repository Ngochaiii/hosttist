<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\{Config, Invoices, Cart, CartItem};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Log};
use Carbon\Carbon;
use App\Services\{OrderService, PaymentService, InvoiceService};

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

    public function showQuote()
    {
        try {
            $cart = $this->invoiceService->getCurrentCart();
            $this->invoiceService->validateCart($cart);

            return view('source.web.invoice.quote', $this->invoiceService->generateQuoteData($cart));
        } catch (\Exception $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
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

            return $this->invoiceService->generatePDF(
                $this->prepareInvoicePdfData($invoice),
                'hoa-don-' . $invoice->invoice_number . '.pdf'
            );
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi tạo PDF: ' . $e->getMessage());
        }
    }

    public function proceedToPayment(Request $request, $invoiceId = null)
    {
        try {
            if ($invoiceId) {
                $invoice = Invoices::with(['order', 'order.items.product'])->findOrFail($invoiceId);
            } else {
                $cart = $this->invoiceService->getCurrentCart();
                $this->invoiceService->validateCart($cart);

                $result  = $this->orderService->createFromCart($cart, Auth::user()->customer->id);
                $invoice = $result['invoice'];
            }

            $customer    = Auth::user()->customer;
            $amountToPay = $invoice->total_amount;

            if ($customer->hasBalance($amountToPay)) {
                $result = $this->paymentService->processWalletPayment($invoice->order, $customer);

                if ($result['success']) {
                    $this->clearCartForUser();
                    return redirect()->route('customer.orders')
                        ->with('success', 'Thanh toán thành công từ số dư tài khoản!');
                }
            } else {
                $config = Config::current();

                $result = $this->paymentService->createBankTransferPayment($invoice->order, [
                    'bank_name'      => $config->company_bank_name,
                    'account_number' => $config->company_bank_account_number,
                    'account_name'   => $config->company_bank_account_name,
                ]);

                if ($result['success']) {
                    $this->clearCartForUser();
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
        } catch (\Exception $e) {
            Log::error('Payment process failed: ' . $e->getMessage(), ['invoice_id' => $invoiceId]);
            return back()->with('error', 'Lỗi xử lý thanh toán: ' . $e->getMessage());
        }
    }

    private function clearCartForUser(): void
    {
        $cart = Cart::where('user_id', Auth::id())->first();
        if ($cart) {
            CartItem::where('cart_id', $cart->id)->delete();
            $cart->delete();
        }
        session(['cart_count' => 0]);
    }

    private function prepareInvoicePdfData(Invoices $invoice): array
    {
        return [
            'invoice'     => $invoice,
            'user'        => Auth::user(),
            'config'      => Config::current(),
            'quoteNumber' => $invoice->invoice_number,
            'quoteDate'   => $invoice->created_at->format('d/m/Y'),
            'expireDate'  => $invoice->due_date
                ? Carbon::parse($invoice->due_date)->format('d/m/Y')
                : Carbon::now()->addDays(7)->format('d/m/Y'),
            'subtotal'    => $invoice->order->subtotal,
            'vatRate'     => 0,
            'vatAmount'   => 0,
            'total'       => $invoice->order->total_amount,
        ];
    }
}
