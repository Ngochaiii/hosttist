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
                           class="list-group-item list-group-item-action {{ request()->routeIs('customer.orders') || request()->routeIs('customer.order.detail') ? 'active' : '' }}">
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
        </div>

        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Chi tiết đơn hàng #{{ $order->order_number }}</h4>
                    <a href="{{ route('customer.orders') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fa fa-arrow-left"></i> Quay lại
                    </a>
                </div>
                <div class="card-body">
                    <!-- Thông tin đơn hàng cơ bản -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Thông tin đơn hàng</h5>
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td><strong>Mã đơn hàng:</strong></td>
                                    <td>{{ $order->order_number }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày đặt hàng:</strong></td>
                                    <td>{{ $order->created_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Trạng thái:</strong></td>
                                    <td>
                                        @if ($order->status == 'completed')
                                            <span class="badge bg-success">Đã hoàn thành</span>
                                        @elseif($order->status == 'processing')
                                            <span class="badge bg-primary">Đang xử lý</span>
                                        @elseif($order->status == 'pending')
                                            <span class="badge bg-warning">Chờ thanh toán</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($order->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Thông tin thanh toán</h5>
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td><strong>Tổng tiền:</strong></td>
                                    <td>{{ number_format($order->total_amount, 0, ',', '.') }} đ</td>
                                </tr>
                                @if($order->invoice)
                                <tr>
                                    <td><strong>Mã hóa đơn:</strong></td>
                                    <td>{{ $order->invoice->invoice_number }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Trạng thái hóa đơn:</strong></td>
                                    <td>
                                        @if ($order->invoice->status == 'paid')
                                            <span class="badge bg-success">Đã thanh toán</span>
                                        @elseif($order->invoice->status == 'pending')
                                            <span class="badge bg-warning">Chờ thanh toán</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($order->invoice->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <!-- Chi tiết sản phẩm đã mua -->
                    <h5 class="mt-4">Sản phẩm đã mua</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>STT</th>
                                    <th>Sản phẩm</th>
                                    <th>Loại</th>
                                    <th>Domain</th>
                                    <th>Đơn giá</th>
                                    <th>Thời hạn</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->name }}</td>
                                    <td>
                                        @if($item->product)
                                            @if($item->product->type == 'ssl')
                                                <span class="badge bg-success">SSL</span>
                                            @elseif($item->product->type == 'domain')
                                                <span class="badge bg-primary">Domain</span>
                                            @elseif($item->product->type == 'hosting')
                                                <span class="badge bg-info">Hosting</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($item->product->type) }}</span>
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $domain = $item->domain ?? '';
                                            if(empty($domain)) {
                                                $options = json_decode($item->options, true) ?: [];
                                                $domain = $options['domain'] ?? '';
                                            }
                                        @endphp

                                        @if(!empty($domain))
                                            <span class="badge bg-info text-white">{{ $domain }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($item->price, 0, ',', '.') }} đ</td>
                                    <td>
                                        @php
                                            $options = json_decode($item->options, true) ?: [];
                                            $period = $options['period'] ?? $item->duration ?? 1;
                                        @endphp
                                        {{ $period }} năm
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Chi tiết dữ liệu meta_data - PHẦN CHÍNH -->
                    @foreach ($order->items as $index => $item)
                        @if($item->product)
                            @php
                                // Lấy trực tiếp data từ database thay vì qua model để tránh vấn đề accessor
                                $rawProduct = DB::table('products')->select('meta_data', 'name')->where('id', $item->product->id)->first();
                            @endphp

                            @if($rawProduct && $rawProduct->meta_data)
                                <div class="card mt-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">Thông tin chi tiết: {{ $rawProduct->name }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="meta_data_{{ $index }}">Dữ liệu Meta</label>
                                            <div class="position-relative">
                                                <textarea class="form-control" id="meta_data_{{ $index }}"
                                                          rows="15" readonly>{{ $rawProduct->meta_data }}</textarea>
                                                <button class="btn btn-sm btn-primary position-absolute top-0 end-0 m-2 copy-text"
                                                        data-target="meta_data_{{ $index }}">
                                                    <i class="fa fa-copy"></i> Sao chép
                                                </button>
                                            </div>
                                            <small class="form-text text-muted">Dữ liệu meta có thể chứa các khóa private, chứng chỉ SSL...</small>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast thông báo copy -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="copyToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fa fa-check-circle me-2"></i> Đã sao chép vào clipboard!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
@endsection

@push('footer_js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Khởi tạo toast
        const copyToast = new bootstrap.Toast(document.getElementById('copyToast'), {
            delay: 2000
        });

        // Xử lý nút sao chép văn bản
        const copyButtons = document.querySelectorAll('.copy-text');
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const textArea = document.getElementById(targetId);

                // Chọn toàn bộ văn bản
                textArea.select();
                textArea.setSelectionRange(0, 99999); // Cho mobile

                // Sao chép
                document.execCommand('copy');

                // Hiển thị thông báo
                copyToast.show();
            });
        });
    });
</script>
@endpush
