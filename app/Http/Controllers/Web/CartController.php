<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Helpers\PricingHelper;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    /**
     * Hiển thị giỏ hàng
     */
    public function index()
    {
        $cart = $this->getCart();

        return view('source.web.cart.index', compact('cart'));
    }

    /**
     * Thêm sản phẩm vào giỏ hàng
     */
    // app/Http/Controllers/Web/CartController.php

    public function addToCart(Request $request)
    {
        try {
            $product = Products::with('category')->findOrFail($request->product_id);

            // Validation cơ bản
            $rules = [
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
            ];

            // Thêm validation cho dynamic fields từ category
            if ($product->category && $product->category->hasServiceFields()) {
                foreach ($product->category->getServiceFields() as $field) {
                    if ($field['required'] ?? false) {
                        $fieldKey = "options.{$field['name']}";
                        $rules[$fieldKey] = 'required';

                        // Thêm validation cụ thể theo loại
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

            $validated = $request->validate($rules);

            // Lấy giỏ hàng
            $cart = $this->getCart();

            // Chuẩn bị options với đầy đủ thông tin
            $options = $request->options ?? [];
            $options['service_type'] = $product->category->getServiceType();

            // Tính giá
            $period = $options['period'] ?? 1;
            $price = ($product->sale_price ?? $product->price) * $period;

            // Tìm hoặc tạo cart item
            $existingItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();

            if ($existingItem) {
                $existingItem->update([
                    'quantity' => $existingItem->quantity + $request->quantity,
                    'options' => json_encode($options),
                    'subtotal' => $price * ($existingItem->quantity + $request->quantity),
                    'total' => $price * ($existingItem->quantity + $request->quantity),
                ]);
            } else {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'name' => $product->name . " ({$period} năm)",
                    'quantity' => $request->quantity,
                    'unit_price' => $price,
                    'subtotal' => $price * $request->quantity,
                    'total' => $price * $request->quantity,
                    'options' => json_encode($options)
                ]);
            }

            $this->updateCartTotals($cart);

            return redirect()->route('cart.index')
                ->with('success', 'Đã thêm vào giỏ hàng');
        } catch (\Exception $e) {
            Log::error('Add to cart error: ' . $e->getMessage());
            return back()->with('error', 'Không thể thêm vào giỏ hàng. Vui lòng thử lại.');
        }
    }

    /**
     * Xử lý trường hợp trùng lặp mục trong giỏ hàng
     */
    private function handleDuplicateCartItem(Request $request, $product)
    {
        try {
            $cart = $this->getCart();
            $options = $request->options ?? [];
            $period = $options['period'] ?? 1;

            // Tìm mục hiện có
            $existingItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();

            if ($existingItem) {
                // Xóa mục hiện có
                $existingItem->delete();

                // Thêm lại với thông tin mới
                return $this->addToCart($request);
            }

            return back()->with('error', 'Không thể thêm sản phẩm vào giỏ hàng. Vui lòng thử lại.');
        } catch (\Exception $e) {
            Log::error('Error handling duplicate cart item: ' . $e->getMessage());
            return back()->with('error', 'Không thể thêm sản phẩm vào giỏ hàng. Vui lòng thử lại sau.');
        }
    }

    /**
     * Lấy giỏ hàng hiện tại hoặc tạo mới
     */
    private function getCart()
    {
        if (Auth::check()) {
            // Nếu đã đăng nhập, lấy giỏ hàng theo user_id
            $cart = Cart::firstOrCreate(
                ['user_id' => Auth::id()],
                [
                    'expires_at' => now()->addDays(7),
                    'subtotal' => 0,
                    'total_amount' => 0
                ]
            );
        } else {
            // Nếu chưa đăng nhập, lấy giỏ hàng theo session_id
            $sessionId = session()->getId();
            $cart = Cart::firstOrCreate(
                ['session_id' => $sessionId],
                [
                    'expires_at' => now()->addDays(7),
                    'subtotal' => 0,
                    'total_amount' => 0
                ]
            );
        }

        return $cart;
    }

    /**
     * Cập nhật tổng giỏ hàng
     */
    private function updateCartTotals(Cart $cart)
    {
        $items = CartItem::where('cart_id', $cart->id)->get();

        $subtotal = $items->sum('subtotal');
        $taxAmount = $items->sum('tax_amount');
        $discountAmount = $items->sum('discount_amount');
        $totalAmount = $subtotal + $taxAmount - $discountAmount;

        $cart->subtotal = $subtotal;
        $cart->tax_amount = $taxAmount ?? 0;
        $cart->discount_amount = $discountAmount ?? 0;
        $cart->total_amount = $totalAmount;
        $cart->save();

        // Cập nhật số lượng sản phẩm trong giỏ hàng vào session
        session(['cart_count' => $items->sum('quantity')]);

        return $cart;
    }
    /**
     * Cập nhật số lượng sản phẩm trong giỏ hàng
     */
    public function updateItem(Request $request, $itemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $cartItem = CartItem::findOrFail($itemId);
        $cart = $cartItem->cart;

        // Kiểm tra quyền truy cập
        if (!$this->checkCartAccess($cart)) {
            return back()->with('error', 'Bạn không có quyền truy cập vào giỏ hàng này');
        }

        // Cập nhật số lượng
        $cartItem->quantity = $request->quantity;
        $cartItem->subtotal = $cartItem->unit_price * $cartItem->quantity;
        $cartItem->total = $cartItem->subtotal;
        $cartItem->save();

        // Cập nhật tổng giỏ hàng
        $this->updateCartTotals($cart);

        return back()->with('success', 'Đã cập nhật giỏ hàng');
    }

    /**
     * Xóa sản phẩm khỏi giỏ hàng
     */
    public function removeItem(Request $request, $itemId)
    {
        $cartItem = CartItem::findOrFail($itemId);
        $cart = $cartItem->cart;

        // Kiểm tra quyền truy cập
        if (!$this->checkCartAccess($cart)) {
            return back()->with('error', 'Bạn không có quyền truy cập vào giỏ hàng này');
        }

        // Xóa sản phẩm
        $cartItem->delete();

        // Cập nhật tổng giỏ hàng
        $this->updateCartTotals($cart);

        return back()->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng');
    }

    /**
     * Xóa tất cả sản phẩm trong giỏ hàng
     */
    public function clearCart(Request $request)
    {
        $cart = $this->getCart();

        // Kiểm tra quyền truy cập
        if (!$this->checkCartAccess($cart)) {
            return back()->with('error', 'Bạn không có quyền truy cập vào giỏ hàng này');
        }

        // Xóa tất cả sản phẩm
        CartItem::where('cart_id', $cart->id)->delete();

        // Cập nhật tổng giỏ hàng
        $cart->subtotal = 0;
        $cart->tax_amount = 0;
        $cart->discount_amount = 0;
        $cart->total_amount = 0;
        $cart->save();

        // Cập nhật session
        session(['cart_count' => 0]);

        return back()->with('success', 'Đã xóa tất cả sản phẩm trong giỏ hàng');
    }

    /**
     * Kiểm tra quyền truy cập vào giỏ hàng
     */
    private function checkCartAccess(Cart $cart)
    {
        if (Auth::check()) {
            // Nếu đã đăng nhập, kiểm tra user_id
            return $cart->user_id == Auth::id();
        } else {
            // Nếu chưa đăng nhập, kiểm tra session_id
            return $cart->session_id == session()->getId();
        }
    }
}
