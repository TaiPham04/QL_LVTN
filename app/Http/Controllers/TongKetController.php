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

    public function index()
    {
        $user = session('user');
        
        if (!$user || !isset($user->magv)) {
            return redirect('/login')->with('error', 'Vui lòng đăng nhập lại');
        }

        $magv = $user->magv;
        \Log::info('=== TongKet Index (GV Hướng Dẫn) ===');
        \Log::info('magv: ' . $magv);

        // ✅ Lấy danh sách đề tài mà GV đang hướng dẫn
        $deTaiList = DB::table('detai as dt')
            ->join('nhom as n', 'dt.nhom_id', '=', 'n.id')
            ->join('sinhvien as sv', 'dt.mssv', '=', 'sv.mssv')
            ->where('dt.magv', $magv)
            ->select(
                'n.id as nhom_id',
                'dt.mssv',
                'sv.hoten',
                'sv.lop',
                'n.tennhom as nhom',
                'n.tendt as tendt'
            )
            ->distinct()
            ->get();

        \Log::info('deTaiList count: ' . $deTaiList->count());
        \Log::info('deTaiList: ' . json_encode($deTaiList));

        $danhSachTongKet = [];

        foreach ($deTaiList as $dt) {
            \Log::info('Processing: mssv=' . $dt->mssv . ', nhom_id=' . $dt->nhom_id);

            // ✅ Lấy điểm hướng dẫn
            $diemHD = DB::table('phieu_cham_diem as pcd')
                ->join('diem_sinh_vien as dsv', 'pcd.id', '=', 'dsv.phieu_cham_id')
                ->where('pcd.nhom_id', $dt->nhom_id)
                ->where('dsv.mssv', $dt->mssv)
                ->where('pcd.loai_phieu', 'huong_dan')
                ->value('dsv.diem_tong');

            \Log::info('diemHD: ' . ($diemHD ?? 'NULL'));

            // ✅ Lấy điểm phản biện
            $diemPB = DB::table('phieu_cham_diem as pcd')
                ->join('diem_sinh_vien as dsv', 'pcd.id', '=', 'dsv.phieu_cham_id')
                ->where('pcd.nhom_id', $dt->nhom_id)
                ->where('dsv.mssv', $dt->mssv)
                ->where('pcd.loai_phieu', 'phan_bien')
                ->value('dsv.diem_tong');

            \Log::info('diemPB: ' . ($diemPB ?? 'NULL'));

            // ✅ Lấy điểm hội đồng - từ hoidong_chamdiem
            $diemHoiDong = DB::table('hoidong_chamdiem as hc')
                ->where('hc.nhom_id', $dt->nhom_id)
                ->where('hc.mssv', $dt->mssv)
                ->value('hc.diem_tong');

            \Log::info('diemHoiDong: ' . ($diemHoiDong ?? 'NULL'));

            // Format các điểm
            $diemHD_formatted = $diemHD !== null ? round($diemHD, 2) : '';
            $diemPB_formatted = $diemPB !== null ? round($diemPB, 2) : '';
            $diemHoiDong_formatted = $diemHoiDong !== null ? round($diemHoiDong, 2) : '';

            // Tính điểm tổng kết = (HD + PB + HĐ) / 3
            $diemTongKet = '';
            if ($diemHD !== null && $diemPB !== null && $diemHoiDong !== null) {
                $diemTongKet = round(($diemHD + $diemPB + $diemHoiDong) / 3, 2);
            }

            $danhSachTongKet[] = [
                'mssv' => $dt->mssv,
                'hoten' => $dt->hoten,
                'nhom' => $dt->nhom,
                'lop' => $dt->lop,
                'tendt' => $dt->tendt,
                'diem_hd' => $diemHD_formatted,
                'diem_pb' => $diemPB_formatted,
                'diem_hoidong' => $diemHoiDong_formatted,
                'diem_tongket' => $diemTongKet
            ];

            \Log::info('Added result: ' . json_encode([
                'mssv' => $dt->mssv,
                'diem_hd' => $diemHD_formatted,
                'diem_pb' => $diemPB_formatted,
                'diem_hoidong' => $diemHoiDong_formatted,
                'diem_tongket' => $diemTongKet
            ]));
        }

        \Log::info('danhSachTongKet final count: ' . count($danhSachTongKet));

        $khongCoDiem = empty($danhSachTongKet);

        return view('lecturers.tong-ket.index', [
            'danhSachTongKet' => $danhSachTongKet,
            'khongCoDiem' => $khongCoDiem,
            'magvHienTai' => $magv
        ]);
    }

    /**
     * Xuất Excel tổng kết
     */
    public function exportExcel()
    {
        $user = session('user');
        
        if (!$user || !isset($user->magv)) {
            return response()->json(['error' => 'User not found'], 401);
        }

        $magv = $user->magv;
        \Log::info('=== EXPORT EXCEL CALLED ===');
        \Log::info('magv: ' . $magv);

        try {
            \Log::info('Starting export...');
            $filepath = $this->exportService->exportExcelGVHuongDan($magv);
            \Log::info('Export successful, filepath: ' . $filepath);
            
            return response()->download($filepath, 'DiemTongKet_' . now()->format('YmdHis') . '.xlsx')
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error('Export Error: ' . $e->getMessage());
            \Log::error('Stack: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }
}