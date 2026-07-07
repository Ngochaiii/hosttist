<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers;
use App\Models\Invoices;
use App\Models\Orders;
use App\Models\Products;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    /**
     * Hiển thị danh sách khách hàng
     */
    public function index()
    {
        $customers = Customers::with('user')->paginate(10);
        return view('source.admin.customers.index', compact('customers'));
    }

    /**
     * Hiển thị form tạo khách hàng mới
     */
    public function create()
    {
        return view('source.admin.customers.create');
    }

    /**
     * Lưu khách hàng mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'company_name' => 'nullable|string|max:255',
            'tax_code' => 'nullable|string|max:50',
            'business_type' => 'nullable|string|max:50',
            'industry' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'status' => 'required|in:active,inactive',
            'balance' => 'nullable|numeric|min:0',
            'wallet_id' => 'nullable|string|max:255',
        ]);

        // Tạo user mới. role/is_active ngoài $fillable (thuộc tính đặc quyền)
        // → gán tường minh sau khi khởi tạo.
        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'address' => $request->address,
        ]);
        $user->role = 'user';
        $user->is_active = true;
        $user->save();

        // Tạo customer liên kết với user
        $customer = Customers::create([
            'user_id' => $user->id,
            'company_name' => $request->company_name,
            'tax_code' => $request->tax_code,
            'business_type' => $request->business_type,
            'industry' => $request->industry,
            'website' => $request->website,
            'status' => $request->status,
            'balance' => $request->balance ?? 0,
            'wallet_id' => $request->wallet_id,
            'source' => 'admin',
        ]);

        return redirect()->route('admin.customers.index')
            ->with('success', 'Khách hàng đã được tạo thành công.');
    }

    /**
     * Hiển thị thông tin chi tiết khách hàng
     */
    public function show($id)
    {
        $customer = Customers::with('user')->findOrFail($id);

        // Lấy các đơn hàng của khách hàng
        $orders = Orders::where('customer_id', $id)->get();

        // Lấy các hóa đơn của khách hàng
        $invoices = Invoices::where('customer_id', $id)->get();

        // Lấy các dịch vụ của khách hàng
        $services = Products::where('customer_id', $id)->get();
        return view('source.admin.customers.show', compact('customer'));
    }

    /**
     * Hiển thị form chỉnh sửa thông tin khách hàng
     */
    public function edit($id)
    {
        $customer = Customers::with('user')->findOrFail($id);
        return view('source.admin.customers.edit', compact('customer'));
    }

    /**
     * Cập nhật thông tin khách hàng
     */
    public function update(Request $request, $id)
    {
        $customer = Customers::findOrFail($id);
        $user = User::findOrFail($customer->user_id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'company_name' => 'nullable|string|max:255',
            'tax_code' => 'nullable|string|max:50',
            'business_type' => 'nullable|string|max:50',
            'industry' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'status' => 'required|in:active,inactive',
            'balance' => 'nullable|numeric|min:0',
            'wallet_id' => 'nullable|string|max:255',
        ]);

        // Cập nhật thông tin user
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->address = $request->address;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        // Cập nhật thông tin customer
        $customer->company_name = $request->company_name;
        $customer->tax_code = $request->tax_code;
        $customer->business_type = $request->business_type;
        $customer->industry = $request->industry;
        $customer->website = $request->website;
        $customer->status = $request->status;

        // Cập nhật số dư nếu thay đổi
        if ($request->filled('balance') && $request->balance != $customer->balance) {
            $customer->balance = $request->balance;
            // Có thể lưu lịch sử thay đổi số dư ở đây nếu cần
        }

        $customer->wallet_id = $request->wallet_id;
        $customer->save();

        return redirect()->route('admin.customers.index')
            ->with('success', 'Thông tin khách hàng đã được cập nhật thành công.');
    }

    /**
     * Xóa khách hàng
     */
    public function destroy($id)
    {
        $customer = Customers::findOrFail($id);
        $user = User::findOrFail($customer->user_id);

        // Xóa customer trước vì có khóa ngoại tham chiếu đến user
        $customer->delete();
        // Xóa user (đã cài đặt soft delete trong User model)
        $user->delete();

        return redirect()->route('admin.customers.index')
            ->with('success', 'Khách hàng đã được xóa thành công.');
    }

    /**
     * Thay đổi trạng thái khách hàng
     */
    public function toggleStatus($id)
    {
        $customer = Customers::findOrFail($id);
        $customer->status = ($customer->status === 'active') ? 'inactive' : 'active';
        $customer->save();

        return redirect()->route('admin.customers.index')
            ->with('success', 'Trạng thái khách hàng đã được cập nhật.');
    }

    /**
     * Điều chỉnh số dư tài khoản
     */
    public function adjustBalance(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'type' => 'required|in:add,subtract',
            'note' => 'nullable|string|max:255',
        ]);

        $customer = Customers::findOrFail($id);
        $amount = $request->amount;

        if ($request->type === 'add') {
            $customer->balance += $amount;
            $message = 'Đã cộng ' . number_format($amount) . ' đ vào tài khoản khách hàng.';
        } else {
            if ($customer->balance < $amount) {
                return back()->with('error', 'Số dư không đủ để trừ.');
            }
            $customer->balance -= $amount;
            $message = 'Đã trừ ' . number_format($amount) . ' đ từ tài khoản khách hàng.';
        }

        $customer->save();

        // Có thể lưu lịch sử thay đổi số dư ở đây

        return redirect()->route('admin.customers.show', $id)
            ->with('success', $message);
    }
}
