<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thông tin pháp lý của công ty để in trên báo giá/chứng từ
 * (tên, MST, địa chỉ, điện thoại, email) — trước đây template PDF
 * dùng fallback hard-code vì configs không có các cột này.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configs', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('url');
            $table->string('company_tax_code', 50)->nullable()->after('company_name');
            $table->string('company_address')->nullable()->after('company_tax_code');
            $table->string('company_phone', 30)->nullable()->after('company_address');
            $table->string('company_email')->nullable()->after('company_phone');
        });
    }

    public function down(): void
    {
        Schema::table('configs', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'company_tax_code',
                'company_address',
                'company_phone',
                'company_email',
            ]);
        });
    }
};
