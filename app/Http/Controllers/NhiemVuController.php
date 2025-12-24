<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\NhiemVuWordExporter;

class NhiemVuController extends Controller
{
    /**
     * Danh sách nhiệm vụ của giảng viên
     */
    public function index()
    {
        $user = session('user');
        
        if (!$user || !isset($user->magv)) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập trước.');
        }

        $magv = $user->magv;

        // ✅ FIX: Lấy danh sách nhóm từ detai theo magv
        $groups = DB::table('detai as d')
            ->join('nhom as n', 'd.nhom_id', '=', 'n.id')
            ->leftJoin('nhiemvu as nv', function($join) use ($magv) {
                $join->on('n.id', '=', 'nv.nhom_id')
                     ->where('nv.magv', '=', $magv);
            })
            ->where('d.magv', $magv)
            ->whereNotNull('d.nhom_id')
            ->select(
                'n.id as nhom_id',
                'n.tennhom as nhom',
                'n.tendt as tenduan',
                DB::raw('COUNT(DISTINCT d.mssv) as so_sv'),
                DB::raw("CASE WHEN nv.id IS NOT NULL THEN 'Đã điền' ELSE 'Chưa điền' END as trangthai"),
                'nv.id as nhiemvu_id'
            )
            ->groupBy('n.id', 'n.tennhom', 'n.tendt', 'nv.id')
            ->get();

        return view('lecturers.nhiemvu.index', compact('groups'));
    }

    /**
     * Form điền nhiệm vụ
     */
    public function create($nhom_id)
    {
        $user = session('user');
        
        if (!$user || !isset($user->magv)) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập trước.');
        }

        $magv = $user->magv;

        // Lấy thông tin nhóm
        $group = DB::table('nhom')
            ->where('id', $nhom_id)
            ->first();

        if (!$group) {
            return redirect()->route('lecturers.nhiemvu.index')
                ->with('error', 'Không tìm thấy nhóm!');
        }

        // ✅ FIX: Kiểm tra giảng viên có hướng dẫn nhóm này không
        $check = DB::table('detai')
            ->where('nhom_id', $nhom_id)
            ->where('magv', $magv)
            ->exists();

        if (!$check) {
            return redirect()->route('lecturers.nhiemvu.index')
                ->with('error', 'Bạn không được phép cập nhật nhóm này!');
        }

        // ✅ FIX: Lấy danh sách sinh viên trong nhóm từ detai
        $students = DB::table('detai as d')
            ->join('sinhvien as sv', 'd.mssv', '=', 'sv.mssv')
            ->where('d.nhom_id', $nhom_id)
            ->where('d.magv', $magv)
            ->select('sv.mssv', 'sv.hoten', 'sv.lop')
            ->distinct()
            ->get();

        // Kiểm tra đã có nhiệm vụ chưa
        $nhiemvu = DB::table('nhiemvu')
            ->where('nhom_id', $nhom_id)
            ->where('magv', $magv)
            ->first();

        // ✅ FIX: Lấy danh sách giảng viên
        $giangviens = DB::table('giangvien')
            ->select('magv', 'hoten')
            ->orderBy('hoten')
            ->get();

        // ✅ FIX: Lấy thông tin giảng viên từ bảng giangvien
        $lecturerInfo = DB::table('giangvien')
            ->where('magv', $magv)
            ->first();

        $lecturer = (object)[
            'magv' => $magv,
            'hoten' => $lecturerInfo ? ucwords(strtolower($lecturerInfo->hoten)) : 'Giảng Viên'
        ];

        return view('lecturers.nhiemvu.form', compact('group', 'students', 'nhiemvu', 'lecturer', 'nhom_id', 'giangviens'));
    }

    /**
     * Lưu nhiệm vụ
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sv1_mssv' => 'required',
            'sv2_mssv' => 'nullable',
            'dau_de_bai_thi' => 'required',
        ]);

        $nhom_id = $request->nhom_id;
        $magv = session('user')->magv;

        // ✅ Chuẩn bị dữ liệu - Lưu mã giảng viên thay vì tên
        $data = [
            'nhom_id' => $nhom_id,
            'magv' => $magv,
            'sv1_hoten' => $request->sv1_hoten,
            'sv1_mssv' => $request->sv1_mssv,
            'sv1_lop' => $request->sv1_lop,
            'sv2_hoten' => $request->sv2_hoten ?: null,
            'sv2_mssv' => $request->sv2_mssv ?: null,
            'sv2_lop' => $request->sv2_lop ?: null,
            'dau_de_bai_thi' => $request->dau_de_bai_thi,
            'nhiem_vu_noi_dung' => $request->nhiem_vu_noi_dung,
            'ho_so_tai_lieu' => $request->ho_so_tai_lieu,
            'ngay_giao' => $request->ngay_giao,
            'ngay_hoanthanh' => $request->ngay_hoanthanh,
            'nguoi_huongdan_1' => $request->magv_huongdan_1 ?? null,  // ✅ Lưu mã
            'phan_huongdan_1' => $request->phan_huongdan_1,
            'nguoi_huongdan_2' => $request->magv_huongdan_2 ?? null,  // ✅ Lưu mã
            'phan_huongdan_2' => $request->phan_huongdan_2 ?: null,
            'trangthai' => $request->trangthai ?? 'Đã điền',
            'updated_at' => now(),
        ];

        \Log::info('Dữ liệu sẽ lưu: ' . json_encode($data));

        try {
            DB::table('nhiemvu')->updateOrInsert(
                ['nhom_id' => $nhom_id, 'magv' => $magv],
                $data
            );

            \Log::info('Kết quả lưu: Thành công');

            return redirect()->route('lecturers.nhiemvu.index')
                ->with('success', 'Lưu nhiệm vụ thành công!');

        } catch (\Exception $e) {
            \Log::error('Lỗi khi lưu nhiệm vụ: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            return redirect()->back()
                ->with('error', 'Lỗi: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Xuất Word
     */
    public function export($nhom_id)
    {
        $user = session('user');
        
        if (!$user || !isset($user->magv)) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập trước.');
        }

        $magv = $user->magv;

        // ✅ FIX: Kiểm tra đã điền nhiệm vụ chưa
        $nhiemvu = DB::table('nhiemvu')
            ->where('nhom_id', $nhom_id)
            ->where('magv', $magv)
            ->first();

        if (!$nhiemvu) {
            return redirect()->route('lecturers.nhiemvu.index')
                ->with('error', 'Vui lòng điền thông tin nhiệm vụ trước khi xuất Word!');
        }

        // Lấy thông tin nhóm
        $group = DB::table('nhom')
            ->where('id', $nhom_id)
            ->first();

        try {
            $exporter = new NhiemVuWordExporter();
            $filePath = $exporter->export($nhom_id, $magv);
            
            return response()->download($filePath)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            \Log::error('Lỗi xuất Word: ' . $e->getMessage());
            return redirect()->route('lecturers.nhiemvu.index')
                ->with('error', 'Không thể xuất file Word: ' . $e->getMessage());
        }
    }

    /**
     * Xóa nhiệm vụ
     */
    public function destroy($nhom_id)
    {
        $user = session('user');
        
        if (!$user || !isset($user->magv)) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập trước.');
        }

        $magv = $user->magv;

        try {
            $deleted = DB::table('nhiemvu')
                ->where('nhom_id', $nhom_id)
                ->where('magv', $magv)
                ->delete();

            if ($deleted) {
                return redirect()->route('lecturers.nhiemvu.index')
                    ->with('success', 'Xóa nhiệm vụ thành công!');
            } else {
                return redirect()->route('lecturers.nhiemvu.index')
                    ->with('error', 'Không tìm thấy nhiệm vụ để xóa!');
            }

        } catch (\Exception $e) {
            return redirect()->route('lecturers.nhiemvu.index')
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}