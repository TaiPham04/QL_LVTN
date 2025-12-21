<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoiDongDeTai extends Model
{
    protected $table = 'hoidong_detai';
    public $timestamps = false;

    protected $fillable = [
        'mahd',
        'nhom',
        'vai_tro',      // ✅ MỚI
        'magv'          // ✅ MỚI
    ];

    // Quan hệ: Thuộc về 1 hội đồng
    public function hoiDong()
    {
        return $this->belongsTo(HoiDong::class, 'mahd', 'mahd');
    }

    // Quan hệ: Lấy thông tin đề tài
    public function deTai()
    {
        return $this->hasMany(Detai::class, 'nhom', 'nhom');
    }

    // ✅ MỚI: Lấy giảng viên
    public function giangVien()
    {
        return $this->belongsTo(Lecturer::class, 'magv', 'magv');
    }

    // Lấy sinh viên trong nhóm
    public function getSinhVien()
    {
        return Detai::getSinhVienByNhom($this->nhom);
    }

    // ✅ MỚI: Helper - Lấy tên vai trò
    public function getVaiTroLabel()
    {
        $roles = [
            'chu_tich' => 'Chủ tịch',
            'thu_ky' => 'Thư ký',
            'thanh_vien' => 'Thành viên'
        ];
        return $roles[$this->vai_tro] ?? 'Thành viên';
    }
}
?>