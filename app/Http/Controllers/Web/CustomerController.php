<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Customers;
use App\Models\Orders;
use App\Models\Invoices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function showProfile()
    {
        $user = Auth::user();
        $customer = $user->customer;

        return view('source.web.profile.profile', compact('user', 'customer'));
    }

    public function updateProfile(Request $request)
    {
        // Code hiện tại của bạn giữ nguyên
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'tax_code' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
        ]);

        // Cập nhật thông tin user
        $user->name = $validated['name'];
        $user->phone = $validated['phone'];
        $user->address = $validated['address'];
        $user->save();

        // Cập nhật thông tin customer
        $customer = $user->customer;
        if (!$customer) {
            // Tạo mới nếu chưa có
            $customer = new Customers();
            $customer->user_id = $user->id;
            $customer->company_name = $validated['company_name'] ?? $validated['name'];
            $customer->tax_code = $validated['tax_code'] ?? null;
            $customer->website = $validated['website'] ?? null;
            $customer->status = 'active';
            $customer->source = 'website';
            $customer->save();
        } else {
            // Cập nhật nếu đã có
            $customer->company_name = $validated['company_name'] ?? $validated['name'];
            $customer->tax_code = $validated['tax_code'] ?? null;
            $customer->website = $validated['website'] ?? null;
            $customer->save();
        }

        // Kiểm tra có URL intended không
        if (session()->has('url.intended')) {
            $intended = session('url.intended');
            session()->forget('url.intended');
            return redirect($intended)->with('success', 'Thông tin đã được cập nhật thành công!');
        }

        // Chuyển hướng về trang profile
        return redirect()->route('customer.profile')->with('success', 'Thông tin đã được cập nhật thành công!');
    }

    /**
     * Hiển thị danh sách hóa đơn chưa thanh toán
     */
    public function showInvoices()
    {
        $user = Auth::user();
        $customer = $user->customer;

        if (!$customer) {
            return redirect()->route('customer.profile')->with('error', 'Vui lòng cập nhật thông tin khách hàng trước.');
        }

        // Lấy các hóa đơn chưa thanh toán
        $invoices = Invoices::whereHas('order', function ($query) use ($customer) {
            $query->where('customer_id', $customer->id)
                ->where('status', 'pending'); // Hóa đơn của đơn hàng đang chờ thanh toán
        })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('source.web.profile.invoices', compact('user', 'customer', 'invoices'));
    }

    /**
     * Hiển thị lịch sử đơn hàng đã thanh toán
     */
    public function showOrders()
    {
        $user = Auth::user();
        $customer = $user->customer;

        if (!$customer) {
            return redirect()->route('customer.profile')->with('error', 'Vui lòng cập nhật thông tin khách hàng trước.');
        }

        // Lấy các đơn hàng đã hoàn thành hoặc đang xử lý
        $orders = Orders::with(['items.product']) // Eager load items and products
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['completed', 'processing']) // Đơn hàng đã xử lý hoặc hoàn thành
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Xử lý thông tin domain và tính ngày hết hạn cho mỗi đơn hàng
        foreach ($orders as $order) {
            // Tìm các domain trong order_items
            $domains = [];

            // Mặc định lấy ngày hết hạn dựa trên ngày tạo đơn hàng + 1 năm (nếu không tìm thấy giá trị khác)
            $orderDate = $order->completed_at ?? $order->created_at;
            $expirationDate = \Carbon\Carbon::parse($orderDate)->addYear();

            // Debug log
            \Illuminate\Support\Facades\Log::info("Processing order #{$order->order_number}");

            foreach ($order->items as $item) {
                // Lấy thông tin domain từ nhiều nguồn
                $domain = null;

                // 1. Kiểm tra trong column domain
                if (!empty($item->domain)) {
                    $domain = $item->domain;
                } else {
                    // 2. Kiểm tra trong options
                    $options = json_decode($item->options, true) ?: [];
                    if (!empty($options['domain'])) {
                        $domain = $options['domain'];
                    } elseif ($item->product_id) {
                        // 3. Kiểm tra trong meta_data của sản phẩm
                        $product = $item->product;
                        if ($product) {
                            // Kiểm tra kiểu dữ liệu của meta_data
                            $metaData = [];
                            if (is_string($product->meta_data)) {
                                $metaData = json_decode($product->meta_data, true) ?: [];
                            } else if (is_array($product->meta_data)) {
                                $metaData = $product->meta_data;
                            }

                            $domain = $metaData['domain'] ?? null;
                        }
                    }
                }

                // Nếu có domain và là SSL hoặc domain, thêm vào danh sách
                if ($domain && $item->product && ($item->product->type == 'ssl' || $item->product->type == 'domain')) {
                    $domains[] = $domain;
                }

                // Tính ngày hết hạn dựa trên thông tin sản phẩm
                $durationYears = null;

                // Log product type
                \Illuminate\Support\Facades\Log::info("Item #{$item->id} - Product Type: " . ($item->product ? $item->product->type : 'No product'));

                // Try multiple sources for duration
                if ($item->product) {
                    // 1. Check directly on the item
                    if (!is_null($item->duration)) {
                        $durationYears = (int)$item->duration;
                        \Illuminate\Support\Facades\Log::info("Found duration in item: $durationYears");
                    }
                    // 2. Check product options
                    elseif (!empty($options['duration_years'])) {
                        $durationYears = (int)$options['duration_years'];
                        \Illuminate\Support\Facades\Log::info("Found duration in options: $durationYears");
                    } elseif (!empty($options['duration'])) {
                        $durationYears = (int)$options['duration'];
                        \Illuminate\Support\Facades\Log::info("Found duration in options: $durationYears");
                    }
                    // 3. Check in meta_data
                    elseif (isset($metaData['duration_years'])) {
                        $durationYears = (int)$metaData['duration_years'];
                        \Illuminate\Support\Facades\Log::info("Found duration in meta_data: $durationYears");
                    } elseif (isset($metaData['duration'])) {
                        $durationYears = (int)$metaData['duration'];
                        \Illuminate\Support\Facades\Log::info("Found duration in meta_data: $durationYears");
                    }
                    // 4. Use quantity as years for domains and SSL
                    elseif (in_array($item->product->type, ['ssl', 'domain', 'hosting'])) {
                        $durationYears = (int)$item->quantity;
                        \Illuminate\Support\Facades\Log::info("Using quantity as duration: $durationYears");
                    }
                    // 5. Check for expiry_date directly in options or meta
                    elseif (!empty($options['expiry_date'])) {
                        try {
                            $itemExpirationDate = \Carbon\Carbon::parse($options['expiry_date']);
                            if ($itemExpirationDate->gt($expirationDate)) {
                                $expirationDate = $itemExpirationDate;
                                \Illuminate\Support\Facades\Log::info("Found expiry_date in options: " . $expirationDate->format('Y-m-d'));
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Error parsing expiry_date: " . $e->getMessage());
                        }
                    } elseif (isset($metaData['expiry_date'])) {
                        try {
                            $itemExpirationDate = \Carbon\Carbon::parse($metaData['expiry_date']);
                            if ($itemExpirationDate->gt($expirationDate)) {
                                $expirationDate = $itemExpirationDate;
                                \Illuminate\Support\Facades\Log::info("Found expiry_date in meta_data: " . $expirationDate->format('Y-m-d'));
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Error parsing expiry_date: " . $e->getMessage());
                        }
                    }

                    // Calculate expiration date if we found a duration
                    if ($durationYears) {
                        try {
                            $startDate = $order->completed_at ?? $order->created_at;
                            $itemExpirationDate = \Carbon\Carbon::parse($startDate)->addYears($durationYears);

                            \Illuminate\Support\Facades\Log::info("Calculated expiration: " . $itemExpirationDate->format('Y-m-d'));

                            // We want to use the furthest expiration date for the order
                            if ($itemExpirationDate->gt($expirationDate)) {
                                $expirationDate = $itemExpirationDate;
                                \Illuminate\Support\Facades\Log::info("Updated order expiration to: " . $expirationDate->format('Y-m-d'));
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Error calculating expiration: " . $e->getMessage());
                        }
                    }
                }
            }

            // Thêm thông tin domains và ngày hết hạn vào đơn hàng
            $order->domains = array_unique($domains);
            $order->expiration_date = $expirationDate;

            \Illuminate\Support\Facades\Log::info("Final expiration for order #{$order->order_number}: " . $expirationDate->format('Y-m-d'));
        }

        return view('source.web.profile.orders', compact('user', 'customer', 'orders'));
    }
    /**
     * Hiển thị chi tiết đơn hàng
     */
    public function showOrderDetail($id)
    {
        $user = Auth::user();
        $customer = $user->customer;

        if (!$customer) {
            return redirect()->route('customer.profile')->with('error', 'Vui lòng cập nhật thông tin khách hàng trước.');
        }

        // Lấy thông tin đơn hàng và kiểm tra quyền truy cập
        $order = Orders::with(['items.product', 'payments', 'invoice'])
            ->where('id', $id)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        // Debug: Ghi log thông tin đơn hàng và sản phẩm
        \Illuminate\Support\Facades\Log::info('Order Detail ID: ' . $order->id . ', Number: ' . $order->order_number);
        \Illuminate\Support\Facades\Log::info('Order Items Count: ' . $order->items->count());

        // Log chi tiết từng sản phẩm
        foreach ($order->items as $index => $item) {
            \Illuminate\Support\Facades\Log::info('Item #' . ($index + 1) . ' - ID: ' . $item->id . ', Name: ' . $item->name);

            // Log product information if exists
            if ($item->product) {
                \Illuminate\Support\Facades\Log::info('Product ID: ' . $item->product->id . ', Type: ' . $item->product->type);

                // Check and log meta_data
                if ($item->product->meta_data !== null) {
                    \Illuminate\Support\Facades\Log::info('Meta Data Type: ' . gettype($item->product->meta_data));

                    if (is_array($item->product->meta_data)) {
                        \Illuminate\Support\Facades\Log::info('Meta Data Keys: ' . implode(', ', array_keys($item->product->meta_data)));
                        \Illuminate\Support\Facades\Log::info('Meta Data Content: ' . json_encode($item->product->meta_data));
                    } elseif (is_string($item->product->meta_data)) {
                        \Illuminate\Support\Facades\Log::info('Meta Data (String): ' . $item->product->meta_data);
                    } else {
                        \Illuminate\Support\Facades\Log::info('Meta Data is neither array nor string');
                    }
                } else {
                    \Illuminate\Support\Facades\Log::info('Meta Data is NULL');
                }

                // Check raw database value
                $rawProduct = \App\Models\Products::select('meta_data')->where('id', $item->product->id)->first();
                if ($rawProduct) {
                    $metaDataForLog = $rawProduct->meta_data;

                    // Chuyển đổi an toàn sang string để log
                    if (is_array($metaDataForLog) || is_object($metaDataForLog)) {
                        $metaDataForLog = json_encode($metaDataForLog);
                    } elseif ($metaDataForLog === null) {
                        $metaDataForLog = 'NULL';
                    }

                    \Illuminate\Support\Facades\Log::info('Raw Meta Data from DB: ' . $metaDataForLog);
                }
            } else {
                \Illuminate\Support\Facades\Log::info('No product associated with this item');
            }
        }

        return view('source.web.profile.order_detail', compact('user', 'customer', 'order'));
    }
}
