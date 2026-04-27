<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_services', function (Blueprint $table) {
            $table->timestamp('renewal_price_locked_at')->nullable()->after('renewal_price');
        });

        // Backfill: dùng started_at làm mốc chốt giá cho các service đã tồn tại.
        DB::statement('UPDATE customer_services SET renewal_price_locked_at = started_at WHERE renewal_price_locked_at IS NULL AND renewal_price IS NOT NULL');
    }

    public function down(): void
    {
        Schema::table('customer_services', function (Blueprint $table) {
            $table->dropColumn('renewal_price_locked_at');
        });
    }
};
