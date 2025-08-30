<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .success-box { background-color: #d4edda; border-color: #c3e6cb; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        th { font-weight: bold; }
        .footer { margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; font-size: 12px; color: #777; text-align: center; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>{{ $config->company_name ?? 'Hostist Company' }}</h1>
            <p>Xác nhận thanh toán</p>
        </div>
        
        <div class='success-box'>
            <p><strong>Thanh toán của bạn đã được xác nhận!</strong> Cảm ơn bạn đã thanh toán.</p>
        </div>
        
        <p>Kính gửi {{ $user->name }},</p>
        <p>Chúng tôi xác nhận đã nhận được thanh toán của bạn với thông tin như sau:</p>
        
        <table>
            <tr><th>Mã hóa đơn:</th><td>{{ $invoice->invoice_number ?? 'N/A' }}</td></tr>
            <tr><th>Mã đơn hàng:</th><td>{{ $order->order_number }}</td></tr>
            <tr><th>Số tiền:</th><td>{{ number_format($payment->amount, 0, ',', '.') }} đ</td></tr>
            <tr><th>Phương thức:</th><td>{{ $paymentMethodName }}</td></tr>
            <tr><th>Ngày xác nhận:</th><td>{{ $verifiedDate }}</td></tr>
            <tr><th>Mã giao dịch:</th><td>{{ $payment->transaction_id }}</td></tr>
        </table>
        
        <h3>Chi tiết dịch vụ:</h3>
        <table>
            <thead>
                <tr>
                    <th>Dịch vụ</th>
                    <th>Thời hạn</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    @php
                        $options = json_decode($item->options, true) ?: [];
                        $period = $options['period'] ?? $item->duration ?? 1;
                    @endphp
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ $period }} năm</td>
                        <td>{{ number_format($item->subtotal, 0, ',', '.') }} đ</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <p>Đơn hàng của bạn đang được xử lý. Bạn có thể theo dõi tình trạng đơn hàng tại trang quản lý của bạn.</p>
        
        <p>Trân trọng,<br>{{ $config->company_name ?? 'Hostist Company' }}</p>
        
        <div class='footer'>
            <p>© {{ date('Y') }} {{ $config->company_name ?? 'Hostist Company' }}. Tất cả các quyền được bảo lưu.</p>
        </div>
    </div>
</body>
</html>