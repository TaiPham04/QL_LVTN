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

        // ✅ Truyền mahd để tạo tên file
        $this->mahd = $hoiDong->mahd;

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
                ->leftJoin('giangvien as gv_hd', 'dt.magv', '=', 'gv_hd.magv')
                ->where('dt.nhom_id', $deTai->nhom_id)
                ->select('sv.mssv', 'sv.hoten', 'sv.lop', 'dt.magv', 'gv_hd.hoten as gv_huongdan')
                ->get();

            foreach ($sinhVienNhom as $sv) {
                // ✅ Lấy điểm hướng dẫn
                $diemHD = DB::table('phieu_cham_diem as pcd')
                    ->join('diem_sinh_vien as dsv', 'pcd.id', '=', 'dsv.phieu_cham_id')
                    ->where('pcd.nhom_id', $deTai->nhom_id)
                    ->where('dsv.mssv', $sv->mssv)
                    ->where('pcd.loai_phieu', 'huong_dan')
                    ->value('dsv.diem_tong');

                // ✅ Lấy điểm phản biện
                $diemPB = DB::table('phieu_cham_diem as pcd')
                    ->join('diem_sinh_vien as dsv', 'pcd.id', '=', 'dsv.phieu_cham_id')
                    ->where('pcd.nhom_id', $deTai->nhom_id)
                    ->where('dsv.mssv', $sv->mssv)
                    ->where('pcd.loai_phieu', 'phan_bien')
                    ->value('dsv.diem_tong');

                // ✅ Lấy điểm từ 4 thành viên hội đồng
                $diemRecord = DB::table('hoidong_chamdiem')
                    ->where('hoidong_id', $hoidong_id)
                    ->where('nhom_id', $deTai->nhom_id)
                    ->where('mssv', $sv->mssv)
                    ->first();

                $diemTV1 = $diemRecord->diem_chu_tich ?? null;      // Chủ tịch
                $diemTV2 = $diemRecord->diem_thu_ky ?? null;        // Thư ký
                $diemTV3 = $diemRecord->diem_thanh_vien_1 ?? null;  // Thành viên 1
                $diemTV4 = $diemRecord->diem_thanh_vien_2 ?? null;  // Thành viên 2

                // ✅ Tính điểm trung bình hội đồng
                $diemTBHoiDong = null;
                if ($diemTV1 !== null && $diemTV2 !== null && $diemTV3 !== null && $diemTV4 !== null) {
                    $diemTBHoiDong = ($diemTV1 + $diemTV2 + $diemTV3 + $diemTV4) / 4;
                }

                // ✅ Tính điểm tổng theo công thức
                // Điểm Tổng = (HD*20 + PB*20 + (TB HĐ)*60) / 100
                $diemTong = null;
                if ($diemHD !== null && $diemPB !== null && $diemTBHoiDong !== null) {
                    $diemTong = round(($diemHD * 20 + $diemPB * 20 + $diemTBHoiDong * 60) / 100, 2);
                }

                $row = [
                    'mssv' => $sv->mssv,
                    'hoten' => $sv->hoten,
                    'lop' => $sv->lop,
                    'tendt' => $deTai->tendt,
                    'diem_hd' => $diemHD ? round($diemHD, 2) : '',
                    'diem_pb' => $diemPB ? round($diemPB, 2) : '',
                    'diem_tv1' => $diemTV1 ? round($diemTV1, 2) : '',      // Chủ tịch
                    'diem_tv2' => $diemTV2 ? round($diemTV2, 2) : '',      // Thư ký
                    'diem_tv3' => $diemTV3 ? round($diemTV3, 2) : '',      // Thành viên 1
                    'diem_tv4' => $diemTV4 ? round($diemTV4, 2) : '',      // Thành viên 2
                    'diem_tong' => $diemTong ?? '',
                ];

                $data[] = $row;
            }
        }

        return $this->generateExcel($data, $hoiDong->tenhd, $hoiDong->mahd);
    }

    /**
     * Tạo và lưu file Excel vào disk
     */
    private function generateExcel($data, $tenHoiDong, $mahd)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Chấm Điểm');

        // Header
        $sheet->setCellValue('A1', 'HỘI ĐỒNG: ' . $tenHoiDong);
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ✅ Chỉ giữ ngày xuất
        $sheet->setCellValue('A2', 'Ngày xuất: ' . now()->format('d/m/Y'));
        $sheet->mergeCells('A2:K2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Tiêu đề cột
        $headers = [
            'MSSV',
            'Tên Sinh Viên',
            'Lớp',
            'Tên Đề Tài',
            'Điểm HD',
            'Điểm PB',
            'Điểm GV1',
            'Điểm GV2',
            'Điểm GV3',
            'Điểm GV4',
            'Điểm Tổng'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $sheet->getStyle($col . '4')->getFont()->setBold(true)->setColor(new Color('FFFFFFFF'));
            $sheet->getStyle($col . '4')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF0D6EFD');
            $sheet->getStyle($col . '4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($col . '4')->getAlignment()->setWrapText(true);
            $col++;
        }

        // Dữ liệu
        $row = 5;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item['mssv']);
            $sheet->setCellValue('B' . $row, $item['hoten']);
            $sheet->setCellValue('C' . $row, $item['lop']);
            $sheet->setCellValue('D' . $row, $item['tendt']);
            $sheet->setCellValue('E' . $row, $item['diem_hd']);
            $sheet->setCellValue('F' . $row, $item['diem_pb']);
            $sheet->setCellValue('G' . $row, $item['diem_tv1']);
            $sheet->setCellValue('H' . $row, $item['diem_tv2']);
            $sheet->setCellValue('I' . $row, $item['diem_tv3']);
            $sheet->setCellValue('J' . $row, $item['diem_tv4']);
            $sheet->setCellValue('K' . $row, $item['diem_tong']);

            // Căn giữa các cột điểm
            for ($col = 'E'; $col <= 'K'; $col++) {
                $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $row++;
        }

        // Độ rộng cột
        $sheet->getColumnDimension('A')->setWidth(15);  // MSSV
        $sheet->getColumnDimension('B')->setWidth(20);  // Tên SV
        $sheet->getColumnDimension('C')->setWidth(10);  // Lớp
        $sheet->getColumnDimension('D')->setWidth(30);  // Tên Đề Tài
        $sheet->getColumnDimension('E')->setWidth(12);  // Điểm HD
        $sheet->getColumnDimension('F')->setWidth(12);  // Điểm PB
        $sheet->getColumnDimension('G')->setWidth(18);  // Điểm TV1
        $sheet->getColumnDimension('H')->setWidth(18);  // Điểm TV2
        $sheet->getColumnDimension('I')->setWidth(18);  // Điểm TV3
        $sheet->getColumnDimension('J')->setWidth(18);  // Điểm TV4
        $sheet->getColumnDimension('K')->setWidth(12);  // Điểm Tổng

        // ✅ Lưu file với tên: HoiDong_{mahd}_{YYYYMMdd}
        // Ví dụ: HoiDong_1_20251225.xlsx
        $filename = 'HoiDong_' . $mahd . '_' . now()->format('Ymd') . '.xlsx';
        $filepath = storage_path('app/temp/' . $filename);
        
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $filepath;
    }
}