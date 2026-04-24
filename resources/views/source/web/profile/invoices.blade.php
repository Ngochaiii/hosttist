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
                        <h4 class="mb-0">Hóa đơn chưa thanh toán</h4>
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

                        @if ($invoices->isEmpty())
                            <div class="alert alert-info">
                                Bạn không có hóa đơn chưa thanh toán nào.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Mã hóa đơn</th>
                                            <th>Ngày tạo</th>
                                            <th>Hạn thanh toán</th>
                                            <th>Tổng tiền</th>
                                            <th>Trạng thái</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($invoices as $invoice)
                                            <tr>
                                                <td>{{ $invoice->invoice_number }}</td>
                                                <td>{{ $invoice->created_at->format('d/m/Y') }}</td>
                                                <td>
                                                    @if ($invoice->due_date)
                                                        {{ is_string($invoice->due_date) ? \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') : $invoice->due_date->format('d/m/Y') }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                                <td>{{ number_format($invoice->total_amount, 0, ',', '.') }} đ</td>
                                                <td>
                                                    <span class="badge bg-warning text-dark">Chưa thanh toán</span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="{{ route('order.show', $invoice->order_id) }}"
                                                            class="btn btn-info">
                                                            <i class="fa fa-eye"></i> Xem
                                                        </a>
                                                        <a href="{{ route('invoice.download', $invoice->id) }}"
                                                            class="btn btn-secondary">
                                                            <i class="fa fa-download"></i> PDF
                                                        </a>
                                                        <a href="{{ route('invoice.payment', $invoice->id) }}"
                                                            class="btn btn-success">
                                                            <i class="fa fa-credit-card"></i> Thanh toán
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-center mt-4">
                                {{ $invoices->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

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
@endsection
