<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DomainTld;
use App\Services\DomainAvailabilityService;
use App\Services\DomainCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Khách đặt mua tên miền: tra cứu giá, kiểm tra khả dụng, thêm vào giỏ.
 * Tái dùng engine cart/order/payment sẵn có (không đụng code thanh toán).
 */
class DomainController extends Controller
{
    public function __construct(
        private DomainCatalogService $catalog,
        private DomainAvailabilityService $availability
    ) {}

    /** Trang tra cứu/đăng ký tên miền (công khai). */
    public function search()
    {
        $tlds = DomainTld::active()->orderBy('sort_order')->orderBy('tld')->get();

        return view('source.web.domains.search', compact('tlds'));
    }

    /** AJAX công khai: kiểm tra khả dụng (throttle ở route). */
    public function check(Request $request): JsonResponse
    {
        $request->validate(['domain' => ['required', 'string', 'max:255']]);

        return response()->json($this->availability->check($request->domain));
    }

    /** Thêm domain vào giỏ (yêu cầu đăng nhập). */
    public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'domain_name'    => ['required', 'string', 'max:255'],
            'tld_id'         => ['required', 'exists:domain_tlds,id'],
            'years'          => ['required', 'integer', 'min:1', 'max:10'],
            'dns_management' => ['nullable'],
            'reg_name'       => ['nullable', 'string', 'max:255'],
            'reg_email'      => ['nullable', 'email', 'max:255'],
            'reg_phone'      => ['nullable', 'string', 'max:30'],
            'reg_address'    => ['nullable', 'string', 'max:500'],
            'reg_id_type'    => ['nullable', 'in:cccd,passport,tax_code'],
            'reg_id_number'  => ['nullable', 'string', 'max:50'],
        ]);

        $tld    = DomainTld::active()->findOrFail($validated['tld_id']);
        $domain = strtolower(trim($validated['domain_name']));

        // Tên miền phải khớp đuôi đã chọn.
        if (!str_ends_with($domain, '.' . $tld->tld)) {
            return back()->withInput()->with('error', "Tên miền phải kết thúc bằng .{$tld->tld}");
        }

        // .vn bắt buộc thông tin chủ thể (tên + giấy tờ).
        if ($tld->is_vn && (empty($validated['reg_name']) || empty($validated['reg_id_number']))) {
            return back()->withInput()->with('error', 'Đuôi .vn cần Họ tên và Số giấy tờ (CCCD/MST) của chủ thể.');
        }

        // Chặn đặt domain đã có người đăng ký (chỉ với đuôi tra cứu được).
        if (!$tld->is_vn) {
            $result = $this->availability->check($domain);
            if ($result['status'] === DomainAvailabilityService::TAKEN) {
                return back()->withInput()->with('error', "Tên miền {$domain} đã được đăng ký.");
            }
            if ($result['status'] === DomainAvailabilityService::INVALID) {
                return back()->withInput()->with('error', 'Tên miền không hợp lệ.');
            }
        }

        $registrant = array_filter([
            'name'      => $validated['reg_name'] ?? null,
            'email'     => $validated['reg_email'] ?? null,
            'phone'     => $validated['reg_phone'] ?? null,
            'address'   => $validated['reg_address'] ?? null,
            'id_type'   => $validated['reg_id_type'] ?? null,
            'id_number' => $validated['reg_id_number'] ?? null,
        ], fn($v) => $v !== null && $v !== '');

        $cart = Cart::firstOrCreate(
            ['user_id' => Auth::id()],
            ['expires_at' => now()->addDays(7), 'subtotal' => 0, 'total_amount' => 0]
        );

        // Không cho thêm trùng tên miền trong giỏ.
        $dup = CartItem::where('cart_id', $cart->id)->get()->first(function (CartItem $item) use ($domain) {
            $opt = json_decode($item->options, true) ?: [];
            return ($opt['domain'] ?? null) === $domain;
        });
        if ($dup) {
            return back()->with('error', "Tên miền {$domain} đã có trong giỏ hàng.");
        }

        $line = $this->catalog->buildCartLine(
            $tld,
            $domain,
            (int) $validated['years'],
            $registrant,
            $request->boolean('dns_management', true)
        );

        CartItem::create([
            'cart_id'    => $cart->id,
            'product_id' => $line['product_id'],
            'name'       => $line['name'],
            'quantity'   => 1,
            'unit_price' => $line['unit_price'],
            'subtotal'   => $line['unit_price'],
            'total'      => $line['unit_price'],
            'options'    => json_encode($line['options']),
        ]);

        $this->refreshCartTotals($cart);

        return redirect()->route('cart.index')->with('success', "Đã thêm tên miền {$domain} vào giỏ hàng.");
    }

    /** Cập nhật tổng giỏ + cart_count (mirror CartController::updateCartTotals). */
    private function refreshCartTotals(Cart $cart): void
    {
        $items = CartItem::where('cart_id', $cart->id)->get();

        $cart->subtotal        = $items->sum('subtotal');
        $cart->tax_amount      = $items->sum('tax_amount');
        $cart->discount_amount = $items->sum('discount_amount');
        $cart->total_amount    = $cart->subtotal + $cart->tax_amount - $cart->discount_amount;
        $cart->save();

        session(['cart_count' => $items->sum('quantity')]);
    }
}
