<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PhanBienController extends Controller
{
    // 📌 Hiển thị trang phân công phản biện
    public function index(Request $request)
    {
        // Lấy danh sách đề tài theo NHÓM từ bảng detai
        $query = DB::table('detai as dt')
            ->leftJoin('nhom as n', 'dt.nhom_id', '=', 'n.id')
            ->leftJoin('sinhvien as sv', 'dt.mssv', '=', 'sv.mssv')
            ->leftJoin('giangvien as gv_hd', 'dt.magv', '=', 'gv_hd.magv')
            ->leftJoin('phancong_phanbien as pb', 'n.id', '=', 'pb.nhom_id')
            ->leftJoin('giangvien as gv_pb', 'pb.magv_phanbien', '=', 'gv_pb.magv')
            ->select(
                'n.id as nhom_id',
                'n.tennhom as nhom',
                'n.tendt',
                'dt.mssv',
                'sv.hoten as tensv',
                'gv_hd.magv as magv_hd',
                'gv_hd.hoten as tengv_hd',
                'pb.magv_phanbien',
                'gv_pb.hoten as tengv_phanbien'
            )
            ->whereNotNull('dt.nhom_id');

        // Tìm kiếm theo 1 ô - tìm trong nhóm, mssv, đề tài, gvhd
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('n.tennhom', 'LIKE', $searchTerm)
                  ->orWhere('sv.mssv', 'LIKE', $searchTerm)
                  ->orWhere('n.tendt', 'LIKE', $searchTerm)
                  ->orWhere('gv_hd.hoten', 'LIKE', $searchTerm);
            });
        }

        $topics = $query->orderBy('n.tennhom')
            ->orderBy('sv.hoten')
            ->get();

        // Group theo nhóm để hiển thị
        $groupedTopics = $topics->groupBy('nhom_id')->map(function ($items) {
            $first = $items->first();
            return (object)[
                'nhom_id' => $first->nhom_id,
                'nhom' => $first->nhom,
                'tendt' => $first->tendt,
                'magv_hd' => $first->magv_hd,
                'tengv_hd' => $first->tengv_hd,
                'magv_phanbien' => $first->magv_phanbien,
                'tengv_phanbien' => $first->tengv_phanbien,
                'sinhvien' => $items->map(fn($item) => [
                    'mssv' => $item->mssv,
                    'tensv' => $item->tensv
                ])->toArray(),
                'soluong_sv' => $items->count()
            ];
        })->values();

        // Lấy danh sách giảng viên (để chọn làm phản biện)
        $giangviens = DB::table('giangvien')
            ->select('magv', 'hoten')
            ->orderBy('hoten')
            ->get();

        return view('admin.phanbien.index', compact('groupedTopics', 'giangviens'));
    }

    // 📌 Lưu phân công phản biện
    public function store(Request $request)
    {
        // ✅ Kiểm tra chỉ khi form phân công được submit (có selected_topics)
        if (!$request->filled('selected_topics')) {
            return redirect()->back()->withErrors(['selected_topics' => 'Vui lòng chọn ít nhất 1 nhóm']);
        }

        if (!$request->filled('magv_phanbien')) {
            return redirect()->back()->withErrors(['magv_phanbien' => 'Vui lòng chọn giảng viên phản biện']);
        }

        $errors = [];
        $success_count = 0;
        
        foreach ($request->selected_topics as $nhom_id) {
            // ✅ FIX: $nhom_id là ID (số), không phải tên nhóm
            // Lấy thông tin giảng viên hướng dẫn của nhóm từ bảng detai
            $topic = DB::table('detai')
                ->where('nhom_id', $nhom_id)
                ->first();
            
            if (!$topic) {
                // Lấy tên nhóm để hiển thị lỗi tốt hơn
                $nhomName = DB::table('nhom')
                    ->where('id', $nhom_id)
                    ->value('tennhom') ?? "ID {$nhom_id}";
                
                $errors[] = "Nhóm {$nhomName}: Không tìm thấy thông tin";
                continue;
            }
            
            // Kiểm tra GVHD không được làm phản biện
            if ($topic->magv == $request->magv_phanbien) {
                $nhomName = DB::table('nhom')
                    ->where('id', $nhom_id)
                    ->value('tennhom') ?? "ID {$nhom_id}";
                
                $errors[] = "Nhóm {$nhomName}: Giảng viên hướng dẫn không được làm phản biện";
                continue;
            }
            
            // Insert hoặc update
            DB::table('phancong_phanbien')->updateOrInsert(
                ['nhom_id' => $nhom_id],
                [
                    'magv_phanbien' => $request->magv_phanbien,
                    'created_at' => now(),
                ]
            );
            
            $success_count++;
        }

        if (!empty($errors)) {
            return redirect()->back()
                ->withErrors($errors)
                ->with('warning', "Phân công thành công {$success_count} nhóm. Có " . count($errors) . " lỗi.");
        }

        return redirect()->back();//->with('success', "Phân công thành công cho {$success_count} nhóm!");
    }
}
?>