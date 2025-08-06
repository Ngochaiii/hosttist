@switch($method)
    @case('bank')
        <i class="fas fa-university text-primary"></i> Chuyển khoản ngân hàng
        @break
    @case('momo')
        <i class="fas fa-wallet text-danger"></i> Ví MoMo
        @break
    @case('zalopay')
        <i class="fas fa-wallet text-primary"></i> ZaloPay
        @break
    @case('paypal')
        <i class="fab fa-paypal text-primary"></i> PayPal
        @break
    @case('crypto')
        <i class="fab fa-bitcoin text-warning"></i> Tiền điện tử
        @break
@endswitch