<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\DomainTld;
use App\Services\DomainCatalogService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 1 — nền dữ liệu domain: tính giá theo markup, tách sld/tld, import domain sẵn,
 * mã hóa thông tin chủ thể.
 *
 * SQLite :memory: với schema dựng tay (enum → string) — không đụng DB MySQL dev.
 */
class DomainCatalogTest extends TestCase
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

    /** markup dạng số tiền: 300k + 70k = 370k, lãi 70k. */
    public function test_markup_amount_computes_price_and_profit(): void
    {
        $tld = DomainTld::create([
            'tld' => 'com', 'register_cost' => 300000, 'renew_cost' => 290000,
            'markup_type' => DomainTld::MARKUP_AMOUNT, 'markup_value' => 70000,
        ]);

        $this->assertEquals(370000.0, $tld->register_price);
        $this->assertEquals(70000.0, $tld->register_profit);
        $this->assertEquals(360000.0, $tld->renew_price); // 290k + 70k
    }

    /** markup dạng %: 200k + 25% = 250k. */
    public function test_markup_percent_computes_price(): void
    {
        $tld = DomainTld::create([
            'tld' => 'net', 'register_cost' => 200000,
            'markup_type' => DomainTld::MARKUP_PERCENT, 'markup_value' => 25,
        ]);

        $this->assertEquals(250000.0, $tld->register_price);
        $this->assertEquals(50000.0, $tld->register_profit);
    }

    /** Làm tròn: 283k + 70k = 353k, round_to 5000 → 355k. */
    public function test_rounding_applies_to_price(): void
    {
        $tld = DomainTld::create([
            'tld' => 'xyz', 'register_cost' => 283000,
            'markup_type' => DomainTld::MARKUP_AMOUNT, 'markup_value' => 70000,
            'round_to' => 5000,
        ]);

        $this->assertEquals(355000.0, $tld->register_price);
    }

    /** transfer_price = null khi không cấu hình transfer_cost. */
    public function test_transfer_price_null_when_no_cost(): void
    {
        $tld = DomainTld::create([
            'tld' => 'org', 'register_cost' => 250000, 'transfer_cost' => null,
            'markup_type' => DomainTld::MARKUP_AMOUNT, 'markup_value' => 50000,
        ]);

        $this->assertNull($tld->transfer_price);
    }

    /** Tách domain: khớp đuôi dài nhất (com.vn thắng vn). */
    public function test_split_domain_matches_longest_tld(): void
    {
        DomainTld::create(['tld' => 'vn', 'markup_type' => 'amount']);
        DomainTld::create(['tld' => 'com.vn', 'markup_type' => 'amount']);
        DomainTld::create(['tld' => 'com', 'markup_type' => 'amount']);

        $svc = app(DomainCatalogService::class);

        $a = $svc->splitDomain('ABC.com.vn');
        $this->assertSame('abc', $a['sld']);
        $this->assertSame('com.vn', $a['tld']);

        $b = $svc->splitDomain('shop.com');
        $this->assertSame('shop', $b['sld']);
        $this->assertSame('com', $b['tld']);
    }

    /** Import domain sẵn: snapshot lãi, source=admin_import, status active mặc định. */
    public function test_import_existing_domain_snapshots_profit(): void
    {
        DomainTld::create([
            'tld' => 'com', 'register_cost' => 300000,
            'markup_type' => DomainTld::MARKUP_AMOUNT, 'markup_value' => 70000,
        ]);

        $domain = app(DomainCatalogService::class)->importExisting([
            'domain_name' => 'Example.com',
            'cost_price'  => 300000,
            'sell_price'  => 370000,
            'expires_at'  => '2027-06-02',
        ]);

        $this->assertSame('example.com', $domain->domain_name);
        $this->assertSame('example', $domain->sld);
        $this->assertSame('com', $domain->tld);
        $this->assertEquals(70000.0, (float) $domain->profit);
        $this->assertSame(Domain::SOURCE_IMPORT, $domain->source);
        $this->assertSame(Domain::STATUS_ACTIVE, $domain->status);
    }

    /** Import không nhập sell_price → tính từ markup của đuôi. */
    public function test_import_without_sell_price_uses_tld_markup(): void
    {
        DomainTld::create([
            'tld' => 'com', 'register_cost' => 300000,
            'markup_type' => DomainTld::MARKUP_AMOUNT, 'markup_value' => 70000,
        ]);

        $domain = app(DomainCatalogService::class)->importExisting([
            'domain_name' => 'auto.com',
            'cost_price'  => 300000,
        ]);

        $this->assertEquals(370000.0, (float) $domain->sell_price);
        $this->assertEquals(70000.0, (float) $domain->profit);
    }

    /** Thông tin chủ thể được mã hóa tại nghỉ; accessor giải mã lại đúng. */
    public function test_registrant_is_encrypted_at_rest(): void
    {
        $domain = app(DomainCatalogService::class)->importExisting([
            'domain_name' => 'secure.com',
            'cost_price'  => 300000,
            'sell_price'  => 370000,
            'registrant'  => ['name' => 'Nguyen Van A', 'id_number' => '012345678901'],
            'auth_code'   => 'SECRET-AUTH-123',
        ]);

        // Giá trị thô trong DB không được chứa plaintext.
        $rawRegistrant = DB::table('domains')->where('id', $domain->id)->value('registrant');
        $rawAuth       = DB::table('domains')->where('id', $domain->id)->value('auth_code');
        $this->assertStringNotContainsString('Nguyen Van A', $rawRegistrant);
        $this->assertStringNotContainsString('SECRET-AUTH-123', $rawAuth);

        // Accessor giải mã lại đúng.
        $fresh = $domain->fresh();
        $this->assertSame('Nguyen Van A', $fresh->registrant['name']);
        $this->assertSame('012345678901', $fresh->registrant['id_number']);
        $this->assertSame('SECRET-AUTH-123', $fresh->auth_code);
    }

    // ----------------------------------------------------------------------

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
