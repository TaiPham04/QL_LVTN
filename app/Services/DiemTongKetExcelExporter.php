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
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // ✅ Thông tin hội đồng
        $row = 2;
        if ($hoiDong) {
            $sheet->setCellValue('A' . $row, 'Hội đồng: ' . $hoiDong->tenhd);
            $sheet->mergeCells('A' . $row . ':J' . $row);
            $row++;
            
            if ($hoiDong->ngay_hoidong) {
                $sheet->setCellValue('A' . $row, 'Ngày: ' . date('d/m/Y', strtotime($hoiDong->ngay_hoidong)));
                $sheet->mergeCells('A' . $row . ':J' . $row);
                $row++;
            }
        }
        
        // ✅ FIX: Bỏ time, chỉ giữ ngày và căn giữa
        $sheet->setCellValue('A' . $row, 'Ngày xuất: ' . date('d/m/Y'));
        $sheet->mergeCells('A' . $row . ':J' . $row);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // ✅ Headers bảng
        $headerRow = $row + 1;
        $headers = [
            'Nhóm',
            'Tên Hội Đồng',  // ✅ Thay từ "Mã ĐT"
            'Tên Đề Tài',
            'MSSV',
            'Tên SV',
            'Lớp',
            'Điểm HD',
            'Điểm PB',
            'Điểm HĐ',
            'Tổng'
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
        
        // ✅ Dữ liệu (bỏ dòng trắng, data bắt đầu ngay sau header)
        $dataRow = $headerRow + 1;
        foreach ($diemData as $item) {
            // ✅ Convert object to array
            if (is_object($item)) {
                $item = json_decode(json_encode($item), true);
            }
            
            \Log::info('Export item: ' . json_encode($item));
            
            $sheet->setCellValue('A' . $dataRow, $item['tennhom'] ?? '');
            $sheet->setCellValue('B' . $dataRow, $item['tenhd'] ?? '');
            $sheet->setCellValue('C' . $dataRow, $item['tendt'] ?? '');
            $sheet->setCellValue('D' . $dataRow, $item['mssv'] ?? '');
            $sheet->setCellValue('E' . $dataRow, $item['ten_sinh_vien'] ?? '');
            $sheet->setCellValue('F' . $dataRow, $item['lop'] ?? '');
            
            \Log::info('Row ' . $dataRow . ' - lop: ' . ($item['lop'] ?? 'NULL') . ', diem_hd: ' . ($item['diem_hd'] ?? 'NULL'));
            
            // ✅ Format điểm - chỉ set nếu có giá trị
            if (isset($item['diem_hd']) && $item['diem_hd'] != '') {
                $sheet->setCellValue('G' . $dataRow, $item['diem_hd']);
                $sheet->getStyle('G' . $dataRow)->getNumberFormat()->setFormatCode('0.00');
            }
            
            if (isset($item['diem_pb']) && $item['diem_pb'] != '') {
                $sheet->setCellValue('H' . $dataRow, $item['diem_pb']);
                $sheet->getStyle('H' . $dataRow)->getNumberFormat()->setFormatCode('0.00');
            }
            
            if (isset($item['diem_gv']) && $item['diem_gv'] != '') {
                $sheet->setCellValue('I' . $dataRow, $item['diem_gv']);
                $sheet->getStyle('I' . $dataRow)->getNumberFormat()->setFormatCode('0.00');
            }
            
            if (isset($item['diem_tong']) && $item['diem_tong'] != '') {
                $sheet->setCellValue('J' . $dataRow, $item['diem_tong']);
                $sheet->getStyle('J' . $dataRow)->getNumberFormat()->setFormatCode('0.00');
            }
            
            // ✅ Căn giữa cột điểm
            for ($col = 'G'; $col <= 'J'; $col++) {
                $sheet->getStyle($col . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            
            // ✅ Border
            $sheet->getStyle('A' . $dataRow . ':J' . $dataRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            
            $dataRow++;
        }
        
        // ✅ Độ rộng cột
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(28);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(22);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(12);
        $sheet->getColumnDimension('J')->setWidth(12);
        
        // ✅ Freeze pane (đóng cột tiêu đề)
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
?>