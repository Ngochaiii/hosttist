<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cho phép backfill CustomerService cho các Products "sold" cũ (legacy),
 * không có provision_id / order_item_id tương ứng.
 * Chỉ nới lỏng ràng buộc, không xoá dữ liệu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_services', function (Blueprint $table) {
            // Drop FKs trước khi đổi nullable (MySQL yêu cầu)
            $table->dropForeign(['provision_id']);
            $table->dropForeign(['order_item_id']);
        });

        Schema::table('customer_services', function (Blueprint $table) {
            $table->unsignedBigInteger('provision_id')->nullable()->change();
            $table->unsignedBigInteger('order_item_id')->nullable()->change();

            $table->foreign('provision_id')->references('id')->on('service_provisions')
                ->nullOnDelete();
            $table->foreign('order_item_id')->references('id')->on('order_items')
                ->nullOnDelete();

            // Tham chiếu ngược tới legacy Products sold (nullable)
            $table->unsignedBigInteger('legacy_product_id')->nullable()->after('product_id');
            $table->index('legacy_product_id');
        });
    }

    public function down(): void
    {
        Schema::table('customer_services', function (Blueprint $table) {
            $table->dropIndex(['legacy_product_id']);
            $table->dropColumn('legacy_product_id');

            $table->dropForeign(['provision_id']);
            $table->dropForeign(['order_item_id']);
        });

        Schema::table('customer_services', function (Blueprint $table) {
            $table->unsignedBigInteger('provision_id')->nullable(false)->change();
            $table->unsignedBigInteger('order_item_id')->nullable(false)->change();

            $table->foreign('provision_id')->references('id')->on('service_provisions')
                ->cascadeOnDelete();
            $table->foreign('order_item_id')->references('id')->on('order_items');
        });
    }
};
