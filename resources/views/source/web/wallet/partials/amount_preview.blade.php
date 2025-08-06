{{-- resources/views/source/web/wallet/partials/amount_preview.blade.php --}}
<div id="amount-preview" class="mt-3 d-none">
    <div class="alert alert-info">
        <h6 class="mb-2">
            <i class="fas fa-calculator"></i> 
            {{ $locale === 'vi' ? 'Dự tính nhận được:' : 'Amount Calculation:' }}
        </h6>
        <div class="row text-center">
            <div class="col-md-4">
                <span class="text-muted d-block">
                    {{ $locale === 'vi' ? 'Nạp' : 'Deposit' }}
                </span>
                <strong id="preview-amount" class="fs-6">0</strong>
            </div>
            <div class="col-md-4">
                <span class="text-muted d-block">
                    {{ $locale === 'vi' ? 'Thưởng' : 'Bonus' }}
                </span>
                <strong id="preview-bonus" class="text-success fs-6">0</strong>
            </div>
            <div class="col-md-4">
                <span class="text-muted d-block">
                    {{ $locale === 'vi' ? 'Tổng' : 'Total' }}
                </span>
                <strong id="preview-total" class="text-primary fs-5">0</strong>
            </div>
        </div>
    </div>
</div>