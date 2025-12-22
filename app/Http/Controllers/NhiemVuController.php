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
        
        if (!$user) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập trước.');
        }

        $lecturer = DB::table('giangvien')->where('email', $user->email)->first();
        
        if (!$lecturer) {
            return back()->with('error', 'Không tìm thấy thông tin giảng viên!');
        }

        // Lấy danh sách nhóm và thông tin nhiệm vụ
        $groups = DB::table('detai as d')
            ->join('nhom as n', 'd.nhom_id', '=', 'n.id')
            ->leftJoin('nhiemvu as nv', function($join) use ($lecturer) {
                $join->on('n.id', '=', 'nv.nhom_id')
                     ->where('nv.magv', '=', $lecturer->magv);
            })
            ->where('d.magv', $lecturer->magv)
            ->whereNotNull('d.nhom_id')
            ->select(
                'n.id as nhom_id',
                'n.tennhom as nhom',
                'n.tendt as tenduan',
                DB::raw('COUNT(DISTINCT d.mssv) as so_sv'),
                'nv.trangthai',
                'nv.id as nhiemvu_id'
            )
            ->groupBy('n.id', 'n.tennhom', 'n.tendt', 'nv.trangthai', 'nv.id')
            ->get();

        return view('lecturers.nhiemvu.index', compact('groups'));
    }

    /**
     * Form điền nhiệm vụ
     */
    public function create($nhom_id)
    {
        $user = session('user');
        
        if (!$user) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập trước.');
        }

        $lecturer = DB::table('giangvien')->where('email', $user->email)->first();
        
        if (!$lecturer) {
            return back()->with('error', 'Không tìm thấy thông tin giảng viên!');
        }

        // Lấy thông tin nhóm
        $group = DB::table('nhom')
            ->where('id', $nhom_id)
            ->first();

        if (!$group) {
            return back()->with('error', 'Không tìm thấy nhóm!');
        }

        // Kiểm tra giảng viên có hướng dẫn nhóm này không
        $check = DB::table('detai')
            ->where('nhom_id', $nhom_id)
            ->where('magv', $lecturer->magv)
            ->exists();

        if (!$check) {
            return back()->with('error', 'Bạn không được phép cập nhật nhóm này!');
        }

        // Lấy danh sách sinh viên trong nhóm
        $students = DB::table('detai')
            ->join('sinhvien', 'detai.mssv', '=', 'sinhvien.mssv')
            ->where('detai.nhom_id', $nhom_id)
            ->where('detai.magv', $lecturer->magv)
            ->select('sinhvien.mssv', 'sinhvien.hoten', 'sinhvien.lop')
            ->get();

        // Kiểm tra đã có nhiệm vụ chưa
        $nhiemvu = DB::table('nhiemvu')
            ->where('nhom_id', $nhom_id)
            ->where('magv', $lecturer->magv)
            ->first();

        return view('lecturers.nhiemvu.form', compact('group', 'students', 'nhiemvu', 'lecturer', 'nhom_id'));
    }

    /**
     * Lưu nhiệm vụ
     */
    public function store(Request $request)
    {
        $request->validate([
            'nhom_id' => 'required',
            'sv1_hoten' => 'required',
            'sv1_mssv' => 'required',
            'sv1_lop' => 'required',
            'dau_de_bai_thi' => 'required',
            'nhiem_vu_noi_dung' => 'required',
            'ngay_giao' => 'required|date',
            'ngay_hoanthanh' => 'required|date|after:ngay_giao',
            'nguoi_huongdan_1' => 'required',
            'phan_huongdan_1' => 'required'
        ], [
            'sv1_hoten.required' => 'Không tìm thấy thông tin sinh viên 1',
            'sv1_mssv.required' => 'Không tìm thấy MSSV sinh viên 1',
            'sv1_lop.required' => 'Không tìm thấy lớp sinh viên 1',
        ]);

        $user = session('user');
        $lecturer = DB::table('giangvien')->where('email', $user->email)->first();

        if (!$lecturer) {
            return back()->with('error', 'Không tìm thấy thông tin giảng viên!');
        }

        try {
            DB::beginTransaction();

            // Lấy dữ liệu sinh viên TRỰC TIẾP TỪ REQUEST
            $data = [
                // Sinh viên 1 (bắt buộc)
                'sv1_hoten' => $request->sv1_hoten,
                'sv1_mssv' => $request->sv1_mssv,
                'sv1_lop' => $request->sv1_lop,
                
                // Sinh viên 2 (tùy chọn)
                'sv2_hoten' => $request->sv2_hoten ?? '',
                'sv2_mssv' => $request->sv2_mssv ?? '',
                'sv2_lop' => $request->sv2_lop ?? '',
                
                // Thông tin đề tài
                'dau_de_bai_thi' => $request->dau_de_bai_thi,
                'nhiem_vu_noi_dung' => $request->nhiem_vu_noi_dung,
                'ho_so_tai_lieu' => $request->ho_so_tai_lieu,
                'ngay_giao' => $request->ngay_giao,
                'ngay_hoanthanh' => $request->ngay_hoanthanh,
                'nguoi_huongdan_1' => $request->nguoi_huongdan_1,
                'phan_huongdan_1' => $request->phan_huongdan_1,
                'nguoi_huongdan_2' => $request->nguoi_huongdan_2,
                'phan_huongdan_2' => $request->phan_huongdan_2,
                'trangthai' => 'Đã điền',
                'updated_at' => now()
            ];

            // DEBUG: In dữ liệu sẽ lưu
            \Log::info('Dữ liệu sẽ lưu: ' . json_encode($data));

            // Lưu hoặc cập nhật nhiệm vụ
            $result = DB::table('nhiemvu')->updateOrInsert(
                [
                    'nhom_id' => $request->nhom_id,
                    'magv' => $lecturer->magv
                ],
                $data
            );

            \Log::info('Kết quả lưu: ' . ($result ? 'Thành công' : 'Thất bại'));

            DB::commit();
            return redirect()->route('lecturers.nhiemvu.index')
                ->with('success', 'Lưu nhiệm vụ thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Lỗi khi lưu nhiệm vụ: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
    
    /**
     * Xuất Word
     */
    public function exportWord($nhom_id)
    {
        $user = session('user');
        
        if (!$user) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập trước.');
        }

        $lecturer = DB::table('giangvien')->where('email', $user->email)->first();

        if (!$lecturer) {
            return back()->with('error', 'Không tìm thấy thông tin giảng viên!');
        }

        // Kiểm tra đã điền nhiệm vụ chưa
        $nhiemvu = DB::table('nhiemvu')
            ->where('nhom_id', $nhom_id)
            ->where('magv', $lecturer->magv)
            ->first();

        if (!$nhiemvu) {
            return back()->with('error', 'Vui lòng điền thông tin nhiệm vụ trước khi xuất Word!');
        }

        if ($nhiemvu->trangthai !== 'Đã điền') {
            return back()->with('error', 'Nhiệm vụ chưa được hoàn thành!');
        }

        try {
            $exporter = new NhiemVuWordExporter();
            $filePath = $exporter->export($nhom_id, $lecturer->magv);
            
            return response()->download($filePath)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Không thể xuất file Word: ' . $e->getMessage());
        }
    }

    /**
     * Xem chi tiết nhiệm vụ (optional)
     */
    public function show($nhom_id)
    {
        $user = session('user');
        $lecturer = DB::table('giangvien')->where('email', $user->email)->first();

        if (!$lecturer) {
            return back()->with('error', 'Không tìm thấy thông tin giảng viên!');
        }

        $nhiemvu = DB::table('nhiemvu')
            ->where('nhom_id', $nhom_id)
            ->where('magv', $lecturer->magv)
            ->first();

        if (!$nhiemvu) {
            return back()->with('error', 'Không tìm thấy nhiệm vụ!');
        }

        $students = json_decode($nhiemvu->sinhvien_info, true);

        return view('lecturers.nhiemvu.show', compact('nhiemvu', 'students'));
    }

    /**
     * Xóa nhiệm vụ (optional)
     */
    public function destroy($nhom_id)
    {
        $user = session('user');
        $lecturer = DB::table('giangvien')->where('email', $user->email)->first();

        if (!$lecturer) {
            return back()->with('error', 'Không tìm thấy thông tin giảng viên!');
        }

        try {
            $deleted = DB::table('nhiemvu')
                ->where('nhom_id', $nhom_id)
                ->where('magv', $lecturer->magv)
                ->delete();

            if ($deleted) {
                return redirect()->route('lecturers.nhiemvu.index')
                    ->with('success', 'Xóa nhiệm vụ thành công!');
            } else {
                return back()->with('error', 'Không tìm thấy nhiệm vụ để xóa!');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}