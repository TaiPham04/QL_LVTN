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
        
        $query = DB::table('hoidong_chamdiem as hc')
            ->join('hoidong as hd', 'hc.hoidong_id', '=', 'hd.id')
            ->join('nhom as n', 'hc.nhom_id', '=', 'n.id')
            ->join('sinhvien as sv', 'hc.mssv', '=', 'sv.mssv')
            ->select(
                'hc.id',
                'hd.id as hoidong_id',
                'hd.mahd',
                'hd.tenhd',
                'hc.nhom_id',
                'hc.mssv',
                'hc.diem_chu_tich',
                'hc.diem_thu_ky',
                'hc.diem_thanh_vien_1',
                'hc.diem_thanh_vien_2',
                'hc.diem_tong',
                'n.tennhom',
                'n.tendt',
                'sv.hoten as ten_sinh_vien',
                'sv.lop'
            );
        
        // Filter theo hội đồng
        if ($request->filled('hoidong_id')) {
            \Log::info('Filter by hoidong_id: ' . $request->hoidong_id);
            $query->where('hd.mahd', $request->hoidong_id);
        }
        
        // Tìm kiếm Tên nhóm / MSSV (sử dụng LIKE - wildcard)
        if ($request->filled('nhom_id')) {
            $search = $request->nhom_id;
            \Log::info('Search by nhom_id (LIKE): ' . $search);
            $query->where(function($q) use ($search) {
                $q->where('n.tennhom', 'LIKE', '%' . $search . '%')  // Tìm theo tên nhóm
                  ->orWhere('sv.mssv', 'LIKE', '%' . $search . '%');  // Hoặc tìm theo MSSV
            });
        }
        
        $loaiDiem = $request->loai_diem ?? 'all';
        
        $diemData = $query->distinct()
            ->orderBy('hd.mahd')
            ->orderBy('hc.nhom_id')
            ->get();
        
        \Log::info('diemData count: ' . $diemData->count());
        \Log::info('SQL Query: ' . $query->toSql());
        \Log::info('SQL Bindings: ' . json_encode($query->getBindings()));
        if ($diemData->count() > 0) {
            \Log::info('First record: ' . json_encode($diemData->first()));
        }
        
        // Xử lý dữ liệu
        $diem = $this->processScoreData($diemData, $loaiDiem);
        
        \Log::info('Final diem count: ' . $diem->count());
        if ($diem->count() > 0) {
            \Log::info('First processed item: ' . json_encode($diem->first()));
        }
        
        return view('admin.diem-tong.index', compact('diem', 'hoiDongs', 'loaiDiem'));
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
                    'diem_hd' => round($item->diem_chu_tich ?? 0, 2),
                    'diem_pb' => round($item->diem_thu_ky ?? 0, 2),
                    'diem_gv' => round($item->diem_thanh_vien_1 ?? 0, 2),
                    'diem_tong' => round($item->diem_tong ?? 0, 2),
                ];
            }
        }
        
        return collect($processed)->values();
    }
    
    // Xuất Excel
    public function exportExcel(Request $request)
    {
        \Log::info('=== exportExcel CALLED ===');
        \Log::info('Request: ' . json_encode($request->all()));
        
        $hoiDongId = $request->hoidong_id;
        $nhomId = $request->nhom_id;
        $loaiDiem = $request->loai_diem ?? 'all';
        
        $query = DB::table('hoidong_chamdiem as hc')
            ->join('hoidong as hd', 'hc.hoidong_id', '=', 'hd.id')
            ->join('nhom as n', 'hc.nhom_id', '=', 'n.id')
            ->join('sinhvien as sv', 'hc.mssv', '=', 'sv.mssv')
            ->select(
                'hc.id',
                'hd.mahd',
                'hd.tenhd',
                'hc.nhom_id',
                'hc.mssv',
                'hc.diem_chu_tich',
                'hc.diem_thu_ky',
                'hc.diem_thanh_vien_1',
                'hc.diem_thanh_vien_2',
                'hc.diem_tong',
                'n.tennhom',
                'n.tendt',
                'sv.hoten as ten_sinh_vien',
                'sv.lop'
            );
        
        if ($hoiDongId && $hoiDongId !== '') {
            $query->where('hd.mahd', $hoiDongId);
        }
        
        // Tìm kiếm Tên nhóm / MSSV (sử dụng LIKE - wildcard)
        if ($nhomId && $nhomId !== '') {
            $query->where(function($q) use ($nhomId) {
                $q->where('n.tennhom', 'LIKE', '%' . $nhomId . '%')  // Tìm theo tên nhóm
                  ->orWhere('sv.mssv', 'LIKE', '%' . $nhomId . '%');  // Hoặc tìm theo MSSV
            });
        }
        
        $diemData = $query->distinct()
            ->orderBy('hd.mahd')
            ->orderBy('hc.nhom_id')
            ->get();
        
        \Log::info('Export diemData count: ' . $diemData->count());
        
        // Convert dữ liệu
        $processedDiem = [];
        foreach ($diemData as $item) {
            \Log::info('Export item: ' . json_encode($item));
            
            $key = $item->nhom_id . '_' . $item->mssv;
            if (!isset($processedDiem[$key])) {
                $processedDiem[$key] = [
                    'mahd' => $item->mahd,
                    'tenhd' => $item->tenhd,
                    'nhom_id' => $item->nhom_id,
                    'tennhom' => $item->tennhom,
                    'tendt' => $item->tendt,
                    'mssv' => $item->mssv,
                    'ten_sinh_vien' => $item->ten_sinh_vien,
                    'lop' => $item->lop,
                    'diem_hd' => round($item->diem_chu_tich ?? 0, 2),
                    'diem_pb' => round($item->diem_thu_ky ?? 0, 2),
                    'diem_gv' => round($item->diem_thanh_vien_1 ?? 0, 2),
                    'diem_tong' => round($item->diem_tong ?? 0, 2),
                ];
                
                \Log::info('Row ' . ($key) . ' - lop: ' . $item->lop . ', diem_hd: ' . ($processedDiem[$key]['diem_hd'] ?? 'NULL'));
            }
        }
        
        $hoiDong = HoiDong::where('mahd', $hoiDongId)->first();
        
        $exporter = new DiemTongKetExcelExporter();
        return $exporter->export(collect($processedDiem)->values(), $hoiDong);
    }
}
?>