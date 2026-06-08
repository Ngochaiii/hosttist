<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Danh mục đuôi tên miền (TLD) + cấu hình giá.
 *
 * Nguồn sự thật DUY NHẤT về giá domain: lưu giá GỐC (cost) bạn trả Nhân Hòa +
 * markup (số tiền hoặc %), giá bán & lãi được TÍNH ra (accessor trên model),
 * không lưu trùng để tránh lệch. VD: register_cost=300k, markup amount 70k
 * → register_price = 370k, lãi = 70k.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_tlds', function (Blueprint $table) {
            $table->id();
            $table->string('tld', 30)->unique();           // 'com', 'net', 'vn', 'com.vn'
            $table->boolean('is_vn')->default(false);       // true → bắt buộc form chủ thể .vn

            // Giá GỐC (chi phí Nhân Hòa). Giá bán = computePrice(cost) theo markup.
            $table->decimal('register_cost', 12, 2)->default(0);
            $table->decimal('renew_cost', 12, 2)->default(0);
            $table->decimal('transfer_cost', 12, 2)->nullable();

            // Markup áp chung cho TLD này.
            $table->enum('markup_type', ['amount', 'percent'])->default('amount');
            $table->decimal('markup_value', 12, 2)->default(0);
            $table->unsignedInteger('round_to')->nullable(); // làm tròn giá bán tới bội số này (vd 1000)

            $table->unsignedSmallInteger('min_years')->default(1);
            $table->unsignedSmallInteger('max_years')->default(10);

            // Product ẩn dùng để nối vào engine cart/order (set ở Phase 4).
            $table->unsignedBigInteger('product_id')->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_tlds');
    }
};
