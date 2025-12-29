<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.dashboard');
    }

    // 👇 Hiển thị danh sách đề tài
    public function topics(Request $request)
    {
        // 🔹 Lọc theo giảng viên và tìm kiếm
        $selectedLecturer = $request->input('lecturer');
        $searchQuery = $request->input('search');

        $query = DB::table('detai')
            ->leftJoin('sinhvien', 'detai.mssv', '=', 'sinhvien.mssv')
            ->leftJoin('giangvien', 'detai.magv', '=', 'giangvien.magv')
            ->leftJoin('nhom', 'detai.nhom_id', '=', 'nhom.id')
            ->select(
                'detai.mssv',
                'sinhvien.hoten as tensv',
                'nhom.tennhom as nhom',
                'nhom.tendt',
                'giangvien.hoten as tengv'
            )
            ->orderBy('nhom.tennhom')
            ->orderBy('sinhvien.hoten');

        // Lọc theo giảng viên
        if (!empty($selectedLecturer)) {
            $query->where('giangvien.hoten', $selectedLecturer);
        }

        // ✅ Tìm kiếm theo MSSV, tên sinh viên, tên đề tài
        if (!empty($searchQuery)) {
            $query->where(function($q) use ($searchQuery) {
                $q->where('sinhvien.mssv', 'like', "%{$searchQuery}%")
                  ->orWhere('sinhvien.hoten', 'like', "%{$searchQuery}%")
                  ->orWhere('nhom.tendt', 'like', "%{$searchQuery}%");
            });
        }

        $topics = $query->get();

        // Group theo nhóm
        $groupedTopics = $topics->groupBy('nhom')->map(function ($items, $nhom) {
            $first = $items->first();
            return [
                'nhom' => $nhom ?? 'Chưa có',
                'tendt' => $first->tendt,
                'tengv' => $first->tengv,
                'students' => $items->map(function ($item) {
                    return [
                        'mssv' => $item->mssv,
                        'tensv' => $item->tensv
                    ];
                })->toArray()
            ];
        })->values();

        // 🔹 Lấy danh sách giảng viên
        $lecturers = DB::table('giangvien')
            ->select('hoten as tengv')
            ->orderBy('hoten')
            ->get();

        return view('admin.topics.index', compact('groupedTopics', 'lecturers'));
    }
}