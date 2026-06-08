<?php

namespace Tests\Feature;

use App\Models\DomainTld;
use App\Models\Products;
use App\Services\DomainCatalogService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 4 — dựng dòng giỏ hàng domain + product "neo".
 * Đây là phần kết nối domain vào engine cart/order sẵn có (không đụng code tiền).
 */
class DomainCartLineTest extends TestCase
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

    /** Giá = register_price × năm; options mang đủ thông tin để fulfill + báo lãi. */
    public function test_build_cart_line_computes_price_and_options(): void
    {
        $tld  = $this->comTld();
        $line = app(DomainCatalogService::class)->buildCartLine($tld, 'ABC.com', 2, ['name' => 'Nguyen A'], true);

        $this->assertEquals(740000.0, $line['unit_price']); // 370k × 2
        $this->assertStringContainsString('abc.com', $line['name']);

        $o = $line['options'];
        $this->assertSame('domain', $o['service_type']);
        $this->assertSame('abc.com', $o['domain']);
        $this->assertSame('com', $o['tld']);
        $this->assertSame(2, $o['years']);
        $this->assertSame(2, $o['period']);          // khóa dùng chung với engine
        $this->assertEquals(600000.0, $o['cost_price']); // 300k × 2 → báo lãi
        $this->assertEquals(740000.0, $o['sell_price']);
        $this->assertSame('Nguyen A', $o['registrant']['name']);
    }

    /** Số năm bị kẹp trong [min,max] của đuôi. */
    public function test_years_clamped_to_tld_range(): void
    {
        $tld  = $this->comTld(); // max 10
        $line = app(DomainCatalogService::class)->buildCartLine($tld, 'abc.com', 20);

        $this->assertSame(10, $line['options']['years']);
        $this->assertEquals(3700000.0, $line['unit_price']); // 370k × 10
    }

    /** Product "neo" được tạo 1 lần, dùng lại (idempotent). */
    public function test_anchor_product_is_idempotent(): void
    {
        $svc = app(DomainCatalogService::class);

        $a = $svc->ensureAnchorProduct();
        $b = $svc->ensureAnchorProduct();

        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, Products::where('sku', 'DOMAIN-ANCHOR')->count());
        $this->assertSame('domain', $a->type);
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

        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->nullable();
            $t->unsignedBigInteger('parent_id')->nullable();
            $t->string('status')->default('active');
            $t->text('meta_data')->nullable();
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('category_id')->nullable();
            $t->string('name');
            $t->string('slug')->nullable();
            $t->string('sku')->nullable();
            $t->string('type')->default('product');
            $t->decimal('price', 12, 2)->nullable();
            $t->decimal('sale_price', 12, 2)->nullable();
            $t->string('product_status')->default('active');
            $t->text('meta_data')->nullable();
            $t->timestamps();
        });
    }
}
