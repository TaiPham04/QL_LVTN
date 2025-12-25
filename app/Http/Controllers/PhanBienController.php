<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class PhanBienController extends Controller
{
    // 📌 Hiển thị trang phân công phản biện
    public function index(Request $request)
    {
        // ✅ Lấy tất cả dữ liệu (không filter search ở server)
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

        return redirect()->back();
    }

    // ✅ THÊM: Xuất Excel danh sách phân công phản biện
    public function exportExcel(Request $request)
    {
        $nhomIds = explode(',', $request->query('nhom_ids', ''));
        $nhomIds = array_filter($nhomIds); // Loại bỏ phần tử rỗng

        if (empty($nhomIds)) {
            return back()->with('error', 'Vui lòng chọn ít nhất 1 nhóm để xuất!');
        }

        // Lấy dữ liệu nhóm được chọn
        $topics = DB::table('detai as dt')
            ->leftJoin('nhom as n', 'dt.nhom_id', '=', 'n.id')
            ->leftJoin('sinhvien as sv', 'dt.mssv', '=', 'sv.mssv')
            ->leftJoin('giangvien as gv_hd', 'dt.magv', '=', 'gv_hd.magv')
            ->leftJoin('phancong_phanbien as pb', 'n.id', '=', 'pb.nhom_id')
            ->leftJoin('giangvien as gv_pb', 'pb.magv_phanbien', '=', 'gv_pb.magv')
            ->whereIn('n.id', $nhomIds)
            ->select(
                'n.id as nhom_id',
                'n.tennhom as nhom',
                'n.tendt',
                'dt.mssv',
                'sv.hoten as tensv',
                'sv.lop',
                'gv_hd.magv as magv_hd',
                'gv_hd.hoten as tengv_hd',
                'pb.magv_phanbien',
                'gv_pb.hoten as tengv_phanbien'
            )
            ->orderBy('n.tennhom')
            ->orderBy('sv.hoten')
            ->get();

        // Tạo Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'DANH SÁCH PHÂN CÔNG PHẢN BIỆN');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Tiêu đề cột
        $headers = ['Nhóm', 'Tên Đề Tài', 'MSSV', 'Tên Sinh Viên', 'Lớp', 'GVHD', 'GV Phản Biện', 'Trạng Thái'];
        $row = 3;
        foreach ($headers as $col => $header) {
            $cell = chr(65 + $col) . $row;
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true)->setColor(new Color('FFFFFFFF'));
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0066CC');
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Dữ liệu
        $row = 4;
        foreach ($topics as $topic) {
            $sheet->setCellValue('A' . $row, $topic->nhom);
            $sheet->setCellValue('B' . $row, $topic->tendt);
            $sheet->setCellValue('C' . $row, $topic->mssv);
            $sheet->setCellValue('D' . $row, $topic->tensv);
            $sheet->setCellValue('E' . $row, $topic->lop);
            $sheet->setCellValue('F' . $row, $topic->tengv_hd ?? '');
            $sheet->setCellValue('G' . $row, $topic->tengv_phanbien ?? 'Chưa phân');
            $sheet->setCellValue('H' . $row, $topic->magv_phanbien ? 'Đã phân' : 'Chưa phân');

            // Căn chỉnh
            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        // Độ rộng cột
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(12);

        // Export
        $writer = new Xlsx($spreadsheet);
        $filename = 'PhanCongPhanBien_' . date('YmdHis') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }
}