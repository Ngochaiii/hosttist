<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Báo giá {{ $quoteNumber }}</title>
<style>
    @page { margin: 18px 22px; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 13.5px;
        color: #1a1a2e;
        line-height: 1.5;
    }
    /* Khung viền ngoài kiểu hoá đơn — min-height để viền phủ kín trang A4 */
    .frame {
        border: 3px double #2c5aa0;
        padding: 14px 16px;
        min-height: 1660px; /* ~cao A4 với dpi 150 của dompdf */
        position: relative;
    }
    table { width: 100%; border-collapse: collapse; }

    /* ===== Header ===== */
    .header td { vertical-align: top; }
    .brand {
        font-size: 20px;
        font-weight: bold;
        color: #2c5aa0;
        text-transform: uppercase;
    }
    .brand-sub { font-size: 11px; color: #666; }
    .doc-title {
        text-align: center;
        font-size: 28px;
        font-weight: bold;
        letter-spacing: 3px;
        color: #1a1a2e;
    }
    .doc-date { text-align: center; font-style: italic; font-size: 13px; margin-top: 3px; }
    .doc-meta { font-size: 12.5px; text-align: left; }
    .doc-meta .num { color: #c0392b; font-weight: bold; }

    /* ===== Khối bên bán / bên mua ===== */
    .party {
        border-top: 1px solid #444;
        padding: 9px 0;
    }
    .party-last { border-bottom: 1px solid #444; }
    .party .name {
        font-size: 16px;
        font-weight: bold;
        text-transform: uppercase;
        color: #1a1a2e;
    }
    .party td { vertical-align: top; }
    .party .line { padding: 2px 0; }
    .party .lbl { color: #444; }

    /* ===== Bảng hàng hoá ===== */
    .items { margin-top: 14px; }
    .items th {
        border: 1px solid #444;
        background: #eef3fa;
        padding: 8px 6px;
        font-size: 12.5px;
        text-align: center;
    }
    .items td {
        border: 1px solid #444;
        padding: 8px 6px;
        vertical-align: top;
    }
    .items .c { text-align: center; }
    .items .r { text-align: right; }
    .item-name { font-weight: bold; }
    .item-detail { color: #555; font-size: 11px; padding-top: 3px; }

    /* ===== Tổng hợp ===== */
    .summary { margin-top: -1px; }
    .summary td {
        border: 1px solid #444;
        padding: 8px 9px;
    }
    .summary .lbl { width: 55%; }
    .summary .val { text-align: right; }
    .summary .grand td {
        font-weight: bold;
        font-size: 15.5px;
        background: #eef3fa;
    }
    .in-words {
        border: 1px solid #444;
        border-top: none;
        padding: 8px 9px;
        font-size: 13.5px;
    }
    .in-words em { font-weight: bold; }

    /* ===== Thanh toán / điều khoản ===== */
    .pay-box {
        margin-top: 14px;
        border: 1px solid #2c5aa0;
        padding: 10px 12px;
    }
    .pay-box .title {
        font-weight: bold;
        color: #2c5aa0;
        text-transform: uppercase;
        font-size: 14px;
        margin-bottom: 6px;
    }
    .pay-box .line { padding: 3px 0; }
    /* QR to, giữ nguyên tỷ lệ ảnh gốc (chỉ khoá chiều rộng) để không méo mã */
    .pay-qr { width: 250px; text-align: center; vertical-align: middle; }
    .pay-qr img { width: 240px; height: auto; }
    .terms { margin-top: 12px; font-size: 12px; color: #444; }
    .terms .title { font-weight: bold; color: #1a1a2e; font-size: 13px; }
    .terms ol { margin: 4px 0 0 18px; }
    .terms li { margin-bottom: 2px; }

    /* ===== Chữ ký ===== */
    .signs { margin-top: 24px; }
    .signs td { width: 50%; text-align: center; vertical-align: top; }
    .signs .role { font-weight: bold; font-size: 14px; }
    .signs .hint { font-style: italic; font-size: 11.5px; color: #555; }
    .footer {
        position: absolute;
        bottom: 12px;
        left: 16px;
        right: 16px;
        border-top: 1px solid #999;
        padding-top: 6px;
        text-align: center;
        font-size: 11px;
        color: #555;
    }
</style>
</head>
<body>
@php
    $companyName  = $config->company_name ?: ($config->site_name ?? 'Công ty chúng tôi');
    $sellerEmail  = $config->company_email;
    $sellerPhone  = $config->company_phone;
    $website      = $config->url ? preg_replace('#^https?://#', '', rtrim($config->url, '/')) : null;
    $now          = \Carbon\Carbon::now();
@endphp
<div class="frame">

    {{-- ===== HEADER ===== --}}
    <table class="header">
        <tr>
            <td style="width: 28%;">
                <div class="brand">{{ $companyName }}</div>
                @if ($website)
                    <div class="brand-sub">{{ $website }}</div>
                @endif
            </td>
            <td style="width: 44%;">
                <div class="doc-title">{{ $docTitle ?? 'BÁO GIÁ' }}</div>
                <div class="doc-date">Ngày {{ $now->format('d') }} tháng {{ $now->format('m') }} năm {{ $now->format('Y') }}</div>
            </td>
            <td style="width: 28%;">
                <div class="doc-meta">
                    Số: <span class="num">{{ $quoteNumber }}</span><br>
                    Hiệu lực đến: <strong>{{ $expireDate }}</strong>
                </div>
            </td>
        </tr>
    </table>

    {{-- ===== BÊN BÁN ===== --}}
    <table class="party" style="margin-top: 8px;">
        <tr>
            <td>
                <div class="name">{{ $companyName }}</div>
                @if ($config->company_tax_code)
                    <div class="line"><span class="lbl">Mã số thuế:</span> {{ $config->company_tax_code }}</div>
                @endif
                @if ($config->company_address)
                    <div class="line"><span class="lbl">Địa chỉ:</span> {{ $config->company_address }}</div>
                @endif
                @if ($sellerPhone)
                    <div class="line"><span class="lbl">Điện thoại:</span> {{ $sellerPhone }}</div>
                @endif
                @if ($sellerEmail)
                    <div class="line"><span class="lbl">Email:</span> {{ $sellerEmail }}</div>
                @endif
                @if (!empty($bank['account_number']))
                    <div class="line">
                        <span class="lbl">Số tài khoản:</span>
                        {{ $bank['account_number'] }} - {{ $bank['name'] }}{{ !empty($bank['branch']) ? ' - ' . $bank['branch'] : '' }}
                    </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- ===== BÊN MUA ===== --}}
    <table class="party party-last">
        <tr>
            <td>
                <div class="line"><span class="lbl">Kính gửi:</span> <strong>{{ $user->name ?? 'Quý khách hàng' }}</strong></div>
                @if (!empty($user->email))
                    <div class="line"><span class="lbl">Email:</span> {{ $user->email }}</div>
                @endif
                <div class="line">
                    <span class="lbl">Hình thức thanh toán:</span>
                    @if ($vatInvoice)
                        <strong>Chuyển khoản — xuất hoá đơn GTGT (thuế suất {{ (int) round($vatRate * 100) }}%)</strong>
                    @else
                        Chuyển khoản / Ví — thanh toán thường (không xuất hoá đơn GTGT)
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- ===== BẢNG HÀNG HOÁ, DỊCH VỤ ===== --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width: 5%;">STT</th>
                <th>Tên hàng hóa, dịch vụ</th>
                <th style="width: 9%;">Đơn vị<br>tính</th>
                <th style="width: 8%;">Số<br>lượng</th>
                <th style="width: 13%;">Đơn giá<br>(VNĐ)</th>
                <th style="width: 13%;">Thành tiền<br>(VNĐ)</th>
                @if ($vatInvoice)
                    <th style="width: 8%;">Thuế suất<br>GTGT</th>
                    <th style="width: 12%;">Tiền thuế GTGT<br>(VNĐ)</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $i => $row)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>
                        <div class="item-name">{{ $row['name'] }}</div>
                        @if (!empty($row['detail']))
                            <div class="item-detail">{{ $row['detail'] }}</div>
                        @endif
                    </td>
                    <td class="c">{{ $row['unit'] }}</td>
                    <td class="c">{{ $row['qty'] }}</td>
                    <td class="r">{{ number_format($row['unitPrice'], 0, ',', '.') }}</td>
                    <td class="r">{{ number_format($row['lineTotal'], 0, ',', '.') }}</td>
                    @if ($vatInvoice)
                        <td class="c">{{ (int) round($vatRate * 100) }}%</td>
                        <td class="r">{{ number_format(round($row['lineTotal'] * $vatRate), 0, ',', '.') }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ===== TỔNG HỢP ===== --}}
    <table class="summary">
        <tr>
            <td class="lbl">Cộng tiền hàng, dịch vụ (chưa gồm thuế GTGT)</td>
            <td class="val">{{ number_format($subtotal, 0, ',', '.') }} VNĐ</td>
        </tr>
        @if ($discount > 0)
            <tr>
                <td class="lbl">Chiết khấu</td>
                <td class="val">-{{ number_format($discount, 0, ',', '.') }} VNĐ</td>
            </tr>
        @endif
        @if ($vatInvoice)
            <tr>
                <td class="lbl">Tiền thuế GTGT (thuế suất {{ (int) round($vatRate * 100) }}%)</td>
                <td class="val">{{ number_format($vat, 0, ',', '.') }} VNĐ</td>
            </tr>
        @endif
        <tr class="grand">
            <td class="lbl">TỔNG CỘNG TIỀN THANH TOÁN</td>
            <td class="val">{{ number_format($total, 0, ',', '.') }} VNĐ</td>
        </tr>
    </table>
    <div class="in-words">
        Số tiền viết bằng chữ: <em>{{ $totalInWords }}</em>
    </div>

    {{-- ===== THÔNG TIN CHUYỂN KHOẢN ===== --}}
    @if (!empty($bank['account_number']))
        <div class="pay-box">
            <div class="title">Thông tin chuyển khoản</div>
            <table>
                <tr>
                    <td style="vertical-align: middle;">
                        <div class="line">Ngân hàng: <strong>{{ $bank['name'] }}</strong></div>
                        <div class="line">Số tài khoản: <strong>{{ $bank['account_number'] }}</strong></div>
                        <div class="line">Chủ tài khoản: <strong>{{ $bank['account_name'] }}</strong></div>
                        <div class="line">Nội dung chuyển khoản: <strong>THANH TOAN {{ $quoteNumber }}</strong></div>
                        @if (!empty($qrBase64))
                            <div class="line" style="color: #555;">Hoặc quét mã QR bên cạnh để chuyển khoản nhanh.</div>
                        @endif
                    </td>
                    @if (!empty($qrBase64))
                        <td class="pay-qr"><img src="{{ $qrBase64 }}" alt="QR thanh toán"></td>
                    @endif
                </tr>
            </table>
        </div>
    @endif

    {{-- ===== ĐIỀU KHOẢN ===== --}}
    @php
        // Template dùng chung cho BÁO GIÁ và ĐỀ NGHỊ THANH TOÁN — nhãn bám theo $docTitle
        // để bản đề nghị thanh toán không còn tự xưng là "báo giá".
        $docWord = mb_strtolower($docTitle ?? 'BÁO GIÁ');
        $docWordUc = mb_strtoupper(mb_substr($docWord, 0, 1)) . mb_substr($docWord, 1);
    @endphp
    <div class="terms">
        <div class="title">Điều khoản {{ $docWord }}</div>
        <ol>
            <li>{{ $docWordUc }} có hiệu lực đến hết ngày {{ $expireDate }}.</li>
            @if ($vatInvoice)
                <li>Đơn giá chưa bao gồm thuế GTGT; tổng cộng thanh toán đã bao gồm thuế GTGT {{ (int) round($vatRate * 100) }}%. Hoá đơn GTGT sẽ được phát hành sau khi thanh toán được xác nhận.</li>
            @else
                <li>Giá trên áp dụng cho hình thức thanh toán thường, không xuất hoá đơn GTGT.</li>
            @endif
            <li>Dịch vụ được kích hoạt sau khi thanh toán được xác nhận.</li>
            <li>{{ $docWordUc }} này không thay thế hoá đơn tài chính.</li>
        </ol>
    </div>

    {{-- ===== CHỮ KÝ ===== --}}
    <table class="signs">
        <tr>
            <td>
                <div class="role">Xác nhận của khách hàng</div>
                <div class="hint">(Ký, ghi rõ họ tên)</div>
            </td>
            <td>
                <div class="role">Người lập {{ $docWord }}</div>
                <div class="hint">(Ký, ghi rõ họ tên)</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        {{ $companyName }}@if ($website) | {{ $website }}@endif @if ($sellerPhone) | {{ $sellerPhone }}@endif @if ($sellerEmail) | {{ $sellerEmail }}@endif
        <br>Trân trọng cảm ơn Quý khách đã quan tâm tới dịch vụ của chúng tôi!
    </div>
</div>
</body>
</html>
