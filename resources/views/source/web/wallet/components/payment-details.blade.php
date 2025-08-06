<div class="card border-primary mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-credit-card"></i> Thông tin thanh toán
        </h5>
    </div>
    <div class="card-body">
        @includeWhen($depositData['payment_method'] === 'bank', 'source.web.wallet.components.payment.bank', ['paymentInfo' => $depositData['payment_info']])
        @includeWhen($depositData['payment_method'] === 'momo', 'source.web.wallet.components.payment.momo', ['paymentInfo' => $depositData['payment_info']])
        @includeWhen($depositData['payment_method'] === 'zalopay', 'source.web.wallet.components.payment.zalopay', ['paymentInfo' => $depositData['payment_info']])
        @includeWhen($depositData['payment_method'] === 'paypal', 'source.web.wallet.components.payment.paypal', ['paymentInfo' => $depositData['payment_info']])
        @includeWhen($depositData['payment_method'] === 'crypto', 'source.web.wallet.components.payment.crypto', ['paymentInfo' => $depositData['payment_info']])
    </div>
</div>