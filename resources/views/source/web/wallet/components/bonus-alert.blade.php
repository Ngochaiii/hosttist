<div class="alert alert-success">
    <h5 class="mb-2">
        <i class="fas fa-gift"></i> Chúc mừng! Bạn nhận được tiền thưởng
    </h5>
    <p class="mb-0">
        Bạn đã nạp <strong>{{ number_format($depositData['amount'], 0, ',', '.') }} 
        @if($depositData['currency'] == 'VND') đ @else $ @endif</strong>
        và nhận thêm <strong class="text-success">{{ number_format($depositData['bonus_amount'], 0, ',', '.') }} 
        @if($depositData['currency'] == 'VND') đ @else $ @endif</strong>
        tiền thưởng ({{ $depositData['bonus_percent'] }}%).
        Tổng cộng bạn sẽ nhận được <strong class="text-success">{{ number_format($depositData['final_amount'], 0, ',', '.') }} 
        @if($depositData['currency'] == 'VND') đ @else $ @endif</strong>
        khi giao dịch được xác nhận.
    </p>
</div>