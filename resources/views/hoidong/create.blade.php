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
                                   placeholder="VD: Hội đồng bảo vệ ĐATN K19 - Nhóm 1" 
                                   required>
                            @error('tenhd')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror>
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
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Chọn Thành Viên Hội Đồng (3-4 người) <span class="text-danger">*</span>
                            </label>

                            @error('thanh_vien')
                                <div class="alert alert-danger">{{ $message }}</div>
                            @enderror

                            <div id="thanhVienContainer" class="row">
                                @for($i = 1; $i <= 3; $i++)
                                <div class="col-md-6 mb-3 thanhVienItem">
                                    <label class="form-label">
                                        Thành viên {{ $i }}
                                        @if($i <= 2)
                                            <span class="text-danger">*</span>
                                        @endif
                                    </label>
                                    <div class="input-group">
                                        <select name="thanh_vien[]" 
                                                class="form-select @error('thanh_vien.'.$i-1) is-invalid @enderror giangvienSelect" 
                                                {{ $i <= 3 ? 'required' : '' }}>
                                            <option value="">-- Chọn giảng viên --</option>
                                            @foreach($danhSachGiangVien as $gv)
                                                <option value="{{ $gv->magv }}" 
                                                    {{ (old('thanh_vien.'.$i-1) == $gv->magv) ? 'selected' : '' }}>
                                                    {{ $gv->hoten }}
                                                </option>
                                            @endforeach
                                        </select>
                                        
                                        {{-- Dropdown vai trò --}}
                                        <select name="vai_tro[]" 
                                                class="form-select vaiTroSelect"
                                                {{ $i <= 3 ? 'required' : '' }}>
                                            <option value="">-- Chọn vai trò --</option>
                                            <option value="chu_tich" {{ old('vai_tro.'.$i-1) == 'chu_tich' ? 'selected' : '' }}>Chủ tịch</option>
                                            <option value="thu_ky" {{ old('vai_tro.'.$i-1) == 'thu_ky' ? 'selected' : '' }}>Thư ký</option>
                                            <option value="thanh_vien" {{ old('vai_tro.'.$i-1) == 'thanh_vien' ? 'selected' : '' }}>Thành viên</option>
                                        </select>
                                    </div>
                                    @error('thanh_vien.'.$i-1)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                @endfor
                            </div>

                            <div class="text-center mb-3">
                                <button type="button" id="themThanhVienBtn" class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-plus me-1"></i> Thêm Thành Viên Thứ 4 (Tối đa)
                                </button>
                            </div>

                            <div class="alert alert-info mt-3">
                                <i class="fa fa-info-circle me-2"></i>
                                <strong>Lưu ý:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Hội đồng phải có tối thiểu 3 thành viên, tối đa 4 thành viên</li>
                                    <li>Phải có đúng 1 Chủ tịch và 1 Thư ký</li>
                                    <li>Không được chọn trùng giảng viên</li>
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
document.getElementById('themThanhVienBtn').addEventListener('click', function() {
    const container = document.getElementById('thanhVienContainer');
    const itemCount = document.querySelectorAll('.thanhVienItem').length;
    
    // Chỉ cho thêm nếu chưa đến 4 thành viên
    if (itemCount >= 4) {
        alert('Tối đa 4 thành viên!');
        return;
    }
    
    const newIndex = itemCount;
    const newItem = `
        <div class="col-md-6 mb-3 thanhVienItem">
            <label class="form-label">Thành viên ${itemCount + 1}</label>
            <div class="input-group">
                <select name="thanh_vien[]" class="form-select giangvienSelect">
                    <option value="">-- Chọn giảng viên --</option>
                    @foreach($danhSachGiangVien as $gv)
                        <option value="{{ $gv->magv }}">{{ $gv->hoten }}</option>
                    @endforeach
                </select>
                
                <select name="vai_tro[]" class="form-select vaiTroSelect">
                    <option value="">-- Chọn vai trò --</option>
                    <option value="chu_tich">Chủ tịch</option>
                    <option value="thu_ky">Thư ký</option>
                    <option value="thanh_vien">Thành viên</option>
                </select>
                
                <button type="button" class="btn btn-outline-danger xoaThanhVienBtn">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    const wrapper = document.createElement('div');
    wrapper.innerHTML = newItem;
    container.appendChild(wrapper.firstElementChild);
    
    // Gắn event cho nút xóa
    wrapper.firstElementChild.querySelector('.xoaThanhVienBtn').addEventListener('click', function() {
        wrapper.firstElementChild.remove();
        // Ẩn nút "Thêm" nếu < 4 người
        if (document.querySelectorAll('.thanhVienItem').length < 4) {
            document.getElementById('themThanhVienBtn').style.display = 'inline-block';
        }
    });
    
    // Ẩn nút "Thêm" nếu đã 4 người
    if (itemCount + 1 >= 4) {
        this.style.display = 'none';
    }
});

// Ẩn nút "Thêm" nếu đã có 4 thành viên lúc load trang
window.addEventListener('load', function() {
    if (document.querySelectorAll('.thanhVienItem').length >= 4) {
        document.getElementById('themThanhVienBtn').style.display = 'none';
    }
});
</script>

<style>
.input-group .form-select {
    min-width: 180px;
}
</style>

@endsection