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
        \Log::info('=== TongKet Index ===');
        \Log::info('magv: ' . $magv);

        // Lấy danh sách hội đồng của giảng viên
        $hoiDongList = DB::table('thanhvienhoidong as tv')
            ->join('hoidong as hd', 'tv.hoidong_id', '=', 'hd.id')
            ->where('tv.magv', $magv)
            ->select('hd.id as hoidong_id', 'hd.mahd', 'hd.tenhd', 'tv.vai_tro')
            ->get();

        \Log::info('hoiDongList count: ' . $hoiDongList->count());
        \Log::info('hoiDongList: ' . json_encode($hoiDongList));

        $danhSachTongKet = [];

        foreach ($hoiDongList as $hd) {
            \Log::info('Processing hoidong_id: ' . $hd->hoidong_id . ', mahd: ' . $hd->mahd);
            
            try {
                $sinhVienDiem = $this->exportService->getDanhSachSinhVienDiem($hd->hoidong_id);
                
                \Log::info('sinhVienDiem count: ' . $sinhVienDiem->count());
                
                if (!$sinhVienDiem->isEmpty()) {
                    $danhSachTongKet[] = [
                        'hoiDong' => $hd,
                        'sinhVienDiem' => $sinhVienDiem
                    ];
                    \Log::info('Added to danhSachTongKet');
                } else {
                    \Log::warning('sinhVienDiem is empty for hoidong_id: ' . $hd->hoidong_id);
                }
            } catch (\Exception $e) {
                \Log::warning('Error getting diem for hoidong_id: ' . $hd->hoidong_id . ' - ' . $e->getMessage());
            }
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

        // Kiểm tra giảng viên có trong hội đồng này không
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