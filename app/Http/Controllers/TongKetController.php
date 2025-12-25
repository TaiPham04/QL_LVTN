<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\TongKetExport;

class TongKetController extends Controller
{
    protected $exportService;

    public function __construct(TongKetExport $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * ✅ Danh sách hội đồng mà GV là Chủ tịch hoặc Thư ký
     */
    public function index()
    {
        $user = session('user');
        
        if (!$user || !isset($user->magv)) {
            return redirect('/login')->with('error', 'Vui lòng đăng nhập lại');
        }

        $magv = $user->magv;
        \Log::info('=== TongKet Index ===');
        \Log::info('magv: ' . $magv);

        // ✅ Lấy danh sách hội đồng mà GV là Chủ tịch hoặc Thư ký
        $hoiDongList = DB::table('thanhvienhoidong as tv')
            ->join('hoidong as hd', 'tv.hoidong_id', '=', 'hd.id')
            ->leftJoin('hoidong_detai as hdt', 'hd.id', '=', 'hdt.hoidong_id')
            ->where('tv.magv', $magv)
            ->whereIn('tv.vai_tro', ['chu_tich', 'thu_ky'])
            ->select(
                'hd.id as hoidong_id',
                'hd.mahd',
                'hd.tenhd',
                'hd.ngay_hoidong',
                'hd.trang_thai',
                'tv.vai_tro',
                DB::raw('COUNT(DISTINCT hdt.nhom_id) as so_de_tai')
            )
            ->groupBy('hd.id', 'hd.mahd', 'hd.tenhd', 'hd.ngay_hoidong', 'hd.trang_thai', 'tv.vai_tro')
            ->orderBy('hd.ngay_hoidong', 'desc')
            ->get();

        \Log::info('hoiDongList count: ' . $hoiDongList->count());

        return view('lecturers.tong-ket.index', [
            'hoiDongList' => $hoiDongList,
            'magvHienTai' => $magv
        ]);
    }

    /**
     * ✅ Chi tiết điểm tổng kết của 1 hội đồng
     */
    public function show($hoidong_id)
    {
        $user = session('user');
        
        if (!$user || !isset($user->magv)) {
            return redirect('/login')->with('error', 'Vui lòng đăng nhập lại');
        }

        $magv = $user->magv;

        // ✅ Kiểm tra: GV có phải Chủ tịch hoặc Thư ký của hội đồng này không
        $isChairmanOrSecretary = DB::table('thanhvienhoidong')
            ->where('hoidong_id', $hoidong_id)
            ->where('magv', $magv)
            ->whereIn('vai_tro', ['chu_tich', 'thu_ky'])
            ->exists();

        if (!$isChairmanOrSecretary) {
            return redirect()->route('lecturers.tong-ket.index')
                ->with('error', 'Bạn không có quyền xem hội đồng này!');
        }

        // Lấy thông tin hội đồng
        $hoiDong = DB::table('hoidong')
            ->where('id', $hoidong_id)
            ->first();

        if (!$hoiDong) {
            return redirect()->route('lecturers.tong-ket.index')
                ->with('error', 'Hội đồng không tồn tại!');
        }

        \Log::info('=== TongKet Show (Hội Đồng: ' . $hoiDong->mahd . ') ===');

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

        return view('lecturers.tong-ket.show', [
            'hoiDong' => $hoiDong,
            'danhSachTongKet' => $danhSachTongKet,
            'khongCoDiem' => $khongCoDiem,
            'magvHienTai' => $magv
        ]);
    }

    /**
     * ✅ Xuất Excel tổng kết (từ hoidong_id query param hoặc từ request)
     */
    public function exportExcel(Request $request)
    {
        $user = session('user');
        
        if (!$user || !isset($user->magv)) {
            return response()->json(['error' => 'User not found'], 401);
        }

        $magv = $user->magv;
        $hoidong_id = $request->query('hoidong_id');

        // ✅ Kiểm tra: GV có phải Chủ tịch hoặc Thư ký của hội đồng này không
        $isChairmanOrSecretary = DB::table('thanhvienhoidong')
            ->where('hoidong_id', $hoidong_id)
            ->where('magv', $magv)
            ->whereIn('vai_tro', ['chu_tich', 'thu_ky'])
            ->exists();

        if (!$isChairmanOrSecretary) {
            return response()->json([
                'error' => 'Bạn không có quyền xuất! Chỉ Chủ tịch và Thư ký hội đồng mới có thể xuất.'
            ], 403);
        }

        \Log::info('=== EXPORT EXCEL CALLED ===');
        \Log::info('magv: ' . $magv);
        \Log::info('hoidong_id: ' . $hoidong_id);

        try {
            \Log::info('Starting export...');
            $filepath = $this->exportService->exportExcelHoiDong($hoidong_id);
            \Log::info('Export successful, filepath: ' . $filepath);
            
            return response()->download($filepath, 'DiemTongKet_' . now()->format('Ymd') . '.xlsx')
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error('Export Error: ' . $e->getMessage());
            \Log::error('Stack: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }
}