<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Detai;
use App\Services\DiemGiuaKyExport;
use Illuminate\Support\Facades\DB;

class DiemGiuaKyController extends Controller
{
    /**
     * Hiển thị danh sách sinh viên để chấm điểm giữa kỳ
     */
    public function index()
    {
        $magv = session('user')->magv;

        // Lấy danh sách sinh viên được phân công
        $students = DB::table('detai as dt')
            ->join('sinhvien as s', 'dt.mssv', '=', 's.mssv')
            ->leftJoin('nhom as n', 'dt.nhom_id', '=', 'n.id')
            ->where('dt.magv', $magv)
            ->whereNotNull('dt.mssv')
            ->select(
                's.mssv',
                's.hoten as tensv',
                's.lop',
                'n.tendt',  // ← LẤY TỬ nhom TABLE
                'dt.nhom_id',
                'n.tennhom as nhom'
            )
            ->orderBy('n.tennhom')
            ->orderBy('s.mssv')
            ->get();

        // Lấy điểm đã chấm (nếu có)
        $scores = DB::table('diem_giuaky')
            ->where('magv_cham', $magv)
            ->get()
            ->keyBy('mssv');

        // Nhóm sinh viên theo nhóm đề tài
        $groupedStudents = [];
        
        foreach ($students as $student) {
            $key = $student->nhom_id ?? 'null';
            
            if (!isset($groupedStudents[$key])) {
                $groupedStudents[$key] = [
                    'nhom' => $student->nhom ?? 'Chưa có',
                    'tendt' => $student->tendt ?? '',
                    'students' => []
                ];
            }

            // Lấy điểm cũ (nếu có)
            $oldScore = $scores[$student->mssv] ?? null;

            $groupedStudents[$key]['students'][] = [
                'mssv' => $student->mssv,
                'tensv' => $student->tensv,
                'diem' => $oldScore ? $oldScore->diem : '',
                'ketqua' => $oldScore ? $oldScore->ketqua : '',
                'nhanxet' => $oldScore ? $oldScore->nhanxet : ''
            ];
        }

        return view('lecturers.diem-giuaky.index', compact('groupedStudents'));
    }

    /**
     * Lưu điểm giữa kỳ
     */
    public function store(Request $request)
    {
        $magv = session('user')->magv;

        $validated = $request->validate([
            'mssv' => 'required|array',
            'diem' => 'required|array',
            'ketqua' => 'required|array',
            'nhanxet' => 'nullable|array'
        ]);

        DB::beginTransaction();

        try {
            foreach ($validated['mssv'] as $mssv) {
                $diem = $validated['diem'][$mssv] ?? null;

                if ($diem !== null && $diem !== '') {
                    // ✅ FIX: Bỏ 'updated_at' - cột này không tồn tại trong bảng
                    DB::table('diem_giuaky')->updateOrInsert(
                        [
                            'mssv' => $mssv,
                            'magv_cham' => $magv
                        ],
                        [
                            'diem' => $diem,
                            'ketqua' => $validated['ketqua'][$mssv] ?? 'chua_danh_gia',
                            'nhanxet' => $validated['nhanxet'][$mssv] ?? '',
                            'created_at' => now()
                        ]
                    );
                }
            }

            DB::commit();

            return redirect()->route('lecturers.diemgiuaky.index')
                ->with('success', 'Lưu điểm giữa kỳ thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi lưu điểm: ' . $e->getMessage());
        }
    }

    /**
     * Export Excel
     */
    public function export()
    {
        try {
            $magv = session('user')->magv;
            $exporter = new DiemGiuaKyExport();
            $filePath = $exporter->export($magv);
            
            return response()->download($filePath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}