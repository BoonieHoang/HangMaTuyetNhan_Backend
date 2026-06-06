<?php

namespace App\Services;

use PayOS\PayOS;
use Exception;
use Illuminate\Support\Facades\Log;

class PayOSService
{
    protected PayOS $payOS;

    public function __construct()
    {
        $this->payOS = new PayOS(
            config('services.payos.client_id'),
            config('services.payos.api_key'),
            config('services.payos.checksum_key')
        );
    }

    /**
     * Tạo link thanh toán PayOS cho đơn hàng.
     * Trả về mảng chứa checkoutUrl, qrCode, paymentLinkId, v.v.
     *
     * @throws Exception
     */
    public function createPaymentLink(string $orderCode, int $amount, string $description, array $items = []): array
    {
        // PayOS yêu cầu orderCode là số nguyên dương duy nhất
        // Ta dùng phần số trong order_code (VD: ORD-20260606-0001 → 202606060001)
        $numericCode = (int) preg_replace('/\D/', '', $orderCode);

        $data = [
            'orderCode'  => $numericCode,
            'amount'     => $amount,
            // Tối đa 25 ký tự, không dấu
            'description' => $this->sanitizeDescription($description),
            'returnUrl'  => config('services.payos.return_url'),
            'cancelUrl'  => config('services.payos.cancel_url'),
            'items'      => $items,
        ];

        try {
            $response = $this->payOS->createPaymentLink($data);
            return (array) $response;
        } catch (Exception $e) {
            Log::error('[PayOS] Tạo link thanh toán thất bại', [
                'order_code' => $orderCode,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Xác thực và parse dữ liệu Webhook từ PayOS.
     * Trả về dữ liệu đã xác thực hoặc null nếu chữ ký không hợp lệ.
     */
    public function verifyWebhookData(array $webhookBody): ?array
    {
        try {
            $verified = $this->payOS->verifyPaymentWebhookData($webhookBody);
            return (array) $verified;
        } catch (Exception $e) {
            Log::warning('[PayOS] Xác thực webhook thất bại', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Giới hạn độ dài mô tả thanh toán và loại bỏ ký tự không hợp lệ.
     * PayOS chỉ chấp nhận tối đa 25 ký tự ASCII không dấu.
     */
    private function sanitizeDescription(string $text): string
    {
        $accents = [
            'a' => ['á','à','ả','ã','ạ','ă','ắ','ằ','ẳ','ẵ','ặ','â','ấ','ầ','ẩ','ẫ','ậ'],
            'A' => ['Á','À','Ả','Ã','Ạ','Ă','Ắ','Ằ','Ẳ','Ẵ','Ặ','Â','Ấ','Ầ','Ẩ','Ẫ','Ậ'],
            'e' => ['é','è','ẻ','ẽ','ẹ','ê','ế','ề','ể','ễ','ệ'],
            'E' => ['É','È','Ẻ','Ẽ','Ẹ','Ê','Ế','Ề','Ể','Ễ','Ệ'],
            'i' => ['í','ì','ỉ','ĩ','ị'],
            'I' => ['Í','Ì','Ỉ','Ĩ','Ị'],
            'o' => ['ó','ò','ỏ','õ','ọ','ô','ố','ồ','ổ','ỗ','ộ','ơ','ớ','ờ','ở','ỡ','ợ'],
            'O' => ['Ó','Ò','Ỏ','Õ','Ọ','Ô','Ố','Ồ','Ổ','Ỗ','Ộ','Ơ','Ớ','Ờ','Ở','Ỡ','Ợ'],
            'u' => ['ú','ù','ủ','ũ','ụ','ư','ứ','ừ','ử','ữ','ự'],
            'U' => ['Ú','Ù','Ủ','Ũ','Ụ','Ư','Ứ','Ừ','Ử','Ữ','Ự'],
            'y' => ['ý','ỳ','ỷ','ỹ','ỵ'],
            'Y' => ['Ý','Ỳ','Ỷ','Ỹ','Ỵ'],
            'd' => ['đ'],
            'D' => ['Đ'],
        ];

        foreach ($accents as $plain => $list) {
            $text = str_replace($list, $plain, $text);
        }

        // Giữ lại chỉ ký tự ASCII hợp lệ
        $text = preg_replace('/[^A-Za-z0-9 \-]/', '', $text);

        return substr(trim($text), 0, 25);
    }
}
