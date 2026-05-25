<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemConfig;

class SystemConfigSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'bank_code',            'value' => '',       'label' => 'Mã ngân hàng',                  'group' => 'payment'],
            ['key' => 'bank_account_number',  'value' => '',       'label' => 'Số tài khoản',                  'group' => 'payment'],
            ['key' => 'bank_account_name',    'value' => '',       'label' => 'Tên chủ tài khoản',             'group' => 'payment'],
            ['key' => 'shipping_fee_default', 'value' => '20000',  'label' => 'Phí vận chuyển mặc định (VNĐ)', 'group' => 'shipping'],
        ];

        foreach ($defaults as $item) {
            // firstOrCreate: chỉ tạo nếu key chưa tồn tại, KHÔNG ghi đè giá trị đã được admin cấu hình
            SystemConfig::firstOrCreate(
                ['key' => $item['key']],
                ['value' => $item['value'], 'label' => $item['label'], 'group' => $item['group']]
            );
        }
    }
}
