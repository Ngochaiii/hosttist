<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('vat_invoice_requested')->default(false)->after('total_amount');
            $table->string('vat_company_name')->nullable()->after('vat_invoice_requested');
            $table->string('vat_tax_code', 50)->nullable()->after('vat_company_name');
            $table->string('vat_company_address')->nullable()->after('vat_tax_code');
            $table->string('vat_company_email')->nullable()->after('vat_company_address');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'vat_invoice_requested',
                'vat_company_name',
                'vat_tax_code',
                'vat_company_address',
                'vat_company_email',
            ]);
        });
    }
};
