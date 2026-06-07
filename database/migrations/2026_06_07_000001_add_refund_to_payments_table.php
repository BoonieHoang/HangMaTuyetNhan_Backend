<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Mở rộng enum status để hỗ trợ trạng thái hoàn tiền
            // MySQL: cần dùng raw statement để thay đổi enum
            \DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending','paid','failed','refund_pending','refunded') NOT NULL DEFAULT 'pending'");

            // Lý do yêu cầu hoàn tiền (do khách điền khi hủy đơn)
            $table->string('refund_reason', 500)->nullable()->after('paid_at');
            // Ghi chú của admin khi xử lý hoàn tiền
            $table->string('refund_note', 500)->nullable()->after('refund_reason');
            // Thời điểm hoàn tiền hoàn tất
            $table->timestamp('refunded_at')->nullable()->after('refund_note');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['refund_reason', 'refund_note', 'refunded_at']);
            \DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending'");
        });
    }
};
