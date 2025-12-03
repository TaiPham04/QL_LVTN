<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Detai extends Model
{
    use HasFactory;

    protected $table = 'detai';
    protected $primaryKey = 'madt';
    public $timestamps = false;

    protected $fillable = [
        'madt',
        'tendt',
        'mssv',
        'magv',
        'nhom',
        'trangthai',
    ];

    // === RELATIONSHIPS ===
    
    public function sinhVien()
    {
        return $this->belongsTo(Student::class, 'mssv', 'mssv');
    }

    public function giangVien()
    {
        return $this->belongsTo(Lecturer::class, 'magv', 'magv');
    }

    // === HELPER METHODS ===
    
    // Lấy TẤT CẢ sinh viên trong cùng 1 nhóm
    public static function getSinhVienByNhom($nhom)
    {
        return self::where('detai.nhom', $nhom)
            ->join('sinhvien', 'detai.mssv', '=', 'sinhvien.mssv')
            ->select('sinhvien.*', 'detai.tendt', 'detai.nhom', 'detai.madt')
            ->orderBy('sinhvien.mssv')
            ->get();
    }

    // Lấy thông tin đề tài theo nhóm
    public static function getDeTaiByNhom($nhom)
    {
        return self::where('nhom', $nhom)->first();
    }

    // Đếm số sinh viên trong nhóm
    public static function countSinhVienInNhom($nhom)
    {
        return self::where('nhom', $nhom)->count();
    }
}