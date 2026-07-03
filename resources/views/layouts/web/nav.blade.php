<div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav ml-auto">
        <li class="nav-item {{ request()->routeIs('homepage') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('homepage') }}">Home
                @if (request()->routeIs('homepage'))
                    <span class="sr-only">(current)</span>
                @endif
            </a>
        </li>
        <li class="nav-item {{ request()->routeIs('about.index') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('about.index') }}">About</a>
        </li>
        <li class="nav-item {{ request()->routeIs('pricing.index') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('pricing.index') }}">Pricing</a>
        </li>
        <li class="nav-item {{ request()->routeIs('domains.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('domains.search') }}">Tên miền</a>
        </li>
        <li class="nav-item {{ request()->routeIs('contact.index') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('contact.index') }}">Contact Us</a>
        </li>
        
        @auth
            <!-- Services menu cho customer đã đăng nhập -->
            <li class="nav-item {{ request()->routeIs('customer.services.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('customer.services.index') }}">
                    <i class="fa fa-server" aria-hidden="true"></i> My Services
                </a>
            </li>
        @endauth

        @guest
            <!-- Hiển thị khi chưa đăng nhập -->
            <li class="nav-item {{ request()->routeIs('login') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('login') }}">
                    <i class="fa fa-sign-in" aria-hidden="true"></i> Login
                </a>
            </li>
            <li class="nav-item {{ request()->routeIs('register') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('register') }}">
                    <i class="fa fa-user-plus" aria-hidden="true"></i> Register
                </a>
            </li>
        @else
            <!-- Chuông thông báo in-app -->
            @php
                $navUnreadCount = Auth::user()->unreadNotifications()->count();
                $navNotifications = Auth::user()->notifications()->latest()->take(5)->get();
            @endphp
            <li class="nav-item dropdown">
                <a class="nav-link" href="#" id="notifDropdown" role="button" data-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="false" title="Thông báo">
                    <i class="fa fa-bell" aria-hidden="true"></i>
                    @if ($navUnreadCount > 0)
                        <span class="badge badge-pill badge-danger">{{ $navUnreadCount > 9 ? '9+' : $navUnreadCount }}</span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="notifDropdown"
                    style="min-width: 320px; max-width: 360px;">
                    <div class="d-flex justify-content-between align-items-center px-3 py-1">
                        <strong>Thông báo</strong>
                        @if ($navUnreadCount > 0)
                            <form action="{{ route('notifications.readAll') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-link btn-sm p-0">Đọc tất cả</button>
                            </form>
                        @endif
                    </div>
                    <div class="dropdown-divider my-1"></div>
                    @forelse ($navNotifications as $notif)
                        <a class="dropdown-item py-2 {{ is_null($notif->read_at) ? 'font-weight-bold' : 'text-muted' }}"
                            href="{{ route('notifications.go', $notif->id) }}"
                            style="white-space: normal; border-left: 3px solid
                                {{ ($notif->data['level'] ?? 'info') === 'success' ? '#28a745' : (($notif->data['level'] ?? 'info') === 'danger' ? '#dc3545' : '#17a2b8') }};">
                            <div class="small">{{ $notif->data['title'] ?? 'Thông báo' }}</div>
                            <div class="small text-truncate" style="max-width: 300px; font-weight: normal;">
                                {{ $notif->data['message'] ?? '' }}
                            </div>
                            <div class="small text-muted" style="font-weight: normal;">{{ $notif->created_at->diffForHumans() }}</div>
                        </a>
                    @empty
                        <div class="dropdown-item text-muted small">Chưa có thông báo nào.</div>
                    @endforelse
                    <div class="dropdown-divider my-1"></div>
                    <a class="dropdown-item text-center small" href="{{ route('notifications.index') }}">
                        Xem tất cả thông báo
                    </a>
                </div>
            </li>
            <!-- Hiển thị khi đã đăng nhập -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-user" aria-hidden="true"></i> {{ Auth::user()->name }}
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="{{ route('customer.profile') }}">
                        <i class="fa fa-user-circle" aria-hidden="true"></i> My Profile
                    </a>
                    <a class="dropdown-item" href="{{ route('customer.services.index') }}">
                        <i class="fa fa-server" aria-hidden="true"></i> My Services
                    </a>
                    <a class="dropdown-item" href="{{ route('customer.orders') }}">
                        <i class="fa fa-shopping-bag" aria-hidden="true"></i> My Orders
                    </a>
                    <a class="dropdown-item" href="{{ route('customer.invoices') }}">
                        <i class="fa fa-file-text" aria-hidden="true"></i> My Invoices
                    </a>
                    @if (Auth::user()->role == 'admin' || Auth::user()->role == 'super_admin')
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                            <i class="fa fa-dashboard" aria-hidden="true"></i> Admin Dashboard
                        </a>
                    @endif
                    <div class="dropdown-divider"></div>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fa fa-sign-out" aria-hidden="true"></i> Logout
                        </button>
                    </form>
                </div>
            </li>
        @endguest
        <li class="nav-item">
            <a class="nav-link" href="{{ route('cart.index') }}">
                <i class="fa fa-shopping-cart"></i> Cart
                @php
                    $cartItems = session('cart', []);
                    $cartCount = count($cartItems);
                @endphp
                @if ($cartCount > 0)
                    <span class="badge badge-pill badge-primary">{{ $cartCount }}</span>
                @endif
            </a>
        </li>
    </ul>
</div>