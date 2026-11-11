<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\ExaminationRoom;
use App\Models\Specialty;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

final class DoctorScheduleImportService
{
    private const HEADERS = ['Mã bác sĩ', 'Ngày làm việc', 'Ca', 'Mã chuyên khoa', 'Mã phòng', 'Ghi chú'];
    private const MAX_ROWS = 1000;

    /** Mẫu chỉ có tiêu đề, không tự tạo lịch hoặc thông tin bác sĩ. */
    public function template(): Spreadsheet
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Lich lam viec');
        $sheet->fromArray([self::HEADERS], null, 'A1');
        $sheet->freezePane('A2');
        // Hướng dẫn ngay trong tiêu đề, không tạo sẵn dòng dữ liệu giả.
        $sheet->getComment('A1')->getText()->createText('Dùng mã bác sĩ có trong hệ thống, ví dụ BS000001.');
        $sheet->getComment('B1')->getText()->createText('Nhập ngày Excel hoặc văn bản theo dạng dd/mm/yyyy, ví dụ 15/12/2026.');
        $sheet->getComment('C1')->getText()->createText('Nhập Sáng hoặc Chiều. Một ngày làm cả hai ca thì nhập hai dòng.');
        $sheet->getComment('D1')->getText()->createText('Nhập mã chuyên khoa phù hợp với bác sĩ và phòng.');
        $sheet->getComment('E1')->getText()->createText('Nhập mã phòng khám thuộc chuyên khoa tương ứng.');
        $sheet->getComment('F1')->getText()->createText('Ghi chú không bắt buộc, tối đa 2000 ký tự.');
        foreach (range('A', 'F') as $column) $sheet->getColumnDimension($column)->setWidth(23);
        return $book;
    }

    /** Đọc dữ liệu thô, không tính công thức trong file do người dùng cung cấp. */
    public function read(string $path): array
    {
        if (! class_exists(\ZipArchive::class)) {
            throw ValidationException::withMessages(['file' => 'Máy chủ cần bật phần mở rộng PHP ZIP để đọc Excel.']);
        }
        try {
            if (! is_file($path) || filesize($path) > 2 * 1024 * 1024) throw new \RuntimeException('File phải có dung lượng không quá 2 MB.');
            // Chặn file nén bất thường trước khi thư viện giải nén toàn bộ nội dung.
            $archive = new \ZipArchive;
            if ($archive->open($path) !== true) throw new \RuntimeException('File XLSX không hợp lệ.');
            $size = 0;
            for ($i = 0; $i < $archive->numFiles; $i++) {
                $entry = $archive->statIndex($i);
                $size += $entry['size'] ?? 0;
                if ($archive->numFiles > 300 || $size > 32 * 1024 * 1024) {
                    $archive->close();
                    throw new \RuntimeException('File Excel có nội dung giải nén quá lớn.');
                }
            }
            $archive->close();
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $book = $reader->load($path);
            if ($book->getSheetCount() !== 1) throw new \RuntimeException('File phải có đúng một trang tính.');
            $sheet = $book->getSheet(0);
            if ($sheet->getHighestDataRow() > self::MAX_ROWS + 1 || $sheet->getHighestDataColumn() !== 'F') {
                throw new \RuntimeException('File phải có đúng 6 cột và tối đa 1000 dòng dữ liệu.');
            }
            $header = [];
            foreach (range('A', 'F') as $column) $header[] = trim((string) $sheet->getCell($column.'1')->getValue());
            if ($header !== self::HEADERS) throw new \RuntimeException('Tiêu đề cột không đúng mẫu. Vui lòng tải lại file mẫu.');
            $rows = [];
            $rowErrors = [];
            for ($number = 2; $number <= $sheet->getHighestDataRow(); $number++) {
                $values = [];
                $hasFormula = false;
                foreach (range('A', 'F') as $column) {
                    $cell = $sheet->getCell($column.$number);
                    if ($cell->getDataType() === DataType::TYPE_FORMULA) $hasFormula = true;
                    $values[] = $cell->getValue();
                }
                if ($hasFormula) $rowErrors[] = 'Dòng '.$number.': không sử dụng công thức.';
                if (count(array_filter($values, fn ($value) => $value !== null && trim((string) $value) !== '')) === 0) {
                    $rowErrors[] = 'Dòng '.$number.': dòng dữ liệu để trống.';
                }
                $rows[$number] = $values;
            }
            $book->disconnectWorksheets();
            if ($rowErrors) throw ValidationException::withMessages(['file' => $rowErrors]);
            if ($rows === []) throw new \RuntimeException('File không có dòng lịch làm việc.');
            return $rows;
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['file' => 'Không thể đọc file Excel: '.$exception->getMessage()]);
        }
    }

    /** Mọi dòng được khóa và kiểm tra lại trước khi ghi; lỗi bất kỳ sẽ hoàn tác toàn bộ. */
    public function import(array $rows): array
    {
        return DB::transaction(function () use ($rows): array {
            $errors = [];
            $parsed = [];
            foreach ($rows as $line => [$doctorCode, $date, $shift, $specialtyCode, $roomCode, $notes]) {
                $doctor = Doctor::where('doctor_code', trim((string) $doctorCode))->first();
                $specialty = Specialty::where('specialty_code', trim((string) $specialtyCode))->first();
                $room = ExaminationRoom::where('room_code', trim((string) $roomCode))->first();
                $shift = mb_strtolower(trim((string) $shift));
                $shift = match ($shift) { 'sáng' => 'MORNING', 'chiều' => 'AFTERNOON', default => null };
                try {
                    if (is_int($date) || is_float($date)) {
                        $workDate = $date >= 1 && $date <= 2958465 && floor($date) === (float) $date
                            ? CarbonImmutable::instance(Date::excelToDateTimeObject((float) $date))->format('Y-m-d') : null;
                    } else {
                        $text = trim((string) $date);
                        $parsedDate = CarbonImmutable::createFromFormat('!d/m/Y', $text);
                        $workDate = $parsedDate && $parsedDate->format('d/m/Y') === $text ? $parsedDate->format('Y-m-d') : null;
                    }
                } catch (\Throwable) { $workDate = null; }
                $problems = [];
                if (! $doctor) $problems[] = 'mã bác sĩ không tồn tại';
                if (! $specialty) $problems[] = 'mã chuyên khoa không tồn tại';
                if (! $room) $problems[] = 'mã phòng không tồn tại';
                if (! $shift) $problems[] = 'ca phải là Sáng hoặc Chiều';
                if (! $workDate) $problems[] = 'ngày phải hợp lệ theo dạng dd/mm/yyyy hoặc ngày Excel';
                if (mb_strlen((string) $notes) > 2000) $problems[] = 'ghi chú vượt quá 2000 ký tự';
                if ($problems) {
                    $errors[] = 'Dòng '.$line.': '.implode('; ', $problems).'.';
                    continue;
                }
                [$start, $end] = app(DoctorShiftService::class)->times($shift);
                $parsed[$line] = compact('doctor', 'specialty', 'room', 'workDate', 'start', 'end', 'notes');
            }
            if ($errors) throw ValidationException::withMessages(['file' => $errors]);

            // Khóa các đối tượng theo thứ tự ổn định để giảm xung đột giữa nhiều lượt nhập.
            foreach (['doctor' => Doctor::class, 'specialty' => Specialty::class, 'room' => ExaminationRoom::class] as $key => $model) {
                $ids = collect($parsed)->map(fn ($row) => $row[$key]->id)->unique()->sort()->values();
                $model::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            }
            $created = [];
            foreach ($parsed as $line => $row) {
                // Đọc có khóa để thấy dữ liệu vừa được giao dịch khác hoàn tất trên MySQL.
                $doctor = Doctor::whereKey($row['doctor']->id)->lockForUpdate()->firstOrFail();
                $specialty = Specialty::whereKey($row['specialty']->id)->lockForUpdate()->firstOrFail();
                $room = ExaminationRoom::whereKey($row['room']->id)->lockForUpdate()->firstOrFail();
                $conflict = DoctorSchedule::whereDate('work_date', $row['workDate'])->where('status', 'ACTIVE')
                    ->where('start_time', '<', $row['end'])->where('end_time', '>', $row['start'])
                    ->where(fn ($q) => $q->where('doctor_id', $doctor->id)->orWhere('examination_room_id', $room->id))
                    ->lockForUpdate()->first();
                $problems = [];
                if ($doctor->status !== 'ACTIVE') $problems[] = 'bác sĩ đã ngừng hoạt động';
                if ($specialty->status !== 'ACTIVE') $problems[] = 'chuyên khoa đã ngừng hoạt động';
                if ($room->status !== 'ACTIVE') $problems[] = 'phòng đã ngừng hoạt động';
                if (! $doctor->canPracticeSpecialty($specialty->id)) $problems[] = 'bác sĩ không thuộc chuyên khoa';
                if ((int) $room->specialty_id !== (int) $specialty->id) $problems[] = 'phòng không thuộc chuyên khoa';
                if ($conflict) {
                    $problems[] = (int) $conflict->doctor_id === (int) $doctor->id
                        ? 'bác sĩ đã có lịch trùng thời gian' : 'phòng đã có lịch trùng thời gian';
                }
                if ($problems) {
                    $errors[] = 'Dòng '.$line.' ('.$doctor->doctor_code.', '.$row['workDate'].', '.$row['start'].'–'.$row['end'].'): '.implode('; ', $problems).'.';
                    continue;
                }
                $schedule = DoctorSchedule::create([
                    'doctor_id' => $doctor->id, 'specialty_id' => $specialty->id, 'examination_room_id' => $room->id,
                    'work_date' => $row['workDate'], 'start_time' => $row['start'], 'end_time' => $row['end'],
                    'status' => 'ACTIVE', 'notes' => $row['notes'] ?: null,
                ]);
                $created[] = app(DoctorShiftService::class)->createSlots($schedule);
            }
            if ($errors) throw ValidationException::withMessages(['file' => $errors]);
            return ['schedules' => count($created), 'slots' => array_sum($created)];
        }, 3);
    }
}
