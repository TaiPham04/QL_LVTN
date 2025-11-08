<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Detai extends Model
{
    use HasFactory;

    protected $table = 'detai';
    protected $primaryKey = 'madt';
    public $timestamps = false;

    protected $fillable = [
        'madt',
        'tendt',
        'mssv',
        'magv',
        'nhom',
        'trangthai',
    ];
}
