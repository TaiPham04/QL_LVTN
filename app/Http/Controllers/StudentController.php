<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;

class StudentController extends Controller
{
    // Hiển thị form thêm sinh viên
    public function create()
    {
        return view('students.create');
    }

    // Lưu sinh viên vào DB
    public function store(Request $request)
    {
        // Kiểm tra dữ liệu nhập vào
        $validated = $request->validate([
            'mssv' => 'required|string|max:20',
            'hoten' => 'required|string|max:100',
            'lop' => 'required|string|max:50',
            'sdt' => 'required|string|max:15',
            'email' => 'required|email',
        ]);
        try {
            DB::table('sinhvien')->insert([
                'mssv' => $request->mssv,
                'hoten' => $request->hoten,
                'lop' => $request->lop,
                'sdt' => $request->sdt,
                'email' => $request->email,
            ]);

            return redirect()->back()->with('success', 'Thêm sinh viên thành công!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi khi thêm sinh viên: ' . $e->getMessage());
        }

    }

    public function showImportForm()
    {
        return view('students.import');
    }

    public function import(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|mimes:xlsx,xls',
            ]);

            Excel::import(new StudentsImport, $request->file('file'));

            return back()->with('success', 'Nhập dữ liệu sinh viên thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi khi nhập file: ' . $e->getMessage());
        }
    }

    public function index()
    {
        $user = session('user');

        if (!$user) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập trước.');
        }

        // Nếu là admin → hiển thị tất cả sinh viên
        if ($user->role === 'admin') {
            $students = DB::table('sinhvien')->get();
            return view('students.index', compact('students'));
        }

        // Nếu là giảng viên → lọc sinh viên theo phân công
        if ($user->role === 'giangvien') {
            $email = $user->email;

            $lecturer = DB::table('giangvien')->where('email', $email)->first();

            if (!$lecturer) {
                return back()->with('error', 'Không tìm thấy thông tin giảng viên!');
            }

            $assignedStudents = DB::table('phancong')
                ->where('magv', $lecturer->magv)
                ->pluck('mssv');

            $students = DB::table('sinhvien')
                ->whereIn('mssv', $assignedStudents)
                ->get();

            return view('students.index', compact('students'));
        }

        // Nếu role khác
        return redirect()->route('login')->with('error', 'Không có quyền truy cập.');
    }


    // Hiển thị DANH SÁCH sinh viên có nút sửa (dùng cho edit.blade.php)
    public function showEditList()
    {
        $students = Student::all();
        return view('students.edit', compact('students')); // Trả về view edit.blade.php
    }

    // Hiển thị FORM sửa thông tin 1 sinh viên (dùng cho edit-form.blade.php)
    public function edit($mssv)
    {
        $student = Student::where('mssv', $mssv)->firstOrFail();
        return view('students.edit-form', compact('student')); // Trả về view edit-form.blade.php
    }

    // Cập nhật sinh viên
    public function update(Request $request, $mssv)
    {
        $student = Student::where('mssv', $mssv)->firstOrFail();

        $request->validate([
            'hoten' => 'required',
            'lop' => 'required',
            'sdt' => 'nullable',
            'email' => 'nullable|email',
        ]);

        $student->update($request->all());

        return redirect()->route('students.edit.list')->with('success', 'Cập nhật thông tin sinh viên thành công!');
    }

}