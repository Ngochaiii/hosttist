<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm verified_by / verified_at vào bảng payments.
 *
 * Lý do: PaymentService (approvePayment, confirmPaymentFromGateway...) vẫn ghi
 * 2 cột này để lưu "ai/lúc nào đã xác nhận thanh toán" — dấu vết kiểm toán cho
 * dữ liệu tài chính. Trước đây chúng bị $fillable chặn nên update âm thầm trôi
 * (không lỗi nhưng cũng không lưu). Nay đã đưa vào fillable → cần cột thật.
 *
 * Guard hasColumn: an toàn nếu cột đã được thêm thủ công ngoài migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('payments', 'verified_at')) {
                $table->datetime('verified_at')->nullable()->after('verified_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'verified_at')) {
                $table->dropColumn('verified_at');
            }
            if (Schema::hasColumn('payments', 'verified_by')) {
                $table->dropColumn('verified_by');
            }
        });
    }
};
