<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemConfig;

class SystemConfigSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'store_name',           'value' => 'Cửa Hàng Tuyết Nhàn',   'label' => 'Tên cửa hàng',                 'group' => 'general'],
            ['key' => 'store_phone',          'value' => '0332852924',              'label' => 'Số điện thoại hỗ trợ',         'group' => 'general'],
            ['key' => 'bank_code',            'value' => '',                        'label' => 'Mã ngân hàng',                 'group' => 'payment'],
            ['key' => 'bank_account_number',  'value' => '',                        'label' => 'Số tài khoản',                 'group' => 'payment'],
            ['key' => 'bank_account_name',    'value' => '',                        'label' => 'Tên chủ tài khoản',            'group' => 'payment'],
            ['key' => 'shipping_fee_default', 'value' => '30000',                   'label' => 'Phí vận chuyển mặc định (VNĐ)','group' => 'shipping'],
        ];

        foreach ($defaults as $item) {
            SystemConfig::updateOrCreate(
                ['key' => $item['key']],
                ['value' => $item['value'], 'label' => $item['label'], 'group' => $item['group']]
            );
        }
    }
}
