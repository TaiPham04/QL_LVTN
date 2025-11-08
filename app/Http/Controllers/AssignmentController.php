<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Lecturer;
use App\Models\Assignment;

class AssignmentController extends Controller
{
    public function showForm()
    {
        $students = Student::with('assignment')
            ->whereDoesntHave('assignment') // chỉ lấy sinh viên chưa có giảng viên
            ->get();

        $lecturers = Lecturer::all(); // chỉ lấy giảng viên chưa hướng dẫn ai

        return view('assignments.form', compact('students', 'lecturers'));
    }

    public function save(Request $request)
    {
        $magv = $request->input('magv');
        $mssvList = $request->input('mssv', []);

        if (empty($magv)) {
            return redirect()->route('assignments.form')->with('error', 'Vui lòng chọn giảng viên!');
        }

        if (empty($mssvList)) {
            return redirect()->route('assignments.form')->with('error', 'Vui lòng chọn ít nhất một sinh viên!');
        }

        foreach ($mssvList as $mssv) {
            Assignment::updateOrCreate(
                ['mssv' => $mssv],
                ['magv' => $magv, 'tg_phancong' => now()]
            );
        }

        return redirect()->route('assignments.form')->with('success', 'Phân công giảng viên thành công!');
    }

    // Trang "Bảng phân công"
    public function index(Request $request)
    {
        $lecturers = Lecturer::all();
        $selectedLecturer = $request->input('magv');

        $assignments = Assignment::with('student', 'lecturer')
            ->when($selectedLecturer, function ($query, $magv) {
                return $query->where('magv', $magv);
            })
            ->get();

        return view('assignments.index', compact('lecturers', 'assignments', 'selectedLecturer'));
    }

    public function bulkSave(Request $request)
    {
        $magv = $request->input('magv');
        $selectedStudents = $request->input('students', []);

        if (!$magv) {
            return back()->with('error', 'Vui lòng chọn giảng viên trước khi lưu!');
        }

        if (empty($selectedStudents)) {
            return back()->with('error', 'Vui lòng chọn ít nhất một sinh viên!');
        }

        foreach ($selectedStudents as $mssv) {
            \App\Models\Assignment::updateOrCreate(
                ['mssv' => $mssv],
                ['magv' => $magv, 'tg_phancong' => now()]
            );
        }

        return redirect()->route('assignments.form')->with('success', 'Phân công giảng viên thành công!');
    }

}
