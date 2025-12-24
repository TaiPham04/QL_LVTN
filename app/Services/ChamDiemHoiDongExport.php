<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ChamDiemHoiDongExport
{
    /**
     * Xuất file Excel - Lưu vào disk
     */
    public function exportExcel($hoidong_id)
    {
        // Lấy thông tin hội đồng
        $hoiDong = DB::table('hoidong')
            ->where('id', $hoidong_id)
            ->first();

        if (!$hoiDong) {
            throw new \Exception('Hội đồng không tồn tại!');
        }

        // Lấy danh sách thành viên hội đồng
        $thanhVien = DB::table('thanhvienhoidong as tv')
            ->join('giangvien as gv', 'tv.magv', '=', 'gv.magv')
            ->where('tv.hoidong_id', $hoidong_id)
            ->select('tv.magv', 'gv.hoten', 'tv.vai_tro')
            ->orderBy('tv.vai_tro')
            ->get();

        // Lấy danh sách đề tài
        $deTaiList = DB::table('hoidong_detai as hdt')
            ->join('nhom as n', 'hdt.nhom_id', '=', 'n.id')
            ->where('hdt.hoidong_id', $hoidong_id)
            ->select('n.id as nhom_id', 'n.tennhom as nhom', 'n.tendt')
            ->distinct()
            ->get();

        // Chuẩn bị dữ liệu
        $data = [];
        foreach ($deTaiList as $deTai) {
            $sinhVienNhom = DB::table('detai as dt')
                ->join('sinhvien as sv', 'dt.mssv', '=', 'sv.mssv')
                ->where('dt.nhom_id', $deTai->nhom_id)
                ->select('sv.mssv', 'sv.hoten', 'sv.lop')
                ->get();

            foreach ($sinhVienNhom as $sv) {
                // Lấy điểm từ cấu trúc mới (1 record/sinh viên với 4 cột điểm)
                $diemRecord = DB::table('hoidong_chamdiem')
                    ->where('hoidong_id', $hoidong_id)
                    ->where('nhom_id', $deTai->nhom_id)
                    ->where('mssv', $sv->mssv)
                    ->first();

                $row = [
                    'nhom' => $deTai->nhom,
                    'mssv' => $sv->mssv,
                    'hoten' => $sv->hoten,
                    'lop' => $sv->lop,
                    'tendt' => $deTai->tendt,
                    'diem_chu_tich' => $diemRecord->diem_chu_tich ?? '',
                    'diem_thu_ky' => $diemRecord->diem_thu_ky ?? '',
                    'diem_thanh_vien_1' => $diemRecord->diem_thanh_vien_1 ?? '',
                    'diem_thanh_vien_2' => $diemRecord->diem_thanh_vien_2 ?? '',
                    'diem_tong' => $diemRecord->diem_tong ?? '',
                ];

                $data[] = $row;
            }
        }

        return $this->generateExcel($data, $hoiDong->tenhd, $thanhVien);
    }

    /**
     * Tạo và lưu file Excel vào disk
     */
    private function generateExcel($data, $tenHoiDong, $thanhVien)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Chấm Điểm');

        // Header
        $sheet->setCellValue('A1', 'HỘI ĐỒNG: ' . $tenHoiDong);
        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Ngày xuất: ' . now()->format('d/m/Y H:i'));
        $sheet->mergeCells('A2:M2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Tiêu đề cột
        $headers = [
            'Nhóm', 'MSSV', 'Tên SV', 'Lớp', 'Tên Đề Tài',
            'Điểm Chủ tịch', 'Điểm Thư ký', 'Điểm Thành viên 1', 'Điểm Thành viên 2',
            'Điểm TB'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $sheet->getStyle($col . '4')->getFont()->setBold(true)->setColor(new Color('FFFFFFFF'));
            $sheet->getStyle($col . '4')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF0D6EFD');
            $sheet->getStyle($col . '4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col++;
        }

        // Dữ liệu
        $row = 5;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item['nhom']);
            $sheet->setCellValue('B' . $row, $item['mssv']);
            $sheet->setCellValue('C' . $row, $item['hoten']);
            $sheet->setCellValue('D' . $row, $item['lop']);
            $sheet->setCellValue('E' . $row, $item['tendt']);
            $sheet->setCellValue('F' . $row, $item['diem_chu_tich']);
            $sheet->setCellValue('G' . $row, $item['diem_thu_ky']);
            $sheet->setCellValue('H' . $row, $item['diem_thanh_vien_1']);
            $sheet->setCellValue('I' . $row, $item['diem_thanh_vien_2']);
            $sheet->setCellValue('J' . $row, $item['diem_tong']);

            // Căn giữa các cột điểm
            for ($col = 'F'; $col <= 'J'; $col++) {
                $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $row++;
        }

        // Độ rộng cột
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(30);
        
        for ($col = 'F'; $col <= 'J'; $col++) {
            $sheet->getColumnDimension($col)->setWidth(15);
        }

        // Lưu file vào disk
        $filename = 'HoiDong_' . now()->format('YmdHis') . '.xlsx';
        $filepath = storage_path('app/temp/' . $filename);
        
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $filepath;
    }
}