<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\TongKetExport;

class AdminTongKetController extends Controller
{
    protected $exportService;

    public function __construct(TongKetExport $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * ✅ Danh sách tất cả hội đồng (Admin xem được tất cả)
     */
    public function index()
    {
        \Log::info('=== Admin TongKet Index ===');

        // ✅ Lấy tất cả hội đồng (không giới hạn quyền)
        $hoiDongList = DB::table('hoidong as hd')
            ->leftJoin('hoidong_detai as hdt', 'hd.id', '=', 'hdt.hoidong_id')
            ->select(
                'hd.id as hoidong_id',
                'hd.mahd',
                'hd.tenhd',
                'hd.ngay_hoidong',
                'hd.trang_thai',
                DB::raw('COUNT(DISTINCT hdt.nhom_id) as so_de_tai')
            )
            ->groupBy('hd.id', 'hd.mahd', 'hd.tenhd', 'hd.ngay_hoidong', 'hd.trang_thai')
            ->orderBy('hd.ngay_hoidong', 'desc')
            ->get();

        // Lấy thêm thông tin Chủ tịch và Thư ký cho mỗi hội đồng
        foreach ($hoiDongList as $hd) {
            $chuTich = DB::table('thanhvienhoidong as tv')
                ->join('giangvien as gv', 'tv.magv', '=', 'gv.magv')
                ->where('tv.hoidong_id', $hd->hoidong_id)
                ->where('tv.vai_tro', 'chu_tich')
                ->select('gv.hoten')
                ->first();
            
            $thuKy = DB::table('thanhvienhoidong as tv')
                ->join('giangvien as gv', 'tv.magv', '=', 'gv.magv')
                ->where('tv.hoidong_id', $hd->hoidong_id)
                ->where('tv.vai_tro', 'thu_ky')
                ->select('gv.hoten')
                ->first();

            $hd->chu_tich = $chuTich?->hoten ?? '-';
            $hd->thu_ky = $thuKy?->hoten ?? '-';
        }

        \Log::info('hoiDongList count: ' . $hoiDongList->count());

        return view('admin.tong-ket.index', [
            'hoiDongList' => $hoiDongList
        ]);
    }

    /**
     * ✅ Chi tiết điểm tổng kết của 1 hội đồng
     */
    public function show($hoidong_id)
    {
        // Lấy thông tin hội đồng
        $hoiDong = DB::table('hoidong')
            ->where('id', $hoidong_id)
            ->first();

        if (!$hoiDong) {
            return redirect()->route('admin.tong-ket.index')
                ->with('error', 'Hội đồng không tồn tại!');
        }

        \Log::info('=== Admin TongKet Show (Hội Đồng: ' . $hoiDong->mahd . ') ===');

        // ✅ Lấy tất cả đề tài của hội đồng (kể cả chưa chấm điểm)
        $deTaiList = DB::table('hoidong_detai as hdt')
            ->join('nhom as n', 'hdt.nhom_id', '=', 'n.id')
            ->leftJoin('detai as dt', function($join) {
                $join->on('hdt.nhom_id', '=', 'dt.nhom_id')
                     ->whereNotNull('dt.mssv');
            })
            ->leftJoin('sinhvien as sv', 'dt.mssv', '=', 'sv.mssv')
            ->where('hdt.hoidong_id', $hoidong_id)
            ->select(
                'hdt.hoidong_id',
                'hdt.thu_tu',
                'n.id as nhom_id',
                'dt.mssv',
                'sv.hoten',
                'sv.lop',
                'n.tennhom as nhom',
                'n.tendt as tendt'
            )
            ->distinct()
            ->orderBy('hdt.thu_tu')
            ->get();

        \Log::info('deTaiList count: ' . $deTaiList->count());

        // ✅ Map MSSV với TTBC từ hoidong_detai.thu_tu
        $ttbcMap = [];
        foreach ($deTaiList as $dt) {
            if ($dt->mssv) {
                $ttbcMap[$dt->mssv] = $dt->thu_tu;
            }
        }

        // ✅ Lấy thông tin thành viên hội đồng (GV1-4 trong hội đồng chấm điểm)
        $hoiDongMembers = DB::table('thanhvienhoidong as tv')
            ->join('giangvien as gv', 'tv.magv', '=', 'gv.magv')
            ->where('tv.hoidong_id', $hoidong_id)
            ->select('tv.vai_tro', 'gv.magv', 'gv.hoten')
            ->get();

        // Tạo map: vai_tro => tên GV
        $memberMap = [];
        foreach ($hoiDongMembers as $member) {
            $memberMap[$member->vai_tro] = $member->hoten;
        }

        // GV1-4 từ hội đồng chấm điểm (Chủ tịch, Thư ký, Thành viên 1, Thành viên 2)
        $tenGV1 = $memberMap['chu_tich'] ?? '-';
        $tenGV2 = $memberMap['thu_ky'] ?? '-';
        $tenGV3 = $memberMap['thanh_vien_1'] ?? '-';
        $tenGV4 = $memberMap['thanh_vien_2'] ?? '-';

        $danhSachTongKet = [];

        foreach ($deTaiList as $dt) {
            if (!$dt->mssv) continue;

            // ✅ LẤY TÊN GVHD từ bảng detai (luôn có, không cần phiếu chấm)
            $gvhdInfo = DB::table('detai as d')
                ->join('giangvien as gv', 'd.magv', '=', 'gv.magv')
                ->where('d.nhom_id', $dt->nhom_id)
                ->where('d.mssv', $dt->mssv)
                ->select('gv.hoten')
                ->first();

            $tenGVHD = $gvhdInfo?->hoten ?? '-';

            // ✅ LẤY ĐIỂM GVHD (từ phieu_cham_diem, nếu có)
            $diemHDData = DB::table('phieu_cham_diem as pcd')
                ->join('diem_sinh_vien as dsv', 'pcd.id', '=', 'dsv.phieu_cham_id')
                ->where('pcd.nhom_id', $dt->nhom_id)
                ->where('dsv.mssv', $dt->mssv)
                ->where('pcd.loai_phieu', 'huong_dan')
                ->select('dsv.diem_tong')
                ->first();

            $diemHD = $diemHDData?->diem_tong;

            // ✅ LẤY TÊN GVPB từ bảng phancong_phanbien (luôn có, không cần phiếu chấm)
            $gvpbInfo = DB::table('phancong_phanbien as ppb')
                ->join('giangvien as gv', 'ppb.magv_phanbien', '=', 'gv.magv')
                ->where('ppb.nhom_id', $dt->nhom_id)
                ->select('gv.hoten')
                ->first();

            $tenGVPB = $gvpbInfo?->hoten ?? '-';

            // ✅ LẤY ĐIỂM GVPB (từ phieu_cham_diem, nếu có)
            $diemPBData = DB::table('phieu_cham_diem as pcd')
                ->join('diem_sinh_vien as dsv', 'pcd.id', '=', 'dsv.phieu_cham_id')
                ->where('pcd.nhom_id', $dt->nhom_id)
                ->where('dsv.mssv', $dt->mssv)
                ->where('pcd.loai_phieu', 'phan_bien')
                ->select('dsv.diem_tong')
                ->first();

            $diemPB = $diemPBData?->diem_tong;

            // ✅ Lấy điểm hội đồng (4 thành viên)
            $hoiDongScores = DB::table('hoidong_chamdiem as hc')
                ->where('hc.hoidong_id', $hoidong_id)
                ->where('hc.nhom_id', $dt->nhom_id)
                ->where('hc.mssv', $dt->mssv)
                ->select('hc.diem_chu_tich', 'hc.diem_thu_ky', 'hc.diem_thanh_vien_1', 'hc.diem_thanh_vien_2', 'hc.diem_tong')
                ->first();

            // Format các điểm
            $diemHD_formatted = $diemHD !== null ? round($diemHD, 2) : '';
            $diemPB_formatted = $diemPB !== null ? round($diemPB, 2) : '';
            
            $diemGV1 = $hoiDongScores?->diem_chu_tich !== null ? round($hoiDongScores->diem_chu_tich, 2) : '';
            $diemGV2 = $hoiDongScores?->diem_thu_ky !== null ? round($hoiDongScores->diem_thu_ky, 2) : '';
            $diemGV3 = $hoiDongScores?->diem_thanh_vien_1 !== null ? round($hoiDongScores->diem_thanh_vien_1, 2) : '';
            $diemGV4 = $hoiDongScores?->diem_thanh_vien_2 !== null ? round($hoiDongScores->diem_thanh_vien_2, 2) : '';

            // Tính điểm tổng kết theo công thức: (HD*20 + PB*20 + (TB 4 thành viên)*60) / 100
            $diemTongKet = '';
            if ($diemHD !== null && $diemPB !== null && $diemGV1 !== '' && $diemGV2 !== '' && $diemGV3 !== '' && $diemGV4 !== '') {
                $tbHoiDong = ($diemGV1 + $diemGV2 + $diemGV3 + $diemGV4) / 4;
                $diemTongKet = round(($diemHD * 20 + $diemPB * 20 + $tbHoiDong * 60) / 100, 2);
            }

            $danhSachTongKet[] = [
                'ttbc' => $ttbcMap[$dt->mssv] ?? '',
                'mssv' => $dt->mssv,
                'hoten' => $dt->hoten,
                'nhom' => $dt->nhom,
                'lop' => $dt->lop,
                'tendt' => $dt->tendt,
                'ten_gvhd' => $tenGVHD,
                'diem_hd' => $diemHD_formatted,
                'ten_gvpb' => $tenGVPB,
                'diem_pb' => $diemPB_formatted,
                'diem_gv1' => $diemGV1,
                'diem_gv2' => $diemGV2,
                'diem_gv3' => $diemGV3,
                'diem_gv4' => $diemGV4,
                'diem_tongket' => $diemTongKet
            ];
        }

        $khongCoDiem = empty($danhSachTongKet);

        return view('admin.tong-ket.show', [
            'hoiDong' => $hoiDong,
            'danhSachTongKet' => $danhSachTongKet,
            'khongCoDiem' => $khongCoDiem,
            'tenGV1' => $tenGV1,
            'tenGV2' => $tenGV2,
            'tenGV3' => $tenGV3,
            'tenGV4' => $tenGV4
        ]);
    }

    /**
     * ✅ Xuất Excel tổng kết (Admin không cần kiểm tra quyền)
     */
    public function exportExcel(Request $request)
    {
        $hoidong_id = $request->query('hoidong_id');

        if (!$hoidong_id) {
            \Log::error('❌ Thiếu hoidong_id');
            return response()->json(['error' => 'Thiếu hoidong_id'], 400);
        }

        \Log::info('✅ EXPORT START - hoidong_id: ' . $hoidong_id);

        try {
            // 1️⃣ Lấy hội đồng
            $hoiDong = DB::table('hoidong')
                ->where('id', $hoidong_id)
                ->first();

            if (!$hoiDong) {
                \Log::error('❌ Hội đồng không tồn tại');
                return response()->json(['error' => 'Hội đồng không tồn tại'], 404);
            }

            \Log::info('✅ Hội đồng: ' . $hoiDong->tenhd);

            // 2️⃣ Gọi Service để lấy dữ liệu
            \Log::info('📊 Calling exportService->exportExcelHoiDong()');
            $filepath = $this->exportService->exportExcelHoiDong($hoidong_id);

            \Log::info('✅ File created: ' . $filepath);
            \Log::info('✅ File exists: ' . (file_exists($filepath) ? 'YES' : 'NO'));
            \Log::info('✅ File size: ' . (file_exists($filepath) ? filesize($filepath) . ' bytes' : 'N/A'));

            // 3️⃣ Download file
            $filename = 'DiemTongKet_' . $hoiDong->mahd . '_' . now()->format('Ymd_His') . '.xlsx';
            
            \Log::info('📥 Downloading: ' . $filename);

            return response()->download($filepath, $filename)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            \Log::error('❌ ERROR: ' . $e->getMessage());
            \Log::error('📍 Stack: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => 'Lỗi xuất file',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}