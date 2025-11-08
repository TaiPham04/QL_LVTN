<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Detai;
use Illuminate\Support\Facades\DB;

class LecturerAssignmentController extends Controller
{
    // Hiển thị danh sách sinh viên và nhóm
    public function index(Request $request)
    {
        $user = session('user');

        if (!$user) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập trước.');
        }

        $lecturer = DB::table('giangvien')->where('email', $user->email)->first();

        if (!$lecturer) {
            return back()->with('error', 'Không tìm thấy thông tin giảng viên!');
        }

        // Lấy danh sách sinh viên được phân công cho giảng viên
        $assignedStudents = DB::table('phancong')
            ->where('magv', $lecturer->magv)
            ->pluck('mssv');

        // Lấy danh sách sinh viên + thông tin đề tài, sắp xếp theo nhóm rồi mssv
        $students = DB::table('sinhvien')
            ->leftJoin('detai', 'sinhvien.mssv', '=', 'detai.mssv')
            ->whereIn('sinhvien.mssv', $assignedStudents)
            ->select(
                'sinhvien.mssv',
                'sinhvien.hoten',
                'detai.nhom',
                'detai.tendt',
                'detai.trangthai'
            )
            ->orderByRaw('CASE WHEN detai.nhom IS NULL THEN 999 ELSE detai.nhom END ASC')
            ->orderBy('sinhvien.mssv')
            ->get();

        return view('assignments.lecturer-form', compact('students'));
    }



    // Lưu nhóm và giảng viên
    public function store(Request $request)
{
    $selectedStudents = $request->input('students');
    $titles = $request->input('titles');
    $statuses = $request->input('statuses');

    if (!$selectedStudents || count($selectedStudents) === 0) {
        return back()->with('error', 'Vui lòng chọn ít nhất 1 sinh viên để phân nhóm.');
    }

    if (count($selectedStudents) > 2) {
        return back()->with('error', 'Mỗi nhóm chỉ được tối đa 2 sinh viên.');
    }

    // 🔹 Lấy giảng viên đang đăng nhập
    $user = session('user');
    $lecturer = \DB::table('giangvien')->where('email', $user->email)->first();

    if (!$lecturer) {
        return back()->with('error', 'Không tìm thấy thông tin giảng viên!');
    }

    // 🔹 Kiểm tra sinh viên đầu tiên đã thuộc nhóm nào chưa
    $firstStudent = $selectedStudents[0];
    $existingRecord = \DB::table('detai')
        ->where('mssv', $firstStudent)
        ->where('magv', $lecturer->magv)
        ->first();

    if ($existingRecord) {
        // 🟩 Nếu sinh viên đã có nhóm, dùng lại nhóm đó
        $groupNumber = $existingRecord->nhom;
    } else {
        // 🟩 Nếu chưa có nhóm thì tạo nhóm mới
        $maxGroup = \DB::table('detai')->where('magv', $lecturer->magv)->max('nhom');
        $groupNumber = $maxGroup ? $maxGroup + 1 : 1;
    }

    // 🔹 Lấy tên đề tài và trạng thái mới nhập (nếu có)
    $groupTitle = null;
    $groupStatus = null;

    foreach ($selectedStudents as $mssv) {
        if (!empty($titles[$mssv])) {
            $groupTitle = $titles[$mssv];
        }
        if (!empty($statuses[$mssv])) {
            $groupStatus = $statuses[$mssv];
        }
    }

    // Nếu không nhập gì mới thì lấy từ DB (nhóm hiện tại)
    if (!$groupTitle || !$groupStatus) {
        $groupData = \DB::table('detai')
            ->where('magv', $lecturer->magv)
            ->where('nhom', $groupNumber)
            ->first();

        if ($groupData) {
            $groupTitle = $groupTitle ?: $groupData->tendt;
            $groupStatus = $groupStatus ?: $groupData->trangthai;
        }
    }

    // 🔹 Lưu hoặc cập nhật lại cho tất cả sinh viên được chọn
    foreach ($selectedStudents as $mssv) {
        \App\Models\Detai::updateOrCreate(
            ['mssv' => $mssv],
            [
                'magv' => $lecturer->magv,
                'nhom' => $groupNumber,
                'tendt' => $groupTitle ?: 'Chưa đặt tên đề tài',
                'trangthai' => $groupStatus ?: 'Chưa bắt đầu',
            ]
        );
    }

    // 🔹 Cập nhật đồng bộ cho toàn bộ thành viên trong nhóm (kể cả chưa chọn)
    \DB::table('detai')
        ->where('magv', $lecturer->magv)
        ->where('nhom', $groupNumber)
        ->update([
            'tendt' => $groupTitle ?: 'Chưa đặt tên đề tài',
            'trangthai' => $groupStatus ?: 'Chưa bắt đầu',
        ]);

    return back()->with('success', "Đã cập nhật thông tin nhóm {$groupNumber} thành công!");
}

}
