<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .error-box { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        .footer { margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; font-size: 12px; color: #777; text-align: center; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>{{ $config->company_name ?? 'Hostist Company' }}</h1>
            <p>Thông báo thanh toán</p>
        </div>
        
        <div class='error-box'>
            <p><strong>Thanh toán của bạn đã bị từ chối</strong></p>
        </div>
        
        <p>Kính gửi {{ $user->name }},</p>
        <p>Chúng tôi rất tiếc phải thông báo rằng thanh toán của bạn đã bị từ chối với lý do:</p>
        
        <table>
            <tr><th>Mã giao dịch:</th><td>{{ $payment->transaction_id }}</td></tr>
            <tr><th>Số tiền:</th><td>{{ number_format($payment->amount, 0, ',', '.') }} đ</td></tr>
            <tr><th>Lý do từ chối:</th><td>{{ $reason }}</td></tr>
            <tr><th>Ngày từ chối:</th><td>{{ now()->format('d/m/Y H:i:s') }}</td></tr>
        </table>
        
        <p>Vui lòng kiểm tra lại thông tin thanh toán và thực hiện lại giao dịch, hoặc liên hệ với chúng tôi để được hỗ trợ.</p>
        
        <p>Trân trọng,<br>{{ $config->company_name ?? 'Hostist Company' }}</p>
        
        <div class='footer'>
            <p>© {{ date('Y') }} {{ $config->company_name ?? 'Hostist Company' }}. Tất cả các quyền được bảo lưu.</p>
        </div>
    </div>
</body>
</html>