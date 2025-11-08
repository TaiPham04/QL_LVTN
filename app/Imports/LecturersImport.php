<?php

namespace App\Imports;

use App\Models\Lecturer;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;
use Exception;

class LecturersImport implements ToModel, WithHeadingRow, WithEvents
{
    // Danh sách cột chuẩn theo DB
    protected $expectedHeaders = ['magv', 'hoten', 'email'];

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                $sheet = $event->reader->getDelegate()->getActiveSheet();
                $headerRow = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1')[0];

                if (!$headerRow) {
                    throw new Exception("⚠️ File Excel không có hàng tiêu đề (header). Vui lòng kiểm tra lại!");
                }

                // Chuyển tiêu đề về chữ thường, bỏ khoảng trắng
                $normalizedHeader = array_map(fn($h) => strtolower(trim($h)), $headerRow);

                // Kiểm tra cột thiếu
                $missing = array_diff($this->expectedHeaders, $normalizedHeader);
                if (!empty($missing)) {
                    throw new Exception("⚠️ File Excel thiếu cột: " . implode(', ', $missing));
                }

                // Không cần báo cột dư – hệ thống tự bỏ qua
            },
        ];
    }

    public function model(array $row)
    {
        // Bỏ qua nếu thiếu thông tin bắt buộc
        if (empty($row['magv']) || empty($row['hoten'])) {
            return null;
        }

        // Nếu mã giảng viên đã tồn tại thì bỏ qua
        if (Lecturer::where('magv', trim($row['magv']))->exists()) {
            return null;
        }

        // Thêm giảng viên mới
        return new Lecturer([
            'magv'  => trim($row['magv']),
            'hoten' => trim($row['hoten']),
            'email' => $row['email'] ?? null,
        ]);
    }
}
