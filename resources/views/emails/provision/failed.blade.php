<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lỗi cung cấp dịch vụ</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
        .container { padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .header { text-align: center; padding-bottom: 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
        .header h1 { color: #dc2626; margin: 0; }
        .alert-box { background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin-bottom: 20px; }
        .service-info { background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .footer { text-align: center; font-size: 12px; color: #888; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Có lỗi xảy ra với dịch vụ của bạn</h1>
        </div>

        <p>Xin chào <strong>{{ $provision->customer->user->name ?? 'Quý khách' }}</strong>,</p>

        <div class="alert-box">
            Yêu cầu cung cấp dịch vụ <strong>{{ ucfirst($provision->provision_type) }}</strong>
            (mã <strong>#{{ $provision->id }}</strong>) gặp sự cố và chưa hoàn tất.
            Đội ngũ kỹ thuật đã được thông báo và sẽ xử lý trong thời gian sớm nhất.
        </div>

        <div class="service-info">
            <p><strong>Loại dịch vụ:</strong> {{ ucfirst($provision->provision_type) }}</p>
            @if ($provision->product)
                <p><strong>Sản phẩm:</strong> {{ $provision->product->name }}</p>
            @endif
            @if ($provision->failure_reason ?? null)
                <p><strong>Lý do:</strong> {{ $provision->failure_reason }}</p>
            @endif
        </div>

        <p>Quý khách không cần thực hiện thêm thao tác nào. Chúng tôi sẽ liên hệ qua email/điện thoại nếu cần thông tin bổ sung.</p>

        <div class="footer">
            <p>Email tự động — vui lòng không trả lời trực tiếp. Cần hỗ trợ vui lòng liên hệ bộ phận CSKH.</p>
        </div>
    </div>
</body>
</html>
