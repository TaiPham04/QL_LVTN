<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Detai;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LecturerAssignmentsController extends Controller
{
    /**
     * Hiển thị form tạo nhóm
     * 
     * Logic:
     * 1. $availableStudents: Sinh viên được phân công cho GV này + chưa có nhóm
     * 2. $students: Sinh viên được phân công cho GV này (có nhóm hoặc chưa)
     */
    public function form()
    {
        $lecturer = session('user');
        
        // ✅ Sinh viên chưa có nhóm (được phân công cho GV này)
        $availableStudents = DB::table('sinhvien')
            ->join('phancong', 'sinhvien.mssv', '=', 'phancong.mssv')
            ->where('phancong.magv', $lecturer->magv)  // ← Lọc theo GV hiện tại
            ->whereNotExists(function($query) {
                $query->select(DB::raw(1))
                    ->from('detai')
                    ->whereColumn('detai.mssv', 'sinhvien.mssv');
            })  // ← Chưa có nhóm
            ->select('sinhvien.*')
            ->orderBy('sinhvien.hoten')
            ->get();

        // ✅ Sinh viên được phân công cho GV này (dù có nhóm hay chưa)
        $students = DB::table('sinhvien')
            ->join('phancong', 'sinhvien.mssv', '=', 'phancong.mssv')
            ->leftJoin('detai', 'sinhvien.mssv', '=', 'detai.mssv')
            ->leftJoin('nhom', 'detai.nhom_id', '=', 'nhom.id')
            ->where('phancong.magv', $lecturer->magv)  // ← LỌC THEO GV HIỆN TẠI
            ->select(
                'sinhvien.mssv',
                'sinhvien.hoten',
                'sinhvien.lop',
                'nhom.tennhom as nhom',
                'nhom.tendt',
                'nhom.trangthai'
            )
            ->orderBy('sinhvien.hoten')
            ->get();

        return view('lecturers.assignments.form', compact('availableStudents', 'students'));
    }

    /**
     * 🆕 Lưu nhóm mới (MÃ NHÓM TỰ ĐỘNG)
     * 
     * Quy trình:
     * 1. Validate input (tên đề tài, trạng thái, sinh viên)
     * 2. Tự động tạo mã nhóm từ magv + TH + 4 số cuối MSSV
     * 3. Kiểm tra mã nhóm có trùng không
     * 4. Thêm từng sinh viên vào nhóm
     */
    public function store(Request $request)
    {
        $request->validate([
            'tendt' => 'required|string|max:255',
            'trangthai' => 'required|in:chua_bat_dau,dang_thuc_hien,hoan_thanh,dinh_chi',
            'sinhvien' => 'required|array|min:1|max:2',
            'sinhvien.*' => 'required|exists:sinhvien,mssv',
        ], [
            'sinhvien.min' => 'Phải chọn ít nhất 1 sinh viên',
            'sinhvien.max' => 'Tối đa 2 sinh viên mỗi nhóm',
            'tendt.required' => 'Tên đề tài không được để trống',
        ]);

        try {
            $lecturer = session('user');
            $sinhvienIds = $request->input('sinhvien');
            
            // ✅ BƯỚC 1: TỰ ĐỘNG SINH MÃ NHÓM
            $nhomCode = Detai::generateNhomCode($lecturer->magv, $sinhvienIds);
            
            // ✅ BƯỚC 2: KIỂM TRA MÃ NHÓM ĐÃ TỒN TẠI CHƯA
            $nhom = DB::table('nhom')->where('tennhom', $nhomCode)->first();
            if ($nhom) {
                return back()->with('error', 'Mã nhóm ' . $nhomCode . ' đã tồn tại! Vui lòng kiểm tra lại.');
            }

            // ✅ BƯỚC 3: TẠO NHÓM MỚI (lưu tendt + trangthai vào nhom)
            $nhom_id = DB::table('nhom')->insertGetId([
                'tennhom' => $nhomCode,
                'tendt' => $request->input('tendt'),
                'trangthai' => $request->input('trangthai'),
                'magv' => $lecturer->magv,
                'created_at' => now(),
            ]);

            // ✅ BƯỚC 4: THÊM TỪNG SINH VIÊN VÀO BẢNG DETAI (chỉ lưu tham chiếu)
            foreach ($sinhvienIds as $mssv) {
                Detai::create([
                    'mssv' => $mssv,
                    'magv' => $lecturer->magv,
                    'nhom_id' => $nhom_id,
                ]);
            }

            return redirect()->route('lecturers.assignments.form')
                ->with('success', 'Tạo nhóm ' . $nhomCode . ' thành công!');

        } catch (\Exception $e) {
            Log::error('Error creating group: ' . $e->getMessage());
            return back()->with('error', 'Lỗi khi tạo nhóm: ' . $e->getMessage());
        }
    }

    /**
     * Hiển thị chi tiết nhóm
     */
    public function show($nhom)
    {
        $lecturer = session('user');
        
        $deTai = DB::table('detai')
            ->join('nhom', 'detai.nhom_id', '=', 'nhom.id')
            ->where('nhom.tennhom', $nhom)
            ->where('detai.magv', $lecturer->magv)
            ->first();

        if (!$deTai) {
            return redirect()->route('lecturers.assignments.form')
                ->with('error', 'Nhóm không tồn tại hoặc bạn không có quyền truy cập');
        }

        $students = Detai::getSinhVienByNhomId($deTai->nhom_id);

        return view('lecturers.assignments.show', compact('deTai', 'students', 'nhom'));
    }

    /**
     * Form sửa nhóm
     */
    public function edit($nhom)
    {
        $lecturer = session('user');
        
        $deTai = DB::table('detai')
            ->join('nhom', 'detai.nhom_id', '=', 'nhom.id')
            ->where('nhom.tennhom', $nhom)
            ->where('detai.magv', $lecturer->magv)
            ->first();

        if (!$deTai) {
            return redirect()->route('lecturers.assignments.form')
                ->with('error', 'Nhóm không tồn tại');
        }

        $students = Detai::getSinhVienByNhomId($deTai->nhom_id);
        $availableStudents = DB::table('sinhvien')
            ->join('phancong', 'sinhvien.mssv', '=', 'phancong.mssv')
            ->where('phancong.magv', $lecturer->magv)
            ->whereNotIn('sinhvien.mssv', $students->pluck('mssv')->toArray())
            ->select('sinhvien.*')
            ->get();

        return view('lecturers.assignments.edit', compact('deTai', 'students', 'availableStudents', 'nhom'));
    }

    /**
     * Cập nhật nhóm (chỉ tên đề tài + trạng thái ở bảng nhom, MÃ NHÓM KHÔNG ĐƯỢC SỬA)
     */
    public function update(Request $request, $nhom)
    {
        $request->validate([
            'tendt' => 'required|string|max:255',
            'trangthai' => 'required|in:chua_bat_dau,dang_thuc_hien,hoan_thanh,dinh_chi',
        ]);

        try {
            $lecturer = session('user');
            
            DB::table('nhom')
                ->where('tennhom', $nhom)
                ->update([
                    'tendt' => $request->input('tendt'),
                    'trangthai' => $request->input('trangthai'),
                ]);

            return redirect()->route('lecturers.assignments.form')
                ->with('success', 'Cập nhật nhóm ' . $nhom . ' thành công!');

        } catch (\Exception $e) {
            Log::error('Error updating group: ' . $e->getMessage());
            return back()->with('error', 'Lỗi khi cập nhật: ' . $e->getMessage());
        }
    }

    /**
     * Xóa nhóm (xóa tất cả sinh viên trong nhóm)
     */
    public function destroy($nhom)
    {
        try {
            $lecturer = session('user');
            
            DB::table('detai')
                ->join('nhom', 'detai.nhom_id', '=', 'nhom.id')
                ->where('nhom.tennhom', $nhom)
                ->where('detai.magv', $lecturer->magv)
                ->delete();

            return redirect()->route('lecturers.assignments.form')
                ->with('success', 'Xóa nhóm thành công!');

        } catch (\Exception $e) {
            Log::error('Error deleting group: ' . $e->getMessage());
            return back()->with('error', 'Lỗi khi xóa nhóm');
        }
    }

    /**
     * Cập nhật trạng thái nhiều nhóm
     */
    public function updateAllStatus(Request $request)
    {
        try {
            $lecturer = session('user');
            $changes = $request->input('trangthai', []);

            foreach ($changes as $change) {
                DB::table('nhom')
                    ->where('tennhom', $change['nhom'])
                    ->update(['trangthai' => $change['trangthai']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật trạng thái 1 nhóm
     */
    public function updateStatus(Request $request, $nhom)
    {
        try {
            $request->validate([
                'trangthai' => 'required|in:chua_bat_dau,dang_thuc_hien,hoan_thanh,dinh_chi',
            ]);

            $lecturer = session('user');

            DB::table('nhom')
                ->where('tennhom', $nhom)
                ->update(['trangthai' => $request->input('trangthai')]);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}