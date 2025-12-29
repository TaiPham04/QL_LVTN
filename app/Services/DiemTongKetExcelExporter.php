<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

class DiemTongKetExcelExporter
{
    public function export($diemData, $hoiDong)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tổng Kết');
        
        // ✅ Header chính
        $sheet->setCellValue('A1', 'BẢNG ĐIỂM TỔNG KẾT');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // ✅ Thông tin hội đồng
        $row = 2;
        if ($hoiDong) {
            $sheet->setCellValue('A' . $row, 'Hội đồng: ' . $hoiDong->tenhd);
            $sheet->mergeCells('A' . $row . ':H' . $row);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
            
            if ($hoiDong->ngay_hoidong) {
                $sheet->setCellValue('A' . $row, 'Ngày: ' . date('d/m/Y', strtotime($hoiDong->ngay_hoidong)));
                $sheet->mergeCells('A' . $row . ':H' . $row);
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $row++;
            }
        }
        
        // ✅ Ngày xuất
        $sheet->setCellValue('A' . $row, 'Ngày xuất: ' . date('d/m/Y'));
        $sheet->mergeCells('A' . $row . ':H' . $row);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // ✅ Headers bảng
        $headerRow = $row + 1;
        $headers = [
            'TT BC',          // A
            'MSSV',           // B
            'Tên SV',         // C
            'Tên Đề Tài',     // D
            'GVHD',           // E
            'Điểm HD',        // F
            'GVPB',           // G
            'Điểm PB',        // H
        ];
        
        foreach ($headers as $col => $header) {
            $cell = chr(65 + $col) . $headerRow;
            $sheet->setCellValue($cell, $header);
            
            $style = $sheet->getStyle($cell);
            $style->getFont()->setBold(true)->setColor(new Color('FFFFFFFF'));
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0D6EFD');
            $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }
        
        // ✅ Dữ liệu
        $dataRow = $headerRow + 1;
        
        // ✅ Convert sang array đúng cách
        if ($diemData instanceof \Illuminate\Support\Collection) {
            $diemData = $diemData->toArray();
        }
        
        \Log::info('Exporting ' . count($diemData) . ' rows');
        
        // ✅ Xuất dữ liệu
        foreach ($diemData as $idx => $item) {
            // Convert object to array
            if (is_object($item)) {
                $item = (array)$item;
            }
            
            // ✅ Kiểm tra dữ liệu hợp lệ
            if (!is_array($item)) {
                \Log::warning('Invalid item at row ' . $idx . ': ' . gettype($item));
                continue;
            }
            
            // ✅ TT BC (A)
            $ttbc = $item['ttbc'] ?? $item['thitubao'] ?? '';
            $sheet->setCellValue('A' . $dataRow, $ttbc);
            $sheet->getStyle('A' . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // ✅ MSSV (B)
            $mssv = $item['mssv'] ?? '';
            $sheet->setCellValue('B' . $dataRow, $mssv);
            $sheet->getStyle('B' . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // ✅ Tên SV (C)
            $tenSV = $item['ten_sinh_vien'] ?? $item['hoten'] ?? '';
            $sheet->setCellValue('C' . $dataRow, $tenSV);
            
            // ✅ Tên Đề Tài (D)
            $tenDT = $item['tendt'] ?? '';
            $sheet->setCellValue('D' . $dataRow, $tenDT);
            
            // ✅ GVHD (E)
            $gvHD = $item['ten_gvhd'] ?? '-';
            $sheet->setCellValue('E' . $dataRow, $gvHD);
            
            // ✅ Điểm HD (F)
            $diemHD = $item['diem_hd'] ?? 0;
            if ($diemHD && floatval($diemHD) > 0) {
                $sheet->setCellValue('F' . $dataRow, floatval($diemHD));
                $sheet->getStyle('F' . $dataRow)->getNumberFormat()->setFormatCode('0.00');
            } else {
                $sheet->setCellValue('F' . $dataRow, '-');
            }
            $sheet->getStyle('F' . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // ✅ GVPB (G)
            $gvPB = $item['ten_gvpb'] ?? '-';
            $sheet->setCellValue('G' . $dataRow, $gvPB);
            
            // ✅ Điểm PB (H)
            $diemPB = $item['diem_pb'] ?? 0;
            if ($diemPB && floatval($diemPB) > 0) {
                $sheet->setCellValue('H' . $dataRow, floatval($diemPB));
                $sheet->getStyle('H' . $dataRow)->getNumberFormat()->setFormatCode('0.00');
            } else {
                $sheet->setCellValue('H' . $dataRow, '-');
            }
            $sheet->getStyle('H' . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // ✅ Border
            $sheet->getStyle('A' . $dataRow . ':H' . $dataRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            
            $dataRow++;
        }
        
        // ✅ Độ rộng cột
        $sheet->getColumnDimension('A')->setWidth(10);   // TT BC
        $sheet->getColumnDimension('B')->setWidth(12);   // MSSV
        $sheet->getColumnDimension('C')->setWidth(20);   // Tên SV
        $sheet->getColumnDimension('D')->setWidth(25);   // Tên Đề Tài
        $sheet->getColumnDimension('E')->setWidth(18);   // GVHD
        $sheet->getColumnDimension('F')->setWidth(12);   // Điểm HD
        $sheet->getColumnDimension('G')->setWidth(18);   // GVPB
        $sheet->getColumnDimension('H')->setWidth(12);   // Điểm PB
        
        // ✅ Freeze pane
        $sheet->freezePane('A' . ($headerRow + 1));
        
        // ✅ Export
        $writer = new Xlsx($spreadsheet);
        $filename = 'DiemTongKet_' . now()->format('YmdHis') . '.xlsx';
        
        $filepath = storage_path('app/temp/' . $filename);
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        
        $writer->save($filepath);
        
        \Log::info('Excel exported: ' . $filepath);
        
        return response()->download($filepath, $filename)->deleteFileAfterSend(true);
    }
}