<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Biên nhận thanh toán {{ $receiptNumber }}</title>
<style>
    @page { margin: 20px 24px; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 13px;
        color: #1a1a2e;
        line-height: 1.5;
    }
    table { width: 100%; border-collapse: collapse; }

    /* ===== Header ===== */
    .header { border-bottom: 2px solid #2c5aa0; padding-bottom: 10px; }
    .header td { vertical-align: top; }
    .brand { font-size: 19px; font-weight: bold; color: #2c5aa0; text-transform: uppercase; }
    .seller-line { font-size: 11px; color: #555; margin-top: 2px; }
    .doc-title { text-align: right; font-size: 20px; font-weight: bold; letter-spacing: 1px; }
    .doc-sub { text-align: right; font-size: 11px; color: #777; font-style: italic; }
    .doc-meta { text-align: right; font-size: 11.5px; margin-top: 6px; color: #333; }
    .doc-meta .num { font-weight: bold; }

    /* ===== Khối số tiền ===== */
    .paid-box {
        margin-top: 16px;
        border: 2px solid #1e7e34;
        border-radius: 6px;
        background: #f2fbf4;
        padding: 14px 16px;
        text-align: center;
    }
    .paid-badge {
        font-size: 13px;
        font-weight: bold;
        color: #1e7e34;
        letter-spacing: 2px;
        text-transform: uppercase;
    }
    .paid-amount { font-size: 30px; font-weight: bold; color: #1e7e34; margin: 4px 0 2px; }
    .paid-words { font-size: 12px; font-style: italic; color: #444; }

    /* ===== Hai cột thông tin ===== */
    .parties { margin-top: 16px; }
    .parties td { vertical-align: top; width: 50%; padding-right: 14px; }
    .block-title {
        font-size: 10.5px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #777;
        border-bottom: 1px solid #ddd;
        padding-bottom: 3px;
        margin-bottom: 5px;
    }
    .line { margin-bottom: 2px; }
    .line .lbl { color: #666; }
    .strong { font-weight: bold; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 11.5px; }

    /* ===== Bảng dịch vụ ===== */
    .items { margin-top: 16px; }
    .items th {
        background: #2c5aa0;
        color: #fff;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: 7px 8px;
        text-align: left;
    }
    .items td { padding: 8px; border-bottom: 1px solid #e3e3e3; vertical-align: top; }
    .items .c { text-align: center; }
    .items .r { text-align: right; }
    .item-name { font-weight: bold; }
    .item-detail { font-size: 11px; color: #666; }

    /* ===== Tổng ===== */
    .summary { margin-top: 10px; }
    .summary td { padding: 4px 8px; }
    .summary .lbl { text-align: right; color: #444; }
    .summary .val { text-align: right; width: 34%; }
    .summary .grand td {
        border-top: 2px solid #2c5aa0;
        font-size: 15px;
        font-weight: bold;
        color: #1e7e34;
        padding-top: 8px;
    }

    /* ===== Giao dịch ===== */
    .txn { margin-top: 16px; border: 1px solid #ddd; border-radius: 6px; padding: 10px 12px; }
    .txn td { width: 50%; vertical-align: top; padding: 2px 0; }

    /* ===== Ghi chú ===== */
    .note {
        margin-top: 14px;
        border-left: 3px solid #f0ad4e;
        background: #fffaf0;
        padding: 8px 12px;
        font-size: 11.5px;
        color: #6b5312;
    }
    .footer {
        margin-top: 18px;
        border-top: 1px solid #ccc;
        padding-top: 6px;
        text-align: center;
        font-size: 11px;
        color: #666;
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
@endphp

{{-- ===== HEADER ===== --}}
<table class="header">
    <tr>
        <td style="width: 55%;">
            <div class="brand">{{ $companyName }}</div>
            @if ($website)
                <div class="seller-line">{{ $website }}</div>
            @endif
            @foreach ($sellerBits as $bit)
                <div class="seller-line">{{ $bit }}</div>
            @endforeach
        </td>
        <td style="width: 45%;">
            <div class="doc-title">BIÊN NHẬN THANH TOÁN</div>
            <div class="doc-sub">Payment Receipt</div>
            <div class="doc-meta">
                Số: <span class="num mono">{{ $receiptNumber }}</span><br>
                Ngày: <strong>{{ $paidAt->format('d/m/Y') }}</strong>
            </div>
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
<table class="parties">
    <tr>
        <td>
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
        <td style="padding-right: 0;">
            <div class="block-title">Tham chiếu</div>
            <div class="line"><span class="lbl">Đơn hàng:</span> <span class="mono">{{ $orderNumber }}</span></div>
            <div class="line"><span class="lbl">Hóa đơn:</span> <span class="mono">{{ $invoiceNumber }}</span></div>
            <div class="line"><span class="lbl">Ngày đặt:</span> {{ $orderedAt->format('d/m/Y H:i') }}</div>
        </td>
    </tr>
</table>

{{-- ===== KHỐI VAT (chỉ khi khách yêu cầu xuất hóa đơn GTGT) ===== --}}
@if ($vatInvoice)
    <table class="parties">
        <tr>
            <td colspan="2" style="padding-right: 0;">
                <div class="block-title">Đơn vị xuất hóa đơn GTGT</div>
                <div class="line strong">{{ $vatInfo['company_name'] ?: '—' }}</div>
                @if (!empty($vatInfo['tax_code']))
                    <div class="line"><span class="lbl">Mã số thuế:</span> {{ $vatInfo['tax_code'] }}</div>
                @endif
                @if (!empty($vatInfo['address']))
                    <div class="line"><span class="lbl">Địa chỉ:</span> {{ $vatInfo['address'] }}</div>
                @endif
                @if (!empty($vatInfo['email']))
                    <div class="line"><span class="lbl">Email nhận hóa đơn:</span> {{ $vatInfo['email'] }}</div>
                @endif
            </td>
        </tr>
    </table>
@endif

{{-- ===== DỊCH VỤ ===== --}}
<table class="items">
    <thead>
        <tr>
            <th style="width: 5%;" class="c">#</th>
            <th>Dịch vụ</th>
            <th style="width: 15%;" class="c">Thời hạn</th>
            <th style="width: 20%;" class="r">Thành tiền</th>
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
                <td class="c">{{ $row['period'] }}</td>
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
            <td><span class="lbl">Hình thức:</span> <strong>{{ $methodLabel }}</strong></td>
            <td><span class="lbl">Thời điểm:</span> <strong>{{ $paidAt->format('d/m/Y H:i') }}</strong></td>
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
        Chứng từ này xác nhận {{ $companyName }} đã nhận đủ số tiền nêu trên.
        Hóa đơn GTGT sẽ được phát hành riêng qua hệ thống hóa đơn điện tử và gửi tới
        email đăng ký. Biên nhận này <strong>không thay thế hóa đơn GTGT</strong>.
    @else
        Chứng từ này xác nhận {{ $companyName }} đã nhận đủ số tiền nêu trên.
        Đây <strong>không phải hóa đơn GTGT</strong>. Nếu cần xuất hóa đơn GTGT,
        vui lòng liên hệ bộ phận hỗ trợ.
    @endif
</div>

<div class="footer">
    Chứng từ được tạo tự động từ hệ thống, không cần chữ ký và con dấu.<br>
    {{ $companyName }}@if ($config->company_email) &middot; {{ $config->company_email }}@endif
</div>
</body>
</html>
