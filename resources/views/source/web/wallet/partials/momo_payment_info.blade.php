{{-- resources/views/source/web/wallet/partials/momo_payment_info.blade.php --}}
<div class="bank-info-grid">
    <div class="info-item">
        <span class="info-label">{{ $locale === 'vi' ? 'SĐT MoMo:' : 'MoMo Phone:' }}</span>
        <span class="info-value">
            <code>{{ $config->momo_phone_number ?? '0987654321' }}</code>
            <button type="button" class="btn btn-sm btn-outline-primary ms-2" 
                    onclick="copyText('{{ $config->momo_phone_number ?? '0987654321' }}')">
                <i class="fas fa-copy"></i>
            </button>
        </span>
    </div>
    <div class="info-item">
        <span class="info-label">{{ $locale === 'vi' ? 'Tên:' : 'Name:' }}</span>
        <span class="info-value">{{ $config->momo_account_name ?? 'COMPANY NAME' }}</span>
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