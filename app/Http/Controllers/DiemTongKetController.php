<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HoiDong;
use App\Models\HoiDongChamDiem;
use Illuminate\Support\Facades\DB;
use App\Services\DiemTongKetExcelExporter;

class DiemTongKetController extends Controller
{
    // Xem điểm tổng kết
    public function index(Request $request)
    {
        \Log::info('=== DiemTongKetController@index ===');
        \Log::info('Request params: ' . json_encode($request->all()));
        
        $hoiDongs = HoiDong::select('id', 'mahd', 'tenhd', 'ngay_hoidong')
            ->orderBy('ngay_hoidong', 'desc')
            ->get();
        
        \Log::info('hoiDongs count: ' . $hoiDongs->count());
        
        // ✅ Lấy tất cả nhóm được phân công (từ hoidong_detai)
        $query = $this->buildDiemQuery();
        
        // Filter theo hội đồng
        if ($request->filled('hoidong_id')) {
            \Log::info('Filter by hoidong_id: ' . $request->hoidong_id);
            $query->where('hd.mahd', $request->hoidong_id);
        }
        
        // Tìm kiếm Tên nhóm / MSSV
        if ($request->filled('nhom_id')) {
            $search = $request->nhom_id;
            \Log::info('Search by nhom_id (LIKE): ' . $search);
            $query->where(function($q) use ($search) {
                $q->where('n.tennhom', 'LIKE', '%' . $search . '%')
                  ->orWhere('dt.mssv', 'LIKE', '%' . $search . '%');
            });
        }
        
        $loaiDiem = $request->loai_diem ?? 'all';
        
        $diemData = $query->distinct()
            ->orderBy('hd.mahd')
            ->orderBy('hdt.thu_tu')
            ->get();
        
        \Log::info('diemData count: ' . $diemData->count());
        
        // Xử lý dữ liệu
        $diem = $this->processScoreData($diemData, $loaiDiem);
        
        \Log::info('Final diem count: ' . $diem->count());
        
        return view('admin.diem-tong.index', compact('diem', 'hoiDongs', 'loaiDiem'));
    }
    
    // ✅ Build query chung cho cả index và export
    private function buildDiemQuery()
    {
        return DB::table('hoidong_detai as hdt')
            ->join('hoidong as hd', 'hdt.hoidong_id', '=', 'hd.id')
            ->join('nhom as n', 'hdt.nhom_id', '=', 'n.id')
            ->leftJoin('detai as dt', 'n.id', '=', 'dt.nhom_id')
            ->leftJoin('sinhvien as sv', 'dt.mssv', '=', 'sv.mssv')
            ->leftJoin('hoidong_chamdiem as hc', function($join) {
                $join->on('hdt.hoidong_id', '=', 'hc.hoidong_id')
                     ->on('hdt.nhom_id', '=', 'hc.nhom_id');
            })
            // ✅ JOIN lấy điểm hướng dẫn
            ->leftJoin('phieu_cham_diem as pcd_hd', function($join) {
                $join->on('n.id', '=', 'pcd_hd.nhom_id')
                     ->where('pcd_hd.loai_phieu', '=', 'huong_dan');
            })
            ->leftJoin('diem_sinh_vien as dsv_hd', function($join) {
                $join->on('pcd_hd.id', '=', 'dsv_hd.phieu_cham_id')
                     ->on('dt.mssv', '=', 'dsv_hd.mssv');
            })
            // ✅ JOIN lấy điểm phản biện
            ->leftJoin('phieu_cham_diem as pcd_pb', function($join) {
                $join->on('n.id', '=', 'pcd_pb.nhom_id')
                     ->where('pcd_pb.loai_phieu', '=', 'phan_bien');
            })
            ->leftJoin('diem_sinh_vien as dsv_pb', function($join) {
                $join->on('pcd_pb.id', '=', 'dsv_pb.phieu_cham_id')
                     ->on('dt.mssv', '=', 'dsv_pb.mssv');
            })
            // ✅ JOIN lấy tên GVHD
            ->leftJoin('giangvien as gv_hd', 'dt.magv', '=', 'gv_hd.magv')
            // ✅ JOIN lấy tên GVPB
            ->leftJoin('phancong_phanbien as ppb', 'n.id', '=', 'ppb.nhom_id')
            ->leftJoin('giangvien as gv_pb', 'ppb.magv_phanbien', '=', 'gv_pb.magv')
            ->select(
                'hdt.hoidong_id',
                'hd.id as hd_id',
                'hd.mahd',
                'hd.tenhd',
                'hdt.nhom_id',
                'hdt.thu_tu as ttbc',
                'n.tennhom',
                'n.tendt',
                DB::raw('COALESCE(dt.mssv, "") as mssv'),
                DB::raw('COALESCE(sv.hoten, "") as ten_sinh_vien'),
                DB::raw('COALESCE(sv.lop, "") as lop'),
                DB::raw('COALESCE(hc.diem_chu_tich, 0) as diem_chu_tich'),
                DB::raw('COALESCE(hc.diem_thu_ky, 0) as diem_thu_ky'),
                DB::raw('COALESCE(hc.diem_thanh_vien_1, 0) as diem_thanh_vien_1'),
                DB::raw('COALESCE(hc.diem_thanh_vien_2, 0) as diem_thanh_vien_2'),
                DB::raw('COALESCE(hc.diem_tong, 0) as diem_tong'),
                DB::raw('COALESCE(dsv_hd.diem_tong, 0) as diem_hd'),
                DB::raw('COALESCE(dsv_pb.diem_tong, 0) as diem_pb'),
                DB::raw('COALESCE(gv_hd.hoten, "-") as ten_gvhd'),
                DB::raw('COALESCE(gv_pb.hoten, "-") as ten_gvpb')
            );
    }
    
    // Xử lý dữ liệu điểm
    private function processScoreData($data, $loaiDiem)
    {
        $processed = [];
        
        foreach ($data as $item) {
            $key = $item->nhom_id . '_' . $item->mssv;
            
            if (!isset($processed[$key])) {
                $processed[$key] = [
                    'mahd' => $item->mahd,
                    'tenhd' => $item->tenhd,
                    'nhom_id' => $item->nhom_id,
                    'tennhom' => $item->tennhom,
                    'tendt' => $item->tendt,
                    'mssv' => $item->mssv,
                    'ten_sinh_vien' => $item->ten_sinh_vien,
                    'lop' => $item->lop,
                    // ✅ Lấy ttbc từ database
                    'ttbc' => intval($item->ttbc),
                    'diem_hd' => round(floatval($item->diem_hd ?? 0), 2),
                    'diem_pb' => round(floatval($item->diem_pb ?? 0), 2),
                    'ten_gvhd' => $item->ten_gvhd ?? '-',
                    'ten_gvpb' => $item->ten_gvpb ?? '-',
                    // Giữ lại
                    'diem_gv' => round(floatval($item->diem_thanh_vien_1 ?? 0), 2),
                    'diem_tong' => round(floatval($item->diem_tong ?? 0), 2),
                ];
            }
        }
        
        return collect($processed)->values();
    }
    
    // ✅ Xuất Excel - SỬA LẠI
    public function exportExcel(Request $request)
    {
        \Log::info('=== exportExcel CALLED ===');
        \Log::info('Request: ' . json_encode($request->all()));
        
        $hoiDongId = $request->hoidong_id;
        $nhomId = $request->nhom_id;
        
        // ✅ Sử dụng query chung
        $query = $this->buildDiemQuery();
        
        // Filter theo hội đồng
        if ($hoiDongId && $hoiDongId !== '') {
            $query->where('hd.mahd', $hoiDongId);
        }
        
        // Tìm kiếm Tên nhóm / MSSV
        if ($nhomId && $nhomId !== '') {
            $query->where(function($q) use ($nhomId) {
                $q->where('n.tennhom', 'LIKE', '%' . $nhomId . '%')
                  ->orWhere('dt.mssv', 'LIKE', '%' . $nhomId . '%');
            });
        }
        
        $diemData = $query->distinct()
            ->orderBy('hd.mahd')
            ->orderBy('hdt.thu_tu')
            ->get();
        
        \Log::info('Export diemData count: ' . $diemData->count());
        
        // Xử lý dữ liệu
        $processedDiem = $this->processScoreData($diemData, 'all');
        
        \Log::info('Processed diem count: ' . $processedDiem->count());
        
        foreach ($processedDiem as $item) {
            \Log::info('Export item: ' . json_encode($item));
        }
        
        $hoiDong = HoiDong::where('mahd', $hoiDongId)->first();
        
        $exporter = new DiemTongKetExcelExporter();
        return $exporter->export($processedDiem, $hoiDong);
    }
}
?>