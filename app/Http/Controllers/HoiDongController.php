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
            ->leftJoin('thanhvienhoidong as tv', 'h.mahd', '=', 'tv.mahd')
            ->leftJoin('hoidong_detai as hd', 'h.mahd', '=', 'hd.mahd')
            ->select(
                'h.mahd',
                'h.tenhd',
                'h.trang_thai',
                'h.ghi_chu',
                DB::raw('COUNT(DISTINCT tv.magv) as so_thanh_vien'),
                DB::raw('COUNT(DISTINCT hd.nhom) as so_de_tai')
            )
            ->groupBy('h.mahd', 'h.tenhd', 'h.trang_thai', 'h.ghi_chu')
            ->orderBy('h.mahd', 'desc')
            ->get();

        return view('hoidong.index', compact('danhSachHoiDong'));
    }

    /**
     * Form tạo hội đồng mới
     */
    public function create()
    {
        // Lấy danh sách tất cả giảng viên
        $danhSachGiangVien = DB::table('giangvien')
            ->select('magv', 'hoten', 'email')
            ->orderBy('hoten')
            ->get();

        return view('hoidong.create', compact('danhSachGiangVien'));
    }

    /**
     * Lưu hội đồng mới
     * ✅ CHỈNH: Cho phép 3-4 thành viên
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

        // ✅ MỚI: Kiểm tra phải có 1 Chủ tịch và 1 Thư ký
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
            HoiDong::create([
                'mahd' => $request->mahd,
                'tenhd' => $request->tenhd,
                'ghi_chu' => $request->ghi_chu,
                'trang_thai' => 'dang_mo'
            ]);

            // ✅ CHỈNH: Thêm thành viên với vai trò
            foreach ($request->thanh_vien as $index => $magv) {
                ThanhVienHoiDong::create([
                    'mahd' => $request->mahd,
                    'magv' => $magv,
                    'vai_tro' => $request->vai_tro[$index] ?? 'thanh_vien'  // ✅ Lưu vai trò
                ]);
            }

            DB::commit();
            return redirect()->route('admin.hoidong.index')
                ->with('success', 'Tạo hội đồng thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Xem chi tiết hội đồng
     */
    public function show($mahd)
    {
        $hoiDong = HoiDong::findOrFail($mahd);

        // Lấy thành viên
        $thanhVien = DB::table('thanhvienhoidong as tv')
            ->join('giangvien as g', 'tv.magv', '=', 'g.magv')
            ->where('tv.mahd', $mahd)
            ->select('g.magv', 'g.hoten', 'g.email', 'tv.vai_tro')
            ->get();

        // Lấy đề tài đã phân công
        $deTai = DB::table('hoidong_detai as hd')
            ->join('detai as d', 'hd.nhom', '=', 'd.nhom')
            ->leftJoin('giangvien as g', 'd.magv', '=', 'g.magv')
            ->where('hd.mahd', $mahd)
            ->select('d.nhom', 'd.tendt', 'd.magv', 'g.hoten as gv_huongdan')
            ->distinct()
            ->get();

        // Lấy sinh viên trong các nhóm
        $sinhVienTheoNhom = [];
        foreach ($deTai as $dt) {
            $sinhVienTheoNhom[$dt->nhom] = Detai::getSinhVienByNhom($dt->nhom);
        }

        return view('hoidong.show', compact('hoiDong', 'thanhVien', 'deTai', 'sinhVienTheoNhom'));
    }

    /**
     * Xóa hội đồng
     */
    public function destroy($mahd)
    {
        try {
            $hoiDong = HoiDong::findOrFail($mahd);
            
            $soDeTai = HoiDongDeTai::where('mahd', $mahd)->count();
            
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
    public function phanCongForm($mahd)
    {
        $hoiDong = HoiDong::findOrFail($mahd);

        // ✅ CHỈNH: Kiểm tra hội đồng đã đủ 3 thành viên chưa (không thay đổi logic)
        if ($hoiDong->thanhVien()->count() < 3) {
            return back()->with('error', 'Hội đồng chưa đủ 3 thành viên!');
        }

        // Lấy danh sách mã GV trong hội đồng
        $magvTrongHoiDong = $hoiDong->thanhVien()->pluck('magv')->toArray();

        // Lấy đề tài CHƯA phân công
        $deTaiKhaDung = DB::table('detai as d')
            ->leftJoin('hoidong_detai as hd', 'd.nhom', '=', 'hd.nhom')
            ->leftJoin('giangvien as g', 'd.magv', '=', 'g.magv')
            ->whereNotNull('d.nhom')
            ->whereNull('hd.nhom')
            ->whereNotIn('d.magv', $magvTrongHoiDong)
            ->select('d.nhom', 'd.tendt', 'd.magv', 'g.hoten as gv_huongdan')
            ->distinct()
            ->get();

        // Lấy sinh viên cho mỗi nhóm
        $sinhVienTheoNhom = [];
        foreach ($deTaiKhaDung as $dt) {
            $sinhVienTheoNhom[$dt->nhom] = Detai::getSinhVienByNhom($dt->nhom);
        }

        // Đề tài đã phân công
        $deTaiDaPhanCong = DB::table('hoidong_detai as hd')
            ->join('detai as d', 'hd.nhom', '=', 'd.nhom')
            ->leftJoin('giangvien as g', 'd.magv', '=', 'g.magv')
            ->where('hd.mahd', $mahd)
            ->select('d.nhom', 'd.tendt', 'd.magv', 'g.hoten as gv_huongdan')
            ->distinct()
            ->get();

        $sinhVienDaPhanCong = [];
        foreach ($deTaiDaPhanCong as $dt) {
            $sinhVienDaPhanCong[$dt->nhom] = Detai::getSinhVienByNhom($dt->nhom);
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
    public function phanCongStore(Request $request, $mahd)
    {
        $request->validate([
            'nhom' => 'required|array|min:1',
            'nhom.*' => 'required|exists:detai,nhom',
        ], [
            'nhom.required' => 'Vui lòng chọn ít nhất 1 đề tài',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->nhom as $nhom) {
                $exists = HoiDongDeTai::where('nhom', $nhom)->exists();
                if ($exists) {
                    DB::rollBack();
                    return back()->with('error', "Nhóm {$nhom} đã được phân công cho hội đồng khác!");
                }

                HoiDongDeTai::create([
                    'mahd' => $mahd,
                    'nhom' => $nhom
                ]);
            }

            DB::commit();
            return back()->with('success', 'Phân công đề tài thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Xóa đề tài khỏi hội đồng
     */
    public function phanCongDelete($mahd, $nhom)
    {
        try {
            HoiDongDeTai::where('mahd', $mahd)
                ->where('nhom', $nhom)
                ->delete();

            return back()->with('success', 'Đã xóa đề tài khỏi hội đồng!');
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Export Excel danh sách đề tài của hội đồng
     */
    public function exportExcel($mahd)
    {
        try {
            $exporter = new HoiDongExcelExporter();
            $filePath = $exporter->export($mahd);
            
            return response()->download($filePath)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Không thể xuất file Excel: ' . $e->getMessage());
        }
    }
}
?>