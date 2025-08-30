<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .info-box { background-color: #e3f2fd; border-color: #bbdefb; color: #0277bd; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        .footer { margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; font-size: 12px; color: #777; text-align: center; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>{{ $config->company_name ?? 'Hostist Company' }}</h1>
            <p>Xác nhận đơn hàng</p>
        </div>
        
        <div class='info-box'>
            <p><strong>Đơn hàng của bạn đã được tạo thành công!</strong></p>
        </div>
        
        <p>Kính gửi {{ $user->name }},</p>
        <p>Cảm ơn bạn đã đặt hàng. Thông tin đơn hàng của bạn như sau:</p>
        
        <table>
            <tr><th>Mã đơn hàng:</th><td>{{ $order->order_number }}</td></tr>
            <tr><th>Ngày đặt hàng:</th><td>{{ $order->created_at->format('d/m/Y H:i:s') }}</td></tr>
            <tr><th>Trạng thái:</th><td>{{ $orderStatusName }}</td></tr>
            <tr><th>Tổng tiền:</th><td>{{ number_format($order->total_amount, 0, ',', '.') }} đ</td></tr>
        </table>
        
        <h3>Chi tiết dịch vụ:</h3>
        <table>
            <thead>
                <tr>
                    <th>Dịch vụ</th>
                    <th>Số lượng</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->subtotal, 0, ',', '.') }} đ</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <p>Đơn hàng của bạn đang được xử lý. Chúng tôi sẽ thông báo cho bạn khi có cập nhật mới.</p>
        
        <p>Trân trọng,<br>{{ $config->company_name ?? 'Hostist Company' }}</p>
        
        <div class='footer'>
            <p>© {{ date('Y') }} {{ $config->company_name ?? 'Hostist Company' }}. Tất cả các quyền được bảo lưu.</p>
        </div>
    </div>
</body>
</html>