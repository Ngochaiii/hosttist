<div class="row">
    <div class="col-md-8">
        <h6 class="text-primary mb-3">Thông tin chuyển khoản ngân hàng:</h6>
        <div class="table-responsive">
            <table class="table table-borderless">
                <tr>
                    <td><strong>Ngân hàng:</strong></td>
                    <td>{{ $paymentInfo['bank_name'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Số tài khoản:</strong></td>
                    <td>
                        <span class="badge bg-dark">{{ $paymentInfo['account_number'] ?? 'N/A' }}</span>
                        @if (isset($paymentInfo['account_number']))
                            <button class="btn btn-sm btn-outline-primary ms-2 copy-button" 
                                    onclick="copyToClipboard('{{ $paymentInfo['account_number'] }}')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td><strong>Chủ tài khoản:</strong></td>
                    <td>{{ $paymentInfo['account_name'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Chi nhánh:</strong></td>
                    <td>{{ $paymentInfo['branch'] ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Nội dung:</strong></td>
                    <td>
                        <span class="badge bg-warning text-dark">{{ $depositData['note_format'] ?? 'N/A' }}</span>
                        @if (isset($depositData['note_format']))
                            <button class="btn btn-sm btn-outline-primary ms-2 copy-button" 
                                    onclick="copyToClipboard('{{ $depositData['note_format'] }}')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div class="col-md-4 text-center">
        {{-- Sử dụng company_bank_qr_code từ config với asset storage --}}
        @if (isset($depositData['config']) && $depositData['config']->company_bank_qr_code)
            <h6 class="text-primary mb-3">Quét mã QR:</h6>
            <div class="qr-container">
                <img src="{{ asset('storage/' . $depositData['config']->company_bank_qr_code) }}" 
                     alt="QR Code Banking" 
                     class="img-fluid border rounded payment-qr" 
                     style="max-height: 200px;"
                     loading="lazy"
                     onerror="this.parentElement.innerHTML='<div class=\'text-muted\'><i class=\'fas fa-exclamation-triangle\'></i><br>Không thể tải QR Code</div>'">
            </div>
            <p class="small mt-2 text-muted">
                <i class="fas fa-mobile-alt me-1"></i>
                Quét mã QR để thanh toán nhanh
            </p>
        @else
            <div class="text-center p-3 border rounded bg-light">
                <i class="fas fa-university fa-3x text-primary mb-2"></i>
                <p class="small text-muted mb-0">Chuyển khoản theo thông tin bên trái</p>
                <small class="text-muted">Hoặc liên hệ để có QR Code</small>
            </div>
        @endif
    </div>
</div>