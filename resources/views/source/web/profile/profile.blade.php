@extends('layouts.web.default')

@section('content')
    <div class="container py-5">
        <div class="row">
            <div class="col-md-4">
                <!-- Trong file profile.blade.php -->
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
                    <div class="card-header">
                        <h4>Thông tin cá nhân</h4>
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

                        <form method="POST" action="{{ route('customer.profile.update') }}">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Họ tên <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" value="{{ $user->email }}"
                                        readonly disabled>
                                    <small class="form-text text-muted">Email không thể thay đổi</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Số điện thoại <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                        id="phone" name="phone" value="{{ old('phone', $user->phone) }}" required>
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="company_name" class="form-label">Tên công ty</label>
                                    <input type="text" class="form-control @error('company_name') is-invalid @enderror"
                                        id="company_name" name="company_name"
                                        value="{{ old('company_name', optional($customer)->company_name) }}">
                                    @error('company_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Địa chỉ <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3"
                                    required>{{ old('address', $user->address) }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tax_code" class="form-label">Mã số thuế</label>
                                    <input type="text" class="form-control @error('tax_code') is-invalid @enderror"
                                        id="tax_code" name="tax_code"
                                        value="{{ old('tax_code', optional($customer)->tax_code) }}">
                                    @error('tax_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="website" class="form-label">Website</label>
                                    <input type="url" class="form-control @error('website') is-invalid @enderror"
                                        id="website" name="website"
                                        value="{{ old('website', optional($customer)->website) }}">
                                    @error('website')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary">Cập nhật thông tin</button>
                            </div>
                        </form>
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
