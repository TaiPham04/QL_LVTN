<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{PhieuChamDiem, DiemSinhVien, Detai, Student};
use Illuminate\Support\Facades\DB;
use App\Services\PhieuChamWordExporter;

class ChamDiemController extends Controller
{
    // =============================================
    // CHẤM ĐIỂM HƯỚNG DẪN
    // =============================================

    /**
     * Danh sách nhóm cần chấm hướng dẫn
     */
    public function indexHuongDan()
    {
        $magv = session('user')->magv;

        // Lấy danh sách nhóm từ bảng detai mà giảng viên đang hướng dẫn
        $danhSachNhom = Detai::where('magv', $magv)
            ->whereNotNull('nhom')
            ->select('nhom', 'tendt', DB::raw('COUNT(*) as so_luong_sv'))
            ->groupBy('nhom', 'tendt')
            ->get();

        // Kiểm tra xem nhóm nào đã chấm rồi
        foreach ($danhSachNhom as $nhom) {
            $phieuCham = PhieuChamDiem::where('nhom', $nhom->nhom)
                ->where('magv', $magv)
                ->where('loai_phieu', 'huong_dan')
                ->first();

            $nhom->da_cham = $phieuCham ? true : false;
            $nhom->trang_thai = $phieuCham ? $phieuCham->trang_thai : null;
            $nhom->phieu_id = $phieuCham ? $phieuCham->id : null;
        }

        return view('lecturers.cham-diem.huong-dan.index', compact('danhSachNhom'));
    }

    /**
     * Form chấm điểm hướng dẫn
     */
    public function formHuongDan($nhom)
    {
        $magv = session('user')->magv;

        // Kiểm tra quyền: giảng viên chỉ chấm nhóm mình hướng dẫn
        $kiemTra = Detai::where('nhom', $nhom)
            ->where('magv', $magv)
            ->exists();

        if (!$kiemTra) {
            return redirect()->route('lecturers.chamdiem.huongdan.index')
                ->with('error', 'Bạn không có quyền chấm điểm nhóm này!');
        }

        // Lấy thông tin đề tài
        $deTai = Detai::getDeTaiByNhom($nhom);

        // Lấy danh sách sinh viên trong nhóm
        $danhSachSinhVien = Detai::getSinhVienByNhom($nhom);

        // Kiểm tra đã chấm chưa
        $phieuCham = PhieuChamDiem::where('nhom', $nhom)
            ->where('magv', $magv)
            ->where('loai_phieu', 'huong_dan')
            ->first();

        // Nếu đã chấm rồi, lấy điểm cũ
        $diemCu = [];
        if ($phieuCham) {
            $diemCu = DiemSinhVien::where('phieu_cham_id', $phieuCham->id)
                ->get()
                ->keyBy('mssv');
        }

        return view('lecturers.cham-diem.huong-dan.form', compact(
            'nhom',
            'deTai',
            'danhSachSinhVien',
            'phieuCham',
            'diemCu'
        ));
    }

    /**
     * Lưu điểm hướng dẫn
     */
    public function storeHuongDan(Request $request, $nhom)
    {
        $magv = session('user')->magv;

        // Validate
        $request->validate([
            'dat_chuan' => 'required|boolean',
            'uu_diem' => 'required|string',
            'thieu_sot' => 'required|string',
            'ngay_cham' => 'required|date',
            'sinh_vien' => 'required|array',
            'sinh_vien.*.diem_phan_tich' => 'required|numeric|min:0|max:2.5',
            'sinh_vien.*.diem_thiet_ke' => 'required|numeric|min:0|max:2.5',
            'sinh_vien.*.diem_hien_thuc' => 'required|numeric|min:0|max:2.5',
            'sinh_vien.*.diem_kiem_tra' => 'required|numeric|min:0|max:2.5',
            'sinh_vien.*.de_nghi' => 'required|in:duoc_bao_ve,khong_duoc_bao_ve,bo_sung_hieu_chinh',
        ]);

        DB::beginTransaction();
        try {
            // Lấy tên đề tài
            $deTai = Detai::getDeTaiByNhom($nhom);

            // Tạo hoặc cập nhật phiếu chấm
            $phieuCham = PhieuChamDiem::updateOrCreate(
                [
                    'loai_phieu' => 'huong_dan',
                    'nhom' => $nhom,
                    'magv' => $magv,
                ],
                [
                    'ten_de_tai' => $deTai->tendt,
                    'dat_chuan' => $request->dat_chuan,
                    'yeu_cau_dieu_chinh' => $request->yeu_cau_dieu_chinh,
                    'uu_diem' => $request->uu_diem,
                    'thieu_sot' => $request->thieu_sot,
                    'cau_hoi' => $request->cau_hoi,
                    'ngay_cham' => $request->ngay_cham,
                    'trang_thai' => 'hoan_thanh',
                ]
            );

            // Xóa điểm cũ (nếu có)
            DiemSinhVien::where('phieu_cham_id', $phieuCham->id)->delete();

            // Lưu điểm từng sinh viên
            foreach ($request->sinh_vien as $mssv => $diem) {
                DiemSinhVien::create([
                    'phieu_cham_id' => $phieuCham->id,
                    'mssv' => $mssv,
                    'diem_phan_tich' => $diem['diem_phan_tich'],
                    'diem_thiet_ke' => $diem['diem_thiet_ke'],
                    'diem_hien_thuc' => $diem['diem_hien_thuc'],
                    'diem_kiem_tra' => $diem['diem_kiem_tra'],
                    'de_nghi' => $diem['de_nghi'],
                ]);
            }

            DB::commit();

            return redirect()
                ->route('lecturers.chamdiem.huongdan.index')
                ->with('success', 'Chấm điểm thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Export Word - Hướng dẫn
     */
    public function exportHuongDan($nhom)
    {
        try {
            $magv = session('user')->magv;
            $exporter = new PhieuChamWordExporter();
            $filePath = $exporter->exportHuongDan($nhom, $magv);
            
            return response()->download($filePath)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Không thể xuất file Word: ' . $e->getMessage());
        }
    }

    // =============================================
    // CHẤM ĐIỂM PHẢN BIỆN
    // =============================================

    /**
     * Danh sách nhóm cần chấm phản biện
     */
    public function indexPhanBien()
    {
        $magv = session('user')->magv;

        // Lấy danh sách nhóm từ bảng phancong_phanbien
        $danhSachNhom = DB::table('phancong_phanbien')
            ->where('magv_phanbien', $magv)
            ->join('detai', 'phancong_phanbien.nhom', '=', 'detai.nhom')
            ->select('detai.nhom', 'detai.tendt', DB::raw('COUNT(DISTINCT detai.mssv) as so_luong_sv'))
            ->groupBy('detai.nhom', 'detai.tendt')
            ->get();

        // Kiểm tra đã chấm chưa
        foreach ($danhSachNhom as $nhom) {
            $phieuCham = PhieuChamDiem::where('nhom', $nhom->nhom)
                ->where('magv', $magv)
                ->where('loai_phieu', 'phan_bien')
                ->first();

            $nhom->da_cham = $phieuCham ? true : false;
            $nhom->trang_thai = $phieuCham ? $phieuCham->trang_thai : null;
        }

        return view('lecturers.cham-diem.phan-bien.index', compact('danhSachNhom'));
    }

    /**
     * Form chấm điểm phản biện
     */
    public function formPhanBien($nhom)
    {
        $magv = session('user')->magv;

        // Kiểm tra quyền
        $kiemTra = DB::table('phancong_phanbien')
            ->where('nhom', $nhom)
            ->where('magv_phanbien', $magv)
            ->exists();

        if (!$kiemTra) {
            return redirect()->route('lecturers.chamdiem.phanbien.index')
                ->with('error', 'Bạn không có quyền chấm điểm nhóm này!');
        }

        // Lấy thông tin
        $deTai = Detai::getDeTaiByNhom($nhom);
        $danhSachSinhVien = Detai::getSinhVienByNhom($nhom);

        $phieuCham = PhieuChamDiem::where('nhom', $nhom)
            ->where('magv', $magv)
            ->where('loai_phieu', 'phan_bien')
            ->first();

        $diemCu = [];
        if ($phieuCham) {
            $diemCu = DiemSinhVien::where('phieu_cham_id', $phieuCham->id)
                ->get()
                ->keyBy('mssv');
        }

        return view('lecturers.cham-diem.phan-bien.form', compact(
            'nhom',
            'deTai',
            'danhSachSinhVien',
            'phieuCham',
            'diemCu'
        ));
    }

    /**
     * Lưu điểm phản biện
     */
    public function storePhanBien(Request $request, $nhom)
    {
        $magv = session('user')->magv;

        // Validate (tương tự hướng dẫn)
        $request->validate([
            'dat_chuan' => 'required|boolean',
            'uu_diem' => 'required|string',
            'thieu_sot' => 'required|string',
            'ngay_cham' => 'required|date',
            'sinh_vien' => 'required|array',
            'sinh_vien.*.diem_phan_tich' => 'required|numeric|min:0|max:2.5',
            'sinh_vien.*.diem_thiet_ke' => 'required|numeric|min:0|max:2.5',
            'sinh_vien.*.diem_hien_thuc' => 'required|numeric|min:0|max:2.5',
            'sinh_vien.*.diem_kiem_tra' => 'required|numeric|min:0|max:2.5',
            'sinh_vien.*.de_nghi' => 'required|in:duoc_bao_ve,khong_duoc_bao_ve,bo_sung_hieu_chinh',
        ]);

        DB::beginTransaction();
        try {
            $deTai = Detai::getDeTaiByNhom($nhom);

            $phieuCham = PhieuChamDiem::updateOrCreate(
                [
                    'loai_phieu' => 'phan_bien',
                    'nhom' => $nhom,
                    'magv' => $magv,
                ],
                [
                    'ten_de_tai' => $deTai->tendt,
                    'dat_chuan' => $request->dat_chuan,
                    'yeu_cau_dieu_chinh' => $request->yeu_cau_dieu_chinh,
                    'uu_diem' => $request->uu_diem,
                    'thieu_sot' => $request->thieu_sot,
                    'cau_hoi' => $request->cau_hoi,
                    'ngay_cham' => $request->ngay_cham,
                    'trang_thai' => 'hoan_thanh',
                ]
            );

            DiemSinhVien::where('phieu_cham_id', $phieuCham->id)->delete();

            foreach ($request->sinh_vien as $mssv => $diem) {
                DiemSinhVien::create([
                    'phieu_cham_id' => $phieuCham->id,
                    'mssv' => $mssv,
                    'diem_phan_tich' => $diem['diem_phan_tich'],
                    'diem_thiet_ke' => $diem['diem_thiet_ke'],
                    'diem_hien_thuc' => $diem['diem_hien_thuc'],
                    'diem_kiem_tra' => $diem['diem_kiem_tra'],
                    'de_nghi' => $diem['de_nghi'],
                ]);
            }

            DB::commit();

            return redirect()
                ->route('lecturers.chamdiem.phanbien.index')
                ->with('success', 'Chấm điểm phản biện thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Export Word - Phản biện
     */
    public function exportPhanBien($nhom)
    {
        try {
            $magv = session('user')->magv;
            $exporter = new PhieuChamWordExporter();
            $filePath = $exporter->exportPhanBien($nhom, $magv);
            
            return response()->download($filePath)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Không thể xuất file Word: ' . $e->getMessage());
        }
    }
}