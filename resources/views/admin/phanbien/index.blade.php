@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <h4 class="mb-4">Phân công phản biện theo nhóm</h4>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form id="phancong-form" action="{{ route('admin.phanbien.store') }}" method="POST">
        @csrf

        <div class="row">
            {{-- Bảng danh sách nhóm --}}
            <div class="col-md-8">
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-folder2-open me-2"></i>Danh sách nhóm đề tài</span>
                        <span class="badge bg-light text-dark">Tổng: {{ count($groupedTopics) }} nhóm</span>
                    </div>
                    <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-hover table-bordered mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="text-center" style="width: 50px;">
                                        <input type="checkbox" id="checkAll" class="form-check-input">
                                    </th>
                                    <th style="width: 80px;">Nhóm</th>
                                    <th style="width: 200px;">Sinh viên (MSSV)</th>
                                    <th>Đề tài</th>
                                    <th style="width: 150px;">GVHD</th>
                                    <th style="width: 150px;">GV Phản biện</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($groupedTopics as $topic)
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" 
                                               name="selected_topics[]" 
                                               value="{{ $topic->nhom }}" 
                                               class="form-check-input topic-checkbox"
                                               data-magv-hd="{{ $topic->magv_hd }}">
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ $topic->nhom }}</span>
                                        <br>
                                        <small class="text-muted">({{ $topic->soluong_sv }} SV)</small>
                                    </td>
                                    <td>
                                        @foreach($topic->sinhvien as $sv)
                                            <div class="mb-1">
                                                <small><strong>{{ $sv['mssv'] }}</strong></small><br>
                                                <small class="text-muted">{{ $sv['tensv'] }}</small>
                                            </div>
                                        @endforeach
                                    </td>
                                    <td>
                                        <small><strong>{{ $topic->tendt }}</strong></small>
                                    </td>
                                    <td>
                                        <small class="text-primary">
                                            <i class="bi bi-person-badge"></i> {{ $topic->tengv_hd ?? 'Chưa có' }}
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        @if($topic->magv_phanbien)
                                            <span class="badge bg-success mb-1">
                                                <i class="bi bi-check-circle"></i> Đã phân
                                            </span>
                                            <br>
                                            <small class="text-success">{{ $topic->tengv_phanbien }}</small>
                                        @else
                                            <span class="badge bg-warning text-dark">
                                                <i class="bi bi-clock"></i> Chưa phân
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Chưa có nhóm nào
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Form chọn giảng viên phản biện --}}
            <div class="col-md-4">
                <div class="card shadow-sm rounded-3 sticky-top" style="top: 20px;">
                    <div class="card-header bg-success text-white">
                        <i class="bi bi-person-check-fill me-2"></i>Chọn giảng viên phản biện
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Hướng dẫn:</strong><br>
                            1. Chọn các nhóm bên trái<br>
                            2. Chọn 1 giảng viên phản biện<br>
                            3. Nhấn "Lưu phân công"
                        </div>

                        <div class="mb-3">
                            <label for="magv_phanbien" class="form-label fw-bold">
                                <i class="bi bi-person-circle text-primary"></i> Giảng viên phản biện
                                <span class="text-danger">*</span>
                            </label>
                            <select name="magv_phanbien" id="magv_phanbien" class="form-select form-select-lg" required>
                                <option value="">-- Chọn giảng viên --</option>
                                @foreach ($giangviens as $gv)
                                <option value="{{ $gv->magv }}">{{ $gv->hoten }}</option>
                                @endforeach
                            </select>
                        </div>

                        <hr>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save me-2"></i>Lưu phân công
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="clearSelection()">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Xóa lựa chọn
                            </button>
                        </div>

                        <div class="mt-3 p-3 bg-light rounded">
                            <div class="mb-2">
                                <i class="bi bi-check-square text-success"></i>
                                <strong>Đã chọn:</strong> <span id="selectedCount">0</span> nhóm
                            </div>
                            <small class="text-muted">
                                <i class="bi bi-lightbulb"></i> 
                                Có thể chọn nhiều nhóm để phân công cùng lúc
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Script --}}
<script>
// Chọn tất cả checkbox
document.getElementById('checkAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.topic-checkbox');
    checkboxes.forEach(chk => chk.checked = this.checked);
    updateSelectedCount();
});

// Cập nhật số lượng đã chọn
document.querySelectorAll('.topic-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateSelectedCount);
});

function updateSelectedCount() {
    const count = document.querySelectorAll('.topic-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = count;
}

// Xóa lựa chọn
function clearSelection() {
    document.querySelectorAll('.topic-checkbox').forEach(chk => chk.checked = false);
    document.getElementById('checkAll').checked = false;
    document.getElementById('magv_phanbien').value = '';
    updateSelectedCount();
}

// Kiểm tra trước khi submit
document.querySelector('form').addEventListener('submit', function(e) {
    const selectedTopics = document.querySelectorAll('.topic-checkbox:checked');
    
    if (selectedTopics.length === 0) {
        e.preventDefault();
        alert('Vui lòng chọn ít nhất 1 nhóm!');
        return false;
    }
    
    const magvPhanbien = document.getElementById('magv_phanbien').value;
    if (!magvPhanbien) {
        e.preventDefault();
        alert('Vui lòng chọn giảng viên phản biện!');
        return false;
    }
    
    // Kiểm tra không chọn GVHD làm phản biện
    let hasError = false;
    selectedTopics.forEach(chk => {
        const magvHd = chk.getAttribute('data-magv-hd');
        if (magvHd === magvPhanbien) {
            alert('Cảnh báo: Giảng viên hướng dẫn không được làm phản biện!');
            hasError = true;
        }
    });
    
    if (hasError) {
        e.preventDefault();
        return false;
    }
    
    return confirm(`Bạn có chắc muốn phân công ${selectedTopics.length} nhóm cho giảng viên này?`);
});

// Khởi tạo count
updateSelectedCount();
</script>
@endsection