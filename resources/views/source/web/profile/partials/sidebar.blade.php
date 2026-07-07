{{--
    Sidebar tài khoản khách hàng — dùng chung cho mọi trang /customer/*.
    Tự chứa: lấy dữ liệu từ auth()->user() nên không phụ thuộc biến $user/$customer
    mà trang cha truyền vào. Gồm menu điều hướng + card số dư + JS ẩn/hiện số dư.
--}}
@php
    $sidebarCustomer = auth()->user()?->customer;
    $sidebarUnread   = auth()->user()?->unreadNotifications()->count() ?? 0;
@endphp
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
                    class="list-group-item list-group-item-action {{ request()->routeIs('customer.orders') || request()->routeIs('customer.order.detail') ? 'active' : '' }}">
                    Lịch sử đơn hàng
                </a>
                <a href="{{ route('customer.services.index') }}"
                    class="list-group-item list-group-item-action {{ request()->routeIs('customer.services.*') ? 'active' : '' }}">
                    Dịch vụ của tôi
                </a>
                <a href="{{ route('customer.invoices') }}"
                    class="list-group-item list-group-item-action {{ request()->routeIs('customer.invoices') ? 'active' : '' }}">
                    Hóa đơn chưa thanh toán
                </a>
                <a href="{{ route('notifications.index') }}"
                    class="list-group-item list-group-item-action {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                    Thông báo
                    @if ($sidebarUnread > 0)
                        <span class="badge badge-pill badge-danger">{{ $sidebarUnread }}</span>
                    @endif
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
                        value="{{ optional($sidebarCustomer)->formatted_balance ?? '0 đ' }}" readonly>
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

<script>
    // Ẩn/hiện số dư, ghi nhớ trạng thái trong localStorage.
    document.addEventListener('DOMContentLoaded', function () {
        const balanceDisplay = document.getElementById('balanceDisplay');
        const toggleBalance = document.getElementById('toggleBalance');
        const balanceToggleIcon = document.getElementById('balanceToggleIcon');
        if (!balanceDisplay || !toggleBalance) return;

        const actualBalance = balanceDisplay.value;
        const hiddenBalance = "•••••••••••";
        let isBalanceVisible = true;

        const savedVisibility = localStorage.getItem('balanceVisibility');
        if (savedVisibility !== null) {
            isBalanceVisible = savedVisibility === 'true';
            if (!isBalanceVisible) {
                balanceDisplay.value = hiddenBalance;
                balanceToggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
            }
        }

        toggleBalance.addEventListener('click', function () {
            isBalanceVisible = !isBalanceVisible;
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
