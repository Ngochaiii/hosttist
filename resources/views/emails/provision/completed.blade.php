<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dịch vụ đã sẵn sàng</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
        .container { padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .header { text-align: center; padding-bottom: 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
        .header h1 { color: #16a34a; margin: 0; }
        .success-box { background-color: #ecfdf5; border-left: 4px solid #16a34a; padding: 15px; margin-bottom: 20px; }
        .service-info { background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .btn { display: inline-block; background-color: #16a34a; color: #fff !important; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px; }
        .footer { text-align: center; font-size: 12px; color: #888; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Dịch vụ đã sẵn sàng</h1>
        </div>

        <p>Xin chào <strong>{{ $provision->customer->user->name ?? 'Quý khách' }}</strong>,</p>

        <div class="success-box">
            Dịch vụ <strong>{{ ucfirst($provision->provision_type) }}</strong>
            (mã <strong>#{{ $provision->id }}</strong>) đã được cung cấp thành công và sẵn sàng sử dụng.
        </div>

        <div class="service-info">
            <p><strong>Loại dịch vụ:</strong> {{ ucfirst($provision->provision_type) }}</p>
            @if ($provision->product)
                <p><strong>Sản phẩm:</strong> {{ $provision->product->name }}</p>
            @endif
            <p><strong>Hoàn tất lúc:</strong>
                {{ optional($provision->provisioned_at)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}
            </p>
            @if ($provision->provision_notes)
                <p><strong>Ghi chú:</strong> {{ $provision->provision_notes }}</p>
            @endif
        </div>

        <p>Bạn có thể đăng nhập để xem thông tin truy cập / credentials chi tiết:</p>
        <a href="{{ url('/customer/services') }}" class="btn">Xem dịch vụ của tôi</a>

        <div class="footer">
            <p>Email tự động — vui lòng không trả lời trực tiếp. Cần hỗ trợ vui lòng liên hệ bộ phận CSKH.</p>
        </div>
    </div>
</body>
</html>
