<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class HoiDongExcelExporter
{
    /**
     * Export danh sách đề tài của hội đồng
     */
    public function export($hoidong_id)
    {
        // Lấy thông tin hội đồng
        $hoiDong = DB::table('hoidong')
            ->where('id', $hoidong_id)
            ->first();

        if (!$hoiDong) {
            throw new \Exception('Hội đồng không tồn tại!');
        }

        // ✅ Lấy danh sách đề tài và sinh viên trong hội đồng - THÊM thu_tu
        $danhSach = DB::table('hoidong_detai as hd')
            ->join('nhom as n', 'hd.nhom_id', '=', 'n.id')
            ->join('detai as d', 'hd.nhom_id', '=', 'd.nhom_id')
            ->join('sinhvien as s', 'd.mssv', '=', 's.mssv')
            ->leftJoin('giangvien as g', 'd.magv', '=', 'g.magv')
            ->where('hd.hoidong_id', $hoidong_id)
            ->select(
                'hd.thu_tu',
                'n.tennhom as nhom',
                'n.tendt',
                's.mssv',
                's.hoten as ten_sv',
                's.lop',
                'd.magv',
                'g.hoten as ten_gv'
            )
            ->orderBy('hd.thu_tu')
            ->orderBy('n.tennhom')
            ->orderBy('s.mssv')
            ->get();

        if ($danhSach->isEmpty()) {
            throw new \Exception('Không có đề tài nào trong hội đồng này!');
        }

        return $this->generateExcel($danhSach, $hoiDong->mahd, $hoiDong->tenhd, $hoiDong->ngay_hoidong);
    }

    /**
     * Tạo file Excel và lưu vào disk
     */
    private function generateExcel($danhSach, $mahd, $tenHoiDong, $ngayHoiDong)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Danh Sách');

        // Header
        $sheet->setCellValue('A1', 'DANH SÁCH ĐỀ TÀI HỘI ĐỒNG');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Hội Đồng: ' . $tenHoiDong . ' (' . $mahd . ')');
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ✅ Hiển thị ngày hội đồng thay vì ngày xuất
        $ngayDinhDang = $ngayHoiDong ? \Carbon\Carbon::parse($ngayHoiDong)->format('d/m/Y') : 'Chưa chọn';
        $sheet->setCellValue('A3', 'Ngày hội đồng: ' . $ngayDinhDang);
        $sheet->mergeCells('A3:H3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ✅ Tiêu đề cột - THÊM cột "Thứ Tự"
        $headers = ['Thứ Tự', 'Nhóm', 'Tên Đề Tài', 'MSSV', 'Tên SV', 'Lớp', 'GVHD', 'Email GV'];
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

        // ✅ Dữ liệu - THÊM giá trị thứ tự
        $row = 6;
        foreach ($danhSach as $item) {
            $sheet->setCellValue('A' . $row, $item->thu_tu ?? '-');
            $sheet->setCellValue('B' . $row, $item->nhom);
            $sheet->setCellValue('C' . $row, $item->tendt);
            $sheet->setCellValue('D' . $row, $item->mssv);
            $sheet->setCellValue('E' . $row, $item->ten_sv);
            $sheet->setCellValue('F' . $row, $item->lop);
            $sheet->setCellValue('G' . $row, $item->ten_gv ?? 'N/A');
            
            // Lấy email từ magv
            $email = '';
            if ($item->magv) {
                $email = DB::table('giangvien')
                    ->where('magv', $item->magv)
                    ->value('email') ?? '';
            }
            $sheet->setCellValue('H' . $row, $email);

            $row++;
        }

        // ✅ Độ rộng cột - CẬP NHẬT cho 8 cột
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(25);

        // Lưu file vào temp storage
        $filename = 'DanhSachHoiDong_' . $mahd . '_' . time() . '.xlsx';
        $filepath = storage_path('app/temp/' . $filename);
        
        // Tạo thư mục nếu chưa tồn tại
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $filepath;
    }
}