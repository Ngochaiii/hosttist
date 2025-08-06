{{-- resources/views/source/web/wallet/partials/payment_methods.blade.php --}}
<div class="mb-4">
    <h5 class="text-primary mb-3">
        <i class="fas fa-credit-card"></i> 
        {{ $locale === 'vi' ? 'Chọn phương thức thanh toán' : 'Select Payment Method' }}
    </h5>

    <!-- Payment method cards -->
    <div class="row">
        @foreach($paymentMethods as $method => $info)
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="payment-method-card">
                    <input type="radio" class="btn-check" name="payment_method"
                        id="payment_{{ $method }}" value="{{ $method }}" 
                        {{ $loop->first ? 'checked' : '' }} autocomplete="off">
                    <label class="payment-method-label" for="payment_{{ $method }}">
                        <div class="payment-icon">
                            <i class="{{ $info['icon'] }}"></i>
                        </div>
                        <div class="payment-text">
                            <strong>{{ $info['name'] }}</strong>
                            <small>
                                @switch($method)
                                    @case('bank')
                                        {{ $locale === 'vi' ? 'Tất cả ngân hàng' : 'All banks' }}
                                        @break
                                    @case('momo')
                                        {{ $locale === 'vi' ? 'Nhanh chóng' : 'Quick pay' }}
                                        @break
                                    @case('zalopay')
                                        {{ $locale === 'vi' ? 'Tiện lợi' : 'Convenient' }}
                                        @break
                                    @case('paypal')
                                        {{ $locale === 'vi' ? 'Quốc tế' : 'International' }}
                                        @break
                                    @case('crypto')
                                        {{ $locale === 'vi' ? 'Hiện đại' : 'Modern' }}
                                        @break
                                @endswitch
                            </small>
                        </div>
                        <div class="payment-badge">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </label>
                </div>
            </div>
        @endforeach
    </div>

    @include('source.web.wallet.partials.crypto_options')
    @include('source.web.wallet.partials.payment_info')
</div>