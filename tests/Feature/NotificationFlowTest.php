<?php

namespace Tests\Feature;

use App\Models\Customers;
use App\Models\Invoices;
use App\Models\Order_items;
use App\Models\Orders;
use App\Models\Payments;
use App\Models\User;
use App\Notifications\CustomerAlert;
use App\Services\PaymentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Kiểm thử luồng thông báo in-app sau khi hoàn thành hoá đơn/thanh toán.
 *
 * In-app (bảng notifications, kênh database) là kênh thông báo duy nhất —
 * luồng email đã được gỡ bỏ. Khách hàng PHẢI nhận được thông báo trên UI.
 * Test chốt hợp đồng đó.
 *
 * Dùng SQLite :memory: với schema tối thiểu dựng tay — theo pattern của
 * PaymentWebhookIdempotencyTest, KHÔNG đụng tới DB MySQL dev.
 */
class NotificationFlowTest extends TestCase
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

        $this->createSchema();
    }

    /**
     * MẤU CHỐT: xác nhận thanh toán từ gateway → payment completed
     * và khách có thông báo payment_approved trong DB để hiển thị trên UI.
     */
    public function test_gateway_confirmation_creates_in_app_notification(): void
    {
        $payment = $this->seedPendingPayment('TXN-NOTIF-001');
        $user = User::first();

        $result = app(PaymentService::class)->confirmPaymentFromGateway('TXN-NOTIF-001', 'vnpay', []);

        $this->assertTrue($result['success']);
        $this->assertSame('completed', $payment->fresh()->status);

        $notifications = $user->notifications;
        $this->assertCount(1, $notifications, 'Thông báo in-app phải được tạo khi thanh toán được xác nhận');

        $data = $notifications->first()->data;
        $this->assertSame('payment_approved', $data['type']);
        $this->assertSame('success', $data['level']);
        $this->assertNotEmpty($data['title']);
        $this->assertStringContainsString('INV-TXN-NOTIF-001', $data['message']);
        $this->assertNotEmpty($data['action_url']);
        $this->assertNull($notifications->first()->read_at, 'Thông báo mới phải ở trạng thái chưa đọc');
    }

    /** Từ chối thanh toán → thông báo payment_rejected kèm lý do. */
    public function test_reject_payment_creates_in_app_notification(): void
    {
        $payment = $this->seedPendingPayment('TXN-NOTIF-002');
        $user = User::first();

        $result = app(PaymentService::class)->rejectPayment($payment, 'Sai nội dung chuyển khoản', 99);

        $this->assertTrue($result['success']);
        $this->assertSame('failed', $payment->fresh()->status);

        $data = $user->notifications->first()->data;
        $this->assertSame('payment_rejected', $data['type']);
        $this->assertSame('danger', $data['level']);
        $this->assertStringContainsString('Sai nội dung chuyển khoản', $data['message']);
    }

    /** Nhánh ví: trước đây không gửi gì — giờ phải trừ tiền + tạo thông báo. */
    public function test_wallet_payment_creates_in_app_notification(): void
    {
        $payment = $this->seedPendingPayment('TXN-NOTIF-003');
        $customer = Customers::first();
        $customer->update(['balance' => 500000]);
        $user = User::first();

        // Order riêng chưa có payment cho nhánh wallet
        $order = $payment->order;
        $payment->delete();

        $result = app(PaymentService::class)->processWalletPayment($order, $customer->fresh());

        $this->assertTrue($result['success']);
        $this->assertEquals(400000, (float) $customer->fresh()->balance);
        $this->assertSame('paid', $order->invoice->fresh()->status);

        $data = $user->notifications->first()->data;
        $this->assertSame('payment_approved', $data['type']);
    }

    /** Endpoint UI: click thông báo → đánh dấu đã đọc + redirect tới action_url. */
    public function test_notification_go_endpoint_marks_read_and_redirects(): void
    {
        $user = $this->seedUser();
        $user->notify(new CustomerAlert('payment_approved', 'Tiêu đề', 'Nội dung', 'http://localhost/customer/invoices', 'success'));

        $notification = $user->notifications()->first();
        $this->assertNull($notification->read_at);

        $response = $this->actingAs($user)->get(route('notifications.go', $notification->id));

        $response->assertRedirect('http://localhost/customer/invoices');
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** Endpoint UI: đọc tất cả; và không cho đọc thông báo của người khác. */
    public function test_read_all_and_cross_user_isolation(): void
    {
        $user = $this->seedUser();
        $other = User::create([
            'name' => 'Other', 'email' => 'other@example.com',
            'password' => bcrypt('secret'), 'role' => 'customer',
        ]);

        $user->notify(new CustomerAlert('a', 'T1', 'M1'));
        $user->notify(new CustomerAlert('b', 'T2', 'M2'));
        $other->notify(new CustomerAlert('c', 'T3', 'M3'));

        $this->actingAs($user)->post(route('notifications.readAll'));

        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertSame(1, $other->fresh()->unreadNotifications()->count(), 'Không được đụng thông báo của user khác');

        // markRead chéo user: không được đánh dấu thông báo người khác
        $otherNotif = $other->notifications()->first();
        $this->actingAs($user)->post(route('notifications.read', $otherNotif->id));
        $this->assertNull($otherNotif->fresh()->read_at);
    }

    /** Chưa đăng nhập → redirect về login, không lộ dữ liệu. */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/notifications')->assertRedirect(route('login'));
    }

    // ----------------------------------------------------------------------

    private function seedUser(): User
    {
        return User::create([
            'name'     => 'Khách Test',
            'email'    => 'khach@example.com',
            'password' => bcrypt('secret'),
            'role'     => 'customer',
        ]);
    }

    /** User + customer + order (1 item ssl) + invoice + payment pending. */
    private function seedPendingPayment(string $transactionId): Payments
    {
        $user = $this->seedUser();

        $customer = Customers::create([
            'user_id' => $user->id,
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

    /** Schema tối thiểu, SQLite-friendly — chỉ các bảng luồng notification chạm tới. */
    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->string('role')->default('customer');
            $t->boolean('is_active')->default(true);
            $t->string('username')->nullable();
            $t->string('phone')->nullable();
            $t->string('avatar')->nullable();
            $t->string('address')->nullable();
            $t->timestamp('email_verified_at')->nullable();
            $t->timestamp('last_login_at')->nullable();
            $t->rememberToken();
            $t->softDeletes();
            $t->timestamps();
        });

        // Bảng notifications chuẩn Laravel — giống migration thật
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });

        Schema::create('configs', function (Blueprint $t) {
            $t->id();
            $t->timestamps();
        });

        // products rỗng: shouldCreateProvision() trả false → nhánh wallet không sinh provision
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->timestamps();
        });

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
            $t->text('notes')->nullable();
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
