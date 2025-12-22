<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoiDong extends Model
{
    protected $table = 'hoidong';
    protected $primaryKey = 'id';
    public $timestamps = false;  // ✅ PHẢI CÓ DÒNG NÀY
    public $incrementing = true;  // ✅ VÀ CÓ DÒNG NÀY
    
    protected $fillable = [
        'mahd',
        'tenhd',
        'ghi_chu',
        'trang_thai',
    ];

    public function thanhVien()
    {
        return $this->hasMany(ThanhVienHoiDong::class, 'hoidong_id', 'id');
    }

    public function deTai()
    {
        return $this->hasMany(HoiDongDeTai::class, 'hoidong_id', 'id');
    }
}