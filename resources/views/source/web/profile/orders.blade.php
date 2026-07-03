<!-- File: resources/views/source/web/profile/orders.blade.php -->
@extends('layouts.web.default')

@section('content')
    <div class="container py-5">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Tài khoản của tôi</h5>
                        <div class="list-group">
                            <a href="{{ route('customer.profile') }}"
                                class="list-group-item list-group-item-action {{ request()->routeIs('customer.profile') ? 'active' : '' }}">
                                Thông tin cá nhân
                            </a>
                            <a href="{{ route('customer.orders') }}"
                                class="list-group-item list-group-item-action {{ request()->routeIs('customer.orders') ? 'active' : '' }}">
                                Lịch sử đơn hàng
                            </a>
                            <a href="{{ route('customer.invoices') }}"
                                class="list-group-item list-group-item-action {{ request()->routeIs('customer.invoices') ? 'active' : '' }}">
                                Hóa đơn chưa thanh toán
                            </a>
                            <a href="{{ route('notifications.index') }}"
                                class="list-group-item list-group-item-action {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                                Thông báo
                            </a>
                            <form action="{{ route('logout') }}" method="POST" class="d-none" id="logout-form">
                                @csrf
                            </form>
                            <a href="#" class="list-group-item list-group-item-action text-danger"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                Đăng xuất
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Hiển thị số dư tài khoản -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Thông tin tài chính</h5>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Số dư tài khoản:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="balanceDisplay"
                                    value="{{ optional($customer)->formatted_balance ?? '0 đ' }}" readonly>
                                <button class="btn btn-outline-secondary" type="button" id="toggleBalance">
                                    <i class="fa fa-eye" id="balanceToggleIcon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-grid">
                            <a href="{{ route('deposit') }}" class="btn btn-success btn-sm">
                                <i class="fa fa-plus-circle"></i> Nạp tiền
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Lịch sử đơn hàng</h4>
                    </div>
                    <div class="card-body">
                        @if (session('message'))
                            <div class="alert alert-info">
                                {{ session('message') }}
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        @if ($orders->isEmpty())
                            <div class="alert alert-info">
                                Bạn chưa có đơn hàng nào.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Mã đơn hàng</th>
                                            <th>Ngày mua</th>
                                            <th>Domain</th>
                                            <th>Ngày hết hạn</th> <!-- New column -->
                                            <th>Tổng tiền</th>
                                            <th>Trạng thái</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($orders as $order)
                                            <tr>
                                                <td>{{ $order->order_number }}</td>
                                                <td>{{ $order->created_at->format('d/m/Y') }}</td>
                                                <td>
                                                    @if (count($order->domains) > 0)
                                                        @foreach ($order->domains as $domain)
                                                            <span
                                                                class="badge bg-info text-white mb-1">{{ $domain }}</span><br>
                                                        @endforeach
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if (isset($order->expiration_date))
                                                        <span class="badge bg-warning text-white">
                                                            {{ $order->expiration_date->format('d/m/Y') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                                <td>{{ number_format($order->total_amount, 0, ',', '.') }} đ</td>
                                                <td>
                                                    @if ($order->status == 'completed')
                                                        <span class="badge bg-success">Đã hoàn thành</span>
                                                    @elseif($order->status == 'processing')
                                                        <span class="badge bg-primary">Đang xử lý</span>
                                                    @elseif($order->status == 'pending')
                                                        <span class="badge bg-warning">Chờ thanh toán</span>
                                                    @else
                                                        <span
                                                            class="badge bg-secondary">{{ ucfirst($order->status) }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        {{-- @if ($order->status == 'completed' && $order->total_amount >= 9000000)
                                                            @php
                                                                $cashback = App\Models\Cashbacks::where(
                                                                    'order_id',
                                                                    $order->id,
                                                                )->first();
                                                            @endphp

                                                            @if (!$cashback)
                                                                <!-- Chưa có yêu cầu hoàn tiền -->
                                                                <a href="#" class="btn btn-success register-cashback"
                                                                    data-order-id="{{ $order->id }}"
                                                                    data-order-number="{{ $order->order_number }}"
                                                                    data-order-total="{{ $order->total_amount }}"
                                                                    data-cashback-amount="{{ $order->total_amount * 0.12 }}">
                                                                    <i class="fa fa-money"></i> Đăng ký hoàn tiền 12%
                                                                </a>
                                                            @else
                                                                <!-- Đã có yêu cầu hoàn tiền -->
                                                                <a href="#"
                                                                    class="btn btn-primary view-cashback-status"
                                                                    data-cashback-id="{{ $cashback->id }}"
                                                                    data-order-number="{{ $order->order_number }}">
                                                                    <i class="fa fa-info-circle"></i> Xem trạng thái hoàn
                                                                    tiền
                                                                </a>
                                                            @endif
                                                        @else

                                                        @endif
                                                                                                                    <!-- Hiển thị các nút mặc định cho đơn hàng không đủ điều kiện --> --}}
                                                        <a href="{{ route('homepage') }}" class="btn btn-info">
                                                            <i class="fa fa-eye"></i> Tiếp tục mua hàng
                                                        </a>
                                                        <a href="{{ route('customer.order.detail', $order->id) }}"
                                                            class="btn btn-success">
                                                            <i class="fa fa-eye"></i> Xem chi tiết
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-center mt-4">
                                {{ $orders->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Đăng ký Hoàn tiền -->
    <div class="modal fade" id="registerCashbackModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Đăng ký nhận hoàn tiền</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="cashbackForm" method="POST" action="{{ route('cashback.register') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="cashbackOrderId">

                        <div class="mb-3">
                            <p><strong>Đơn hàng:</strong> <span id="cashbackOrderNumber"></span></p>
                            <p><strong>Tổng đơn hàng:</strong> <span id="cashbackOrderTotal"></span></p>
                            <p><strong>Số tiền hoàn (12%):</strong> <span id="cashbackAmount"></span></p>
                        </div>

                        <div class="mb-3">
                            <label for="bank_name" class="form-label">Tên ngân hàng</label>
                            <select class="form-select" id="bank_name" name="bank_name" required>
                                <option value="">-- Chọn ngân hàng --</option>
                                <option value="Vietcombank">Vietcombank</option>
                                <option value="BIDV">BIDV</option>
                                <option value="Vietinbank">Vietinbank</option>
                                <option value="Agribank">Agribank</option>
                                <option value="Techcombank">Techcombank</option>
                                <option value="MBBank">MBBank</option>
                                <!-- Thêm các ngân hàng khác -->
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="account_number" class="form-label">Số tài khoản</label>
                            <input type="text" class="form-control" id="account_number" name="account_number"
                                required>
                        </div>

                        <div class="mb-3">
                            <label for="account_holder" class="form-label">Tên chủ tài khoản</label>
                            <input type="text" class="form-control" id="account_holder" name="account_holder"
                                required>
                        </div>

                        <div class="mb-3">
                            <label for="branch" class="form-label">Chi nhánh (không bắt buộc)</label>
                            <input type="text" class="form-control" id="branch" name="branch">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Gửi yêu cầu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Xem trạng thái hoàn tiền -->
    <div class="modal fade" id="cashbackStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Trạng thái hoàn tiền</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="cashbackStatusContent">
                    <!-- Nội dung sẽ được tải bằng Ajax -->
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Đang tải...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

@endsection
@push('footer_js')
    <!-- Script xử lý modal -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Xử lý khi nhấn nút đăng ký hoàn tiền
            const registerBtns = document.querySelectorAll('.register-cashback');
            registerBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();

                    const orderId = this.getAttribute('data-order-id');
                    const orderNumber = this.getAttribute('data-order-number');
                    const orderTotal = this.getAttribute('data-order-total');
                    const cashbackAmount = this.getAttribute('data-cashback-amount');

                    document.getElementById('cashbackOrderId').value = orderId;
                    document.getElementById('cashbackOrderNumber').textContent = '#' + orderNumber;
                    document.getElementById('cashbackOrderTotal').textContent = new Intl
                        .NumberFormat('vi-VN').format(orderTotal) + ' đ';
                    document.getElementById('cashbackAmount').textContent = new Intl.NumberFormat(
                        'vi-VN').format(cashbackAmount) + ' đ';

                    const modal = new bootstrap.Modal(document.getElementById(
                        'registerCashbackModal'));
                    modal.show();
                });
            });

            // Xử lý khi nhấn nút xem trạng thái hoàn tiền
            const statusBtns = document.querySelectorAll('.view-cashback-status');
            statusBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();

                    const cashbackId = this.getAttribute('data-cashback-id');

                    // Hiển thị modal
                    const modal = new bootstrap.Modal(document.getElementById(
                        'cashbackStatusModal'));
                    modal.show();

                    // Tải nội dung trạng thái bằng Ajax
                    fetch('{{ route('cashback.status') }}?id=' + cashbackId)
                        .then(response => response.text())
                        .then(html => {
                            document.getElementById('cashbackStatusContent').innerHTML = html;
                        })
                        .catch(error => {
                            document.getElementById('cashbackStatusContent').innerHTML =
                                '<div class="alert alert-danger">Lỗi khi tải thông tin. Vui lòng thử lại.</div>';
                        });
                });
            });
        });
    </script>
    <!-- Script cho tính năng ẩn hiện số dư -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const balanceDisplay = document.getElementById('balanceDisplay');
            const toggleBalance = document.getElementById('toggleBalance');
            const balanceToggleIcon = document.getElementById('balanceToggleIcon');

            // Giá trị thật của số dư
            const actualBalance = "{{ optional($customer)->formatted_balance ?? '0 đ' }}";
            // Giá trị ẩn của số dư
            const hiddenBalance = "•••••••••••";

            // Mặc định hiển thị giá trị thật
            let isBalanceVisible = true;

            // Lưu trạng thái hiển thị trong localStorage nếu có
            const savedVisibility = localStorage.getItem('balanceVisibility');
            if (savedVisibility !== null) {
                isBalanceVisible = savedVisibility === 'true';

                // Cập nhật hiển thị dựa trên trạng thái đã lưu
                if (!isBalanceVisible) {
                    balanceDisplay.value = hiddenBalance;
                    balanceToggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
                }
            }

            // Xử lý sự kiện khi click vào nút toggle
            toggleBalance.addEventListener('click', function() {
                isBalanceVisible = !isBalanceVisible;

                // Lưu trạng thái hiển thị vào localStorage
                localStorage.setItem('balanceVisibility', isBalanceVisible);

                if (isBalanceVisible) {
                    balanceDisplay.value = actualBalance;
                    balanceToggleIcon.classList.replace('fa-eye-slash', 'fa-eye');
                } else {
                    balanceDisplay.value = hiddenBalance;
                    balanceToggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
                }
            });
        });
    </script>
@endpush
