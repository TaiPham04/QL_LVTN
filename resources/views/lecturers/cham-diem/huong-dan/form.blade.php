@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <form action="{{ route('lecturers.chamdiem.huongdan.store', $nhom) }}" method="POST">
        @csrf
        
        <div class="row">
            <div class="col-12">
                {{-- Header --}}
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fa fa-clipboard-check me-2"></i>
                            Phiếu Chấm Điểm Hướng Dẫn
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Mã nhóm:</strong> <span class="badge bg-secondary">{{ $nhom }}</span></p>
                                <p class="mb-2"><strong>Tên đề tài:</strong> {{ $deTai->tendt }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Số lượng sinh viên:</strong> {{ $danhSachSinhVien->count() }}</p>
                                <p class="mb-2"><strong>Giảng viên:</strong> {{ session('user')->username }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Phần 1: Thông tin chung --}}
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Phần I: Nhận Xét Chung</h5>
                    </div>
                    <div class="card-body">
                        {{-- Đạt chuẩn --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nhận xét chung về định dạng:</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="dat_chuan" id="dat_chuan_yes" 
                                       value="1" {{ old('dat_chuan', $phieuCham->dat_chuan ?? 1) == 1 ? 'checked' : '' }} required>
                                <label class="form-check-label" for="dat_chuan_yes">
                                    <i class="fa fa-check-circle text-success me-1"></i> Đạt
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="dat_chuan" id="dat_chuan_no" 
                                       value="0" {{ old('dat_chuan', $phieuCham->dat_chuan ?? 1) == 0 ? 'checked' : '' }}>
                                <label class="form-check-label" for="dat_chuan_no">
                                    <i class="fa fa-times-circle text-danger me-1"></i> Không đạt
                                </label>
                            </div>
                        </div>

                        {{-- Yêu cầu điều chỉnh --}}
                        <div class="mb-3">
                            <label for="yeu_cau_dieu_chinh" class="form-label fw-bold">
                                Yêu cầu điều chỉnh, thay đổi, bổ sung (nếu có):
                            </label>
                            <textarea class="form-control" id="yeu_cau_dieu_chinh" name="yeu_cau_dieu_chinh" 
                                      rows="3">{{ old('yeu_cau_dieu_chinh', $phieuCham->yeu_cau_dieu_chinh ?? '') }}</textarea>
                        </div>

                        {{-- Ưu điểm --}}
                        <div class="mb-3">
                            <label for="uu_diem" class="form-label fw-bold">
                                Những ưu điểm chính: <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('uu_diem') is-invalid @enderror" 
                                      id="uu_diem" name="uu_diem" rows="4" required>{{ old('uu_diem', $phieuCham->uu_diem ?? '') }}</textarea>
                            @error('uu_diem')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Thiếu sót --}}
                        <div class="mb-3">
                            <label for="thieu_sot" class="form-label fw-bold">
                                Những thiếu sót chính: <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('thieu_sot') is-invalid @enderror" 
                                      id="thieu_sot" name="thieu_sot" rows="4" required>{{ old('thieu_sot', $phieuCham->thieu_sot ?? '') }}</textarea>
                            @error('thieu_sot')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Câu hỏi --}}
                        <div class="mb-3">
                            <label for="cau_hoi" class="form-label fw-bold">
                                Câu hỏi dành cho sinh viên (tối đa 2 câu):
                            </label>
                            <textarea class="form-control" id="cau_hoi" name="cau_hoi" 
                                      rows="3" placeholder="Nhập câu hỏi (nếu có)...">{{ old('cau_hoi', $phieuCham->cau_hoi ?? '') }}</textarea>
                        </div>

                        {{-- Ngày chấm --}}
                        <div class="mb-3">
                            <label for="ngay_cham" class="form-label fw-bold">
                                Ngày chấm: <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control @error('ngay_cham') is-invalid @enderror" 
                                   id="ngay_cham" name="ngay_cham" 
                                   value="{{ old('ngay_cham', $phieuCham->ngay_cham ?? date('Y-m-d')) }}" required>
                            @error('ngay_cham')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Phần 2: Chấm điểm từng sinh viên --}}
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Phần II: Chấm Điểm Chi Tiết</h5>
                    </div>
                    <div class="card-body">
                        @foreach($danhSachSinhVien as $index => $sv)
                        <div class="card mb-3 border-primary">
                            <div class="card-header bg-primary bg-opacity-10">
                                <h6 class="mb-0">
                                    <i class="fa fa-user me-2"></i>
                                    Sinh viên {{ $index + 1 }}: <strong>{{ $sv->hoten }}</strong> - MSSV: <strong>{{ $sv->mssv }}</strong>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="50%">Tiêu chí</th>
                                                <th width="15%" class="text-center">Thang điểm</th>
                                                <th width="35%" class="text-center">Điểm đánh giá</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Phân tích vấn đề</td>
                                                <td class="text-center">2.5</td>
                                                <td>
                                                    <input type="number" step="0.01" min="0" max="2.5" 
                                                           class="form-control @error('sinh_vien.'.$sv->mssv.'.diem_phan_tich') is-invalid @enderror" 
                                                           name="sinh_vien[{{ $sv->mssv }}][diem_phan_tich]"
                                                           value="{{ old('sinh_vien.'.$sv->mssv.'.diem_phan_tich', $diemCu[$sv->mssv]->diem_phan_tich ?? '') }}" 
                                                           required>
                                                    @error('sinh_vien.'.$sv->mssv.'.diem_phan_tich')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Thiết kế vấn đề</td>
                                                <td class="text-center">2.5</td>
                                                <td>
                                                    <input type="number" step="0.01" min="0" max="2.5" 
                                                           class="form-control @error('sinh_vien.'.$sv->mssv.'.diem_thiet_ke') is-invalid @enderror" 
                                                           name="sinh_vien[{{ $sv->mssv }}][diem_thiet_ke]"
                                                           value="{{ old('sinh_vien.'.$sv->mssv.'.diem_thiet_ke', $diemCu[$sv->mssv]->diem_thiet_ke ?? '') }}" 
                                                           required>
                                                    @error('sinh_vien.'.$sv->mssv.'.diem_thiet_ke')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Hiện thực vấn đề</td>
                                                <td class="text-center">2.5</td>
                                                <td>
                                                    <input type="number" step="0.01" min="0" max="2.5" 
                                                           class="form-control @error('sinh_vien.'.$sv->mssv.'.diem_hien_thuc') is-invalid @enderror" 
                                                           name="sinh_vien[{{ $sv->mssv }}][diem_hien_thuc]"
                                                           value="{{ old('sinh_vien.'.$sv->mssv.'.diem_hien_thuc', $diemCu[$sv->mssv]->diem_hien_thuc ?? '') }}" 
                                                           required>
                                                    @error('sinh_vien.'.$sv->mssv.'.diem_hien_thuc')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Kiểm tra sản phẩm</td>
                                                <td class="text-center">2.5</td>
                                                <td>
                                                    <input type="number" step="0.01" min="0" max="2.5" 
                                                           class="form-control @error('sinh_vien.'.$sv->mssv.'.diem_kiem_tra') is-invalid @enderror" 
                                                           name="sinh_vien[{{ $sv->mssv }}][diem_kiem_tra]"
                                                           value="{{ old('sinh_vien.'.$sv->mssv.'.diem_kiem_tra', $diemCu[$sv->mssv]->diem_kiem_tra ?? '') }}" 
                                                           required>
                                                    @error('sinh_vien.'.$sv->mssv.'.diem_kiem_tra')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                            </tr>
                                            <tr class="table-info">
                                                <td colspan="2" class="text-end"><strong>Tổng điểm (thang 10):</strong></td>
                                                <td class="text-center">
                                                    <strong class="text-primary fs-5" id="tong_diem_{{ $sv->mssv }}">0.00</strong>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Đề nghị --}}
                                <div class="mt-3">
                                    <label class="form-label fw-bold">
                                        Đề nghị: <span class="text-danger">*</span>
                                    </label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" 
                                               name="sinh_vien[{{ $sv->mssv }}][de_nghi]" 
                                               id="de_nghi_{{ $sv->mssv }}_duoc" 
                                               value="duoc_bao_ve"
                                               {{ old('sinh_vien.'.$sv->mssv.'.de_nghi', $diemCu[$sv->mssv]->de_nghi ?? '') == 'duoc_bao_ve' ? 'checked' : '' }}
                                               required>
                                        <label class="form-check-label" for="de_nghi_{{ $sv->mssv }}_duoc">
                                            <i class="fa fa-check-circle text-success me-1"></i> Được bảo vệ
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" 
                                               name="sinh_vien[{{ $sv->mssv }}][de_nghi]" 
                                               id="de_nghi_{{ $sv->mssv }}_khong" 
                                               value="khong_duoc_bao_ve"
                                               {{ old('sinh_vien.'.$sv->mssv.'.de_nghi', $diemCu[$sv->mssv]->de_nghi ?? '') == 'khong_duoc_bao_ve' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="de_nghi_{{ $sv->mssv }}_khong">
                                            <i class="fa fa-times-circle text-danger me-1"></i> Không được bảo vệ
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" 
                                               name="sinh_vien[{{ $sv->mssv }}][de_nghi]" 
                                               id="de_nghi_{{ $sv->mssv }}_bo_sung" 
                                               value="bo_sung_hieu_chinh"
                                               {{ old('sinh_vien.'.$sv->mssv.'.de_nghi', $diemCu[$sv->mssv]->de_nghi ?? '') == 'bo_sung_hieu_chinh' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="de_nghi_{{ $sv->mssv }}_bo_sung">
                                            <i class="fa fa-edit text-warning me-1"></i> Bổ sung/hiệu chỉnh để được bảo vệ
                                        </label>
                                    </div>
                                    @error('sinh_vien.'.$sv->mssv.'.de_nghi')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="card">
                    <div class="card-body text-center">
                        <button type="submit" class="btn btn-primary btn-lg me-2">
                            <i class="fa fa-save me-2"></i>Lưu Phiếu Chấm
                        </button>
                        <a href="{{ route('lecturers.chamdiem.huongdan.index') }}" class="btn btn-secondary btn-lg">
                            <i class="fa fa-arrow-left me-2"></i>Quay Lại
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- JavaScript tự động tính tổng điểm --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Lấy tất cả các input điểm
    const diemInputs = document.querySelectorAll('input[type="number"]');
    
    diemInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Lấy MSSV từ name attribute
            const mssv = this.name.match(/sinh_vien\[([^\]]+)\]/)[1];
            
            // Lấy 4 điểm
            const diem1 = parseFloat(document.querySelector(`input[name="sinh_vien[${mssv}][diem_phan_tich]"]`).value) || 0;
            const diem2 = parseFloat(document.querySelector(`input[name="sinh_vien[${mssv}][diem_thiet_ke]"]`).value) || 0;
            const diem3 = parseFloat(document.querySelector(`input[name="sinh_vien[${mssv}][diem_hien_thuc]"]`).value) || 0;
            const diem4 = parseFloat(document.querySelector(`input[name="sinh_vien[${mssv}][diem_kiem_tra]"]`).value) || 0;
            
            // Tính tổng
            const tongDiem = (diem1 + diem2 + diem3 + diem4).toFixed(2);
            
            // Hiển thị
            document.getElementById(`tong_diem_${mssv}`).textContent = tongDiem;
        });
        
        // Trigger để tính tổng ban đầu
        input.dispatchEvent(new Event('input'));
    });
});
</script>
@endsection