<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\{Config, Invoices, Cart, CartItem};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Log, Mail};
use Illuminate\Support\Str;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
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
        $this->orderService = $orderService;
        $this->paymentService = $paymentService;
        $this->invoiceService = $invoiceService;
        
        Log::info("[InvoiceController] Controller instantiated", [
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name ?? 'Guest',
            'services_injected' => [
                'order' => OrderService::class,
                'payment' => PaymentService::class,
                'invoice' => InvoiceService::class
            ]
        ]);
    }

    /**
     * Show quote page
     */
    public function showQuote(Request $request)
    {
        $requestId = uniqid('quote_show_');
        Log::info("[{$requestId}] Quote page requested", [
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name ?? 'Guest',
            'ip' => $request->ip()
        ]);

        try {
            // Get and validate cart
            $cart = $this->invoiceService->getCurrentCart();
            $this->invoiceService->validateCart($cart);

            // Generate quote data
            $quoteData = $this->invoiceService->generateQuoteData($cart);

            Log::info("[{$requestId}] Quote data prepared successfully", [
                'quote_number' => $quoteData['quoteNumber'],
                'total_amount' => $quoteData['total'],
                'items_count' => $cart->items->count()
            ]);

            return view('source.web.invoice.quote', $quoteData);

        } catch (\Exception $e) {
            Log::error("[{$requestId}] Quote page failed", [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }
    }

    /**
     * Download PDF
     */
    public function downloadPdf(Request $request, $id = null)
    {
        $requestId = uniqid('pdf_download_');
        Log::info("[{$requestId}] PDF download requested", [
            'invoice_id' => $id,
            'user_id' => Auth::id(),
            'has_invoice_id' => !is_null($id)
        ]);

        try {
            if ($id) {
                // Existing invoice PDF
                $invoice = Invoices::with(['order.items.product', 'order.customer'])->findOrFail($id);
                
                // Check permissions
                if (Auth::user()->customer->id != $invoice->order->customer_id) {
                    Log::warning("[{$requestId}] Unauthorized PDF access attempt", [
                        'user_customer_id' => Auth::user()->customer->id,
                        'invoice_customer_id' => $invoice->order->customer_id
                    ]);
                    return redirect()->route('customer.invoices')
                        ->with('error', 'Bạn không có quyền truy cập hóa đơn này');
                }

                $data = $this->prepareInvoicePdfData($invoice);
                $fileName = 'hoa-don-' . $invoice->invoice_number . '.pdf';

            } else {
                // New quote PDF from cart
                $cart = $this->invoiceService->getCurrentCart();
                $this->invoiceService->validateCart($cart);
                $data = $this->invoiceService->generateQuoteData($cart);
                $fileName = 'bao-gia-' . date('Ymd') . '.pdf';
            }

            return $this->invoiceService->generatePDF($data, $fileName);

        } catch (\Exception $e) {
            Log::error("[{$requestId}] PDF download failed", [
                'invoice_id' => $id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Lỗi tạo PDF: ' . $e->getMessage());
        }
    }

    /**
     * Send quote email - FIXED to match existing Blade template structure
     */
    public function sendEmail(Request $request)
    {
        $requestId = uniqid('quote_email_');
        Log::info("[{$requestId}] Quote email send requested", [
            'user_id' => Auth::id(),
            'recipient_email' => $request->input('email'),
            'has_message' => !empty($request->input('message'))
        ]);

        $request->validate([
            'email' => 'required|email',
            'message' => 'nullable|string'
        ]);

        try {
            $email = $request->input('email');
            $message = $request->input('message', '');

            // Get cart and validate
            $cart = $this->invoiceService->getCurrentCart();
            $this->invoiceService->validateCart($cart);

            $user = Auth::user();
            $config = Config::current();
            $quoteNumber = 'QUOTE-' . time() . Str::random(5);

            // Prepare cart items for template (match your existing structure)
            $items = [];
            foreach ($cart->items as $item) {
                $options = json_decode($item->options, true) ?: [];
                $period = $options['period'] ?? 1;
                $items[] = [
                    'name' => $item->product->name ?? 'Sản phẩm',
                    'period' => $period,
                    'subtotal' => $item->subtotal
                ];
            }

            // Prepare data for existing Blade template structure
            $emailData = [
                'quoteNumber' => $quoteNumber,
                'quoteDate' => date('d/m/Y'),
                'expireDate' => Carbon::now()->addDays(7)->format('d/m/Y'),
                'userName' => $user->name,
                'companyName' => $config->company_name ?? 'Hostist company',
                'companyEmail' => $config->support_email ?? 'support@hostist.com',
                'companyPhone' => $config->support_phone ?? 'N/A',
                'message' => $message, // Custom message from user
                'items' => $items, // Formatted items array
                'total' => $cart->subtotal
            ];

            Log::debug("[{$requestId}] Preparing email with existing Blade template", [
                'template' => 'emails.quote_request',
                'cart_items_count' => count($items),
                'template_data_keys' => array_keys($emailData)
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

            $subject = 'Báo giá #' . $quoteNumber . ' - ' . ($config->company_name ?? 'Công ty chúng tôi');
            $attachmentName = 'bao-gia-' . date('Ymd') . '.pdf';

            // Send using existing Blade template
            Mail::send('emails.quote_request', $emailData, function ($mail) use ($email, $subject, $pdf, $attachmentName) {
                $mail->to($email)
                    ->subject($subject)
                    ->attachData($pdf->output(), $attachmentName);
            });

            Log::info("[{$requestId}] Quote email sent successfully using existing Blade template", [
                'recipient_email' => $email,
                'template_used' => 'emails.quote_request',
                'quote_number' => $quoteNumber,
                'items_count' => count($items)
            ]);

            return redirect()->back()->with('success', 'Đã gửi báo giá qua email thành công.');

        } catch (\Exception $e) {
            Log::error("[{$requestId}] Quote email sending failed", [
                'recipient_email' => $request->input('email'),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return redirect()->back()->with('error', 'Lỗi khi gửi email: ' . $e->getMessage());
        }
    }

    /**
     * Proceed to payment
     */
    public function proceedToPayment(Request $request, $invoiceId = null)
    {
        $requestId = uniqid('payment_process_');
        Log::info("[{$requestId}] Payment process started", [
            'invoice_id' => $invoiceId,
            'user_id' => Auth::id(),
            'customer_id' => Auth::user()->customer->id ?? null
        ]);

        try {
            $invoice = null;
            
            if ($invoiceId) {
                // Existing invoice payment
                $invoice = Invoices::with(['order', 'order.items.product'])->findOrFail($invoiceId);
                
                Log::info("[{$requestId}] Processing existing invoice payment", [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => $invoice->total_amount
                ]);
            } else {
                // Create new order and invoice from cart
                $cart = $this->invoiceService->getCurrentCart();
                $this->invoiceService->validateCart($cart);

                Log::info("[{$requestId}] Creating new order from cart", [
                    'cart_id' => $cart->id,
                    'customer_id' => Auth::user()->customer->id
                ]);

                $result = $this->orderService->createFromCart($cart, Auth::user()->customer->id);
                $invoice = $result['invoice'];

                Log::info("[{$requestId}] New order and invoice created", [
                    'order_id' => $result['order']->id,
                    'invoice_id' => $invoice->id
                ]);
            }

            $customer = Auth::user()->customer;
            $amountToPay = $invoice->total_amount;

            Log::debug("[{$requestId}] Checking payment options", [
                'amount_to_pay' => $amountToPay,
                'customer_balance' => $customer->balance,
                'can_pay_with_wallet' => $customer->hasBalance($amountToPay)
            ]);

            if ($customer->hasBalance($amountToPay)) {
                // Process wallet payment
                Log::info("[{$requestId}] Processing wallet payment");

                $result = $this->paymentService->processWalletPayment($invoice->order, $customer);

                if ($result['success']) {
                    $this->clearCart();
                    
                    Log::info("[{$requestId}] Wallet payment completed successfully", [
                        'payment_id' => $result['payment']->id,
                        'new_balance' => $result['new_balance']
                    ]);

                    return redirect()->route('customer.orders')
                        ->with('success', 'Thanh toán thành công từ số dư tài khoản!');
                }
            } else {
                // Create bank transfer payment
                Log::info("[{$requestId}] Creating bank transfer payment", [
                    'insufficient_balance' => $customer->balance,
                    'required_amount' => $amountToPay
                ]);

                $config = Config::current();
                $bankDetails = [
                    'bank_name' => $config->company_bank_name,
                    'account_number' => $config->company_bank_account_number,
                    'account_name' => $config->company_bank_account_name,
                ];

                $result = $this->paymentService->createBankTransferPayment($invoice->order, $bankDetails);

                if ($result['success']) {
                    $this->clearCart();
                    
                    Log::info("[{$requestId}] Bank transfer payment created successfully", [
                        'payment_id' => $result['payment']->id,
                        'transaction_code' => $result['transaction_code']
                    ]);

                    return view('source.web.payment.bank_transfer', [
                        'payment' => $result['payment'],
                        'transaction_code' => $result['transaction_code'],
                        'config' => $config,
                        'invoice' => $invoice,  // Thêm dòng này
                        'amountToPay' => $amountToPay  // Thêm dòng này nếu cần
                    ]);
                }
            }

            Log::error("[{$requestId}] Payment processing failed - no successful path");
            return back()->with('error', 'Không thể xử lý thanh toán');

        } catch (\Exception $e) {
            Log::error("[{$requestId}] Payment process exception", [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return back()->with('error', 'Lỗi xử lý thanh toán: ' . $e->getMessage());
        }
    }

    /**
     * Clear cart after successful payment
     */
    private function clearCart()
    {
        $requestId = uniqid('clear_cart_');
        Log::info("[{$requestId}] Clearing cart after payment", [
            'user_id' => Auth::id()
        ]);

        if (Auth::check()) {
            $cart = Cart::where('user_id', Auth::id())->first();
            if ($cart) {
                $itemsCount = $cart->items()->count();
                
                CartItem::where('cart_id', $cart->id)->delete();
                $cart->delete();

                Log::info("[{$requestId}] Cart cleared successfully", [
                    'cart_id' => $cart->id,
                    'items_deleted' => $itemsCount
                ]);
            }
        }
    }

    /**
     * Prepare PDF data for existing invoice
     */
    private function prepareInvoicePdfData(Invoices $invoice): array
    {
        Log::debug("Preparing invoice PDF data", [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number
        ]);

        $user = Auth::user();
        $config = Config::current();

        return [
            'invoice' => $invoice,
            'user' => $user,
            'config' => $config,
            'quoteNumber' => $invoice->invoice_number,
            'quoteDate' => $invoice->created_at->format('d/m/Y'),
            'expireDate' => $invoice->due_date ? 
            Carbon::parse($invoice->due_date)->format('d/m/Y') :  // ✅ Thêm Carbon::parse()
            Carbon::now()->addDays(7)->format('d/m/Y'),
            'subtotal' => $invoice->order->subtotal,
            'vatRate' => 0,
            'vatAmount' => 0,
            'total' => $invoice->order->total_amount
        ];
    }
}