<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Insert default holidays
        $holidays = [
            ['name' => 'Tết Nguyên Đán'],
            ['name' => 'Lễ Tạ Mộ'],
            ['name' => 'Tết Đoan Ngọ'],
            ['name' => 'Thanh Minh (Tảo Mộ)'],
            ['name' => 'Rằm tháng Giêng (Tết Nguyên Tiêu)'],
            ['name' => 'Rằm Trung Thu'],
            ['name' => 'Ông Công, Ông Táo'],
            ['name' => 'Rằm tháng Bảy (cúng Cô Hồn)'],
            ['name' => 'Mùng Một, Rằm hàng tháng'],
        ];

        foreach ($holidays as $h) {
            DB::table('holidays')->updateOrInsert(['name' => $h['name']], $h);
        }

        // 2. Insert default purposes
        $purposes = [
            ['name' => 'Cúng Động Thổ'],
            ['name' => 'Cúng Thần Tài, Thổ Địa'],
            ['name' => 'Cúng Giỗ Chạp'],
            ['name' => 'Đi lễ chùa'],
            ['name' => 'Đồ Cúng 49 ngày'],
        ];

        foreach ($purposes as $p) {
            DB::table('purposes')->updateOrInsert(['name' => $p['name']], $p);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optional rollback action
    }
};
