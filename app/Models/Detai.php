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

    // === RELATIONSHIPS ===
    
    public function sinhVien()
    {
        return $this->belongsTo(Student::class, 'mssv', 'mssv');
    }

    public function giangVien()
    {
        return $this->belongsTo(Lecturer::class, 'magv', 'magv');
    }

    // === HELPER METHODS ===
    
    public static function getSinhVienByNhom($nhom)
    {
        return self::where('detai.nhom', $nhom)
            ->join('sinhvien', 'detai.mssv', '=', 'sinhvien.mssv')
            ->select('sinhvien.*', 'detai.tendt', 'detai.nhom', 'detai.madt')
            ->orderBy('sinhvien.mssv')
            ->get();
    }

    public static function getDeTaiByNhom($nhom)
    {
        return self::where('nhom', $nhom)->first();
    }

    public static function countSinhVienInNhom($nhom)
    {
        return self::where('nhom', $nhom)->count();
    }

    /**
     * 🆕 TỰ ĐỘNG SINH MÃ NHÓM
     * Format: {magv}TH{4 số cuối MSSV}
     * 
     * Ví dụ:
     *   - magv: GV001, MSSV: 2021010567
     *   - Kết quả: GV001TH0567
     * 
     * @param string $magv - Mã giảng viên
     * @param array $sinhvienIds - Danh sách MSSV (chỉ dùng cái đầu tiên)
     * @return string - Mã nhóm tự động
     */
    public static function generateNhomCode($magv, $sinhvienIds)
    {
        // Lấy MSSV đầu tiên trong danh sách
        $firstMssv = $sinhvienIds[0];
        
        // Lấy 4 ký tự cuối của MSSV
        $lastFourDigits = substr($firstMssv, -4);
        
        // Ghép lại: magv + TH + 4 số cuối
        $nhomCode = $magv . 'TH' . $lastFourDigits;
        
        return $nhomCode;
    }

    /**
     * 🆕 KIỂM TRA MÃ NHÓM ĐÃ TỒN TẠI
     * 
     * @param string $nhomCode - Mã nhóm cần kiểm tra
     * @return bool - true nếu tồn tại, false nếu chưa tồn tại
     */
    public static function nhomCodeExists($nhomCode)
    {
        return self::where('nhom', $nhomCode)->exists();
    }

    // === TRẠNG THÁI ===
    
    public static function getTrangThaiList()
    {
        return [
            'chua_bat_dau' => 'Chưa bắt đầu',
            'dang_thuc_hien' => 'Đang thực hiện',
            'hoan_thanh' => 'Hoàn thành',
            'dinh_chi' => 'Đình chỉ'
        ];
    }

    public function getTrangThaiText()
    {
        $list = self::getTrangThaiList();
        return $list[$this->trangthai] ?? 'Không xác định';
    }

    public function getTrangThaiBadgeClass()
    {
        $classes = [
            'chua_bat_dau' => 'bg-secondary',
            'dang_thuc_hien' => 'bg-primary',
            'hoan_thanh' => 'bg-success',
            'dinh_chi' => 'bg-danger'
        ];
        
        return $classes[$this->trangthai] ?? 'bg-secondary';
    }
}