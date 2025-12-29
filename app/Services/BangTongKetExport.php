<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class BangTongKetExport
{
    protected $students;
    protected $visibleColumns;

    public function __construct($students, $visibleColumns = [])
    {
        $this->students = $students;
        
        // Mặc định nếu không có visible columns
        if (empty($visibleColumns)) {
            $this->visibleColumns = ['mssv', 'tennhom', 'hoten', 'lop', 'tendt', 'ten_gvhd', 'diem_gvhd', 'ten_gvpb', 'diem_gvpb', 'diem_hoidong', 'diem_tong'];
        } else {
            $this->visibleColumns = $visibleColumns;
        }
    }

    public function export()
    {
        // ✅ Định nghĩa tất cả cột có thể có
        $allColumns = [
            'mssv' => 'MSSV',
            'tennhom' => 'Nhóm',
            'hoten' => 'Tên Sinh Viên',
            'lop' => 'LỚP',
            'tendt' => 'Đề Tài',
            'ten_gvhd' => 'GV Hướng Dẫn',
            'diem_gvhd' => 'Điểm GVHD',
            'ten_gvpb' => 'GV Phản Biện',
            'diem_gvpb' => 'Điểm GVPB',
            'diem_hoidong' => 'Điểm HD',
            'diem_tong' => 'Điểm Tổng'
        ];

        // ✅ Lọc chỉ lấy cột hiển thị theo thứ tự
        $headers = [];
        $columnOrder = ['mssv', 'tennhom', 'hoten', 'lop', 'tendt', 'ten_gvhd', 'diem_gvhd', 'ten_gvpb', 'diem_gvpb', 'diem_hoidong', 'diem_tong'];
        
        foreach ($columnOrder as $col) {
            if (in_array($col, $this->visibleColumns)) {
                $headers[$col] = $allColumns[$col];
            }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bảng Tổng Kết');

        // ✅ Tiêu đề
        $sheet->setCellValue('A1', 'BẢNG TỔNG KẾT ĐIỂM LUẬN VĂN TỐT NGHIỆP');
        $sheet->mergeCells('A1:' . chr(64 + count($headers)) . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ✅ Ngày xuất
        $sheet->setCellValue('A2', 'Ngày xuất: ' . now()->format('d/m/Y'));
        $sheet->mergeCells('A2:' . chr(64 + count($headers)) . '2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ✅ Header row
        $col = 'A';
        $colWidth = [
            'mssv' => 15,
            'tennhom' => 12,
            'hoten' => 20,
            'lop' => 10,
            'tendt' => 25,
            'ten_gvhd' => 15,
            'diem_gvhd' => 12,
            'ten_gvpb' => 15,
            'diem_gvpb' => 12,
            'diem_hoidong' => 12,
            'diem_tong' => 12
        ];

        foreach ($headers as $columnKey => $headerLabel) {
            $sheet->setCellValue($col . '4', $headerLabel);
            $sheet->getStyle($col . '4')->getFont()->setBold(true)->setColor(new Color('FFFFFFFF'));
            $sheet->getStyle($col . '4')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF0D6EFD'); // Màu xanh
            $sheet->getStyle($col . '4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Đặt độ rộng cột
            $sheet->getColumnDimension($col)->setWidth($colWidth[$columnKey] ?? 15);
            
            $col++;
        }

        // ✅ Ghi dữ liệu
        $row = 5;
        foreach ($this->students as $student) {
            $col = 'A';
            
            // ✅ Lặp theo headers (đã được lọc) để đảm bảo thứ tự cột đúng
            foreach ($headers as $columnKey => $headerLabel) {
                $value = '';
                
                // Xử lý giá trị theo cột
                if ($columnKey === 'diem_gvhd' || $columnKey === 'diem_gvpb' || $columnKey === 'diem_hoidong' || $columnKey === 'diem_tong') {
                    // Nếu điểm <= 0 hoặc null, hiển thị rỗng
                    if (isset($student->$columnKey) && $student->$columnKey && $student->$columnKey > 0) {
                        $value = $student->$columnKey;
                    }
                } else {
                    $value = $student->$columnKey ?? '';
                }
                
                $sheet->setCellValue($col . $row, $value);

                // ✅ Căn giữa các cột số
                if (in_array($columnKey, ['mssv', 'lop', 'diem_gvhd', 'diem_gvpb', 'diem_hoidong', 'diem_tong', 'tennhom'])) {
                    $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $col++;
            }

            $row++;
        }

        // ✅ Freeze header
        $sheet->freezePane('A5');

        // ✅ Xuất file
        $filename = 'BangTongKet_' . now()->format('Ymd_His') . '.xlsx';
        $filepath = storage_path('app/temp/' . $filename);
        
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        // ✅ Download file
        return response()->download($filepath)->deleteFileAfterSend(true);
    }
}
?>