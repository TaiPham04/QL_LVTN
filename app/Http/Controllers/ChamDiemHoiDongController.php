<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ChamDiemHoiDongExport as ExportService;

class ChamDiemHoiDongController extends Controller
{
    protected $export;

    public function __construct(ExportService $export)
    {
        $this->export = $export;
    }

    /**
     * Danh sách hội đồng của giảng viên
     */
    public function index()
    {
        $magv = session('user')->magv;

        $danhSachHoiDong = DB::table('thanhvienhoidong as tv')
            ->join('hoidong as hd', 'tv.mahd', '=', 'hd.mahd')
            ->leftJoin('hoidong_detai as hdt', 'hd.mahd', '=', 'hdt.mahd')
            ->where('tv.magv', $magv)
            ->select(
                'hd.mahd',
                'hd.tenhd',
                'tv.vai_tro',
                DB::raw('COUNT(DISTINCT hdt.nhom) as so_detai')
            )
            ->groupBy('hd.mahd', 'hd.tenhd', 'tv.vai_tro')
            ->orderBy('hd.tenhd')
            ->get();

        $khongCoHoiDong = $danhSachHoiDong->isEmpty();

        return view('lecturers.cham-diem.hoi-dong.index', [
            'danhSachHoiDong' => $danhSachHoiDong,
            'khongCoHoiDong' => $khongCoHoiDong
        ]);
    }

    /**
     * Form chấm điểm
     */
    public function form($mahd)
    {
        $magv = session('user')->magv;

        $hoiDong = DB::table('hoidong')
            ->where('mahd', $mahd)
            ->first();

        if (!$hoiDong) {
            return redirect()->route('lecturers.cham-diem.hoi-dong.index')
                ->with('error', 'Hội đồng không tồn tại!');
        }

        $vaiTroGV = DB::table('thanhvienhoidong')
            ->where('mahd', $mahd)
            ->where('magv', $magv)
            ->value('vai_tro');

        if (!$vaiTroGV) {
            return redirect()->route('lecturers.cham-diem.hoi-dong.index')
                ->with('error', 'Bạn không có trong hội đồng này!');
        }

        $deTaiList = DB::table('hoidong_detai as hdt')
            ->join('detai as dt', 'hdt.nhom', '=', 'dt.nhom')
            ->where('hdt.mahd', $mahd)
            ->select('dt.nhom', 'dt.tendt')
            ->distinct()
            ->get();

        $sinhVienNhom = [];
        foreach ($deTaiList as $deTai) {
            $sinhVienNhom[$deTai->nhom] = DB::table('detai as dt')
                ->join('sinhvien as sv', 'dt.mssv', '=', 'sv.mssv')
                ->where('dt.nhom', $deTai->nhom)
                ->select('sv.mssv', 'sv.hoten', 'sv.lop')
                ->get();
        }

        $thanhVien = DB::table('thanhvienhoidong as tv')
            ->join('giangvien as gv', 'tv.magv', '=', 'gv.magv')
            ->where('tv.mahd', $mahd)
            ->select('tv.magv', 'gv.hoten', 'tv.vai_tro')
            ->get();

        $diemHienTai = DB::table('hoidong_chamdiem')
            ->where('mahd', $mahd)
            ->get()
            ->groupBy(function($item) {
                return $item->nhom . '_' . $item->mssv;
            })
            ->map(function($items) {
                $result = [];
                foreach ($items as $item) {
                    $result[$item->magv_danh_gia] = [
                        'diem' => $item->diem,
                        'vai_tro' => $item->vai_tro_danh_gia
                    ];
                }
                return $result;
            });

        return view('lecturers.cham-diem.hoi-dong.form', [
            'mahd' => $mahd,
            'hoiDong' => $hoiDong,
            'deTaiList' => $deTaiList,
            'sinhVienNhom' => $sinhVienNhom,
            'thanhVien' => $thanhVien,
            'diemHienTai' => $diemHienTai,
            'magvHienTai' => $magv,
            'vaiTroGV' => $vaiTroGV
        ]);
    }

    /**
     * Lưu điểm chấm
     */
    public function store(Request $request, $mahd)
    {
        $magv = session('user')->magv;

        $vaiTro = DB::table('thanhvienhoidong')
            ->where('mahd', $mahd)
            ->where('magv', $magv)
            ->value('vai_tro');

        if (!in_array($vaiTro, ['chu_tich', 'thu_ky'])) {
            return back()->with('error', 'Chỉ chủ tịch và thư ký mới có quyền chấm điểm!');
        }

        try {
            $diem = $request->input('diem', []);

            foreach ($diem as $nhomMssv => $giangVienDiem) {
                [$nhom, $mssv] = explode('_', $nhomMssv);

                foreach ($giangVienDiem as $magvDanhGia => $diemValue) {
                    if ($diemValue !== null && $diemValue !== '') {
                        $vaiTroDanhGia = DB::table('thanhvienhoidong')
                            ->where('mahd', $mahd)
                            ->where('magv', $magvDanhGia)
                            ->value('vai_tro');

                        $existing = DB::table('hoidong_chamdiem')
                            ->where('mahd', $mahd)
                            ->where('nhom', $nhom)
                            ->where('mssv', $mssv)
                            ->where('magv_danh_gia', $magvDanhGia)
                            ->first();

                        $sinhVien = DB::table('detai')
                            ->where('nhom', $nhom)
                            ->where('mssv', $mssv)
                            ->first();

                        $deTai = DB::table('detai')
                            ->where('nhom', $nhom)
                            ->first();

                        $gvHuongDan = DB::table('phancong')
                            ->where('mssv', $mssv)
                            ->value('magv');

                        if ($existing) {
                            DB::table('hoidong_chamdiem')
                                ->where('id', $existing->id)
                                ->update([
                                    'diem' => $diemValue,
                                    'ngay_cham_diem' => now(),
                                    'vai_tro_danh_gia' => $vaiTroDanhGia
                                ]);
                        } else {
                            DB::table('hoidong_chamdiem')->insert([
                                'mahd' => $mahd,
                                'nhom' => $nhom,
                                'mssv' => $mssv,
                                'magv_danh_gia' => $magvDanhGia,
                                'vai_tro_danh_gia' => $vaiTroDanhGia,
                                'ten_sinh_vien' => $sinhVien->hoten ?? '',
                                'lop' => $sinhVien->lop ?? '',
                                'tendt' => $deTai->tendt ?? '',
                                'magv_hd' => $gvHuongDan,
                                'diem' => $diemValue,
                                'ngay_cham_diem' => now()
                            ]);
                        }
                    }
                }
            }

            return redirect()->route('lecturers.cham-diem.hoi-dong.index')
                ->with('success', 'Lưu điểm thành công!');

        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Xuất Excel
     */
    public function exportExcel($mahd)
    {
        $magv = session('user')->magv;

        // Kiểm tra vai trò
        $vaiTro = DB::table('thanhvienhoidong')
            ->where('mahd', $mahd)
            ->where('magv', $magv)
            ->value('vai_tro');

        if (!in_array($vaiTro, ['chu_tich', 'thu_ky'])) {
            return response()->json([
                'error' => 'Chỉ chủ tịch và thư ký mới có quyền xuất file Excel!'
            ], 403);
        }

        try {
            // Kiểm tra tất cả đề tài đã chấm hết chưa
            $checkResult = $this->export->checkAllScored($mahd);

            if (!$checkResult['scored']) {
                return response()->json([
                    'error' => $checkResult['message'],
                    'uncheckedCount' => $checkResult['uncheckedCount']
                ], 400);
            }

            // Xuất file Excel
            return $this->export->exportExcel($mahd);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
}