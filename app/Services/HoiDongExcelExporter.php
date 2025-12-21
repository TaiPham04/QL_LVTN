<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\{HoiDong, Detai};
use Illuminate\Support\Facades\DB;

class HoiDongExcelExporter
{
    /**
     * Export danh sách đề tài của hội đồng ra Excel
     */
    public function export($mahd)
    {
        // Lấy thông tin hội đồng
        $hoiDong = HoiDong::findOrFail($mahd);

        // Lấy thành viên hội đồng
        $thanhVien = DB::table('thanhvienhoidong as tv')
            ->join('giangvien as g', 'tv.magv', '=', 'g.magv')
            ->where('tv.mahd', $mahd)
            ->select('g.hoten')
            ->pluck('hoten')
            ->toArray();

        // Lấy danh sách đề tài và sinh viên
        $danhSachDeTai = DB::table('hoidong_detai as hd')
            ->join('detai as d', 'hd.nhom', '=', 'd.nhom')
            ->join('sinhvien as s', 'd.mssv', '=', 's.mssv')
            ->leftJoin('giangvien as g', 'd.magv', '=', 'g.magv')
            ->where('hd.mahd', $mahd)
            ->select(
                'd.nhom',
                'd.tendt',
                's.mssv',
                's.hoten as ten_sv',
                's.lop',
                'd.magv',
                'g.hoten as ten_gv'
            )
            ->orderBy('d.nhom')
            ->orderBy('s.mssv')
            ->get();

        // Tạo Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Thiết lập tiêu đề sheet
        $sheet->setTitle('Danh sách đề tài');

        // ===================================
        // PHẦN HEADER - THÔNG TIN HỘI ĐỒNG
        // ===================================
        
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'DANH SÁCH ĐỀ TÀI BẢO VỆ');
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

        // Thông tin hội đồng
        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', 'Mã hội đồng: ' . $hoiDong->mahd);
        
        $sheet->mergeCells('A3:G3');
        $sheet->setCellValue('A3', 'Tên hội đồng: ' . $hoiDong->tenhd);

        $sheet->mergeCells('A4:G4');
        $sheet->setCellValue('A4', 'Thành viên hội đồng: ' . implode(', ', $thanhVien));

        // Style cho thông tin hội đồng
        foreach ([2, 3, 4] as $row) {
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
            ]);
        }

        // ===================================
        // PHẦN TABLE - DANH SÁCH ĐỀ TÀI
        // ===================================

        $headerRow = 6;
        
        // Header table
        $headers = ['STT', 'Mã Nhóm', 'Tên Đề Tài', 'MSSV', 'Họ Tên Sinh Viên', 'Lớp', 'Giảng Viên Hướng Dẫn'];
        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

        foreach ($headers as $index => $header) {
            $cell = $columns[$index] . $headerRow;
            $sheet->setCellValue($cell, $header);
        }

        // Style cho header
        $sheet->getStyle("A{$headerRow}:G{$headerRow}")->applyFromArray([
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
        $previousNhom = null;
        $groupStartRow = $currentRow;

        foreach ($danhSachDeTai as $dt) {
            // Nếu là nhóm mới, merge cells cho STT, Nhóm, Tên đề tài, GV
            if ($dt->nhom !== $previousNhom) {
                // Merge cells cho nhóm trước (nếu có)
                if ($previousNhom !== null && $currentRow > $groupStartRow) {
                    $mergeEndRow = $currentRow - 1;
                    
                    // Merge STT
                    if ($groupStartRow != $mergeEndRow) {
                        $sheet->mergeCells("A{$groupStartRow}:A{$mergeEndRow}");
                    }
                    
                    // Merge Mã Nhóm
                    if ($groupStartRow != $mergeEndRow) {
                        $sheet->mergeCells("B{$groupStartRow}:B{$mergeEndRow}");
                    }
                    
                    // Merge Tên Đề Tài
                    if ($groupStartRow != $mergeEndRow) {
                        $sheet->mergeCells("C{$groupStartRow}:C{$mergeEndRow}");
                    }
                    
                    // Merge GV Hướng Dẫn
                    if ($groupStartRow != $mergeEndRow) {
                        $sheet->mergeCells("G{$groupStartRow}:G{$mergeEndRow}");
                    }
                }

                // Lưu vị trí bắt đầu nhóm mới
                $groupStartRow = $currentRow;
                $previousNhom = $dt->nhom;
            }

            // Điền dữ liệu
            $sheet->setCellValue("A{$currentRow}", $stt);
            $sheet->setCellValue("B{$currentRow}", $dt->nhom);
            $sheet->setCellValue("C{$currentRow}", $dt->tendt);
            $sheet->setCellValue("D{$currentRow}", $dt->mssv);
            $sheet->setCellValue("E{$currentRow}", $dt->ten_sv);
            $sheet->setCellValue("F{$currentRow}", $dt->lop);
            $sheet->setCellValue("G{$currentRow}", $dt->ten_gv . ' (' . $dt->magv . ')');

            $currentRow++;
            
            // Tăng STT chỉ khi chuyển nhóm mới
            if ($currentRow > $groupStartRow + 1 && $dt->nhom !== $previousNhom) {
                $stt++;
            } else if ($currentRow == $groupStartRow + 1) {
                // Nhóm đầu tiên hoặc nhóm mới
                if ($dt->nhom !== $previousNhom) {
                    $stt = $groupStartRow - $headerRow;
                }
            }
        }

        // Merge cells cho nhóm cuối cùng
        if ($previousNhom !== null && $currentRow > $groupStartRow) {
            $mergeEndRow = $currentRow - 1;
            
            if ($groupStartRow != $mergeEndRow) {
                $sheet->mergeCells("A{$groupStartRow}:A{$mergeEndRow}");
                $sheet->mergeCells("B{$groupStartRow}:B{$mergeEndRow}");
                $sheet->mergeCells("C{$groupStartRow}:C{$mergeEndRow}");
                $sheet->mergeCells("G{$groupStartRow}:G{$mergeEndRow}");
            }
        }

        // ===================================
        // STYLE CHO DATA
        // ===================================

        $dataStartRow = $headerRow + 1;
        $dataEndRow = $currentRow - 1;

        // Border cho toàn bộ data
        $sheet->getStyle("A{$dataStartRow}:G{$dataEndRow}")->applyFromArray([
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

        // Center align cho cột STT, Mã Nhóm, MSSV, Lớp
        $sheet->getStyle("A{$dataStartRow}:A{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B{$dataStartRow}:B{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$dataStartRow}:D{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F{$dataStartRow}:F{$dataEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ===================================
        // ĐIỀU CHỈNH ĐỘ RỘNG CỘT
        // ===================================

        $sheet->getColumnDimension('A')->setWidth(6);   // STT
        $sheet->getColumnDimension('B')->setWidth(12);  // Mã Nhóm
        $sheet->getColumnDimension('C')->setWidth(40);  // Tên Đề Tài
        $sheet->getColumnDimension('D')->setWidth(12);  // MSSV
        $sheet->getColumnDimension('E')->setWidth(25);  // Họ Tên SV
        $sheet->getColumnDimension('F')->setWidth(12);  // Lớp
        $sheet->getColumnDimension('G')->setWidth(30);  // GV Hướng Dẫn

        // Auto height cho các row
        foreach (range($dataStartRow, $dataEndRow) as $row) {
            $sheet->getRowDimension($row)->setRowHeight(-1);
        }

        // ===================================
        // TẠO FILE VÀ RETURN
        // ===================================

        $fileName = "HoiDong_{$mahd}_" . date('YmdHis') . '.xlsx';
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
     * Export tất cả hội đồng (nếu cần)
     */
    public function exportAll()
    {
        // Có thể implement sau nếu cần
    }
}