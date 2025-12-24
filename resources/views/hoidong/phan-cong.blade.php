@extends('layouts.app')

@section('header', 'Phân Công Đề Tài Cho Hội Đồng')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            {{-- Thông tin hội đồng --}}
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fa fa-info-circle me-2"></i>
                        Hội Đồng: <strong>{{ $hoiDong->tenhd }}</strong> ({{ $hoiDong->mahd }})
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        <strong>Số thành viên:</strong> {{ $hoiDong->thanhVien->count() }}/3 |
                        <strong>Số đề tài đã phân công:</strong> {{ $deTaiDaPhanCong->count() }}
                    </p>
                </div>
            </div>

            {{-- Alerts --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fa fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row">
                {{-- Cột trái: Đề tài chưa phân công --}}
                <div class="col-lg-6 col-md-12 mb-3">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fa fa-list me-2"></i> 
                                Đề Tài Chưa Phân Công ({{ $deTaiKhaDung->count() }})
                            </h5>
                        </div>

                        {{-- ✅ THÊM: Ô tìm kiếm --}}
                        @if(!$deTaiKhaDung->isEmpty())
                        <div class="card-header bg-light">
                            <form method="GET" action="{{ route('admin.hoidong.phancong.form', $hoiDong->id) }}" class="d-flex gap-2">
                                <div class="flex-grow-1">
                                    <input type="text" 
                                           name="search" 
                                           class="form-control" 
                                           placeholder="Tìm kiếm theo tên nhóm, tên đề tài, hoặc tên GV..."
                                           value="{{ $search ?? '' }}">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-search me-1"></i> Tìm
                                </button>
                                <a href="{{ route('admin.hoidong.phancong.form', $hoiDong->id) }}" class="btn btn-secondary">
                                    <i class="fa fa-refresh me-1"></i> Làm Mới
                                </a>
                            </form>
                        </div>
                        @endif

                        <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                            @if($deTaiKhaDung->isEmpty())
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle me-2"></i>
                                    @if($search ?? false)
                                        Không tìm thấy đề tài nào phù hợp với "<strong>{{ $search }}</strong>"
                                    @else
                                        Không còn đề tài nào khả dụng để phân công.
                                    @endif
                                </div>
                            @else
                                <form action="{{ route('admin.hoidong.phancong.store', $hoiDong->id) }}" method="POST">
                                    @csrf

                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle me-2"></i>
                                        <strong>Lưu ý:</strong> Chỉ hiển thị đề tài mà GV hướng dẫn KHÔNG thuộc hội đồng này.
                                    </div>

                                    @foreach($deTaiKhaDung as $index => $dt)
                                    <div class="card mb-3 border-warning">
                                        <div class="card-body p-3">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input detaiCheckbox" 
                                                       type="checkbox" 
                                                       name="nhom_id[]" 
                                                       value="{{ $dt->nhom_id }}" 
                                                       id="nhom_{{ $dt->nhom_id }}"
                                                       data-index="{{ $index }}">
                                                <label class="form-check-label w-100" for="nhom_{{ $dt->nhom_id }}">
                                                    <strong class="text-primary">{{ $dt->nhom }}</strong> - {{ $dt->tendt }}
                                                    <br>
                                                    <small class="text-muted">
                                                        GV: {{ $dt->gv_huongdan }}
                                                    </small>
                                                    <br>
                                                    <small>
                                                        <strong>Sinh viên:</strong>
                                                        @foreach($sinhVienTheoNhom[$dt->nhom_id] as $sv)
                                                            {{ $sv->hoten }} ({{ $sv->mssv }})@if(!$loop->last), @endif
                                                        @endforeach
                                                    </small>
                                                </label>
                                            </div>

                                            {{-- ✅ INPUT THỨ TỰ BÁO CÁO --}}
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fa fa-sort-numeric-down me-2"></i>Thứ tự báo cáo
                                                </span>
                                                <input type="number" 
                                                       class="form-control thuTuInput" 
                                                       name="thu_tu[]" 
                                                       value=""
                                                       min="1"
                                                       placeholder="VD: 1, 2, 3..."
                                                       data-nhom-id="{{ $dt->nhom_id }}"
                                                       disabled>
                                            </div>
                                            <small class="text-muted d-block mt-2">
                                                ✓ Chọn checkbox để bật input nhập thứ tự
                                            </small>
                                        </div>
                                    </div>
                                    @endforeach

                                    <div class="text-center mt-3">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fa fa-check me-2"></i> Phân Công Đề Tài Đã Chọn
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Cột phải: Đề tài đã phân công --}}
                <div class="col-lg-6 col-md-12 mb-3">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fa fa-check-circle me-2"></i> 
                                Đề Tài Đã Phân Công ({{ $deTaiDaPhanCong->count() }})
                            </h5>
                        </div>
                        <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                            @if($deTaiDaPhanCong->isEmpty())
                                <div class="alert alert-warning">
                                    <i class="fa fa-exclamation-triangle me-2"></i>
                                    Chưa có đề tài nào được phân công!
                                </div>
                            @else
                                @foreach($deTaiDaPhanCong as $index => $dt)
                                <div class="card mb-2 border-success">
                                    <div class="card-header bg-success bg-opacity-10 py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                {{-- ✅ HIỂN THỊ THỨ TỰ BÁO CÁO --}}
                                                @if($dt->thu_tu)
                                                    <span class="badge bg-info me-2">
                                                        <i class="fa fa-sort-numeric-down me-1"></i>Thứ tự {{ $dt->thu_tu }}
                                                    </span>
                                                @endif
                                                <strong class="text-success">
                                                    {{ $dt->nhom }}
                                                </strong>
                                            </div>
                                            <form action="{{ route('admin.hoidong.phancong.delete', [$hoiDong->id, $dt->nhom_id]) }}" 
                                                  method="POST" 
                                                  onsubmit="return confirm('Xác nhận xóa đề tài này?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="card-body p-3">
                                        <p class="mb-1"><strong>{{ $dt->tendt }}</strong></p>
                                        <p class="mb-1 text-muted small">
                                            GV: {{ $dt->gv_huongdan }}
                                        </p>
                                        <hr class="my-2">
                                        <p class="mb-0 small">
                                            <strong>Sinh viên:</strong><br>
                                            @foreach($sinhVienDaPhanCong[$dt->nhom_id] as $sv)
                                                <span class="badge bg-secondary me-1">{{ $sv->hoten }} ({{ $sv->mssv }})</span>
                                            @endforeach
                                        </p>
                                    </div>
                                </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Nút quay lại --}}
            <div class="text-center mt-3">
                <a href="{{ route('admin.hoidong.show', $hoiDong->id) }}" class="btn btn-secondary btn-lg">
                    <i class="fa fa-arrow-left me-2"></i> Quay Lại Chi Tiết Hội Đồng
                </a>
                <a href="{{ route('admin.hoidong.index') }}" class="btn btn-outline-secondary btn-lg">
                    <i class="fa fa-list me-2"></i> Danh Sách Hội Đồng
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// ✅ Bật/Tắt input thứ tự khi checkbox được chọn
document.querySelectorAll('.detaiCheckbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const thuTuInput = this.closest('.card-body').querySelector('.thuTuInput');
        
        if (this.checked) {
            thuTuInput.disabled = false;
            thuTuInput.focus();
        } else {
            thuTuInput.disabled = true;
            thuTuInput.value = '';
        }
    });
});

// ✅ Validate form trước khi submit
document.querySelectorAll('form').forEach(form => {
    if (form.action.includes('phancong.store')) {
        form.addEventListener('submit', function(e) {
            const checkedBoxes = document.querySelectorAll('.detaiCheckbox:checked');
            
            let isValid = true;
            checkedBoxes.forEach(checkbox => {
                const thuTuInput = checkbox.closest('.card-body').querySelector('.thuTuInput');
                if (!thuTuInput.value || thuTuInput.value < 1) {
                    isValid = false;
                    thuTuInput.classList.add('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Vui lòng nhập thứ tự báo cáo cho tất cả đề tài được chọn!');
            }
        });
    }
});
</script>

<style>
.thuTuInput:disabled {
    background-color: #f5f5f5;
    cursor: not-allowed;
}

.thuTuInput {
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
    font-size: 0.85rem;
    padding: 0.5rem 0.75rem;
}
</style>

@endsection