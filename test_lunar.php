<?php
require 'vendor/autoload.php';

use Carbon\Carbon;
use App\Services\LunarCalendarService;

$s = new LunarCalendarService();

for ($day = 12; $day <= 18; $day++) {
    $date = Carbon::create(2026, 7, $day, 0, 0, 0, 'Asia/Ho_Chi_Minh');
    $res = $s->gregorianToLunar($date);
    echo "Solar: " . $date->format('Y-m-d H:i:s T') . " -> Lunar: " . $res['day'] . "/" . $res['month'] . "/" . $res['year'] . " (" . $res['label'] . ")\n";
}
