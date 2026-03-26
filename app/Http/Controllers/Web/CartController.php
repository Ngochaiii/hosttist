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

            $rules = [
                'product_id' => 'required|exists:products,id',
                'quantity'   => 'required|integer|min:1',
            ];

            if ($product->category && $product->category->hasServiceFields()) {
                foreach ($product->category->getServiceFields() as $field) {
                    if ($field['required'] ?? false) {
                        $fieldKey = "options.{$field['name']}";
                        $rules[$fieldKey] = 'required';

                        if (isset($field['validation'])) {
                            switch ($field['validation']) {
                                case 'domain':
                                    $rules[$fieldKey] .= '|regex:/^[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9](\.[a-zA-Z]{2,})+$/';
                                    break;
                                case 'phone_vn':
                                    $rules[$fieldKey] .= '|regex:/^(0[3|5|7|8|9])+([0-9]{8})$/';
                                    break;
                                case 'url':
                                    $rules[$fieldKey] .= '|url';
                                    break;
                            }
                        }
                    }
                }
            }

            $request->validate($rules);

            $cart = $this->getCart();

            $options = $request->options ?? [];
            $options['service_type'] = $product->category->getServiceType();

            $period = $options['period'] ?? 1;
            $price  = ($product->sale_price ?? $product->price) * $period;

            $existingItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();

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
        if (Auth::check()) {
            return Cart::firstOrCreate(
                ['user_id' => Auth::id()],
                ['expires_at' => now()->addDays(7), 'subtotal' => 0, 'total_amount' => 0]
            );
        }

        return Cart::firstOrCreate(
            ['session_id' => session()->getId()],
            ['expires_at' => now()->addDays(7), 'subtotal' => 0, 'total_amount' => 0]
        );
    }

    private function checkCartAccess(Cart $cart): bool
    {
        if (Auth::check()) {
            return $cart->user_id == Auth::id();
        }

        return $cart->session_id == session()->getId();
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
