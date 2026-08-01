<?php

namespace App\Services;

use App\Models\Config;
use App\Models\CustomerService;
use App\Models\Invoices;
use App\Models\ServiceProvision;
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

        // Dịch vụ đã cấp cho từng dòng hàng — nguồn của tên miền và ngày hết hạn.
        $provisions = ServiceProvision::whereIn('order_item_id', $order?->items->pluck('id') ?? [])
            ->with('customerService')
            ->get()
            ->keyBy('order_item_id');

        // Đơn gia hạn không sinh provision mới; dịch vụ đích nằm ở renewal_of_service_id.
        $renewalService = $order?->renewal_of_service_id
            ? CustomerService::with('provision')->find($order->renewal_of_service_id)
            : null;

        $rows = [];
        foreach ($order?->items ?? [] as $item) {
            $options   = json_decode($item->options, true) ?: [];
            $provision = $provisions[$item->id] ?? null;
            $service   = $provision?->customerService ?? $renewalService;
            $data      = $this->provisionData($provision ?? $renewalService?->provision);
            $period    = \App\Helpers\ServiceHelper::orderItemDurationYears($item);

            $domain = $item->domain ?: ($options['domain'] ?? ($data['domain'] ?? null));

            // Hạn dùng lấy từ CustomerService (nguồn chân lý), lùi về expiry_date trong
            // provision_data, cuối cùng mới suy ra từ ngày bắt đầu + số năm đã mua.
            $expiresAt = $service?->expires_at
                ?? (!empty($data['expiry_date']) ? Carbon::parse($data['expiry_date']) : null);

            if (!$expiresAt && $period) {
                $start = $service?->started_at ?? $provision?->provisioned_at ?? $order?->created_at;
                $expiresAt = $start ? Carbon::parse($start)->addYears($period) : null;
            }

            $rows[] = [
                'name'      => $item->name ?? $item->product->name ?? 'Dịch vụ',
                'domain'    => $domain ?: null,
                'period'    => $period ? $period . ' năm' : '—',
                'expiresAt' => $expiresAt ? Carbon::parse($expiresAt) : null,
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

    /** provision_data lưu dạng JSON string hoặc array tuỳ nơi ghi. */
    private function provisionData(?ServiceProvision $provision): array
    {
        $data = $provision?->provision_data;
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return is_array($data) ? $data : [];
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
