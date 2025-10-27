<?php

namespace App\Imports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\BeforeImport;
use Exception;

class StudentsImport implements ToModel, WithHeadingRow, WithEvents, WithValidation
{
    // Các cột hợp lệ cần có trong Excel
    protected $expectedHeaders = ['mssv', 'hoten', 'lop', 'email', 'sdt'];

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                $sheet = $event->reader->getDelegate()->getActiveSheet();
                $headerRow = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1')[0];

                if (!$headerRow) {
                    throw new Exception("File Excel không có hàng tiêu đề (header). Vui lòng tải mẫu chuẩn.");
                }

                // Chuyển header về dạng thường, không dấu cách, chữ thường
                $normalizedHeader = array_map(function ($h) {
                    return strtolower(trim($h));
                }, $headerRow);

                // Kiểm tra cột thiếu
                $missing = array_diff($this->expectedHeaders, $normalizedHeader);
                if (!empty($missing)) {
                    throw new Exception("File Excel thiếu cột: " . implode(', ', $missing));
                }

                // Không cần báo dư — chỉ bỏ qua các cột dư thôi
            },
        ];
    }

    public function model(array $row)
    {
        // Nếu MSSV trùng thì bỏ qua (không thêm)
        if (Student::where('mssv', $row['mssv'])->exists()) {
            return null;
        }

        // Chỉ lấy các cột hợp lệ (bỏ cột dư)
        return new Student([
            'mssv'  => $row['mssv'] ?? null,
            'hoten' => $row['hoten'] ?? null,
            'lop'   => $row['lop'] ?? null,
            'email' => $row['email'] ?? null,
            'sdt'   => $row['sdt'] ?? null,
        ]);
    }

    public function rules(): array
    {
        return [
            '*.mssv'  => 'required',
            '*.hoten' => 'required',
            '*.lop'   => 'required',
            '*.email' => 'nullable|email',
            '*.sdt'   => 'nullable',
        ];
    }

    public function customValidationMessages()
    {
        return [
            '*.mssv.required'  => 'Cột MSSV bị thiếu hoặc trống.',
            '*.hoten.required' => 'Cột Họ tên bị thiếu hoặc trống.',
            '*.lop.required'   => 'Cột Lớp bị thiếu hoặc trống.',
            '*.email.email'    => 'Email không hợp lệ.',
        ];
    }
}
