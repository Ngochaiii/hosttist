<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    public function index()
    {
        $cart = $this->getCart();

        return view('source.web.cart.index', compact('cart'));
    }

    public function addToCart(Request $request)
    {
        try {
            $product = Products::with('category')->findOrFail($request->product_id);

            if (($product->product_status ?? 'active') !== 'active') {
                return back()->with('error', 'Sản phẩm hiện không khả dụng');
            }

            $rules = [
                'product_id'     => 'required|exists:products,id',
                'quantity'       => 'required|integer|min:1|max:100',
                'options.period' => 'nullable|integer|min:1|max:10',
            ];

            // Messages/attributes tiếng Việt để lỗi hiện đúng tên field ("Tên miền là bắt buộc")
            // thay vì "The options.domain field is required".
            $messages = [];
            $attributes = [];
            if ($product->category && $product->category->hasServiceFields()) {
                foreach ($product->category->getServiceFields() as $field) {
                    if ($field['required'] ?? false) {
                        $fieldKey = "options.{$field['name']}";
                        $rules[$fieldKey] = 'required';
                        $label = $field['label'] ?? $field['name'];
                        $attributes[$fieldKey] = $label;
                        $messages["{$fieldKey}.required"] = "Vui lòng nhập {$label}.";

                        if (isset($field['validation'])) {
                            switch ($field['validation']) {
                                case 'domain':
                                    $rules[$fieldKey] .= '|regex:/^[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9](\.[a-zA-Z]{2,})+$/';
                                    $messages["{$fieldKey}.regex"] = "{$label} không hợp lệ (vd: example.com).";
                                    break;
                                case 'phone_vn':
                                    $rules[$fieldKey] .= '|regex:/^(0[3|5|7|8|9])+([0-9]{8})$/';
                                    $messages["{$fieldKey}.regex"] = "{$label} không đúng định dạng số điện thoại.";
                                    break;
                                case 'url':
                                    $rules[$fieldKey] .= '|url';
                                    break;
                            }
                        }
                    }
                }
            }

            $request->validate($rules, $messages, $attributes);

            $cart = $this->getCart();

            $options = $request->options ?? [];
            $options['service_type'] = $product->category->getServiceType();

            $period = (int) ($options['period'] ?? 1);
            $basePrice = ($product->sale_price > 0) ? $product->sale_price : $product->price;

            // Chặn add-to-cart cho sản phẩm chưa cấu hình giá (price = 0/null) — admin có thể
            // tạo product nháp với price=0 rồi quên set, không nên cho khách checkout 0đ.
            if (!$basePrice || $basePrice <= 0) {
                Log::warning('Add to cart blocked: product has invalid price', [
                    'product_id' => $product->id,
                    'price'      => $product->price,
                    'sale_price' => $product->sale_price,
                ]);
                return back()->with('error', 'Sản phẩm chưa được cấu hình giá. Vui lòng liên hệ quản trị viên.');
            }

            $price  = $basePrice * $period;

            // Matching key = product_id + period + domain (nếu có).
            // Tránh ghi đè options khi cùng product nhưng khác domain/period.
            $domainKey = $options['domain'] ?? null;

            $existingItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->get()
                ->first(function (CartItem $item) use ($period, $domainKey) {
                    $itemOpts = json_decode($item->options, true) ?: [];
                    $itemPeriod = (int) ($itemOpts['period'] ?? 1);
                    $itemDomain = $itemOpts['domain'] ?? null;
                    return $itemPeriod === $period && $itemDomain === $domainKey;
                });

            if ($existingItem) {
                $newQty = $existingItem->quantity + $request->quantity;
                $existingItem->update([
                    'quantity' => $newQty,
                    'options'  => json_encode($options),
                    'subtotal' => $price * $newQty,
                    'total'    => $price * $newQty,
                ]);
            } else {
                CartItem::create([
                    'cart_id'    => $cart->id,
                    'product_id' => $product->id,
                    'name'       => $product->name . " ({$period} năm)",
                    'quantity'   => $request->quantity,
                    'unit_price' => $price,
                    'subtotal'   => $price * $request->quantity,
                    'total'      => $price * $request->quantity,
                    'options'    => json_encode($options),
                ]);
            }

            $this->updateCartTotals($cart);

            return redirect()->route('cart.index')->with('success', 'Đã thêm vào giỏ hàng');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Để lỗi validation bong lên: Laravel tự trả về withErrors + old input
            // (vd "Vui lòng nhập tên miền") thay vì thông báo chung vô nghĩa.
            throw $e;
        } catch (\Exception $e) {
            Log::error('Add to cart error: ' . $e->getMessage());
            return back()->with('error', 'Không thể thêm vào giỏ hàng. Vui lòng thử lại.');
        }
    }

    public function updateItem(Request $request, $itemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $cartItem = CartItem::findOrFail($itemId);
        $cart     = $cartItem->cart;

        if (!$this->checkCartAccess($cart)) {
            return back()->with('error', 'Bạn không có quyền truy cập vào giỏ hàng này');
        }

        $cartItem->quantity = $request->quantity;
        $cartItem->subtotal = $cartItem->unit_price * $cartItem->quantity;
        $cartItem->total    = $cartItem->subtotal;
        $cartItem->save();

        $this->updateCartTotals($cart);

        return back()->with('success', 'Đã cập nhật giỏ hàng');
    }

    public function removeItem($itemId)
    {
        $cartItem = CartItem::findOrFail($itemId);
        $cart     = $cartItem->cart;

        if (!$this->checkCartAccess($cart)) {
            return back()->with('error', 'Bạn không có quyền truy cập vào giỏ hàng này');
        }

        $cartItem->delete();
        $this->updateCartTotals($cart);

        return back()->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng');
    }

    public function clearCart()
    {
        $cart = $this->getCart();

        if (!$this->checkCartAccess($cart)) {
            return back()->with('error', 'Bạn không có quyền truy cập vào giỏ hàng này');
        }

        CartItem::where('cart_id', $cart->id)->delete();

        $cart->subtotal        = 0;
        $cart->tax_amount      = 0;
        $cart->discount_amount = 0;
        $cart->total_amount    = 0;
        $cart->save();

        session(['cart_count' => 0]);

        return back()->with('success', 'Đã xóa tất cả sản phẩm trong giỏ hàng');
    }

    private function getCart(): Cart
    {
        // Tất cả route cart nằm sau middleware frontend.auth, không có guest.
        return Cart::firstOrCreate(
            ['user_id' => Auth::id()],
            ['expires_at' => now()->addDays(7), 'subtotal' => 0, 'total_amount' => 0]
        );
    }

    private function checkCartAccess(Cart $cart): bool
    {
        return $cart->user_id == Auth::id();
    }

    private function updateCartTotals(Cart $cart): void
    {
        $items = CartItem::where('cart_id', $cart->id)->get();

        $subtotal       = $items->sum('subtotal');
        $taxAmount      = $items->sum('tax_amount');
        $discountAmount = $items->sum('discount_amount');

        $cart->subtotal        = $subtotal;
        $cart->tax_amount      = $taxAmount;
        $cart->discount_amount = $discountAmount;
        $cart->total_amount    = $subtotal + $taxAmount - $discountAmount;
        $cart->save();

        session(['cart_count' => $items->sum('quantity')]);
    }
}
