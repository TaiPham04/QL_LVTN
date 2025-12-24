<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ChamDiemHoiDongExport;

class ChamDiemHoiDongController extends Controller
{
    /**
     * Danh sách hội đồng
     */
    public function index()
    {
        $user = session('user');
        $magv = $user->magv ?? null;

        $danhSachHoiDong = DB::table('thanhvienhoidong as tv')
            ->join('hoidong as hd', 'tv.hoidong_id', '=', 'hd.id')
            ->leftJoin('hoidong_detai as hdt', 'hd.id', '=', 'hdt.hoidong_id')
            ->where('tv.magv', $magv)
            ->select('hd.id as hoidong_id', 'hd.mahd', 'hd.tenhd', 'tv.vai_tro', DB::raw('COUNT(DISTINCT hdt.nhom_id) as so_detai'))
            ->groupBy('hd.id', 'hd.mahd', 'hd.tenhd', 'tv.vai_tro')
            ->orderBy('hd.tenhd')
            ->get();

        $khongCoHoiDong = $danhSachHoiDong->isEmpty();

        return view('lecturers.cham-diem.hoi-dong.index', compact('danhSachHoiDong', 'khongCoHoiDong'));
    }

    /**
     * Form chấm điểm
     */
    public function form($mahd)
    {
        $user = session('user');
        $magv = $user->magv ?? null;

        // Lấy hội đồng
        $hoiDong = DB::table('hoidong')
            ->where('mahd', $mahd)
            ->first();

        if (!$hoiDong) {
            return back()->with('error', 'Hội đồng không tồn tại!');
        }

        $hoidong_id = $hoiDong->id;

        // Kiểm tra quyền và lấy vai trò của GV
        $vaiTroGV = DB::table('thanhvienhoidong')
            ->where('hoidong_id', $hoidong_id)
            ->where('magv', $magv)
            ->value('vai_tro');

        if (!$vaiTroGV) {
            return back()->with('error', 'Bạn không có trong hội đồng này!');
        }

        // Lấy danh sách thành viên hội đồng
        $thanhVien = DB::table('thanhvienhoidong as tv')
            ->join('giangvien as gv', 'tv.magv', '=', 'gv.magv')
            ->where('tv.hoidong_id', $hoidong_id)
            ->select('tv.magv', 'gv.hoten', 'tv.vai_tro')
            ->orderBy('tv.vai_tro')
            ->get();

        // Lấy danh sách đề tài
        $deTaiList = DB::table('hoidong_detai as hdt')
            ->join('nhom as n', 'hdt.nhom_id', '=', 'n.id')
            ->where('hdt.hoidong_id', $hoidong_id)
            ->select('n.id as nhom_id', 'n.tennhom as nhom', 'n.tendt')
            ->distinct()
            ->get();

        // Lấy sinh viên theo nhóm
        $sinhVienNhom = [];
        foreach ($deTaiList as $deTai) {
            $sinhVienNhom[$deTai->nhom_id] = DB::table('detai as dt')
                ->join('sinhvien as sv', 'dt.mssv', '=', 'sv.mssv')
                ->where('dt.nhom_id', $deTai->nhom_id)
                ->select('sv.mssv', 'sv.hoten', 'sv.lop')
                ->get();
        }

        // Lấy điểm đã chấm (cấu trúc mới: diem_chu_tich, diem_thu_ky, diem_thanh_vien_1, diem_thanh_vien_2)
        $diemHienTai = DB::table('hoidong_chamdiem')
            ->where('hoidong_id', $hoidong_id)
            ->get()
            ->groupBy(function($item) {
                return $item->nhom_id . '_' . $item->mssv;
            })
            ->map(function($group) {
                $first = $group->first();
                return [
                    'diem_chu_tich' => $first->diem_chu_tich,
                    'diem_thu_ky' => $first->diem_thu_ky,
                    'diem_thanh_vien_1' => $first->diem_thanh_vien_1,
                    'diem_thanh_vien_2' => $first->diem_thanh_vien_2,
                ];
            });

        return view('lecturers.cham-diem.hoi-dong.form', compact(
            'mahd',
            'hoiDong',
            'vaiTroGV',
            'thanhVien',
            'deTaiList',
            'sinhVienNhom',
            'diemHienTai'
        ));
    }

    /**
     * Lưu điểm chấm (cấu trúc mới)
     */
    public function store(Request $request, $mahd)
    {
        $user = session('user');
        $magv = $user->magv ?? null;

        // Lấy hội đồng
        $hoiDong = DB::table('hoidong')
            ->where('mahd', $mahd)
            ->first();

        if (!$hoiDong) {
            return back()->with('error', 'Hội đồng không tồn tại!');
        }

        $hoidong_id = $hoiDong->id;

        // Validate
        $request->validate([
            'diem' => 'required|array',
            'diem.*' => 'nullable|array',
            'diem.*.*' => 'nullable|numeric|min:0|max:10'
        ]);

        DB::beginTransaction();
        try {
            $diemData = $request->input('diem', []);
            
            \Log::info('=== STORE DIEM ===');
            \Log::info('mahd: ' . $mahd);
            \Log::info('hoidong_id: ' . $hoidong_id);
            \Log::info('diemData count: ' . count($diemData));
            \Log::info('diemData: ' . json_encode($diemData));
            
            $countInsert = 0;

            // Lưu điểm theo cấu trúc mới
            foreach ($diemData as $key => $diemArray) {
                \Log::info('Processing key: ' . $key);
                \Log::info('diemArray: ' . json_encode($diemArray));
                
                $parts = explode('_', $key);
                if (count($parts) < 2) {
                    \Log::warning('Invalid key: ' . $key);
                    continue;
                }

                $nhom_id = $parts[0];
                $mssv = implode('_', array_slice($parts, 1));

                \Log::info('nhom_id: ' . $nhom_id . ', mssv: ' . $mssv);

                // Lấy thông tin sinh viên và đề tài
                $sinhVien = DB::table('sinhvien')->where('mssv', $mssv)->first();
                $mdt = DB::table('detai')->where('nhom_id', $nhom_id)->value('madt');

                if (!$mdt || !$sinhVien) {
                    \Log::warning('Missing mdt or sinhVien. mdt: ' . ($mdt ?? 'NULL') . ', sinhVien: ' . ($sinhVien ? 'OK' : 'NULL'));
                    continue;
                }

                // Chuẩn bị dữ liệu update/insert
                $updateData = [
                    'hoidong_id' => $hoidong_id,
                    'mahd' => $mahd,
                    'nhom_id' => $nhom_id,
                    'mdt' => $mdt,
                    'mssv' => $mssv,
                    'ten_sinh_vien' => $sinhVien->hoten,
                    'lop' => $sinhVien->lop,
                    'ngay_cham_diem' => now(),
                ];

                // ✅ Áp dụng điểm từ form
                $hasAnyDiem = false;
                foreach ($diemArray as $vaiTro => $diem) {
                    \Log::info('vaiTro: ' . $vaiTro . ', diem: ' . ($diem ?? 'NULL'));
                    
                    if ($diem === null || $diem === '' || $diem === '0') {
                        continue;
                    }

                    $hasAnyDiem = true;

                    // Map từ tên vai trò → tên cột
                    if ($vaiTro === 'chu_tich') {
                        $updateData['diem_chu_tich'] = (float)$diem;
                    } elseif ($vaiTro === 'thu_ky') {
                        $updateData['diem_thu_ky'] = (float)$diem;
                    } elseif ($vaiTro === 'thanh_vien_1') {
                        $updateData['diem_thanh_vien_1'] = (float)$diem;
                    } elseif ($vaiTro === 'thanh_vien_2') {
                        $updateData['diem_thanh_vien_2'] = (float)$diem;
                    }
                }

                if (!$hasAnyDiem) {
                    \Log::info('No diem found for this key');
                    continue;
                }

                \Log::info('updateData before diem_tong: ' . json_encode($updateData));

                // ✅ Tính diem_tong = trung bình 4 cột
                $diemValues = [];
                foreach (['diem_chu_tich', 'diem_thu_ky', 'diem_thanh_vien_1', 'diem_thanh_vien_2'] as $col) {
                    if (isset($updateData[$col]) && $updateData[$col] !== null) {
                        $diemValues[] = $updateData[$col];
                    }
                }

                if (!empty($diemValues)) {
                    $updateData['diem_tong'] = round(array_sum($diemValues) / count($diemValues), 2);
                }

                \Log::info('updateData final: ' . json_encode($updateData));

                // ✅ Update hoặc Insert
                $exists = DB::table('hoidong_chamdiem')
                    ->where('hoidong_id', $hoidong_id)
                    ->where('nhom_id', $nhom_id)
                    ->where('mssv', $mssv)
                    ->exists();

                if ($exists) {
                    \Log::info('Updating existing record');
                    DB::table('hoidong_chamdiem')
                        ->where('hoidong_id', $hoidong_id)
                        ->where('nhom_id', $nhom_id)
                        ->where('mssv', $mssv)
                        ->update($updateData);
                } else {
                    \Log::info('Inserting new record');
                    DB::table('hoidong_chamdiem')->insert($updateData);
                }

                $countInsert++;
                \Log::info('countInsert: ' . $countInsert);
            }

            DB::commit();

            \Log::info('Commit successful. Total: ' . $countInsert);

            return redirect()->route('lecturers.cham-diem.hoi-dong.index')
                ->with('success', 'Lưu điểm thành công! (' . $countInsert . ' bản ghi)');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Store HoiDong ChamDiem Error: ' . $e->getMessage());
            \Log::error('Stack: ' . $e->getTraceAsString());
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Xuất Excel
     */
    public function exportExcel($mahd)
    {
        try {
            $hoiDong = DB::table('hoidong')
                ->where('mahd', $mahd)
                ->first();

            if (!$hoiDong) {
                return back()->with('error', 'Hội đồng không tồn tại!');
            }

            $exporter = new ChamDiemHoiDongExport();
            $filePath = $exporter->exportExcel($hoiDong->id);

            return response()->download($filePath, basename($filePath))
                ->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            \Log::error('Export Excel Error: ' . $e->getMessage());
            return back()->with('error', 'Không thể xuất file: ' . $e->getMessage());
        }
    }
}