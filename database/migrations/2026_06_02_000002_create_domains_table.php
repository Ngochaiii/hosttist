<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bản ghi tài sản tên miền — mỗi domain khách đặt HOẶC admin import từ Nhân Hòa.
 *
 * Giá (cost/sell/profit) được SNAPSHOT tại thời điểm bán: đổi giá TLD sau này
 * không làm thay đổi lãi đã ghi nhận (giống WHMCS — đổi giá chỉ áp domain mới).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();

            // Liên kết (đều nullable để hỗ trợ domain import sẵn / chưa gán khách).
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('order_item_id')->nullable();
            $table->unsignedBigInteger('customer_service_id')->nullable(); // lifecycle gia hạn/hết hạn
            $table->unsignedBigInteger('tld_id')->nullable();

            $table->string('domain_name')->unique();        // 'abc.com'
            $table->string('sld');                          // 'abc'
            $table->string('tld', 30);                      // 'com' (denormalize phòng khi TLD bị xoá)

            $table->enum('status', ['pending', 'active', 'expired', 'cancelled', 'transferred'])
                  ->default('pending');
            $table->unsignedSmallInteger('years')->default(1);
            $table->date('registered_at')->nullable();
            $table->date('expires_at')->nullable();

            // Snapshot tài chính → báo cáo lãi.
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('sell_price', 12, 2)->default(0);
            $table->decimal('profit', 12, 2)->default(0);

            // Dữ liệu nhạy cảm — MÃ HÓA (EncryptsData).
            $table->text('registrant')->nullable();         // {name,email,phone,address, id_type,id_number,tax_code}
            $table->text('auth_code')->nullable();          // EPP/auth code chuyển nhượng

            $table->string('registrar')->default('nhanhoa');
            $table->json('nameservers')->nullable();
            $table->boolean('auto_renew')->default(false);

            $table->enum('source', ['customer_order', 'admin_import'])->default('customer_order');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('set null');
            $table->foreign('tld_id')->references('id')->on('domain_tlds')->onDelete('set null');

            $table->index('status');
            $table->index('expires_at');
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
