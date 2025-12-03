<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhieuChamDiem extends Model
{
    protected $table = 'phieu_cham_diem';
    
    protected $fillable = [
        'loai_phieu',
        'nhom',
        'magv',
        'ten_de_tai',
        'dat_chuan',
        'yeu_cau_dieu_chinh',
        'uu_diem',
        'thieu_sot',
        'cau_hoi',
        'ngay_cham',
        'file_word_path',
        'trang_thai'
    ];

    protected $casts = [
        'dat_chuan' => 'boolean',
        'ngay_cham' => 'date',
    ];

    // Relationship: 1 phiếu chấm có nhiều điểm sinh viên
    public function diemSinhVien()
    {
        return $this->hasMany(DiemSinhVien::class, 'phieu_cham_id');
    }

    // Lấy thông tin giảng viên
    public function giangVien()
    {
        return $this->belongsTo(Lecturer::class, 'magv', 'magv');
    }
}