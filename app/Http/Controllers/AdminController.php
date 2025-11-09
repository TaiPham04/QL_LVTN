<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index()
    {
        return view('layouts.app');
    }

    // 👇 Hiển thị danh sách đề tài giảng viên gửi lên
    public function topics(Request $request)
    {
        // 🔹 Lọc theo giảng viên nếu có chọn
        $selectedLecturer = $request->input('lecturer');

        $query = DB::table('detai_admin')
            ->leftJoin('sinhvien', 'detai_admin.mssv', '=', 'sinhvien.mssv')
            ->leftJoin('giangvien', 'detai_admin.magv', '=', 'giangvien.magv')
            ->select(
                'sinhvien.mssv',
                'detai_admin.tendt',
                'giangvien.hoten as tengv',
                'detai_admin.created_at'
            )
            ->orderByDesc('detai_admin.created_at');

        // Nếu có lọc theo giảng viên
        if (!empty($selectedLecturer)) {
            $query->where('giangvien.hoten', $selectedLecturer);
        }

        $topics = $query->get();

        // 🔹 Lấy danh sách tất cả giảng viên để hiển thị trong select box
        $lecturers = DB::table('giangvien')
            ->select('hoten as tengv')
            ->orderBy('hoten')
            ->get();

        return view('admin.topics.index', compact('topics', 'lecturers'));
    }

}
