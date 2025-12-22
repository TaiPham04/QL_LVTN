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

        $hoiDongList = DB::table('thanhvienhoidong as tv')
            ->join('hoidong as hd', 'tv.hoidong_id', '=', 'hd.id')
            ->where('tv.magv', $magv)
            ->select('hd.id as hoidong_id', 'hd.mahd', 'hd.tenhd', 'tv.vai_tro')
            ->get();

        $danhSachTongKet = [];

        foreach ($hoiDongList as $hd) {
            try {
                $sinhVienDiem = $this->exportService->getDanhSachSinhVienDiem($hd->hoidong_id);
                
                if (!$sinhVienDiem->isEmpty()) {
                    $danhSachTongKet[] = [
                        'hoiDong' => $hd,
                        'sinhVienDiem' => $sinhVienDiem
                    ];
                }
            } catch (\Exception $e) {
                \Log::warning('Error getting diem for hoidong_id: ' . $hd->hoidong_id . ' - ' . $e->getMessage());
            }
        }

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
    public function exportExcel($hoidong_id)
    {
        \Log::info('=== EXPORT EXCEL CALLED ===');
        \Log::info('hoidong_id: ' . $hoidong_id);
        \Log::info('Session user: ' . json_encode(session('user')));

        $user = session('user');
        
        if (!$user) {
            \Log::error('User not found in session');
            return response()->json(['error' => 'User not found'], 401);
        }

        if (!isset($user->magv)) {
            \Log::error('magv not in user object');
            return response()->json(['error' => 'magv not found'], 401);
        }

        $magv = $user->magv;
        \Log::info('magv: ' . $magv);

        // Kiểm tra giảng viên có trong hội đồng này không - ĐÚNG QUERY
        $vaiTro = DB::table('thanhvienhoidong as tv')
            ->join('hoidong as hd', 'tv.hoidong_id', '=', 'hd.id')
            ->where('hd.id', $hoidong_id)
            ->where('tv.magv', $magv)
            ->value('tv.vai_tro');

        \Log::info('vai_tro: ' . ($vaiTro ?? 'NULL'));

        if (!$vaiTro) {
            \Log::error('Teacher not in council or council not exist');
            return response()->json(['error' => 'Bạn không có trong hội đồng này!'], 403);
        }

        // Chỉ thư ký và chủ tịch mới xuất được
        if (!in_array($vaiTro, ['chu_tich', 'thu_ky'])) {
            \Log::error('Permission denied. vai_tro: ' . $vaiTro);
            return response()->json(['error' => 'Chỉ chủ tịch và thư ký mới có thể xuất Excel!'], 403);
        }

        try {
            \Log::info('Starting export...');
            $filepath = $this->exportService->exportExcel($hoidong_id);
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