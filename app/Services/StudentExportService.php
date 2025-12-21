<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StudentExportService implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function collection()
    {
        $user = session('user');

        if ($user->role === 'admin') {
            return DB::table('sinhvien')->get();
        }

        if ($user->role === 'giangvien') {
            $lecturer = DB::table('giangvien')->where('email', $user->email)->first();
            $assignedStudents = DB::table('phancong')
                ->where('magv', $lecturer->magv)
                ->pluck('mssv');

            return DB::table('sinhvien')
                ->whereIn('mssv', $assignedStudents)
                ->get();
        }
    }

    public function headings(): array
    {
        return ['MSSV', 'Họ tên', 'Lớp', 'Email', 'SDT'];
    }
}