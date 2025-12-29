<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Detai;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LecturerAssignmentsController extends Controller
{

    
    /**
     * Hiển thị form tạo nhóm
     */
    public function form()
    {
        $lecturer = session('user');
        
        $availableStudents = DB::table('sinhvien')
            ->join('phancong', 'sinhvien.mssv', '=', 'phancong.mssv')
            ->leftJoin('detai', 'sinhvien.mssv', '=', 'detai.mssv')
            ->where('phancong.magv', $lecturer->magv)
            ->whereNull('detai.madt') // CHƯA CÓ ĐỀ TÀI => CHƯA CÓ NHÓM
            ->select('sinhvien.*')
            ->orderBy('sinhvien.hoten')
            ->get();


        // MỖI SINH VIÊN CÓ TRẠNG THÁI RIÊNG TỪ BẢNG DETAI
        $students = DB::table('sinhvien')
            ->join('phancong', 'sinhvien.mssv', '=', 'phancong.mssv')
            ->leftJoin('detai', 'sinhvien.mssv', '=', 'detai.mssv')
            ->leftJoin('nhom', 'detai.nhom_id', '=', 'nhom.id')
            ->where('phancong.magv', $lecturer->magv)
            ->select(
                'sinhvien.mssv',
                'sinhvien.hoten',
                'sinhvien.lop',
                'nhom.tennhom as nhom',
                'nhom.tendt',
                'detai.trangthai',
                'detai.madt as detai_id'
            )
            ->orderBy('nhom.tennhom')
            ->orderBy('sinhvien.hoten')
            ->get();

        return view('lecturers.assignments.form', compact('availableStudents', 'students'));
    }

    /**
     * Lưu nhóm mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'tendt' => 'required|string|max:255',
            'trangthai' => 'required|in:chua_bat_dau,dang_thuc_hien,hoan_thanh,dinh_chi',
            'sinhvien' => 'required|array|min:1|max:2',
            'sinhvien.*' => 'required|exists:sinhvien,mssv',
        ], [
            'sinhvien.min' => 'Phải chọn ít nhất 1 sinh viên',
            'sinhvien.max' => 'Tối đa 2 sinh viên mỗi nhóm',
            'tendt.required' => 'Tên đề tài không được để trống',
        ]);

        try {
            $lecturer = session('user');
            $sinhvienIds = $request->input('sinhvien');
            
            $nhomCode = Detai::generateNhomCode($lecturer->magv, $sinhvienIds);
            
            $nhom = DB::table('nhom')->where('tennhom', $nhomCode)->first();
            if ($nhom) {
                return back()->with('error', 'Mã nhóm ' . $nhomCode . ' đã tồn tại! Vui lòng kiểm tra lại.');
            }

            $nhom_id = DB::table('nhom')->insertGetId([
                'tennhom' => $nhomCode,
                'tendt' => $request->input('tendt'),
                'magv' => $lecturer->magv,
                'created_at' => now(),
            ]);

            // Mỗi sinh viên có trạng thái riêng
            foreach ($sinhvienIds as $mssv) {
                Detai::create([
                    'mssv' => $mssv,
                    'magv' => $lecturer->magv,
                    'nhom_id' => $nhom_id,
                    'trangthai' => $request->input('trangthai'),
                ]);
            }

            return redirect()->route('lecturers.assignments.form')
                ->with('success', 'Tạo nhóm ' . $nhomCode . ' thành công!');

        } catch (\Exception $e) {
            Log::error('Error creating group: ' . $e->getMessage());
            return back()->with('error', 'Lỗi khi tạo nhóm: ' . $e->getMessage());
        }
    }

    /**
     * Hiển thị chi tiết nhóm
     */
    public function show($nhom)
    {
        $lecturer = session('user');
        
        $deTai = DB::table('detai')
            ->join('nhom', 'detai.nhom_id', '=', 'nhom.id')
            ->where('nhom.tennhom', $nhom)
            ->where('detai.magv', $lecturer->magv)
            ->first();

        if (!$deTai) {
            return redirect()->route('lecturers.assignments.form')
                ->with('error', 'Nhóm không tồn tại hoặc bạn không có quyền truy cập');
        }

        $students = Detai::getSinhVienByNhomId($deTai->nhom_id);

        return view('lecturers.assignments.show', compact('deTai', 'students', 'nhom'));
    }

    /**
     * Form sửa nhóm
     */
    public function edit($nhom)
    {
        $lecturer = session('user');
        
        $deTai = DB::table('detai')
            ->join('nhom', 'detai.nhom_id', '=', 'nhom.id')
            ->where('nhom.tennhom', $nhom)
            ->where('detai.magv', $lecturer->magv)
            ->first();

        if (!$deTai) {
            return redirect()->route('lecturers.assignments.form')
                ->with('error', 'Nhóm không tồn tại');
        }

        $students = Detai::getSinhVienByNhomId($deTai->nhom_id);
        $availableStudents = DB::table('sinhvien')
            ->join('phancong', 'sinhvien.mssv', '=', 'phancong.mssv')
            ->where('phancong.magv', $lecturer->magv)
            ->whereNotIn('sinhvien.mssv', $students->pluck('mssv')->toArray())
            ->select('sinhvien.*')
            ->get();

        return view('lecturers.assignments.edit', compact('deTai', 'students', 'availableStudents', 'nhom'));
    }

    /**
     * Cập nhật nhóm
     */
    public function update(Request $request, $nhom)
    {
        $request->validate([
            'tendt' => 'required|string|max:255',
        ]);

        try {
            $lecturer = session('user');
            
            DB::table('nhom')
                ->where('tennhom', $nhom)
                ->update([
                    'tendt' => $request->input('tendt'),
                ]);

            return redirect()->route('lecturers.assignments.form')
                ->with('success', 'Cập nhật nhóm ' . $nhom . ' thành công!');

        } catch (\Exception $e) {
            Log::error('Error updating group: ' . $e->getMessage());
            return back()->with('error', 'Lỗi khi cập nhật: ' . $e->getMessage());
        }
    }

    /**
     * Xóa nhóm
     */
    public function destroy($nhom)
    {
        try {
            $lecturer = session('user');
            
            DB::table('detai')
                ->join('nhom', 'detai.nhom_id', '=', 'nhom.id')
                ->where('nhom.tennhom', $nhom)
                ->where('detai.magv', $lecturer->magv)
                ->delete();

            return redirect()->route('lecturers.assignments.form')
                ->with('success', 'Xóa nhóm thành công!');

        } catch (\Exception $e) {
            Log::error('Error deleting group: ' . $e->getMessage());
            return back()->with('error', 'Lỗi khi xóa nhóm');
        }
    }

    /**
     * Cập nhật trạng thái từng sinh viên (RIÊNG BIỆT)
     */
    public function updateAllStatus(Request $request)
    {
        try {
            $lecturer = session('user');
            $changes = $request->input('trangthai', []);

            foreach ($changes as $change) {
                DB::table('detai')
                    ->where('madt', $change['detai_id'])
                    ->where('magv', $lecturer->magv)
                    ->update(['trangthai' => $change['trangthai']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    
    /**
     * Xóa sinh viên khỏi nhóm
     */
    /**
     * Xóa sinh viên khỏi nhóm
     */
    public function deleteStudents(Request $request)
    {
        try {
            $lecturer = session('user');
            $detaiIds = $request->input('detai_ids', []);
            $cannotDelete = [];
            $deletedCount = 0;

            foreach ($detaiIds as $madt) {
                // Lấy thông tin detai
                $detai = DB::table('detai')
                    ->where('madt', $madt)
                    ->where('magv', $lecturer->magv)
                    ->first();

                if (!$detai) {
                    continue;
                }

                $mssv = $detai->mssv;
                $nhom_id = $detai->nhom_id;

                // 1. Kiểm tra điểm giữa kì (bảng diem_giuaky)
                $hasDiemGiuaky = DB::table('diem_giuaky')
                    ->where('mssv', $mssv)
                    ->whereRaw('diem IS NOT NULL AND diem > 0')
                    ->exists();

                // 2. Kiểm tra điểm sinh viên (bảng diem_sinh_vien)
                $hasDiemSinhVien = DB::table('diem_sinh_vien')
                    ->where('mssv', $mssv)
                    ->where(function($q) {
                        $q->whereRaw('diem_phan_tich IS NOT NULL AND diem_phan_tich > 0')
                          ->orWhereRaw('diem_thiet_ke IS NOT NULL AND diem_thiet_ke > 0')
                          ->orWhereRaw('diem_hien_thuc IS NOT NULL AND diem_hien_thuc > 0')
                          ->orWhereRaw('diem_kiem_tra IS NOT NULL AND diem_kiem_tra > 0');
                    })
                    ->exists();

                // 3. Kiểm tra phiếu chấm điểm - hướng dẫn (bảng phieu_cham_diem với loai_phieu = 'huong_dan')
                $hasPhieuHuongDan = DB::table('phieu_cham_diem')
                    ->where('mdt', $madt)
                    ->where('loai_phieu', 'huong_dan')
                    ->exists();

                // 4. Kiểm tra phiếu chấm điểm - phản biện (bảng phieu_cham_diem với loai_phieu = 'phan_bien')
                $hasPhieuPhanBien = DB::table('phieu_cham_diem')
                    ->where('mdt', $madt)
                    ->where('loai_phieu', 'phan_bien')
                    ->exists();

                // 5. Kiểm tra hội đồng chấm điểm (bảng hoidong_chamdiem)
                $hasHoidongChamDiem = DB::table('hoidong_chamdiem')
                    ->where('nhom_id', $nhom_id)
                    ->exists();

                // Nếu có bất kì điểm nào, không cho xóa
                if ($hasDiemGiuaky || $hasDiemSinhVien || $hasPhieuHuongDan || $hasPhieuPhanBien || $hasHoidongChamDiem) {
                    // Lấy tên sinh viên để hiển thị
                    $student = DB::table('sinhvien')
                        ->where('mssv', $mssv)
                        ->select('mssv', 'hoten')
                        ->first();
                    
                    $cannotDelete[] = ($student ? $student->hoten . ' (' . $student->mssv . ')' : $mssv);
                    continue;
                }

                // Xóa detai (sinh viên khỏi nhóm)
                DB::table('detai')->where('madt', $madt)->delete();
                
                // Kiểm tra nhom còn sinh viên không
                if ($nhom_id) {
                    $nhomHasStudents = DB::table('detai')
                        ->where('nhom_id', $nhom_id)
                        ->exists();
                    
                    if ($nhomHasStudents) {
                        // ✅ CHỈNH MÃ NHÓM nếu cần
                        $nhom = DB::table('nhom')->where('id', $nhom_id)->first();
                        
                        if ($nhom) {
                            // Lấy 4 số cuối mã nhóm hiện tại
                            $currentLastFour = substr($nhom->tennhom, -4);
                            // Lấy 4 số cuối MSSV bị tách
                            $deletedLastFour = substr($mssv, -4);
                            
                            // Nếu 4 số cuối trùng, cần đổi mã nhóm
                            if ($currentLastFour === $deletedLastFour) {
                                // Lấy sinh viên còn lại trong nhóm
                                $remainingStudent = DB::table('detai')
                                    ->where('nhom_id', $nhom_id)
                                    ->select('mssv')
                                    ->first();
                                
                                if ($remainingStudent) {
                                    // Tạo mã nhóm mới từ MSSV sinh viên còn lại
                                    $magv = $nhom->magv;
                                    $remainingLastFour = substr($remainingStudent->mssv, -4);
                                    $newNhomCode = $magv . 'TH' . $remainingLastFour;
                                    
                                    // Kiểm tra mã mới có trùng không
                                    $existingNhom = DB::table('nhom')
                                        ->where('tennhom', $newNhomCode)
                                        ->where('id', '!=', $nhom_id)
                                        ->exists();
                                    
                                    if (!$existingNhom) {
                                        // Cập nhật mã nhóm
                                        DB::table('nhom')
                                            ->where('id', $nhom_id)
                                            ->update(['tennhom' => $newNhomCode]);
                                    }
                                }
                            }
                        }
                    } else {
                        // Nếu nhom không còn sinh viên, xóa nhom
                        DB::table('nhom')->where('id', $nhom_id)->delete();
                    }
                }

                $deletedCount++;
            }

            // Tạo message phản hồi
            $message = '';
            if ($deletedCount > 0) {
                $message = 'Xóa ' . $deletedCount . ' sinh viên thành công!';
            }
            if (count($cannotDelete) > 0) {
                if ($deletedCount > 0) {
                    $message .= ' ';
                }
                $message .= 'Không thể xóa ' . count($cannotDelete) . ' sinh viên vì đã có điểm: ' . 
                           implode(', ', $cannotDelete);
            }

            return response()->json([
                'success' => ($deletedCount > 0),
                'message' => $message ?: 'Không có sinh viên nào được xóa'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting students: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function updateStatus(Request $request, $detai_id){
        try {
            $request->validate([
                'trangthai' => 'required|in:chua_bat_dau,dang_thuc_hien,hoan_thanh,dinh_chi',
            ]);

            $lecturer = session('user');

            DB::table('detai')
                ->where('madt', $detai_id)
                ->where('magv', $lecturer->magv)
                ->update(['trangthai' => $request->input('trangthai')]);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function mergeGroup(Request $request)
    {
        $mssvs = $request->input('mssv'); // array MSSV

        // 1. Validate số lượng
        if (!$mssvs || count($mssvs) !== 2) {
            return back()->withErrors('Chỉ được chọn đúng 2 sinh viên');
        }

        // 2. Lấy thông tin đề tài / nhóm
        $students = DB::table('sinhvien')
            ->leftJoin('detai', 'sinhvien.mssv', '=', 'detai.mssv')
            ->whereIn('sinhvien.mssv', $mssvs)
            ->select(
                'sinhvien.mssv',
                'detai.nhom_id'
            )
            ->get();

        $coNhom = $students->whereNotNull('nhom_id');
        $chuaNhom = $students->whereNull('nhom_id');

        // 3. Check nghiệp vụ
        if ($coNhom->count() !== 1 || $chuaNhom->count() !== 1) {
            return back()->withErrors(
                'Chỉ được chọn 1 sinh viên đã có nhóm và 1 sinh viên chưa có nhóm'
            );
        }

        $nhomId = $coNhom->first()->nhom_id;
        $mssvChuaNhom = $chuaNhom->first()->mssv;

        // 4. Gán sinh viên chưa có nhóm vào nhóm đã có
        DB::transaction(function () use ($nhomId, $mssvChuaNhom) {
            Detai::create([
                'mssv' => $mssvChuaNhom,
                'nhom_id' => $nhomId,
                'magv' => auth()->user()->magv,
                'trangthai' => 'Chưa bắt đầu',
            ]);
        });

        return back()->with('success', 'Đã gộp sinh viên vào nhóm thành công');
    }
}