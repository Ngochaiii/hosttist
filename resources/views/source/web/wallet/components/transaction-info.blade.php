<div class="card bg-light mb-4">
    <div class="card-body">
        <h5 class="card-title text-primary">
            <i class="fas fa-receipt"></i> Thông tin giao dịch
        </h5>
        <div class="row">
            <div class="col-md-6">
                <p class="mb-2">
                    <strong>Mã giao dịch:</strong>
                    <span class="badge bg-primary">{{ $depositData['transaction_code'] }}</span>
                    <button class="btn btn-sm btn-outline-primary ms-1" 
                            onclick="copyToClipboard('{{ $depositData['transaction_code'] }}')">
                        <i class="fas fa-copy"></i>
                    </button>
                </p>
                <p class="mb-2"><strong>Ngày tạo:</strong> {{ $depositData['date'] }}</p>
                <p class="mb-2">
                    <strong>Số tiền gốc:</strong>
                    <span class="text-primary fw-bold">{{ number_format($depositData['amount'], 0, ',', '.') }} 
                        @if($depositData['currency'] == 'VND') đ @else $ @endif
                    </span>
                </p>
                @if (($depositData['bonus_amount'] ?? 0) > 0)
                    <p class="mb-2">
                        <strong>Tiền thưởng ({{ $depositData['bonus_percent'] ?? 5 }}%):</strong>
                        <span class="text-success fw-bold">+{{ number_format($depositData['bonus_amount'], 0, ',', '.') }} 
                            @if($depositData['currency'] == 'VND') đ @else $ @endif
                        </span>
                    </p>
                    <p class="mb-2">
                        <strong>Tổng nhận được:</strong>
                        <span class="text-success fw-bold fs-5">{{ number_format($depositData['final_amount'], 0, ',', '.') }} 
                            @if($depositData['currency'] == 'VND') đ @else $ @endif
                        </span>
                    </p>
                @endif
            </div>
            <div class="col-md-6">
                <p class="mb-2">
                    <strong>Phương thức:</strong>
                    @include('source.web.wallet.components.payment-method-icon', ['method' => $depositData['payment_method']])
                </p>
                <p class="mb-2">
                    <strong>Nội dung chuyển khoản:</strong>
                    <span class="badge bg-warning text-dark">{{ $depositData['note_format'] }}</span>
                    <button class="btn btn-sm btn-outline-primary ms-1" 
                            onclick="copyToClipboard('{{ $depositData['note_format'] }}')">
                        <i class="fas fa-copy"></i>
                    </button>
                </p>
                <p class="mb-0">
                    <strong>Trạng thái:</strong>
                    <span class="badge bg-warning" id="status-badge">
                        <i class="fas fa-clock"></i> Chờ thanh toán
                    </span>
                </p>
            </div>
        </div>
    </div>
</div>