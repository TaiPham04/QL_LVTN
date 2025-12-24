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

        // ✅ FIX: Lấy danh sách nhóm từ bảng detai (không leftJoin detai)
        $danhSachNhom = DB::table('detai as dt')
            ->join('nhom as n', 'dt.nhom_id', '=', 'n.id')
            ->where('dt.magv', $magv)
            ->whereNotNull('dt.nhom_id')
            ->select(
                'n.id as nhom_id',
                'n.tennhom as nhom',
                'n.tendt',
                DB::raw('COUNT(DISTINCT dt.mssv) as so_luong_sv')
            )
            ->groupBy('n.id', 'n.tennhom', 'n.tendt')
            ->get();

        // Kiểm tra xem nhóm nào đã chấm rồi
        foreach ($danhSachNhom as $nhom) {
            $phieuCham = PhieuChamDiem::where('nhom_id', $nhom->nhom_id)
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
    public function formHuongDan($nhom_id)
    {
        $magv = session('user')->magv;

        // Kiểm tra quyền: giảng viên chỉ chấm nhóm mình hướng dẫn
        $kiemTra = Detai::where('nhom_id', $nhom_id)
            ->where('magv', $magv)
            ->exists();

        if (!$kiemTra) {
            return redirect()->route('lecturers.chamdiem.huongdan.index')
                ->with('error', 'Bạn không có quyền chấm điểm nhóm này!');
        }

        // Lấy thông tin đề tài từ bảng nhom
        $deTai = DB::table('nhom')->where('id', $nhom_id)->first();
        $nhom = $deTai->tennhom;

        // Lấy danh sách sinh viên trong nhóm
        $danhSachSinhVien = Detai::where('nhom_id', $nhom_id)->get();

        // Kiểm tra đã chấm chưa
        $phieuCham = PhieuChamDiem::where('nhom_id', $nhom_id)
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
            'nhom_id',
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
    public function storeHuongDan(Request $request, $nhom_id)
    {
        $magv = session('user')->magv;

        \Log::info('=== START storeHuongDan ===');
        \Log::info('nhom_id: ' . $nhom_id);
        \Log::info('magv: ' . $magv);
        \Log::info('Request all: ', $request->all());

        // Validate
        $validated = $request->validate([
            'dat_chuan' => 'required|in:0,1',
            'uu_diem' => 'required|string|min:5',
            'thieu_sot' => 'required|string|min:5',
            'ngay_cham' => 'required|date',
            'sinh_vien' => 'required|array|min:1',
            'sinh_vien.*.diem_phan_tich' => 'required|numeric|min:0|max:2.5',
            'sinh_vien.*.diem_thiet_ke' => 'required|numeric|min:0|max:2.5',
            'sinh_vien.*.diem_hien_thuc' => 'required|numeric|min:0|max:2.5',
            'sinh_vien.*.diem_kiem_tra' => 'required|numeric|min:0|max:2.5',
            'sinh_vien.*.de_nghi' => 'required|in:duoc_bao_ve,khong_duoc_bao_ve,bo_sung_hieu_chinh',
        ], [
            'sinh_vien.required' => 'Vui lòng điền điểm cho sinh viên!',
            'sinh_vien.min' => 'Vui lòng điền điểm cho ít nhất 1 sinh viên!',
            'sinh_vien.*.diem_phan_tich.required' => 'Vui lòng điền điểm Phân tích!',
            'sinh_vien.*.diem_thiet_ke.required' => 'Vui lòng điền điểm Thiết kế!',
            'sinh_vien.*.diem_hien_thuc.required' => 'Vui lòng điền điểm Hiện thực!',
            'sinh_vien.*.diem_kiem_tra.required' => 'Vui lòng điền điểm Kiểm tra!',
            'sinh_vien.*.de_nghi.required' => 'Vui lòng chọn đề nghị!',
        ]);

        \Log::info('Validated data: ', $validated);

        DB::beginTransaction();
        try {
            \Log::info('DB transaction started');
            // Lấy thông tin đề tài và chi tiết detai
            $deTai = DB::table('nhom')->where('id', $nhom_id)->first();
            $detai = Detai::where('nhom_id', $nhom_id)->first();
            $mdt = $detai ? $detai->madt : null;

            \Log::info('Creating phieu: nhom_id=' . $nhom_id . ', mdt=' . $mdt . ', magv=' . $magv);

            // Sử dụng raw query thay vì updateOrCreate()
            $phieuChamId = DB::table('phieu_cham_diem')->insertOrIgnore([
                'loai_phieu' => 'huong_dan',
                'nhom_id' => $nhom_id,
                'mdt' => $mdt,
                'magv' => $magv,
                'dat_chuan' => (int) $request->dat_chuan,
                'yeu_cau_dieu_chinh' => $request->yeu_cau_dieu_chinh ?? '',
                'uu_diem' => $request->uu_diem,
                'thieu_sot' => $request->thieu_sot,
                'cau_hoi' => $request->cau_hoi ?? '',
                'ngay_cham' => $request->ngay_cham,
                'trang_thai' => 'hoan_thanh',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Nếu insert mới, lấy ID mới. Nếu đã tồn tại, update
            if (!$phieuChamId) {
                DB::table('phieu_cham_diem')
                    ->where('loai_phieu', 'huong_dan')
                    ->where('nhom_id', $nhom_id)
                    ->where('magv', $magv)
                    ->update([
                        'dat_chuan' => (int) $request->dat_chuan,
                        'yeu_cau_dieu_chinh' => $request->yeu_cau_dieu_chinh ?? '',
                        'uu_diem' => $request->uu_diem,
                        'thieu_sot' => $request->thieu_sot,
                        'cau_hoi' => $request->cau_hoi ?? '',
                        'ngay_cham' => $request->ngay_cham,
                        'trang_thai' => 'hoan_thanh',
                        'updated_at' => now(),
                    ]);
            }

            // Lấy ID phiếu chấm
            $phieuCham = DB::table('phieu_cham_diem')
                ->where('loai_phieu', 'huong_dan')
                ->where('nhom_id', $nhom_id)
                ->where('magv', $magv)
                ->first();

            $phieuChamId = $phieuCham->id;

            \Log::info('PhieuCham ID: ' . $phieuChamId);

            // Xóa điểm cũ (nếu có)
            DB::table('diem_sinh_vien')->where('phieu_cham_id', $phieuChamId)->delete();

            \Log::info('Old diem deleted, phieu_cham_id: ' . $phieuChamId);

            // Lưu điểm từng sinh viên
            foreach ($request->sinh_vien as $mssv => $diem) {
                \Log::info('Inserting diem for mssv: ' . $mssv, $diem);
                
                DB::table('diem_sinh_vien')->insert([
                    'phieu_cham_id' => $phieuChamId,
                    'mssv' => $mssv,
                    'diem_phan_tich' => $diem['diem_phan_tich'],
                    'diem_thiet_ke' => $diem['diem_thiet_ke'],
                    'diem_hien_thuc' => $diem['diem_hien_thuc'],
                    'diem_kiem_tra' => $diem['diem_kiem_tra'],
                    'de_nghi' => $diem['de_nghi'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                \Log::info('Diem inserted successfully for mssv: ' . $mssv);
            }

            DB::commit();

            \Log::info('=== SUCCESS storeHuongDan ===');

            return redirect()
                ->route('lecturers.chamdiem.huongdan.index')
                ->with('success', 'Chấm điểm hướng dẫn thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('=== ERROR storeHuongDan ===');
            \Log::error('Message: ' . $e->getMessage());
            \Log::error('File: ' . $e->getFile());
            \Log::error('Line: ' . $e->getLine());
            \Log::error('Stack: ' . $e->getTraceAsString());
            
            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Export Word - Hướng dẫn
     */
    public function exportHuongDan($nhom_id)
    {
        try {
            $magv = session('user')->magv;
            $exporter = new PhieuChamWordExporter();
            $filePath = $exporter->exportHuongDan($nhom_id, $magv);
            
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

        // ✅ FIX: Thêm WHERE condition để giới hạn dữ liệu
        // Bỏ leftJoin detai, chỉ join với phancong_phanbien (table nhỏ)
        $danhSachNhom = DB::table('phancong_phanbien as pb')
            ->where('pb.magv_phanbien', $magv)
            ->join('nhom as n', 'pb.nhom_id', '=', 'n.id')
            ->select(
                'n.id as nhom_id', 
                'n.tennhom as nhom', 
                'n.tendt'
            )
            ->distinct()
            ->get();

        // ✅ Đếm sinh viên riêng (không leftJoin)
        foreach ($danhSachNhom as $nhom) {
            $so_luong_sv = DB::table('detai')
                ->where('nhom_id', $nhom->nhom_id)
                ->count();
            
            $nhom->so_luong_sv = $so_luong_sv;

            // Kiểm tra đã chấm chưa
            $phieuCham = PhieuChamDiem::where('nhom_id', $nhom->nhom_id)
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
    public function formPhanBien($nhom_id)
    {
        $magv = session('user')->magv;

        // Kiểm tra quyền - sửa tên cột thành magv_phanbien
        $kiemTra = DB::table('phancong_phanbien')
            ->where('nhom_id', $nhom_id)
            ->where('magv_phanbien', $magv)
            ->exists();

        if (!$kiemTra) {
            return redirect()->route('lecturers.chamdiem.phanbien.index')
                ->with('error', 'Bạn không có quyền chấm điểm nhóm này!');
        }

        // Lấy thông tin
        $deTai = DB::table('nhom')->where('id', $nhom_id)->first();
        $nhom = $deTai->tennhom;
        $danhSachSinhVien = Detai::where('nhom_id', $nhom_id)->get();

        $phieuCham = PhieuChamDiem::where('nhom_id', $nhom_id)
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
            'nhom_id',
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
    public function storePhanBien(Request $request, $nhom_id)
    {
        $magv = session('user')->magv;

        \Log::info('=== START storePhanBien ===');
        \Log::info('nhom_id: ' . $nhom_id . ', magv: ' . $magv);
        \Log::info('Request data: ', $request->all());

        // Validate
        $validated = $request->validate([
            'dat_chuan' => 'required|in:0,1',
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

        \Log::info('Validated data: ', $validated);

        DB::beginTransaction();
        try {
            \Log::info('DB transaction started');

            // Lấy thông tin detai
            $detai = Detai::where('nhom_id', $nhom_id)->first();
            $mdt = $detai ? $detai->madt : null;

            \Log::info('Creating phieu: nhom_id=' . $nhom_id . ', mdt=' . $mdt . ', magv=' . $magv);

            // Sử dụng raw query
            $phieuChamId = DB::table('phieu_cham_diem')->insertOrIgnore([
                'loai_phieu' => 'phan_bien',
                'nhom_id' => $nhom_id,
                'mdt' => $mdt,
                'magv' => $magv,
                'dat_chuan' => (int) $validated['dat_chuan'],
                'yeu_cau_dieu_chinh' => $request->yeu_cau_dieu_chinh ?? '',
                'uu_diem' => $validated['uu_diem'],
                'thieu_sot' => $validated['thieu_sot'],
                'cau_hoi' => $request->cau_hoi ?? '',
                'ngay_cham' => $validated['ngay_cham'],
                'trang_thai' => 'hoan_thanh',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Log::info('insertOrIgnore result: ' . ($phieuChamId ? 'inserted' : 'already exists'));

            // Nếu chưa insert (đã tồn tại), thì update
            if (!$phieuChamId) {
                \Log::info('Record already exists, updating...');
                DB::table('phieu_cham_diem')
                    ->where('loai_phieu', 'phan_bien')
                    ->where('nhom_id', $nhom_id)
                    ->where('magv', $magv)
                    ->update([
                        'dat_chuan' => (int) $validated['dat_chuan'],
                        'yeu_cau_dieu_chinh' => $request->yeu_cau_dieu_chinh ?? '',
                        'uu_diem' => $validated['uu_diem'],
                        'thieu_sot' => $validated['thieu_sot'],
                        'cau_hoi' => $request->cau_hoi ?? '',
                        'ngay_cham' => $validated['ngay_cham'],
                        'trang_thai' => 'hoan_thanh',
                        'updated_at' => now(),
                    ]);
            }

            // Lấy ID phiếu chấm
            $phieuCham = DB::table('phieu_cham_diem')
                ->where('loai_phieu', 'phan_bien')
                ->where('nhom_id', $nhom_id)
                ->where('magv', $magv)
                ->first();

            if (!$phieuCham) {
                throw new \Exception('Không thể lấy phiếu chấm sau khi insert/update');
            }

            $phieuChamId = $phieuCham->id;
            \Log::info('PhieuCham ID: ' . $phieuChamId);

            // Xóa điểm cũ
            DB::table('diem_sinh_vien')->where('phieu_cham_id', $phieuChamId)->delete();
            \Log::info('Old diem deleted');

            // Lưu điểm từng sinh viên
            foreach ($validated['sinh_vien'] as $mssv => $diem) {
                \Log::info('Inserting diem for mssv: ' . $mssv, $diem);

                DB::table('diem_sinh_vien')->insert([
                    'phieu_cham_id' => $phieuChamId,
                    'mssv' => $mssv,
                    'diem_phan_tich' => $diem['diem_phan_tich'],
                    'diem_thiet_ke' => $diem['diem_thiet_ke'],
                    'diem_hien_thuc' => $diem['diem_hien_thuc'],
                    'diem_kiem_tra' => $diem['diem_kiem_tra'],
                    'de_nghi' => $diem['de_nghi'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                \Log::info('Diem inserted successfully for mssv: ' . $mssv);
            }

            DB::commit();
            \Log::info('=== SUCCESS storePhanBien ===');

            return redirect()
                ->route('lecturers.chamdiem.phanbien.index')
                ->with('success', 'Chấm điểm phản biện thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('=== ERROR storePhanBien ===');
            \Log::error('Message: ' . $e->getMessage());
            \Log::error('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            
            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Export Word - Phản biện
     */
    public function exportPhanBien($nhom_id)
    {
        try {
            $magv = session('user')->magv;
            $exporter = new PhieuChamWordExporter();
            $filePath = $exporter->exportPhanBien($nhom_id, $magv);
            
            return response()->download($filePath)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Không thể xuất file Word: ' . $e->getMessage());
        }
    }
}