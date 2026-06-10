<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Thêm cột điểm công đức cho users
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('merit_points')->default(0)->after('address');
        });

        // Bảng mã giảm giá
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('description', 255)->nullable();
            $table->enum('discount_type', ['fixed', 'percent'])->default('fixed');
            $table->decimal('discount_value', 15, 2);        // Giá trị giảm (VND hoặc %)
            $table->decimal('min_order_amount', 15, 2)->default(0); // Đơn tối thiểu
            $table->decimal('max_discount', 15, 2)->nullable();     // Giảm tối đa (cho loại %)
            $table->unsignedInteger('points_cost');                  // Số điểm cần để đổi
            $table->unsignedInteger('usage_limit')->nullable();      // Giới hạn tổng lượt dùng
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // Bảng mã giảm giá của từng người dùng
        Schema::create('user_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete();
            $table->boolean('is_used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        // Thêm cột discount vào orders
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete()->after('note');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('coupon_id');
            $table->unsignedInteger('points_earned')->default(0)->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn(['discount_amount', 'points_earned']);
        });
        Schema::dropIfExists('user_coupons');
        Schema::dropIfExists('coupons');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('merit_points');
        });
    }
};
