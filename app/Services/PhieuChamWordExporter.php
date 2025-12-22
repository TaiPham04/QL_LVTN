<?php

namespace App\Services;

use PhpOffice\PhpWord\TemplateProcessor;
use App\Models\{PhieuChamDiem, DiemSinhVien, Detai};
use Illuminate\Support\Facades\DB;

class PhieuChamWordExporter
{
    /**
     * Export phiếu chấm hướng dẫn
     */
    public function exportHuongDan($nhom_id, $magv)
    {
        // Tìm theo nhom_id thay vì nhom
        $phieuCham = PhieuChamDiem::where('nhom_id', $nhom_id)
            ->where('magv', $magv)
            ->where('loai_phieu', 'huong_dan')
            ->firstOrFail();

        // Lấy danh sách sinh viên theo nhom_id
        $danhSachSinhVien = Detai::where('nhom_id', $nhom_id)->get();
        $soLuongSV = $danhSachSinhVien->count();

        // Chọn template theo số lượng sinh viên
        $templateFile = $soLuongSV == 1 
            ? 'phieu_huongdan_1sv.docx'     
            : 'phieu_huongdan_2sv.docx';

        return $this->generateDocument($phieuCham, $danhSachSinhVien, $templateFile, 'huong_dan');
    }

    /**
     * Export phiếu chấm phản biện
     */
    public function exportPhanBien($nhom_id, $magv)
    {
        // Tìm theo nhom_id thay vì nhom
        $phieuCham = PhieuChamDiem::where('nhom_id', $nhom_id)
            ->where('magv', $magv)
            ->where('loai_phieu', 'phan_bien')
            ->firstOrFail();

        // Lấy danh sách sinh viên theo nhom_id
        $danhSachSinhVien = Detai::where('nhom_id', $nhom_id)->get();
        $soLuongSV = $danhSachSinhVien->count();

        // Chọn template theo số lượng sinh viên
        $templateFile = $soLuongSV == 1 
            ? 'phieu_phanbien_1sv.docx'     
            : 'phieu_phanbien_2sv.docx';    

        return $this->generateDocument($phieuCham, $danhSachSinhVien, $templateFile, 'phan_bien');
    }


    private function generateDocument($phieuCham, $danhSachSinhVien, $templateFile, $loaiPhieu)
    {
        $templatePath = storage_path('app/templates/' . $templateFile);

        // Debug 1: Kiểm tra file tồn tại
        if (!file_exists($templatePath)) {
            throw new \Exception("Template không tồn tại: {$templatePath}");
        }

        // Debug 2: Kiểm tra có quyền đọc
        if (!is_readable($templatePath)) {
            throw new \Exception("Không có quyền đọc file: {$templatePath}");
        }

        // Debug 3: Kiểm tra kích thước file
        $fileSize = filesize($templatePath);
        if ($fileSize === 0) {
            throw new \Exception("File rỗng (0 bytes): {$templatePath}");
        }

        // Debug 4: Kiểm tra file có phải ZIP không
        $zip = new \ZipArchive();
        $zipResult = $zip->open($templatePath, \ZipArchive::CHECKCONS);
        
        if ($zipResult !== true) {
            $errorCodes = [
                \ZipArchive::ER_EXISTS => 'File đã tồn tại',
                \ZipArchive::ER_INCONS => 'ZIP không nhất quán',
                \ZipArchive::ER_INVAL => 'Tham số không hợp lệ',
                \ZipArchive::ER_MEMORY => 'Lỗi bộ nhớ',
                \ZipArchive::ER_NOENT => 'File không tồn tại',
                \ZipArchive::ER_NOZIP => 'Không phải file ZIP',
                \ZipArchive::ER_OPEN => 'Không thể mở file',
                \ZipArchive::ER_READ => 'Lỗi đọc file',
                \ZipArchive::ER_SEEK => 'Lỗi seek',
            ];
            
            $errorMsg = $errorCodes[$zipResult] ?? "Mã lỗi: {$zipResult}";
            throw new \Exception("Lỗi khi mở file ZIP: {$errorMsg}. File: {$templatePath}, Size: {$fileSize} bytes");
        }
        $zip->close();

        // Debug 5: Thử tạo TemplateProcessor
        try {
            $templateProcessor = new TemplateProcessor($templatePath);
        } catch (\Exception $e) {
            throw new \Exception("Lỗi TemplateProcessor: " . $e->getMessage() . " | File: {$templatePath}");
        }

        // === PHẦN XỬ LÝ BÌNH THƯỜNG ===
        
        // Lấy tên đề tài từ bảng nhom
        $nhom = DB::table('nhom')->where('id', $phieuCham->nhom_id)->first();
        $tenDeTai = $nhom ? $nhom->tendt : 'N/A';
        $tenNhom = $nhom ? $nhom->tennhom : 'N/A';

        // Thông tin chung
        $templateProcessor->setValue('ten_de_tai', $tenDeTai);
        $templateProcessor->setValue('nhom', $tenNhom);
        
        $giangVien = DB::table('giangvien')
            ->where('magv', $phieuCham->magv)
            ->first();

        $tenGiangVien = $giangVien ? $giangVien->hoten : $phieuCham->magv;
        $tenGiangVien = mb_convert_case(mb_strtolower($tenGiangVien, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        $templateProcessor->setValue('magv', $tenGiangVien);
        
        if ($phieuCham->ngay_cham) {
            $templateProcessor->setValue('ngay_cham', date('d', strtotime($phieuCham->ngay_cham)));
            $templateProcessor->setValue('thang_cham', date('m', strtotime($phieuCham->ngay_cham)));
            $templateProcessor->setValue('nam_cham', date('Y', strtotime($phieuCham->ngay_cham)));
        }

        // Nhận xét
        $templateProcessor->setValue('dat_chuan', $phieuCham->dat_chuan ? '☑' : '☐');
        $templateProcessor->setValue('khong_dat_chuan', $phieuCham->dat_chuan ? '☐' : '☑');
        $templateProcessor->setValue('yeu_cau_dieu_chinh', $phieuCham->yeu_cau_dieu_chinh ?? '');
        $templateProcessor->setValue('uu_diem', $phieuCham->uu_diem ?? '');
        $templateProcessor->setValue('thieu_sot', $phieuCham->thieu_sot ?? '');
        $templateProcessor->setValue('cau_hoi', $phieuCham->cau_hoi ?? '');

        // Thông tin sinh viên và điểm
        foreach ($danhSachSinhVien as $index => $sv) {
            $stt = $index + 1;
            
            // Lấy điểm từ diem_sinh_vien
            $diemSV = DB::table('diem_sinh_vien')
                ->where('phieu_cham_id', $phieuCham->id)
                ->where('mssv', $sv->mssv)
                ->first();

            if (!$diemSV) {
                continue;
            }

            // Lấy thông tin sinh viên
            $sinhVien = DB::table('sinhvien')->where('mssv', $sv->mssv)->first();

            // Thông tin sinh viên
            $templateProcessor->setValue("hoten_sv{$stt}", $sinhVien ? $sinhVien->hoten : '');
            $templateProcessor->setValue("mssv_sv{$stt}", $sinhVien ? $sinhVien->mssv : '');
            $templateProcessor->setValue("lop_sv{$stt}", $sinhVien ? $sinhVien->lop : '');

            // Điểm chi tiết
            $templateProcessor->setValue("diem_phan_tich_sv{$stt}", number_format($diemSV->diem_phan_tich, 1));
            $templateProcessor->setValue("diem_thiet_ke_sv{$stt}", number_format($diemSV->diem_thiet_ke, 1));
            $templateProcessor->setValue("diem_hien_thuc_sv{$stt}", number_format($diemSV->diem_hien_thuc, 1));
            $templateProcessor->setValue("diem_kiem_tra_sv{$stt}", number_format($diemSV->diem_kiem_tra, 1));

            // Tổng điểm
            $tongDiem = $diemSV->diem_phan_tich + $diemSV->diem_thiet_ke + 
                        $diemSV->diem_hien_thuc + $diemSV->diem_kiem_tra;
            
            $templateProcessor->setValue("diem_tong_sv{$stt}", number_format($tongDiem, 1));
            $templateProcessor->setValue("diem_tong_chu_sv{$stt}", $this->convertNumberToWords($tongDiem));

            // Tỷ lệ phần trăm
            $tyLe = ($tongDiem / 10) * 100;
            $templateProcessor->setValue("ty_le_sv{$stt}", number_format($tyLe, 0) . '%');

            // Đề nghị
            $deNghiText = '';
            switch ($diemSV->de_nghi) {
                case 'duoc_bao_ve':
                    $deNghiText = 'Được bảo vệ';
                    $templateProcessor->setValue("de_nghi_duoc_sv{$stt}", '☑');
                    $templateProcessor->setValue("de_nghi_khong_sv{$stt}", '☐');
                    $templateProcessor->setValue("de_nghi_bo_sung_sv{$stt}", '☐');
                    break;
                case 'khong_duoc_bao_ve':
                    $deNghiText = 'Không được bảo vệ';
                    $templateProcessor->setValue("de_nghi_duoc_sv{$stt}", '☐');
                    $templateProcessor->setValue("de_nghi_khong_sv{$stt}", '☑');
                    $templateProcessor->setValue("de_nghi_bo_sung_sv{$stt}", '☐');
                    break;
                case 'bo_sung_hieu_chinh':
                    $deNghiText = 'Bổ sung/hiệu chỉnh để được bảo vệ';
                    $templateProcessor->setValue("de_nghi_duoc_sv{$stt}", '☐');
                    $templateProcessor->setValue("de_nghi_khong_sv{$stt}", '☐');
                    $templateProcessor->setValue("de_nghi_bo_sung_sv{$stt}", '☑');
                    break;
            }

            // Thêm placeholder dạng text
            $templateProcessor->setValue("de_nghi_text_sv{$stt}", $deNghiText);
        }

        // Tạo file output
        $loaiPhieuText = $loaiPhieu == 'huong_dan' ? 'HuongDan' : 'PhanBien';
        $fileName = "PhieuCham_{$loaiPhieuText}_{$tenNhom}_" . date('YmdHis') . '.docx';
        $outputPath = storage_path('app/public/phieu_cham/' . $fileName);

        // Tạo thư mục nếu chưa có
        if (!file_exists(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0777, true);
        }

        $templateProcessor->saveAs($outputPath);

        // Lưu đường dẫn vào database
        $phieuCham->update(['file_word_path' => 'phieu_cham/' . $fileName]);

        return $outputPath;
    }

    /**
     * Chuyển số thành chữ (tiếng Việt)
     */
    private function convertNumberToWords($number)
    {
        $number = round($number, 1);
        
        $units = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
        
        $integer = floor($number);
        $decimal = round(($number - $integer) * 10);
        
        $result = '';
        
        // Phần nguyên
        if ($integer == 0) {
            $result = 'không';
        } elseif ($integer < 10) {
            $result = $units[$integer];
        } elseif ($integer == 10) {
            $result = 'mười';
        } else {
            $result = $units[floor($integer / 10)] . ' mươi';
            if ($integer % 10 > 0) {
                $result .= ' ' . $units[$integer % 10];
            }
        }
        
        // Phần thập phân
        if ($decimal > 0) {
            $result .= ' phẩy ' . $units[$decimal];
        }
        
        return ucfirst($result) . ' điểm';
    }
}