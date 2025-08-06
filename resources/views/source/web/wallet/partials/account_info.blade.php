{{-- resources/views/source/web/wallet/partials/account_info.blade.php --}}
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="text-primary mb-0">
            <i class="fas fa-user-circle"></i> 
            {{ $locale === 'vi' ? 'Thông tin tài khoản' : 'Account Information' }}
        </h5>
        <span class="badge bg-success p-2 fs-6">
            <i class="fas fa-coins"></i> 
            {{ $locale === 'vi' ? 'Số dư:' : 'Balance:' }}
            @if($locale === 'vi')
                {{ number_format($customer->balance ?? 0, 0, ',', '.') }} đ
            @else
                ${{ number_format(($customer->balance ?? 0) / $usdRate, 2) }}
            @endif
        </span>
    </div>
    <div class="card bg-light border-0">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-2">
                        <i class="fas fa-user text-primary"></i>
                        <strong>{{ $locale === 'vi' ? 'Khách hàng:' : 'Customer:' }}</strong> 
                        {{ auth()->user()->name }}
                    </p>
                    <p class="mb-2">
                        <i class="fas fa-envelope text-primary"></i>
                        <strong>Email:</strong> {{ auth()->user()->email }}
                    </p>
                </div>
                <div class="col-md-6">
                    <p class="mb-2">
                        <i class="fas fa-id-card text-primary"></i>
                        <strong>{{ $locale === 'vi' ? 'Mã KH:' : 'Customer ID:' }}</strong>
                        {{ $customer ? $customer->id : 'N/A' }}
                    </p>
                    <p class="mb-2">
                        <i class="fas fa-globe text-primary"></i>
                        <strong>{{ $locale === 'vi' ? 'Tiền tệ:' : 'Currency:' }}</strong>
                        {{ $currency['code'] }} ({{ $currency['symbol'] }})
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>