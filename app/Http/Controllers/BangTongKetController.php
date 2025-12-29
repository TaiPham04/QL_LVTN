<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\BangTongKetExport;

class BangTongKetController extends Controller
{
    /**
     * Hiển thị bảng tổng kết
     */
    public function index(Request $request)
    {
        $query = $this->buildQuery();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('sv.mssv', 'like', "%{$search}%")
                  ->orWhere('sv.hoten', 'like', "%{$search}%")
                  ->orWhere('nhom.tennhom', 'like', "%{$search}%")
                  ->orWhere('nhom.tendt', 'like', "%{$search}%");
            });
        }

        // Filter theo giảng viên
        if ($request->filled('magv')) {
            $query->where('nhom.magv', $request->magv);
        }

        // Filter theo trạng thái
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status == 'completed') {
                $query->whereNotNull('phieu_hd.id')
                      ->whereNotNull('phieu_pb.id');
            } elseif ($status == 'incomplete') {
                $query->where(function($q) {
                    $q->whereNull('phieu_hd.id')
                      ->orWhereNull('phieu_pb.id');
                });
            }
        }

        $students = $query->get();
        $lecturers = DB::table('giangvien')->get();
        
        return view('admin.bangtongket.index', compact('students', 'lecturers'));
    }

    /**
     * Xuất Excel bảng tổng kết - CÓ LỌC THEO SEARCH + VISIBLE COLUMNS
     */
    public function export(Request $request)
    {
        $query = $this->buildQuery();

        // ✅ Nếu có mssv_filter (từ table hiển thị), chỉ lấy những MSSV đó
        if ($request->filled('mssv_filter')) {
            $mssv_list = explode(',', $request->mssv_filter);
            $query->whereIn('sv.mssv', $mssv_list);
        } else {
            // Nếu không, áp dụng search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('sv.mssv', 'like', "%{$search}%")
                      ->orWhere('sv.hoten', 'like', "%{$search}%")
                      ->orWhere('nhom.tennhom', 'like', "%{$search}%")
                      ->orWhere('nhom.tendt', 'like', "%{$search}%");
                });
            }
        }

        // Apply filters khi export
        if ($request->filled('magv')) {
            $query->where('nhom.magv', $request->magv);
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status == 'completed') {
                $query->whereNotNull('phieu_hd.id')
                      ->whereNotNull('phieu_pb.id');
            } elseif ($status == 'incomplete') {
                $query->where(function($q) {
                    $q->whereNull('phieu_hd.id')
                      ->orWhereNull('phieu_pb.id');
                });
            }
        }

        $students = $query->get();
        
        // ✅ Lấy danh sách cột hiển thị từ query parameter
        $visibleColumns = [];
        if ($request->filled('visible_columns')) {
            $visibleColumns = explode(',', $request->visible_columns);
        } else {
            // Nếu không có parameter, mặc định hiển thị tất cả (kể cả diem_hoidong)
            $visibleColumns = ['mssv', 'tennhom', 'hoten', 'lop', 'tendt', 'ten_gvhd', 'diem_gvhd', 'ten_gvpb', 'diem_gvpb', 'diem_hoidong', 'diem_tong'];
        }
        
        $export = new BangTongKetExport($students, $visibleColumns);
        return $export->export();
    }

    /**
     * Lấy thống kê tổng quan
     */
    public function getStats()
    {
        $total = DB::table('nhom')->count();
        
        $completed = DB::table('nhom as n')
            ->leftJoin('phieu_cham_diem as phieu_hd', function($join) {
                $join->on('n.id', '=', 'phieu_hd.nhom_id')
                     ->where('phieu_hd.loai_phieu', '=', 'huong_dan');
            })
            ->leftJoin('phieu_cham_diem as phieu_pb', function($join) {
                $join->on('n.id', '=', 'phieu_pb.nhom_id')
                     ->where('phieu_pb.loai_phieu', '=', 'phan_bien');
            })
            ->whereNotNull('phieu_hd.id')
            ->whereNotNull('phieu_pb.id')
            ->distinct('n.id')
            ->count('n.id');

        $avgScore = DB::table('diem_sinh_vien')
            ->selectRaw('ROUND(AVG(diem_tong), 2) as avg')
            ->value('avg') ?? 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'incomplete' => $total - $completed,
            'avgScore' => $avgScore
        ];
    }

    /**
     * Lấy danh sách nhóm chưa hoàn chỉnh điểm
     */
    public function getIncompleteStudents()
    {
        return DB::table('nhom as n')
            ->leftJoin('detai as d', 'n.id', '=', 'd.nhom_id')
            ->leftJoin('sinhvien as s', 'd.mssv', '=', 's.mssv')
            ->leftJoin('phieu_cham_diem as phieu_hd', function($join) {
                $join->on('n.id', '=', 'phieu_hd.nhom_id')
                     ->where('phieu_hd.loai_phieu', '=', 'huong_dan');
            })
            ->leftJoin('phieu_cham_diem as phieu_pb', function($join) {
                $join->on('n.id', '=', 'phieu_pb.nhom_id')
                     ->where('phieu_pb.loai_phieu', '=', 'phan_bien');
            })
            ->where(function($q) {
                $q->whereNull('phieu_hd.id')
                  ->orWhereNull('phieu_pb.id');
            })
            ->select('n.id', 'n.tennhom', 's.mssv', 's.hoten', 'phieu_hd.id as has_huongdan', 'phieu_pb.id as has_phanbien')
            ->distinct()
            ->get();
    }

    /**
     * Build query base cho bảng tổng kết
     */
    private function buildQuery()
    {
        return DB::table('nhom as nhom')
            ->leftJoin('detai as d', 'nhom.id', '=', 'd.nhom_id')
            ->leftJoin('sinhvien as sv', 'd.mssv', '=', 'sv.mssv')
            ->leftJoin('giangvien as gv_hd', 'nhom.magv', '=', 'gv_hd.magv')
            ->leftJoin('phancong_phanbien as pb', 'nhom.id', '=', 'pb.nhom_id')
            ->leftJoin('giangvien as gv_pb', 'pb.magv_phanbien', '=', 'gv_pb.magv')
            ->leftJoin('phieu_cham_diem as phieu_hd', function($join) {
                $join->on('nhom.id', '=', 'phieu_hd.nhom_id')
                     ->where('phieu_hd.loai_phieu', '=', 'huong_dan');
            })
            ->leftJoin('phieu_cham_diem as phieu_pb', function($join) {
                $join->on('nhom.id', '=', 'phieu_pb.nhom_id')
                     ->where('phieu_pb.loai_phieu', '=', 'phan_bien');
            })
            ->leftJoin('diem_sinh_vien as dsv_hd', 'phieu_hd.id', '=', 'dsv_hd.phieu_cham_id')
            ->leftJoin('diem_sinh_vien as dsv_pb', 'phieu_pb.id', '=', 'dsv_pb.phieu_cham_id')
            // ✅ JOIN để lấy điểm hội đồng
            ->leftJoinSub(
                DB::table('hoidong_chamdiem')->select('nhom_id', DB::raw('MAX(diem_tong) as diem_tong'))->groupBy('nhom_id'),
                'hd',
                'nhom.id',
                '=',
                'hd.nhom_id'
            )
            ->select(
                'sv.mssv',
                'nhom.id as nhom_id',
                'nhom.tennhom',
                'sv.hoten',
                'sv.lop',
                'nhom.tendt',
                'gv_hd.hoten as ten_gvhd',
                DB::raw('MAX(dsv_hd.diem_tong) as diem_gvhd'),
                'gv_pb.hoten as ten_gvpb',
                DB::raw('MAX(dsv_pb.diem_tong) as diem_gvpb'),
                // ✅ Điểm hội đồng
                DB::raw('MAX(CASE WHEN hd.diem_tong > 0 THEN ROUND(hd.diem_tong, 2) ELSE NULL END) as diem_hoidong'),
                DB::raw('ROUND((COALESCE(MAX(dsv_hd.diem_tong), 0) + COALESCE(MAX(dsv_pb.diem_tong), 0)) / 2, 2) as diem_tong')
            )
            ->groupBy('sv.mssv', 'nhom.id', 'nhom.tennhom', 'sv.hoten', 'sv.lop', 'nhom.tendt', 'gv_hd.hoten', 'gv_pb.hoten')
            ->orderBy('nhom.tennhom')
            ->orderBy('sv.mssv');
    }
}