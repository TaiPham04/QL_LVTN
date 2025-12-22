<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ChamDiemHoiDongExport;

class ChamDiemHoiDongController extends Controller
{
    /**
     * Danh sách hội đồng
     */
    public function index()
    {
        $user = session('user');
        $magv = $user->magv ?? null;

        $danhSachHoiDong = DB::table('thanhvienhoidong as tv')
            ->join('hoidong as hd', 'tv.hoidong_id', '=', 'hd.id')
            ->leftJoin('hoidong_detai as hdt', 'hd.id', '=', 'hdt.hoidong_id')
            ->where('tv.magv', $magv)
            ->select('hd.id as hoidong_id', 'hd.mahd', 'hd.tenhd', 'tv.vai_tro', DB::raw('COUNT(DISTINCT hdt.nhom_id) as so_detai'))
            ->groupBy('hd.id', 'hd.mahd', 'hd.tenhd', 'tv.vai_tro')
            ->orderBy('hd.tenhd')
            ->get();

        $khongCoHoiDong = $danhSachHoiDong->isEmpty();

        return view('lecturers.cham-diem.hoi-dong.index', compact('danhSachHoiDong', 'khongCoHoiDong'));
    }

    /**
     * Form chấm điểm
     */
    public function form($mahd)
    {
        $user = session('user');
        $magv = $user->magv ?? null;

        // Lấy hội đồng
        $hoiDong = DB::table('hoidong')
            ->where('mahd', $mahd)
            ->first();

        if (!$hoiDong) {
            return back()->with('error', 'Hội đồng không tồn tại!');
        }

        $hoidong_id = $hoiDong->id;

        // Kiểm tra quyền
        $vaiTroGV = DB::table('thanhvienhoidong')
            ->where('hoidong_id', $hoidong_id)
            ->where('magv', $magv)
            ->value('vai_tro');

        if (!$vaiTroGV) {
            return back()->with('error', 'Bạn không có trong hội đồng này!');
        }

        // Lấy danh sách thành viên
        $thanhVien = DB::table('thanhvienhoidong as tv')
            ->join('giangvien as gv', 'tv.magv', '=', 'gv.magv')
            ->where('tv.hoidong_id', $hoidong_id)
            ->select('tv.magv', 'gv.hoten', 'tv.vai_tro')
            ->orderBy('tv.vai_tro')
            ->get();

        // Lấy danh sách đề tài
        $deTaiList = DB::table('hoidong_detai as hdt')
            ->join('nhom as n', 'hdt.nhom_id', '=', 'n.id')
            ->where('hdt.hoidong_id', $hoidong_id)
            ->select('n.id as nhom_id', 'n.tennhom as nhom', 'n.tendt')
            ->distinct()
            ->get();

        // Lấy sinh viên theo nhóm
        $sinhVienNhom = [];
        foreach ($deTaiList as $deTai) {
            $sinhVienNhom[$deTai->nhom_id] = DB::table('detai as dt')
                ->join('sinhvien as sv', 'dt.mssv', '=', 'sv.mssv')
                ->where('dt.nhom_id', $deTai->nhom_id)
                ->select('sv.mssv', 'sv.hoten', 'sv.lop')
                ->get();
        }

        // Lấy điểm đã chấm
        $diemHienTai = DB::table('hoidong_chamdiem')
            ->where('hoidong_id', $hoidong_id)
            ->get()
            ->groupBy(function($item) {
                return $item->nhom_id . '_' . $item->mssv;
            })
            ->map(function($group) {
                return $group->keyBy('magv_danh_gia')->map(function($item) {
                    return ['diem' => $item->diem];
                })->toArray();
            });

        return view('lecturers.cham-diem.hoi-dong.form', compact(
            'mahd',
            'hoiDong',
            'vaiTroGV',
            'thanhVien',
            'deTaiList',
            'sinhVienNhom',
            'diemHienTai'
        ));
    }

    /**
     * Lưu điểm chấm
     */
    public function store(Request $request, $mahd)
    {
        $user = session('user');
        $magv = $user->magv ?? null;

        // Lấy hội đồng
        $hoiDong = DB::table('hoidong')
            ->where('mahd', $mahd)
            ->first();

        if (!$hoiDong) {
            return back()->with('error', 'Hội đồng không tồn tại!');
        }

        $hoidong_id = $hoiDong->id;

        // Validate
        $request->validate([
            'diem' => 'required|array',
            'diem.*' => 'nullable|array',
            'diem.*.*' => 'nullable|numeric|min:0|max:10'
        ]);

        DB::beginTransaction();
        try {
            // Lấy dữ liệu từ request
            $diemData = $request->input('diem', []);

            // Lưu điểm
            foreach ($diemData as $key => $diemArray) {
                // Key format: "nhom_id_mssv"
                $parts = explode('_', $key);
                if (count($parts) < 2) continue;

                // Lấy nhom_id (phần tử đầu tiên)
                $nhom_id = $parts[0];
                // Lấy mssv (từ phần tử thứ 2 trở đi, vì mssv có thể chứa dấu gạch ngang)
                $mssv = implode('_', array_slice($parts, 1));

                foreach ($diemArray as $magv_danh_gia => $diem) {
                    if ($diem === null || $diem === '') {
                        continue;
                    }

                    // Lấy mdt từ detai
                    $mdt = DB::table('detai')
                        ->where('nhom_id', $nhom_id)
                        ->value('madt');

                    if (!$mdt) {
                        continue; // Bỏ qua nếu không tìm thấy mdt
                    }

                    // Xóa cũ
                    DB::table('hoidong_chamdiem')
                        ->where('hoidong_id', $hoidong_id)
                        ->where('nhom_id', $nhom_id)
                        ->where('mssv', $mssv)
                        ->where('magv_danh_gia', $magv_danh_gia)
                        ->delete();

                    // Lưu mới
                    DB::table('hoidong_chamdiem')->insert([
                        'hoidong_id' => $hoidong_id,
                        'mahd' => $mahd,
                        'nhom_id' => $nhom_id,
                        'mdt' => $mdt,
                        'mssv' => $mssv,
                        'magv_danh_gia' => $magv_danh_gia,
                        'diem' => (float)$diem,
                        'ngay_cham_diem' => now(),
                        'created_at' => now()
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('lecturers.cham-diem.hoi-dong.index')
                ->with('success', 'Lưu điểm thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Store HoiDong ChamDiem Error: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Xuất Excel
     */
    public function exportExcel($mahd)
    {
        try {
            $hoiDong = DB::table('hoidong')
                ->where('mahd', $mahd)
                ->first();

            if (!$hoiDong) {
                return back()->with('error', 'Hội đồng không tồn tại!');
            }

            $exporter = new ChamDiemHoiDongExport();
            $filePath = $exporter->exportExcel($hoiDong->id);

            return response()->download($filePath, basename($filePath))
                ->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            \Log::error('Export Excel Error: ' . $e->getMessage());
            return back()->with('error', 'Không thể xuất file: ' . $e->getMessage());
        }
    }
}