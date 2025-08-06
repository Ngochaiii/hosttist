{{-- resources/views/source/web/wallet/partials/promotion_banner.blade.php --}}
<div class="mb-4">
    <div class="card border-success">
        <div class="card-body bg-gradient"
            style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <i class="fas fa-gift fa-3x text-success gift-icon"></i>
                </div>
                <div class="col-md-10">
                    <h5 class="text-success mb-2">
                        <i class="fas fa-star"></i> 
                        {{ $locale === 'vi' ? 'Ưu đãi đặc biệt' : 'Special Promotion' }}
                    </h5>
                    <p class="mb-1 fw-bold text-dark">
                        @if($locale === 'vi')
                            <i class="fas fa-arrow-up text-success"></i>
                            Nạp từ 10.000.000đ trở lên - Nhận ngay thưởng 5%
                        @else
                            <i class="fas fa-arrow-up text-success"></i>
                            Deposit ${{ number_format(10000000 / $usdRate, 0) }}+ - Get 5% bonus instantly
                        @endif
                    </p>
                    <p class="mb-2 text-success">
                        <i class="fas fa-calculator"></i>
                        @if($locale === 'vi')
                            Ví dụ: Nạp 10.000.000đ → Nhận <strong>10.500.000đ</strong>!
                        @else
                            Example: Deposit ${{ number_format(10000000 / $usdRate, 0) }} → Get <strong>${{ number_format((10000000 * 1.05) / $usdRate, 0) }}</strong>!
                        @endif
                    </p>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                            style="width: 100%"></div>
                    </div>
                    <p class="small mb-0 mt-2 text-muted">
                        <i class="fas fa-check-circle text-success"></i>
                        {{ $locale === 'vi' ? 'Áp dụng cho mọi phương thức thanh toán' : 'Applies to all payment methods' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>