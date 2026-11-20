<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiseaseGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['ICD01', 'I', 'Bệnh truyền nhiễm và ký sinh trùng', 'A00-B99'],
            ['ICD02', 'II', 'U tân sinh', 'C00-D48'],
            ['ICD03', 'III', 'Bệnh máu, cơ quan tạo máu và một số rối loạn liên quan đến cơ chế miễn dịch', 'D50-D89'],
            ['ICD04', 'IV', 'Bệnh nội tiết, dinh dưỡng và chuyển hóa', 'E00-E90'],
            ['ICD05', 'V', 'Rối loạn tâm thần và hành vi', 'F00-F99'],
            ['ICD06', 'VI', 'Bệnh hệ thần kinh', 'G00-G99'],
            ['ICD07', 'VII', 'Bệnh mắt và phần phụ', 'H00-H59'],
            ['ICD08', 'VIII', 'Bệnh tai và xương chũm', 'H60-H95'],
            ['ICD09', 'IX', 'Bệnh hệ tuần hoàn', 'I00-I99'],
            ['ICD10', 'X', 'Bệnh hệ hô hấp', 'J00-J99'],
            ['ICD11', 'XI', 'Bệnh hệ tiêu hóa', 'K00-K93'],
            ['ICD12', 'XII', 'Bệnh da và mô dưới da', 'L00-L99'],
            ['ICD13', 'XIII', 'Bệnh hệ cơ-xương-khớp và mô liên kết', 'M00-M99'],
            ['ICD14', 'XIV', 'Bệnh hệ tiết niệu-sinh dục', 'N00-N99'],
            ['ICD15', 'XV', 'Thai nghén, sinh đẻ và hậu sản', 'O00-O99'],
            ['ICD16', 'XVI', 'Một số tình trạng bắt nguồn từ thời kỳ chu sinh', 'P00-P96'],
            ['ICD17', 'XVII', 'Dị tật bẩm sinh, biến dạng và bất thường nhiễm sắc thể', 'Q00-Q99'],
            ['ICD18', 'XVIII', 'Triệu chứng, dấu hiệu và những bất thường về lâm sàng, cận lâm sàng, không phân loại mục khác', 'R00-R99'],
            ['ICD19', 'XIX', 'Tổn thương, ngộ độc và một số hậu quả khác do nguyên nhân bên ngoài', 'S00-T98'],
            ['ICD20', 'XX', 'Nguyên nhân bên ngoài gây bệnh và tử vong', 'V01-Y98'],
            ['ICD21', 'XXI', 'Các yếu tố liên quan đến tình trạng sức khỏe và tiếp cận dịch vụ y tế', 'Z00-Z99'],
            ['ICD22', 'XXII', 'Mã cho mục đích đặc biệt', 'U00-U99'],
        ];

        foreach ($groups as [$code, $chapter, $name, $range]) {
            DB::table('disease_groups')->updateOrInsert(
                ['disease_group_code' => $code],
                [
                    'name' => $name,
                    'description' => "ICD-10 Chương {$chapter} ({$range}) theo Thông tư 06/2026/TT-BYT.",
                    'status' => 'ACTIVE',
                    'notes' => 'Nguồn: https://datafiles.chinhphu.vn/cpp/files/vbpq/2026/4/06-byt-kem.pdf',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Chuyển các hồ sơ bệnh cũ sang chương ICD-10 tương ứng trước khi ẩn nhóm tự đặt.
        $chapterByLetter = [
            'A' => 'ICD01', 'B' => 'ICD01', 'C' => 'ICD02', 'D' => 'ICD03',
            'E' => 'ICD04', 'F' => 'ICD05', 'G' => 'ICD06', 'H' => null,
            'I' => 'ICD09', 'J' => 'ICD10', 'K' => 'ICD11', 'L' => 'ICD12',
            'M' => 'ICD13', 'N' => 'ICD14', 'O' => 'ICD15', 'P' => 'ICD16',
            'Q' => 'ICD17', 'R' => 'ICD18', 'S' => 'ICD19', 'T' => 'ICD19',
            'U' => 'ICD22', 'V' => 'ICD20', 'W' => 'ICD20', 'X' => 'ICD20',
            'Y' => 'ICD20', 'Z' => 'ICD21',
        ];
        $groupIds = DB::table('disease_groups')->pluck('id', 'disease_group_code');
        foreach (DB::table('diseases')->select(['id', 'disease_code'])->get() as $disease) {
            $letter = strtoupper(substr((string) $disease->disease_code, 0, 1));
            $groupCode = $chapterByLetter[$letter] ?? null;
            if ($letter === 'H') {
                $groupCode = (int) substr((string) $disease->disease_code, 1, 2) >= 60 ? 'ICD08' : 'ICD07';
            }
            if ($groupCode !== null && isset($groupIds[$groupCode])) {
                DB::table('diseases')->where('id', $disease->id)->update([
                    'disease_group_id' => $groupIds[$groupCode],
                    'updated_at' => now(),
                ]);
            }
        }

        $legacyGroups = DB::table('disease_groups')->whereIn('disease_group_code', ['NBHH', 'NBTM', 'NBTH', 'NBCD', 'NBDL'])->get(['id', 'disease_group_code']);
        foreach ($legacyGroups as $legacyGroup) {
            $isStillReferenced = DB::table('diseases')->where('disease_group_id', $legacyGroup->id)->exists();
            if ($isStillReferenced) {
                DB::table('disease_groups')->where('id', $legacyGroup->id)->update(['status' => 'INACTIVE', 'updated_at' => now()]);
            } else {
                DB::table('disease_groups')->where('id', $legacyGroup->id)->delete();
            }
        }
    }
}
