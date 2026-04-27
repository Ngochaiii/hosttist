<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Đổi ON DELETE CASCADE → RESTRICT trên các bảng tài chính.
 * Lý do: tránh việc xoá nhầm 1 customer làm mất TOÀN BỘ orders/invoices/payments
 * — đây là dữ liệu chứng từ pháp lý, không được phép tự động xoá.
 *
 * Sau migration này, muốn xoá customer có giao dịch → admin phải xử lý từng bảng thủ công
 * (hoặc dùng soft-delete sau này nếu cần).
 */
return new class extends Migration
{
    public function up(): void
    {
        // orders.customer_id
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')->references('id')->on('customers')
                ->onDelete('restrict');
        });

        // invoices.customer_id
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')->references('id')->on('customers')
                ->onDelete('restrict');
        });

        // deposits.customer_id
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')->references('id')->on('customers')
                ->onDelete('restrict');
        });

        // customer_services.customer_id (modern lifecycle bảng)
        Schema::table('customer_services', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')->references('id')->on('customers')
                ->onDelete('restrict');
        });

        // products.customer_id (legacy services gắn với khách)
        if (Schema::hasColumn('products', 'customer_id')) {
            Schema::table('products', function (Blueprint $table) {
                try { $table->dropForeign(['customer_id']); } catch (\Throwable $e) {}
                $table->foreign('customer_id')->references('id')->on('customers')
                    ->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')->references('id')->on('customers')
                ->onDelete('cascade');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')->references('id')->on('customers')
                ->onDelete('cascade');
        });

        Schema::table('deposits', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')->references('id')->on('customers')
                ->onDelete('cascade');
        });

        Schema::table('customer_services', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->foreign('customer_id')->references('id')->on('customers')
                ->onDelete('cascade');
        });
    }
};
