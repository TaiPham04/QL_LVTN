<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{HoiDong, ThanhVienHoiDong, HoiDongDeTai, Lecturer, Detai};
use Illuminate\Support\Facades\DB;
use App\Services\HoiDongExcelExporter;

class HoiDongController extends Controller
{
    // =============================================
    // QUẢN LÝ HỘI ĐỒNG
    // =============================================

    /**
     * Danh sách hội đồng
     */
    public function index()
    {
        $danhSachHoiDong = DB::table('hoidong as h')
            ->leftJoin('thanhvienhoidong as tv', 'h.id', '=', 'tv.hoidong_id')
            ->leftJoin('hoidong_detai as hd', 'h.id', '=', 'hd.hoidong_id')
            ->select(
                'h.id',
                'h.mahd',
                'h.tenhd',
                'h.trang_thai',
                'h.ghi_chu',
                DB::raw('COUNT(DISTINCT tv.magv) as so_thanh_vien'),
                DB::raw('COUNT(DISTINCT hd.nhom_id) as so_de_tai')
            )
            ->groupBy('h.id', 'h.mahd', 'h.tenhd', 'h.trang_thai', 'h.ghi_chu')
            ->orderBy('h.mahd', 'desc')
            ->get();

        return view('hoidong.index', compact('danhSachHoiDong'));
    }

    
    /**
     * Form tạo hội đồng mới
     */
    public function create()
    {
        // ✅ FIX: Chỉ lấy giảng viên CHƯA nằm trong hội đồng nào
        $danhSachGiangVien = DB::table('giangvien as g')
            ->leftJoin('thanhvienhoidong as tv', 'g.magv', '=', 'tv.magv')
            ->whereNull('tv.id')  // CHƯA nằm trong hội đồng nào
            ->select('g.magv', 'g.hoten', 'g.email')
            ->orderBy('g.hoten')
            ->distinct()
            ->get();

        return view('hoidong.create', compact('danhSachGiangVien'));
    }

    /**
     * Lưu hội đồng mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'mahd' => 'required|unique:hoidong,mahd|max:20',
            'tenhd' => 'required|max:255',
            'thanh_vien' => 'required|array|min:3|max:4',
            'thanh_vien.*' => 'required|exists:giangvien,magv',
            'vai_tro' => 'required|array',
            'vai_tro.*' => 'required|in:chu_tich,thu_ky,thanh_vien',
        ], [
            'mahd.required' => 'Vui lòng nhập mã hội đồng',
            'mahd.unique' => 'Mã hội đồng đã tồn tại',
            'tenhd.required' => 'Vui lòng nhập tên hội đồng',
            'thanh_vien.required' => 'Vui lòng chọn thành viên hội đồng',
            'thanh_vien.min' => 'Hội đồng phải có tối thiểu 3 thành viên',
            'thanh_vien.max' => 'Hội đồng tối đa 4 thành viên',
        ]);

        // Kiểm tra không có thành viên trùng
        if (count($request->thanh_vien) !== count(array_unique($request->thanh_vien))) {
            return back()->withInput()->with('error', 'Không được chọn trùng giảng viên!');
        }

        // Kiểm tra phải có 1 Chủ tịch và 1 Thư ký
        $chuTichCount = count(array_filter($request->vai_tro, function($vt) { return $vt === 'chu_tich'; }));
        $thuKyCount = count(array_filter($request->vai_tro, function($vt) { return $vt === 'thu_ky'; }));

        if ($chuTichCount !== 1) {
            return back()->withInput()->with('error', 'Hội đồng phải có đúng 1 Chủ tịch!');
        }
        if ($thuKyCount !== 1) {
            return back()->withInput()->with('error', 'Hội đồng phải có đúng 1 Thư ký!');
        }

        DB::beginTransaction();
        try {
            // Tạo hội đồng
            $hoiDong = HoiDong::create([
                'mahd' => $request->mahd,
                'tenhd' => $request->tenhd,
                'ghi_chu' => $request->ghi_chu,
                'trang_thai' => 'dang_mo'
            ]);

            // Thêm thành viên
            foreach ($request->thanh_vien as $index => $magv) {
                DB::table('thanhvienhoidong')->insert([
                    'hoidong_id' => $hoiDong->id,
                    'mahd' => $request->mahd,
                    'magv' => $magv,
                    'vai_tro' => $request->vai_tro[$index] ?? 'thanh_vien',
                    'created_at' => now()
                ]);
            }

            DB::commit();
            return redirect()->route('admin.hoidong.index')
                ->with('success', 'Tạo hội đồng thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Lỗi tạo hội đồng: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Xem chi tiết hội đồng
     */
    public function show($id)
    {
        $hoiDong = HoiDong::findOrFail($id);

        // Lấy thành viên
        $thanhVien = DB::table('thanhvienhoidong as tv')
            ->join('giangvien as g', 'tv.magv', '=', 'g.magv')
            ->where('tv.hoidong_id', $id)
            ->select('g.magv', 'g.hoten', 'g.email', 'tv.vai_tro')
            ->get();

        // Lấy đề tài đã phân công
        $deTai = DB::table('hoidong_detai as hd')
            ->join('nhom as n', 'hd.nhom_id', '=', 'n.id')
            ->join('detai as d', 'hd.mdt', '=', 'd.madt')
            ->leftJoin('giangvien as g', 'd.magv', '=', 'g.magv')
            ->where('hd.hoidong_id', $id)
            ->select('n.id as nhom_id', 'n.tennhom as nhom', 'n.tendt', 'd.magv', 'g.hoten as gv_huongdan')
            ->distinct()
            ->get();

        // Lấy sinh viên trong các nhóm
        $sinhVienTheoNhom = [];
        foreach ($deTai as $dt) {
            $sinhVienTheoNhom[$dt->nhom_id] = Detai::where('nhom_id', $dt->nhom_id)->get();
        }

        return view('hoidong.show', compact('hoiDong', 'thanhVien', 'deTai', 'sinhVienTheoNhom'));
    }

    /**
     * Xóa hội đồng
     */
    public function destroy($id)
    {
        try {
            $hoiDong = HoiDong::findOrFail($id);
            
            $soDeTai = HoiDongDeTai::where('hoidong_id', $id)->count();
            
            if ($soDeTai > 0) {
                return back()->with('error', 
                    "Không thể xóa hội đồng này! Hội đồng đã có {$soDeTai} đề tài được phân công. " .
                    "Vui lòng xóa hết đề tài trước khi xóa hội đồng."
                );
            }
            
            $hoiDong->delete();
            
            return back()->with('success', 'Xóa hội đồng thành công!');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Không thể xóa hội đồng: ' . $e->getMessage());
        }
    }

    // =============================================
    // PHÂN CÔNG ĐỀ TÀI
    // =============================================

    /**
     * Form phân công đề tài cho hội đồng
     */
    public function phanCongForm($id)
    {
        $hoiDong = HoiDong::findOrFail($id);

        // Kiểm tra hội đồng đã đủ 3 thành viên chưa
        if ($hoiDong->thanhVien()->count() < 3) {
            return back()->with('error', 'Hội đồng chưa đủ 3 thành viên!');
        }

        // ✅ FIX: Lấy danh sách mã GV trong hội đồng
        $magvTrongHoiDong = $hoiDong->thanhVien()->pluck('magv')->toArray();

        // ✅ FIX: Lấy đề tài CHƯA phân công 
        // VÀ GV hướng dẫn KHÔNG trong hội đồng
        // VÀ GV phản biện KHÔNG trong hội đồng
        $deTaiKhaDung = DB::table('detai as d')
            ->join('nhom as n', 'd.nhom_id', '=', 'n.id')
            ->leftJoin('hoidong_detai as hd', 'n.id', '=', 'hd.nhom_id')
            ->leftJoin('giangvien as g', 'd.magv', '=', 'g.magv')
            ->leftJoin('phancong_phanbien as ppb', 'n.id', '=', 'ppb.nhom_id')
            ->whereNotNull('d.nhom_id')
            ->whereNull('hd.nhom_id')  // CHƯA phân công
            ->whereNotIn('d.magv', $magvTrongHoiDong)  // GV HD KHÔNG trong hội đồng
            ->whereRaw("(ppb.magv_phanbien IS NULL OR ppb.magv_phanbien NOT IN ('" . implode("','", $magvTrongHoiDong) . "'))")  // GV PB KHÔNG trong hội đồng
            ->select('n.id as nhom_id', 'n.tennhom as nhom', 'n.tendt', 'd.magv', 'g.hoten as gv_huongdan')
            ->distinct()
            ->get();

        // Lấy sinh viên cho mỗi nhóm
        $sinhVienTheoNhom = [];
        foreach ($deTaiKhaDung as $dt) {
            $sinhVienTheoNhom[$dt->nhom_id] = Detai::where('nhom_id', $dt->nhom_id)->get();
        }

        // Đề tài đã phân công cho hội đồng này
        $deTaiDaPhanCong = DB::table('hoidong_detai as hd')
            ->join('nhom as n', 'hd.nhom_id', '=', 'n.id')
            ->join('detai as d', 'hd.mdt', '=', 'd.madt')
            ->leftJoin('giangvien as g', 'd.magv', '=', 'g.magv')
            ->where('hd.hoidong_id', $id)
            ->select('n.id as nhom_id', 'n.tennhom as nhom', 'n.tendt', 'd.magv', 'g.hoten as gv_huongdan')
            ->distinct()
            ->get();

        $sinhVienDaPhanCong = [];
        foreach ($deTaiDaPhanCong as $dt) {
            $sinhVienDaPhanCong[$dt->nhom_id] = Detai::where('nhom_id', $dt->nhom_id)->get();
        }

        return view('hoidong.phan-cong', compact(
            'hoiDong',
            'deTaiKhaDung',
            'sinhVienTheoNhom',
            'deTaiDaPhanCong',
            'sinhVienDaPhanCong'
        ));
    }

    /**
     * Lưu phân công đề tài
     */
    public function phanCongStore(Request $request, $id)
    {
        $request->validate([
            'nhom_id' => 'required|array|min:1',
            'nhom_id.*' => 'required|exists:nhom,id',
        ], [
            'nhom_id.required' => 'Vui lòng chọn ít nhất 1 đề tài',
        ]);

        DB::beginTransaction();
        try {
            $hoiDong = HoiDong::findOrFail($id);

            foreach ($request->nhom_id as $nhom_id) {
                $exists = HoiDongDeTai::where('nhom_id', $nhom_id)->exists();
                if ($exists) {
                    DB::rollBack();
                    return back()->with('error', "Nhóm này đã được phân công cho hội đồng khác!");
                }

                // Lấy mdt từ detai
                $detai = DB::table('detai')->where('nhom_id', $nhom_id)->first();
                
                if (!$detai) {
                    DB::rollBack();
                    return back()->with('error', "Không tìm thấy đề tài cho nhóm này!");
                }

                // Thêm hoidong_id vào insert
                DB::table('hoidong_detai')->insert([
                    'hoidong_id' => $hoiDong->id,
                    'nhom_id' => $nhom_id,
                    'mdt' => $detai->madt,
                    'created_at' => now()
                ]);
            }

            DB::commit();
            return back()->with('success', 'Phân công đề tài thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Lỗi phân công đề tài: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Xóa đề tài khỏi hội đồng
     */
    public function phanCongDelete($hoidong_id, $nhom_id)
    {
        try {
            // Kiểm tra hội đồng đã chấm điểm cho nhóm này chưa
            $diemHoiDong = DB::table('hoidong_chamdiem')
                ->where('hoidong_id', $hoidong_id)
                ->where('nhom_id', $nhom_id)
                ->exists();

            if ($diemHoiDong) {
                return redirect()->back()->with('error', 'Không thể xóa đề tài này! Hội đồng đã chấm điểm cho nhóm này rồi.');
            }

            // Xóa từ bảng hoidong_detai
            DB::table('hoidong_detai')
                ->where('hoidong_id', $hoidong_id)
                ->where('nhom_id', $nhom_id)
                ->delete();

            return redirect()->back()->with('success', 'Xóa đề tài thành công!');

        } catch (\Exception $e) {
            \Log::error('Lỗi xóa đề tài: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Export Excel danh sách đề tài của hội đồng
     */
    public function exportExcel($id)
    {
        try {
            $exporter = new HoiDongExcelExporter();
            $filePath = $exporter->export($id);
            
            return response()->download($filePath)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Không thể xuất file Excel: ' . $e->getMessage());
        }
    }
}