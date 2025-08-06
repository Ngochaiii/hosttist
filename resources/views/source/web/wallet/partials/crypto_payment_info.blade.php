{{-- resources/views/source/web/wallet/partials/crypto_payment_info.blade.php --}}
<div id="crypto-wallet-info">
    <!-- Bitcoin Wallet -->
    <div class="crypto-wallet" data-crypto="bitcoin" style="display: none;">
        <div class="crypto-header mb-3">
            <i class="fab fa-bitcoin fa-2x text-warning me-2"></i>
            <strong>Bitcoin (BTC)</strong>
        </div>
        <div class="info-item">
            <span class="info-label">{{ $locale === 'vi' ? 'Địa chỉ ví:' : 'Wallet Address:' }}</span>
            <span class="info-value">
                <code>1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa</code>
                <button type="button" class="btn btn-sm btn-outline-warning ms-2" 
                        onclick="copyText('1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa')">
                    <i class="fas fa-copy"></i>
                </button>
            </span>
        </div>
        <div class="info-item">
            <span class="info-label">{{ $locale === 'vi' ? 'Mạng:' : 'Network:' }}</span>
            <span class="info-value">Bitcoin Network</span>
        </div>
    </div>

    <!-- Ethereum Wallet -->
    <div class="crypto-wallet" data-crypto="ethereum" style="display: none;">
        <div class="crypto-header mb-3">
            <i class="fab fa-ethereum fa-2x text-primary me-2"></i>
            <strong>Ethereum (ETH)</strong>
        </div>
        <div class="info-item">
            <span class="info-label">{{ $locale === 'vi' ? 'Địa chỉ ví:' : 'Wallet Address:' }}</span>
            <span class="info-value">
                <code>0x742d35cc6634c0532925a3b8d0c9c84cec932e1b</code>
                <button type="button" class="btn btn-sm btn-outline-primary ms-2" 
                        onclick="copyText('0x742d35cc6634c0532925a3b8d0c9c84cec932e1b')">
                    <i class="fas fa-copy"></i>
                </button>
            </span>
        </div>
        <div class="info-item">
            <span class="info-label">{{ $locale === 'vi' ? 'Mạng:' : 'Network:' }}</span>
            <span class="info-value">Ethereum (ERC-20)</span>
        </div>
    </div>

    <!-- USDT Wallet -->
    <div class="crypto-wallet" data-crypto="usdt" style="display: none;">
        <div class="crypto-header mb-3">
            <i class="fas fa-dollar-sign fa-2x text-success me-2"></i>
            <strong>Tether (USDT)</strong>
        </div>
        <div class="info-item">
            <span class="info-label">{{ $locale === 'vi' ? 'Địa chỉ ví:' : 'Wallet Address:' }}</span>
            <span class="info-value">
                <code>0x742d35cc6634c0532925a3b8d0c9c84cec932e1b</code>
                <button type="button" class="btn btn-sm btn-outline-success ms-2" 
                        onclick="copyText('0x742d35cc6634c0532925a3b8d0c9c84cec932e1b')">
                    <i class="fas fa-copy"></i>
                </button>
            </span>
        </div>
        <div class="info-item">
            <span class="info-label">{{ $locale === 'vi' ? 'Mạng:' : 'Network:' }}</span>
            <span class="info-value">Tether (ERC-20)</span>
        </div>
    </div>

    <!-- Crypto Warning -->
    <div class="alert alert-warning mt-3">
        <strong><i class="fas fa-exclamation-triangle"></i> 
            {{ $locale === 'vi' ? 'Lưu ý quan trọng:' : 'Important Notes:' }}
        </strong>
        <ul class="mb-0 mt-2">
            <li>{{ $locale === 'vi' ? 'Chỉ gửi đúng loại coin đã chọn' : 'Only send the selected cryptocurrency' }}</li>
            <li>{{ $locale === 'vi' ? 'Kiểm tra kỹ địa chỉ ví trước khi gửi' : 'Double-check wallet address before sending' }}</li>
            <li>{{ $locale === 'vi' ? 'Xác nhận sau 3-6 blocks' : 'Confirmation after 3-6 blocks' }}</li>
            <li>{{ $locale === 'vi' ? 'Phí mạng do bạn chi trả' : 'Network fees are your responsibility' }}</li>
        </ul>
    </div>
</div>