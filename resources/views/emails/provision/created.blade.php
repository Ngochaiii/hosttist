{{-- resources/views/emails/provision/created.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Yêu cầu dịch vụ đang được xử lý</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
        }
        .container {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #0066cc;
            margin: 0;
        }
        .info-box {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
        }
        .service-info {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .service-info p {
            margin: 5px 0;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
        .tracking-info {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Yêu cầu đang được xử lý</h1>
            <p>Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi</p>
        </div>

        <p>Kính gửi {{ $provision->customer->user->name }},</p>

        <div class="info-box">
            <p><strong>Chúng tôi đã nhận được yêu cầu dịch vụ của bạn và đang tiến hành xử lý.</strong></p>
        </div>

        <div class="service-info">
            <p><strong>Mã yêu cầu:</strong> #{{ $provision->id }}</p>
            <p><strong>Loại dịch vụ:</strong> 
                @if($provision->provision_type == 'ssl')
                    SSL Certificate
                @elseif($provision->provision_type == 'domain')
                    Đăng ký tên miền
                @elseif($provision->provision_type == 'hosting')
                    Hosting Account
                @else
                    {{ ucfirst($provision->provision_type) }}
                @endif
            </p>
            <p><strong>Sản phẩm:</strong> {{ $provision->product->name }}</p>
            <p><strong>Ngày yêu cầu:</strong> {{ $provision->created_at->format('d/m/Y H:i') }}</p>
            @if($provision->estimated_completion)
            <p><strong>Dự kiến hoàn thành:</strong> {{ $provision->estimated_completion->format('d/m/Y H:i') }}</p>
            @endif
        </div>

        <p>Dịch vụ của bạn sẽ được cung cấp trong thời gian sớm nhất. Chúng tôi sẽ thông báo ngay khi dịch vụ sẵn sàng sử dụng.</p>

        <div class="tracking-info">
            <p><strong>Theo dõi tiến độ:</strong></p>
            <p>Bạn có thể theo dõi trạng thái yêu cầu qua dashboard khách hàng của mình.</p>
            <p><a href="{{ route('customer.services.provision.show', $provision->id) }}">Xem chi tiết yêu cầu</a></p>
        </div>

        <p>Nếu có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi qua email hoặc hotline.</p>

        <p>Trân trọng,<br>Đội ngũ hỗ trợ {{ config('app.name') }}</p>

        <div class="footer">
            <p>Email này được gửi tự động, vui lòng không trả lời.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Tất cả các quyền được bảo lưu.</p>
        </div>
    </div>
</body>
</html>

{{-- resources/views/emails/provision/completed.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dịch vụ đã sẵn sàng</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
        }
        .container {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #28a745;
            margin: 0;
        }
        .success-box {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin-bottom: 20px;
        }
        .service-info {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .service-info p {
            margin: 5px 0;
        }
        .action-btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
        .security-notice {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
        .service-details {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Dịch vụ sẵn sàng!</h1>
            <p>Dịch vụ của bạn đã được cung cấp thành công</p>
        </div>

        <p>Kính gửi {{ $provision->customer->user->name }},</p>

        <div class="success-box">
            <p><strong>Chúc mừng! Dịch vụ của bạn đã được cung cấp thành công và sẵn sàng sử dụng.</strong></p>
        </div>

        <div class="service-info">
            <p><strong>Mã yêu cầu:</strong> #{{ $provision->id }}</p>
            <p><strong>Loại dịch vụ:</strong> 
                @if($provision->provision_type == 'ssl')
                    SSL Certificate
                @elseif($provision->provision_type == 'domain')
                    Tên miền
                @elseif($provision->provision_type == 'hosting')
                    Hosting Account
                @else
                    {{ ucfirst($provision->provision_type) }}
                @endif
            </p>
            <p><strong>Sản phẩm:</strong> {{ $provision->product->name }}</p>
            <p><strong>Hoàn thành lúc:</strong> {{ $provision->provisioned_at->format('d/m/Y H:i') }}</p>
        </div>

        @if($provision->provision_type == 'ssl')
        <div class="service-details">
            <h3>Thông tin SSL Certificate:</h3>
            <ul>
                <li>Chứng thư số đã được cấp và sẵn sàng cài đặt</li>
                <li>Thời hạn sử dụng: {{ $provision->product->duration ?? 1 }} năm</li>
                <li>Hỗ trợ wildcard subdomain (nếu áp dụng)</li>
                <li>Bảo hành theo tiêu chuẩn quốc tế</li>
            </ul>
        </div>
        @elseif($provision->provision_type == 'hosting')
        <div class="service-details">
            <h3>Thông tin Hosting Account:</h3>
            <ul>
                <li>Control Panel: cPanel</li>
                <li>PHP version: 8.1+</li>
                <li>MySQL database</li>
                <li>SSL miễn phí Let's Encrypt</li>
                <li>Backup tự động hàng ngày</li>
            </ul>
        </div>
        @elseif($provision->provision_type == 'domain')
        <div class="service-details">
            <h3>Thông tin Tên miền:</h3>
            <ul>
                <li>Tên miền đã được đăng ký thành công</li>
                <li>DNS management đầy đủ</li>
                <li>Domain forwarding</li>
                <li>Email forwarding</li>
                <li>Bảo vệ khỏi transfer trái phép</li>
            </ul>
        </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ route('customer.services.provision.show', $provision->id) }}" class="action-btn">
                Xem chi tiết dịch vụ
            </a>
        </div>

        <div class="security-notice">
            <p><strong>Lưu ý bảo mật:</strong></p>
            <p>Vì lý do bảo mật, thông tin đăng nhập và chi tiết kỹ thuật chỉ có thể xem qua dashboard bảo mật của bạn.</p>
        </div>

        <p>Nếu bạn cần hỗ trợ kỹ thuật hoặc có câu hỏi về việc sử dụng dịch vụ, đội ngũ của chúng tôi sẵn sàng hỗ trợ 24/7.</p>

        <p>Cảm ơn bạn đã tin tưởng và sử dụng dịch vụ của chúng tôi!</p>

        <p>Trân trọng,<br>Đội ngũ kỹ thuật {{ config('app.name') }}</p>

        <div class="footer">
            <p>Email này được gửi tự động, vui lòng không trả lời.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Tất cả các quyền được bảo lưu.</p>
        </div>
    </div>
</body>
</html>

{{-- resources/views/emails/provision/failed.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Thông báo về dịch vụ</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
        }
        .container {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #dc3545;
            margin: 0;
        }
        .error-box {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin-bottom: 20px;
        }
        .service-info {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .service-info p {
            margin: 5px 0;
        }
        .next-steps {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 20px 0;
        }
        .contact-info {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Cập nhật về dịch vụ</h1>
            <p>Thông báo quan trọng về yêu cầu của bạn</p>
        </div>

        <p>Kính gửi {{ $provision->customer->user->name }},</p>

        <div class="error-box">
            <p><strong>Chúng tôi gặp sự cố khi xử lý yêu cầu dịch vụ của bạn và cần thêm thời gian để khắc phục.</strong></p>
        </div>

        <div class="service-info">
            <p><strong>Mã yêu cầu:</strong> #{{ $provision->id }}</p>
            <p><strong>Loại dịch vụ:</strong> 
                @if($provision->provision_type == 'ssl')
                    SSL Certificate
                @elseif($provision->provision_type == 'domain')
                    Đăng ký tên miền
                @elseif($provision->provision_type == 'hosting')
                    Hosting Account
                @else
                    {{ ucfirst($provision->provision_type) }}
                @endif
            </p>
            <p><strong>Sản phẩm:</strong> {{ $provision->product->name }}</p>
            <p><strong>Thời gian gặp sự cố:</strong> {{ now()->format('d/m/Y H:i') }}</p>
            @if($provision->failure_reason)
            <p><strong>Chi tiết:</strong> {{ $provision->failure_reason }}</p>
            @endif
        </div>

        <div class="next-steps">
            <p><strong>Các bước tiếp theo:</strong></p>
            <ul>
                <li>Đội ngũ kỹ thuật của chúng tôi đang xem xét và khắc phục sự cố</li>
                <li>Chúng tôi sẽ liên hệ với bạn trong vòng 24 giờ với cập nhật mới</li>
                <li>Không cần thực hiện thêm hành động nào từ phía bạn</li>
            </ul>
        </div>

        @if($provision->provision_type == 'ssl')
        <p>Đối với SSL Certificate, sự cố thường liên quan đến việc xác minh tên miền. Chúng tôi sẽ hướng dẫn bạn các bước cụ thể nếu cần.</p>
        @elseif($provision->provision_type == 'domain')
        <p>Đối với đăng ký tên miền, sự cố có thể do tên miền không khả dụng hoặc cần thêm thông tin xác minh. Chúng tôi sẽ liên hệ với bạn để hoàn tất.</p>
        @elseif($provision->provision_type == 'hosting')
        <p>Đối với hosting account, chúng tôi đang kiểm tra hạ tầng server và sẽ khắc phục trong thời gian sớm nhất.</p>
        @endif

        <div class="contact-info">
            <p><strong>Cần hỗ trợ ngay?</strong></p>
            <p>Nếu bạn cần hỗ trợ khẩn cấp, vui lòng liên hệ:</p>
            <p>📧 Email: {{ config('mail.from.address') }}</p>
            <p>📞 Hotline: 1900-xxxx (24/7)</p>
            <p>💬 Live Chat: Truy cập website và nhấn nút chat</p>
        </div>

        <p>Chúng tôi chân thành xin lỗi về sự bất tiện này và cam kết sẽ khắc phục trong thời gian sớm nhất.</p>

        <p>Trân trọng,<br>Đội ngũ hỗ trợ kỹ thuật {{ config('app.name') }}</p>

        <div class="footer">
            <p>Email này được gửi tự động, vui lòng không trả lời.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Tất cả các quyền được bảo lưu.</p>
        </div>
    </div>
</body>
</html>