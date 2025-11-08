<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $table = 'phancong';
    public $timestamps = false;

    protected $fillable = [
        'mssv',
        'magv',
        'tg_phancong',
    ];

    // 🔹 Thêm quan hệ đến giảng viên
    public function lecturer()
    {
        return $this->belongsTo(\App\Models\Lecturer::class, 'magv', 'magv');
    }

    // 🔹 (Không bắt buộc) Quan hệ ngược đến sinh viên
    public function student()
    {
        return $this->belongsTo(\App\Models\Student::class, 'mssv', 'mssv');
    }
}
