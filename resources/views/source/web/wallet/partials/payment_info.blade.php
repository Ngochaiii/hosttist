{{-- Sửa file partials/payment_info.blade.php --}}
<div class="payment-info mt-4">
    @foreach($paymentMethods as $method => $info)
        <div id="{{ $method }}_info" class="payment-details" 
             {{-- Bỏ style="display: none" để luôn hiển thị --}}
             style="display: block;">
            <div class="alert alert-info">
                <h6 class="mb-3">
                    <i class="{{ $info['icon'] }}"></i> 
                    {{ $locale === 'vi' ? 'Thông tin thanh toán' : 'Payment Information' }} - {{ $info['name'] }}
                </h6>
                
                @if($method === 'bank')
                    @include('source.web.wallet.partials.bank_payment_info')
                @elseif($method === 'momo')
                    @include('source.web.wallet.partials.momo_payment_info')
                @elseif($method === 'zalopay')
                    @include('source.web.wallet.partials.zalopay_payment_info')
                @elseif($method === 'paypal')
                    @include('source.web.wallet.partials.paypal_payment_info')
                @elseif($method === 'crypto')
                    @include('source.web.wallet.partials.crypto_payment_info')
                @endif
            </div>
        </div>
    @endforeach
</div>