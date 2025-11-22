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

    // 👇 Hiển thị danh sách đề tài
    public function topics(Request $request)
    {
        // 🔹 Lọc theo giảng viên và trạng thái
        $selectedLecturer = $request->input('lecturer');
        $selectedStatus = $request->input('status');

        $query = DB::table('detai')
            ->leftJoin('sinhvien', 'detai.mssv', '=', 'sinhvien.mssv')
            ->leftJoin('giangvien', 'detai.magv', '=', 'giangvien.magv')
            ->select(
                'detai.mssv',
                'sinhvien.hoten as tensv',
                'detai.nhom',
                'detai.tendt',
                'giangvien.hoten as tengv'
            )
            ->orderBy('detai.nhom')
            ->orderBy('sinhvien.hoten');

        // Lọc theo giảng viên
        if (!empty($selectedLecturer)) {
            $query->where('giangvien.hoten', $selectedLecturer);
        }

        // Lọc theo trạng thái đề tài
        if ($selectedStatus === 'co_detai') {
            $query->whereNotNull('detai.tendt')
                  ->where('detai.tendt', '!=', '');
        } elseif ($selectedStatus === 'chua_detai') {
            $query->where(function($q) {
                $q->whereNull('detai.tendt')
                  ->orWhere('detai.tendt', '');
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