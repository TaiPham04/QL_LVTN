<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAssignmentController extends Controller
{
    /**
     * 🆕 Hiển thị danh sách phân công (CÓ KIỂM TRA ĐỀ TÀI)
     */
    public function index(Request $request)
{
    // 🔍 Lọc theo trạng thái phân công
    $status = $request->input('status');
    
    if ($status === 'assigned') {
        // ✅ INNERjoin phancong (chỉ lấy SV đã phân công)
        $query = DB::table('sinhvien')
            ->join('phancong', 'sinhvien.mssv', '=', 'phancong.mssv')
            ->leftJoin('giangvien', 'phancong.magv', '=', 'giangvien.magv')
            ->leftJoin('detai', 'sinhvien.mssv', '=', 'detai.mssv')
            ->select(
                'sinhvien.mssv',
                'sinhvien.hoten',
                'sinhvien.lop',
                'phancong.magv',
                'giangvien.hoten as tengiangvien',
                DB::raw('IF(detai.madt IS NOT NULL, 1, 0) as co_de_tai')
            );
    } elseif ($status === 'unassigned') {
        // ✅ Chỉ query sinhvien + detai (không join phancong)
        $query = DB::table('sinhvien')
            ->whereNotExists(function($subquery) {
                $subquery->select(DB::raw(1))
                    ->from('phancong')
                    ->whereColumn('phancong.mssv', 'sinhvien.mssv');
            })
            ->leftJoin('detai', 'sinhvien.mssv', '=', 'detai.mssv')
            ->select(
                'sinhvien.mssv',
                'sinhvien.hoten',
                'sinhvien.lop',
                DB::raw('NULL as magv'),
                DB::raw('NULL as tengiangvien'),
                DB::raw('IF(detai.madt IS NOT NULL, 1, 0) as co_de_tai')
            );
    } else {
        // ✅ FIX: Tách thành 2 query sau đó merge
        // Query 1: SV có phân công
        $assignedSVs = DB::table('sinhvien')
            ->join('phancong', 'sinhvien.mssv', '=', 'phancong.mssv')
            ->leftJoin('giangvien', 'phancong.magv', '=', 'giangvien.magv')
            ->leftJoin('detai', 'sinhvien.mssv', '=', 'detai.mssv')
            ->select(
                'sinhvien.mssv',
                'sinhvien.hoten',
                'sinhvien.lop',
                'phancong.magv',
                'giangvien.hoten as tengiangvien',
                DB::raw('IF(detai.madt IS NOT NULL, 1, 0) as co_de_tai')
            );

        // Query 2: SV chưa phân công
        $unassignedSVs = DB::table('sinhvien')
            ->whereNotExists(function($subquery) {
                $subquery->select(DB::raw(1))
                    ->from('phancong')
                    ->whereColumn('phancong.mssv', 'sinhvien.mssv');
            })
            ->leftJoin('detai', 'sinhvien.mssv', '=', 'detai.mssv')
            ->select(
                'sinhvien.mssv',
                'sinhvien.hoten',
                'sinhvien.lop',
                DB::raw('NULL as magv'),
                DB::raw('NULL as tengiangvien'),
                DB::raw('IF(detai.madt IS NOT NULL, 1, 0) as co_de_tai')
            );

        // ✅ Union 2 query
        $query = $assignedSVs->unionAll($unassignedSVs);
    }

    // 🔍 Tìm kiếm
    if ($request->has('search') && !empty($request->input('search'))) {
        $search = $request->input('search');
        $query->where(function($q) use ($search) {
            $q->where('sinhvien.mssv', 'like', '%' . $search . '%')
              ->orWhere('sinhvien.hoten', 'like', '%' . $search . '%');
        });
    }

    // 🔍 Lọc theo giảng viên (chỉ khi status là 'assigned' hoặc không có status)
    if ($request->has('magv') && !empty($request->input('magv')) && $status !== 'unassigned') {
        $query->where('phancong.magv', $request->input('magv'));
    }

    // ✅ Wrap query vào subquery để avoid DISTINCT + ORDER BY issue
    if ($status === null) {
        $assignments = DB::table(DB::raw("({$query->toSql()}) as sub"))
            ->mergeBindings($query)
            ->orderBy('hoten')
            ->get();
    } else {
        $assignments = $query->distinct()->orderBy('sinhvien.hoten')->get();
    }

    $lecturers = DB::table('giangvien')
        ->select('magv', 'hoten')
        ->orderBy('hoten')
        ->get();

    return view('admin.assignments.index', compact('assignments', 'lecturers'));
}

    /**
     * Form thêm/sửa phân công (PHÂN CÔNG NHIỀU SINH VIÊN)
     */
    public function form(Request $request)
    {
        // Lấy danh sách giảng viên
        $lecturers = DB::table('giangvien')
            ->select('magv', 'hoten')
            ->orderBy('hoten')
            ->get();
        
        // 🆕 Lấy danh sách sinh viên chưa được phân công
        $students = DB::table('sinhvien')
            ->whereNotExists(function($subquery) {
                $subquery->select(DB::raw(1))
                    ->from('phancong')
                    ->whereColumn('phancong.mssv', 'sinhvien.mssv');
            })
            ->orderBy('lop', 'asc')
            ->orderBy('hoten', 'asc')
            ->get();

        return view('admin.assignments.form', compact('lecturers', 'students'));
    }

    /**
     * Lưu phân công (NHIỀU SINH VIÊN CÙNG LÚC)
     */
    public function store(Request $request)
    {
        $request->validate([
            'mssv' => 'required|array',
            'mssv.*' => 'exists:sinhvien,mssv',
            'magv' => 'required|exists:giangvien,magv',
        ], [
            'mssv.required' => 'Vui lòng chọn ít nhất 1 sinh viên',
            'magv.required' => 'Vui lòng chọn giảng viên',
        ]);

        try {
            $mssvList = $request->input('mssv');
            $magv = $request->input('magv');
            $successCount = 0;
            $skipCount = 0;

            foreach ($mssvList as $mssv) {
                // Kiểm tra xem phân công đã tồn tại chưa
                $existing = DB::table('phancong')
                    ->where('mssv', $mssv)
                    ->exists();

                if (!$existing) {
                    // Tạo phân công mới
                    DB::table('phancong')->insert([
                        'mssv' => $mssv,
                        'magv' => $magv,
                        'tg_phancong' => now(),
                    ]);
                    $successCount++;
                } else {
                    $skipCount++;
                }
            }

            $message = "Phân công thành công: $successCount sinh viên";
            if ($skipCount > 0) {
                $message .= " ($skipCount sinh viên bỏ qua - đã phân công)";
            }

            return redirect()->route('admin.assignments.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Hủy phân công
     */
    public function destroy($mssv)
    {
        try {
            // ✅ KIỂM TRA: Sinh viên có đề tài chưa?
            $hasTopic = DB::table('detai')
                ->where('mssv', $mssv)
                ->exists();

            if ($hasTopic) {
                return redirect()->route('admin.assignments.index')
                    ->with('error', 'Không thể hủy phân công: Sinh viên đã có đề tài!');
            }

            DB::table('phancong')
                ->where('mssv', $mssv)
                ->delete();

            return redirect()->route('admin.assignments.index')
                ->with('success', 'Hủy phân công thành công!');

        } catch (\Exception $e) {
            return redirect()->route('admin.assignments.index')
                ->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }
}