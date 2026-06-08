<?php

namespace Tests\Feature;

use App\Models\Customers;
use App\Models\Invoices;
use App\Models\Order_items;
use App\Models\Orders;
use App\Models\Payments;
use App\Models\ServiceProvision;
use App\Services\PaymentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Kiểm thử tính idempotent của luồng xác nhận thanh toán qua webhook.
 *
 * Bối cảnh: provider (VNPay/MoMo/ZaloPay) thường gửi webhook TRÙNG cho cùng 1
 * giao dịch (retry khi không nhận được 200 đủ nhanh, hoặc gửi nhiều lần). Nếu
 * PaymentService::confirmPaymentFromGateway() không khoá row + re-check trong
 * transaction thì hai webhook có thể cùng tạo ServiceProvision → double-provision.
 *
 * LƯU Ý PHẠM VI: PHPUnit chạy đơn luồng nên không thể tái hiện đúng race ở mức OS.
 * Test này xác minh HỢP ĐỒNG QUAN SÁT ĐƯỢC của bản vá: webhook trùng (gọi tuần tự)
 * không tạo provision thứ hai và trả về already_processed. Đây là hành vi mà
 * khoá row + re-check đảm bảo, và là thứ một test deterministic có thể chốt.
 *
 * Dùng SQLite :memory: với schema tối thiểu dựng tay — KHÔNG đụng tới DB MySQL dev,
 * và tránh migration thật (bảng service_provisions dùng kiểu `set` không chạy trên SQLite).
 */
class PaymentWebhookIdempotencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default'                                    => 'sqlite',
            'database.connections.sqlite.database'                => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');

        Mail::fake();
        $this->createSchema();
    }

    /** Webhook trùng không được tạo provision thứ hai (chống double-provision). */
    public function test_duplicate_webhook_does_not_create_second_provision(): void
    {
        $this->seedPendingPayment('TXN-DUP-001');
        $service = app(PaymentService::class);

        // Webhook lần 1 — xử lý thật
        $first = $service->confirmPaymentFromGateway('TXN-DUP-001', 'vnpay', []);

        $this->assertTrue($first['success']);
        $this->assertArrayNotHasKey('already_processed', $first);
        $this->assertCount(1, $first['provisions']);
        $this->assertSame(1, ServiceProvision::count());

        // Webhook lần 2 — TRÙNG, phải bị bỏ qua (idempotent)
        $second = $service->confirmPaymentFromGateway('TXN-DUP-001', 'vnpay', []);

        $this->assertTrue($second['success']);
        $this->assertTrue($second['already_processed']);

        // Mấu chốt: vẫn chỉ có ĐÚNG 1 provision sau 2 lần webhook
        $this->assertSame(1, ServiceProvision::count(), 'Webhook trùng đã tạo provision thứ hai (double-provision)');
        $this->assertSame(1, Payments::where('status', 'completed')->count());
    }

    /** Webhook lần đầu chuyển đúng trạng thái payment/order/invoice. */
    public function test_first_webhook_marks_payment_order_and_invoice(): void
    {
        $payment = $this->seedPendingPayment('TXN-OK-002');

        app(PaymentService::class)->confirmPaymentFromGateway('TXN-OK-002', 'momo', []);

        $payment->refresh();
        $this->assertSame('completed', $payment->status);
        $this->assertNotNull($payment->verified_at, 'verified_at phải được lưu (fillable + cột DB)');
        $this->assertSame('processing', $payment->order->status);
        $this->assertSame('paid', $payment->order->invoice->status);
    }

    /** Payment không tồn tại → no_retry (job không nên retry vô ích). */
    public function test_unknown_transaction_returns_no_retry(): void
    {
        $result = app(PaymentService::class)->confirmPaymentFromGateway('TXN-MISSING', 'zalopay', []);

        $this->assertFalse($result['success']);
        $this->assertTrue($result['no_retry']);
        $this->assertSame(0, ServiceProvision::count());
    }

    /**
     * Payment đã ở trạng thái khác 'pending'/'completed' (vd 'failed') → no_retry,
     * không tạo provision. Exercise nhánh re-check status BÊN TRONG khoá transaction.
     */
    public function test_non_pending_payment_is_rejected_inside_lock(): void
    {
        $payment = $this->seedPendingPayment('TXN-FAILED-003');
        $payment->update(['status' => 'failed']);

        $result = app(PaymentService::class)->confirmPaymentFromGateway('TXN-FAILED-003', 'vnpay', []);

        $this->assertFalse($result['success']);
        $this->assertTrue($result['no_retry']);
        $this->assertSame(0, ServiceProvision::count());
    }

    /**
     * Order_items::completedProvisions() tham chiếu ServiceProvision::STATUS_COMPLETED.
     * Trước khi định nghĩa hằng số, gọi quan hệ này sẽ fatal. Test xác minh nó chạy
     * và lọc đúng theo trạng thái 'completed'.
     */
    public function test_completed_provisions_relation_resolves_status_constant(): void
    {
        $payment = $this->seedPendingPayment('TXN-REL-004');
        $item    = $payment->order->items()->first();

        ServiceProvision::create([
            'order_item_id'    => $item->id,
            'product_id'       => 1,
            'customer_id'      => $payment->order->customer_id,
            'provision_type'   => 'ssl',
            'provision_status' => ServiceProvision::STATUS_COMPLETED,
        ]);
        ServiceProvision::create([
            'order_item_id'    => $item->id,
            'product_id'       => 1,
            'customer_id'      => $payment->order->customer_id,
            'provision_type'   => 'ssl',
            'provision_status' => ServiceProvision::STATUS_PENDING,
        ]);

        $this->assertSame('completed', ServiceProvision::STATUS_COMPLETED);
        // Chỉ provision 'completed' được trả về (1/2), không fatal.
        $this->assertCount(1, $item->completedProvisions()->get());
    }

    // ----------------------------------------------------------------------

    /** Tạo 1 customer + order (1 item cần provision) + invoice + payment đang pending. */
    private function seedPendingPayment(string $transactionId): Payments
    {
        $customer = Customers::create([
            'user_id' => 1,
            'balance' => 0,
            'status'  => 'active',
        ]);

        $order = Orders::create([
            'order_number'    => 'ORD-' . $transactionId,
            'customer_id'     => $customer->id,
            'status'          => 'pending',
            'subtotal'        => 100000,
            'tax_amount'      => 0,
            'discount_amount' => 0,
            'total_amount'    => 100000,
        ]);

        Order_items::create([
            'order_id'   => $order->id,
            'product_id' => 1,
            'name'       => 'SSL Certificate',
            'quantity'   => 1,
            'price'      => 100000,
            'subtotal'   => 100000,
            'total'      => 100000,
            // service_type quyết định item này có sinh provision hay không
            'options'    => json_encode(['service_type' => 'ssl', 'domain' => 'example.com']),
        ]);

        $invoice = Invoices::create([
            'invoice_number'  => 'INV-' . $transactionId,
            'order_id'        => $order->id,
            'customer_id'     => $customer->id,
            'status'          => 'sent',
            'subtotal'        => 100000,
            'tax_amount'      => 0,
            'discount_amount' => 0,
            'total_amount'    => 100000,
            'due_date'        => now()->addDays(7),
        ]);

        return Payments::create([
            'order_id'       => $order->id,
            'invoice_id'     => $invoice->id,
            'payment_number' => 'PAY-' . $transactionId,
            'amount'         => 100000,
            'payment_method' => 'vnpay',
            'payment_date'   => now(),
            'transaction_id' => $transactionId,
            'status'         => 'pending',
        ]);
    }

    /** Schema tối thiểu, SQLite-friendly (enum/set → string), chỉ các bảng luồng webhook chạm tới. */
    private function createSchema(): void
    {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('status')->default('active');
            $t->decimal('balance', 15, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('order_number');
            $t->unsignedBigInteger('customer_id');
            $t->string('status')->default('pending');
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->decimal('tax_amount', 12, 2)->default(0);
            $t->decimal('discount_amount', 12, 2)->default(0);
            $t->decimal('total_amount', 12, 2)->default(0);
            $t->unsignedBigInteger('renewal_of_service_id')->nullable();
            $t->timestamps();
        });

        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('order_id');
            $t->unsignedBigInteger('product_id');
            $t->string('name');
            $t->integer('quantity')->default(1);
            $t->decimal('price', 12, 2)->default(0);
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->decimal('total', 12, 2)->default(0);
            $t->text('options')->nullable();
            $t->timestamps();
        });

        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->string('invoice_number');
            $t->unsignedBigInteger('order_id')->nullable();
            $t->unsignedBigInteger('customer_id');
            $t->string('status')->default('draft');
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->decimal('tax_amount', 12, 2)->default(0);
            $t->decimal('discount_amount', 12, 2)->default(0);
            $t->decimal('total_amount', 12, 2)->default(0);
            $t->date('due_date')->nullable();
            $t->timestamps();
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('order_id')->nullable();
            $t->unsignedBigInteger('invoice_id')->nullable();
            $t->string('payment_number');
            $t->decimal('amount', 12, 2);
            $t->string('payment_method');
            $t->datetime('payment_date');
            $t->string('transaction_id')->nullable();
            $t->string('status')->default('pending');
            $t->text('notes')->nullable();
            // Cột code có update nhưng không nằm trong migration gốc — thêm để test chạy đúng.
            $t->unsignedBigInteger('verified_by')->nullable();
            $t->datetime('verified_at')->nullable();
            $t->timestamps();
        });

        Schema::create('service_provisions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('order_item_id');
            $t->unsignedBigInteger('product_id');
            $t->unsignedBigInteger('customer_id');
            $t->string('provision_type', 50);
            $t->string('provision_status')->default('pending');
            $t->text('provision_data')->nullable();
            $t->integer('priority')->default(5);
            $t->string('delivery_status')->default('pending');
            $t->integer('view_count')->default(0);
            $t->unsignedBigInteger('provisioned_by')->nullable();
            $t->timestamp('provisioned_at')->nullable();
            $t->timestamp('delivered_at')->nullable();
            $t->timestamp('last_viewed_at')->nullable();
            $t->timestamp('estimated_completion')->nullable();
            $t->string('external_id')->nullable();
            $t->text('failure_reason')->nullable();
            $t->text('provision_notes')->nullable();
            $t->timestamps();
        });
    }
}
