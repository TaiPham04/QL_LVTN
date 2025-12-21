<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChamDiemHoiDongExport
{
    /**
     * Kiểm tra tất cả đề tài đã chấm điểm đủ chưa
     */
    public function checkAllScored($mahd)
    {
        // Lấy danh sách đề tài
        $deTaiList = DB::table('hoidong_detai as hdt')
            ->join('detai as dt', 'hdt.nhom', '=', 'dt.nhom')
            ->where('hdt.mahd', $mahd)
            ->select('dt.nhom', 'dt.tendt')
            ->distinct()
            ->get();

        if ($deTaiList->isEmpty()) {
            return [
                'scored' => true,
                'uncheckedCount' => 0,
                'message' => 'Không có đề tài'
            ];
        }

        // Lấy số thành viên hội đồng
        $soThanhVien = DB::table('thanhvienhoidong')
            ->where('mahd', $mahd)
            ->count();

        if ($soThanhVien === 0) {
            return [
                'scored' => true,
                'uncheckedCount' => 0,
                'message' => 'Hội đồng không có thành viên'
            ];
        }

        $uncheckedCount = 0;

        // Kiểm tra từng đề tài
        foreach ($deTaiList as $deTai) {
            $sinhVienNhom = DB::table('detai as dt')
                ->join('sinhvien as sv', 'dt.mssv', '=', 'sv.mssv')
                ->where('dt.nhom', $deTai->nhom)
                ->select('sv.mssv')
                ->get();

            foreach ($sinhVienNhom as $sv) {
                $diemCount = DB::table('hoidong_chamdiem')
                    ->where('mahd', $mahd)
                    ->where('nhom', $deTai->nhom)
                    ->where('mssv', $sv->mssv)
                    ->count();

                if ($diemCount < $soThanhVien) {
                    $uncheckedCount++;
                }
            }
        }

        return [
            'scored' => $uncheckedCount === 0,
            'uncheckedCount' => $uncheckedCount,
            'message' => $uncheckedCount > 0 
                ? "Còn $uncheckedCount đề tài chưa chấm đủ!" 
                : 'Tất cả đề tài đã chấm'
        ];
    }

    /**
     * Xuất file Excel
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

        // Lấy danh sách thành viên hội đồng
        $thanhVien = DB::table('thanhvienhoidong as tv')
            ->join('giangvien as gv', 'tv.magv', '=', 'gv.magv')
            ->where('tv.mahd', $mahd)
            ->select('tv.magv', 'gv.hoten', 'tv.vai_tro')
            ->orderBy('tv.vai_tro')
            ->get();

        // Lấy danh sách đề tài
        $deTaiList = DB::table('hoidong_detai as hdt')
            ->join('detai as dt', 'hdt.nhom', '=', 'dt.nhom')
            ->where('hdt.mahd', $mahd)
            ->select('dt.nhom', 'dt.tendt')
            ->distinct()
            ->get();

        // Chuẩn bị dữ liệu
        $data = [];
        foreach ($deTaiList as $deTai) {
            $sinhVienNhom = DB::table('detai as dt')
                ->join('sinhvien as sv', 'dt.mssv', '=', 'sv.mssv')
                ->where('dt.nhom', $deTai->nhom)
                ->select('sv.mssv', 'sv.hoten', 'sv.lop')
                ->get();

            foreach ($sinhVienNhom as $sv) {
                $row = [
                    'nhom' => $deTai->nhom,
                    'mssv' => $sv->mssv,
                    'hoten' => $sv->hoten,
                    'lop' => $sv->lop,
                    'tendt' => $deTai->tendt
                ];

                // Lấy điểm từng thành viên
                foreach ($thanhVien as $tv) {
                    $diem = DB::table('hoidong_chamdiem')
                        ->where('mahd', $mahd)
                        ->where('nhom', $deTai->nhom)
                        ->where('mssv', $sv->mssv)
                        ->where('magv_danh_gia', $tv->magv)
                        ->value('diem') ?? '';
                    
                    $row['diem_' . $tv->magv] = $diem;
                }

                $data[] = $row;
            }
        }

        return $this->generateExcel($data, $hoiDong->tenhd, $thanhVien);
    }

    /**
     * Tạo và trả về file Excel
     */
    private function generateExcel($data, $tenHoiDong, $thanhVien)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Chấm Điểm');

        // Header
        $sheet->setCellValue('A1', 'HỘI ĐỒNG: ' . $tenHoiDong);
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Ngày xuất: ' . now()->format('d/m/Y H:i'));
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Tiêu đề cột
        $headers = ['Nhóm', 'MSSV', 'Tên SV', 'Lớp', 'Tên Đề Tài'];
        
        // Thêm tên thành viên vào header (không viết tắt)
        foreach ($thanhVien as $tv) {
            $headers[] = $tv->hoten;
        }
        
        // Thêm cột điểm tổng
        $headers[] = 'Điểm TB';

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

            // Điểm từng thành viên
            $col = 'F';
            $diemList = [];
            foreach ($thanhVien as $tv) {
                $diemValue = $item['diem_' . $tv->magv];
                $sheet->setCellValue($col . $row, $diemValue);
                $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Nếu có điểm thì thêm vào mảng tính TB
                if ($diemValue !== '') {
                    $diemList[] = $diemValue;
                }
                
                $col++;
            }

            // Cột Điểm TB (tổng)
            if (!empty($diemList)) {
                $diemTB = round(array_sum($diemList) / count($diemList), 2);
                $sheet->setCellValue($col . $row, $diemTB);
            } else {
                $sheet->setCellValue($col . $row, '');
            }
            $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        // Độ rộng cột
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(30);
        
        // Độ rộng cột thành viên
        $col = 'F';
        for ($i = 0; $i < count($thanhVien); $i++) {
            $sheet->getColumnDimension($col)->setWidth(25);
            $col++;
        }

        // Xuất file
        $filename = 'HoiDong_' . now()->format('YmdHis') . '.xlsx';
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

    /**
     * Viết tắt tên giảng viên
     */
    private function abbreviateName($fullName)
    {
        $parts = explode(' ', trim($fullName));
        if (count($parts) <= 1) {
            return $fullName;
        }

        $firstName = $parts[0];
        $lastName = implode(' ', array_slice($parts, 1));

        return strtoupper(substr($firstName, 0, 1)) . '. ' . $lastName;
    }
}