<?php

namespace Tests\Feature;

use App\Models\CustomerService;
use App\Models\ServiceProvision;
use App\Services\ServiceParameterSchema;
use App\Services\ServiceParameterService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Module "Quản lý dịch vụ đang chạy": đọc/ghi thông số vào provision_data,
 * mã hóa field nhạy cảm (mật khẩu VPS), lưu file SSL ra disk private + key mã hóa.
 *
 * Pattern test theo dự án: SQLite :memory:, dựng schema tay (KHÔNG RefreshDatabase).
 */
class CustomerServiceParamsTest extends TestCase
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

    private function service(string $type, array $pdata = []): CustomerService
    {
        $provision = ServiceProvision::create([
            'order_item_id'    => 1,
            'product_id'       => 1,
            'customer_id'      => 1,
            'provision_type'   => $type,
            'provision_status' => 'completed',
            'provision_data'   => json_encode($pdata),
        ]);

        return CustomerService::create([
            'customer_id'  => 1,
            'provision_id' => $provision->id,
            'product_id'   => 1,
            'status'       => 'active',
        ]);
    }

    private function svc(): ServiceParameterService
    {
        return app(ServiceParameterService::class);
    }

    public function test_schema_per_type(): void
    {
        $vps = collect(ServiceParameterSchema::forType('vps'));
        $this->assertTrue($vps->firstWhere('name', 'password')['encrypted'] ?? false);

        $ssl = collect(ServiceParameterSchema::forType('ssl'));
        $this->assertTrue($ssl->firstWhere('name', 'private_key')['encrypted'] ?? false);
        $this->assertTrue($ssl->firstWhere('name', 'certificate')['file'] ?? false);

        $this->assertTrue(ServiceParameterSchema::isManaged('vps'));
        $this->assertFalse(ServiceParameterSchema::isManaged('domain'));
    }

    public function test_service_type_accessor(): void
    {
        $service = $this->service('vps');
        $this->assertSame('vps', $service->service_type);
    }

    public function test_vps_password_is_encrypted_at_rest(): void
    {
        $service = $this->service('vps');

        $request = Request::create('/x', 'PUT', [
            'server_ip' => '1.2.3.4',
            'os'        => 'ubuntu-22.04',
            'username'  => 'root',
            'password'  => 'SuperSecret#123',
        ]);
        $this->svc()->applyUpdate($service, $request);

        // provision_data thô KHÔNG được chứa plaintext mật khẩu.
        $raw = ServiceProvision::find($service->provision_id)->provision_data;
        $this->assertStringNotContainsString('SuperSecret#123', $raw);
        $this->assertStringContainsString('1.2.3.4', $raw); // field thường lưu thẳng

        // Hiển thị giải mã đúng.
        $values = $this->svc()->displayValues($service->fresh());
        $this->assertSame('SuperSecret#123', $values['password']);
        $this->assertSame('1.2.3.4', $values['server_ip']);
    }

    public function test_blank_secret_keeps_existing(): void
    {
        $service = $this->service('vps');

        $this->svc()->applyUpdate($service, Request::create('/x', 'PUT', ['password' => 'first-pass']));
        // Lần 2 để trống password → giữ nguyên.
        $this->svc()->applyUpdate($service, Request::create('/x', 'PUT', ['password' => '', 'username' => 'admin']));

        $values = $this->svc()->displayValues($service->fresh());
        $this->assertSame('first-pass', $values['password']);
        $this->assertSame('admin', $values['username']);
    }

    public function test_ssl_files_stored_private_and_key_encrypted(): void
    {
        Storage::fake('local');
        $service = $this->service('ssl', ['domain' => 'example.com']);

        $cert = UploadedFile::fake()->createWithContent('cert.crt', "CERT-CONTENT-XYZ");
        $key  = UploadedFile::fake()->createWithContent('key.key', "PRIVATE-KEY-SECRET");

        $request = Request::create('/x', 'PUT', ['domain' => 'example.com'], [], [
            'certificate_file' => $cert,
            'private_key_file' => $key,
        ]);
        $this->svc()->applyUpdate($service, $request);

        $svc = $this->svc();
        $certPath = $svc->sslFilePath($service->fresh(), 'certificate');
        $keyPath  = $svc->sslFilePath($service->fresh(), 'private_key');

        $this->assertNotNull($certPath);
        $this->assertNotNull($keyPath);
        Storage::disk('local')->assertExists($certPath);
        Storage::disk('local')->assertExists($keyPath);

        // Key trên disk KHÔNG plaintext; cert thì plaintext.
        $this->assertStringNotContainsString('PRIVATE-KEY-SECRET', Storage::disk('local')->get($keyPath));
        $this->assertStringContainsString('CERT-CONTENT-XYZ', Storage::disk('local')->get($certPath));

        // Đọc lại giải mã đúng.
        $this->assertSame('PRIVATE-KEY-SECRET', $svc->readSslFile($service->fresh(), 'private_key'));
        $this->assertSame('CERT-CONTENT-XYZ', $svc->readSslFile($service->fresh(), 'certificate'));
    }

    public function test_customer_fields_decrypt_and_hide_internal_notes(): void
    {
        $service = $this->service('vps');
        $this->svc()->applyUpdate($service, Request::create('/x', 'PUT', [
            'server_ip' => '9.9.9.9',
            'username'  => 'root',
            'password'  => 'KhachThay#1',
            'notes'     => 'ghi chú nội bộ không cho khách thấy',
        ]));

        $provision = ServiceProvision::find($service->provision_id);
        $rows = collect($this->svc()->customerFieldsForProvision($provision, $service->fresh()));

        // Mật khẩu hiện ra (đã giải mã) và được đánh dấu secret.
        $pw = $rows->firstWhere('label', 'Mật khẩu');
        $this->assertNotNull($pw);
        $this->assertSame('KhachThay#1', $pw['value']);
        $this->assertTrue($pw['secret']);

        // IP hiển thị bình thường.
        $this->assertSame('9.9.9.9', $rows->firstWhere('label', 'IP')['value']);

        // Ghi chú nội bộ KHÔNG xuất hiện.
        $this->assertNull($rows->firstWhere('label', 'Ghi chú nội bộ'));
    }

    public function test_status_update_changes_column(): void
    {
        $service = $this->service('vps');
        $service->update(['status' => 'suspended', 'notes' => 'nợ phí']);

        $fresh = $service->fresh();
        $this->assertSame('suspended', $fresh->status);
        $this->assertSame('nợ phí', $fresh->notes);
    }

    private function createSchema(): void
    {
        Schema::create('service_provisions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('order_item_id')->nullable();
            $t->unsignedBigInteger('product_id')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->string('provision_type', 50);
            $t->string('provision_status')->default('pending');
            $t->text('provision_data')->nullable();
            $t->timestamps();
        });

        Schema::create('customer_services', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->unsignedBigInteger('provision_id')->nullable();
            $t->unsignedBigInteger('product_id')->nullable();
            $t->unsignedBigInteger('order_item_id')->nullable();
            $t->string('status')->default('active');
            $t->timestamp('started_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->decimal('renewal_price', 12, 2)->nullable();
            $t->string('billing_cycle')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        // Khớp đúng cột thật của bảng provision_logs (old_data/new_data/notes/source).
        Schema::create('provision_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('provision_id');
            $t->string('action', 50);
            $t->json('old_data')->nullable();
            $t->json('new_data')->nullable();
            $t->unsignedBigInteger('performed_by')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->text('notes')->nullable();
            $t->string('source', 50)->default('system');
            $t->timestamps();
        });
    }
}
