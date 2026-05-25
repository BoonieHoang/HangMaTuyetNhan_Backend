<?php

namespace App\Services;

use App\Models\SystemConfig;
use Illuminate\Support\Facades\Cache;

class VietQRService
{
    public function generateQRUrl($amount, $content): ?string
    {
        // Đọc thẳng từ DB, không cache — đảm bảo cài đặt admin có hiệu lực ngay
        $configs = SystemConfig::pluck('value', 'key')->toArray();

        $bankCode = $configs['bank_code'] ?? '';
        $accountNumber = $configs['bank_account_number'] ?? '';
        $accountName = $configs['bank_account_name'] ?? '';

        if (!$bankCode || !$accountNumber) {
            return null;
        }

        $accountNameEncoded = urlencode($accountName);
        $contentEncoded = urlencode($content);

        return "https://img.vietqr.io/image/{$bankCode}-{$accountNumber}-compact2.png?amount={$amount}&addInfo={$contentEncoded}&accountName={$accountNameEncoded}";
    }
}
