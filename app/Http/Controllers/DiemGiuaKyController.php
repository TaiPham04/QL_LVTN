<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DiemGiuaKyExport;
use Maatwebsite\Excel\Facades\Excel;

class DiemGiuaKyController extends Controller
{
    // Hiển thị danh sách sinh viên để chấm điểm
    public function index()
    {
        $magv = session('user')->magv ?? null;

        if (!$magv) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập');
        }

        // Lấy danh sách sinh viên của giảng viên này
        $students = DB::table('detai as dt')
            ->leftJoin('sinhvien as sv', 'dt.mssv', '=', 'sv.mssv')
            ->leftJoin('diem_giuaky as dg', 'dt.mssv', '=', 'dg.mssv')
            ->where('dt.magv', $magv)
            ->select(
                'dt.mssv',
                'sv.hoten as tensv',
                'dt.nhom',
                'dt.tendt',
                'dg.diem',
                'dg.ketqua',
                'dg.nhanxet'
            )
            ->orderBy('dt.nhom')
            ->orderBy('sv.hoten')
            ->get();

        // Group theo nhóm
        $groupedStudents = $students->groupBy('nhom')->map(function ($items, $nhom) {
            $first = $items->first();
            
            return [
                'nhom' => $nhom ?? 'Chưa có',
                'tendt' => $first->tendt,
                'students' => $items->map(function ($student) {
                    // Tính trạng thái
                    if ($student->ketqua === 'duoc_tieptuc') {
                        $trangthai = 'Được tiếp tục';
                        $badge_class = 'bg-success';
                    } elseif ($student->ketqua === 'khong_duoc_tieptuc') {
                        $trangthai = 'Không được tiếp tục';
                        $badge_class = 'bg-danger';
                    } else {
                        $trangthai = 'Chưa đánh giá';
                        $badge_class = 'bg-secondary';
                    }
                    
                    // Chuyển thành array
                    return [
                        'mssv' => $student->mssv,
                        'tensv' => $student->tensv,
                        'diem' => $student->diem,
                        'ketqua' => $student->ketqua,
                        'nhanxet' => $student->nhanxet,
                        'trangthai' => $trangthai,
                        'badge_class' => $badge_class,
                    ];
                })->toArray()
            ];
        })->values();

        return view('lecturers.diem-giuaky.index', compact('groupedStudents'));
    }

    // Lưu điểm và kết quả đánh giá
    public function store(Request $request)
    {
        $magv = session('user')->magv ?? null;

        if (!$magv) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập');
        }

        $request->validate([
            'mssv' => 'required|array|min:1',
            'diem.*' => 'nullable|numeric|min:0|max:10',
            'ketqua.*' => 'nullable|in:duoc_tieptuc,khong_duoc_tieptuc,chua_danh_gia',
            'nhanxet.*' => 'nullable|string|max:500',
        ], [
            'diem.*.numeric' => 'Điểm phải là số',
            'diem.*.min' => 'Điểm phải từ 0 đến 10',
            'diem.*.max' => 'Điểm phải từ 0 đến 10',
        ]);

        $count = 0;

        foreach ($request->mssv as $mssv) {
            $diem = $request->diem[$mssv] ?? null;
            $ketqua = $request->ketqua[$mssv] ?? 'chua_danh_gia';
            $nhanxet = $request->nhanxet[$mssv] ?? null;

            // Chỉ lưu nếu có điểm hoặc có kết quả đánh giá
            if (($diem !== null && $diem !== '') || $ketqua !== 'chua_danh_gia') {
                DB::table('diem_giuaky')->updateOrInsert(
                    ['mssv' => $mssv],
                    [
                        'diem' => $diem !== '' ? $diem : null,
                        'ketqua' => $ketqua,
                        'nhanxet' => $nhanxet,
                        'magv_cham' => $magv,
                    ]
                );
                $count++;
            }
        }

        return redirect()->back()->with('success', "Đã lưu đánh giá cho {$count} sinh viên!");
    }

    

    public function export()
    {
        $user = session('user');
        $lecturer = DB::table('giangvien')->where('email', $user->email)->first();
        
        if (!$lecturer) {
            return back()->with('error', 'Không tìm thấy thông tin giảng viên!');
        }

        try {
            $exporter = new DiemGiuaKyExport();
            $filePath = $exporter->export($lecturer->magv);
            
            return response()->download($filePath)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Không thể xuất file Excel: ' . $e->getMessage());
        }
    }
}