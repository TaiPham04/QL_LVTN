<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoiDongChamDiem extends Model
{
    protected $table = 'hoidong_chamdiem';
    public $timestamps = false;

    protected $fillable = [
        'mahd',
        'nhom',
        'mssv',
        'ten_sinh_vien',
        'lop',
        'tendt',
        'magv_hd',
        'magv_danh_gia',
        'vai_tro_danh_gia',
        'diem',
        'diem_tong',
        'ngay_cham_diem',
        'ghi_chu'
    ];

    // Quan hệ: Thuộc về 1 hội đồng
    public function hoiDong()
    {
        return $this->belongsTo(HoiDong::class, 'mahd', 'mahd');
    }

    // Quan hệ: Thuộc về 1 đề tài
    public function deTai()
    {
        return $this->belongsTo(Detai::class, 'nhom', 'nhom');
    }

    // Quan hệ: Thuộc về 1 sinh viên
    public function sinhVien()
    {
        return $this->belongsTo(SinhVien::class, 'mssv', 'mssv');
    }

    // Quan hệ: GV hướng dẫn
    public function giangVienHuongDan()
    {
        return $this->belongsTo(Lecturer::class, 'magv_hd', 'magv');
    }

    // Quan hệ: GV chấm điểm
    public function giangVienDanhGia()
    {
        return $this->belongsTo(Lecturer::class, 'magv_danh_gia', 'magv');
    }

    // Helper: Lấy tên vai trò
    public function getVaiTroLabel()
    {
        $roles = [
            'chu_tich' => 'Chủ tịch',
            'thu_ky' => 'Thư ký',
            'thanh_vien' => 'Thành viên'
        ];
        return $roles[$this->vai_tro_danh_gia] ?? 'Thành viên';
    }
}
?>