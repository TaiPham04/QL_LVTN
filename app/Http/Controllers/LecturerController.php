<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lecturer;
use App\Imports\LecturersImport;
use Maatwebsite\Excel\Facades\Excel;

class LecturerController extends Controller
{
    //Danh sách giảng viên
    public function index()
    {
        $lecturers = Lecturer::all();
        return view('lecturers.index', compact('lecturers'));
    }

    //Form thêm mới
    public function create()
    {
        return view('lecturers.create');
    }

    //Lưu giảng viên mới
    public function store(Request $request)
    {
        $request->validate([
            'magv' => 'required|unique:giangvien,magv',
            'hoten' => 'required|string|max:255',
            'email' => 'required|email|unique:giangvien,email',
        ]);

        Lecturer::create([
            'magv' => $request->magv,
            'hoten' => $request->hoten,
            'email' => $request->email,
        ]);

        return redirect()->route('lecturers.index')->with('success', 'Thêm giảng viên thành công!');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        try {
            // Thực hiện import dữ liệu vào DB
            Excel::import(new LecturersImport, $request->file('file'));

            return redirect()->route('lecturers.create')->with('success', 'Import giảng viên thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi khi import file: ' . $e->getMessage());
        }
    }

    public function editList()
    {
        $lecturers = Lecturer::all();
        return view('lecturers.edit', compact('lecturers'));
    }

    public function edit($magv)
    {
        $lecturer = Lecturer::findOrFail($magv);
        return view('lecturers.edit-form', compact('lecturer'));
    }

    public function update(Request $request, $magv)
    {
        $lecturer = Lecturer::findOrFail($magv);
        $lecturer->update($request->only(['hoten', 'email']));

        return redirect()->route('lecturers.edit.list')->with('success', 'Cập nhật giảng viên thành công!');
    }

}
