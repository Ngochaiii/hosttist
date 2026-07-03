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
                                @if ($unreadCount > 0)
                                    <span class="badge badge-pill badge-danger">{{ $unreadCount }}</span>
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
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Thông báo</h4>
                        @if ($unreadCount > 0)
                            <form action="{{ route('notifications.readAll') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary btn-sm">
                                    <i class="fa fa-check" aria-hidden="true"></i> Đánh dấu tất cả đã đọc
                                </button>
                            </form>
                        @endif
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @forelse ($notifications as $notif)
                            @php $level = $notif->data['level'] ?? 'info'; @endphp
                            <div class="media border-bottom py-3 {{ is_null($notif->read_at) ? '' : 'text-muted' }}">
                                <div class="mr-3 text-center" style="width: 40px;">
                                    <i class="fa fa-2x
                                        {{ $level === 'success' ? 'fa-check-circle text-success' : ($level === 'danger' ? 'fa-times-circle text-danger' : ($level === 'warning' ? 'fa-exclamation-circle text-warning' : 'fa-info-circle text-info')) }}"
                                        aria-hidden="true"></i>
                                </div>
                                <div class="media-body">
                                    <div class="d-flex justify-content-between">
                                        <strong>
                                            {{ $notif->data['title'] ?? 'Thông báo' }}
                                            @if (is_null($notif->read_at))
                                                <span class="badge badge-primary">Mới</span>
                                            @endif
                                        </strong>
                                        <small class="text-muted">{{ $notif->created_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                    <p class="mb-1">{{ $notif->data['message'] ?? '' }}</p>
                                    <div>
                                        @if (!empty($notif->data['action_url']))
                                            <a href="{{ route('notifications.go', $notif->id) }}" class="btn btn-sm btn-outline-primary">
                                                Xem chi tiết
                                            </a>
                                        @endif
                                        @if (is_null($notif->read_at))
                                            <form action="{{ route('notifications.read', $notif->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-link text-muted">Đánh dấu đã đọc</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-5">
                                <i class="fa fa-bell-o fa-3x mb-3" aria-hidden="true"></i>
                                <p>Bạn chưa có thông báo nào.</p>
                            </div>
                        @endforelse

                        <div class="mt-3">
                            {{ $notifications->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
