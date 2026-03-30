@extends('layouts.web.default')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card text-center">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0"><i class="fa fa-times-circle me-2"></i>Thanh toán thất bại</h4>
                    </div>
                    <div class="card-body py-5">
                        <div class="mb-4">
                            <i class="fa fa-times-circle fa-5x text-danger"></i>
                        </div>

                        <h5>Thanh toán không thành công</h5>
                        <p class="text-muted">Đơn hàng #{{ $payment->order->order_number ?? 'N/A' }}</p>
                        <p class="text-muted mb-4">Số tiền: <strong>{{ number_format($payment->amount, 0, ',', '.') }} đ</strong></p>

                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-triangle me-2"></i>
                            Giao dịch của bạn không thể hoàn tất. Vui lòng thử lại hoặc liên hệ hỗ trợ.
                        </div>

                        <div class="d-flex justify-content-center gap-3 mt-4">
                            <a href="{{ route('customer.invoices') }}" class="btn btn-primary">
                                <i class="fa fa-credit-card me-2"></i>Thử lại thanh toán
                            </a>
                            <a href="{{ route('customer.orders') }}" class="btn btn-outline-secondary">
                                <i class="fa fa-list me-2"></i>Xem đơn hàng
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
