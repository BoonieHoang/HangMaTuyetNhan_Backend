<?php

namespace Database\Seeders;

use App\Models\Ritual;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RitualSeeder extends Seeder
{
    public function run()
    {
        $rituals = [
            [
                'title' => 'Lễ Tất Niên',
                'summary' => 'Nghi lễ tạ ơn trời đất, thần linh và tổ tiên đã phù hộ cho gia đình trong suốt một năm qua.',
                'significance' => 'Lễ Tất niên (hay lễ cúng cuối năm) là một nghi lễ quan trọng của người Việt, đánh dấu sự kết thúc của một năm cũ và chuẩn bị bước sang năm mới. Đây là dịp để gia chủ tạ ơn trời đất, các vị thần linh và tổ tiên đã phù hộ độ trì cho gia đình bình an, mạnh khỏe trong suốt một năm qua.',
                'preparation' => "- Mâm ngũ quả, hoa tươi (thường là hoa cúc hoặc hoa hồng).\n- Hương, đăng (nến), trà, tửu (rượu), quả cau lá trầu.\n- Mâm cỗ mặn (gà luộc, bánh chưng, xôi, các món xào, canh...).\n- Giấy tiền vàng mã (bộ ngũ phương, quần áo mã...).\n- Gạo muối và nước sạch.",
                'prayer_text' => "Nam mô A Di Đà Phật! (3 lần)\n\nCon lạy chín phương Trời, mười phương Chư Phật, Chư Phật mười phương.\nCon kính lạy Hoàng thiên Hậu Thổ chư vị Tôn thần.\nCon kính lạy ngài Kim Niên Đương cai Thái tuế chí đức Tôn thần.\nCon kính lạy các ngài Bản cảnh Thành hoàng chư vị Đại Vương.\nCon kính lạy các ngài Ngũ phương, Ngũ thổ, Phúc đức Tôn thần.\nCon kính lạy ngài Bản gia Thổ địa Long Mạch Tôn thần.\nCon kính lạy các ngài Hồng từ Đại đức chư vị Hiền thần.\n\nHôm nay là ngày 30 tháng Chạp năm Quý Mão.\nTín chủ con là: ...\nNgụ tại: ...\n\nTrước án tọa kính cẩn thưa rằng: Một năm vừa qua, nhờ ơn đức của Thiên, Địa, Thần, Phật và Tổ tiên, gia đình chúng con được bình an, mạnh khỏe, công việc thuận lợi. Nay tiết năm cùng tháng tận, chúng con sắm sanh lễ vật, hoa quả, cơm canh đạm bạc, dâng lên trước án để bày tỏ lòng thành.\n\nChúng con kính mời ngài Kim Niên Đương cai Thái tuế chí đức Tôn thần, các vị Tôn thần, cùng anh linh Tổ tiên, ông bà, cha mẹ và các vị hương linh khuất mặt, khuất mày quanh đây về thụ hưởng lễ vật.\n\nCúi xin các vị phù hộ độ trì cho gia đình chúng con năm mới được an khang thịnh vượng, vạn sự như ý, sức khỏe dồi dào, tai qua nạn khỏi.\n\nChúng con lễ bạc tâm thành, cúi xin chứng giám.\n\nNam mô A Di Đà Phật! (3 lần)",
            ],
            [
                'title' => 'Lễ Giao Thừa',
                'summary' => 'Thời khắc thiêng liêng nhất, chuyển giao giữa năm cũ và năm mới, đón vị quan hành khiển mới.',
                'significance' => 'Lễ Giao thừa (Trừ Tịch) là lễ quan trọng nhất trong năm, nhằm tống cựu nghinh tân, tiễn đưa các vị quan Hành khiển năm cũ và đón các vị quan của năm mới xuống hạ giới để cai quản.',
                'preparation' => "Lễ ngoài trời (Cúng Thiên Địa):\n- Gà trống luộc, xôi gấc.\n- Trái cây, trầu cau, hương, nến.\n- Rượu trắng, nước sạch.\n\nLễ trong nhà (Cúng Tổ Tiên):\n- Mâm cỗ mặn (gà luộc, bánh chưng, nem rán...).\n- Hoa quả, tiền vàng, hương nến.",
                'prayer_text' => "Nam mô A Di Đà Phật! (3 lần)\n\nCon lạy chín phương Trời, mười phương Chư Phật...\nKính lạy các vị Quan hành khiển đương niên, quan Hành binh, các vị Phán quan...\nHôm nay thời khắc Giao thừa thiêng liêng, tín chủ con là... ngụ tại... sắm sửa lễ vật tòng tâm, kính dâng trước án.\nKính lạy các vị tôn thần, tổ tiên nội ngoại chứng giám lòng thành, phù hộ cho gia đạo năm mới bình an, hạnh phúc...",
            ],
            [
                'title' => 'Lễ Hóa Vàng',
                'summary' => 'Nghi lễ tiễn đưa tổ tiên sau những ngày Tết vui vầy cùng con cháu.',
                'significance' => 'Lễ hóa vàng thường được tổ chức vào mùng 3 hoặc mùng 4 Tết. Đây là lễ tiễn đưa tổ tiên về lại cõi vĩnh hằng sau những ngày về vui Tết cùng con cháu, đồng thời cũng là dịp để con cháu bày tỏ lòng thành kính và cầu mong sự phù hộ.',
                'preparation' => "- Mâm cỗ mặn/chay.\n- Tiền vàng, giấy mã (quần áo, đồ dùng cho tổ tiên).\n- Mâm ngũ quả, hoa tươi.\n- Hương, trà, rượu.",
                'prayer_text' => "Nam mô A Di Đà Phật! (3 lần)\n\nCon lạy chín phương Trời...\nTín chủ con là... cùng toàn gia quyến kính bái. Hôm nay ngày mùng 3 tháng Giêng, nhân ngày đầu xuân năm mới, chúng con xin được hóa hóa vàng mã, tiễn đưa các vị tôn thần, tổ tiên về nơi an nghỉ...",
            ],
            [
                'title' => 'Lễ Rằm Tháng Giêng',
                'summary' => 'Cúng cả năm không bằng Rằm tháng Giêng - Ngày lễ quan trọng nhất trong các ngày rằm.',
                'significance' => 'Rằm tháng Giêng (Tết Nguyên Tiêu) là ngày rằm đầu tiên của năm mới. Theo quan niệm dân gian, đây là ngày Thần Phật giáng lâm, mọi lời cầu nguyện đều linh ứng hơn cả.',
                'preparation' => "- Hoa quả, hoa tươi.\n- Chè trôi nước (mong cầu sự trôi chảy, hanh thông).\n- Mâm cỗ chay hoặc mặn tùy điều kiện gia đình.\n- Hương, đèn, trầu cau.",
                'prayer_text' => "Nam mô A Di Đà Phật! (3 lần)\n\nCon kính lạy các vị Thần, Phật...\nHôm nay ngày Rằm tháng Giêng, chúng con thành tâm sắm sửa lễ vật dâng lên trước án, cầu xin sự bình an, hanh thông cho cả năm...",
            ]
        ];

        foreach ($rituals as $r) {
            $r['slug'] = Str::slug($r['title']);
            Ritual::firstOrCreate(
                ['slug' => $r['slug']],
                $r
            );
        }
    }
}
