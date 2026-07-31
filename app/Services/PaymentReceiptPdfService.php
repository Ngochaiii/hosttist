<?php

namespace App\Services;

use App\Models\Config;
use App\Models\Invoices;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

/**
 * Dựng PDF BIÊN NHẬN THANH TOÁN (view: source.web.invoice.receipt).
 *
 * Khác hẳn QuotePdfService: đây là chứng từ xác nhận ĐÃ nhận tiền, nên không có
 * hiệu lực báo giá, không có thông tin chuyển khoản/QR và không có ô ký.
 * Chỉ dùng cho invoice đã ở trạng thái 'paid'.
 */
class PaymentReceiptPdfService
{
    public function build(Invoices $invoice)
    {
        $order   = $invoice->order;
        // Lấy payment đã hoàn tất mới nhất — đó là giao dịch tạo ra biên nhận này.
        $payment = $order?->payments
            ->where('status', 'completed')
            ->sortByDesc('payment_date')
            ->first();

        $customer   = $order?->customer;
        $vatInvoice = (bool) $invoice->vat_invoice_requested;
        $subtotal   = (float) $invoice->subtotal;
        $vat        = (float) $invoice->tax_amount;

        $rows = [];
        foreach ($order?->items ?? [] as $item) {
            $options = json_decode($item->options, true) ?: [];
            $domain  = $options['domain'] ?? $item->domain ?? null;
            $period  = \App\Helpers\ServiceHelper::orderItemDurationYears($item);

            $rows[] = [
                'name'      => $item->name ?? $item->product->name ?? 'Dịch vụ',
                'detail'    => $domain ?: '',
                'period'    => $period ? $period . ' năm' : '—',
                'lineTotal' => (float) ($item->subtotal ?? ($item->price * $item->quantity)),
            ];
        }

        // Thời điểm thanh toán: ưu tiên payment_date, lùi dần về mốc gần đúng nhất.
        $paidAt = $payment?->payment_date
            ?? $payment?->verified_at
            ?? $invoice->updated_at
            ?? Carbon::now();

        $total        = (float) $invoice->total_amount;
        $totalInWords = app(QuotePdfService::class)->numberToWords($total) . ' đồng';
        $totalInWords = mb_strtoupper(mb_substr($totalInWords, 0, 1)) . mb_substr($totalInWords, 1);

        $pdf = Pdf::loadView('source.web.invoice.receipt', [
            'config'          => Config::current(),
            'receiptNumber'   => $payment?->payment_number ?? $invoice->invoice_number,
            'invoiceNumber'   => $invoice->invoice_number,
            'orderNumber'     => $order?->order_number ?? '—',
            'orderedAt'       => $order?->created_at ?? $paidAt,
            'paidAt'          => Carbon::parse($paidAt),
            'verifiedAt'      => $payment?->verified_at ? Carbon::parse($payment->verified_at) : null,
            'txnCode'         => $payment?->transaction_id ?? $payment?->payment_number ?? '—',
            'methodLabel'     => $this->methodLabel($payment?->payment_method),
            'customerName'    => $customer?->name ?? $customer?->user?->name ?? 'Quý khách hàng',
            'customerEmail'   => $customer?->email ?? $customer?->user?->email,
            'customerPhone'   => $customer?->phone,
            'customerAddress' => $customer?->address,
            'rows'            => $rows,
            'subtotal'        => $subtotal,
            'discount'        => (float) ($invoice->discount_amount ?? 0),
            'vat'             => $vat,
            'vatRate'         => $vatInvoice && $subtotal > 0 ? round($vat / $subtotal, 2) : 0.0,
            'total'           => $total,
            'totalInWords'    => $totalInWords,
            'vatInvoice'      => $vatInvoice,
            'vatInfo'         => [
                'company_name' => $invoice->vat_company_name,
                'tax_code'     => $invoice->vat_tax_code,
                'address'      => $invoice->vat_company_address,
                'email'        => $invoice->vat_company_email,
            ],
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled'    => true,
            'isRemoteEnabled'         => false,
            'defaultFont'             => 'DejaVu Sans',
            'dpi'                     => 150,
            'defaultMediaType'        => 'print',
            'isFontSubsettingEnabled' => true,
        ]);

        return $pdf;
    }

    private function methodLabel(?string $method): string
    {
        return match ($method) {
            'bank'   => 'Chuyển khoản ngân hàng',
            'wallet' => 'Số dư tài khoản',
            'cash'   => 'Tiền mặt',
            null     => '—',
            default  => ucfirst($method),
        };
    }
}
