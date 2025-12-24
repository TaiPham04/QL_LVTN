<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class TongKetExport
{
    /**
     * Lấy danh sách sinh viên và điểm tổng kết của 1 hội đồng
     */
    public function getDanhSachSinhVienDiem($hoidong_id)
    {
        \Log::info('=== getDanhSachSinhVienDiem ===');
        \Log::info('hoidong_id: ' . $hoidong_id);

        // Lấy hội đồng
        $hoiDong = DB::table('hoidong')
            ->where('id', $hoidong_id)
            ->first();

        if (!$hoiDong) {
            \Log::error('Hội đồng không tồn tại!');
            throw new \Exception('Hội đồng không tồn tại!');
        }

        \Log::info('hoiDong found: ' . json_encode($hoiDong));

        // ✅ FIX: Query trực tiếp từ hoidong_chamdiem
        $sinhVienDiem = DB::table('hoidong_chamdiem as hc')
            ->join('nhom as n', 'hc.nhom_id', '=', 'n.id')
            ->join('sinhvien as sv', 'hc.mssv', '=', 'sv.mssv')
            ->where('hc.hoidong_id', $hoidong_id)
            ->select(
                'hc.id',
                'hc.hoidong_id',
                'hc.mssv',
                'hc.nhom_id',
                'hc.diem_tong',
                'sv.hoten',
                'sv.lop',
                'n.tennhom as nhom',
                'n.tendt as tendt'
            )
            ->distinct()
            ->orderBy('hc.nhom_id')
            ->orderBy('hc.mssv')
            ->get();

        \Log::info('sinhVienDiem count: ' . $sinhVienDiem->count());

        $result = [];

        foreach ($sinhVienDiem as $item) {
            \Log::info('Processing: mssv=' . $item->mssv . ', diem_tong=' . $item->diem_tong);

            $diemHoiDong = $item->diem_tong !== null ? round($item->diem_tong, 2) : '';
            $diemHD = $diemHoiDong;
            $diemPB = $diemHoiDong;
            $diemTongKet = $diemHoiDong;

            $result[] = [
                'mssv' => $item->mssv,
                'hoten' => $item->hoten,
                'nhom' => $item->nhom,
                'lop' => $item->lop,
                'gvhd' => 'N/A',
                'tendt' => $item->tendt,
                'diem_hd' => $diemHD,
                'diem_pb' => $diemPB,
                'diem_hoidong' => $diemHoiDong,
                'diem_tongket' => $diemTongKet
            ];
        }

        \Log::info('result final count: ' . count($result));
        return collect($result);
    }

    /**
     * Xuất file Excel tổng kết (cho hội đồng)
     */
    public function exportExcel($hoidong_id)
    {
        \Log::info('=== exportExcel ===');
        \Log::info('hoidong_id: ' . $hoidong_id);

        $hoiDong = DB::table('hoidong')
            ->where('id', $hoidong_id)
            ->first();

        if (!$hoiDong) {
            \Log::error('Hội đồng không tồn tại!');
            throw new \Exception('Hội đồng không tồn tại!');
        }

        \Log::info('hoiDong: ' . json_encode($hoiDong));

        $danhSachSinhVien = $this->getDanhSachSinhVienDiem($hoidong_id);

        if ($danhSachSinhVien->isEmpty()) {
            \Log::error('Không có sinh viên trong hội đồng này!');
            throw new \Exception('Không có sinh viên trong hội đồng này!');
        }

        return $this->generateExcel($danhSachSinhVien, $hoiDong->mahd, $hoiDong->tenhd);
    }

    /**
     * ✅ NEW: Xuất Excel cho GV Hướng Dẫn
     */
    public function exportExcelGVHuongDan($magv)
    {
        \Log::info('=== exportExcelGVHuongDan ===');
        \Log::info('magv: ' . $magv);

        // Lấy danh sách đề tài mà GV hướng dẫn
        $deTaiList = DB::table('detai as dt')
            ->join('nhom as n', 'dt.nhom_id', '=', 'n.id')
            ->join('sinhvien as sv', 'dt.mssv', '=', 'sv.mssv')
            ->where('dt.magv', $magv)
            ->select(
                'n.id as nhom_id',
                'dt.mssv',
                'sv.hoten',
                'sv.lop',
                'n.tennhom as nhom',
                'n.tendt as tendt'
            )
            ->distinct()
            ->get();

        \Log::info('deTaiList count: ' . $deTaiList->count());

        if ($deTaiList->isEmpty()) {
            \Log::error('Không có đề tài nào!');
            throw new \Exception('Không có đề tài nào!');
        }

        $danhSachTongKet = [];

        foreach ($deTaiList as $dt) {
            // ✅ Lấy điểm hướng dẫn
            $diemHD = DB::table('phieu_cham_diem as pcd')
                ->join('diem_sinh_vien as dsv', 'pcd.id', '=', 'dsv.phieu_cham_id')
                ->where('pcd.nhom_id', $dt->nhom_id)
                ->where('dsv.mssv', $dt->mssv)
                ->where('pcd.loai_phieu', 'huong_dan')
                ->value('dsv.diem_tong');

            // ✅ Lấy điểm phản biện
            $diemPB = DB::table('phieu_cham_diem as pcd')
                ->join('diem_sinh_vien as dsv', 'pcd.id', '=', 'dsv.phieu_cham_id')
                ->where('pcd.nhom_id', $dt->nhom_id)
                ->where('dsv.mssv', $dt->mssv)
                ->where('pcd.loai_phieu', 'phan_bien')
                ->value('dsv.diem_tong');

            // ✅ Lấy điểm hội đồng
            $diemHoiDong = DB::table('hoidong_chamdiem as hc')
                ->where('hc.nhom_id', $dt->nhom_id)
                ->where('hc.mssv', $dt->mssv)
                ->value('hc.diem_tong');

            // Format các điểm
            $diemHD_formatted = $diemHD !== null ? round($diemHD, 2) : '';
            $diemPB_formatted = $diemPB !== null ? round($diemPB, 2) : '';
            $diemHoiDong_formatted = $diemHoiDong !== null ? round($diemHoiDong, 2) : '';

            // Tính điểm tổng kết
            $diemTongKet = '';
            if ($diemHD !== null && $diemPB !== null && $diemHoiDong !== null) {
                $diemTongKet = round(($diemHD + $diemPB + $diemHoiDong) / 3, 2);
            }

            $danhSachTongKet[] = [
                'mssv' => $dt->mssv,
                'hoten' => $dt->hoten,
                'nhom' => $dt->nhom,
                'lop' => $dt->lop,
                'tendt' => $dt->tendt,
                'diem_hd' => $diemHD_formatted,
                'diem_pb' => $diemPB_formatted,
                'diem_hoidong' => $diemHoiDong_formatted,
                'diem_tongket' => $diemTongKet
            ];
        }

        \Log::info('danhSachTongKet final count: ' . count($danhSachTongKet));

        return $this->generateExcelGVHuongDan(collect($danhSachTongKet));
    }

    /**
     * Tạo và trả về file Excel (cho hội đồng)
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
            $sheet->setCellValue('G' . $row, $item['diem_hd'] !== '' ? $item['diem_hd'] : '');
            $sheet->setCellValue('H' . $row, $item['diem_pb'] !== '' ? $item['diem_pb'] : '');
            $sheet->setCellValue('I' . $row, $item['diem_hoidong'] !== '' ? $item['diem_hoidong'] : '');
            $sheet->setCellValue('J' . $row, $item['diem_tongket'] !== '' ? $item['diem_tongket'] : '');

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

        // Lưu file vào disk
        $filename = 'DiemTongKet_' . now()->format('YmdHis') . '.xlsx';
        $filepath = storage_path('app/temp/' . $filename);
        
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        \Log::info('Excel file saved: ' . $filepath);

        return $filepath;
    }

    /**
     * ✅ NEW: Tạo file Excel cho GV Hướng Dẫn
     */
    private function generateExcelGVHuongDan($data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tổng Kết');

        // Header
        $sheet->setCellValue('A1', 'BẢNG ĐIỂM TỔNG KẾT - GIẢNG VIÊN HƯỚNG DẪN');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Ngày xuất: ' . now()->format('d/m/Y'));
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Tiêu đề cột
        $headers = [
            'MSSV',
            'Tên Sinh Viên',
            'Nhóm',
            'Lớp',
            'Tên Đề Tài',
            'Điểm HD',
            'Điểm PB',
            'Điểm Hội Đồng',
            'Điểm Tổng Kết'
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
            $sheet->setCellValue('A' . $row, $item['mssv']);
            $sheet->setCellValue('B' . $row, $item['hoten']);
            $sheet->setCellValue('C' . $row, $item['nhom']);
            $sheet->setCellValue('D' . $row, $item['lop']);
            $sheet->setCellValue('E' . $row, $item['tendt']);
            $sheet->setCellValue('F' . $row, $item['diem_hd'] !== '' ? $item['diem_hd'] : '');
            $sheet->setCellValue('G' . $row, $item['diem_pb'] !== '' ? $item['diem_pb'] : '');
            $sheet->setCellValue('H' . $row, $item['diem_hoidong'] !== '' ? $item['diem_hoidong'] : '');
            $sheet->setCellValue('I' . $row, $item['diem_tongket'] !== '' ? $item['diem_tongket'] : '');

            // Căn giữa các cột điểm
            for ($col = 'F'; $col <= 'I'; $col++) {
                $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $row++;
        }

        // Độ rộng cột
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(10);
        $sheet->getColumnDimension('E')->setWidth(35);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(15);

        // Lưu file
        $filename = 'DiemTongKet_' . now()->format('YmdHis') . '.xlsx';
        $filepath = storage_path('app/temp/' . $filename);
        
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        \Log::info('Excel file saved: ' . $filepath);

        return $filepath;
    }
}