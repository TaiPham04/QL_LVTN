<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\DB;

class DiemGiuaKyExport
{
    /**
     * Export điểm giữa kỳ ra Excel
     */
    public function export($magv)
    {
        // Lấy danh sách sinh viên đã chấm điểm
        $results = DB::table('diem_giuaky as d')
            ->join('sinhvien as s', 'd.mssv', '=', 's.mssv')
            ->leftJoin('detai as dt', 'd.mssv', '=', 'dt.mssv')
            ->leftJoin('nhom as n', 'dt.nhom_id', '=', 'n.id')
            ->where('d.magv_cham', $magv)
            ->whereNotNull('d.diem')
            ->select(
                'n.tennhom as nhom',
                's.mssv',
                's.hoten',
                's.lop',
                'n.tendt',  // ← SỬA: LẤY TỬ nhom TABLE, KHÔNG PHẢI dt.tendt
                'd.diem',
                'd.ketqua',
                'd.nhanxet'
            )
            ->orderBy('n.tennhom')
            ->orderBy('s.mssv')
            ->get();

        // Kiểm tra nếu không có dữ liệu
        if ($results->isEmpty()) {
            throw new \Exception('Không có sinh viên nào được chấm điểm!');
        }

        // Tạo Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Điểm Giữa Kỳ');

        // ===================================
        // PHẦN HEADER
        // ===================================
        
        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'BẢNG ĐIỂM GIỮA KỲ');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '000000']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Thông tin giảng viên
        $giangVien = DB::table('giangvien')->where('magv', $magv)->first();
        
        $sheet->mergeCells('A2:I2');
        $sheet->setCellValue('A2', 'Giảng viên: ' . ($giangVien->hoten ?? ''));
        
        $sheet->mergeCells('A3:I3');
        $sheet->setCellValue('A3', 'Ngày xuất: ' . date('d/m/Y H:i:s'));

        foreach ([2, 3] as $row) {
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
            ]);
        }

        // ===================================
        // PHẦN TABLE HEADER
        // ===================================

        $headerRow = 5;
        
        $headers = ['STT', 'Nhóm', 'MSSV', 'Họ Tên', 'Lớp', 'Đề Tài', 'Điểm', 'Kết Quả', 'Nhận Xét'];
        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

        foreach ($headers as $index => $header) {
            $cell = $columns[$index] . $headerRow;
            $sheet->setCellValue($cell, $header);
        }

        // Style cho header
        $sheet->getStyle("A{$headerRow}:I{$headerRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // ===================================
        // PHẦN DATA
        // ===================================

        $currentRow = $headerRow + 1;
        $stt = 1;

        foreach ($results as $item) {
            $sheet->setCellValue("A{$currentRow}", $stt);
            $sheet->setCellValue("B{$currentRow}", $item->nhom ?? 'Chưa có');
            $sheet->setCellValue("C{$currentRow}", $item->mssv);
            $sheet->setCellValue("D{$currentRow}", $item->hoten);
            $sheet->setCellValue("E{$currentRow}", $item->lop ?? 'N/A');
            $sheet->setCellValue("F{$currentRow}", $item->tendt ?? 'Chưa có đề tài');
            $sheet->setCellValue("G{$currentRow}", $item->diem ?? '');
            $sheet->setCellValue("H{$currentRow}", $this->formatKetQua($item->ketqua));
            $sheet->setCellValue("I{$currentRow}", $item->nhanxet ?? '');

            $currentRow++;
            $stt++;
        }

        // ===================================
        // STYLE CHO DATA
        // ===================================

        $dataStartRow = $headerRow + 1;
        $dataEndRow = $currentRow - 1;

        if ($dataEndRow >= $dataStartRow) {
            // Border cho toàn bộ data
            $sheet->getStyle("A{$dataStartRow}:I{$dataEndRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ]
            ]);

            // Center align cho các cột
            $sheet->getStyle("A{$dataStartRow}:A{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$dataStartRow}:B{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$dataStartRow}:C{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$dataStartRow}:E{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$dataStartRow}:G{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$dataStartRow}:H{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // ===================================
        // ĐIỀU CHỈNH ĐỘ RỘNG CỘT
        // ===================================

        $sheet->getColumnDimension('A')->setWidth(6);   // STT
        $sheet->getColumnDimension('B')->setWidth(12);  // Nhóm
        $sheet->getColumnDimension('C')->setWidth(15);  // MSSV
        $sheet->getColumnDimension('D')->setWidth(25);  // Họ Tên
        $sheet->getColumnDimension('E')->setWidth(12);  // Lớp
        $sheet->getColumnDimension('F')->setWidth(40);  // Đề Tài
        $sheet->getColumnDimension('G')->setWidth(10);  // Điểm
        $sheet->getColumnDimension('H')->setWidth(18);  // Kết Quả
        $sheet->getColumnDimension('I')->setWidth(35);  // Nhận Xét

        // Auto height cho các row
        foreach (range($dataStartRow, $dataEndRow) as $row) {
            $sheet->getRowDimension($row)->setRowHeight(-1);
        }

        // ===================================
        // TẠO FILE VÀ RETURN
        // ===================================

        $fileName = "DiemGiuaKy_{$magv}_" . date('YmdHis') . '.xlsx';
        $filePath = storage_path('app/public/exports/' . $fileName);

        // Tạo thư mục nếu chưa có
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        // Ghi file
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return $filePath;
    }

    /**
     * Format kết quả sang tiếng Việt
     */
    private function formatKetQua($ketqua)
    {
        switch($ketqua) {
            case 'duoc_tieptuc':
                return 'Được tiếp tục';
            case 'khong_duoc_tieptuc':
                return 'Không được tiếp tục';
            case 'chua_danh_gia':
                return 'Chưa đánh giá';
            default:
                return 'Chưa đánh giá';
        }
    }
}