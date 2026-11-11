<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use ZipArchive;

class DoctorScheduleWorkbook
{
    private const HEADERS = ['Ngày', 'Ca sáng', 'Ca chiều'];

    public function read(string $path, string $month): array
    {
        $this->checkArchive($path);
        $reader = new XlsxReader;
        try {
            $info = $reader->listWorksheetInfo($path);
            if (count($info) !== 1 || $info[0]['totalRows'] > 32 || $info[0]['totalColumns'] !== 3) {
                $this->invalid('File cần một trang tính, đúng ba cột và tối đa 31 dòng dữ liệu.');
            }
            $book = $reader->load($path);
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception | \PhpOffice\PhpSpreadsheet\Exception $error) {
            $this->invalid('Không đọc được file Excel .xlsx.');
        }

        try {
            $sheet = $book->getSheet(0);
            $headers = array_map(fn ($column) => trim((string) $sheet->getCell($column.'1')->getValue()), ['A', 'B', 'C']);
            if ($headers !== self::HEADERS) {
                $this->invalid('Dòng đầu cần có đúng các cột Ngày, Ca sáng và Ca chiều.');
            }
            $days = [];
            for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
                $values = [];
                foreach (['A', 'B', 'C'] as $column) {
                    $cell = $sheet->getCell($column.$row);
                    if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                        $this->invalid("Dòng $row chứa công thức. Hãy nhập giá trị trực tiếp.");
                    }
                    $values[] = $cell->getValue();
                }
                if (array_reduce($values, fn (bool $empty, $value) => $empty && blank($value), true)) {
                    continue;
                }
                $date = $this->date($values[0], $book->getExcelCalendar());
                if ($date === null || substr($date, 0, 7) !== $month) {
                    $this->invalid("Ngày ở dòng $row không hợp lệ hoặc nằm ngoài tháng đã chọn.");
                }
                if (isset($days[$date])) {
                    $this->invalid("Ngày ở dòng $row bị trùng.");
                }
                foreach (['MORNING' => 1, 'AFTERNOON' => 2] as $code => $column) {
                    $days[$date][$code] = match (mb_strtoupper(trim((string) $values[$column]))) {
                        'LÀM' => 'WORKING',
                        'NGHỈ' => 'OFF',
                        default => $this->invalid("Trạng thái ở dòng $row phải là LÀM hoặc NGHỈ."),
                    };
                }
            }
            if ($days === []) {
                $this->invalid('File chưa có ngày làm việc hoặc ngày nghỉ để nhập.');
            }
            ksort($days);

            return $days;
        } finally {
            $book->disconnectWorksheets();
        }
    }

    public function template(): Spreadsheet
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Lịch bác sĩ');
        $sheet->fromArray(self::HEADERS, null, 'A1');
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet->freezePane('A2');
        foreach (['A', 'B', 'C'] as $column) {
            $sheet->getColumnDimension($column)->setWidth(18);
        }
        $sheet->getStyle('A2:A32')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $sheet->setDataValidation('B2:C32', (new DataValidation)
            ->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(true)
            ->setShowDropDown(true)
            ->setShowErrorMessage(true)
            ->setErrorTitle('Trạng thái không hợp lệ')
            ->setError('Chọn LÀM hoặc NGHỈ.')
            ->setFormula1('"LÀM,NGHỈ"'));

        return $book;
    }

    private function date(mixed $value, int $calendar): ?string
    {
        if ((is_int($value) || is_float($value)) && $value >= 1 && $value < 2957000 && floor($value) == $value) {
            $previous = Date::getExcelCalendar();
            Date::setExcelCalendar($calendar);
            try {
                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            } finally {
                Date::setExcelCalendar($previous);
            }
        }
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);
        foreach (['Y-m-d', 'd/m/Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!'.$format, $value);
            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function checkArchive(string $path): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->invalid('PHP chưa bật extension zip để đọc file Excel.');
        }
        $archive = new ZipArchive;
        if ($archive->open($path, ZipArchive::RDONLY) !== true) {
            $this->invalid('File không phải là bảng tính .xlsx hợp lệ.');
        }
        try {
            $size = 0;
            if ($archive->numFiles > 128 || $archive->locateName('[Content_Types].xml') === false) {
                $this->invalid('Cấu trúc file Excel không hợp lệ.');
            }
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $entry = $archive->statIndex($index);
                if (! $entry || ($size += $entry['size']) > 16 * 1024 * 1024) {
                    $this->invalid('Nội dung giải nén của file Excel quá lớn.');
                }
            }
        } finally {
            $archive->close();
        }
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['workbook' => $message]);
    }
}
