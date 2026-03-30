@extends('layouts.web.default')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card text-center">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0"><i class="fa fa-clock me-2"></i>Đang chờ xác nhận thanh toán</h4>
                    </div>
                    <div class="card-body py-5">
                        <div class="mb-4">
                            <div class="spinner-border text-warning" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Đang tải...</span>
                            </div>
                        </div>

                        <h5>Đơn hàng #{{ $payment->order->order_number ?? 'N/A' }}</h5>
                        <p class="text-muted">Số tiền: <strong>{{ number_format($payment->amount, 0, ',', '.') }} đ</strong></p>
                        <p class="text-muted mb-4">
                            Mã giao dịch: <code>{{ $payment->transaction_id }}</code>
                        </p>

                        <div class="alert alert-info">
                            <i class="fa fa-info-circle me-2"></i>
                            Hệ thống đang chờ xác nhận từ ngân hàng. Trang này sẽ tự động cập nhật.
                        </div>

                        <div class="d-flex justify-content-center gap-3 mt-4">
                            <a href="{{ route('customer.orders') }}" class="btn btn-outline-secondary">
                                <i class="fa fa-list me-2"></i>Xem đơn hàng
                            </a>
                            <a href="{{ route('deposit') }}" class="btn btn-success">
                                <i class="fa fa-wallet me-2"></i>Nạp tiền ví
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Polling mỗi 10 giây để kiểm tra trạng thái
        const paymentId = {{ $payment->id }};
        const statusUrl = '{{ route('payment.status', $payment->id) }}';

        function checkStatus() {
            fetch(statusUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => r.json())
                .then(data => {
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    }
                })
                .catch(() => {}); // Silent fail, sẽ retry sau
        }

        // Kiểm tra ngay sau 5 giây, rồi mỗi 10 giây
        setTimeout(checkStatus, 5000);
        setInterval(checkStatus, 10000);
    </script>
@endsection
