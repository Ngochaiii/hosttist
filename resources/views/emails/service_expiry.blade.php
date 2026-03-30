<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .warning-box { background-color: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .danger-box  { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        .btn { display: inline-block; padding: 10px 24px; background-color: #0d6efd; color: #fff; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .footer { margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; font-size: 12px; color: #777; text-align: center; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>Hostist VPS</h1>
            <p>Thông báo hết hạn dịch vụ</p>
        </div>

        @if ($daysLeft <= 1)
        <div class='danger-box'>
            <strong>⚠️ Khẩn cấp:</strong> Dịch vụ của bạn sẽ hết hạn hôm nay!
        </div>
        @else
        <div class='warning-box'>
            <strong>⏰ Nhắc nhở:</strong> Dịch vụ của bạn sẽ hết hạn trong <strong>{{ $daysLeft }} ngày</strong>.
        </div>
        @endif

        <p>Xin chào <strong>{{ $service->customer->user->name ?? 'Quý khách' }}</strong>,</p>

        <p>Chúng tôi xin thông báo rằng dịch vụ của bạn sắp đến ngày hết hạn. Vui lòng gia hạn để tránh gián đoạn dịch vụ.</p>

        <table>
            <tr>
                <th>Dịch vụ</th>
                <td>{{ $service->product->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Trạng thái</th>
                <td>{{ $service->status_label }}</td>
            </tr>
            <tr>
                <th>Ngày hết hạn</th>
                <td><strong>{{ $service->expires_at?->format('d/m/Y') ?? 'N/A' }}</strong></td>
            </tr>
            <tr>
                <th>Giá gia hạn</th>
                <td>{{ $service->renewal_price ? number_format($service->renewal_price, 0, ',', '.') . ' đ' : 'Liên hệ admin' }}</td>
            </tr>
            <tr>
                <th>Chu kỳ</th>
                <td>{{ $service->billing_cycle === 'yearly' ? 'Hàng năm' : 'Hàng tháng' }}</td>
            </tr>
        </table>

        <p style="text-align:center; margin: 30px 0;">
            <a href="{{ url('/customer/services') }}" class='btn'>Gia hạn ngay</a>
        </p>

        @if ($service->auto_renew)
        <p style="color: #198754;">✅ Dịch vụ của bạn đã bật <strong>Tự động gia hạn</strong>. Hệ thống sẽ tự động gia hạn nếu số dư ví đủ.</p>
        @else
        <p style="color: #dc3545;">❌ Tự động gia hạn chưa được bật. Vui lòng gia hạn thủ công hoặc bật tính năng này trong phần quản lý dịch vụ.</p>
        @endif

        <div class='footer'>
            <p>Nếu bạn có câu hỏi, vui lòng liên hệ bộ phận hỗ trợ của chúng tôi.</p>
            <p>Email này được gửi tự động, vui lòng không trả lời trực tiếp.</p>
        </div>
    </div>
</body>
</html>
