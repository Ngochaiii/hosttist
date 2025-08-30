<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo Giá Dịch Vụ</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            background: white;
            color: #333;
        }

        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 20px;
            background: white;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            position: relative;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo {
            width: 60px;
            height: 40px;
            background: linear-gradient(45deg, #ff6b35, #4dabf7, #69db7c);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 8px;
            text-align: center;
            border-radius: 4px;
        }

        .company-info {
            font-size: 14px;
            font-weight: bold;
            color: #4dabf7;
        }

        .stamp {
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 120px;
            border: 3px solid #e74c3c;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: #e74c3c;
            font-weight: bold;
            text-align: center;
            background: rgba(255, 255, 255, 0.9);
        }

        .quote-title {
            position: absolute;
            top: 0;
            right: 0;
            text-align: right;
        }

        .quote-title h1 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .quote-date {
            font-size: 12px;
            color: #666;
        }

        .company-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 40px 0 20px 0;
        }

        .company-box {
            border: 1px solid #ddd;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }

        .company-box h3 {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
            background: #e9ecef;
            padding: 5px;
            text-align: center;
            border-radius: 2px;
        }

        .company-details-content {
            font-size: 10px;
            line-height: 1.6;
        }

        .quotation-content {
            margin-top: 20px;
        }

        .section-title {
            background: #6c757d;
            color: white;
            padding: 8px;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 10px;
            text-align: center;
            border-radius: 4px;
        }

        .quotation-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 20px;
        }

        .quotation-table th,
        .quotation-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            vertical-align: top;
        }

        .quotation-table th {
            background: #f8f9fa;
            font-weight: bold;
            font-size: 9px;
        }

        .quotation-table td:first-child {
            text-align: left;
        }

        .item-details {
            text-align: left;
            font-size: 9px;
            line-height: 1.5;
        }

        .price-column {
            text-align: right;
            font-weight: bold;
        }

        .total-section {
            background: #f8f9fa;
            border: 1px solid #ddd;
        }

        .total-row {
            background: #e9ecef;
        }

        .payment-info {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            background-color: #f8f9fa;
            padding: 15px;
            border: 1px solid #e9ecef;
            margin: 20px 0;
            border-radius: 4px;
        }

        .payment-details {
            flex: 1;
        }

        .payment-details table {
            margin: 0;
            width: 100%;
        }

        .payment-details td {
            border: none;
            padding: 8px 0;
        }

        .qr-section {
            flex: 0 0 200px;
            text-align: center;
        }

        .qr-code {
            width: 150px;
            height: 150px;
            margin: 0 auto;
        }

        .qr-code img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 11px;
            color: #666;
        }

        @media print {
            .container {
                max-width: none;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-section">
                <div class="logo">LOGO</div>
                <div>
                    <div class="company-info">{{ $config->company_name ?? 'CÔNG TY CỦA BẠN' }}</div>
                    <div style="font-size: 10px; color: #666;">Technology Solutions</div>
                </div>
            </div>
            
            @if($config && $config->company_tax_code)
            <div class="stamp">
                <div>MST: {{ $config->company_tax_code }}</div>
                <div style="margin: 5px 0;">★★★</div>
                <div>{{ strtoupper($config->company_name ?? 'CÔNG TY') }}</div>
            </div>
            @endif

            <div class="quote-title">
                <h1>
                    @if(isset($invoice))
                        HÓA ĐƠN
                    @else
                        BÁO GIÁ
                    @endif
                </h1>
                <div class="quote-date">
                    @if(isset($invoice))
                        MÃ: {{ $invoice->invoice_number }}<br>
                    @else
                        MÃ: {{ $quoteNumber }}<br>
                    @endif
                    NGÀY TẠO: {{ $quoteDate }}<br>
                    HẾT HẠN: {{ $expireDate }}
                </div>
            </div>
        </div>

        <div class="company-details">
            <div class="company-box">
                <h3>BÊN CUNG CẤP DỊCH VỤ</h3>
                <div class="company-details-content">
                    <strong>{{ $config->company_name ?? 'CÔNG TY TNHH DỊCH VỤ' }}</strong><br>
                    @if($config->company_address)
                        Địa chỉ: {{ $config->company_address }}<br>
                    @endif
                    @if($config->support_phone)
                        Điện thoại: {{ $config->support_phone }}<br>
                    @endif
                    @if($config->support_email)
                        Email: {{ $config->support_email }}<br>
                    @endif
                    @if($config->company_website)
                        Website: {{ $config->company_website }}
                    @endif
                </div>
            </div>

            <div class="company-box">
                <h3>KHÁCH HÀNG</h3>
                <div class="company-details-content">
                    <strong>{{ $user->name ?? 'KHÁCH HÀNG' }}</strong><br>
                    @if($user->customer)
                        @if($user->customer->company_name)
                            Công ty: {{ $user->customer->company_name }}<br>
                        @endif
                        @if($user->customer->address)
                            Địa chỉ: {{ $user->customer->address }}<br>
                        @endif
                    @endif
                    @if($user->phone)
                        Điện thoại: {{ $user->phone }}<br>
                    @endif
                    Email: {{ $user->email }}
                </div>
            </div>
        </div>

        <div class="quotation-content">
            <div class="section-title">
                NỘI DUNG: 
                @if(isset($invoice))
                    CHI TIẾT HÓA ĐƠN DỊCH VỤ
                @else
                    BÁO GIÁ DỊCH VỤ
                @endif
            </div>

            <table class="quotation-table">
                <thead>
                    <tr>
                        <th style="width: 60%;">SẢN PHẨM / DỊCH VỤ</th>
                        <th style="width: 15%; text-align: center;">THỜI HẠN</th>
                        <th style="width: 25%; text-align: right;">THÀNH TIỀN (VNĐ)</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $items = isset($invoice) ? $invoice->order->items : $cart->items;
                    @endphp
                    
                    @foreach($items as $item)
                        @php
                            $options = json_decode($item->options, true) ?: [];
                            $period = $options['period'] ?? $item->duration ?? 1;
                            $productName = isset($item->product) ? $item->product->name : ($item->name ?? 'Sản phẩm');
                            $domain = $item->domain ?? ($options['domain'] ?? null);
                            
                            // Get product details
                            $productDetails = [];
                            if(isset($item->product)) {
                                if($item->product->type == 'ssl') {
                                    $productDetails[] = "• Domain: " . ($domain ?: 'www.domain.com');
                                    $productDetails[] = "• Loại SSL: " . $item->product->name;
                                    $productDetails[] = "• Thời hạn: $period năm";
                                }
                                elseif($item->product->type == 'hosting') {
                                    $productDetails[] = "• Domain: " . ($domain ?: 'www.nissan.net');
                                    $productDetails[] = "• Dung lượng: " . ($item->product->disk_space ?? '10GB') . " SSD";
                                    $productDetails[] = "• Băng thông: " . ($item->product->bandwidth ?? 'Unlimited');
                                    $productDetails[] = "• Email: " . ($item->product->email_accounts ?? '50') . " tài khoản";
                                    $productDetails[] = "• Database: " . ($item->product->databases ?? '10') . " MySQL";
                                    $productDetails[] = "• Control Panel: cPanel";
                                }
                                elseif($item->product->type == 'vps') {
                                    $productDetails[] = "• CPU: " . ($item->product->cpu_cores ?? '2') . " vCPU";
                                    $productDetails[] = "• RAM: " . ($item->product->ram ?? '4') . "GB";
                                    $productDetails[] = "• SSD: " . ($item->product->disk_space ?? '80') . "GB";
                                    $productDetails[] = "• Băng thông: " . ($item->product->bandwidth ?? '2TB');
                                    $productDetails[] = "• IP: 1 IPv4";
                                    $productDetails[] = "• OS: " . ($item->product->os ?? 'CentOS/Ubuntu');
                                }
                            }
                        @endphp
                        <tr>
                            <td class="item-details">
                                <strong>{{ $productName }}</strong><br>
                                @if(!empty($productDetails))
                                    <div style="margin-top: 5px; color: #666; font-size: 9px; line-height: 1.4;">
                                        {!! implode('<br>', $productDetails) !!}
                                    </div>
                                @elseif($domain)
                                    <div style="margin-top: 5px; color: #666; font-size: 9px;">
                                        • Domain: {{ $domain }}
                                    </div>
                                @endif
                            </td>
                            <td style="text-align: center;">{{ $period }} năm</td>
                            <td class="price-column">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    
                    <tr class="total-section">
                        <td colspan="2" style="text-align: right; font-weight: bold;">Tổng cộng</td>
                        <td class="price-column">{{ number_format($subtotal, 0, ',', '.') }}</td>
                    </tr>
                    
                    @if($vatAmount > 0)
                    <tr class="total-section">
                        <td colspan="2" style="text-align: right;">Thuế VAT {{ $vatRate }}%</td>
                        <td class="price-column">{{ number_format($vatAmount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    
                    <tr class="total-row">
                        <td colspan="2" style="text-align: right; font-weight: bold; font-size: 11px;">TỔNG THANH TOÁN</td>
                        <td class="price-column" style="font-weight: bold; font-size: 11px;">{{ number_format($total, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="section-title">THÔNG TIN THANH TOÁN</div>

            <div class="payment-info">
                <div class="payment-details">
                    <table>
                        <tr>
                            <td style="width: 35%; font-weight: bold;">Số tiền:</td>
                            <td style="color: #dc3545; font-weight: bold; font-size: 14px;">
                                {{ number_format($total, 0, ',', '.') }} VNĐ
                            </td>
                        </tr>
                        @if($config->company_bank_name)
                        <tr>
                            <td style="font-weight: bold;">Ngân hàng:</td>
                            <td>{{ $config->company_bank_name }}</td>
                        </tr>
                        @endif
                        @if($config->company_bank_account_number)
                        <tr>
                            <td style="font-weight: bold;">Số tài khoản:</td>
                            <td style="font-weight: bold; color: #007bff;">{{ $config->company_bank_account_number }}</td>
                        </tr>
                        @endif
                        @if($config->company_bank_account_name)
                        <tr>
                            <td style="font-weight: bold;">Chủ tài khoản:</td>
                            <td>{{ $config->company_bank_account_name }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td style="font-weight: bold;">Nội dung CK:</td>
                            <td style="color: #28a745; font-weight: bold;">
                                @if(isset($invoice))
                                    ThanhToan{{ $invoice->invoice_number }}
                                @else
                                    {{ $quoteNumber }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">Hạn thanh toán:</td>
                            <td style="color: #dc3545; font-weight: bold;">{{ $expireDate }}</td>
                        </tr>
                    </table>
                </div>

                @if(isset($qrBase64))
                <div class="qr-section">
                    <div class="qr-code">
                        <img src="{{ $qrBase64 }}" alt="QR Code thanh toán">
                    </div>
                    <div style="margin-top: 10px; font-size: 10px; color: #666;">
                        Quét mã QR để thanh toán nhanh
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="footer">
            <p style="margin: 5px 0;"><strong>Cảm ơn quý khách đã tin tưởng dịch vụ của chúng tôi!</strong></p>
            @if($config->support_email || $config->support_phone)
            <p style="margin: 5px 0;">
                Mọi thắc mắc xin liên hệ: 
                {{ $config->support_email ?? '' }} 
                @if($config->support_phone)
                    | {{ $config->support_phone }}
                @endif
            </p>
            @endif
            <p style="margin: 5px 0;">
                @if(isset($invoice))
                    Hóa đơn này có hiệu lực đến ngày {{ $expireDate }}
                @else
                    Báo giá này có hiệu lực đến ngày {{ $expireDate }}
                @endif
            </p>
        </div>
    </div>
</body>
</html>