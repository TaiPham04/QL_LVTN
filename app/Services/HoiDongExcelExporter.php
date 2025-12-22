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

        // Lấy danh sách đề tài và sinh viên trong hội đồng
        $danhSach = DB::table('hoidong_detai as hd')
            ->join('nhom as n', 'hd.nhom_id', '=', 'n.id')
            ->join('detai as d', 'hd.nhom_id', '=', 'd.nhom_id')
            ->join('sinhvien as s', 'd.mssv', '=', 's.mssv')
            ->leftJoin('giangvien as g', 'd.magv', '=', 'g.magv')
            ->where('hd.hoidong_id', $hoidong_id)
            ->select(
                'n.tennhom as nhom',
                'n.tendt',
                's.mssv',
                's.hoten as ten_sv',
                's.lop',
                'd.magv',
                'g.hoten as ten_gv'
            )
            ->orderBy('n.tennhom')
            ->orderBy('s.mssv')
            ->get();

        if ($danhSach->isEmpty()) {
            throw new \Exception('Không có đề tài nào trong hội đồng này!');
        }

        return $this->generateExcel($danhSach, $hoiDong->mahd, $hoiDong->tenhd);
    }

    /**
     * Tạo file Excel và lưu vào disk
     */
    private function generateExcel($danhSach, $mahd, $tenHoiDong)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Danh Sách');

        // Header
        $sheet->setCellValue('A1', 'DANH SÁCH ĐỀ TÀI HỘI ĐỒNG');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Hội Đồng: ' . $tenHoiDong . ' (' . $mahd . ')');
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', 'Ngày xuất: ' . now()->format('d/m/Y H:i'));
        $sheet->mergeCells('A3:G3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Tiêu đề cột
        $headers = ['Nhóm', 'Tên Đề Tài', 'MSSV', 'Tên SV', 'Lớp', 'GVHD', 'Email GV'];
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
        foreach ($danhSach as $item) {
            $sheet->setCellValue('A' . $row, $item->nhom);
            $sheet->setCellValue('B' . $row, $item->tendt);
            $sheet->setCellValue('C' . $row, $item->mssv);
            $sheet->setCellValue('D' . $row, $item->ten_sv);
            $sheet->setCellValue('E' . $row, $item->lop);
            $sheet->setCellValue('F' . $row, $item->ten_gv ?? 'N/A');
            
            // Lấy email từ magv
            $email = '';
            if ($item->magv) {
                $email = DB::table('giangvien')
                    ->where('magv', $item->magv)
                    ->value('email') ?? '';
            }
            $sheet->setCellValue('G' . $row, $email);

            $row++;
        }

        // Độ rộng cột
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(25);

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