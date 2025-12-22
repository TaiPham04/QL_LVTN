<?php

namespace App\Services;

use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\DB;

class NhiemVuWordExporter
{
    public function export($nhom_id, $magv)
    {
        // Lấy thông tin nhiệm vụ
        $nhiemvu = DB::table('nhiemvu')
            ->where('nhom_id', $nhom_id)
            ->where('magv', $magv)
            ->first();

        if (!$nhiemvu) {
            throw new \Exception('Chưa có thông tin nhiệm vụ cho nhóm này!');
        }

        // Lấy tên nhóm để tạo tên file
        $nhom = DB::table('nhom')->where('id', $nhom_id)->value('tennhom');

        // Đường dẫn template
        $templatePath = storage_path('app/templates/nhiemvu_template.docx');

        if (!file_exists($templatePath)) {
            throw new \Exception('Không tìm thấy file template tại: ' . $templatePath);
        }

        // Load template
        $templateProcessor = new TemplateProcessor($templatePath);

        // Thay thế thông tin sinh viên 1
        $templateProcessor->setValue('sv1_hoten', $nhiemvu->sv1_hoten ?? '');
        $templateProcessor->setValue('sv1_mssv', $nhiemvu->sv1_mssv ?? '');
        $templateProcessor->setValue('sv1_lop', $nhiemvu->sv1_lop ?? '');
        
        // Thay thế thông tin sinh viên 2
        $templateProcessor->setValue('sv2_hoten', $nhiemvu->sv2_hoten ?? '');
        $templateProcessor->setValue('sv2_mssv', $nhiemvu->sv2_mssv ?? '');
        $templateProcessor->setValue('sv2_lop', $nhiemvu->sv2_lop ?? '');
        
        // Thay thế các thông tin khác
        $templateProcessor->setValue('dau_de_bai_thi', $nhiemvu->dau_de_bai_thi ?? '');
        $templateProcessor->setValue('nhiem_vu_noi_dung', $nhiemvu->nhiem_vu_noi_dung ?? '');
        $templateProcessor->setValue('ho_so_tai_lieu', $nhiemvu->ho_so_tai_lieu ?? '');
        $templateProcessor->setValue('ngay_giao', $this->formatDate($nhiemvu->ngay_giao));
        $templateProcessor->setValue('ngay_hoanthanh', $this->formatDate($nhiemvu->ngay_hoanthanh));
        $templateProcessor->setValue('nguoi_huongdan_1', $nhiemvu->nguoi_huongdan_1 ?? '');
        $templateProcessor->setValue('phan_huongdan_1', $nhiemvu->phan_huongdan_1 ?? '');
        $templateProcessor->setValue('nguoi_huongdan_2', $nhiemvu->nguoi_huongdan_2 ?? '');
        $templateProcessor->setValue('phan_huongdan_2', $nhiemvu->phan_huongdan_2 ?? '');
        
        // Ngày ký
        $ngay_ky = date('d') . ' tháng ' . date('m') . ' năm ' . date('Y');
        $templateProcessor->setValue('ngay_ky', $ngay_ky);

        // Tạo tên file
        $fileName = "NhiemVu_{$nhom}_" . date('YmdHis') . '.docx';
        $filePath = storage_path('app/public/exports/' . $fileName);

        // Tạo thư mục nếu chưa có
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        // Lưu file
        $templateProcessor->saveAs($filePath);

        return $filePath;
    }

    private function formatDate($date)
    {
        if (!$date) return '';
        
        try {
            return date('d/m/Y', strtotime($date));
        } catch (\Exception $e) {
            return $date;
        }
    }
}