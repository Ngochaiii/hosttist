{{-- Sửa file partials/amount_selection.blade.php --}}

<!-- Custom amount input -->
<div class="mb-3" id="custom_amount_container">
    <label for="amount" class="form-label fw-bold">
        <i class="fas fa-calculator"></i> 
        {{ $locale === 'vi' ? 'Số tiền nạp' : 'Deposit Amount' }} ({{ $currency['code'] }}) 
        <span class="text-danger">*</span>
    </label>
    <div class="input-group">
        @if($currency['code'] === 'USD')
            <span class="input-group-text">$</span>
        @endif
        <input type="number" class="form-control form-control-lg" id="amount" name="amount"
               placeholder="{{ $locale === 'vi' ? 'Nhập số tiền' : 'Enter amount' }}" 
               step="{{ $currency['code'] === 'USD' ? '0.01' : '1' }}"
               min="{{ $locale === 'vi' ? $minDeposit : round($minDeposit / $usdRate, 2) }}" 
               max="{{ $locale === 'vi' ? $maxDeposit : round($maxDeposit / $usdRate, 2) }}"
               required>
        @if($currency['code'] === 'VND')
            <span class="input-group-text">đ</span>
        @endif
    </div>
    <div class="form-text">
        <i class="fas fa-info-circle"></i>
        {{ $locale === 'vi' ? 'Số tiền tối thiểu:' : 'Minimum amount:' }}
        @if($locale === 'vi')
            <strong>{{ number_format($minDeposit, 0, ',', '.') }} đ</strong>
            - {{ $locale === 'vi' ? 'Tối đa:' : 'Maximum:' }}
            <strong>{{ number_format($maxDeposit, 0, ',', '.') }} đ</strong>
        @else
            <strong>${{ number_format($minDeposit / $usdRate, 2) }}</strong>
            - {{ $locale === 'vi' ? 'Tối đa:' : 'Maximum:' }}
            <strong>${{ number_format($maxDeposit / $usdRate, 2) }}</strong>
            <br><small class="text-muted">Exchange Rate: 1 USD = {{ number_format($usdRate, 0, ',', '.') }} VND</small>
        @endif
    </div>
    
    @include('source.web.wallet.partials.amount_preview')
</div>