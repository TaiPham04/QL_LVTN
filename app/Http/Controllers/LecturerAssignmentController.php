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
            return back()->with('error', 'Vui lòng chọn ít nhất 1 sinh viên để lưu thông tin.');
        }

        if (count($selectedStudents) > 2) {
            return back()->with('error', 'Mỗi nhóm chỉ được tối đa 2 sinh viên.');
        }

        // 🔹 Lấy giảng viên hiện tại
        $user = session('user');
        $lecturer = DB::table('giangvien')->where('email', $user->email)->first();
        if (!$lecturer) {
            return back()->with('error', 'Không tìm thấy thông tin giảng viên!');
        }

        // 🟡 Phân loại sinh viên hợp lệ và không hợp lệ
        $validStudents = [];
        $invalidStudents = [];

        foreach ($selectedStudents as $mssv) {
            $title = trim($titles[$mssv] ?? '');
            $status = trim($statuses[$mssv] ?? '');

            if (
                strcasecmp($title, 'Không chọn đề tài') === 0 ||
                strcasecmp($status, 'Đình chỉ') === 0
            ) {
                $invalidStudents[] = $mssv; // không được xếp nhóm
            } else {
                $validStudents[] = $mssv; // đủ điều kiện xếp nhóm
            }
        }

      
        if (count($validStudents) > 2) {
            return back()->with('error', 'Mỗi nhóm chỉ được tối đa 2 sinh viên hợp lệ.');
        }

        // ⚙️ Cho phép lưu nếu có sinh viên không hợp lệ (VD: Đình chỉ hoặc Không chọn đề tài)
        if (count($validStudents) === 0 && count($invalidStudents) === 0) {
            return back()->with('error', 'Vui lòng chọn ít nhất 1 sinh viên để lưu.');
        }

        // 🔹 Nếu có sinh viên hợp lệ, xác định nhóm
        $groupNumber = null;
        if (!empty($validStudents)) {
            $firstStudent = $validStudents[0];
            $existingRecord = DB::table('detai')
                ->where('mssv', $firstStudent)
                ->where('magv', $lecturer->magv)
                ->first();

            $groupNumber = $existingRecord->nhom ?? null;

            // Nếu chưa có nhóm → tạo mới
            if (!$groupNumber) {
                $maxGroup = DB::table('detai')->where('magv', $lecturer->magv)->max('nhom');
                $groupNumber = $maxGroup ? $maxGroup + 1 : 1;
            }

            // 🔹 Lấy đề tài & trạng thái từ input
            $groupTitle = null;
            $groupStatus = null;
            foreach ($validStudents as $mssv) {
                if (!empty($titles[$mssv])) $groupTitle = $titles[$mssv];
                if (!empty($statuses[$mssv])) $groupStatus = $statuses[$mssv];
            }

            // Nếu chưa nhập gì thì giữ nguyên dữ liệu nhóm cũ (nếu có)
            $groupData = DB::table('detai')
                ->where('magv', $lecturer->magv)
                ->where('nhom', $groupNumber)
                ->first();

            if ($groupData) {
                $groupTitle = $groupTitle ?: $groupData->tendt;
                $groupStatus = $groupStatus ?: $groupData->trangthai;
            }

            // 🔹 Lưu thông tin cho sinh viên hợp lệ
            foreach ($validStudents as $mssv) {
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
        }

        // 🔹 Lưu sinh viên không hợp lệ (không phân nhóm)
        foreach ($invalidStudents as $mssv) {
            \App\Models\Detai::updateOrCreate(
                ['mssv' => $mssv],
                [
                    'magv' => $lecturer->magv,
                    'nhom' => null,
                    'tendt' => $titles[$mssv] ?? 'Không chọn đề tài',
                    'trangthai' => $statuses[$mssv] ?? 'Đình chỉ',
                ]
            );
        }

        return back()->with('success', 'Đã lưu thông tin thành công!');
    }

    public function deleteSelected(Request $request)
    {
        $students = json_decode($request->input('students'), true);

        if (!$students || count($students) === 0) {
            return back()->with('error', 'Vui lòng chọn ít nhất 1 sinh viên để xóa!');
        }

        $user = session('user');
        $lecturer = DB::table('giangvien')->where('email', $user->email)->first();
        if (!$lecturer) {
            return back()->with('error', 'Không tìm thấy thông tin giảng viên!');
        }

        foreach ($students as $mssv) {
            $record = DB::table('detai')
                ->where('mssv', $mssv)
                ->where('magv', $lecturer->magv)
                ->first();

            if ($record && $record->nhom) {
                // kiểm tra còn sinh viên khác trong nhóm không
                $others = DB::table('detai')
                    ->where('magv', $lecturer->magv)
                    ->where('nhom', $record->nhom)
                    ->where('mssv', '!=', $mssv)
                    ->count();

                if ($others > 0) {
                    // nếu còn sinh viên khác cùng nhóm → chỉ tách sinh viên được chọn
                    DB::table('detai')
                        ->where('mssv', $mssv)
                        ->update([
                            'nhom' => null,
                            'tendt' => '',
                            'trangthai' => '',
                        ]);
                } else {
                    // nếu nhóm chỉ có 1 người → xóa luôn record
                    DB::table('detai')
                        ->where('mssv', $mssv)
                        ->delete();
                }
            } else {
                // không thuộc nhóm nào → xóa record
                DB::table('detai')
                    ->where('mssv', $mssv)
                    ->delete();
            }
        }

        return back()->with('success', 'Đã xóa thông tin sinh viên được chọn thành công!');
    }

    public function sendToAdmin(Request $request)
    {
        $user = session('user');

        // 🔹 Lấy thông tin giảng viên từ bảng giangvien
        $lecturer = DB::table('giangvien')->where('email', $user->email)->first();

        if (!$lecturer) {
            return back()->with('error', 'Không tìm thấy thông tin giảng viên!');
        }

        // 🔹 Lấy danh sách đề tài mà giảng viên đã tạo
        $topics = DB::table('detai')->where('magv', $lecturer->magv)->get();

        if ($topics->isEmpty()) {
            return back()->with('error', 'Không có đề tài nào để gửi.');
        }

        // 🔹 Duyệt từng đề tài và gửi sang bảng detai_admin
        foreach ($topics as $topic) {
            DB::table('detai_admin')->updateOrInsert(
                ['mssv' => $topic->mssv],
                [
                    'magv'       => $topic->magv,
                    'tendt'      => $topic->tendt,
                    'nhom'       => $topic->nhom ?? null,
                    'trangthai'  => 'Chờ duyệt',
                    'created_at' => now(),
                ]
            );
        }

        // 🔹 Tạo thông báo hiển thị trên chuông 🔔 cho admin
        $message = "Giảng viên {$lecturer->hoten} đã gửi " . count($topics) . " đề tài mới.";
        $notifications = session('notifications', []);
        $notifications[] = $message;
        session(['notifications' => $notifications]);

        return back()->with('success', 'Đã gửi toàn bộ đề tài cho admin thành công!');
    }

}
