@extends('layouts.app')

@section('header', 'Tạo Hội Đồng Mới')

@section('content')
<div class="container-fluid py-4">
    <form action="{{ route('admin.hoidong.store') }}" method="POST">
        @csrf
        
        <div class="row">
            <div class="col-lg-8 col-md-12 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fa fa-plus-circle me-2"></i> Tạo Hội Đồng Mới
                        </h4>
                    </div>

                    <div class="card-body">
                        {{-- Alerts --}}
                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fa fa-exclamation-circle me-2"></i>{{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        {{-- Mã hội đồng --}}
                        <div class="mb-3">
                            <label for="mahd" class="form-label fw-bold">
                                Mã Hội Đồng <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('mahd') is-invalid @enderror" 
                                   id="mahd" 
                                   name="mahd" 
                                   value="{{ old('mahd') }}" 
                                   placeholder="VD: HD2025_01" 
                                   required>
                            @error('mahd')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Tối đa 20 ký tự, không trùng với hội đồng đã có</small>
                        </div>

                        {{-- Tên hội đồng --}}
                        <div class="mb-3">
                            <label for="tenhd" class="form-label fw-bold">
                                Tên Hội Đồng <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('tenhd') is-invalid @enderror" 
                                   id="tenhd" 
                                   name="tenhd" 
                                   value="{{ old('tenhd') }}" 
                                   placeholder="VD: Hội đồng bảo vệ ĐATN K19" 
                                   required>
                            @error('tenhd')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Ghi chú --}}
                        <div class="mb-3">
                            <label for="ghi_chu" class="form-label fw-bold">Ghi Chú</label>
                            <textarea class="form-control" 
                                      id="ghi_chu" 
                                      name="ghi_chu" 
                                      rows="3" 
                                      placeholder="Ghi chú thêm (nếu có)...">{{ old('ghi_chu') }}</textarea>
                        </div>

                        <hr class="my-4">

                        {{-- Chọn thành viên --}}
                        <div class="mb-4">
                            <label class="form-label fw-bold mb-3">
                                <i class="fa fa-users me-2 text-primary"></i>Chọn Thành Viên Hội Đồng (3-4 người)
                                <span class="text-danger">*</span>
                            </label>

                            @error('thanh_vien')
                                <div class="alert alert-danger">{{ $message }}</div>
                            @enderror

                            <div id="thanhVienContainer">
                                {{-- Thành viên 1, 2, 3 (bắt buộc) --}}
                                @for($i = 1; $i <= 3; $i++)
                                <div class="card mb-2 border-primary thanhVienItem">
                                    <div class="card-body">
                                        <div class="row align-items-end">
                                            <div class="col-md-8">
                                                <label class="form-label fw-bold">
                                                    Thành Viên {{ $i }}
                                                    <span class="badge bg-danger">Bắt buộc</span>
                                                </label>
                                                <select name="thanh_vien[]" 
                                                        class="form-select @error('thanh_vien.'.$i-1) is-invalid @enderror giangvienSelect" 
                                                        required>
                                                    <option value="">-- Chọn giảng viên --</option>
                                                    @foreach($danhSachGiangVien as $gv)
                                                        <option value="{{ $gv->magv }}" 
                                                            {{ (old('thanh_vien.'.$i-1) == $gv->magv) ? 'selected' : '' }}>
                                                            {{ $gv->hoten }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold">Vai Trò</label>
                                                <select name="vai_tro[]" 
                                                        class="form-select vaiTroSelect"
                                                        required>
                                                    <option value="">-- Chọn vai trò --</option>
                                                    <option value="chu_tich" {{ old('vai_tro.'.$i-1) == 'chu_tich' ? 'selected' : '' }}>
                                                        👑 Chủ tịch
                                                    </option>
                                                    <option value="thu_ky" {{ old('vai_tro.'.$i-1) == 'thu_ky' ? 'selected' : '' }}>
                                                        📋 Thư ký
                                                    </option>
                                                    <option value="thanh_vien" {{ old('vai_tro.'.$i-1) == 'thanh_vien' ? 'selected' : '' }}>
                                                        👤 Thành viên
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endfor

                                {{-- Thành viên 4 (nếu thêm) --}}
                                <div id="thanhVienThu4Container"></div>
                            </div>

                            <div class="text-center mt-3">
                                <button type="button" id="themThanhVienBtn" class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-plus me-1"></i> Thêm Thành Viên Thứ 4 (Tối đa)
                                </button>
                            </div>

                            <div class="alert alert-info mt-3">
                                <i class="fa fa-info-circle me-2"></i>
                                <strong>Lưu ý:</strong>
                                <ul class="mb-0 mt-2 ps-3">
                                    <li>Hội đồng phải có tối thiểu <strong>3 thành viên</strong>, tối đa <strong>4 thành viên</strong></li>
                                    <li>Phải có đúng <strong>1 Chủ tịch</strong> và <strong>1 Thư ký</strong></li>
                                    <li>Không được chọn trùng giảng viên</li>
                                    <li>Thành viên thứ 4 <strong>có thể để trống</strong> (nullable)</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer text-center">
                        <button type="submit" class="btn btn-primary btn-lg me-2">
                            <i class="fa fa-save me-2"></i>Tạo Hội Đồng
                        </button>
                        <a href="{{ route('admin.hoidong.index') }}" class="btn btn-secondary btn-lg">
                            <i class="fa fa-arrow-left me-2"></i>Quay Lại
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// ✅ FIX: Lọc giảng viên đã chọn - Ẩn giảng viên trùng lặp
function updateGiangvienSelects() {
    const allSelects = document.querySelectorAll('.giangvienSelect');
    const selectedValues = [];
    
    // Lấy tất cả giá trị đã chọn (trừ empty value)
    allSelects.forEach(select => {
        if (select.value) {
            selectedValues.push(select.value);
        }
    });
    
    // Ẩn/hiển thị options dựa trên giá trị đã chọn
    allSelects.forEach((select, selectIndex) => {
        select.querySelectorAll('option').forEach(option => {
            if (!option.value) {
                // Luôn hiển thị option trống
                option.style.display = 'block';
            } else if (selectedValues.includes(option.value)) {
                // Ẩn option nếu giá trị này đã được chọn ở select khác
                const isCurrentValue = select.value === option.value;
                option.style.display = isCurrentValue ? 'block' : 'none';
            } else {
                // Hiển thị option nếu chưa được chọn
                option.style.display = 'block';
            }
        });
    });
}

// Gắn event change cho tất cả select
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('giangvienSelect')) {
        updateGiangvienSelects();
    }
});

// Nút thêm thành viên 4
document.getElementById('themThanhVienBtn').addEventListener('click', function() {
    const itemCount = document.querySelectorAll('.thanhVienItem').length;
    
    // Chỉ cho thêm nếu chưa đến 4 thành viên
    if (itemCount >= 4) {
        alert('Tối đa 4 thành viên!');
        return;
    }
    
    // Lấy tất cả options từ select đầu tiên
    const firstSelect = document.querySelector('.giangvienSelect');
    const giangvienOptions = Array.from(firstSelect.options)
        .map(opt => `<option value="${opt.value}">${opt.textContent}</option>`)
        .join('');
    
    const newItem = `
        <div class="card mb-2 border-success thanhVienItem">
            <div class="card-body">
                <div class="row align-items-end">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">
                            Thành Viên 4
                            <span class="badge bg-warning text-dark">Tùy chọn</span>
                        </label>
                        <select name="thanh_vien[]" class="form-select giangvienSelect">
                            ${giangvienOptions}
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Vai Trò</label>
                        <div class="input-group">
                            <select name="vai_tro[]" class="form-select vaiTroSelect">
                                <option value="">-- Chọn vai trò --</option>
                                <option value="chu_tich">👑 Chủ tịch</option>
                                <option value="thu_ky">📋 Thư ký</option>
                                <option value="thanh_vien">👤 Thành viên</option>
                            </select>
                            <button type="button" class="btn btn-outline-danger xoaThanhVienBtn" title="Xóa thành viên này">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    const wrapper = document.createElement('div');
    wrapper.innerHTML = newItem;
    const newElement = wrapper.firstElementChild;
    document.getElementById('thanhVienThu4Container').appendChild(newElement);
    
    // Gắn event cho nút xóa
    newElement.querySelector('.xoaThanhVienBtn').addEventListener('click', function() {
        newElement.remove();
        // Hiển thị lại nút "Thêm"
        document.getElementById('themThanhVienBtn').style.display = 'inline-block';
        updateGiangvienSelects();
    });
    
    // Ẩn nút "Thêm" sau khi thêm thành viên 4
    this.style.display = 'none';
    
    // Cập nhật lọc
    updateGiangvienSelects();
});

// Ẩn nút "Thêm" nếu đã có 4 thành viên lúc load trang
window.addEventListener('load', function() {
    if (document.querySelectorAll('.thanhVienItem').length >= 4) {
        document.getElementById('themThanhVienBtn').style.display = 'none';
    }
    updateGiangvienSelects();
});
</script>

<style>
.form-select {
    border-radius: 8px;
}

.card {
    border-radius: 12px;
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.badge {
    font-size: 0.75rem;
    padding: 4px 8px;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
}
</style>

@endsection