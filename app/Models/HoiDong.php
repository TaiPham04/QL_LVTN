<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoiDong extends Model
{
    protected $table = 'hoidong';
    public $timestamps = false;  // ← Tắt timestamps
    
    protected $fillable = [
        'mahd',
        'tenhd',
        'ngay_hoidong',
        'ghi_chu',
        'trang_thai'
    ];

    protected $dates = [
        'ngay_hoidong'
    ];

    public function thanhVien()
    {
        return $this->hasMany(ThanhVienHoiDong::class, 'hoidong_id');
    }

    public function deTai()
    {
        return $this->hasMany(HoiDongDeTai::class, 'hoidong_id');
    }
}