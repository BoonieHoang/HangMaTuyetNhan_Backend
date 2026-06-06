<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Lưu mã số nguyên (numeric code) gửi cho PayOS, dùng để đối chiếu Webhook
            $table->unsignedBigInteger('payos_order_code')->nullable()->unique()->after('order_code');
        });

        Schema::table('payments', function (Blueprint $table) {
            // Lưu link thanh toán PayOS trả về (thay thế qr_url cũ)
            $table->string('payos_checkout_url', 1000)->nullable()->after('qr_url');
            // Lưu link ảnh QR PayOS (dạng base64 hoặc URL)
            $table->string('payos_qr_code', 1000)->nullable()->after('payos_checkout_url');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payos_order_code');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['payos_checkout_url', 'payos_qr_code']);
        });
    }
};
