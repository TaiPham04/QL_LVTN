<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    public $timestamps = false;
    
    protected $table = 'sinhvien';           // Tên bảng trong database
    protected $primaryKey = 'mssv';          // Khóa chính là mssv
    public $incrementing = false;            // Không tự động tăng vì mssv là string
    protected $keyType = 'string';           // Kiểu khóa chính là string

    protected $fillable = [
        "mssv",     // Mã số sinh viên
        "hoten",    // Họ tên
        "lop",      // Lớp
        "email",    // Email
        "sdt",      // Số điện thoại
    ];
}