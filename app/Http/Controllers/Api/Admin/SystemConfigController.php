<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SystemConfigController extends Controller
{
    public function index()
    {
        $configs = SystemConfig::pluck('value', 'key');
        return response()->json($configs);
    }

    public function update(Request $request)
    {
        $request->validate([
            'configs' => 'required|array',
            'configs.bank_code' => 'nullable|string',
            'configs.bank_account_number' => 'nullable|string',
            'configs.bank_account_name' => 'nullable|string',
            'configs.shipping_fee_default' => 'nullable|numeric',
            'configs.store_name' => 'nullable|string',
            'configs.store_phone' => 'nullable|string',
        ]);

        foreach ($request->configs as $key => $value) {
            if ($value !== null) {
                SystemConfig::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value, 'updated_at' => now()]
                );
            }
        }

        Cache::forget('system_configs');

        return response()->json(['message' => 'Cập nhật cấu hình thành công']);
    }
}
