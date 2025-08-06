{{-- resources/views/source/web/wallet/partials/bank_payment_info.blade.php --}}
<div class="bank-info-grid">
    <div class="info-item">
        <span class="info-label">{{ $locale === 'vi' ? 'Ngân hàng:' : 'Bank:' }}</span>
        <span class="info-value">{{ $config->company_bank_name ?? 'VietcomBank' }}</span>
    </div>
    <div class="info-item">
        <span class="info-label">{{ $locale === 'vi' ? 'Số TK:' : 'Account:' }}</span>
        <span class="info-value">
            <code>{{ $config->company_bank_account_number ?? '1234567890' }}</code>
            <button type="button" class="btn btn-sm btn-outline-primary ms-2" 
                    onclick="copyText('{{ $config->company_bank_account_number ?? '1234567890' }}')">
                <i class="fas fa-copy"></i>
            </button>
        </span>
    </div>
    <div class="info-item">
        <span class="info-label">{{ $locale === 'vi' ? 'Chủ TK:' : 'Name:' }}</span>
        <span class="info-value">{{ $config->company_bank_account_name ?? 'COMPANY NAME' }}</span>
    </div>
    <div class="info-item">
        <span class="info-label">{{ $locale === 'vi' ? 'Nội dung:' : 'Memo:' }}</span>
        <span class="info-value">
            <code>DEP{{ $customer->id }}</code>
            <button type="button" class="btn btn-sm btn-outline-primary ms-2" 
                    onclick="copyText('DEP{{ $customer->id }}')">
                <i class="fas fa-copy"></i>
            </button>
        </span>
    </div>
</div>