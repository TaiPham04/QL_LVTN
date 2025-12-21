<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TongKetExport
{
    /**
     * Lấy danh sách sinh viên và điểm tổng kết của 1 hội đồng
     */
    public function getDanhSachSinhVienDiem($mahd)
    {
        // Lấy danh sách sinh viên trong các đề tài của hội đồng
        $sinhVienList = DB::table('hoidong_detai as hdt')
            ->join('detai as dt', 'hdt.nhom', '=', 'dt.nhom')
            ->join('sinhvien as sv', 'dt.mssv', '=', 'sv.mssv')
            ->where('hdt.mahd', $mahd)
            ->select('dt.nhom', 'sv.mssv', 'sv.hoten', 'sv.lop', 'dt.tendt')
            ->distinct()
            ->get();

        $result = [];

        foreach ($sinhVienList as $sv) {
            // Lấy giáo viên hướng dẫn
            $gvHD = DB::table('phancong')
                ->where('mssv', $sv->mssv)
                ->value('magv');

            $gvHDName = '';
            if ($gvHD) {
                $gvHDName = DB::table('giangvien')
                    ->where('magv', $gvHD)
                    ->value('hoten');
            }

            // Lấy điểm hướng dẫn
            $diemHD = DB::table('phieu_cham_diem as pcd')
                ->join('diem_sinh_vien as dsv', 'pcd.id', '=', 'dsv.phieu_cham_id')
                ->where('pcd.nhom', $sv->nhom)
                ->where('dsv.mssv', $sv->mssv)
                ->where('pcd.loai_phieu', 'huong_dan')
                ->value('dsv.diem_tong') ?? '';

            // Lấy điểm phản biện
            $diemPB = DB::table('phieu_cham_diem as pcd')
                ->join('diem_sinh_vien as dsv', 'pcd.id', '=', 'dsv.phieu_cham_id')
                ->where('pcd.nhom', $sv->nhom)
                ->where('dsv.mssv', $sv->mssv)
                ->where('pcd.loai_phieu', 'phan_bien')
                ->value('dsv.diem_tong') ?? '';

            // Lấy điểm hội đồng
            $diemHD_HoiDong = DB::table('hoidong_chamdiem')
                ->where('mahd', $mahd)
                ->where('nhom', $sv->nhom)
                ->where('mssv', $sv->mssv)
                ->avg('diem') ?? '';

            // Tính điểm tổng kết
            $diemTongKet = '';
            if ($diemHD !== '' && $diemPB !== '' && $diemHD_HoiDong !== '') {
                $diemTongKet = round(($diemHD + $diemPB + $diemHD_HoiDong) / 3, 2);
            }

            $result[] = [
                'mssv' => $sv->mssv,
                'hoten' => $sv->hoten,
                'nhom' => $sv->nhom,
                'lop' => $sv->lop,
                'gvhd' => $gvHDName,
                'tendt' => $sv->tendt,
                'diem_hd' => $diemHD,
                'diem_pb' => $diemPB,
                'diem_hoidong' => $diemHD_HoiDong,
                'diem_tongket' => $diemTongKet
            ];
        }

        return collect($result);
    }

    /**
     * Xuất file Excel tổng kết
     */
    public function exportExcel($mahd)
    {
        // Lấy thông tin hội đồng
        $hoiDong = DB::table('hoidong')
            ->where('mahd', $mahd)
            ->first();

        if (!$hoiDong) {
            throw new \Exception('Hội đồng không tồn tại!');
        }

        // Lấy danh sách sinh viên và điểm
        $danhSachSinhVien = $this->getDanhSachSinhVienDiem($mahd);

        if ($danhSachSinhVien->isEmpty()) {
            throw new \Exception('Không có sinh viên trong hội đồng này!');
        }

        return $this->generateExcel($danhSachSinhVien, $hoiDong->mahd, $hoiDong->tenhd);
    }

    /**
     * Tạo và trả về file Excel
     */
    private function generateExcel($data, $mahd, $tenHoiDong)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tổng Kết');

        // Header
        $sheet->setCellValue('A1', 'BẢNG ĐIỂM TỔNG KẾT');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Hội Đồng: ' . $tenHoiDong);
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', 'Ngày xuất: ' . now()->format('d/m/Y'));
        $sheet->mergeCells('A3:J3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Tiêu đề cột
        $headers = [
            'MSSV',
            'Tên Sinh Viên',
            'Nhóm',
            'Lớp',
            'GVHD',
            'Tên Đề Tài',
            'Điểm HD',
            'Điểm PB',
            'Điểm Hội Đồng',
            'Điểm Tổng Kết'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $sheet->getStyle($col . '5')->getFont()->setBold(true)->setColor(new Color('FFFFFFFF'));
            $sheet->getStyle($col . '5')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF0D6EFD');
            $sheet->getStyle($col . '5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col++;
        }

        // Dữ liệu
        $row = 6;
        foreach ($data as $item) {
            $sheet->setCellValue('A' . $row, $item['mssv']);
            $sheet->setCellValue('B' . $row, $item['hoten']);
            $sheet->setCellValue('C' . $row, $item['nhom']);
            $sheet->setCellValue('D' . $row, $item['lop']);
            $sheet->setCellValue('E' . $row, $item['gvhd']);
            $sheet->setCellValue('F' . $row, $item['tendt']);
            $sheet->setCellValue('G' . $row, $item['diem_hd']);
            $sheet->setCellValue('H' . $row, $item['diem_pb']);
            $sheet->setCellValue('I' . $row, $item['diem_hoidong']);
            $sheet->setCellValue('J' . $row, $item['diem_tongket']);

            // Căn giữa các cột điểm
            for ($col = 'G'; $col <= 'J'; $col++) {
                $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $row++;
        }

        // Độ rộng cột
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(35);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(15);
        $sheet->getColumnDimension('J')->setWidth(15);

        // Xuất file
        $filename = 'DiemTongKet_' . $mahd . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(
            function() use ($writer) {
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]
        );
    }
}