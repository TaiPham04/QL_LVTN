<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiemSinhVien extends Model
{
    protected $table = 'diem_sinh_vien';
    
    protected $fillable = [
        'phieu_cham_id',
        'mssv',
        'diem_phan_tich',
        'diem_thiet_ke',
        'diem_hien_thuc',
        'diem_kiem_tra',
        'de_nghi'
    ];

    protected $casts = [
        'diem_phan_tich' => 'decimal:2',
        'diem_thiet_ke' => 'decimal:2',
        'diem_hien_thuc' => 'decimal:2',
        'diem_kiem_tra' => 'decimal:2',
    ];

    // Relationship: Thuộc về 1 phiếu chấm
    public function phieuCham()
    {
        return $this->belongsTo(PhieuChamDiem::class, 'phieu_cham_id');
    }

    // Lấy thông tin sinh viên
    public function sinhVien()
    {
        return $this->belongsTo(Student::class, 'mssv', 'mssv');
    }
}