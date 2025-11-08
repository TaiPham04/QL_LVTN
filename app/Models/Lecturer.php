<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
    use HasFactory;

    protected $table = 'giangvien';   // tên bảng
    protected $primaryKey = 'magv';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'magv',
        'hoten',
        'email',
    ];

    public function assignments()
    {
        return $this->hasMany(\App\Models\Assignment::class, 'magv', 'magv');
    }
}
