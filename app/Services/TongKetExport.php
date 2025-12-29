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
     * ✅ Xuất Excel tổng kết cho hội đồng (với đầy đủ các cột)
     */
    public function exportExcelHoiDong($hoidong_id)
    {
        \Log::info('=== exportExcelHoiDong ===');
        \Log::info('hoidong_id: ' . $hoidong_id);

        // Lấy hội đồng
        $hoiDong = DB::table('hoidong')
            ->where('id', $hoidong_id)
            ->first();

        if (!$hoiDong) {
            \Log::error('Hội đồng không tồn tại!');
            throw new \Exception('Hội đồng không tồn tại!');
        }

        \Log::info('hoiDong: ' . json_encode($hoiDong));

        // ✅ Lấy tất cả đề tài của hội đồng
        $deTaiList = DB::table('hoidong_detai as hdt')
            ->join('nhom as n', 'hdt.nhom_id', '=', 'n.id')
            ->leftJoin('detai as dt', function($join) {
                $join->on('hdt.nhom_id', '=', 'dt.nhom_id')
                     ->whereNotNull('dt.mssv');
            })
            ->leftJoin('sinhvien as sv', 'dt.mssv', '=', 'sv.mssv')
            ->where('hdt.hoidong_id', $hoidong_id)
            ->select(
                'hdt.hoidong_id',
                'hdt.thu_tu',
                'n.id as nhom_id',
                'dt.mssv',
                'sv.hoten',
                'sv.lop',
                'n.tennhom as nhom',
                'n.tendt as tendt'
            )
            ->distinct()
            ->orderBy('hdt.thu_tu')
            ->get();

        \Log::info('deTaiList count: ' . $deTaiList->count());

        // ✅ Map MSSV với TTBC
        $ttbcMap = [];
        foreach ($deTaiList as $dt) {
            if ($dt->mssv) {
                $ttbcMap[$dt->mssv] = $dt->thu_tu;
            }
        }

        $danhSachTongKet = [];

        foreach ($deTaiList as $dt) {
            if (!$dt->mssv) continue;

            // ✅ LẤY TÊN GVHD từ bảng detai
            $gvhdInfo = DB::table('detai as d')
                ->join('giangvien as gv', 'd.magv', '=', 'gv.magv')
                ->where('d.nhom_id', $dt->nhom_id)
                ->where('d.mssv', $dt->mssv)
                ->select('gv.hoten')
                ->first();

            $tenGVHD = $gvhdInfo?->hoten ?? '-';

            // ✅ LẤY ĐIỂM GVHD
            $diemHDData = DB::table('phieu_cham_diem as pcd')
                ->join('diem_sinh_vien as dsv', 'pcd.id', '=', 'dsv.phieu_cham_id')
                ->where('pcd.nhom_id', $dt->nhom_id)
                ->where('dsv.mssv', $dt->mssv)
                ->where('pcd.loai_phieu', 'huong_dan')
                ->select('dsv.diem_tong')
                ->first();

            $diemHD = $diemHDData?->diem_tong;

            // ✅ LẤY TÊN GVPB từ bảng phancong_phanbien
            $gvpbInfo = DB::table('phancong_phanbien as ppb')
                ->join('giangvien as gv', 'ppb.magv_phanbien', '=', 'gv.magv')
                ->where('ppb.nhom_id', $dt->nhom_id)
                ->select('gv.hoten')
                ->first();

            $tenGVPB = $gvpbInfo?->hoten ?? '-';

            // ✅ LẤY ĐIỂM GVPB
            $diemPBData = DB::table('phieu_cham_diem as pcd')
                ->join('diem_sinh_vien as dsv', 'pcd.id', '=', 'dsv.phieu_cham_id')
                ->where('pcd.nhom_id', $dt->nhom_id)
                ->where('dsv.mssv', $dt->mssv)
                ->where('pcd.loai_phieu', 'phan_bien')
                ->select('dsv.diem_tong')
                ->first();

            $diemPB = $diemPBData?->diem_tong;

            // ✅ Lấy điểm hội đồng (4 thành viên)
            $hoiDongScores = DB::table('hoidong_chamdiem as hc')
                ->where('hc.hoidong_id', $hoidong_id)
                ->where('hc.nhom_id', $dt->nhom_id)
                ->where('hc.mssv', $dt->mssv)
                ->select('hc.diem_chu_tich', 'hc.diem_thu_ky', 'hc.diem_thanh_vien_1', 'hc.diem_thanh_vien_2', 'hc.diem_tong')
                ->first();

            // Format các điểm
            $diemHD_formatted = $diemHD !== null ? round($diemHD, 2) : '';
            $diemPB_formatted = $diemPB !== null ? round($diemPB, 2) : '';
            
            $diemGV1 = $hoiDongScores?->diem_chu_tich !== null ? round($hoiDongScores->diem_chu_tich, 2) : '';
            $diemGV2 = $hoiDongScores?->diem_thu_ky !== null ? round($hoiDongScores->diem_thu_ky, 2) : '';
            $diemGV3 = $hoiDongScores?->diem_thanh_vien_1 !== null ? round($hoiDongScores->diem_thanh_vien_1, 2) : '';
            $diemGV4 = $hoiDongScores?->diem_thanh_vien_2 !== null ? round($hoiDongScores->diem_thanh_vien_2, 2) : '';

            // Tính điểm tổng kết
            $diemTongKet = '';
            if ($diemHD !== null && $diemPB !== null && $diemGV1 !== '' && $diemGV2 !== '' && $diemGV3 !== '' && $diemGV4 !== '') {
                $tbHoiDong = ($diemGV1 + $diemGV2 + $diemGV3 + $diemGV4) / 4;
                $diemTongKet = round(($diemHD * 20 + $diemPB * 20 + $tbHoiDong * 60) / 100, 2);
            }

            $danhSachTongKet[] = [
                'ttbc' => $ttbcMap[$dt->mssv] ?? '',
                'mssv' => $dt->mssv,
                'hoten' => $dt->hoten,
                'lop' => $dt->lop,
                'tendt' => $dt->tendt,
                'ten_gvhd' => $tenGVHD,
                'diem_hd' => $diemHD_formatted,
                'ten_gvpb' => $tenGVPB,
                'diem_pb' => $diemPB_formatted,
                'diem_gv1' => $diemGV1,
                'diem_gv2' => $diemGV2,
                'diem_gv3' => $diemGV3,
                'diem_gv4' => $diemGV4,
                'diem_tongket' => $diemTongKet
            ];
        }

        if (empty($danhSachTongKet)) {
            \Log::error('Không có sinh viên trong hội đồng này!');
            throw new \Exception('Không có sinh viên trong hội đồng này!');
        }

        \Log::info('danhSachTongKet count: ' . count($danhSachTongKet));

        // ✅ CHUYỂN THÀNH ARRAY THAY VÌ COLLECTION
        return $this->generateExcelHoiDong($danhSachTongKet, $hoiDong->mahd, $hoiDong->tenhd);
    }

    /**
     * ✅ Tạo file Excel với đầy đủ các cột
     */
    private function generateExcelHoiDong($data, $mahd, $tenHoiDong)
    {
        // ✅ ĐẢM BẢO DATA LÀ ARRAY
        if (!is_array($data)) {
            $data = $data->toArray();
        }

        \Log::info('generateExcelHoiDong - data count: ' . count($data));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tổng Kết');

        // Header
        $sheet->setCellValue('A1', 'BẢNG ĐIỂM TỔNG KẾT');
        $sheet->mergeCells('A1:N1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Hội Đồng: ' . $tenHoiDong . ' (' . $mahd . ')');
        $sheet->mergeCells('A2:N2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', 'Ngày xuất: ' . now()->format('d/m/Y'));
        $sheet->mergeCells('A3:N3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Tiêu đề cột
        $headers = [
            'TTBC',
            'MSSV',
            'Tên SV',
            'Lớp',
            'Tên Đề Tài',
            'Tên GVHD',
            'Điểm HD',
            'Tên GVPB',
            'Điểm PB',
            'GV1',
            'GV2',
            'GV3',
            'GV4',
            'Điểm TK'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $sheet->getStyle($col . '5')->getFont()->setBold(true)->setColor(new Color('FFFFFFFF'));
            $sheet->getStyle($col . '5')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF0D6EFD');
            $sheet->getStyle($col . '5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($col . '5')->getAlignment()->setWrapText(true);
            $col++;
        }

        // ✅ Dữ liệu - CHẮC CHẮN LÀ ARRAY
        $row = 6;
        foreach ($data as $item) {
            // Convert object to array nếu cần
            if (is_object($item)) {
                $item = (array)$item;
            }

            \Log::info('Row ' . $row . ': ' . json_encode($item));

            $sheet->setCellValue('A' . $row, $item['ttbc'] ?? '');
            $sheet->setCellValue('B' . $row, $item['mssv'] ?? '');
            $sheet->setCellValue('C' . $row, $item['hoten'] ?? '');
            $sheet->setCellValue('D' . $row, $item['lop'] ?? '');
            $sheet->setCellValue('E' . $row, $item['tendt'] ?? '');
            $sheet->setCellValue('F' . $row, $item['ten_gvhd'] ?? '');
            $sheet->setCellValue('G' . $row, ($item['diem_hd'] ?? '') !== '' ? floatval($item['diem_hd']) : '');
            $sheet->setCellValue('H' . $row, $item['ten_gvpb'] ?? '');
            $sheet->setCellValue('I' . $row, ($item['diem_pb'] ?? '') !== '' ? floatval($item['diem_pb']) : '');
            $sheet->setCellValue('J' . $row, ($item['diem_gv1'] ?? '') !== '' ? floatval($item['diem_gv1']) : '');
            $sheet->setCellValue('K' . $row, ($item['diem_gv2'] ?? '') !== '' ? floatval($item['diem_gv2']) : '');
            $sheet->setCellValue('L' . $row, ($item['diem_gv3'] ?? '') !== '' ? floatval($item['diem_gv3']) : '');
            $sheet->setCellValue('M' . $row, ($item['diem_gv4'] ?? '') !== '' ? floatval($item['diem_gv4']) : '');
            $sheet->setCellValue('N' . $row, ($item['diem_tongket'] ?? '') !== '' ? floatval($item['diem_tongket']) : '');

            // Căn giữa các cột
            for ($col = 'A'; $col <= 'N'; $col++) {
                $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $row++;
        }

        // Độ rộng cột
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(11);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(8);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(9);
        $sheet->getColumnDimension('H')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(9);
        $sheet->getColumnDimension('J')->setWidth(7);
        $sheet->getColumnDimension('K')->setWidth(7);
        $sheet->getColumnDimension('L')->setWidth(7);
        $sheet->getColumnDimension('M')->setWidth(7);
        $sheet->getColumnDimension('N')->setWidth(9);

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