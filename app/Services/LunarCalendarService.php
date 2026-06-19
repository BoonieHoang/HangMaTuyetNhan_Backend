<?php

namespace {
    // Polyfill cho hàm gregoriantojd nếu extension calendar không được cài đặt (ví dụ trên môi trường Railway)
    if (!function_exists('gregoriantojd')) {
        function gregoriantojd(int $month, int $day, int $year): int {
            if ($month <= 2) {
                $year -= 1;
                $month += 12;
            }
            $A = floor($year / 100);
            $B = 2 - $A + floor($A / 4);
            $JDN = floor(365.25 * ($year + 4716)) + floor(30.6001 * ($month + 1)) + $day + $B - 1524;
            return (int)$JDN;
        }
    }

    // Polyfill cho hàm jdtogregorian nếu extension calendar không được cài đặt
    if (!function_exists('jdtogregorian')) {
        function jdtogregorian(int $julian): string {
            $julian = $julian - 1721119;
            $calc1 = 4 * $julian - 1;
            $year = floor($calc1 / 146097);
            $julian = floor($calc1 - 146097 * $year);
            $day = floor($julian / 4);
            $calc2 = 4 * $day + 3;
            $julian = floor($calc2 / 1461);
            $day = $calc2 - 1461 * $julian;
            $day = floor(($day + 4) / 4);
            $calc3 = 5 * $day - 3;
            $month = floor($calc3 / 153);
            $day = $calc3 - 153 * $month;
            $day = floor(($day + 5) / 5);
            $year = 100 * $year + $julian;
            
            if ($month < 10) {
                $month = $month + 3;
            } else {
                $month = $month - 9;
                $year = $year + 1;
            }
            return "$month/$day/$year";
        }
    }
}

namespace App\Services {

    use Carbon\Carbon;
    use DateTimeZone;
    use VanTran\LunarCalendar\LunarDateTime;

    /**
     * Service chuyên dụng chuyển đổi Dương lịch ↔ Âm lịch Việt Nam
     * Sử dụng thư viện luc-nham/lunar-calendar v2 (namespace VanTran\LunarCalendar)
     * Tính múi giờ GMT+7 (Hà Nội) để tránh lệch ngày so với lịch Trung Quốc (GMT+8)
     */
    class LunarCalendarService
    {
        protected DateTimeZone $tz;

        public function __construct()
        {
            $this->tz = new DateTimeZone('Asia/Ho_Chi_Minh');
        }

        /**
         * Chuyển đổi một ngày dương lịch (Carbon hoặc string Y-m-d) sang âm lịch Việt Nam.
         * Trả về array: ['day' => int, 'month' => int, 'year' => int, 'label' => string]
         * Ví dụ: ['day' => 5, 'month' => 5, 'year' => 2026, 'label' => '05/05 Âm lịch']
         */
        public function gregorianToLunar($date): array
        {
            if ($date instanceof Carbon) {
                $dateStr = $date->format('Y-m-d H:i:s');
            } else {
                $dateStr = (string) $date;
            }

            // v2: dùng createFromGregorian()
            $lunar = LunarDateTime::createFromGregorian($dateStr, $this->tz);

            $day   = (int) $lunar->format('j');
            $month = (int) $lunar->format('n');
            $year  = (int) $lunar->format('Y');

            return [
                'day'   => $day,
                'month' => $month,
                'year'  => $year,
                'label' => sprintf('%02d/%02d Âm lịch', $day, $month),
            ];
        }

        /**
         * Lấy thông tin âm lịch của ngày hôm nay (giờ Việt Nam).
         */
        public function today(): array
        {
            $now = Carbon::now('Asia/Ho_Chi_Minh');
            return $this->gregorianToLunar($now);
        }

        /**
         * Tạo chuỗi mô tả ngày âm lịch hôm nay, dùng để nhúng vào prompt AI.
         * Ví dụ: "Thứ Sáu, ngày 19/06/2026 dương lịch, tức ngày 05/05/2026 Âm lịch"
         */
        public function todayPromptString(): string
        {
            $now   = Carbon::now('Asia/Ho_Chi_Minh');
            $lunar = $this->today();

            $dayNames = [
                0 => 'Chủ Nhật', 1 => 'Thứ Hai', 2 => 'Thứ Ba',
                3 => 'Thứ Tư',   4 => 'Thứ Năm', 5 => 'Thứ Sáu', 6 => 'Thứ Bảy',
            ];

            $dayOfWeek = $dayNames[$now->dayOfWeek];
            $solar     = $now->format('d/m/Y');
            $lunarStr  = sprintf('%02d/%02d/%d', $lunar['day'], $lunar['month'], $lunar['year']);

            return "{$dayOfWeek}, ngày {$solar} dương lịch, tức ngày {$lunarStr} Âm lịch";
        }

        /**
         * Tìm ngày dương lịch tiếp theo của một ngày âm lịch cố định (ngày/tháng).
         * Tìm kiếm từ ngày mai trở đi, trong vòng tối đa 400 ngày.
         * Trả về Carbon (múi giờ VN) hoặc null nếu không tìm thấy.
         */
        public function nextGregorianDate(int $lunarDay, int $lunarMonth): ?Carbon
        {
            $start = Carbon::tomorrow('Asia/Ho_Chi_Minh')->startOfDay();

            for ($i = 0; $i < 400; $i++) {
                $check = $start->copy()->addDays($i);
                $lunar = $this->gregorianToLunar($check);

                if ($lunar['day'] === $lunarDay && $lunar['month'] === $lunarMonth) {
                    return $check;
                }
            }

            return null;
        }
    }
}
