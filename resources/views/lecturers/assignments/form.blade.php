@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="mb-4">
        <h4 class="mb-1">Tạo nhóm luận văn từ sinh viên được phân công</h4>
        
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Form Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form action="{{ route('lecturers.assignments.store') }}" method="POST">
                @csrf
                
                <div class="row g-3">
                    <!-- 🆕 MÃ NHÓM READ-ONLY (TỰ ĐỘNG) -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Mã Nhóm <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               id="nhomCode" 
                               class="form-control" 
                               value="VD: {{ session('user')->magv }}TH2805"
                               readonly
                               style="background-color: #f0f0f0; font-weight: bold; color: #0066cc;">
                        <small class="text-muted">
                            <i class="fas fa-lock me-1"></i>Mã nhóm tự động (không thể chỉnh sửa)
                        </small>
                    </div>

                    <!-- TRẠNG THÁI -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Trạng Thái <span class="text-danger">*</span>
                        </label>
                        <select name="trangthai" class="form-select" required>
                            <option value="">-- Chọn trạng thái --</option>
                            <option value="chua_bat_dau" selected>Chưa bắt đầu</option>
                            <option value="dang_thuc_hien">Đang thực hiện</option>
                            <option value="hoan_thanh">Hoàn thành</option>
                            <option value="dinh_chi">Đình chỉ</option>
                        </select>
                        @error('trangthai')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- TÊN ĐỀ TÀI -->
                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            Tên Đề Tài <span class="text-danger">*</span>
                        </label>
                        <textarea name="tendt" 
                                  class="form-control" 
                                  rows="3"
                                  placeholder="Nhập tên đề tài luận văn" 
                                  required>{{ old('tendt') }}</textarea>
                        @error('tendt')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- CHỌN SINH VIÊN -->
                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            Chọn Sinh Viên <span class="text-danger">*</span>
                        </label>
                        
                        @if(isset($availableStudents) && $availableStudents->count() > 0)
                            <div class="student-select-box">
                                @foreach($availableStudents as $student)
                                    <div class="form-check student-item">
                                        <input class="form-check-input student-checkbox" 
                                               type="checkbox" 
                                               name="sinhvien[]" 
                                               value="{{ $student->mssv }}" 
                                               id="student_{{ $student->mssv }}"
                                               data-mssv="{{ $student->mssv }}">
                                        <label class="form-check-label" for="student_{{ $student->mssv }}">
                                            <strong>{{ $student->mssv }}</strong> - {{ $student->hoten }}
                                            @if($student->lop)
                                                <span class="text-muted">({{ $student->lop }})</span>
                                            @endif
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Chọn 1-2 sinh viên để tạo nhóm. Mã nhóm sẽ dùng MSSV của <strong>sinh viên đầu tiên</strong>.
                            </small>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Tất cả sinh viên đã được phân nhóm hoặc không có sinh viên được phân công
                            </div>
                        @endif
                        @error('sinhvien')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- BUTTONS -->
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Lưu Nhóm
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload()">
                        <i class="fas fa-redo me-2"></i>Làm mới
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- DANH SÁCH SINH VIÊN ĐÃ CÓ NHÓM -->
    @if(isset($students) && $students->count() > 0)
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-list me-2"></i>Danh Sách Sinh Viên</h6>
                <button type="button" class="btn btn-success btn-sm" id="btnSaveStatus">
                    <i class="fas fa-save me-1"></i>Lưu Thay Đổi
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 12%">MSSV</th>
                                <th style="width: 22%">Họ Tên</th>
                                <th style="width: 12%">Lớp</th>
                                <th style="width: 18%">Mã Nhóm</th>
                                <th style="width: 24%">Đề Tài</th>
                                <th style="width: 12%">Trạng Thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $student)
                                <tr>
                                    <td><strong>{{ $student->mssv }}</strong></td>
                                    <td>{{ $student->hoten }}</td>
                                    <td>{{ $student->lop ?? 'N/A' }}</td>
                                    <td>
                                        @if($student->nhom)
                                            <span class="badge bg-success" title="{{ $student->nhom }}">
                                                {{ $student->nhom }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">Chưa có nhóm</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $student->tendt ? substr($student->tendt, 0, 40) . '...' : '-' }}</small>
                                    </td>
                                    <td>
                                        @if($student->nhom)
                                            <select class="form-select form-select-sm status-select" 
                                                    data-nhom="{{ $student->nhom }}"
                                                    style="width: 150px;">
                                                <option value="chua_bat_dau" {{ $student->trangthai == 'chua_bat_dau' ? 'selected' : '' }}>
                                                    Chưa bắt đầu
                                                </option>
                                                <option value="dang_thuc_hien" {{ $student->trangthai == 'dang_thuc_hien' ? 'selected' : '' }}>
                                                    Đang thực hiện
                                                </option>
                                                <option value="hoan_thanh" {{ $student->trangthai == 'hoan_thanh' ? 'selected' : '' }}>
                                                    Hoàn thành
                                                </option>
                                                <option value="dinh_chi" {{ $student->trangthai == 'dinh_chi' ? 'selected' : '' }}>
                                                    Đình chỉ
                                                </option>
                                            </select>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
body {
    background: #f8f9fa;
}

.card {
    border-radius: 12px;
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
}

.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    padding: 10px 14px;
}

.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.1);
}

.form-control:read-only {
    cursor: not-allowed;
    background-color: #f0f0f0;
}

.form-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 8px;
}

.btn {
    border-radius: 8px;
    padding: 10px 20px;
    font-weight: 500;
}

.student-select-box {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    background: #f8f9fa;
}

.student-item {
    padding: 10px;
    margin-bottom: 8px;
    background: white;
    border-radius: 6px;
    transition: all 0.2s;
    cursor: pointer;
}

.student-item:hover {
    background: #e7f3ff;
}

.student-item:last-child {
    margin-bottom: 0;
}

.form-check-input {
    width: 1.2em;
    height: 1.2em;
    margin-top: 0.15em;
    cursor: pointer;
}

.form-check-label {
    margin-left: 8px;
    cursor: pointer;
    flex: 1;
}

.table th {
    font-weight: 600;
    color: #495057;
    font-size: 14px;
}

.table td {
    font-size: 14px;
    vertical-align: middle;
}

.badge {
    padding: 6px 12px;
    font-weight: 500;
}

.alert {
    border-radius: 8px;
}

/* Style cho mã nhóm */
#nhomCode {
    font-size: 16px;
    letter-spacing: 1px;
    text-transform: uppercase;
}

/* Style cho status select */
.status-select {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 13px;
    transition: all 0.2s;
    cursor: pointer;
}

.status-select:hover {
    border-color: #0d6efd;
}

.status-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
    outline: none;
}

/* Toast styling */
.toast-container {
    z-index: 9999;
}

.toast {
    min-width: 250px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    const nhomCodeInput = document.getElementById('nhomCode');
    const magv = '{{ session("user")->magv }}';
    
    /**
     * 🆕 FUNCTION: Tự động tạo mã nhóm
     * Format: {magv}TH{4 số cuối MSSV}
     */
    function generateNhomCode() {
        // Lấy sinh viên đầu tiên được chọn
        const checkedBoxes = document.querySelectorAll('.student-checkbox:checked');
        
        if (checkedBoxes.length === 0) {
            // Không có sinh viên nào chọn
            nhomCodeInput.value = 'VD: ' + magv + 'TH2805';
            nhomCodeInput.style.color = '#999';
        } else {
            // Lấy MSSV của sinh viên đầu tiên
            const firstMssv = checkedBoxes[0].getAttribute('data-mssv');
            const lastFourDigits = firstMssv.slice(-4);
            const generatedCode = magv + 'TH' + lastFourDigits;
            
            nhomCodeInput.value = generatedCode;
            nhomCodeInput.style.color = '#0066cc';
        }
    }
    
    /**
     * ⚠️ Giới hạn tối đa 2 sinh viên + Tự động tạo mã nhóm
     */
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
            
            if (checkedCount > 2) {
                this.checked = false;
                alert('Mỗi nhóm chỉ được chọn tối đa 2 sinh viên!');
                return;
            }
            
            // ✅ Tự động tạo mã nhóm khi chọn sinh viên
            generateNhomCode();
        });
    });
    
    // 🆕 Khởi tạo mã nhóm lúc load trang
    generateNhomCode();
    
    /**
     * 🆕 XỬ LÝ LƯU TRẠNG THÁI
     */
    const btnSaveStatus = document.getElementById('btnSaveStatus');
    const statusSelects = document.querySelectorAll('.status-select');
    
    if (btnSaveStatus) {
        btnSaveStatus.addEventListener('click', function() {
            // Thu thập tất cả thay đổi trạng thái
            const changes = [];
            
            statusSelects.forEach(select => {
                const nhom = select.getAttribute('data-nhom');
                const newStatus = select.value;
                const oldStatus = select.getAttribute('data-old-status') || select.value;
                
                // Chỉ thêm nếu có thay đổi
                if (newStatus !== oldStatus) {
                    changes.push({
                        nhom: nhom,
                        trangthai: newStatus
                    });
                }
            });
            
            if (changes.length === 0) {
                alert('Không có thay đổi nào để lưu!');
                return;
            }
            
            // Hiển thị loading
            const originalText = btnSaveStatus.innerHTML;
            btnSaveStatus.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...';
            btnSaveStatus.disabled = true;
            
            // Gửi request lưu trạng thái
            fetch('{{ route("lecturers.assignments.update-all-status") }}', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    trangthai: changes
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast('success', 'Lưu thay đổi thành công!');
                    
                    // Cập nhật data-old-status
                    changes.forEach(change => {
                        const select = document.querySelector(`.status-select[data-nhom="${change.nhom}"]`);
                        if (select) {
                            select.setAttribute('data-old-status', change.trangthai);
                        }
                    });
                    
                    // Reload trang sau 1 giây
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast('error', data.message || 'Lỗi khi lưu!');
                    btnSaveStatus.innerHTML = originalText;
                    btnSaveStatus.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Có lỗi xảy ra: ' + error.message);
                btnSaveStatus.innerHTML = originalText;
                btnSaveStatus.disabled = false;
            });
        });
        
        // Lưu giá trị ban đầu
        statusSelects.forEach(select => {
            select.setAttribute('data-old-status', select.value);
        });
    }
});

/**
 * 🆕 FUNCTION: Hiển thị toast notification
 */
function showToast(type, message) {
    const toastContainer = document.querySelector('.toast-container') || createToastContainer();
    
    const bgColor = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
    const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white ${bgColor} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas ${icon} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 5000
    });
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

/**
 * 🆕 FUNCTION: Tạo container cho toast
 */
function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}
</script>

@endsection