<?php

namespace Database\Seeders;

use App\Models\SystemConfig;
use Illuminate\Database\Seeder;

class SystemConfigSeeder extends Seeder
{
    public function run()
    {
        $configs = [
            [
                'key'   => 'bank_name',
                'value' => 'Vietcombank',
                'label' => 'Tên ngân hàng',
            ],
            [
                'key'   => 'bank_account_number',
                'value' => '1234567890',
                'label' => 'Số tài khoản ngân hàng',
            ],
            [
                'key'   => 'bank_account_name',
                'value' => 'NGUYEN VAN A',
                'label' => 'Tên chủ tài khoản',
            ],
            [
                'key'   => 'bank_bin',
                'value' => '970436',
                'label' => 'Mã BIN ngân hàng (VietQR)',
            ],
            [
                'key'   => 'shipping_fee',
                'value' => '30000',
                'label' => 'Phí vận chuyển mặc định (VNĐ)',
            ],
            [
                'key'   => 'free_shipping_threshold',
                'value' => '500000',
                'label' => 'Miễn phí ship khi đơn từ (VNĐ)',
            ],
            [
                'key'   => 'shop_name',
                'value' => 'Lễ Phẩm Tâm An',
                'label' => 'Tên cửa hàng',
            ],
            [
                'key'   => 'shop_phone',
                'value' => '0901234567',
                'label' => 'Số điện thoại cửa hàng',
            ],
            [
                'key'   => 'shop_address',
                'value' => '123 Đường Nguyễn Huệ, Quận 1, TP.HCM',
                'label' => 'Địa chỉ cửa hàng',
            ],
            [
                'key'   => 'vietqr_template',
                'value' => 'compact2',
                'label' => 'Template VietQR (compact/compact2/qr_only)',
            ],
        ];

        foreach ($configs as $config) {
            SystemConfig::updateOrCreate(
                ['key' => $config['key']],
                $config
            );
        }
    }
}
