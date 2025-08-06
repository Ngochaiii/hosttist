<div class="row">
    <div class="col-md-8">
        <h6 class="text-primary mb-3">
            <i class="fas fa-wallet text-danger me-2"></i>Thông tin chuyển khoản MoMo:
        </h6>
        <div class="table-responsive">
            <table class="table table-borderless">
                <tr>
                    <td><strong>Số điện thoại:</strong></td>
                    <td>
                        <span class="badge bg-danger">{{ $paymentInfo['phone'] ?? 'N/A' }}</span>
                        @if (isset($paymentInfo['phone']))
                            <button class="btn btn-sm btn-outline-primary ms-2 copy-button" 
                                    onclick="copyToClipboard('{{ $paymentInfo['phone'] }}')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td><strong>Tên tài khoản:</strong></td>
                    <td>{{ $paymentInfo['account_name'] ?? 'N/A' }}</td>
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
        <div class="alert alert-info">
            <small>
                <i class="fas fa-info-circle me-1"></i>
                Mở app MoMo → Chọn "Chuyển tiền" → Nhập số điện thoại và nội dung chuyển khoản
            </small>
        </div>
    </div>
    <div class="col-md-4 text-center">
        {{-- Kiểm tra momo QR - có thể là company_momo_qr_code hoặc tương tự --}}
        @if (isset($depositData['config']) && 
             (property_exists($depositData['config'], 'company_momo_qr_code') && $depositData['config']->company_momo_qr_code))
            <h6 class="text-primary mb-3">
                <i class="fas fa-wallet text-danger me-1"></i>Quét mã QR MoMo:
            </h6>
            <div class="qr-container">
                <img src="{{ asset('storage/' . $depositData['config']->company_momo_qr_code) }}" 
                     alt="QR Code MoMo" 
                     class="img-fluid border rounded payment-qr" 
                     style="max-height: 200px;"
                     loading="lazy"
                     onerror="this.parentElement.innerHTML='<div class=\'text-muted\'><i class=\'fas fa-exclamation-triangle\'></i><br>Không thể tải QR Code</div>'">
            </div>
            <p class="small mt-2 text-muted">
                <i class="fas fa-mobile-alt me-1"></i>
                Quét bằng app MoMo
            </p>
        @else
            <div class="text-center p-3 border rounded bg-light">
                <i class="fas fa-mobile-alt fa-3x text-danger mb-2"></i>
                <p class="small text-muted mb-0">Sử dụng app MoMo để chuyển tiền</p>
                <small class="text-muted">Nhập thông tin bên trái</small>
            </div>
        @endif
    </div>
</div>
