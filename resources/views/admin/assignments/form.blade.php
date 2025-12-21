@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="mb-4">
        <h4 class="mb-1">📋 Phân Công Giảng Viên Cho Sinh Viên</h4>
    </div>

    <!-- Alert -->
    @if($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Form -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('admin.assignments.store') }}" method="POST" id="assignmentForm">
                @csrf

                <div class="row g-4">
                    <!-- Cột 1: Chọn Giảng Viên -->
                    <div class="col-lg-4 col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-bold mb-3">
                                <i class="fas fa-chalkboard-user text-primary me-2"></i>
                                Chọn Giảng Viên <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-lg" name="magv" id="lecturerSelect" required>
                                <option value="">-- Chọn giảng viên --</option>
                                @forelse($lecturers as $lecturer)
                                    <option value="{{ $lecturer->magv }}">
                                        {{ $lecturer->hoten }}
                                    </option>
                                @empty
                                    <option value="" disabled>Không có giảng viên</option>
                                @endforelse
                            </select>
                            <small class="text-muted d-block mt-2">
                                ℹ️ Tất cả sinh viên được chọn sẽ được phân công cho giảng viên này
                            </small>
                        </div>
                    </div>

                    <!-- Cột 2: Thống kê -->
                    <div class="col-lg-8 col-md-6">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <p class="text-muted small mb-1">Tổng sinh viên chưa phân công</p>
                                        <p class="h4 mb-0">
                                            <strong id="totalStudents">{{ count($students) }}</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <p class="text-muted small mb-1">Sinh viên được chọn</p>
                                        <p class="h4 mb-0">
                                            <strong id="selectedCount" class="text-primary">0</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Danh Sách Sinh Viên -->
                <div class="mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label class="form-label fw-bold mb-0">
                            <i class="fas fa-users text-primary me-2"></i>
                            Danh Sách Sinh Viên <span class="text-danger">*</span>
                        </label>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllBtn">
                                <i class="fas fa-check-double me-1"></i> Chọn tất cả
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllBtn">
                                <i class="fas fa-times me-1"></i> Bỏ chọn
                            </button>
                        </div>
                    </div>

                    <!-- Thanh tìm kiếm -->
                    <div class="mb-3">
                        <input type="text" class="form-control" id="searchInput" 
                               placeholder="🔍 Tìm kiếm theo MSSV hoặc tên sinh viên...">
                    </div>

                    <!-- Danh sách -->
                    <div class="student-list border rounded-2 p-3" style="max-height: 500px; overflow-y: auto;">
                        @forelse($students as $student)
                            <div class="form-check student-item mb-2">
                                <input class="form-check-input student-checkbox" type="checkbox" 
                                       name="mssv[]" value="{{ $student->mssv }}" 
                                       id="student_{{ $student->mssv }}"
                                       data-name="{{ strtolower($student->hoten) }}"
                                       data-mssv="{{ strtolower($student->mssv) }}">
                                <label class="form-check-label cursor-pointer w-100" for="student_{{ $student->mssv }}">
                                    <div class="d-flex justify-content-between align-items-center w-100">
                                        <div>
                                            <strong>{{ $student->mssv }}</strong> - {{ $student->hoten }}
                                        </div>
                                        <span class="badge bg-secondary">{{ $student->lop }}</span>
                                    </div>
                                </label>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p>Tất cả sinh viên đã được phân công</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Nút Hành Động -->
                <div class="mt-4 d-flex gap-2 justify-content-end">
                    <a href="{{ route('admin.assignments.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Quay Lại
                    </a>
                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                        <i class="fas fa-save me-1"></i> Phân Công
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .student-list {
        background-color: #f8f9fa;
        border-color: #dee2e6 !important;
    }

    .student-item {
        padding: 8px;
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    .student-item:hover {
        background-color: #e7f3ff;
    }

    .student-item input[type="checkbox"]:checked ~ label {
        color: #0d6efd;
        font-weight: 500;
    }

    .form-check-input {
        width: 1.2em;
        height: 1.2em;
        margin-top: 0.15em;
        cursor: pointer;
    }

    .form-check-label {
        cursor: pointer;
        margin-bottom: 0;
    }

    .form-select-lg {
        font-size: 1rem;
        padding: 0.75rem 1rem;
    }

    .card {
        transition: all 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .btn-outline-secondary:hover {
        background-color: #6c757d;
    }

    .badge {
        font-weight: 500;
        padding: 0.35em 0.65em;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.student-checkbox');
        const submitBtn = document.getElementById('submitBtn');
        const lecturerSelect = document.getElementById('lecturerSelect');
        const selectedCount = document.getElementById('selectedCount');
        const searchInput = document.getElementById('searchInput');
        const selectAllBtn = document.getElementById('selectAllBtn');
        const deselectAllBtn = document.getElementById('deselectAllBtn');

        // 🔄 Cập nhật trạng thái nút "Phân Công"
        function updateSubmitBtn() {
            const isLecturerSelected = lecturerSelect.value !== '';
            const isAnySelected = Array.from(checkboxes).some(cb => cb.checked);
            const count = Array.from(checkboxes).filter(cb => cb.checked).length;
            
            selectedCount.textContent = count;
            submitBtn.disabled = !isLecturerSelected || !isAnySelected;
        }

        // 📝 Sự kiện thay đổi checkbox
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSubmitBtn);
        });

        // 📝 Sự kiện thay đổi giảng viên
        lecturerSelect.addEventListener('change', updateSubmitBtn);

        // 🔍 Chức năng tìm kiếm
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            
            checkboxes.forEach(checkbox => {
                const parent = checkbox.closest('.student-item');
                const name = checkbox.dataset.name;
                const mssv = checkbox.dataset.mssv;
                
                if (name.includes(searchTerm) || mssv.includes(searchTerm)) {
                    parent.style.display = '';
                } else {
                    parent.style.display = 'none';
                }
            });
        });

        // ✅ Chọn tất cả
        selectAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            checkboxes.forEach(checkbox => {
                if (checkbox.closest('.student-item').style.display !== 'none') {
                    checkbox.checked = true;
                }
            });
            updateSubmitBtn();
        });

        // ❌ Bỏ chọn tất cả
        deselectAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            updateSubmitBtn();
        });

        // Kiểm tra form trước submit
        document.getElementById('assignmentForm').addEventListener('submit', function(e) {
            const lecturer = lecturerSelect.value;
            const selectedStudents = Array.from(checkboxes).filter(cb => cb.checked).length;

            if (!lecturer) {
                e.preventDefault();
                alert('Vui lòng chọn giảng viên!');
                return false;
            }

            if (selectedStudents === 0) {
                e.preventDefault();
                alert('Vui lòng chọn ít nhất 1 sinh viên!');
                return false;
            }

            if (!confirm(`Xác nhận phân công ${selectedStudents} sinh viên cho giảng viên này?`)) {
                e.preventDefault();
                return false;
            }
        });
    });
</script>

@endsection