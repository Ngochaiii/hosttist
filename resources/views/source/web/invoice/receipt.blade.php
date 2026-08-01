<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Biên nhận thanh toán {{ $receiptNumber }}</title>
<style>
    /* Dompdf build này BỎ QUA @page { margin } (thử cả px lẫn mm đều không ăn) —
       lề trang phải đặt bằng margin của body. Ngoài ra dpi=150 khiến 1px = 0.48pt
       nên px không dùng được cho kích thước: dài dùng mm, chữ dùng pt.
       Lề trên/dưới chừa chỗ cho header/footer position:fixed (lặp lại mọi trang). */
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        margin: 30mm 16mm 22mm 16mm;
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 10pt;
        color: #1f2937;
        line-height: 1.55;
    }
    table { width: 100%; border-collapse: collapse; }

    /* ===== HEADER cố định ===== */
    /* Dompdf định vị position:fixed theo mép giấy, không theo vùng nội dung —
       nên dùng offset dương nằm gọn trong phần lề đã chừa. */
    .page-header {
        position: fixed;
        top: 12mm; left: 16mm; right: 16mm;
        height: 16mm;
        border-bottom: 0.6mm solid #2c5aa0;
    }
    .page-header td { vertical-align: top; }
    /* Cỡ chữ phải đủ nhỏ để tên công ty và tiêu đề nằm gọn 1 dòng — xuống dòng
       là tràn khỏi vùng header cố định và đè lên nội dung bên dưới. */
    .brand {
        font-size: 11pt;
        font-weight: bold;
        color: #2c5aa0;
        text-transform: uppercase;
        line-height: 1.2;
    }
    .brand-sub { font-size: 8pt; color: #6b7280; margin-top: 0.8mm; }
    .doc-title {
        text-align: right;
        font-size: 12pt;
        font-weight: bold;
        letter-spacing: 0.15mm;
        color: #111827;
        line-height: 1.2;
    }
    .doc-sub { text-align: right; font-size: 8pt; color: #9ca3af; font-style: italic; }

    /* ===== FOOTER cố định ===== */
    .page-footer {
        position: fixed;
        bottom: 10mm; left: 16mm; right: 16mm;
        height: 12mm;
        border-top: 0.3mm solid #d1d5db;
        padding-top: 2mm;
        text-align: center;
        font-size: 8pt;
        color: #6b7280;
        line-height: 1.5;
    }

    /* ===== Khối tiêu đề phụ: bên bán + số chứng từ ===== */
    .meta-bar { margin-bottom: 3mm; }
    .meta-bar td { vertical-align: top; font-size: 8.5pt; color: #4b5563; }
    .meta-bar .r { text-align: right; }
    .meta-bar .lbl { color: #9ca3af; }
    .meta-bar .num { font-family: 'DejaVu Sans Mono', monospace; font-weight: bold; color: #111827; }

    /* ===== Khối số tiền ===== */
    .paid-box {
        border: 0.5mm solid #1e7e34;
        border-radius: 2mm;
        background: #f2fbf4;
        padding: 3.5mm 6mm 3.5mm;
        text-align: center;
        margin-bottom: 4mm;
    }
    .paid-badge {
        font-size: 9pt;
        font-weight: bold;
        color: #1e7e34;
        letter-spacing: 0.8mm;
        text-transform: uppercase;
    }
    .paid-amount {
        font-size: 18pt;
        font-weight: bold;
        color: #1e7e34;
        line-height: 1.2;
        margin: 1.5mm 0 0.8mm;
    }
    .paid-words { font-size: 9pt; font-style: italic; color: #4b5563; }

    /* ===== Khối thông tin ===== */
    .section { margin-bottom: 3mm; }
    .parties td { vertical-align: top; width: 50%; padding-right: 8mm; }
    .parties td.last { padding-right: 0; }
    .block-title {
        font-size: 7.5pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.35mm;
        color: #9ca3af;
        border-bottom: 0.25mm solid #e5e7eb;
        padding-bottom: 1mm;
        margin-bottom: 2mm;
    }
    .line { margin-bottom: 1mm; }
    .line .lbl { color: #6b7280; }
    .strong { font-weight: bold; color: #111827; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 8.5pt; }

    /* ===== Bảng dịch vụ ===== */
    .items { margin-bottom: 4mm; }
    .items th {
        background: #2c5aa0;
        color: #fff;
        font-size: 8pt;
        text-transform: uppercase;
        letter-spacing: 0.25mm;
        padding: 2.6mm 3mm;
        text-align: left;
    }
    .items td {
        padding: 2mm 3mm;
        border-bottom: 0.25mm solid #e5e7eb;
        vertical-align: top;
    }
    .items .c { text-align: center; }
    .items .r { text-align: right; }
    .item-name { font-weight: bold; color: #111827; }
    .item-detail { font-size: 8.5pt; color: #6b7280; margin-top: 0.5mm; }

    /* ===== Tổng ===== */
    .summary { margin-bottom: 4mm; }
    .summary td { padding: 1.2mm 3mm; }
    .summary .lbl { text-align: right; color: #4b5563; }
    .summary .val { text-align: right; width: 36%; }
    .summary .grand td {
        border-top: 0.5mm solid #2c5aa0;
        font-size: 12pt;
        font-weight: bold;
        color: #1e7e34;
        padding-top: 3mm;
    }

    /* ===== Giao dịch ===== */
    .txn {
        border: 0.25mm solid #e5e7eb;
        border-radius: 2mm;
        padding: 2.8mm 5mm;
        margin-bottom: 3mm;
    }
    .txn table td { width: 50%; vertical-align: top; padding: 0.8mm 0; }

    /* ===== Ghi chú ===== */
    .note {
        border-left: 1mm solid #f0ad4e;
        background: #fffaf0;
        padding: 2.5mm 4mm;
        font-size: 8pt;
        color: #7a5c11;
        line-height: 1.45;
    }
</style>
</head>
<body>
@php
    $companyName = $config->company_name ?: ($config->site_name ?? 'Công ty chúng tôi');
    $website     = $config->url ? preg_replace('#^https?://#', '', rtrim($config->url, '/')) : null;
    $sellerBits  = array_filter([
        $config->company_tax_code ? 'MST: ' . $config->company_tax_code : null,
        $config->company_address,
        $config->company_phone ? 'ĐT: ' . $config->company_phone : null,
        $config->company_email,
    ]);
    $footerBits  = array_filter([$companyName, $website, $config->company_phone, $config->company_email]);
@endphp

{{-- ===== HEADER (lặp lại mọi trang) ===== --}}
<div class="page-header">
    <table>
        <tr>
            <td style="width: 58%;">
                <div class="brand">{{ $companyName }}</div>
                @if ($website)
                    <div class="brand-sub">{{ $website }}</div>
                @endif
            </td>
            <td style="width: 42%;">
                <div class="doc-title">BIÊN NHẬN THANH TOÁN</div>
                <div class="doc-sub">Payment Receipt</div>
            </td>
        </tr>
    </table>
</div>

{{-- ===== FOOTER (lặp lại mọi trang) ===== --}}
<div class="page-footer">
    Chứng từ được tạo tự động từ hệ thống, không cần chữ ký và con dấu.<br>
    {{ implode(' · ', $footerBits) }}
</div>

{{-- ===== BÊN BÁN + SỐ CHỨNG TỪ ===== --}}
<table class="meta-bar">
    <tr>
        <td style="width: 58%;">
            @foreach ($sellerBits as $bit)
                <div>{{ $bit }}</div>
            @endforeach
        </td>
        <td style="width: 42%;" class="r">
            <div><span class="lbl">Số biên nhận:</span> <span class="num">{{ $receiptNumber }}</span></div>
            <div><span class="lbl">Ngày:</span> <span class="strong">{{ $paidAt->format('d/m/Y') }}</span></div>
        </td>
    </tr>
</table>

{{-- ===== SỐ TIỀN ĐÃ THANH TOÁN ===== --}}
<div class="paid-box">
    <div class="paid-badge">&#10003; Đã thanh toán</div>
    <div class="paid-amount">{{ number_format($total, 0, ',', '.') }} VNĐ</div>
    <div class="paid-words">{{ $totalInWords }}</div>
</div>

{{-- ===== KHÁCH HÀNG / THAM CHIẾU ===== --}}
{{-- Có VAT thì xếp 3 cột để khối "đơn vị xuất hóa đơn" không tốn thêm một
     section riêng — bản VAT vốn dài, thêm section là tràn sang trang 2. --}}
<table class="parties section">
    <tr>
        <td style="width: {{ $vatInvoice ? '34%' : '50%' }};">
            <div class="block-title">Khách hàng</div>
            <div class="line strong">{{ $customerName }}</div>
            @if ($customerEmail)
                <div class="line">{{ $customerEmail }}</div>
            @endif
            @if ($customerPhone)
                <div class="line"><span class="lbl">Điện thoại:</span> {{ $customerPhone }}</div>
            @endif
            @if ($customerAddress)
                <div class="line"><span class="lbl">Địa chỉ:</span> {{ $customerAddress }}</div>
            @endif
        </td>
        <td style="width: {{ $vatInvoice ? '33%' : '50%' }};" @class(['last' => !$vatInvoice])>
            <div class="block-title">Tham chiếu</div>
            <div class="line"><span class="lbl">Đơn hàng:</span> <span class="mono">{{ $orderNumber }}</span></div>
            <div class="line"><span class="lbl">Hóa đơn:</span> <span class="mono">{{ $invoiceNumber }}</span></div>
            <div class="line"><span class="lbl">Ngày đặt:</span> {{ $orderedAt->format('d/m/Y H:i') }}</div>
        </td>
        @if ($vatInvoice)
            <td style="width: 33%;" class="last">
                <div class="block-title">Đơn vị xuất hóa đơn GTGT</div>
                <div class="line strong">{{ $vatInfo['company_name'] ?: '—' }}</div>
                @if (!empty($vatInfo['tax_code']))
                    <div class="line"><span class="lbl">MST:</span> {{ $vatInfo['tax_code'] }}</div>
                @endif
                @if (!empty($vatInfo['address']))
                    <div class="line"><span class="lbl">Địa chỉ:</span> {{ $vatInfo['address'] }}</div>
                @endif
                @if (!empty($vatInfo['email']))
                    <div class="line"><span class="lbl">Email nhận HĐ:</span> {{ $vatInfo['email'] }}</div>
                @endif
            </td>
        @endif
    </tr>
</table>

{{-- ===== DỊCH VỤ ===== --}}
<div class="block-title">Dịch vụ đã thanh toán</div>
<table class="items">
    <thead>
        <tr>
            <th style="width: 6%;" class="c">#</th>
            <th>Dịch vụ / Tên miền</th>
            <th style="width: 14%;" class="c">Thời hạn</th>
            <th style="width: 17%;" class="c">Hết hạn</th>
            <th style="width: 21%;" class="r">Thành tiền</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $i => $row)
            <tr>
                <td class="c">{{ $i + 1 }}</td>
                <td>
                    <div class="item-name">{{ $row['name'] }}</div>
                    @if (!empty($row['domain']))
                        <div class="item-detail">Tên miền: {{ $row['domain'] }}</div>
                    @endif
                </td>
                <td class="c">{{ $row['period'] }}</td>
                <td class="c">{{ $row['expiresAt'] ? $row['expiresAt']->format('d/m/Y') : '—' }}</td>
                <td class="r">{{ number_format($row['lineTotal'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="summary">
    @if ($vatInvoice || $discount > 0)
        <tr>
            <td class="lbl">Tiền dịch vụ (chưa gồm thuế GTGT)</td>
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
                <td class="lbl">Thuế GTGT ({{ (int) round($vatRate * 100) }}%)</td>
                <td class="val">{{ number_format($vat, 0, ',', '.') }} VNĐ</td>
            </tr>
        @endif
    @endif
    <tr class="grand">
        <td class="lbl">TỔNG ĐÃ THANH TOÁN</td>
        <td class="val">{{ number_format($total, 0, ',', '.') }} VNĐ</td>
    </tr>
</table>

{{-- ===== THÔNG TIN GIAO DỊCH ===== --}}
<div class="txn">
    <div class="block-title">Thông tin giao dịch</div>
    <table>
        <tr>
            <td><span class="lbl">Hình thức:</span> <span class="strong">{{ $methodLabel }}</span></td>
            <td><span class="lbl">Thời điểm:</span> <span class="strong">{{ $paidAt->format('d/m/Y H:i') }}</span></td>
        </tr>
        <tr>
            <td><span class="lbl">Mã giao dịch:</span> <span class="mono">{{ $txnCode }}</span></td>
            <td>
                @if ($verifiedAt)
                    <span class="lbl">Xác nhận lúc:</span> {{ $verifiedAt->format('d/m/Y H:i') }}
                @endif
            </td>
        </tr>
    </table>
</div>

{{-- ===== GHI CHÚ ===== --}}
<div class="note">
    @if ($vatInvoice)
        Xác nhận {{ $companyName }} đã nhận đủ số tiền nêu trên. Biên nhận này
        <strong>không thay thế hóa đơn GTGT</strong> — hóa đơn GTGT phát hành riêng
        qua hệ thống hóa đơn điện tử và gửi tới email đăng ký.
    @else
        Chứng từ này xác nhận {{ $companyName }} đã nhận đủ số tiền nêu trên.
        Đây <strong>không phải hóa đơn GTGT</strong>. Nếu cần xuất hóa đơn GTGT,
        vui lòng liên hệ bộ phận hỗ trợ.
    @endif
</div>
</body>
</html>
