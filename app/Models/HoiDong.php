<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoiDong extends Model
{
    protected $table = 'hoidong';
    protected $primaryKey = 'mahd';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false; // ← BỎ TIMESTAMPS

    protected $fillable = [
        'mahd',
        'tenhd',
        'ghi_chu',
        'trang_thai'
    ];

    // Quan hệ: 1 hội đồng có nhiều thành viên
    public function thanhVien()
    {
        return $this->hasMany(ThanhVienHoiDong::class, 'mahd', 'mahd');
    }

    // Quan hệ: 1 hội đồng có nhiều đề tài
    public function deTai()
    {
        return $this->hasMany(HoiDongDeTai::class, 'mahd', 'mahd');
    }

    // Lấy danh sách giảng viên trong hội đồng
    public function giangVien()
    {
        return $this->belongsToMany(Lecturer::class, 'thanhvienhoidong', 'mahd', 'magv');
    }

    // Helper: Kiểm tra hội đồng đã đủ 3 thành viên chưa
    public function isDayDu()
    {
        return $this->thanhVien()->count() >= 3;
    }

    // Helper: Đếm số đề tài được phân công
    public function getSoLuongDeTai()
    {
        return $this->deTai()->count();
    }
}