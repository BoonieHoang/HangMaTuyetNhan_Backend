<?php

namespace App\Services;

use App\Models\SystemConfig;

class VietQRService
{
    public function generateQRUrl($amount, $content): ?string
    {
        // Đọc thẳng từ DB, không cache — đảm bảo cài đặt admin có hiệu lực ngay
        $configs = SystemConfig::pluck('value', 'key')->toArray();

        $bankCode = trim($configs['bank_code'] ?? '');
        $accountNumber = trim($configs['bank_account_number'] ?? '');
        $accountName = trim($configs['bank_account_name'] ?? '');

        if (!$bankCode || !$accountNumber) {
            return null;
        }

        // Clean & standardize bank code (uppercase, alphanumeric)
        $bankCodeClean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $bankCode));
        if ($bankCodeClean === 'VP') {
            $bankCodeClean = 'VPB';
        }

        // Clean & standardize account number (alphanumeric only)
        $accountNumberClean = preg_replace('/[^A-Za-z0-9]/', '', $accountNumber);

        // Clean & standardize account name (no accents, uppercase)
        $accountNameClean = strtoupper($this->removeAccents($accountName));

        $accountNameEncoded = urlencode($accountNameClean);
        $contentEncoded = urlencode($content);

        return "https://img.vietqr.io/image/{$bankCodeClean}-{$accountNumberClean}-compact2.png?amount={$amount}&addInfo={$contentEncoded}&accountName={$accountNameEncoded}";
    }

    private function removeAccents(string $str): string
    {
        $accents = [
            'a' => ['á', 'à', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ', 'ä', 'å', 'æ'],
            'A' => ['Á', 'À', 'Ả', 'Ã', 'Ạ', 'Ă', 'Ắ', 'Ằ', 'Ẳ', 'Ẵ', 'Ặ', 'Â', 'Ấ', 'Ầ', 'Ẩ', 'Ẫ', 'Ậ', 'Ä', 'Å', 'Æ'],
            'e' => ['é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ', 'ë'],
            'E' => ['É', 'È', 'Ẻ', 'Ẽ', 'Ẹ', 'Ê', 'Ế', 'Ề', 'Ể', 'Ễ', 'Ệ', 'Ë'],
            'i' => ['í', 'ì', 'ỉ', 'ĩ', 'ị', 'ï'],
            'I' => ['Í', 'Ì', 'Ỉ', 'Ĩ', 'Ị', 'Ï'],
            'o' => ['ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ', 'ö', 'ø'],
            'O' => ['Ó', 'Ò', 'Ỏ', 'Õ', 'Ọ', 'Ô', 'Ố', 'Ồ', 'Ổ', 'Ỗ', 'Ộ', 'Ơ', 'Ớ', 'Ờ', 'Ở', 'Ỡ', 'Ợ', 'Ö', 'Ø'],
            'u' => ['ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự', 'ü'],
            'U' => ['Ú', 'Ù', 'Ủ', 'Ũ', 'Ụ', 'Ư', 'Ứ', 'Ừ', 'Ử', 'Ữ', 'Ự', 'Ü'],
            'y' => ['ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ', 'ÿ'],
            'Y' => ['Ý', 'Ỳ', 'Ỷ', 'Ỹ', 'Ỵ', 'Ÿ'],
            'd' => ['đ'],
            'D' => ['Đ']
        ];
        foreach ($accents as $nonAccent => $accentList) {
            $str = str_replace($accentList, $nonAccent, $str);
        }
        return $str;
    }
}
