<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThanhVienHoiDong extends Model
{
    protected $table = 'thanhvienhoidong';
    public $timestamps = false;

    protected $fillable = [
        'mahd',
        'magv',
        'vai_tro'  // ✅ THÊM CỘT NÀY
    ];

    // Quan hệ: Thuộc về 1 hội đồng
    public function hoiDong()
    {
        return $this->belongsTo(HoiDong::class, 'mahd', 'mahd');
    }

    // Quan hệ: Thuộc về 1 giảng viên
    public function giangVien()
    {
        return $this->belongsTo(Lecturer::class, 'magv', 'magv');
    }
}
?>