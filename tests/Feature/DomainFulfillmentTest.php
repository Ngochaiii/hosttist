<?php

namespace Tests\Feature;

use App\Models\CustomerService;
use App\Models\Domain;
use App\Models\DomainTld;
use App\Models\ServiceProvision;
use App\Services\DomainProvisioningService;
use App\Services\ServiceLifecycleService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 5 — admin fulfill domain khách đặt: tạo bản ghi Domain (active) +
 * CustomerService (lifecycle gia hạn/hết hạn) với hạn theo NĂM và giá gia hạn = renew_price.
 */
class DomainFulfillmentTest extends TestCase
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

    private function comTld(): DomainTld
    {
        return DomainTld::create([
            'tld' => 'com', 'register_cost' => 300000, 'renew_cost' => 290000,
            'markup_type' => DomainTld::MARKUP_AMOUNT, 'markup_value' => 70000,
            'min_years' => 1, 'max_years' => 10,
        ]);
    }

    private function domainProvision(DomainTld $tld, array $override = []): ServiceProvision
    {
        $pdata = array_merge([
            'service_type' => 'domain',
            'domain'       => 'khachdat.com',
            'domain_name'  => 'khachdat.com',
            'sld'          => 'khachdat',
            'tld'          => 'com',
            'tld_id'       => $tld->id,
            'years'        => 2,
            'period'       => 2,
            'cost_price'   => 600000,  // 300k × 2
            'sell_price'   => 740000,  // 370k × 2
            'registrant'   => ['name' => 'Khach A'],
            'nameservers'  => ['ns1.nhanhoa.com', 'ns2.nhanhoa.com'],
        ], $override);

        return ServiceProvision::create([
            'order_item_id'    => 10,
            'product_id'       => 1,
            'customer_id'      => 5,
            'provision_type'   => 'domain',
            'provision_status' => 'pending',
            'provision_data'   => json_encode($pdata),
        ]);
    }

    /** activate tạo Domain(active) + CS(active), hạn = now + số năm. */
    public function test_activate_creates_domain_and_service(): void
    {
        $tld       = $this->comTld();
        $provision = $this->domainProvision($tld);

        $service = app(DomainProvisioningService::class)->activate(
            $provision,
            json_decode($provision->provision_data, true)
        );

        $this->assertSame('active', $service->status);
        $this->assertSame('yearly', $service->billing_cycle);
        $this->assertEquals(now()->addYears(2)->toDateString(), $service->expires_at->toDateString());

        $domain = Domain::where('domain_name', 'khachdat.com')->first();
        $this->assertNotNull($domain);
        $this->assertSame(Domain::STATUS_ACTIVE, $domain->status);
        $this->assertSame(Domain::SOURCE_ORDER, $domain->source);
        $this->assertSame($service->id, $domain->customer_service_id);
        $this->assertEquals(140000.0, (float) $domain->profit); // 740k - 600k
        $this->assertSame('Khach A', $domain->registrant['name']); // giải mã được
    }

    /** Giá gia hạn = renew_price của đuôi × năm (KHÁC giá đăng ký). */
    public function test_renewal_price_uses_tld_renew_price(): void
    {
        $tld       = $this->comTld();           // renew_price = 290k + 70k = 360k
        $provision = $this->domainProvision($tld);

        $service = app(DomainProvisioningService::class)->activate(
            $provision,
            json_decode($provision->provision_data, true)
        );

        $this->assertEquals(720000.0, (float) $service->renewal_price); // 360k × 2
    }

    /** Admin nhập expiry_date → dùng đúng ngày đó. */
    public function test_explicit_expiry_date_is_used(): void
    {
        $tld       = $this->comTld();
        $provision = $this->domainProvision($tld, ['expiry_date' => '2028-12-31']);

        $service = app(DomainProvisioningService::class)->activate(
            $provision,
            json_decode($provision->provision_data, true)
        );

        $this->assertSame('2028-12-31', $service->expires_at->toDateString());
        $this->assertSame('2028-12-31', Domain::first()->expires_at->toDateString());
    }

    /** Gọi activate 2 lần (re-complete) không tạo Domain trùng. */
    public function test_activate_is_idempotent_on_domain(): void
    {
        $tld       = $this->comTld();
        $provision = $this->domainProvision($tld);
        $pdata     = json_decode($provision->provision_data, true);

        app(DomainProvisioningService::class)->activate($provision, $pdata);
        app(DomainProvisioningService::class)->activate($provision, $pdata);

        $this->assertSame(1, Domain::where('order_item_id', 10)->count());
    }

    /** Hook: ServiceLifecycleService định tuyến provision domain sang DomainProvisioningService. */
    public function test_lifecycle_routes_domain_provision(): void
    {
        $tld       = $this->comTld();
        $provision = $this->domainProvision($tld);

        $service = app(ServiceLifecycleService::class)->activateFromProvision($provision);

        $this->assertSame('yearly', $service->billing_cycle);
        $this->assertSame(1, Domain::count());
    }

    /** syncFromService đồng bộ hạn + trạng thái Domain theo CS. */
    public function test_sync_from_service_updates_domain(): void
    {
        $tld       = $this->comTld();
        $provision = $this->domainProvision($tld);
        $service   = app(DomainProvisioningService::class)->activate(
            $provision, json_decode($provision->provision_data, true)
        );

        // Giả lập hết hạn.
        $service->update(['status' => 'expired', 'expires_at' => '2030-01-01']);
        app(DomainProvisioningService::class)->syncFromService($service);

        $domain = Domain::first()->fresh();
        $this->assertSame(Domain::STATUS_EXPIRED, $domain->status);
        $this->assertSame('2030-01-01', $domain->expires_at->toDateString());
    }

    private function createSchema(): void
    {
        Schema::create('domain_tlds', function (Blueprint $t) {
            $t->id();
            $t->string('tld', 30)->unique();
            $t->boolean('is_vn')->default(false);
            $t->decimal('register_cost', 12, 2)->default(0);
            $t->decimal('renew_cost', 12, 2)->default(0);
            $t->decimal('transfer_cost', 12, 2)->nullable();
            $t->string('markup_type')->default('amount');
            $t->decimal('markup_value', 12, 2)->default(0);
            $t->integer('round_to')->nullable();
            $t->integer('min_years')->default(1);
            $t->integer('max_years')->default(10);
            $t->unsignedBigInteger('product_id')->nullable();
            $t->boolean('is_active')->default(true);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });

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
            $t->unsignedBigInteger('legacy_product_id')->nullable();
            $t->unsignedBigInteger('order_item_id')->nullable();
            $t->string('status')->default('active');
            $t->timestamp('started_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('next_renewal_date')->nullable();
            $t->boolean('auto_renew')->default(false);
            $t->decimal('renewal_price', 12, 2)->nullable();
            $t->timestamp('renewal_price_locked_at')->nullable();
            $t->string('billing_cycle')->nullable();
            $t->timestamp('notified_30d_at')->nullable();
            $t->timestamp('notified_15d_at')->nullable();
            $t->timestamp('notified_7d_at')->nullable();
            $t->timestamp('notified_1d_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('domains', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->unsignedBigInteger('order_item_id')->nullable();
            $t->unsignedBigInteger('customer_service_id')->nullable();
            $t->unsignedBigInteger('tld_id')->nullable();
            $t->string('domain_name')->unique();
            $t->string('sld');
            $t->string('tld', 30);
            $t->string('status')->default('pending');
            $t->integer('years')->default(1);
            $t->date('registered_at')->nullable();
            $t->date('expires_at')->nullable();
            $t->decimal('cost_price', 12, 2)->default(0);
            $t->decimal('sell_price', 12, 2)->default(0);
            $t->decimal('profit', 12, 2)->default(0);
            $t->text('registrant')->nullable();
            $t->text('auth_code')->nullable();
            $t->string('registrar')->default('nhanhoa');
            $t->text('nameservers')->nullable();
            $t->boolean('auto_renew')->default(false);
            $t->string('source')->default('customer_order');
            $t->text('notes')->nullable();
            $t->timestamps();
        });
    }
}
