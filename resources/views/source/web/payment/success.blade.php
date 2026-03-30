@extends('layouts.web.default')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card text-center">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="fa fa-check-circle me-2"></i>Thanh toán thành công!</h4>
                    </div>
                    <div class="card-body py-5">
                        <div class="mb-4">
                            <i class="fa fa-check-circle fa-5x text-success"></i>
                        </div>

                        <h5>Cảm ơn bạn đã thanh toán!</h5>
                        <p class="text-muted">Đơn hàng #{{ $payment->order->order_number ?? 'N/A' }}</p>
                        <p class="text-muted mb-1">Số tiền: <strong>{{ number_format($payment->amount, 0, ',', '.') }} đ</strong></p>
                        <p class="text-muted mb-4">Thời gian: {{ $payment->verified_at ? $payment->verified_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</p>

                        <div class="alert alert-success">
                            <i class="fa fa-cog fa-spin me-2"></i>
                            Chúng tôi đang xử lý dịch vụ của bạn. Bạn sẽ nhận được email khi dịch vụ sẵn sàng.
                        </div>

                        <div class="d-flex justify-content-center gap-3 mt-4">
                            <a href="{{ route('customer.services.index') }}" class="btn btn-primary">
                                <i class="fa fa-server me-2"></i>Xem dịch vụ
                            </a>
                            <a href="{{ route('customer.orders') }}" class="btn btn-outline-secondary">
                                <i class="fa fa-list me-2"></i>Lịch sử đơn hàng
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
