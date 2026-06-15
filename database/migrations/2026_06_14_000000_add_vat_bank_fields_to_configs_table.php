<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configs', function (Blueprint $table) {
            $table->string('vat_bank_name')->nullable()->after('company_bank_qr_code'); // Tên ngân hàng (TK xuất hóa đơn VAT)
            $table->string('vat_bank_account_number')->nullable()->after('vat_bank_name'); // Số tài khoản
            $table->string('vat_bank_account_name')->nullable()->after('vat_bank_account_number'); // Tên chủ tài khoản
            $table->string('vat_bank_branch')->nullable()->after('vat_bank_account_name'); // Chi nhánh
            $table->string('vat_bank_qr_code')->nullable()->after('vat_bank_branch'); // Đường dẫn ảnh mã QR
        });
    }

    public function down(): void
    {
        Schema::table('configs', function (Blueprint $table) {
            $table->dropColumn([
                'vat_bank_name',
                'vat_bank_account_number',
                'vat_bank_account_name',
                'vat_bank_branch',
                'vat_bank_qr_code',
            ]);
        });
    }
};
