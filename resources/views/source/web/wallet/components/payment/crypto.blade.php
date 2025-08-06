<div class="row">
    <div class="col-md-8">
        <h6 class="text-primary mb-3">
            <i class="fab fa-bitcoin text-warning me-2"></i>Cryptocurrency Payment:
        </h6>
        <div class="table-responsive">
            <table class="table table-borderless">
                <tr>
                    <td><strong>Currency:</strong></td>
                    <td>
                        <span class="badge bg-warning text-dark">
                            {{ strtoupper($paymentInfo['crypto_type'] ?? 'BTC') }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Network:</strong></td>
                    <td>{{ $paymentInfo['network'] ?? 'Bitcoin Network' }}</td>
                </tr>
                <tr>
                    <td><strong>Wallet Address:</strong></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <code class="me-2 text-truncate" style="max-width: 200px;">
                                {{ $paymentInfo['wallet_address'] ?? 'N/A' }}
                            </code>
                            @if (isset($paymentInfo['wallet_address']))
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="copyToClipboard('{{ $paymentInfo['wallet_address'] }}')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    <td><strong>Amount:</strong></td>
                    <td>
                        <span class="badge bg-success">${{ number_format($depositData['amount'], 2) }}</span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Reference:</strong></td>
                    <td>
                        <span class="badge bg-info">{{ $depositData['note_format'] ?? 'N/A' }}</span>
                    </td>
                </tr>
            </table>
        </div>
        <div class="alert alert-warning">
            <small>
                <i class="fas fa-exclamation-triangle me-1"></i>
                <strong>Important:</strong> Only send the exact amount to this address. 
                Include the reference code in the transaction memo if supported.
            </small>
        </div>
    </div>
    <div class="col-md-4 text-center">
        @if (isset($paymentInfo['qr_code']) && $paymentInfo['qr_code'])
            <h6 class="text-primary mb-3">QR Code:</h6>
            <img src="{{ $paymentInfo['qr_code'] }}" alt="Crypto QR Code" 
                 class="img-fluid border rounded shadow-hover" style="max-height: 200px;">
            <p class="small mt-2 text-muted">Scan with crypto wallet</p>
        @else
            <div class="text-muted border rounded p-3">
                <i class="fab fa-bitcoin fa-4x mb-2 text-warning"></i>
                <p class="small">Send to wallet address above</p>
                <div class="small text-muted">
                    Network: {{ $paymentInfo['network'] ?? 'Bitcoin Network' }}
                </div>
            </div>
        @endif
    </div>
</div>