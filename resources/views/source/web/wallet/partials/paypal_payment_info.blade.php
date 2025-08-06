{{-- resources/views/source/web/wallet/partials/paypal_payment_info.blade.php --}}
<div class="paypal-info">
    <div class="d-flex align-items-center mb-3">
        <i class="fab fa-paypal fa-3x text-primary me-3"></i>
        <div>
            <h6 class="mb-1">{{ $locale === 'vi' ? 'Thanh toán PayPal' : 'PayPal Payment' }}</h6>
            <p class="mb-0 text-muted">
                {{ $locale === 'vi' ? 'Bạn sẽ chuyển sang PayPal để thanh toán' : 'You will be redirected to PayPal for payment' }}
            </p>
        </div>
    </div>
    <ul class="list-unstyled mb-0">
        <li><i class="fas fa-check text-success me-2"></i>{{ $locale === 'vi' ? 'Hỗ trợ thẻ tín dụng' : 'Credit cards supported' }}</li>
        <li><i class="fas fa-check text-success me-2"></i>{{ $locale === 'vi' ? 'Bảo mật SSL cao' : 'High SSL security' }}</li>
        <li><i class="fas fa-info text-primary me-2"></i>{{ $locale === 'vi' ? 'Phí: 2.9% + $0.30' : 'Fee: 2.9% + $0.30' }}</li>
        <li><i class="fas fa-bolt text-warning me-2"></i>{{ $locale === 'vi' ? 'Xử lý tức thì' : 'Instant processing' }}</li>
    </ul>
</div>