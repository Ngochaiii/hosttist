<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Config;
use App\Models\Customers;
use App\Mail\DepositRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\deposits;

class WalletController extends Controller
{
    /**
     * Hiển thị trang nạp tiền
     */
    public function deposit(Request $request)
    {
        $locale = $this->getLocale($request);
        $config = Config::current();
        $customer = $this->getOrCreateCustomer();

        $data = [
            'config' => $config,
            'customer' => $customer,
            'depositAmounts' => $this->getDepositAmounts($locale),
            'minDeposit' => 100000, // Hard code vì chưa có field
            'maxDeposit' => 100000000,
            'locale' => $locale,
            'paymentMethods' => $this->getPaymentMethods($locale),
            'currency' => $this->getCurrency($locale),
            'usdRate' => 24000
        ];

        return view('source.web.wallet.deposit', $data);
    }

    /**
     * Xử lý yêu cầu nạp tiền
     */
    public function processDeposit(Request $request)
    {
        $this->validateDepositRequest($request);
        
        $locale = $this->getLocale($request);
        $customer = $this->getOrCreateCustomer();
        $config = Config::current();

        // Tính toán số tiền
        $amounts = $this->calculateAmounts($request->amount, $request->payment_method);
        
        // Tạo mã giao dịch
        $transactionCode = $this->generateTransactionCode();
        
        // Lấy thông tin thanh toán - sử dụng fields có sẵn hoặc hard code
        $paymentInfo = $this->getPaymentInfo($config, $request->payment_method, $customer);
        
        // Tạo deposit data đầy đủ cho session và email
        $depositData = $this->createDepositData($transactionCode, $amounts, $request, $customer, $paymentInfo, $locale, $config);
        
        // Lưu vào database
        $this->saveDeposit($transactionCode, $amounts, $request, $customer->id, $paymentInfo);
        
        // Gửi email
        $this->sendDepositEmail($depositData, Auth::user()->email);
        
        // Lưu session
        session(['deposit_data' => $depositData]);

        return redirect()->route('deposit.success', ['code' => $transactionCode]);
    }

    /**
     * Trang thành công
     */
    public function depositSuccess(Request $request)
    {
        $code = $request->code;
        
        if (!session()->has('deposit_data')) {
            return redirect()->route('deposit')->with('error', 'Session đã hết hạn');
        }

        $depositData = session('deposit_data');
        
        if ($depositData['transaction_code'] !== $code) {
            return redirect()->route('deposit')->with('error', 'Mã giao dịch không hợp lệ');
        }

        return view('source.web.wallet.deposit_success', compact('depositData'));
    }

    /**
     * Kiểm tra trạng thái giao dịch
     */
    public function checkDepositStatus(Request $request, $code)
    {
        $customer = Auth::user()->customer;
        $deposit = deposits::where('transaction_code', $code)
            ->where('customer_id', $customer->id)
            ->first();

        if (!$deposit) {
            return response()->json([
                'success' => false, 
                'message' => 'Không tìm thấy giao dịch'
            ], 404);
        }

        $statusInfo = $this->getStatusInfo($deposit);

        return response()->json([
            'success' => true,
            'data' => $statusInfo
        ]);
    }

    // ===== PRIVATE HELPER METHODS =====

    private function getLocale(Request $request): string
    {
        $locale = $request->get('lang', session('locale', 'vi'));
        session(['locale' => $locale]);
        return $locale;
    }

    private function getOrCreateCustomer(): Customers
    {
        $user = Auth::user();
        $customer = $user->customer;

        if (!$customer) {
            $customer = Customers::create([
                'user_id' => $user->id,
                'company_name' => $user->name,
                'status' => 'active',
                'source' => 'website',
                'balance' => 0
            ]);
            $user->refresh();
        }

        return $customer;
    }

    private function getDepositAmounts(string $locale): array
    {
        return $locale === 'vi' ? [
            5 => 5000000,    // 5 triệu
            10 => 10000000,  // 10 triệu
            15 => 15000000,  // 15 triệu
        ] : [
            100 => 100,      // $100
            500 => 500,      // $500
            1000 => 1000,    // $1000
        ];
    }

    private function getPaymentMethods(string $locale): array
    {
        $methods = [];

        if ($locale === 'vi') {
            $methods['bank'] = ['name' => 'Chuyển khoản ngân hàng', 'icon' => 'fas fa-university'];
            $methods['momo'] = ['name' => 'Ví MoMo', 'icon' => 'fas fa-wallet'];
            $methods['zalopay'] = ['name' => 'ZaloPay', 'icon' => 'fas fa-wallet'];
        }

        // International methods
        $methods['paypal'] = ['name' => 'PayPal', 'icon' => 'fab fa-paypal'];
        $methods['crypto'] = ['name' => $locale === 'vi' ? 'Tiền điện tử' : 'Cryptocurrency', 'icon' => 'fab fa-bitcoin'];

        return $methods;
    }

    private function getCurrency(string $locale): array
    {
        return $locale === 'vi' 
            ? ['code' => 'VND', 'symbol' => 'đ'] 
            : ['code' => 'USD', 'symbol' => '$'];
    }

    private function validateDepositRequest(Request $request): void
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:bank,momo,zalopay,paypal,crypto',
            'agree_terms' => 'required|accepted'
        ]);
    }

    private function calculateAmounts(float $originalAmount, string $paymentMethod): array
    {
        $usdRate = 24000;
        
        // Convert to VND if needed
        $amountVND = in_array($paymentMethod, ['paypal', 'crypto']) 
            ? $originalAmount * $usdRate 
            : $originalAmount;

        // Calculate bonus (5% for amounts >= 10M VND)
        $bonusAmount = $amountVND >= 10000000 ? round($amountVND * 0.05) : 0;
        $finalAmount = $amountVND + $bonusAmount;

        return [
            'original' => $originalAmount,
            'vnd' => $amountVND,
            'bonus' => $bonusAmount,
            'bonus_percent' => $bonusAmount > 0 ? 5 : 0,
            'final' => $finalAmount,
            'exchange_rate' => $usdRate
        ];
    }

    private function generateTransactionCode(): string
    {
        return 'DEP' . time() . Str::random(5);
    }

    /**
     * Lấy payment info - sử dụng fields có sẵn hoặc hard code
     */
    private function getPaymentInfo(Config $config, string $paymentMethod, Customers $customer): array
    {
        $baseInfo = [
            'payment_type' => $paymentMethod,
            'customer_id' => $customer->id
        ];

        return match($paymentMethod) {
            'bank' => array_merge($baseInfo, [
                // Sử dụng fields có sẵn trong config hoặc hard code
                'bank_name' => $config->company_bank_name ?? 'Ngân hàng Tiền Phong',
                'account_number' => $config->company_bank_account_number ?? '69692648888',
                'account_name' => $config->company_bank_account_name ?? 'NGUYEN VAN THIEN',
                'branch' => $config->company_bank_branch ?? 'Cầu Giấy',
            ]),
            'momo' => array_merge($baseInfo, [
                'phone' => $config->momo_phone_number ?? '0123456789',
                'account_name' => $config->momo_account_name ?? 'NGUYEN VAN THIEN',
            ]),
            'zalopay' => array_merge($baseInfo, [
                'phone' => $config->zalopay_phone_number ?? '0123456789',
                'account_name' => $config->zalopay_account_name ?? 'NGUYEN VAN THIEN',
            ]),
            'paypal' => array_merge($baseInfo, [
                'currency' => 'USD',
                'paypal_email' => $config->paypal_email ?? 'your-paypal@email.com',
            ]),
            'crypto' => array_merge($baseInfo, [
                'crypto_type' => 'bitcoin',
                'wallet_address' => $config->crypto_wallet_address ?? '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
                'network' => 'Bitcoin Network'
            ]),
            default => $baseInfo
        };
    }

    /**
     * Tạo deposit data đầy đủ cho session và email
     */
    private function createDepositData(string $transactionCode, array $amounts, Request $request, Customers $customer, array $paymentInfo, string $locale, Config $config): array
    {
        return [
            'transaction_code' => $transactionCode,
            'amount' => $amounts['original'],
            'amount_vnd' => $amounts['vnd'],
            'final_amount' => $amounts['final'],
            'bonus_amount' => $amounts['bonus'],
            'bonus_percent' => $amounts['bonus_percent'],
            'currency' => $locale === 'vi' ? 'VND' : 'USD',
            'exchange_rate' => $amounts['exchange_rate'],
            'payment_method' => $request->payment_method,
            'payment_info' => $paymentInfo,
            'customer_name' => Auth::user()->name,
            'customer_email' => Auth::user()->email,
            'customer_id' => $customer->id,
            'date' => now()->format('d/m/Y H:i:s'),
            'note_format' => "DEP{$customer->id}",
            'locale' => $locale,
            'config' => $config // Truyền config để component access QR
        ];
    }

    /**
     * Lưu vào database - chỉ dùng columns có sẵn
     */
    private function saveDeposit(string $transactionCode, array $amounts, Request $request, int $customerId, array $paymentInfo): void
    {
        $paymentDetails = array_merge($paymentInfo, [
            'original_amount' => $amounts['original'],
            'bonus_amount' => $amounts['bonus'],
            'bonus_percent' => $amounts['bonus_percent'],
            'exchange_rate' => $amounts['exchange_rate'],
            'locale' => session('locale', 'vi'),
            'expires_at' => now()->addMinutes(30)->toDateTimeString()
        ]);

        deposits::create([
            'transaction_code' => $transactionCode,
            'customer_id' => $customerId,
            'amount' => $amounts['final'],
            'payment_method' => $request->payment_method,
            'note' => "DEP{$customerId}",
            'status' => 'pending',
            'payment_details' => $paymentDetails,
        ]);
    }

    private function sendDepositEmail(array $depositData, string $email): void
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($email)->send(new DepositRequest($depositData));
            } catch (\Exception $e) {
                Log::error('Email sending failed: ' . $e->getMessage());
            }
        }
    }

    private function getStatusInfo(deposits $deposit): array
    {
        $paymentDetails = $deposit->payment_details;
        $expiresAt = isset($paymentDetails['expires_at']) 
            ? \Carbon\Carbon::parse($paymentDetails['expires_at']) 
            : $deposit->created_at->addMinutes(30);
        
        $isExpired = $expiresAt->isPast() && $deposit->status === 'pending';
        
        $statusColors = [
            'pending' => 'warning',
            'approved' => 'success', 
            'rejected' => 'danger'
        ];

        $statusTexts = [
            'pending' => 'Chờ thanh toán',
            'approved' => 'Đã duyệt',
            'rejected' => 'Bị từ chối'
        ];

        return [
            'status' => $deposit->status,
            'status_color' => $statusColors[$deposit->status] ?? 'secondary',
            'status_text' => $statusTexts[$deposit->status] ?? 'Không xác định',
            'is_expired' => $isExpired
        ];
    }

    /**
     * Switch language
     */
    public function switchLanguage(Request $request)
    {
        $locale = $request->get('locale', 'vi');
        session(['locale' => $locale]);
        return redirect()->back();
    }
}